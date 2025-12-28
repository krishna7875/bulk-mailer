<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $table = 'email_templates';

    /**
     * Mass-assignable fields
     * Attachment fields are allowed here because
     * they are filled internally (not user input)
     */
    protected $fillable = [
        'name',
        'subject',
        'body',
        'status',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'attachment_size' => 'integer',
    ];

    /**
     * Relationship:
     * One template can be used by many mappings
     */
    public function mappings()
    {
        return $this->hasMany(
            ShooterTargetMapping::class,
            'email_template_id'
        );
    }

    /**
     * Helper: does template have attachment?
     */
    public function hasAttachment(): bool
    {
        return !empty($this->attachment_path);
    }

    /**
     * Helper: full storage path (for Gmail attach)
     */
    public function attachmentFullPath(): ?string
    {
        if (!$this->hasAttachment()) {
            return null;
        }

        return storage_path('app/' . $this->attachment_path);
    }
}
