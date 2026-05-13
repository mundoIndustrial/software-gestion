<?php

namespace App\Infrastructure\Services\Bodega;

use App\Domain\Bodega\Services\BodegaNotaServiceContract;

use App\Models\BodegaNota;
use App\Events\BodegaNotaCreada;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BodegaNotaService implements BodegaNotaServiceContract
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

            $tieneTallaColorId = Schema::hasColumn('bodega_notas', 'talla_color_id');
            
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
            $datosCrear = [
                'pedido_produccion_id' => $pedido->id,
                'numero_pedido' => $validatedData['numero_pedido'],
                'talla' => $validatedData['talla'],
                'contenido' => $validatedData['contenido'],
                'usuario_id' => $usuario->id,
                'usuario_nombre' => $usuario->name,
                'usuario_rol' => $rolActual,
                'ip_address' => $request->ip(),
            ];

            if ($tieneTallaColorId) {
                $datosCrear['talla_color_id'] = $validatedData['talla_color_id'] ?? null;
            }

            if (Schema::hasColumn('bodega_notas', 'row_hash')) {
                $datosCrear['row_hash'] = $validatedData['row_hash'] ?? null;
            }
            if (Schema::hasColumn('bodega_notas', 'prenda_id')) {
                $datosCrear['prenda_id'] = $validatedData['prenda_id'] ?? null;
            }
            if (Schema::hasColumn('bodega_notas', 'pedido_epp_id')) {
                $datosCrear['pedido_epp_id'] = $validatedData['pedido_epp_id'] ?? null;
            }

            if (Schema::hasColumn('bodega_notas', 'bodega_detalle_talla_id')) {
                $detalleId = $validatedData['bodega_detalle_talla_id'] ?? null;
                if ($detalleId && Schema::hasTable('bodega_detalles_talla')) {
                    $detalleValido = DB::table('bodega_detalles_talla')
                        ->where('id', (int) $detalleId)
                        ->where('pedido_produccion_id', $pedido->id)
                        ->exists();
                    if (!$detalleValido) {
                        $detalleId = null;
                    }
                }

                if (!$detalleId && Schema::hasTable('bodega_detalles_talla')) {
                    $baseQuery = DB::table('bodega_detalles_talla')
                        ->where('pedido_produccion_id', $pedido->id);

                    $rowHash = trim((string) ($validatedData['row_hash'] ?? ''));

                    // 1) Intento exacto por row_hash
                    if ($rowHash !== '') {
                        $detalleId = (clone $baseQuery)
                            ->where('row_hash', $rowHash)
                            ->orderByDesc('id')
                            ->value('id');
                    }

                    // 2) Fallback por claves funcionales del item
                    if (!$detalleId) {
                        $detalleId = (clone $baseQuery)
                            ->where('talla', $validatedData['talla'])
                            ->when(!empty($validatedData['pedido_epp_id'] ?? null), function ($q) use ($validatedData) {
                                return $q->where('pedido_epp_id', (int) $validatedData['pedido_epp_id']);
                            }, function ($q) use ($validatedData) {
                                if (!empty($validatedData['prenda_id'] ?? null)) {
                                    return $q->where('prenda_id', (int) $validatedData['prenda_id']);
                                }
                                return $q;
                            })
                            ->when(array_key_exists('talla_color_id', $validatedData), function ($q) use ($validatedData) {
                                if ($validatedData['talla_color_id'] !== null && $validatedData['talla_color_id'] !== '') {
                                    return $q->where('talla_color_id', (int) $validatedData['talla_color_id']);
                                }
                                return $q->whereNull('talla_color_id');
                            })
                            ->orderByDesc('id')
                            ->value('id');
                    }
                }

                $datosCrear['bodega_detalle_talla_id'] = $detalleId ?: null;
            }

            $nota = BodegaNota::create($datosCrear);

            // El broadcast no debe bloquear el guardado de la nota.
            // Si Reverb/Pusher estÃ¡ caÃ­do, se registra warning y se continÃºa.
            try {
                BodegaNotaCreada::dispatch($nota);
            } catch (\Throwable $broadcastError) {
                \Log::warning('[BodegaNotaService] Nota guardada sin broadcast por error de conexiÃ³n', [
                    'nota_id' => $nota->id,
                    'numero_pedido' => $nota->numero_pedido,
                    'error' => $broadcastError->getMessage(),
                ]);
            }

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
            // Obtener notas ordenadas por fecha mÃ¡s reciente
            $tieneTallaColorId = Schema::hasColumn('bodega_notas', 'talla_color_id');
            $tallaColorId = $validatedData['talla_color_id'] ?? null;

            $notas = BodegaNota::where('numero_pedido', $validatedData['numero_pedido'])
                ->where('talla', $validatedData['talla'])
                ->when($tieneTallaColorId, function ($q) use ($tallaColorId) {
                    return $q->when($tallaColorId !== null, function ($qq) use ($tallaColorId) {
                        return $qq->where('talla_color_id', $tallaColorId);
                    }, function ($qq) {
                        return $qq->whereNull('talla_color_id');
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($nota) {
                    $itemNombre = null;
                    if (Schema::hasTable('bodega_detalles_talla')) {
                        $itemNombre = DB::table('bodega_detalles_talla')
                            ->where('pedido_produccion_id', $nota->pedido_produccion_id)
                            ->where('talla', $nota->talla)
                            ->when($nota->talla_color_id !== null, function ($q) use ($nota) {
                                return $q->where('talla_color_id', $nota->talla_color_id);
                            }, function ($q) {
                                return $q->whereNull('talla_color_id');
                            })
                            ->value('prenda_nombre');
                    }
                    // Determinar fecha de creaciÃ³n y Ãºltima modificaciÃ³n
                    $fechaCreacion = $nota->created_at;
                    $fechaModificacion = $nota->updated_at;
                    $fueEditada = $fechaCreacion->lt($fechaModificacion);
                    
                    // Usar fecha de modificaciÃ³n si fue editada, sino fecha de creaciÃ³n
                    $fechaMostrar = $fueEditada ? $fechaModificacion : $fechaCreacion;
                    
                    return [
                        'id' => $nota->id,
                        'usuario_id' => $nota->usuario_id,
                        'contenido' => $nota->contenido,
                        'usuario_nombre' => $nota->usuario_nombre,
                        'usuario_rol' => $nota->usuario_rol,
                        'talla_color_id' => $nota->talla_color_id,
                        'bodega_detalle_talla_id' => $nota->bodega_detalle_talla_id ?? null,
                        'pedido_epp_id' => $nota->pedido_epp_id ?? null,
                        'prenda_id' => $nota->prenda_id ?? null,
                        'row_hash' => $nota->row_hash ?? null,
                        'item_nombre' => $itemNombre,
                        'fecha' => $fechaMostrar->format('d/m/Y'),
                        'hora' => $fechaMostrar->format('H:i:s'),
                        'fecha_completa' => $fechaMostrar->format('d/m/Y H:i:s'),
                        'created_at' => $fechaCreacion->toISOString(),
                        'updated_at' => $fechaModificacion->toISOString(),
                        'fue_editada' => $fueEditada,
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

            // Verificar que el usuario sea el dueÃ±o de la nota o tenga permisos
            if ($nota->usuario_id !== $usuario->id && !$usuario->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar esta nota'
                ], 403);
            }

            // Actualizar la nota (updated_at se actualiza automÃ¡ticamente)
            $nota->update([
                'contenido' => $validatedData['contenido'],
                'updated_at' => now(), // Forzar actualizaciÃ³n de fecha
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nota actualizada correctamente',
                'data' => $nota
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validaciÃ³n',
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

            // Verificar que el usuario sea el dueÃ±o de la nota o tenga permisos
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
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'La nota ya no existe o ya fue eliminada'
            ], 404);
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
            // Si no, ver solo las notas de su rol o las que Ã©l creÃ³
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

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {BodegaNotaService}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
