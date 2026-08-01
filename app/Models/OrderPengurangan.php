<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class OrderPengurangan extends Model
{
    use LogsActivity;

    //
    protected $fillable = [
        'order_id',
        'total_pengurangan',
        'description',
        'notes',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'total_pengurangan', 'description'])
            ->setDescriptionForEvent(fn (string $eventName) => "{$eventName}")
            ->useLogName('order_pengurangan');
    }
}
