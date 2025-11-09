<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Application\ApplicationFormData;
use App\Http\Controllers\Controller;
use App\Mail\ApplicationCreatedNotification;
use App\Models\ApplicationForm;
use App\Models\ApplicationDocument;
use App\Models\User;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PublicApplicationController extends Controller
{
    /**
     * Crear una nueva application form sin autenticación.
     * Endpoint público para que el jefe pueda crear planillas sin login.
     * 
     * POST /api/v1/public/new-application
     */
    public function store(Request $request)
    {
        // Validar datos básicos
        $validator = Validator::make($request->all(), [
            'agent_id' => 'required|exists:users,id',
            'applicant_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'dob' => 'required|date',
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Datos de validación incorrectos',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            $data = ApplicationFormData::from($request->all());
            
            // Validar que el agente existe y es tipo agente o admin
            $agent = User::where('id', $request->agent_id)
                        ->whereIn('type', ['agent', 'admin'])
                        ->first();

            if (!$agent) {
                return response()->json([
                    'error' => 'Agente no encontrado o no es un usuario válido'
                ], 404);
            }

            // Crear el cliente automáticamente si viene el email
            $client = null;
            if ($request->email) {
                // Buscar si el cliente ya existe
                $client = User::where('email', $request->email)
                            ->where('type', 'client')
                            ->first();
                
                // Si no existe, crearlo
                if (!$client) {
                    $client = User::create([
                        'name' => $request->applicant_name,
                        'email' => $request->email,
                        'password' => bcrypt('latin1234*'), // Password por defecto
                        'type' => 'client',
                        'created_by' => $agent->id
                    ]);
                }
            } else {
                // Si no viene email, crear cliente sin email (usando timestamp en el email)
                $client = User::create([
                    'name' => $request->applicant_name,
                    'email' => 'client_' . time() . '_' . rand(1000, 9999) . '@noemail.com',
                    'password' => bcrypt('latin1234*'),
                    'type' => 'client',
                    'created_by' => $agent->id
                ]);
            }

            // Verificar que el cliente no tenga ya una application form
            $existingForm = ApplicationForm::where('client_id', $client->id)->first();
            if ($existingForm) {
                return response()->json([
                    'error' => 'Este cliente ya tiene una planilla de aplicación'
                ], 409);
            }

            // Preparar datos del formulario
            $formData = array_filter($data->toArray(), function($value) {
                return $value !== null;
            });

            // Crear la application form
            $form = ApplicationForm::create([
                'client_id' => $client->id,
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                ...$formData,
                'status' => $data->status ?? 'En Revisión',
                'confirmed' => false,
            ]);

            // Generar token de confirmación (3 días de validez)
            $token = $form->generateConfirmationToken();

            // Generar PDF completo de la planilla automáticamente
            $pdfService = new PdfGeneratorService();
            $completePdfPath = null;
            $completePdfFullPath = null;
            
            try {
                $pdfPath = $pdfService->generateApplicationPdf($form);
                
                // Guardar el PDF en la carpeta de documentos
                $pdfFileName = "Planilla_Aplicacion_{$form->client->name}_{$form->id}.pdf";
                $publicPdfPath = "application_documents/{$pdfFileName}";
                
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
                    'uploaded_by' => $agent->id, // Usar el ID del agente
                    'original_name' => $pdfFileName,
                    'file_name' => $pdfFileName,
                    'file_path' => $publicPdfPath,
                    'mime_type' => 'application/pdf',
                    'file_size' => $fileSize,
                    'document_type' => 'application_form'
                ]);

                $completePdfFullPath = $publicFullPath;
                
                // Eliminar archivo temporal
                @unlink($tempPath);
                
            } catch (\Exception $e) {
                \Log::error('Error al generar PDF completo de aplicación: ' . $e->getMessage());
            }

            // Enviar email al agente con la planilla adjunta
            try {
                if ($agent->email && $completePdfFullPath) {
                    Mail::to($agent->email)->send(
                        new ApplicationCreatedNotification(
                            $form,
                            $agent,
                            $completePdfFullPath,
                            null,
                            $token
                        )
                    );
                    
                    \Log::info("✅ Email enviado a {$agent->email} con planilla adjunta");
                } else {
                    \Log::warning("⚠️ No se pudo enviar email: faltan datos o archivo de planilla");
                }
            } catch (\Exception $e) {
                \Log::error('Error al enviar email al agente: ' . $e->getMessage());
                // No fallar la creación aunque falle el email
            }

            // Generar link de confirmación
            $frontendBaseUrl = rtrim(config('services.frontend.url', url('/')), '/');
            $confirmationLink = $frontendBaseUrl . '/confirm/' . $token;

            return response()->json([
                'success' => true,
                'message' => 'Planilla de aplicación creada exitosamente. Se envió el PDF completo al agente por correo electrónico.',
                'form' => $form->load(['client', 'agent', 'documents']),
                'confirmation_token' => $token,
                'token_expires_at' => $form->token_expires_at,
                'confirmation_link' => $confirmationLink,
                'agent_email' => $agent->email,
                'pdfs_sent' => true,
                'authorization_pdf_sent' => false
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error en PublicApplicationController@store: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al crear la planilla',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
