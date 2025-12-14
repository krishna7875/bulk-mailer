<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shooter extends Model
{
   protected $fillable = [
        'name',
        'email',
        'description',
        'daily_quota',
        'sent_today',
        'last_quota_date',
        'refresh_token',
        'status',
    ];

    protected $casts = [
        'refresh_token' => 'encrypted',
        'last_quota_date' => 'date',
    ];

    public function mappings()
    {
        return $this->hasMany(ShooterTargetMapping::class);
    }

}
