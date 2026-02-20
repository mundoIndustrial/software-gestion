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
                        'especificaciones' => json_encode($request->input('especificaciones', [])),
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
                        'especificaciones' => json_encode($request->input('especificaciones', [])),
                    ]);
                }

                $observacionesGenerales = $request->input('observaciones_generales', []);
                if (is_string($observacionesGenerales)) {
                    $observacionesGenerales = json_decode($observacionesGenerales, true) ?? [];
                }
                if (!is_array($observacionesGenerales)) {
                    $observacionesGenerales = [];
                }

                DB::table('epp_cotizacion')->updateOrInsert(
                    ['cotizacion_id' => $cotizacion->id],
                    [
                        'tipo_venta' => $tipoVenta,
                        'observaciones_generales' => json_encode($observacionesGenerales),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $items = $request->input('items', []);
                if (is_string($items)) {
                    $items = json_decode($items, true) ?? [];
                }
                if (!is_array($items)) {
                    $items = [];
                }

                $keptItemIds = [];

                foreach ($items as $idx => $item) {
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

                // En edición: eliminar items que ya no vienen (con sus imágenes/valores)
                if ($cotizacionIdEdicion) {
                    $idsToDelete = DB::table('epp_items_cot')
                        ->where('cotizacion_id', $cotizacion->id)
                        ->when(count($keptItemIds) > 0, fn($q) => $q->whereNotIn('id', $keptItemIds))
                        ->pluck('id');

                    if ($idsToDelete->isNotEmpty()) {
                        // Borrar archivos físicos antes de borrar registros
                        $rutas = DB::table('epp_img_cot')->whereIn('epp_item_id', $idsToDelete)->pluck('ruta');
                        foreach ($rutas as $ruta) {
                            if ($ruta) {
                                Storage::disk('public')->delete($ruta);
                            }
                        }
                        DB::table('epp_img_cot')->whereIn('epp_item_id', $idsToDelete)->delete();
                        DB::table('epp_valor_unitario')->whereIn('epp_item_id', $idsToDelete)->delete();
                        DB::table('epp_items_cot')->whereIn('id', $idsToDelete)->delete();
                    }
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
                        ? 'Cotización EPP guardada como borrador'
                        : 'Cotización EPP enviada - Número: ' . $numeroCotizacion,
                    'cotizacionId' => $cotizacion->id,
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
