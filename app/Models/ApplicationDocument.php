<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ApplicationDocument extends Model
{
    protected $fillable = [
        'application_form_id',
        'uploaded_by',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'document_type'
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // Relationships
    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class, 'application_form_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Helper methods
    public function getFileUrl(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    // Delete file when model is deleted
    protected static function booted()
    {
        static::deleting(function ($document) {
            Storage::delete($document->file_path);
        });
    }
}
