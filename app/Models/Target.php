<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    protected $fillable = [
        'email',
        'name',
        'metadata',
        'import_batch',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function mappings()
    {
        return $this->hasMany(ShooterTargetMapping::class);
    }
}
