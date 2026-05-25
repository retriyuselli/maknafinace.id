<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Industry extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'industry_name',
        'description',
        'is_active',
    ];

    protected $dates = ['deleted_at'];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['industry_name', 'is_active'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('industry');
    }

    public function prospects()
    {
        return $this->hasMany(Prospect::class);
    }

    public function prospectApps()
    {
        return $this->hasMany(ProspectApp::class);
    }
}
