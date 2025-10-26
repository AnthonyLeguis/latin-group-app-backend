<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Application\ApplicationFormData;
use App\Http\Controllers\Controller;
use App\Models\ApplicationForm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationFormController extends Controller
{
    /**
     * Display a listing of application forms.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ApplicationForm::with(['client', 'agent', 'documents']);

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

        $forms = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($forms);
    }

    /**
     * Store a newly created application form.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $data = ApplicationFormData::from($request->all());

        // Only agents can create application forms
        if ($user->type !== 'agent') {
            return response()->json([
                'error' => 'Solo los agentes pueden crear planillas de aplicación'
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

            $form = ApplicationForm::create([
                'client_id' => $client->id,
                'agent_id' => $user->id,
                'agent_name' => $user->name,
                ...$formData,
                'status' => $data->status ?? 'En Revisión',
                'confirmed' => $data->confirmed ?? false,
            ]);

            return response()->json([
                'message' => 'Planilla de aplicación creada exitosamente',
                'form' => $form->load(['client', 'agent'])
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
    public function show(Request $request, ApplicationForm $form)
    {
        $user = $request->user();

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
    public function update(Request $request, ApplicationForm $form)
    {
        $user = $request->user();

        // Check permissions
        if (!$form->isEditableBy($user)) {
            return response()->json([
                'error' => 'No tienes permisos para editar esta planilla'
            ], 403);
        }

        $data = ApplicationFormData::from($request->all());

        try {
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
    public function confirm(Request $request, ApplicationForm $form)
    {
        $user = $request->user();

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
    public function updateStatus(Request $request, ApplicationForm $form)
    {
        $user = $request->user();

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
     * Upload document to application form.
     */
    public function uploadDocument(Request $request, ApplicationForm $form)
    {
        $user = $request->user();

        // Check permissions
        if (!$form->canView($user)) {
            return response()->json([
                'error' => 'No tienes permisos para subir documentos a esta planilla'
            ], 403);
        }

        $request->validate([
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120', // 5MB max
            'document_type' => 'required|string|max:100'
        ]);

        try {
            $file = $request->file('document');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('application_documents', $fileName, 'public');

            $document = $form->documents()->create([
                'uploaded_by' => $user->id,
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'document_type' => $request->document_type
            ]);

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
     * Delete document from application form.
     */
    public function deleteDocument(Request $request, ApplicationForm $form, $documentId)
    {
        $user = $request->user();

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
            $document->delete(); // This will also delete the file via model event

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
     * Remove the specified application form.
     */
    public function destroy(Request $request, ApplicationForm $form)
    {
        $user = $request->user();

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
}
