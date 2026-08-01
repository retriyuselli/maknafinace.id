<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Status extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'status_name',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status_name', 'status_user'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('status');
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'status_user');
    }
}
