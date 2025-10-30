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

    protected $appends = [
        'file_url',
        'file_size_formatted',
        'is_image',
        'is_pdf',
        'is_audio',
        'uploaded_at'
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

    // Accessors (computed properties)
    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getIsAudioAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    public function getUploadedAtAttribute(): string
    {
        return $this->created_at->toIso8601String();
    }

    // Helper methods (for internal use)
    public function getFileUrl(): string
    {
        return $this->file_url;
    }

    public function getFileSizeFormatted(): string
    {
        return $this->file_size_formatted;
    }

    public function isImage(): bool
    {
        return $this->is_image;
    }

    public function isPdf(): bool
    {
        return $this->is_pdf;
    }

    public function isAudio(): bool
    {
        return $this->is_audio;
    }

    // Delete file when model is deleted
    protected static function booted()
    {
        static::deleting(function ($document) {
            Storage::delete($document->file_path);
        });
    }
}
