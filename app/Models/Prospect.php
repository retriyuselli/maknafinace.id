<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Prospect extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name_event',
        'name_cpp',
        'name_cpw',
        'address',
        'phone',
        'date_lamaran',
        'time_lamaran',
        'date_akad',
        'time_akad',
        'date_resepsi',
        'time_resepsi',
        'venue',
        'total_penawaran',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'date_lamaran' => 'date',
        'date_akad' => 'date',
        'date_resepsi' => 'date',
        'total_penawaran' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        // Prevent deletion if prospect has associated orders
        static::deleting(function ($prospect) {
            if ($prospect->orders()->exists()) {
                throw new Exception("Cannot delete prospect '{$prospect->name_event}' because it has associated orders.");
            }
        });
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name_event', 'name_cpp', 'name_cpw', 'phone', 'date_akad', 'date_resepsi'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('prospect');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function latestOrder()
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
