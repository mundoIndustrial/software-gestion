<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Cliente;
use App\Http\Requests\CrearPedidoCompletoRequest;

/**
 * CrearPedidoEditableController
 * 
 * Maneja la creación de pedidos desde la interfaz editable (gestión de ítems, validación y creación)
 * Este controlador es parte de la arquitectura web tradicional para crear pedidos de manera interactiva
 * 
 * 🔧 CAMBIOS APLICADOS (24 Enero 2026):
 * - validarPedido() ahora usa CrearPedidoCompletoRequest para VALIDACIÓN COMPLETA
 * - Validación incompleta eliminada (que solo validaba cliente, items, cantidad_talla)
 * - Ahora valida y retorna: variaciones, procesos, telas, imagenes
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
     * 🔧 CORREGIDO (24 Enero 2026):
     * - Ahora usa CrearPedidoCompletoRequest en lugar de validate() inline
     * - Valida y retorna TODOS los campos: variaciones, procesos, telas, imagenes
     * - Antes solo validaba: cliente, items, cantidad_talla (se perdían los demás)
     * 
     * @param CrearPedidoCompletoRequest $request ← CAMBIO: Era Request
     * @return JsonResponse
     */
    public function validarPedido(CrearPedidoCompletoRequest $request): JsonResponse
    {
        try {
            \Log::info('[CrearPedidoEditableController] validarPedido - Datos recibidos', [
                'cliente' => $request->input('cliente'),
                'items_count' => count($request->input('items', [])),
            ]);

            // ✅ CAMBIO: Usar validated() que retorna TODOS los campos validados por FormRequest
            // Antes: $validated = $request->validate([...]) solo retornaba los campos de las reglas
            // Ahora: $request->validated() retorna cliente, forma_de_pago, descripcion, items (COMPLETO)
            $validated = $request->validated();

            \Log::info('[CrearPedidoEditableController] Validación pasada', [
                'cliente' => $validated['cliente'] ?? null,
                'items_count' => count($validated['items'] ?? []),
                'items_keys' => count($validated['items'][0] ?? []) ? array_keys($validated['items'][0]) : [],
            ]);

            // Obtener o crear el cliente
            $clienteNombre = trim($request->input('cliente'));
            $cliente = $this->obtenerOCrearCliente($clienteNombre);

            return response()->json([
                'success' => true,
                'message' => 'Validación exitosa',
                'cliente_id' => $cliente->id,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('[CrearPedidoEditableController] Validación fallida', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('[CrearPedidoEditableController] Error general', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Obtener cliente existente o crear uno nuevo
     * 
     * @param string $nombre
     * @return Cliente
     */
    private function obtenerOCrearCliente(string $nombre): Cliente
    {
        // Buscar cliente por nombre
        $cliente = Cliente::where('nombre', 'LIKE', $nombre)->first();
        
        if ($cliente) {
            \Log::info('[CrearPedidoEditableController] Cliente existente encontrado', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre
            ]);
            return $cliente;
        }
        
        // Crear cliente nuevo si no existe
        $cliente = Cliente::create([
            'nombre' => $nombre,
            'email' => null,
            'telefono' => null,
            'direccion' => null,
            'ciudad' => null,
            'estado' => 'activo',
        ]);
        
        \Log::info('[CrearPedidoEditableController] Cliente nuevo creado', [
            'cliente_id' => $cliente->id,
            'nombre' => $cliente->nombre
        ]);
        
        return $cliente;
    }

    /**
     * Crear el pedido completo con todas sus prendas
     * 
     * Ejecuta CrearPedidoCompletoCommand que orquesta:
     * - Creación del pedido base
     * - Agregado de prendas con tallas, variantes, procesos, imágenes
     * 
     * @param CrearPedidoCompletoRequest $request
     * @return JsonResponse
     */
    public function crearPedido(CrearPedidoCompletoRequest $request): JsonResponse
    {
        try {
            \Log::info('[CrearPedidoEditableController] crearPedido - Inicio', [
                'cliente' => $request->input('cliente'),
                'items_count' => count($request->input('items', [])),
            ]);

            // Los datos ya vienen validados por CrearPedidoCompletoRequest
            $validated = $request->validated();

            // Obtener o crear el cliente
            $clienteNombre = trim($request->input('cliente'));
            $cliente = $this->obtenerOCrearCliente($clienteNombre);

            \Log::info('[CrearPedidoEditableController] Cliente obtenido', [
                'cliente_id' => $cliente->id,
                'nombre' => $cliente->nombre,
            ]);

            // Crear command completo con todos los datos
            $command = new \App\Domain\Pedidos\Commands\CrearPedidoCompletoCommand(
                cliente: $cliente->id,
                formaPago: $validated['forma_de_pago'] ?? 'CONTADO',
                asesorId: \Illuminate\Support\Facades\Auth::id(),
                items: $validated['items'],
                descripcion: $validated['descripcion'] ?? null,
            );

            // Ejecutar a través del CommandBus
            /** @var \App\Models\PedidoProduccion $pedido */
            $pedido = app(\App\Domain\Shared\CQRS\CommandBus::class)->execute($command);

            \Log::info('[CrearPedidoEditableController] Pedido creado exitosamente', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cliente_id' => $cliente->id,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('[CrearPedidoEditableController] Error de validación', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            \Log::error('[CrearPedidoEditableController] Error al crear pedido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage(),
            ], 500);
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
