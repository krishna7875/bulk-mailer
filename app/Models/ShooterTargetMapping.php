<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShooterTargetMapping extends Model
{
     protected $fillable = [
        'shooter_id',
        'target_id',
        'status',
        'assigned_at',
        'attempted_at',
        'sent_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'attempted_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function shooter()
    {
        return $this->belongsTo(Shooter::class);
    }

    public function target()
    {
        return $this->belongsTo(Target::class);
    }

    public function emailTemplate()
    {
        return $this->belongsTo(
            EmailTemplate::class,
            'email_template_id'
        );
    }

}
