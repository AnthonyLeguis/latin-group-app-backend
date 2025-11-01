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
        'subsidy', 'final_cost', // Label: "Costo de la Prima"
        'employment_type', 'employment_company_name',
        'work_phone', 'wages', 'wages_frequency',

        // Póliza Data
        'poliza_number', 
        'poliza_category', // Label: "Póliza Dental"
        'poliza_amount', // Label: "Monto Prima Dental"
        'poliza_payment_day', 
        'poliza_beneficiary',

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

        // Person 5 Data
        'person5_name', 'person5_relation', 'person5_is_applicant', 'person5_legal_status',
        'person5_document_number', 'person5_dob', 'person5_company_name', 'person5_ssn',
        'person5_gender', 'person5_wages', 'person5_frequency',

        // Person 6 Data
        'person6_name', 'person6_relation', 'person6_is_applicant', 'person6_legal_status',
        'person6_document_number', 'person6_dob', 'person6_company_name', 'person6_ssn',
        'person6_gender', 'person6_wages', 'person6_frequency',

        // Payment Method Data
        'card_type', 'card_number', 'card_expiration', 'card_cvv',
        'bank_name', 'bank_routing', 'bank_account',

        // Status and Confirmation
        'status', 'status_comment', 'confirmed', 'reviewed_by', 'reviewed_at',
        
        // Rejection tracking (cuando admin rechaza cambios pendientes)
        'rejection_reason', 'rejected_at',
        
        // Pending Changes (for Active forms edited by agents)
        'pending_changes', 'has_pending_changes', 'pending_changes_at', 'pending_changes_by',
        
        // PDF Sheet (generated document)
        'pdf_sheet', // Ruta relativa del PDF generado (ej: 'pdf_sheets/123/form_456.pdf')
        'pdf_path', // Ruta del PDF de confirmación generado
        
        // Confirmation Token (for client confirmation via link)
        'confirmation_token', 'token_expires_at', 'confirmed_at'
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
        'person5_dob' => 'date',
        'person6_dob' => 'date',
        'person1_is_applicant' => 'boolean',
        'person2_is_applicant' => 'boolean',
        'person3_is_applicant' => 'boolean',
        'person4_is_applicant' => 'boolean',
        'person5_is_applicant' => 'boolean',
        'person6_is_applicant' => 'boolean',
        'confirmed' => 'boolean',
        'subsidy' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'wages' => 'decimal:2',
        'person1_wages' => 'decimal:2',
        'person2_wages' => 'decimal:2',
        'person3_wages' => 'decimal:2',
        'person4_wages' => 'decimal:2',
        'person5_wages' => 'decimal:2',
        'person6_wages' => 'decimal:2',
        'poliza_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'has_pending_changes' => 'boolean',
        'pending_changes' => 'array', // JSON será convertido a array
        'pending_changes_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
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
    
    public function pendingChangesBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pending_changes_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'application_form_id');
    }

    /**
     * Relación con el historial de cambios
     */
    public function history(): HasMany
    {
        return $this->hasMany(ApplicationFormHistory::class)->orderBy('created_at', 'desc');
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
        // Admin puede editar todo directamente
        if ($user->type === 'admin') {
            return true;
        }

        // Agent puede editar sus propias planillas
        // Si está activa, los cambios quedarán pendientes de aprobación
        if ($user->type === 'agent' && $user->id === $this->agent_id) {
            return true;
        }

        return false;
    }
    
    public function hasPendingChanges(): bool
    {
        return $this->has_pending_changes === true;
    }
    
    public function needsAdminApproval(User $user): bool
    {
        // Si el agente edita una planilla activa, necesita aprobación del admin
        return $user->type === 'agent' && $this->status === self::STATUS_ACTIVE;
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

    // ==================== CONFIRMATION TOKEN METHODS ====================

    /**
     * Generar un token único de confirmación con expiración de 3 días
     */
    public function generateConfirmationToken(): string
    {
        $this->confirmation_token = \Illuminate\Support\Str::random(64);
        $this->token_expires_at = now()->addDays(3);
        $this->save();
        
        return $this->confirmation_token;
    }

    /**
     * Verificar si el token es válido (existe y no ha expirado)
     */
    public function hasValidToken(): bool
    {
        return $this->confirmation_token !== null 
            && $this->token_expires_at !== null 
            && !$this->isTokenExpired();
    }

    /**
     * Verificar si el token ha expirado
     */
    public function isTokenExpired(): bool
    {
        if ($this->token_expires_at === null) {
            return true;
        }
        
        return now()->isAfter($this->token_expires_at);
    }

    /**
     * Invalidar el token (después de confirmar o por seguridad)
     */
    public function invalidateToken(): void
    {
        $this->confirmation_token = null;
        $this->token_expires_at = null;
        $this->save();
    }

    /**
     * Renovar el token (extender 3 días más desde ahora)
     */
    public function renewToken(): string
    {
        if ($this->confirmation_token) {
            // Extender el token existente
            $this->token_expires_at = now()->addDays(3);
            $this->save();
            return $this->confirmation_token;
        }
        
        // Si no hay token, generar uno nuevo
        return $this->generateConfirmationToken();
    }

    /**
     * Confirmar la planilla (cliente presionó "Aceptar documento")
     */
    public function confirmByClient(): void
    {
        $this->confirmed = true;
        $this->confirmed_at = now();
        $this->invalidateToken(); // Token de un solo uso
        $this->save();

        // Cargar relación del agente antes de generar PDF
        $this->load('agent');

        // Generar PDF automáticamente
        try {
            $pdfGenerator = app(\App\Services\PdfGeneratorService::class);
            $pdfPath = $pdfGenerator->generateConfirmationPdf($this);
            
            // Guardar la ruta del PDF
            $this->update(['pdf_path' => $pdfPath]);
            
            \Log::info("✅ PDF generado exitosamente para planilla #{$this->id}", [
                'pdf_path' => $pdfPath,
                'client_id' => $this->client_id,
                'agent_id' => $this->agent_id,
                'agent_name' => $this->agent ? $this->agent->name : $this->agent_name
            ]);
        } catch (\Exception $e) {
            \Log::error("❌ Error al generar PDF para planilla #{$this->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Verificar si ya fue confirmada por el cliente
     */
    public function isConfirmedByClient(): bool
    {
        return $this->confirmed === true && $this->confirmed_at !== null;
    }
}
