<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationFormHistory extends Model
{
    /**
     * Disable updated_at timestamp (solo usamos created_at)
     */
    const UPDATED_AT = null;

    /**
     * The table associated with the model.
     */
    protected $table = 'application_form_history';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'application_form_id',
        'action',
        'user_id',
        'comment',
        'metadata',
        'old_status',
        'new_status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relación con la planilla
     */
    public function applicationForm(): BelongsTo
    {
        return $this->belongsTo(ApplicationForm::class);
    }

    /**
     * Relación con el usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Acciones disponibles
     */
    public const ACTION_STATUS_CHANGED = 'status_changed';
    public const ACTION_PENDING_PROPOSED = 'pending_changes_proposed';
    public const ACTION_PENDING_APPROVED = 'pending_changes_approved';
    public const ACTION_PENDING_REJECTED = 'pending_changes_rejected';
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
}

