<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;

class GoogleTestUserService
{
    /**
     * Agregar usuario de prueba a Google Cloud
     * Usa Service Account para autenticación
     */
    public static function addTestUser($email)
    {
        try {
            $serviceAccountPath = config_path('google-service-account.json');
            
            if (!file_exists($serviceAccountPath)) {
                \Log::warning('Service account JSON file not found at: ' . $serviceAccountPath);
                return false;
            }
            
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            
            if (!$serviceAccount) {
                \Log::error('Invalid service account JSON');
                return false;
            }
            
            // Obtener access token usando Service Account
            $accessToken = self::getAccessTokenFromServiceAccount($serviceAccount);
            
            if (!$accessToken) {
                \Log::error('Failed to get access token from service account');
                return false;
            }
            
            // Obtener project ID del service account
            $projectId = $serviceAccount['project_id'] ?? env('GOOGLE_CLOUD_PROJECT_ID');
            
            if (!$projectId) {
                \Log::warning('Project ID not found in service account or .env');
                return false;
            }
            
            // Agregar usuario de prueba usando Firebase Management API
            $response = Http::withToken($accessToken)
                ->post("https://firebasemanagement.googleapis.com/v1/projects/{$projectId}/testUsers", [
                    'email' => $email,
                    'password' => 'TempPassword123!@#',
                ]);
            
            if ($response->successful() || $response->status() === 201) {
                \Log::info('Test user added to Google Cloud', [
                    'email' => $email,
                    'project_id' => $projectId,
                    'response' => $response->json(),
                ]);
                return true;
            } else {
                \Log::error('Error adding test user to Google Cloud', [
                    'email' => $email,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            \Log::error('Exception adding test user to Google Cloud: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener access token usando Service Account
     */
    private static function getAccessTokenFromServiceAccount($serviceAccount)
    {
        try {
            $now = time();
            $expiresAt = $now + 3600; // 1 hora
            
            $payload = [
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/cloud-platform https://www.googleapis.com/auth/identitytoolkit',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $expiresAt,
                'iat' => $now,
            ];
            
            // Crear JWT
            $jwt = JWT::encode($payload, $serviceAccount['private_key'], 'RS256');
            
            // Intercambiar JWT por access token
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
            
            if ($response->successful()) {
                return $response->json('access_token');
            }
            
            \Log::error('Failed to exchange JWT for access token', [
                'response' => $response->json(),
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            \Log::error('Exception in getAccessTokenFromServiceAccount: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Remover usuario de prueba de Google Cloud
     */
    public static function removeTestUser($email)
    {
        try {
            $clientId = env('GOOGLE_CLIENT_ID');
            $clientSecret = env('GOOGLE_CLIENT_SECRET');
            
            if (!$clientId || !$clientSecret) {
                \Log::warning('Google Cloud credentials not configured');
                return false;
            }
            
            // Obtener access token
            $tokenResponse = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
                'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            ]);
            
            if (!$tokenResponse->successful()) {
                \Log::error('Failed to get access token for Google Cloud API');
                return false;
            }
            
            $accessToken = $tokenResponse->json('access_token');
            $projectId = env('GOOGLE_CLOUD_PROJECT_ID');
            
            if (!$projectId) {
                \Log::warning('GOOGLE_CLOUD_PROJECT_ID not configured');
                return false;
            }
            
            $response = Http::withToken($accessToken)
                ->delete("https://identitytoolkit.googleapis.com/v2/projects/{$projectId}/testUsers/{$email}");
            
            if ($response->successful()) {
                \Log::info('Test user removed from Google Cloud', [
                    'email' => $email,
                ]);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            \Log::error('Exception removing test user from Google Cloud: ' . $e->getMessage());
            return false;
        }
    }
}
