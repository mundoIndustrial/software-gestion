<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Cotizacion\Services\GenerarNumeroCotizacionService;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\TipoCotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CotizacionEppController extends Controller
{
    public function __construct(
        private readonly GenerarNumeroCotizacionService $generarNumeroCotizacionService,
    ) {
    }

    /**
     * Guardar cotización EPP
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                $action = $request->input('action') ?? $request->input('accion');
                $esBorrador = $action === 'borrador';
                $estado = $esBorrador ? 'BORRADOR' : 'APROBADO_PARA_PEDIDO';

                $cotizacionIdEdicion = $request->input('cotizacion_id');

                $clienteId = $request->input('cliente_id');
                $nombreCliente = $request->input('cliente');

                if ($nombreCliente && !$clienteId) {
                    $cliente = Cliente::firstOrCreate(
                        ['nombre' => $nombreCliente],
                        ['nombre' => $nombreCliente]
                    );
                    $clienteId = $cliente->id;
                }

                $numeroCotizacion = null;
                // Si es envío y es una edición de borrador que aún no tiene número, se genera.
                if (!$esBorrador && !$cotizacionIdEdicion) {
                    $usuarioId = Auth::id();
                    $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
                }

                $tipoCotizacionId = TipoCotizacion::getIdPorCodigo('EPP');
                if (!$tipoCotizacionId) {
                    throw new \RuntimeException('Tipo de cotización EPP no registrado en tipos_cotizacion (codigo=EPP)');
                }

                $tipoVenta = $request->input('tipo_venta');
                if (!in_array($tipoVenta, ['M', 'D', 'X'], true)) {
                    $tipoVenta = null;
                }

                // Preparar datos adicionales para especificaciones
                $informacionAdicional = [];
                $titulos = $request->input('informacion_adicional_titulo', []);
                $contenidos = $request->input('informacion_adicional_contenido', []);
                
                if (is_array($titulos) && is_array($contenidos)) {
                    foreach ($titulos as $index => $titulo) {
                        if (!empty($titulo) && isset($contenidos[$index])) {
                            $informacionAdicional[] = [
                                'titulo' => $titulo,
                                'contenido' => $contenidos[$index]
                            ];
                        }
                    }
                }
                
                // Preparar especificaciones existentes + información adicional
                $especificaciones = $request->input('especificaciones', []);
                if (!is_array($especificaciones)) {
                    $especificaciones = json_decode($especificaciones, true) ?? [];
                }
                
                // En modo edición, SIEMPRE preservar las especificaciones existentes primero
                if ($cotizacionIdEdicion) {
                    $cotizacionExistente = Cotizacion::find($cotizacionIdEdicion);
                    if ($cotizacionExistente && !empty($cotizacionExistente->especificaciones)) {
                        $especificacionesExistentes = json_decode($cotizacionExistente->especificaciones, true) ?? [];
                        // Mantener campos existentes que no se envían en la solicitud
                        $especificaciones = array_merge($especificacionesExistentes, $especificaciones);
                    }
                }
                
                // Agregar información adicional a especificaciones
                if (!empty($informacionAdicional)) {
                    $especificaciones['informacion_adicional'] = $informacionAdicional;
                }
                
                // Agregar campos fijos a especificaciones
                $condicionesPago = $request->input('condiciones_pago');
                $tiempoEntrega = $request->input('tiempo_entrega');
                $cuentasAutorizadas = $request->input('cuentas_autorizadas');
                
                // Solo actualizar si vienen nuevos valores explícitamente
                if (!empty($condicionesPago)) {
                    $especificaciones['condiciones_pago'] = $condicionesPago;
                } elseif (!$cotizacionIdEdicion) {
                    // Solo remover en nueva cotización, no en edición
                    unset($especificaciones['condiciones_pago']);
                }
                
                if (!empty($tiempoEntrega)) {
                    $especificaciones['tiempo_entrega'] = $tiempoEntrega;
                } elseif (!$cotizacionIdEdicion) {
                    // Solo remover en nueva cotización, no en edición
                    unset($especificaciones['tiempo_entrega']);
                }
                
                if (!empty($cuentasAutorizadas)) {
                    $especificaciones['cuentas_autorizadas'] = $cuentasAutorizadas;
                } elseif (!$cotizacionIdEdicion) {
                    // Solo remover en nueva cotización, no en edición
                    unset($especificaciones['cuentas_autorizadas']);
                }

                // Obtener observaciones generales del textarea
                $observacionesGenerales = $request->input('observaciones_generales_texto', '');
                $observacionesGeneralesJson = $request->input('observaciones_generales', '{}');
                
                // Extraer IVA del JSON y guardarlo en campo específico
                $iva = 0;
                if (!empty($observacionesGeneralesJson)) {
                    $obsDecoded = json_decode($observacionesGeneralesJson, true);
                    if (is_array($obsDecoded) && isset($obsDecoded['valor_iva'])) {
                        $iva = (float) $obsDecoded['valor_iva'];
                    }
                }
                
                // Logging para depuración
                Log::info('[CotizacionEppController] Datos recibidos para guardar', [
                    'observaciones_generales_texto' => $observacionesGenerales,
                    'observaciones_generales_json' => $observacionesGeneralesJson,
                    'iva_extraido' => $iva,
                    'condiciones_pago' => $request->input('condiciones_pago'),
                    'tiempo_entrega' => $request->input('tiempo_entrega'),
                    'cuentas_autorizadas' => $request->input('cuentas_autorizadas'),
                    'informacion_adicional_titulo' => $request->input('informacion_adicional_titulo'),
                    'informacion_adicional_contenido' => $request->input('informacion_adicional_contenido'),
                    'cliente_nit' => $request->input('cliente_nit'),
                    'cliente_direccion' => $request->input('cliente_direccion'),
                    'cliente_telefono' => $request->input('cliente_telefono'),
                    'especificaciones_finales' => $especificaciones,
                ]);

                if ($cotizacionIdEdicion) {
                    $cotizacion = Cotizacion::query()
                        ->where('id', $cotizacionIdEdicion)
                        ->where('asesor_id', Auth::id())
                        ->firstOrFail();

                    if ((int)$cotizacion->tipo_cotizacion_id !== (int)$tipoCotizacionId) {
                        throw new \RuntimeException('La cotización a editar no es de tipo EPP');
                    }

                    if (!$esBorrador && !$cotizacion->numero_cotizacion) {
                        $usuarioId = Auth::id();
                        $numeroCotizacion = $this->generarNumeroCotizacionService->generarNumeroCotizacionFormateado($usuarioId);
                    } else {
                        $numeroCotizacion = $cotizacion->numero_cotizacion;
                    }

                    $cotizacion->update([
                        'cliente_id' => $clienteId,
                        'numero_cotizacion' => $numeroCotizacion,
                        'tipo_venta' => $tipoVenta,
                        'es_borrador' => $esBorrador,
                        'estado' => $estado,
                        'fecha_envio' => !$esBorrador ? now() : null,
                        'especificaciones' => json_encode($especificaciones),
                        'observaciones_generales' => $observacionesGenerales,
                        'iva' => $iva,
                        'cliente_nit' => $request->input('cliente_nit'),
                        'cliente_direccion' => $request->input('cliente_direccion'),
                        'cliente_telefono' => $request->input('cliente_telefono'),
                    ]);
                    
                    Log::info('[CotizacionEppController] Cotización actualizada', [
                        'cotizacion_id' => $cotizacion->id,
                        'observaciones_generales_guardadas' => $cotizacion->observaciones_generales,
                        'especificaciones_guardadas' => $cotizacion->especificaciones,
                        'cliente_nit_guardado' => $cotizacion->cliente_nit,
                        'cliente_direccion_guardada' => $cotizacion->cliente_direccion,
                        'cliente_telefono_guardado' => $cotizacion->cliente_telefono,
                    ]);
                } else {
                    $cotizacion = Cotizacion::create([
                        'asesor_id' => Auth::id(),
                        'cliente_id' => $clienteId,
                        'numero_cotizacion' => $numeroCotizacion,
                        'tipo_cotizacion_id' => $tipoCotizacionId,
                        'tipo_venta' => $tipoVenta,
                        'es_borrador' => $esBorrador,
                        'estado' => $estado,
                        'fecha_envio' => !$esBorrador ? now() : null,
                        'especificaciones' => json_encode($especificaciones),
                        'observaciones_generales' => $observacionesGenerales,
                        'iva' => $iva,
                        'cliente_nit' => $request->input('cliente_nit'),
                        'cliente_direccion' => $request->input('cliente_direccion'),
                        'cliente_telefono' => $request->input('cliente_telefono'),
                    ]);
                    
                    Log::info('[CotizacionEppController] Cotización creada', [
                        'cotizacion_id' => $cotizacion->id,
                        'observaciones_generales_guardadas' => $cotizacion->observaciones_generales,
                        'especificaciones_guardadas' => $cotizacion->especificaciones,
                        'cliente_nit_guardado' => $cotizacion->cliente_nit,
                        'cliente_direccion_guardada' => $cotizacion->cliente_direccion,
                        'cliente_telefono_guardado' => $cotizacion->cliente_telefono,
                    ]);
                }

                $items = $request->input('items', []);
                if (is_string($items)) {
                    $items = json_decode($items, true) ?? [];
                }
                if (!is_array($items)) {
                    $items = [];
                }

                // Separar items por tipo
                $epps = array_filter($items, function($item) {
                    $tipo = strtolower($item['tipo'] ?? 'epp');
                    return $tipo === 'epp';
                });
                
                $prendas = array_filter($items, function($item) {
                    $tipo = strtolower($item['tipo'] ?? 'epp');
                    return $tipo === 'prenda';
                });

                $keptItemIds = [];
                $keptPrendaIds = [];

                // Procesar EPPs igual que antes
                foreach ($epps as $idx => $item) {
                    $payloadItemId = $item['id'] ?? null;
                    $payloadItemId = is_numeric($payloadItemId) ? (int)$payloadItemId : null;

                    $nombre = $item['nombre'] ?? ($item['nombre_completo'] ?? 'Sin nombre');
                    $cantidad = (int)($item['cantidad'] ?? 1);
                    $observ = $item['observaciones'] ?? null;

                    $itemId = null;
                    if ($cotizacionIdEdicion && $payloadItemId) {
                        $exists = DB::table('epp_items_cot')
                            ->where('id', $payloadItemId)
                            ->where('cotizacion_id', $cotizacion->id)
                            ->exists();

                        if ($exists) {
                            DB::table('epp_items_cot')
                                ->where('id', $payloadItemId)
                                ->update([
                                    'nombre' => $nombre,
                                    'cantidad' => $cantidad,
                                    'observaciones' => $observ,
                                    'updated_at' => now(),
                                ]);
                            $itemId = $payloadItemId;
                        }
                    }

                    if (!$itemId) {
                        $itemId = DB::table('epp_items_cot')->insertGetId([
                            'cotizacion_id' => $cotizacion->id,
                            'nombre' => $nombre,
                            'cantidad' => $cantidad,
                            'observaciones' => $observ,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $keptItemIds[] = $itemId;

                    $valorUnitario = $item['valor_unitario'] ?? null;
                    if ($valorUnitario !== null && $valorUnitario !== '') {
                        $valorUnitario = is_numeric($valorUnitario) ? (float)$valorUnitario : null;
                    }

                    if ($valorUnitario !== null) {
                        DB::table('epp_valor_unitario')->updateOrInsert(
                            ['epp_item_id' => $itemId],
                            [
                                'valor_unitario' => $valorUnitario,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    } else {
                        DB::table('epp_valor_unitario')->where('epp_item_id', $itemId)->delete();
                    }

                    // Sincronizar imágenes en edición: borrar las que ya no están (y sus archivos)
                    if ($cotizacionIdEdicion) {
                        $keep = $item['imagenes_keep'] ?? [];
                        if (is_string($keep)) {
                            $keep = json_decode($keep, true) ?? [];
                        }
                        if (!is_array($keep)) {
                            $keep = [];
                        }
                        $clear = (bool)($item['clear_imagenes'] ?? false);

                        if ($clear || count($keep) > 0) {
                            $existentes = DB::table('epp_img_cot')
                                ->where('epp_item_id', $itemId)
                                ->get(['id', 'ruta']);

                            foreach ($existentes as $row) {
                                $ruta = $row->ruta ?? null;
                                if (!$ruta) continue;
                                $debeBorrar = $clear ? true : !in_array($ruta, $keep, true);
                                if ($debeBorrar) {
                                    Storage::disk('public')->delete($ruta);
                                    DB::table('epp_img_cot')->where('id', $row->id)->delete();
                                }
                            }
                        }
                    }

                    // Imágenes: SOLO reemplazar si llegan archivos nuevos.
                    $imagenes = $request->file("items.$idx.imagenes", []);
                    if (is_array($imagenes) && count($imagenes) > 0) {
                        // Si llegan archivos nuevos, reemplazar las imágenes actuales (y borrar archivos antiguos)
                        $existentes = DB::table('epp_img_cot')->where('epp_item_id', $itemId)->get(['id', 'ruta']);
                        foreach ($existentes as $row) {
                            if ($row?->ruta) {
                                Storage::disk('public')->delete($row->ruta);
                            }
                        }
                        DB::table('epp_img_cot')->where('epp_item_id', $itemId)->delete();

                        foreach ($imagenes as $imgFile) {
                            if (!$imgFile || !$imgFile->isValid()) {
                                continue;
                            }

                            $originalName = $imgFile->getClientOriginalName();
                            $safeName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $originalName);
                            $rutaRelativa = "cotizaciones/{$cotizacion->id}/EPP/{$safeName}";

                            Storage::disk('public')->putFileAs(
                                "cotizaciones/{$cotizacion->id}/EPP",
                                $imgFile,
                                $safeName
                            );

                            DB::table('epp_img_cot')->insert([
                                'epp_item_id' => $itemId,
                                'ruta' => $rutaRelativa,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }

                // ==================== PROCESAR PRENDAS ====================
                foreach ($prendas as $idx => $item) {
                    $payloadItemId = $item['id'] ?? null;
                    $payloadItemId = is_numeric($payloadItemId) ? (int)$payloadItemId : null;

                    $nombre = $item['nombre'] ?? ($item['nombre_completo'] ?? 'Sin nombre');
                    $cantidad = (int)($item['cantidad'] ?? 1);
                    $observ = $item['observaciones'] ?? null;

                    $prendaId = null;
                    if ($cotizacionIdEdicion && $payloadItemId) {
                        $exists = DB::table('prenda_items_cot')
                            ->where('id', $payloadItemId)
                            ->where('cotizacion_id', $cotizacion->id)
                            ->exists();

                        if ($exists) {
                            DB::table('prenda_items_cot')
                                ->where('id', $payloadItemId)
                                ->update([
                                    'descripcion' => $nombre,
                                    'cantidad' => $cantidad,
                                    'observaciones' => $observ,
                                    'updated_at' => now(),
                                ]);
                            $prendaId = $payloadItemId;
                        }
                    }

                    if (!$prendaId) {
                        $prendaId = DB::table('prenda_items_cot')->insertGetId([
                            'cotizacion_id' => $cotizacion->id,
                            'descripcion' => $nombre,
                            'cantidad' => $cantidad,
                            'observaciones' => $observ,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $keptPrendaIds[] = $prendaId;

                    // Guardar valor unitario para la prenda
                    $valorUnitario = $item['valor_unitario'] ?? null;
                    if ($valorUnitario !== null && $valorUnitario !== '') {
                        $valorUnitario = is_numeric($valorUnitario) ? (float)$valorUnitario : null;
                    }

                    if ($valorUnitario !== null) {
                        DB::table('prenda_valor_unitario')->updateOrInsert(
                            ['prenda_item_id' => $prendaId],
                            [
                                'valor_unitario' => $valorUnitario,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    } else {
                        DB::table('prenda_valor_unitario')->where('prenda_item_id', $prendaId)->delete();
                    }

                    // Sincronizar imágenes en edición: borrar las que ya no están (y sus archivos)
                    if ($cotizacionIdEdicion) {
                        $keep = $item['imagenes_keep'] ?? [];
                        if (is_string($keep)) {
                            $keep = json_decode($keep, true) ?? [];
                        }
                        if (!is_array($keep)) {
                            $keep = [];
                        }
                        $clear = (bool)($item['clear_imagenes'] ?? false);

                        if ($clear || count($keep) > 0) {
                            $existentes = DB::table('prenda_img_cot')
                                ->where('prenda_item_id', $prendaId)
                                ->get(['id', 'ruta']);

                            foreach ($existentes as $row) {
                                $ruta = $row->ruta ?? null;
                                if (!$ruta) continue;
                                $debeBorrar = $clear ? true : !in_array($ruta, $keep, true);
                                if ($debeBorrar) {
                                    Storage::disk('public')->delete($ruta);
                                    DB::table('prenda_img_cot')->where('id', $row->id)->delete();
                                }
                            }
                        }
                    }

                    // Procesar imágenes de la prenda
                    $imagenes = $request->file("items.$idx.imagenes", []);
                    if (is_array($imagenes) && count($imagenes) > 0) {
                        // Si llegan archivos nuevos, reemplazar las imágenes actuales
                        $existentes = DB::table('prenda_img_cot')->where('prenda_item_id', $prendaId)->get(['id', 'ruta']);
                        foreach ($existentes as $row) {
                            if ($row?->ruta) {
                                Storage::disk('public')->delete($row->ruta);
                            }
                        }
                        DB::table('prenda_img_cot')->where('prenda_item_id', $prendaId)->delete();

                        foreach ($imagenes as $imgFile) {
                            if (!$imgFile || !$imgFile->isValid()) {
                                continue;
                            }

                            $originalName = $imgFile->getClientOriginalName();
                            $safeName = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $originalName);
                            $rutaRelativa = "cotizaciones/{$cotizacion->id}/PRENDA/{$safeName}";

                            Storage::disk('public')->putFileAs(
                                "cotizaciones/{$cotizacion->id}/PRENDA",
                                $imgFile,
                                $safeName
                            );

                            DB::table('prenda_img_cot')->insert([
                                'prenda_item_id' => $prendaId,
                                'ruta' => $rutaRelativa,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
                // ==================== FIN PRENDAS ====================

                // En edición: SOLO eliminar items si viene un payload completo (detectar por items total)
                // Si solo vienen algunos items en edición (ej: un solo EPP a editar), NO eliminar los que falten
                // Solo eliminar cuando vindividualmente se reciba un comando de borrado explícito
                if ($cotizacionIdEdicion && count($items) > 0) {
                    // Solo eliminar si el usuario envió EXPRESAMENTE la lista (con clear_imagenes o payload completo)
                    // Por ahora: NO eliminar items en modo edición a menos que sea una sincronización completa
                    // Esto evita perder prendas cuando se edita solo un EPP
                    
                    // Se podría mejorar con un flag del cliente que indique "sincronización completa" vs "edición parcial"
                    // Por ahora, mantener todos los items existentes
                    Log::info('[CotizacionEppController] Modo edición: preservando items existentes que no vienen en payload');
                }

                if (!$esBorrador) {
                    \App\Jobs\ProcesarEnvioCotizacionJob::dispatch(
                        $cotizacion->id,
                        $tipoCotizacionId
                    )->onQueue('cotizaciones');
                }

                return response()->json([
                    'success' => true,
                    'message' => $esBorrador
                        ? 'Cotización guardada como borrador'
                        : 'Cotización enviada - Número: ' . $numeroCotizacion,
                    'cotizacionId' => $cotizacion->id,
                    'numero_cotizacion' => $numeroCotizacion,
                    'redirect' => route('asesores.cotizaciones.index')
                        . '?'
                        . http_build_query([
                            'tab' => $esBorrador ? 'borradores' : 'cotizaciones',
                            'highlight' => $cotizacion->id,
                        ])
                ], $cotizacionIdEdicion ? 200 : 201);
            } catch (\Exception $e) {
                Log::error('Error al guardar cotización EPP', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar cotización EPP: ' . $e->getMessage(),
                ], 500);
            }
        }, attempts: 3);
    }
}
