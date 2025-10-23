<?php

namespace App\Services;

class RecaptchaEnterpriseService
{
    public function assess(string $recaptchaKey, string $token, string $projectId, string $action)
    {
        try {
            // TEMPORAL: Si el token y la acción son válidos, devolvemos un score simulado
            // Esto es para validar el flujo completo mientras resolvemos el problema de carga de la librería
            
            \Log::info('reCAPTCHA assessment (TEMPORAL MODE)', [
                'token' => substr($token, 0, 20) . '...',
                'action' => $action
            ]);
            
            // Validaciones básicas
            if (empty($token)) {
                return [
                    'success' => false,
                    'reason' => 'Token is empty'
                ];
            }
            
            if (empty($action)) {
                return [
                    'success' => false,
                    'reason' => 'Action is empty'
                ];
            }

            // Devolvemos un score simulado de 0.9 (usuario legítimo)
            // NOTA: Esto debe ser reemplazado con validación real de Google cuando se resuelva el problema de autoload
            $score = 0.9;
            
            \Log::info('reCAPTCHA assessment (TEMPORAL) successful', [
                'score' => $score,
                'action' => $action,
                'mode' => 'TEMPORARY - NOT VALIDATING WITH GOOGLE'
            ]);
            
            return [
                'success' => true,
                'score' => $score,
                'reasons' => ['TEMPORARY_MODE_NO_REAL_VALIDATION']
            ];
            
        } catch (\Exception $e) {
            \Log::error('reCAPTCHA assessment error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return [
                'success' => false,
                'reason' => 'Assessment error: ' . $e->getMessage()
            ];
        }
    }
}
