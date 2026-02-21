<?php

namespace App\Infrastructure\Http\Controllers\ControlCalidad;

use App\Http\Controllers\Controller;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class ControlCalidadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('control-calidad-access');
    }

    private function esControlDeCalidadProceso(?string $proceso): bool
    {
        $norm = strtolower(trim((string) $proceso));
        $norm = str_replace(['-', '_'], ' ', $norm);
        $norm = preg_replace('/\s+/', ' ', $norm);

        return in_array($norm, ['control de calidad', 'control calidad'], true);
    }

    public function dashboard(Request $request)
    {
        $usuario = auth()->user();

        $recibos = ConsecutivoReciboPedido::where('activo', 1)
            ->whereIn('tipo_recibo', ['COSTURA', 'REFLECTIVO'])
            ->with(['pedido', 'prenda', 'pedido.prendas'])
            ->orderBy('created_at', 'desc')
            ->get();

        $numeroPedidos = $recibos
            ->map(fn ($r) => $r->pedido?->numero_pedido)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $ultimoProcesoPorPedido = [];
        if (!empty($numeroPedidos)) {
            $procesosActuales = DB::table('procesos_prenda')
                ->whereIn('numero_pedido', $numeroPedidos)
                ->orderBy('numero_pedido', 'asc')
                ->orderBy('fecha_inicio', 'DESC')
                ->orderBy('id', 'DESC')
                ->select('numero_pedido', 'proceso', 'fecha_inicio', 'id')
                ->get();

            foreach ($procesosActuales as $p) {
                if (!isset($ultimoProcesoPorPedido[$p->numero_pedido])) {
                    $ultimoProcesoPorPedido[$p->numero_pedido] = $p->proceso;
                }
            }
        }

        // Filtrar por área de Control de Calidad según el último proceso en procesos_prenda
        $recibos = $recibos->filter(function ($recibo) use ($ultimoProcesoPorPedido) {
            $numeroPedido = $recibo->pedido?->numero_pedido;
            if (!$numeroPedido) {
                return false;
            }

            $proceso = $ultimoProcesoPorPedido[$numeroPedido] ?? null;
            if (!$proceso) {
                return false;
            }

            return $this->esControlDeCalidadProceso($proceso);
        })->values();

        // Formatear para reutilizar el mismo layout de tarjetas
        $prendasConRecibos = $recibos->map(function ($recibo) {
            $pedido = $recibo->pedido;
            $prenda = $recibo->prenda ?: $pedido?->prendas?->first();

            return [
                'prenda_id' => $prenda->id ?? 0,
                'pedido_id' => $pedido->id ?? 0,
                'numero_pedido' => $pedido->numero_pedido ?? '',
                'cliente' => $pedido->cliente ?? '',
                'nombre_prenda' => $prenda->nombre_prenda ?? 'Pedido',
                'descripcion' => $prenda->descripcion ?? ($pedido->descripcion ?? ''),
                'de_bodega' => $prenda->de_bodega ?? null,
                'recibos' => [[
                    'id' => $recibo->id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'consecutivo_actual' => $recibo->consecutivo_actual,
                    'consecutivo_inicial' => $recibo->consecutivo_inicial,
                    'notas' => $recibo->notas,
                    'creado_en' => $recibo->created_at,
                ]],
                'total_recibos' => 1,
                'fecha_creacion' => $recibo->created_at,
                'estado_pedido' => $pedido->estado ?? 'Pendiente',
            ];
        });

        return view('control-calidad.dashboard', [
            'usuario' => $usuario,
            'prendasConRecibos' => $prendasConRecibos,
        ]);
    }

    /**
     * Ver detalle completo del recibo/pedido (reutiliza la vista del módulo Operario)
     */
    public function verPedido(Request $request, $numeroPedido)
    {
        $usuario = Auth::user();

        $pedidoDB = PedidoProduccion::where('numero_pedido', $numeroPedido)
            ->with('prendas')
            ->first();

        if (!$pedidoDB) {
            return redirect()->route('control-calidad.dashboard')
                ->with('error', 'Pedido no encontrado');
        }

        // Seguridad adicional: solo permitir ver pedidos que estén en Control de Calidad
        $ultimoProceso = DB::table('procesos_prenda')
            ->where('numero_pedido', $pedidoDB->numero_pedido)
            ->orderBy('fecha_inicio', 'desc')
            ->orderBy('id', 'desc')
            ->value('proceso');

        if (!$this->esControlDeCalidadProceso($ultimoProceso)) {
            return redirect()->route('control-calidad.dashboard')
                ->with('error', 'Este pedido no está en Control de Calidad');
        }

        $fotos = $this->obtenerFotosPedido($numeroPedido);

        $tipoRecibo = strtoupper(trim((string) $request->query('tipo_recibo', '')));
        $tipoRecibo = $tipoRecibo === '' ? null : $tipoRecibo;

        // Para reutilizar operario.ver-pedido sin cambios, inyectamos el consecutivo
        // del recibo seleccionado en el mismo campo que el blade espera.
        $numeroReciboSeleccionado = null;
        if ($tipoRecibo) {
            $reciboSeleccionado = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoDB->id)
                ->where('tipo_recibo', $tipoRecibo)
                ->where('activo', 1)
                ->first();

            if ($reciboSeleccionado) {
                $numeroReciboSeleccionado = $reciboSeleccionado->consecutivo_actual;
            }
        }

        // Fallback a COSTURA (compatibilidad)
        if (!$numeroReciboSeleccionado) {
            $reciboCostura = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoDB->id)
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1)
                ->first();

            if ($reciboCostura) {
                $numeroReciboSeleccionado = $reciboCostura->consecutivo_actual;
            }
        }

        return view('operario.ver-pedido', [
            'operario' => null,
            'pedido' => [
                'numero_pedido' => $pedidoDB->numero_pedido,
                'numero_recibo_costura' => $numeroReciboSeleccionado,
                'cliente' => $pedidoDB->cliente,
                'asesor' => $pedidoDB->asesor_id ? $pedidoDB->asesor_id : 'N/A',
                'asesora' => $pedidoDB->asesor_id ? $pedidoDB->asesor_id : 'N/A',
                'forma_de_pago' => $pedidoDB->forma_de_pago ?? 'N/A',
                'forma_pago' => $pedidoDB->forma_de_pago ?? 'N/A',
                'estado' => $pedidoDB->estado ?? 'Pendiente',
                'area' => 'Control de Calidad',
                'fecha_creacion' => $pedidoDB->created_at ? $pedidoDB->created_at->format('d/m/Y') : date('d/m/Y'),
                'fecha_estimada' => $pedidoDB->fecha_estimada ? $pedidoDB->fecha_estimada->format('d/m/Y') : null,
                'descripcion' => $pedidoDB->descripcion ?? 'N/A',
                'descripcion_prendas' => $pedidoDB->descripcion ?? 'N/A',
                'cantidad' => $pedidoDB->total_prendas ?? 0,
                'novedades' => $pedidoDB->novedades ?? 'Sin novedades',
            ],
            'usuario' => $usuario,
            'fotos' => $fotos,
        ]);
    }

    private function obtenerFotosPedido($numeroPedido)
    {
        $cacheKey = "fotos_pedido_{$numeroPedido}";

        return Cache::remember($cacheKey, 600, function() use ($numeroPedido) {
            $fotos = [];

            try {
                $pedido = PedidoProduccion::select('id', 'cotizacion_id')
                    ->where('numero_pedido', $numeroPedido)
                    ->first();

                if (!$pedido || !$pedido->cotizacion_id) {
                    return [];
                }

                $prendasCotIds = \App\Models\PrendaCot::where('cotizacion_id', $pedido->cotizacion_id)
                    ->pluck('id')
                    ->toArray();

                if (empty($prendasCotIds)) {
                    return [];
                }

                $fotosPrendas = \App\Models\PrendaFotoCot::select('ruta_webp', 'ruta_original')
                    ->whereIn('prenda_cot_id', $prendasCotIds)
                    ->orderBy('orden')
                    ->get();

                foreach($fotosPrendas as $foto) {
                    $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                    if($ruta) $fotos[] = $ruta;
                }

                $fotosTelas = \App\Models\PrendaTelaFotoCot::select('ruta_webp', 'ruta_original')
                    ->whereIn('prenda_cot_id', $prendasCotIds)
                    ->orderBy('orden')
                    ->get();

                foreach($fotosTelas as $foto) {
                    $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                    if($ruta) $fotos[] = $ruta;
                }

                $logoCotIds = \App\Models\LogoCotizacion::select('id')
                    ->where('cotizacion_id', $pedido->cotizacion_id)
                    ->pluck('id')
                    ->toArray();

                if (!empty($logoCotIds)) {
                    $fotosLogos = \App\Models\LogoFotoCot::select('ruta_webp', 'ruta_original')
                        ->whereIn('logo_cotizacion_id', $logoCotIds)
                        ->orderBy('orden')
                        ->get();

                    foreach($fotosLogos as $foto) {
                        $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                        if($ruta) $fotos[] = $ruta;
                    }
                }
            } catch (\Exception $e) {
                return [];
            }

            return $fotos;
        });
    }
}
