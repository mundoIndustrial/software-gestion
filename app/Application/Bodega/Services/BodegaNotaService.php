<?php

namespace App\Application\Bodega\Services;

use App\Models\BodegaNota;
use App\Events\BodegaNotasGuardada;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BodegaNotaService
{
    private BodegaRoleService $roleService;

    public function __construct(BodegaRoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Guardar una nueva nota de bodega
     */
    public function guardarNota(array $validatedData, Request $request): JsonResponse
    {
        try {
            $usuario = auth()->user();
            $roleNames = $usuario->getRoleNames()->toArray();
            
            // Obtener el pedido
            $pedido = \App\Models\PedidoProduccion::where('numero_pedido', $validatedData['numero_pedido'])->first();
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            // Determinar el rol actual del usuario
            $rolActual = $this->roleService->determinarRolActual($roleNames);

            // Guardar la nota
            $nota = BodegaNota::create([
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla'],
                'contenido' => $validatedData['contenido'],
                'usuario_id' => $usuario->id,
                'usuario_nombre' => $usuario->name,
                'usuario_rol' => $rolActual,
                'ip_address' => $request->ip(),
            ]);

            // Disparar evento para tiempo real (temporalmente deshabilitado hasta solucionar Reverb)
            // BodegaNotasGuardada::dispatch(
            //     $validatedData['numero_pedido'],
            //     $validatedData['talla'],
            //     [
            //         'id' => $nota->id,
            //         'contenido' => $nota->contenido,
            //         'usuario_nombre' => $nota->usuario_nombre,
            //         'usuario_rol' => $nota->usuario_rol,
            //         'fecha' => $nota->created_at->format('d/m/Y'),
            //         'hora' => $nota->created_at->format('H:i:s'),
            //         'fecha_completa' => $nota->created_at->format('d/m/Y H:i:s'),
            //     ]
            // );

            return response()->json([
                'success' => true,
                'message' => 'Nota guardada exitosamente',
                'data' => $nota
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en guardarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la nota: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de notas para un pedido y talla
     */
    public function obtenerNotas(array $validatedData): JsonResponse
    {
        try {
            // Obtener notas ordenadas por fecha más reciente
            $notas = BodegaNota::where('numero_pedido', $validatedData['numero_pedido'])
                ->where('talla', $validatedData['talla'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($nota) {
                    return [
                        'id' => $nota->id,
                        'usuario_id' => $nota->usuario_id,
                        'contenido' => $nota->contenido,
                        'usuario_nombre' => $nota->usuario_nombre,
                        'usuario_rol' => $nota->usuario_rol,
                        'fecha' => $nota->created_at->format('d/m/Y'),
                        'hora' => $nota->created_at->format('H:i:s'),
                        'fecha_completa' => $nota->created_at->format('d/m/Y H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $notas
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerNotas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas'
            ], 500);
        }
    }

    /**
     * Actualizar una nota existente
     */
    public function actualizarNota(int $notaId, array $validatedData): JsonResponse
    {
        try {
            $usuario = auth()->user();
            $nota = BodegaNota::findOrFail($notaId);

            // Verificar que el usuario sea el dueño de la nota o tenga permisos
            if ($nota->usuario_id !== $usuario->id && !$usuario->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar esta nota'
                ], 403);
            }

            // Actualizar la nota
            $nota->update([
                'contenido' => $validatedData['contenido'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nota actualizada correctamente',
                'data' => $nota
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en actualizarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nota'
            ], 500);
        }
    }

    /**
     * Eliminar una nota
     */
    public function eliminarNota(int $notaId): JsonResponse
    {
        try {
            $usuario = auth()->user();
            $nota = BodegaNota::findOrFail($notaId);

            // Verificar que el usuario sea el dueño de la nota o tenga permisos
            if ($nota->usuario_id !== $usuario->id && !$usuario->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar esta nota'
                ], 403);
            }

            // Eliminar la nota
            $nota->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nota eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en eliminarNota: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nota'
            ], 500);
        }
    }

    /**
     * Verificar si un usuario puede editar una nota
     */
    public function puedeEditarNota(int $notaId): bool
    {
        $usuario = auth()->user();
        $nota = BodegaNota::find($notaId);
        
        if (!$nota) {
            return false;
        }
        
        return $nota->usuario_id === $usuario->id || $usuario->hasRole('admin');
    }

    /**
     * Obtener notas recientes para un usuario
     */
    public function obtenerNotasRecientes(int $limit = 10): JsonResponse
    {
        try {
            $usuario = auth()->user();
            $roleNames = $usuario->getRoleNames()->toArray();
            $rolActual = $this->roleService->determinarRolActual($roleNames);
            
            // Si es admin, ver todas las notas recientes
            // Si no, ver solo las notas de su rol o las que él creó
            $query = BodegaNota::orderBy('created_at', 'desc')
                ->with(['pedidoProduccion'])
                ->limit($limit);
            
            if (!$usuario->hasRole('admin')) {
                $query->where(function($q) use ($usuario, $rolActual) {
                    $q->where('usuario_id', $usuario->id)
                      ->orWhere('usuario_rol', $rolActual);
                });
            }
            
            $notas = $query->get()->map(function ($nota) {
                return [
                    'id' => $nota->id,
                    'contenido' => substr($nota->contenido, 0, 100) . (strlen($nota->contenido) > 100 ? '...' : ''),
                    'usuario_nombre' => $nota->usuario_nombre,
                    'usuario_rol' => $nota->usuario_rol,
                    'numero_pedido' => $nota->numero_pedido,
                    'talla' => $nota->talla,
                    'fecha_completa' => $nota->created_at->format('d/m/Y H:i:s'),
                    'created_at' => $nota->created_at->toISOString(),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $notas
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerNotasRecientes: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas recientes'
            ], 500);
        }
    }

    /**
     * Contar notas por rol
     */
    public function contarNotasPorRol(): array
    {
        try {
            $conteoPorRol = BodegaNota::selectRaw('usuario_rol, COUNT(*) as total')
                ->groupBy('usuario_rol')
                ->orderBy('total', 'desc')
                ->get()
                ->keyBy('usuario_rol')
                ->toArray();
            
            return $conteoPorRol;
        } catch (\Exception $e) {
            \Log::error('Error en contarNotasPorRol: ' . $e->getMessage());
            return [];
        }
    }
}
