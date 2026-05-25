<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class LeaveBalanceHistory extends Model
{
    use LogsActivity;

    protected $fillable = [
        'leave_balance_id',
        'amount',
        'transaction_date',
        'reason',
        'created_by',
        'status',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['leave_balance_id', 'amount', 'transaction_date', 'reason', 'status'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('leave_balance_history');
    }

    public function leaveBalance()
    {
        return $this->belongsTo(LeaveBalance::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
