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
use App\Mail\ContactUsNotification;

class ContactController extends Controller
{
    public function submit(ContactFormRequest $request): JsonResponse
    {
        // Validar reCAPTCHA v3/v2
        $recaptchaToken = $request->input('recaptcha_token');
        $recaptchaSecret = env('RECAPTCHA_SECRET_KEY');
        $recaptchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $recaptchaSecret,
            'response' => $recaptchaToken,
            'remoteip' => $request->ip(),
        ]);
        $recaptchaData = $recaptchaResponse->json();
        if (empty($recaptchaData['success']) || ($recaptchaData['success'] !== true)) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo verificar el reCAPTCHA. Intenta nuevamente.',
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
