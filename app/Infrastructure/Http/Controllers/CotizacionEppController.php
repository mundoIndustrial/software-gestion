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
                $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR';

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
                if (!$esBorrador) {
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

                $observacionesGenerales = $request->input('observaciones_generales', []);
                if (is_string($observacionesGenerales)) {
                    $observacionesGenerales = json_decode($observacionesGenerales, true) ?? [];
                }
                if (!is_array($observacionesGenerales)) {
                    $observacionesGenerales = [];
                }

                DB::table('epp_cotizacion')->insert([
                    'cotizacion_id' => $cotizacion->id,
                    'tipo_venta' => $tipoVenta,
                    'observaciones_generales' => json_encode($observacionesGenerales),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $items = $request->input('items', []);
                if (is_string($items)) {
                    $items = json_decode($items, true) ?? [];
                }
                if (!is_array($items)) {
                    $items = [];
                }

                foreach ($items as $idx => $item) {
                    $itemId = DB::table('epp_items_cot')->insertGetId([
                        'cotizacion_id' => $cotizacion->id,
                        'nombre' => $item['nombre'] ?? ($item['nombre_completo'] ?? 'Sin nombre'),
                        'cantidad' => (int)($item['cantidad'] ?? 1),
                        'observaciones' => $item['observaciones'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $valorUnitario = $item['valor_unitario'] ?? null;
                    if ($valorUnitario !== null && $valorUnitario !== '') {
                        $valorUnitario = is_numeric($valorUnitario) ? (float)$valorUnitario : null;
                    }

                    if ($valorUnitario !== null) {
                        DB::table('epp_valor_unitario')->insert([
                            'epp_item_id' => $itemId,
                            'valor_unitario' => $valorUnitario,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // Imágenes: se envían en FormData como files en el key `items[{idx}][imagenes][]`
                    $imagenes = $request->file("items.$idx.imagenes", []);
                    if (is_array($imagenes)) {
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
                ], 201);
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
