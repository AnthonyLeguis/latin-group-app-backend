<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApplicationForm;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ConfirmationController extends Controller
{
    /**
     * GET /api/v1/confirm/{token}
     * Obtener los datos de la planilla para mostrar al cliente
     * ENDPOINT PÚBLICO - No requiere autenticación
     */
    public function show(string $token): JsonResponse
    {
        try {
            // Buscar la planilla por token
            $form = ApplicationForm::where('confirmation_token', $token)
                ->with(['client', 'agent'])
                ->first();

            // Token no encontrado
            if (!$form) {
                return response()->json([
                    'error' => 'Token inválido',
                    'message' => 'Este enlace ya no es válido. Si ya confirmó su planilla, puede cerrar esta pestaña.'
                ], 404);
            }

            // Token expirado
            if ($form->isTokenExpired()) {
                return response()->json([
                    'error' => 'Token expirado',
                    'message' => 'Este link de confirmación ha expirado. Contacte a su agente para obtener un nuevo link.',
                    'expired_at' => $form->token_expires_at
                ], 410); // 410 Gone - recurso ya no disponible
            }

            // Ya fue confirmado previamente
            if ($form->isConfirmedByClient()) {
                return response()->json([
                    'error' => 'Ya confirmado',
                    'message' => 'Esta planilla ya fue confirmada anteriormente.',
                    'confirmed_at' => $form->confirmed_at
                ], 409); // 409 Conflict
            }

            // Retornar datos de la planilla para mostrar al cliente
            return response()->json([
                'success' => true,
                'form' => [
                    'id' => $form->id,
                    // Datos del cliente
                    'client_name' => $form->client->name,
                    'client_email' => $form->client->email,
                    'applicant_name' => $form->applicant_name,
                    'dob' => $form->dob?->format('m-d-Y'),
                    'address' => $form->address,
                    'city' => $form->city,
                    'state' => $form->state,
                    'zip_code' => $form->zip_code,
                    'phone' => $form->phone,
                    'email' => $form->email,
                    // Datos del agente (usar relación siempre actualizada)
                    'agent_name' => $form->agent ? $form->agent->name : $form->agent_name,
                    'agent_phone' => $form->agent->phone ?? null,
                    // Datos del plan
                    'insurance_company' => $form->insurance_company,
                    'insurance_plan' => $form->insurance_plan,
                    'wages' => $form->wages,
                    'final_cost' => $form->final_cost,
                    // Información del token
                    'token_expires_at' => $form->token_expires_at,
                    'days_remaining' => now()->diffInDays($form->token_expires_at, false)
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en ConfirmationController@show:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => 'Ocurrió un error al procesar su solicitud.'
            ], 500);
        }
    }

    /**
     * POST /api/v1/confirm/{token}/accept
     * Cliente confirma la planilla (presiona "Aceptar documento")
     * ENDPOINT PÚBLICO - No requiere autenticación
     */
    public function accept(string $token): JsonResponse
    {
        // Buscar la planilla por token
        $form = ApplicationForm::where('confirmation_token', $token)->first();

        // Token no encontrado
        if (!$form) {
            return response()->json([
                'error' => 'Token inválido',
                'message' => 'El link de confirmación no es válido.'
            ], 404);
        }

        // Token expirado
        if ($form->isTokenExpired()) {
            return response()->json([
                'error' => 'Token expirado',
                'message' => 'Este link de confirmación ha expirado.'
            ], 410);
        }

        // Ya fue confirmado
        if ($form->isConfirmedByClient()) {
            return response()->json([
                'error' => 'Ya confirmado',
                'message' => 'Esta planilla ya fue confirmada anteriormente.',
                'confirmed_at' => $form->confirmed_at
            ], 409);
        }

        // ✅ CONFIRMAR LA PLANILLA
        $form->confirmByClient();

        return response()->json([
            'success' => true,
            'message' => '¡Documento aceptado exitosamente!',
            'confirmed_at' => $form->confirmed_at,
            'form_id' => $form->id
        ]);
    }

    /**
     * GET /api/v1/forms/{id}/download-pdf
     * Descargar el PDF de una planilla confirmada
     * ENDPOINT PROTEGIDO - Requiere autenticación
     */
    public function downloadPdf(int $id)
    {
        // Obtener la planilla con la relación del agente
        $form = ApplicationForm::with('agent')->findOrFail($id);

        // Verificar que esté confirmada
        if (!$form->isConfirmedByClient()) {
            return response()->json([
                'error' => 'No disponible',
                'message' => 'El PDF solo está disponible para planillas confirmadas.'
            ], 400);
        }

        // Verificar que exista el PDF
        if (!$form->pdf_path || !\Storage::disk('local')->exists($form->pdf_path)) {
            return response()->json([
                'error' => 'No encontrado',
                'message' => 'El PDF no está disponible.'
            ], 404);
        }

        // Obtener la ruta completa del archivo
        $filePath = storage_path("app/{$form->pdf_path}");

        // Retornar el archivo para descargar
        return response()->download($filePath, "{$form->client->name}_-_Confirmación_{$form->id}.pdf");
    }

    /**
     * GET /api/v1/forms/{id}/view-pdf
     * Ver el PDF en el navegador
     * ENDPOINT PROTEGIDO - Requiere autenticación
     */
    public function viewPdf(int $id)
    {
        // Obtener la planilla con la relación del agente
        $form = ApplicationForm::with('agent')->findOrFail($id);

        // Verificar que esté confirmada
        if (!$form->isConfirmedByClient()) {
            return response()->json([
                'error' => 'No disponible',
                'message' => 'El PDF solo está disponible para planillas confirmadas.'
            ], 400);
        }

        // Verificar que exista el PDF
        if (!$form->pdf_path || !\Storage::disk('local')->exists($form->pdf_path)) {
            return response()->json([
                'error' => 'No encontrado',
                'message' => 'El PDF no está disponible.'
            ], 404);
        }

        // Obtener la ruta completa del archivo
        $filePath = storage_path("app/{$form->pdf_path}");

        // Retornar el archivo para visualizar
        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $form->client->name . '_Confirmación_' . $form->id . '.pdf"'
        ]);
    }
}
