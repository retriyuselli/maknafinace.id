<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\OrderStatus;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * SECURITY: Hanya field aman yang boleh di-mass assign
     * Field sensitif harus diupdate secara eksplisit dengan validation
     *
     * @var list<string>
     */
    protected $fillable = [
        // Basic Info - Safe for mass assignment
        'name',
        'email',
        'password', // Required field, tapi akan di-hash otomatis

        // Personal Info - Perlu validation ketat
        'phone_number',
        'address',
        'date_of_birth',
        'gender',
        'hire_date',
        'last_working_date',
        'expire_date',
        'department',
        'annual_leave_quota',
        'status',
        'gaji_pokok_base',
        'tunjangan_base',

        // Avatar - Allowed for file upload
        'avatar_url',
        'signature_url',

        // Documents & Notes - Allowed for upload and notes
        'contract_document',
        'identity_document',
        'additional_documents',
        'notes',
        'emergency_contact',
    ];

    // CATATAN: $guarded dihapus — Laravel hanya menggunakan $fillable ATAU $guarded, tidak keduanya.
    // Ketika $fillable sudah didefinisikan, $guarded sepenuhnya diabaikan (tidak memberikan proteksi apapun).
    // Proteksi field sensitif sudah ditangani oleh $fillable di atas (hanya field yang terdaftar di sana
    // yang bisa di-mass assign) ditambah method updatePassword/updateRole/updateStatus di bawah.

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'avatar',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'status', 'expire_date'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('user');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'expire_date' => 'datetime',
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'last_working_date' => 'date',
            'additional_documents' => 'array',
            'gaji_pokok_base' => 'integer',
            'tunjangan_base' => 'integer',
        ];
    }

    /**
     * 🔐 SECURE METHODS FOR UPDATING PROTECTED FIELDS
     * Method ini memastikan hanya user dengan permission yang tepat yang bisa update field sensitif
     */

    /**
     * Update password dengan validation dan hashing
     */
    public function updatePassword(string $newPassword, User $updatedBy): bool
    {
        // Cek authorization: user sendiri atau admin
        if ($updatedBy->id !== $this->id && ! $updatedBy->hasRole(['super_admin', 'admin'])) {
            abort(403, 'Unauthorized to change password');
        }

        // Validate password strength
        if (strlen($newPassword) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters');
        }

        // Cukup assign plain text — cast 'hashed' di casts() otomatis menghash
        $this->password = $newPassword;

        // Log activity
        Log::info('Password updated', [
            'user_id' => $this->id,
            'updated_by' => $updatedBy->id,
            'timestamp' => now(),
        ]);

        return $this->save();
    }

    /**
     * Update role/status - hanya admin yang bisa
     */
    public function updateRole(string $role, User $updatedBy): bool
    {
        if (! $updatedBy->hasRole(['super_admin', 'admin'])) {
            abort(403, 'Only admin can change user roles');
        }

        $oldRoles = $this->getRoleNames()->toArray();
        $this->syncRoles([$role]);

        Log::info('Roles updated', [
            'user_id' => $this->id,
            'updated_by' => $updatedBy->id,
            'old_roles' => $oldRoles,
            'new_roles' => [$role],
            'timestamp' => now(),
        ]);

        return true;
    }

    /**
     * Update employment info - hanya HR/Admin
     */
    public function updateEmploymentInfo(array $data, User $updatedBy): bool
    {
        if (! $updatedBy->hasRole(['super_admin', 'admin', 'hr'])) {
            abort(403, 'Only HR/Admin can update employment info');
        }

        $allowedFields = ['hire_date', 'last_working_date', 'department', 'annual_leave_quota'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        foreach ($updateData as $field => $value) {
            $this->{$field} = $value;
        }

        // Log activity
        Log::info('Employment info updated', [
            'user_id' => $this->id,
            'updated_by' => $updatedBy->id,
            'updated_fields' => array_keys($updateData),
            'timestamp' => now(),
        ]);

        return $this->save();
    }

    /**
     * Update status - dengan audit trail
     */
    public function updateStatus(string $status, User $updatedBy, ?string $reason = null): bool
    {
        if (! $updatedBy->hasRole(['super_admin', 'admin', 'hr'])) {
            abort(403, 'Unauthorized to change user status');
        }

        $oldStatus = $this->status;
        $this->status = $status;

        // Log activity dengan reason
        Log::info('User status updated', [
            'user_id' => $this->id,
            'updated_by' => $updatedBy->id,
            'old_status' => $oldStatus,
            'new_status' => $status,
            'reason' => $reason,
            'timestamp' => now(),
        ]);

        return $this->save();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->avatar_url) {
            return Storage::url($this->avatar_url);
        }

        return null;
    }

    /**
     * Get the avatar URL for frontend display
     */
    public function getAvatarAttribute(): ?string
    {
        if ($this->avatar_url) {
            return Storage::url($this->avatar_url);
        }

        return null;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function firstEmployee(): HasOne
    {
        return $this->hasOne(Employee::class)->oldestOfMany();
    }

    public function latestEmployee(): HasOne
    {
        return $this->hasOne(Employee::class)->latestOfMany();
    }

    public function activeEmployee(): HasOne
    {
        return $this->hasOne(Employee::class)->whereNull('date_of_out');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function statuses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Status::class, 'status_user');
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'created_by');
    }

    // Computed attributes for HR data
    public function getClosingAttribute()
    {
        return $this->orders->sum('total_price');
    }

    public function getAmCountAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getTotalRevenueAttribute()
    {
        return $this->orders()->where('is_paid', true)->sum('total_price');
    }

    public function getPendingOrdersCountAttribute()
    {
        return $this->orders()->where('status', OrderStatus::Pending)->count();
    }

    public function getCompletedOrdersCountAttribute()
    {
        return $this->orders()->where('status', OrderStatus::Done)->count();
    }

    public function getProcessingOrdersCountAttribute()
    {
        return $this->orders()->where('status', OrderStatus::Processing)->count();
    }

    public function getCancelledOrdersCountAttribute()
    {
        return $this->orders()->where('status', OrderStatus::Cancelled)->count();
    }

    public function getAverageOrderValueAttribute()
    {
        $ordersCount = $this->orders()->count();
        if ($ordersCount === 0) {
            return 0;
        }

        return $this->orders()->sum('total_price') / $ordersCount;
    }

    public function getMonthlyRevenueAttribute()
    {
        return $this->orders()
            ->where('is_paid', true)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_price');
    }

    public function getYearlyRevenueAttribute()
    {
        return $this->orders()
            ->where('is_paid', true)
            ->whereYear('created_at', now()->year)
            ->sum('total_price');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->status === 'terminated' || $this->status === 'inactive') {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        return true;
    }

    /**
     * Check if user account is expired
     */
    public function isExpired(): bool
    {
        if (! $this->expire_date) {
            return false;
        }

        return Carbon::now()->greaterThan($this->expire_date);
    }

    /**
     * Check if user account will expire soon (within 7 days)
     */
    public function isExpiringSoon(): bool
    {
        if (! $this->expire_date) {
            return false;
        }

        // expire_date sudah di-cast ke datetime, jadi sudah Carbon instance
        $expireDate = $this->expire_date;
        $sevenDaysFromNow = Carbon::now()->addDays(7);

        return Carbon::now()->lessThan($expireDate) && $expireDate->lessThanOrEqualTo($sevenDaysFromNow);
    }

    /**
     * Get days until expiration
     */
    public function getDaysUntilExpiration(): ?int
    {
        if (! $this->expire_date) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($this->expire_date, false);
    }

    // new fields
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function logAbsensis(): HasMany
    {
        return $this->hasMany(LogAbsensi::class);
    }

    public function penugasanJadwals(): HasMany
    {
        return $this->hasMany(PenugasanJadwal::class);
    }

    public function koreksiAbsensis(): HasMany
    {
        return $this->hasMany(KoreksiAbsensi::class);
    }

    public function pengajuanLemburs(): HasMany
    {
        return $this->hasMany(PengajuanLembur::class);
    }

    /**
     * Get the employee ID attribute
     * Format: EMP-0001, EMP-0002, etc.
     */
    public function getEmployeeIdAttribute()
    {
        return 'EMP-'.str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    // Document Management System
    public function createdDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    public function documentApprovals(): HasMany
    {
        return $this->hasMany(DocumentApproval::class);
    }

    public function documentRecipients(): HasMany
    {
        return $this->hasMany(DocumentRecipient::class);
    }
}
