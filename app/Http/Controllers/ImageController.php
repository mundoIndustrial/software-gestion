<?php

namespace App\Http\Controllers;

use App\Services\FirebaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class ImageController extends Controller
{
    protected FirebaseStorageService $firebaseStorage;

    public function __construct(FirebaseStorageService $firebaseStorage)
    {
        $this->firebaseStorage = $firebaseStorage;
    }

    /**
     * Subir una imagen
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:5120', // 5MB máximo
            'folder' => 'nullable|string|max:100',
            'custom_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('image');
            $folder = $request->input('folder');
            $customName = $request->input('custom_name');

            $result = $this->firebaseStorage->uploadFile($file, $folder, $customName);

            return response()->json([
                'success' => true,
                'message' => 'Imagen subida exitosamente',
                'data' => $result
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir múltiples imágenes
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|max:5120',
            'folder' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $folder = $request->input('folder');
            $results = [];
            $errors = [];

            foreach ($request->file('images') as $index => $file) {
                try {
                    $result = $this->firebaseStorage->uploadFile($file, $folder);
                    $results[] = $result;
                } catch (Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => count($results) > 0,
                'message' => count($results) . ' imagen(es) subida(s) exitosamente',
                'data' => $results,
                'errors' => $errors
            ], count($results) > 0 ? 201 : 500);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir las imágenes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir imagen desde base64
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadBase64(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|string',
            'folder' => 'nullable|string|max:100',
            'custom_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $imageData = $request->input('image');
            $folder = $request->input('folder');
            $customName = $request->input('custom_name');

            $result = $this->firebaseStorage->uploadBase64Image($imageData, $folder, $customName);

            return response()->json([
                'success' => true,
                'message' => 'Imagen subida exitosamente',
                'data' => $result
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una imagen
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->input('path');
            $deleted = $this->firebaseStorage->deleteFile($path);

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Imagen eliminada exitosamente'
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada'
                ], 404);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar imágenes de una carpeta
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'folder' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $folder = $request->input('folder');
            $files = $this->firebaseStorage->listFiles($folder);

            return response()->json([
                'success' => true,
                'message' => 'Archivos obtenidos exitosamente',
                'data' => $files,
                'count' => count($files)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar las imágenes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar si una imagen existe
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exists(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->input('path');
            $exists = $this->firebaseStorage->fileExists($path);

            return response()->json([
                'success' => true,
                'exists' => $exists,
                'url' => $exists ? $this->firebaseStorage->getPublicUrl($path) : null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información del bucket
     *
     * @return JsonResponse
     */
    public function bucketInfo(): JsonResponse
    {
        try {
            $info = $this->firebaseStorage->getBucketInfo();

            return response()->json([
                'success' => true,
                'data' => $info
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del bucket',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
