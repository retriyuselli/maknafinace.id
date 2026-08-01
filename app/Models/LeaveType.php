<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class LeaveType extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'keterangan',
        'max_days_per_year',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'max_days_per_year'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('leave_type');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
