<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Log;

class EmailTemplateAttachmentService
{
    /**
     * Allowed file extensions
     */
    protected array $allowedExtensions = [ 'pdf', 'png', 'jpg', 'jpeg', 'svg', 'doc', 'docx',];

    /**
     * Max size in KB (10 MB)
     */
    protected int $maxSizeKb = 10240;

    /**
     * Handle attachment upload and return metadata
     */
    public function handleUpload(UploadedFile $file): array
    {
        $this->validateFile($file);

        $storedPath = $file->store(
            'email-attachments/templates'
        );

        return [
            'attachment_path' => $storedPath,
            'attachment_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getMimeType(),
            'attachment_size' => $file->getSize(),
        ];
    }

    /**
     * Validate attachment
     */
    protected function validateFile(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $this->allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'attachment' => 'Invalid attachment type.',
            ]);
        }

        if (($file->getSize() / 1024) > $this->maxSizeKb) {
            throw ValidationException::withMessages([
                'attachment' => 'Attachment size must not exceed 10 MB.',
            ]);
        }
    }

    /**
     * Remove old attachment if replaced
     */
    public function deleteIfExists(?string $path): void
    {
        if ($path && Storage::exists($path)) {
            Storage::delete($path);
        }
    }
}
