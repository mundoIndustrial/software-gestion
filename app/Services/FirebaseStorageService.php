<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use App\Helpers\CurlHelper;
use Exception;

class FirebaseStorageService
{
    protected Storage $storage;
    protected string $bucket;
    protected array $allowedExtensions;
    protected int $maxFileSize;

    public function __construct()
    {
        try {
            // Deshabilitar verificación SSL en desarrollo
            CurlHelper::disableSSLVerification();
            
            $credentialsPath = config('firebase.credentials');
            
            // Si no existe el archivo de credenciales, usar configuración básica
            if (!file_exists($credentialsPath)) {
                Log::warning('Firebase credentials file not found. Using project ID only.');
                $factory = (new Factory)->withProjectId(config('firebase.project_id'));
            } else {
                $factory = (new Factory)->withServiceAccount($credentialsPath);
            }
            
            $this->storage = $factory->createStorage();
            $this->bucket = config('firebase.storage_bucket');
            $this->allowedExtensions = config('firebase.storage.allowed_extensions', []);
            $this->maxFileSize = config('firebase.storage.max_file_size', 5242880);
        } catch (Exception $e) {
            Log::error('Firebase initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Subir un archivo a Firebase Storage
     *
     * @param UploadedFile $file
     * @param string|null $folder
     * @param string|null $customName
     * @return array ['url' => string, 'path' => string]
     * @throws Exception
     */
    public function uploadFile(UploadedFile $file, ?string $folder = null, ?string $customName = null): array
    {
        // IMPORTANTE: Deshabilitar SSL justo antes de subir
        CurlHelper::disableSSLVerification();
        
        // Validar extensión
        $extension = strtolower($file->getClientOriginalExtension());
        if (!empty($this->allowedExtensions) && !in_array($extension, $this->allowedExtensions)) {
            throw new Exception("Tipo de archivo no permitido. Extensiones permitidas: " . implode(', ', $this->allowedExtensions));
        }

        // Validar tamaño
        if ($file->getSize() > $this->maxFileSize) {
            $maxSizeMB = $this->maxFileSize / 1048576;
            throw new Exception("El archivo excede el tamaño máximo permitido de {$maxSizeMB}MB");
        }

        // Generar nombre del archivo
        $fileName = $customName ?? $this->generateFileName($file);
        
        // Construir la ruta completa
        $folder = $folder ?? config('firebase.storage.default_folder');
        $filePath = $folder ? "{$folder}/{$fileName}" : $fileName;

        try {
            // Obtener el bucket
            $bucket = $this->storage->getBucket();
            
            // Subir el archivo
            $object = $bucket->upload(
                fopen($file->getRealPath(), 'r'),
                [
                    'name' => $filePath,
                    'metadata' => [
                        'contentType' => $file->getMimeType(),
                        'metadata' => [
                            'originalName' => $file->getClientOriginalName(),
                            'uploadedAt' => now()->toIso8601String(),
                        ]
                    ]
                ]
            );

            // Generar URL pública
            $url = $this->getPublicUrl($filePath);

            Log::info("File uploaded to Firebase Storage", [
                'path' => $filePath,
                'url' => $url
            ]);

            return [
                'url' => $url,
                'path' => $filePath,
                'name' => $fileName,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ];
        } catch (Exception $e) {
            Log::error('Firebase upload error: ' . $e->getMessage());
            throw new Exception('Error al subir el archivo a Firebase Storage: ' . $e->getMessage());
        }
    }

    /**
     * Subir imagen desde base64
     *
     * @param string $base64Data
     * @param string|null $folder
     * @param string|null $customName
     * @return array
     * @throws Exception
     */
    public function uploadBase64Image(string $base64Data, ?string $folder = null, ?string $customName = null): array
    {
        // Decodificar base64
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
            $extension = $matches[1];
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        } else {
            throw new Exception('Formato de imagen base64 inválido');
        }

        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            throw new Exception('Error al decodificar imagen base64');
        }

        // Generar nombre del archivo
        $fileName = $customName ?? uniqid('img_') . '.' . $extension;
        
        // Construir la ruta completa
        $folder = $folder ?? config('firebase.storage.default_folder');
        $filePath = $folder ? "{$folder}/{$fileName}" : $fileName;

        try {
            // Obtener el bucket
            $bucket = $this->storage->getBucket();
            
            // Subir el archivo
            $object = $bucket->upload(
                $imageData,
                [
                    'name' => $filePath,
                    'metadata' => [
                        'contentType' => "image/{$extension}",
                        'metadata' => [
                            'uploadedAt' => now()->toIso8601String(),
                        ]
                    ]
                ]
            );

            // Generar URL pública
            $url = $this->getPublicUrl($filePath);

            return [
                'url' => $url,
                'path' => $filePath,
                'name' => $fileName,
            ];
        } catch (Exception $e) {
            Log::error('Firebase base64 upload error: ' . $e->getMessage());
            throw new Exception('Error al subir imagen base64 a Firebase Storage: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar un archivo de Firebase Storage
     *
     * @param string $filePath
     * @return bool
     */
    public function deleteFile(string $filePath): bool
    {
        try {
            $bucket = $this->storage->getBucket();
            $object = $bucket->object($filePath);
            
            if ($object->exists()) {
                $object->delete();
                Log::info("File deleted from Firebase Storage: {$filePath}");
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            Log::error('Firebase delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener URL pública de un archivo
     *
     * @param string $filePath
     * @return string
     */
    public function getPublicUrl(string $filePath): string
    {
        // Codificar el path para la URL
        $encodedPath = str_replace(['/', ' '], ['%2F', '%20'], $filePath);
        
        // Construir URL pública
        $baseUrl = config('firebase.url.base');
        $url = $baseUrl . $encodedPath . '?alt=media';
        
        // Agregar token si está configurado
        $token = config('firebase.url.token');
        if ($token) {
            $url .= '&token=' . $token;
        }
        
        return $url;
    }

    /**
     * Verificar si un archivo existe
     *
     * @param string $filePath
     * @return bool
     */
    public function fileExists(string $filePath): bool
    {
        try {
            $bucket = $this->storage->getBucket();
            $object = $bucket->object($filePath);
            return $object->exists();
        } catch (Exception $e) {
            Log::error('Firebase file exists check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Listar archivos en una carpeta
     *
     * @param string|null $folder
     * @return array
     */
    public function listFiles(?string $folder = null): array
    {
        try {
            $bucket = $this->storage->getBucket();
            $prefix = $folder ? $folder . '/' : '';
            
            $objects = $bucket->objects(['prefix' => $prefix]);
            
            $files = [];
            foreach ($objects as $object) {
                $files[] = [
                    'name' => $object->name(),
                    'url' => $this->getPublicUrl($object->name()),
                    'size' => $object->info()['size'] ?? 0,
                    'updated' => $object->info()['updated'] ?? null,
                ];
            }
            
            return $files;
        } catch (Exception $e) {
            Log::error('Firebase list files error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generar nombre único para archivo
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function generateFileName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('YmdHis');
        $random = substr(md5(uniqid()), 0, 8);
        
        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Obtener información del bucket
     *
     * @return array
     */
    public function getBucketInfo(): array
    {
        try {
            $bucket = $this->storage->getBucket();
            $info = $bucket->info();
            
            return [
                'name' => $info['name'] ?? '',
                'location' => $info['location'] ?? '',
                'storageClass' => $info['storageClass'] ?? '',
                'timeCreated' => $info['timeCreated'] ?? '',
            ];
        } catch (Exception $e) {
            Log::error('Firebase bucket info error: ' . $e->getMessage());
            return [];
        }
    }
}
