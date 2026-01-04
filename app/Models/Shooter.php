<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        'gmail_access_token',
        'gmail_refresh_token',
        'gmail_token_expires_at',
        'gmail_connected_at'
    ];

    protected $casts = [
        'refresh_token' => 'encrypted',
        'last_quota_date' => 'date',
        'gmail_token_expires_at' => 'datetime',
        'gmail_connected_at'     => 'datetime',
    ];

    public function mappings()
    {
        return $this->hasMany(ShooterTargetMapping::class);
    }

    public function getGmailStatusAttribute(): string
    {
        if (!$this->gmail_refresh_token) {
            return 'not_connected';
        }

        if (!$this->gmail_token_expires_at) {
            return 'expired';
        }

        if (Carbon::parse($this->gmail_token_expires_at)->isPast()) {
            return 'expired';
        }

        return 'connected';
    }

}
