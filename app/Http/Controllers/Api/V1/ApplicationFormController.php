<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Application\ApplicationFormData;
use App\Http\Controllers\Controller;
use App\Models\ApplicationForm;
use App\Models\ApplicationDocument;
use App\Models\User;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationFormController extends Controller
{
    /**
     * Buscar el formulario por ID.
     * Helper method para reemplazar el route model binding.
     */
    private function findForm(string $id): ApplicationForm
    {
        return ApplicationForm::findOrFail($id);
    }

    /**
     * Display a listing of application forms.
     */
    public function index(Request $request)
    {
        $user = $request->user();

    $query = ApplicationForm::with(['client', 'client.createdBy', 'agent', 'documents']);

        // Filter based on user type
        if ($user->type === 'admin') {
            // Admin can see all forms
        } elseif ($user->type === 'agent') {
            // Agent can only see forms they created
            $query->where('agent_id', $user->id);
        } elseif ($user->type === 'client') {
            // Client can only see their own form
            $query->where('client_id', $user->id);
        }

        // Optional filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Obtener parámetros de paginación (por defecto 15 items por página)
        $perPage = $request->input('per_page', 15);
        $forms = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($forms);
    }

    /**
     * Obtener IDs de clientes que ya tienen application forms.
     * Útil para filtrar clientes disponibles al crear una nueva planilla.
     * IMPORTANTE: Devuelve TODOS los client_ids con forms, sin filtrar por agente,
     * porque un cliente solo puede tener UNA application form en total.
     */
    public function getClientsWithForms(Request $request)
    {
        // Obtener TODOS los client_id únicos que tienen application forms
        // Sin importar el agente, porque un cliente solo puede tener una form
        $clientIds = ApplicationForm::pluck('client_id')->unique()->values()->toArray();

        return response()->json([
            'client_ids' => $clientIds
        ]);
    }

    /**
     * Store a newly created application form.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $data = ApplicationFormData::from($request->all());

        // Only agents and admins can create application forms
        if ($user->type !== 'agent' && $user->type !== 'admin') {
            return response()->json([
                'error' => 'Solo los agentes y administradores pueden crear planillas de aplicación'
            ], 403);
        }

        // Validate that client exists and is a client type
        $client = User::where('id', $request->client_id)
                     ->where('type', 'client')
                     ->first();

        if (!$client) {
            return response()->json([
                'error' => 'Cliente no encontrado o no es un usuario tipo cliente'
            ], 404);
        }

        // Check if client already has an application form
        $existingForm = ApplicationForm::where('client_id', $client->id)->first();
        if ($existingForm) {
            return response()->json([
                'error' => 'Este cliente ya tiene una planilla de aplicación'
            ], 409);
        }

        try {
            $formData = array_filter($data->toArray(), function($value) {
                return $value !== null;
            });

            // Determinar el agent_id correcto:
            // 1. Si viene en el request, usarlo (ya validado que es el del cliente)
            // 2. Si no, obtenerlo del agente que creó al cliente (created_by)
            // 3. Si el cliente no tiene agente, usar el usuario actual como fallback
            $clientAgentId = $client->created_by;
            $agentId = $request->agent_id ?? $clientAgentId ?? $user->id;

            // Obtener el nombre del agente
            $agent = User::find($agentId);
            $agentName = $agent ? $agent->name : $user->name;

            $form = ApplicationForm::create([
                'client_id' => $client->id,
                'agent_id' => $agentId,
                'agent_name' => $agentName,
                ...$formData,
                'status' => $data->status ?? 'En Revisión',
                'confirmed' => $data->confirmed ?? false,
            ]);

            // Generar token de confirmación con expiración de 3 días
            $token = $form->generateConfirmationToken();

            // Generar PDF completo de la planilla automáticamente
            try {
                $pdfService = new PdfGeneratorService();
                $pdfPath = $pdfService->generateApplicationPdf($form);
                
                // Guardar el PDF en la misma ubicación que los documentos cargados (application_documents)
                $pdfFileName = "Planilla_Aplicacion_{$form->client->name}_{$form->id}.pdf";
                $publicPdfPath = "application_documents/{$pdfFileName}";
                
                // Copiar desde la ubicación temporal a la ubicación pública
                $tempPath = storage_path("app/{$pdfPath}");
                $publicFullPath = storage_path("app/public/{$publicPdfPath}");
                
                // Asegurar que el directorio existe
                if (!file_exists(dirname($publicFullPath))) {
                    mkdir(dirname($publicFullPath), 0755, true);
                }
                
                // Copiar el archivo
                copy($tempPath, $publicFullPath);
                $fileSize = filesize($publicFullPath);
                
                // Crear documento en la base de datos
                ApplicationDocument::create([
                    'application_form_id' => $form->id,
                    'uploaded_by' => $user->id,
                    'original_name' => $pdfFileName,
                    'file_name' => $pdfFileName,
                    'file_path' => $publicPdfPath,
                    'mime_type' => 'application/pdf',
                    'file_size' => $fileSize,
                    'document_type' => 'application_form'
                ]);

                // Opcional: eliminar el archivo temporal de pdfs/
                @unlink($tempPath);
                
            } catch (\Exception $e) {
                \Log::error('Error al generar PDF de aplicación: ' . $e->getMessage());
                // No fallar la creación de la planilla si falla el PDF
            }

            return response()->json([
                'message' => 'Planilla de aplicación creada exitosamente',
                'form' => $form->load(['client', 'agent', 'documents']),
                'confirmation_token' => $token,
                'token_expires_at' => $form->token_expires_at,
                'confirmation_link' => url("/confirm/{$token}")
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear la planilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified application form.
     */
    public function show(Request $request, string $application_form)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Check permissions
        if (!$form->canView($user)) {
            return response()->json([
                'error' => 'No tienes permisos para ver esta planilla'
            ], 403);
        }

        return response()->json($form->load(['client', 'agent', 'documents']));
    }

    /**
     * Update the specified application form.
     */
    public function update(Request $request, string $application_form)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Check permissions
        if (!$form->isEditableBy($user)) {
            return response()->json([
                'error' => 'No tienes permisos para editar esta planilla'
            ], 403);
        }

        try {
            $requestData = $request->all();
            
            // Si es un agente editando una planilla activa, puede incluir cambios del cliente
            if ($form->needsAdminApproval($user)) {
                // Separar cambios del cliente (prefijo client_) de cambios de la planilla
                $clientChanges = [];
                $formDataInput = [];
                
                foreach ($requestData as $key => $value) {
                    if (str_starts_with($key, 'client_')) {
                        // Guardar cambios del cliente con su prefijo para pending_changes
                        $clientChanges[$key] = $value;
                    } else {
                        // Datos de la planilla para validar con ApplicationFormData
                        $formDataInput[$key] = $value;
                    }
                }
                
                // Si hay cambios de la planilla, validarlos
                $formData = [];
                if (!empty($formDataInput)) {
                    $data = ApplicationFormData::from($formDataInput);
                    $formData = array_filter($data->toArray(), function($value) {
                        return $value !== null;
                    });
                }
                
                // Combinar ambos para guardar en pending_changes
                $allChanges = array_merge($formData, $clientChanges);
                
                // Guardar los cambios como pendientes
                $form->update([
                    'pending_changes' => $allChanges,
                    'has_pending_changes' => true,
                    'pending_changes_at' => now(),
                    'pending_changes_by' => $user->id
                ]);
                
                return response()->json([
                    'message' => 'Cambios guardados. Pendientes de aprobación del administrador',
                    'form' => $form->fresh(['client', 'agent', 'pendingChangesBy']),
                    'requires_approval' => true
                ]);
            }
            
            // Si es admin o una planilla no activa, actualizar directamente
            // (no debería haber campos client_ aquí, pero filtrarlos por seguridad)
            $formDataInput = array_filter($requestData, function($key) {
                return !str_starts_with($key, 'client_');
            }, ARRAY_FILTER_USE_KEY);
            
            $data = ApplicationFormData::from($formDataInput);
            $formData = array_filter($data->toArray(), function($value) {
                return $value !== null;
            });
            
            $form->update($formData);
            
            return response()->json([
                'message' => 'Planilla actualizada exitosamente',
                'form' => $form->fresh(['client', 'agent'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar la planilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm the application form.
     */
    public function confirm(Request $request, string $application_form)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Only the agent who created it can confirm
        if ($user->type !== 'agent' || $user->id !== $form->agent_id) {
            return response()->json([
                'error' => 'Solo el agente creador puede confirmar la planilla'
            ], 403);
        }

        $request->validate([
            'confirmed' => 'required|boolean'
        ]);

        $form->update([
            'confirmed' => $request->confirmed,
            'status' => $request->confirmed ? 'Activo' : 'En Revisión'
        ]);

        return response()->json([
            'message' => $request->confirmed ? 'Planilla confirmada exitosamente' : 'Confirmación removida',
            'form' => $form->fresh()
        ]);
    }

    /**
     * Update status of the application form (solo admin).
     */
    public function updateStatus(Request $request, string $application_form)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Solo admin puede cambiar status
        if (!$user->isAdmin()) {
            return response()->json([
                'error' => 'Solo el administrador puede cambiar el status'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:' . implode(',', [
                ApplicationForm::STATUS_PENDING,
                ApplicationForm::STATUS_ACTIVE,
                ApplicationForm::STATUS_INACTIVE,
                ApplicationForm::STATUS_REJECTED
            ]),
            'status_comment' => 'required|string|max:1000'
        ]);

        $form->update([
            'status' => $request->status,
            'status_comment' => $request->status_comment,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Status actualizado exitosamente',
            'form' => $form->fresh(['client', 'agent', 'reviewedBy'])
        ]);
    }
    
    /**
     * Approve pending changes (solo admin).
     */
    public function approvePendingChanges(Request $request, string $application_form)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Solo admin puede aprobar cambios
        if (!$user->isAdmin()) {
            return response()->json([
                'error' => 'Solo el administrador puede aprobar cambios'
            ], 403);
        }

        if (!$form->hasPendingChanges()) {
            return response()->json([
                'error' => 'No hay cambios pendientes para aprobar'
            ], 400);
        }

        try {
            // Guardar el agente que propuso los cambios antes de limpiarlos
            $agentId = $form->pending_changes_by;
            $changesData = $form->pending_changes;
            
            // Separar cambios del client (prefijo client_) de cambios de la planilla
            $clientChanges = [];
            $formChanges = [];
            
            foreach ($changesData as $key => $value) {
                if (str_starts_with($key, 'client_')) {
                    // Remover prefijo client_ y guardar para actualizar el usuario
                    $clientKey = str_replace('client_', '', $key);
                    $clientChanges[$clientKey] = $value;
                } else {
                    $formChanges[$key] = $value;
                }
            }
            
            // Aplicar los cambios de la planilla
            if (!empty($formChanges)) {
                $form->update($formChanges);
            }
            
            // Aplicar los cambios del cliente si hay
            if (!empty($clientChanges) && $form->client) {
                $form->client->update($clientChanges);
            }
            
            // Limpiar los cambios pendientes
            $form->update([
                'pending_changes' => null,
                'has_pending_changes' => false,
                'pending_changes_at' => null,
                'pending_changes_by' => null,
                'reviewed_by' => $user->id,
                'reviewed_at' => now()
            ]);

            // ✅ Registrar en el historial
            \App\Models\ApplicationFormHistory::create([
                'application_form_id' => $form->id,
                'action' => \App\Models\ApplicationFormHistory::ACTION_PENDING_APPROVED,
                'user_id' => $user->id,
                'comment' => 'Cambios aprobados por ' . $user->name,
                'metadata' => [
                    'agent_id' => $agentId,
                    'approved_by' => $user->name,
                    'approved_at' => now()->toDateTimeString(),
                    'changes' => $changesData,
                    'client_changes' => $clientChanges,
                    'form_changes' => $formChanges
                ]
            ]);

            return response()->json([
                'message' => 'Cambios aprobados y aplicados exitosamente',
                'form' => $form->fresh(['client', 'agent', 'reviewedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al aprobar los cambios: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Reject pending changes (solo admin).
     */
    public function rejectPendingChanges(Request $request, string $application_form)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Solo admin puede rechazar cambios
        if (!$user->isAdmin()) {
            return response()->json([
                'error' => 'Solo el administrador puede rechazar cambios'
            ], 403);
        }

        if (!$form->hasPendingChanges()) {
            return response()->json([
                'error' => 'No hay cambios pendientes para rechazar'
            ], 400);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        try {
            // Guardar el agente que propuso los cambios antes de limpiarlos
            $agentId = $form->pending_changes_by;
            
            // Actualizar la planilla con el rechazo
            $form->update([
                'pending_changes' => null,
                'has_pending_changes' => false,
                'pending_changes_at' => null,
                'pending_changes_by' => null,
                'status_comment' => 'Cambios rechazados: ' . $request->rejection_reason,
                'rejection_reason' => $request->rejection_reason, // ✅ Campo dedicado
                'rejected_at' => now(), // ✅ Timestamp del rechazo
                'reviewed_by' => $user->id,
                'reviewed_at' => now()
            ]);

            // ✅ Registrar en el historial
            \App\Models\ApplicationFormHistory::create([
                'application_form_id' => $form->id,
                'action' => \App\Models\ApplicationFormHistory::ACTION_PENDING_REJECTED,
                'user_id' => $user->id,
                'comment' => $request->rejection_reason,
                'metadata' => [
                    'agent_id' => $agentId,
                    'rejected_by' => $user->name,
                    'rejected_at' => now()->toDateTimeString()
                ]
            ]);

            return response()->json([
                'message' => 'Cambios rechazados exitosamente',
                'form' => $form->fresh(['client', 'agent', 'reviewedBy'])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al rechazar los cambios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload document to application form.
     */
    public function uploadDocument(Request $request, string $application_form)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Check permissions
        if (!$form->canView($user)) {
            return response()->json([
                'error' => 'No tienes permisos para subir documentos a esta planilla'
            ], 403);
        }

        // Validar que el archivo exista y sea válido
        $serverLimitValue = ini_get('upload_max_filesize') ?: '0';
        $serverLimitBytes = $this->convertIniSizeToBytes($serverLimitValue);
        $serverLimitLabel = $serverLimitBytes > 0 ? $this->formatFileSize($serverLimitBytes) : $serverLimitValue;

        // Log para debugging
        \Log::info('Upload limits check', [
            'upload_max_filesize' => $serverLimitValue,
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'hasFile' => $request->hasFile('document'),
            'filesError' => $_FILES['document']['error'] ?? 'no error key',
            'filesSize' => $_FILES['document']['size'] ?? 'no size key'
        ]);

        if (!$request->hasFile('document')) {
            $fileError = $_FILES['document']['error'] ?? null;
            if (in_array($fileError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                return response()->json([
                    'error' => 'El archivo excede el límite configurado en el servidor (' . $serverLimitLabel . ').'
                ], 422);
            }

            return response()->json([
                'error' => 'No se recibió el archivo a subir. Intenta nuevamente.'
            ], 422);
        }

        $file = $request->file('document');

        if (!$file->isValid()) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el límite configurado en el servidor (' . $serverLimitLabel . ').',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño permitido por el formulario.',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente. Intenta nuevamente.',
                UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo en la solicitud.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal en el servidor. Contacta al administrador.',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco del servidor.',
                UPLOAD_ERR_EXTENSION => 'Una extensión del servidor detuvo la subida del archivo.'
            ];

            $errorCode = $file->getError();

            return response()->json([
                'error' => $errorMessages[$errorCode] ?? 'No se pudo completar la carga del archivo. Intenta nuevamente.'
            ], 422);
        }

        $request->validate([
            'document_type' => 'nullable|string|max:100'
        ]);
        
        // Validar extensión y MIME type
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();
        $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf', 'mp3', 'wma'];
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'audio/mpeg', 'audio/mp3', 'audio/x-ms-wma'];
        
        if (!in_array($extension, $allowedExtensions) && !in_array($mimeType, $allowedMimes)) {
            return response()->json([
                'error' => 'Tipo de archivo no permitido. Solo: PDF, JPG, PNG, MP3, WMA'
            ], 422);
        }
        
        // Determinar si es audio
        $isAudio = str_starts_with($mimeType, 'audio/') || in_array($extension, ['mp3', 'wma']);
        
        // Validar tamaño según tipo de archivo
        $maxSize = $isAudio ? 15360 : 5120; // 15MB para audio, 5MB para otros (en KB)
        $maxSizeLabel = $isAudio ? '15MB' : '5MB';
        
        if ($file->getSize() > ($maxSize * 1024)) {
            return response()->json([
                'error' => "El archivo es demasiado grande. Máximo {$maxSizeLabel} para " . ($isAudio ? 'audios' : 'imágenes/PDFs')
            ], 422);
        }

        try {
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . uniqid() . '.' . $extension;
            
            // Guardar en storage/public/application_documents
            $filePath = $file->storeAs('application_documents', $fileName, 'public');

            // Determinar el tipo de documento automáticamente si no se proporciona
            $documentType = $request->document_type;
            if (!$documentType) {
                $mimeType = $file->getMimeType();
                if (str_starts_with($mimeType, 'image/')) {
                    $documentType = 'imagen';
                } elseif ($mimeType === 'application/pdf') {
                    $documentType = 'pdf';
                } elseif (str_starts_with($mimeType, 'audio/')) {
                    $documentType = 'audio';
                } else {
                    $documentType = 'documento';
                }
            }

            $document = $form->documents()->create([
                'uploaded_by' => $user->id,
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'document_type' => $documentType
            ]);

            // Agregar propiedades calculadas para el frontend
            $document->file_url = asset('storage/' . $filePath);
            $document->is_image = str_starts_with($document->mime_type, 'image/');
            $document->is_pdf = $document->mime_type === 'application/pdf';
            $document->is_audio = str_starts_with($document->mime_type, 'audio/');
            $document->file_size_formatted = $this->formatFileSize($document->file_size);

            return response()->json([
                'message' => 'Documento subido exitosamente',
                'document' => $document
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al subir el documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convert ini size notation (e.g. 2M, 1G) to bytes.
     */
    private function convertIniSizeToBytes(string $size): int
    {
        $size = trim($size);

        if ($size === '') {
            return 0;
        }

        $unit = strtolower(substr($size, -1));
        $number = (float) $size;

        switch ($unit) {
            case 'g':
                $number *= 1024;
                // no break
            case 'm':
                $number *= 1024;
                // no break
            case 'k':
                $number *= 1024;
                break;
        }

        return (int) round($number);
    }

    /**
     * Format file size to human readable format
     */
    private function formatFileSize($bytes): string
    {
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

    /**
     * Delete document from application form.
     */
    public function deleteDocument(Request $request, string $application_form, $documentId)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        $document = $form->documents()->find($documentId);
        if (!$document) {
            return response()->json(['error' => 'Documento no encontrado'], 404);
        }

        // Only admin or the uploader can delete
        if ($user->type !== 'admin' && $document->uploaded_by !== $user->id) {
            return response()->json([
                'error' => 'No tienes permisos para eliminar este documento'
            ], 403);
        }

        try {
            // Eliminar el archivo físico del servidor
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            return response()->json([
                'message' => 'Documento eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar el documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * View document (inline display)
     */
    public function viewDocument(Request $request, string $application_form, $documentId)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Check permissions
        if (!$form->canView($user)) {
            return response()->json([
                'error' => 'No tienes permisos para ver documentos de esta planilla'
            ], 403);
        }

        $document = $form->documents()->find($documentId);
        if (!$document) {
            return response()->json(['error' => 'Documento no encontrado'], 404);
        }

        // Verificar que el archivo existe
        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['error' => 'Archivo no encontrado en el servidor'], 404);
        }

        $filePath = Storage::disk('public')->path($document->file_path);
        return response()->file($filePath);
    }

    /**
     * Download document
     */
    public function downloadDocument(Request $request, string $application_form, $documentId)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Check permissions
        if (!$form->canView($user)) {
            return response()->json([
                'error' => 'No tienes permisos para descargar documentos de esta planilla'
            ], 403);
        }

        $document = $form->documents()->find($documentId);
        if (!$document) {
            return response()->json(['error' => 'Documento no encontrado'], 404);
        }

        // Verificar que el archivo existe
        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['error' => 'Archivo no encontrado en el servidor'], 404);
        }

        $filePath = Storage::disk('public')->path($document->file_path);
        return response()->download($filePath, $document->original_name);
    }

    /**
     * Remove the specified application form.
     */
    public function destroy(Request $request, string $application_form)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Only admin can delete application forms
        if ($user->type !== 'admin') {
            return response()->json([
                'error' => 'Solo el administrador puede eliminar planillas'
            ], 403);
        }

        try {
            // Delete associated documents first (files will be deleted via model events)
            $form->documents()->delete();
            $form->delete();

            return response()->json([
                'message' => 'Planilla eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar la planilla: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Renovar el token de confirmación (extender 3 días más)
     * Solo agent creador o admin pueden renovar
     */
    public function renewToken(Request $request, string $application_form)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Verificar permisos
        if ($user->type !== 'admin' && $form->agent_id !== $user->id) {
            return response()->json([
                'error' => 'No autorizado para renovar el token de esta planilla'
            ], 403);
        }

        // No se puede renovar si ya fue confirmada
        if ($form->isConfirmedByClient()) {
            return response()->json([
                'error' => 'No se puede renovar el token de una planilla ya confirmada',
                'confirmed_at' => $form->confirmed_at
            ], 409);
        }

        try {
            $token = $form->renewToken();

            return response()->json([
                'message' => 'Token renovado exitosamente',
                'token' => $token,
                'expires_at' => $form->token_expires_at,
                'confirmation_link' => url("/confirm/{$token}")
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al renovar el token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get history of changes for an application form (solo admin y agent creador).
     */
    public function getHistory(Request $request, string $application_form)
    {
        $user = $request->user();
        $form = $this->findForm($application_form);

        // Check permissions: admin o agent creador pueden ver el historial
        if (!$user->isAdmin() && $form->agent_id !== $user->id) {
            return response()->json([
                'error' => 'No autorizado para ver el historial'
            ], 403);
        }

        try {
            $history = $form->history()->with('user:id,name,email')->get();

            return response()->json([
                'history' => $history
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener el historial: ' . $e->getMessage()
            ], 500);
        }
    }
}

