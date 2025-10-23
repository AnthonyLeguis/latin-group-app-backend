<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactFormRequest;
use App\Data\Client\ContactFormData;
use App\Models\ContactUs;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use App\Services\RecaptchaEnterpriseService;
use App\Mail\ContactUsNotification;

class ContactController extends Controller
{
    public function submit(ContactFormRequest $request, RecaptchaEnterpriseService $recaptchaService): JsonResponse
    {
        // Validar reCAPTCHA Enterprise
        $recaptchaToken = $request->input('recaptcha_token');
        $recaptchaKey = config('services.recaptcha.site_key');
        $projectId = config('services.recaptcha.project_id');
        $action = 'submit_contact'; // Debe coincidir con el frontend
        $minScore = 0.5;

        \Log::info('Contact request data', $request->all());
        $result = $recaptchaService->assess($recaptchaKey, $recaptchaToken, $projectId, $action);
        \Log::info('Recaptcha assessment result', $result);

        if (!$result['success'] || ($result['score'] ?? 0) < $minScore) {
            \Log::warning('Recaptcha failed or score too low', $result);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo verificar el reCAPTCHA Enterprise o el score es bajo.',
                'score' => $result['score'] ?? null,
                'reason' => $result['reason'] ?? null,
            ], 429);
        }
        
        $data = new ContactFormData($request->validated());

        $contact = ContactUs::create([
            'full_name' => $data->fullName,
            'email' => $data->email,
            'phone' => $data->phone,
            'zip_code' => $data->zipCode,
            'service_medical' => $data->serviceMedical,
            'service_dental' => $data->serviceDental,
            'service_accidents' => $data->serviceAccidents,
            'service_life' => $data->serviceLife,
            'accept_sms' => $data->acceptSms,
            'send_email' => false,
        ]);

        // Envío de email profesional
        $emailError = null;
        try {
            Mail::to(env('CONTACT_EMAIL'))->send(new ContactUsNotification($contact));
            $contact->send_email = true;
            $contact->email_sent_at = Carbon::now();
        } catch (\Exception $e) {
            $emailError = $e->getMessage();
            $contact->email_error = $emailError;
        }
        $contact->save();

        return response()->json([
            'success' => true,
            'message' => $emailError ? 'Formulario almacenado, pero el email no pudo enviarse.' : 'Formulario recibido y notificado correctamente.',
            'data' => $contact,
            'email_error' => $emailError,
        ]);
    }
}
