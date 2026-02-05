<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\ImageUploadService;
use App\Models\PedidoAuditoria;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * Controlador de infraestructura para gestión de imágenes de pedidos
 * Siguiendo arquitectura DDD - Capa de Infraestructura
 */
class ImageUploadController extends Controller
{
    private ImageUploadService $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    /**
     * Upload de imagen de prenda
     * POST /api/pedidos/upload-imagen-prenda
     */
    public function uploadImagenPrenda(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'prenda_index' => 'required|integer|min:0',
            'cotizacion_id' => 'nullable|integer',
            'pedido_id' => 'nullable|integer',
            'prenda_pedido_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('image');
            $prendaIndex = $request->input('prenda_index');
            $cotizacionId = $request->input('cotizacion_id');
            $pedidoId = $request->input('pedido_id');
            $prendaPedidoId = $request->input('prenda_pedido_id');

            $result = $this->imageUploadService->uploadPrendaImage($file, $prendaIndex, $cotizacionId);

            // Registrar auditoría si tenemos pedido_id
            if ($pedidoId) {
                PedidoAuditoria::registrarCambio(
                    $pedidoId,
                    'AGREGADA_IMAGEN_PRENDA',
                    auth()->id(),
                    json_encode([
                        'ruta_webp' => $result['ruta_webp'] ?? null,
                        'ruta_original' => $result['ruta_original'] ?? null,
                        'filename' => $file->getClientOriginalName()
                    ]),
                    null,
                    "Imagen de prenda agregada: {$file->getClientOriginalName()}",
                    $prendaPedidoId,
                    null,
                    null,
                    $result['ruta_webp'] ?? null
                );
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error al subir imagen de prenda: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload de imagen de tela
     * POST /api/pedidos/upload-imagen-tela
     */
    public function uploadImagenTela(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'prenda_index' => 'required|integer|min:0',
            'tela_index' => 'required|integer|min:0',
            'tela_id' => 'nullable|integer',
            'pedido_id' => 'nullable|integer',
            'prenda_pedido_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('image');
            $prendaIndex = $request->input('prenda_index');
            $telaIndex = $request->input('tela_index');
            $telaId = $request->input('tela_id');
            $pedidoId = $request->input('pedido_id');
            $prendaPedidoId = $request->input('prenda_pedido_id');

            $result = $this->imageUploadService->uploadTelaImage($file, $prendaIndex, $telaIndex, $telaId);

            // Registrar auditoría si tenemos pedido_id
            if ($pedidoId) {
                PedidoAuditoria::registrarCambio(
                    $pedidoId,
                    'AGREGADA_IMAGEN_PRENDA',
                    auth()->id(),
                    json_encode([
                        'ruta_webp' => $result['ruta_webp'] ?? null,
                        'ruta_original' => $result['ruta_original'] ?? null,
                        'filename' => $file->getClientOriginalName(),
                        'tipo' => 'tela'
                    ]),
                    null,
                    "Imagen de tela agregada: {$file->getClientOriginalName()}",
                    $prendaPedidoId,
                    null,
                    null,
                    $result['ruta_webp'] ?? null
                );
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error al subir imagen de tela: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload de imagen de logo
     * POST /api/pedidos/upload-imagen-logo
     */
    public function uploadImagenLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'logo_cotizacion_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('image');
            $logoCotizacionId = $request->input('logo_cotizacion_id');

            $result = $this->imageUploadService->uploadLogoImage($file, $logoCotizacionId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al subir imagen de logo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload de imagen de reflectivo
     * POST /api/pedidos/upload-imagen-reflectivo
     */
    public function uploadImagenReflectivo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'reflectivo_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('image');
            $reflectivoId = $request->input('reflectivo_id');

            $result = $this->imageUploadService->uploadReflectivoImage($file, $reflectivoId);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al subir imagen de reflectivo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar imagen
     * DELETE /api/pedidos/eliminar-imagen
     */
    public function eliminarImagen(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ruta_webp' => 'required|string',
            'ruta_original' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'pedido_id' => 'nullable|integer',
            'prenda_pedido_id' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pedidoId = $request->input('pedido_id');
            $prendaPedidoId = $request->input('prenda_pedido_id');
            $rutaWebp = $request->ruta_webp;

            $deleted = $this->imageUploadService->deleteImage(
                $request->ruta_webp,
                $request->ruta_original,
                $request->thumbnail
            );

            // Registrar auditoría si tenemos pedido_id
            if ($pedidoId) {
                PedidoAuditoria::registrarCambio(
                    $pedidoId,
                    'ELIMINADA_IMAGEN_PRENDA',
                    auth()->id(),
                    null,
                    json_encode([
                        'ruta_webp' => $rutaWebp,
                        'ruta_original' => $request->ruta_original
                    ]),
                    "Imagen de prenda eliminada: {$rutaWebp}",
                    $prendaPedidoId,
                    null,
                    null,
                    $rutaWebp
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente',
                'deleted' => $deleted
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar imagen: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload múltiple de imágenes
     * POST /api/pedidos/upload-imagenes-multiple
     */
    public function uploadMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'tipo' => 'required|in:prenda,tela,logo,reflectivo',
            'prenda_index' => 'required_if:tipo,prenda,tela|integer|min:0',
            'tela_index' => 'required_if:tipo,tela|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $files = $request->file('images');
            $tipo = $request->input('tipo');
            $options = [
                'prenda_index' => $request->input('prenda_index'),
                'tela_index' => $request->input('tela_index')
            ];

            $uploadedImages = $this->imageUploadService->uploadMultiple($files, $tipo, $options);

            return response()->json([
                'success' => true,
                'data' => $uploadedImages,
                'count' => count($uploadedImages)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al subir imágenes múltiples: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar las imágenes: ' . $e->getMessage()
            ], 500);
        }
    }
}
