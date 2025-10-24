<?php

namespace App\Services;

use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;

class RecaptchaEnterpriseService
{
    public function assess(string $recaptchaKey, string $token, string $projectId, string $action)
    {
        try {
            // Verificar si la variable de entorno de credenciales está configurada
            $credentialsPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');
            
            if (!$credentialsPath || !file_exists($credentialsPath)) {
                \Log::warning('GOOGLE_APPLICATION_CREDENTIALS no está configurada o el archivo no existe', [
                    'path' => $credentialsPath
                ]);
                
                // Modo temporal: devolvemos score simulado
                return [
                    'success' => true,
                    'score' => 0.9,
                    'reasons' => ['TEMPORARY_MODE_CREDENTIALS_NOT_CONFIGURED']
                ];
            }

            $client = new RecaptchaEnterpriseServiceClient();
            $projectName = $client->projectName($projectId);

            $event = (new Event())
                ->setSiteKey($recaptchaKey)
                ->setToken($token);

            $assessment = (new Assessment())
                ->setEvent($event);

            $request = (new CreateAssessmentRequest())
                ->setParent($projectName)
                ->setAssessment($assessment);

            $response = $client->createAssessment($request);

            // Verifica si el token es válido
            if (!$response->getTokenProperties()->getValid()) {
                \Log::warning('Token de reCAPTCHA inválido', ['reason' => $response->getTokenProperties()->getInvalidReason()]);
                return [
                    'success' => false,
                    'reason' => $response->getTokenProperties()->getInvalidReason()
                ];
            }

            // Verifica la acción
            if ($response->getTokenProperties()->getAction() !== $action) {
                \Log::warning('Acción no coincide', ['expected' => $action, 'actual' => $response->getTokenProperties()->getAction()]);
                return [
                    'success' => false,
                    'reason' => 'Action mismatch'
                ];
            }

            // Devuelve la puntuación y análisis de riesgo
            $score = $response->getRiskAnalysis()->getScore();
            \Log::info('Validación de reCAPTCHA exitosa', ['score' => $score, 'action' => $action]);
            
            return [
                'success' => true,
                'score' => $score,
                'reasons' => $response->getRiskAnalysis()->getReasons()
            ];
        } catch (\Exception $e) {
            \Log::error('Error en validación de reCAPTCHA', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // En modo desarrollo, devolver score temporal si las credenciales no están configuradas
            if (strpos($e->getMessage(), 'ApplicationDefaultCredentials') !== false) {
                \Log::warning('Usando modo temporal: credenciales de Google Cloud no configuradas');
                return [
                    'success' => true,
                    'score' => 0.9,
                    'reasons' => ['TEMPORARY_MODE_DUE_TO_CREDENTIALS_ERROR']
                ];
            }
            
            return [
                'success' => false,
                'reason' => 'Error de validación: ' . $e->getMessage()
            ];
        }
    }
}
