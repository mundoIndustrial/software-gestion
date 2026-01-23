<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * CrearPedidoEditableController
 * 
 * Maneja la creación de pedidos desde la interfaz editable (gestión de ítems, validación y creación)
 * Este controlador es parte de la arquitectura web tradicional para crear pedidos de manera interactiva
 */
class CrearPedidoEditableController extends Controller
{
    /**
     * Agregar item al carrito de pedido
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function agregarItem(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'prenda_id' => 'nullable|integer',
                'nombre_prenda' => 'required|string|max:255',
                'cantidad' => 'required|integer|min:1',
                'descripcion' => 'nullable|string',
            ]);

            // Aquí iría la lógica para agregar el item
            return response()->json([
                'success' => true,
                'message' => 'Item agregado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar item: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Eliminar item del carrito
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function eliminarItem(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'index' => 'required|integer|min:0',
            ]);

            // Lógica para eliminar item

            return response()->json([
                'success' => true,
                'message' => 'Item eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar item: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener items del carrito
     * 
     * @return JsonResponse
     */
    public function obtenerItems(): JsonResponse
    {
        try {
            // Obtener items de la sesión o estado global
            $items = session('items_pedido', []);

            return response()->json([
                'success' => true,
                'items' => $items,
                'count' => count($items),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener items: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validar datos del pedido antes de crear
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validarPedido(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cliente_id' => 'required|integer',
                'descripcion' => 'nullable|string|max:1000',
                'prendas' => 'required|array|min:1',
                'prendas.*.nombre_prenda' => 'required|string',
                'prendas.*.cantidad' => 'required|integer|min:1',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Validación exitosa',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida: ' . $e->getMessage(),
                'errors' => $e instanceof \Illuminate\Validation\ValidationException ? $e->errors() : [],
            ], 422);
        }
    }

    /**
     * Crear el pedido
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function crearPedido(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'cliente_id' => 'required|integer|exists:clientes,id',
                'descripcion' => 'nullable|string|max:1000',
                'prendas' => 'required|array|min:1',
                'prendas.*.nombre_prenda' => 'required|string|max:255',
                'prendas.*.cantidad' => 'required|integer|min:1',
                'prendas.*.descripcion' => 'nullable|string',
            ]);

            // Aquí iría la lógica para crear el pedido usando los Use Cases
            // Por ahora retornamos un placeholder

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'pedido_id' => null, // Se obtendría del Use Case
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Subir imágenes de prenda
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function subirImagenesPrenda(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'imagenes' => 'required|array|min:1',
                'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
            ]);

            $uploadedPaths = [];

            foreach ($request->file('imagenes') as $imagen) {
                $path = $imagen->store('prendas/temp', 'public');
                $uploadedPaths[] = [
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Imágenes subidas correctamente',
                'imagenes' => $uploadedPaths,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir imágenes: ' . $e->getMessage(),
            ], 422);
        }
    }
}
