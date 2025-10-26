<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApplicationForm extends Model
{
    protected $fillable = [
        // Application Data
        'client_id', 'agent_id', 'agent_name', 'applicant_name', 'dob', 'address',
        'unit_apt', 'city', 'state', 'zip_code', 'phone', 'phone2', 'email', 'gender',
        'ssn', 'legal_status', 'document_number', 'insurance_company', 'insurance_plan',
        'subsidy', 'final_cost', 'employment_type', 'employment_company_name',
        'work_phone', 'wages', 'wages_frequency',

        // Póliza Data
        'poliza_number', 'poliza_category', 'poliza_amount', 'poliza_payment_day', 'poliza_beneficiary',

        // Person 1 Data
        'person1_name', 'person1_relation', 'person1_is_applicant', 'person1_legal_status',
        'person1_document_number', 'person1_dob', 'person1_company_name', 'person1_ssn',
        'person1_gender', 'person1_wages', 'person1_frequency',

        // Person 2 Data
        'person2_name', 'person2_relation', 'person2_is_applicant', 'person2_legal_status',
        'person2_document_number', 'person2_dob', 'person2_company_name', 'person2_ssn',
        'person2_gender', 'person2_wages', 'person2_frequency',

        // Person 3 Data
        'person3_name', 'person3_relation', 'person3_is_applicant', 'person3_legal_status',
        'person3_document_number', 'person3_dob', 'person3_company_name', 'person3_ssn',
        'person3_gender', 'person3_wages', 'person3_frequency',

        // Person 4 Data
        'person4_name', 'person4_relation', 'person4_is_applicant', 'person4_legal_status',
        'person4_document_number', 'person4_dob', 'person4_company_name', 'person4_ssn',
        'person4_gender', 'person4_wages', 'person4_frequency',

        // Payment Method Data
        'card_type', 'card_number', 'card_expiration', 'card_cvv',
        'bank_name', 'bank_routing', 'bank_account',

        // Status and Confirmation
        'status', 'status_comment', 'confirmed', 'reviewed_by', 'reviewed_at'
    ];

    // Status constants
    const STATUS_PENDING = 'pendiente';
    const STATUS_ACTIVE = 'activo';
    const STATUS_INACTIVE = 'inactivo';
    const STATUS_REJECTED = 'rechazado';

    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_REJECTED,
        ];
    }

    protected $casts = [
        'dob' => 'date',
        'person1_dob' => 'date',
        'person2_dob' => 'date',
        'person3_dob' => 'date',
        'person4_dob' => 'date',
        'person1_is_applicant' => 'boolean',
        'person2_is_applicant' => 'boolean',
        'person3_is_applicant' => 'boolean',
        'person4_is_applicant' => 'boolean',
        'confirmed' => 'boolean',
        'subsidy' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'wages' => 'decimal:2',
        'person1_wages' => 'decimal:2',
        'person2_wages' => 'decimal:2',
        'person3_wages' => 'decimal:2',
        'person4_wages' => 'decimal:2',
        'poliza_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'application_form_id');
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canChangeStatus(User $user): bool
    {
        // Solo admin puede cambiar el status
        return $user->isAdmin();
    }

    public function isEditableBy(User $user): bool
    {
        // Admin puede editar todo
        if ($user->type === 'admin') {
            return true;
        }

        // Agent solo puede editar si no está confirmado y es el creador
        if ($user->type === 'agent' && $user->id === $this->agent_id) {
            return !$this->confirmed;
        }

        return false;
    }

    public function canView(User $user): bool
    {
        // Admin puede ver todo
        if ($user->type === 'admin') {
            return true;
        }

        // Agent puede ver sus propias planillas
        if ($user->type === 'agent' && $user->id === $this->agent_id) {
            return true;
        }

        // Client puede ver su propia planilla
        if ($user->type === 'client' && $user->id === $this->client_id) {
            return true;
        }

        return false;
    }
}
