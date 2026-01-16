<?php

namespace App\Domain\PedidoProduccion\Repositories;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Repositorio para acceso a datos de Pedidos de Producción
 * Responsabilidad: Encapsular todas las queries de pedidos
 */
class PedidoProduccionRepository
{
    /**
     * Obtener pedido por ID con relaciones
     */
    public function obtenerPorId(int $id): ?PedidoProduccion
    {
        return PedidoProduccion::with([
            'cotizacion.cliente',
            'cotizacion.tipoCotizacion',
            'prendas.variantes',
            'prendas.fotos',
            'prendas.fotosTelas',
            'prendas.procesos',
            'prendas.procesos.imagenes',
        ])->find($id);
    }

    /**
     * Obtener pedidos del asesor con filtros
     */
    public function obtenerPedidosAsesor(array $filtros = []): LengthAwarePaginator
    {
        $query = PedidoProduccion::query()
            ->where('asesor_id', Auth::id())
            ->with(['cotizacion', 'prendas']);

        // Aplicar filtros
        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    /**
     * Verificar si el pedido pertenece al asesor
     */
    public function perteneceAlAsesor(int $pedidoId, int $asesorId): bool
    {
        return PedidoProduccion::where('id', $pedidoId)
            ->where('asesor_id', $asesorId)
            ->exists();
    }

    /**
     * Actualizar cantidad total del pedido
     */
    public function actualizarCantidadTotal(string $numeroPedido): void
    {
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
        
        if ($pedido) {
            $cantidadTotal = $pedido->prendas()->sum('cantidad');
            $pedido->update(['cantidad_total' => $cantidadTotal]);
        }
    }

    /**
     * Obtener datos completos de factura de un pedido
     * Incluye prendas con variantes, colores, telas e imágenes
     */
    public function obtenerDatosFactura(int $pedidoId): array
    {
        $pedido = $this->obtenerPorId($pedidoId);
        
        if (!$pedido) {
            throw new \Exception('Pedido no encontrado');
        }

        // Construir datos base
        $datos = [
            'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
            'numero_pedido_temporal' => $pedido->numero_pedido ?? 0,
            'cliente' => $pedido->cliente ?? 'Cliente Desconocido',
            'asesora' => is_object($pedido->asesora) ? $pedido->asesora->name : ($pedido->asesora ?? 'Sin asignar'),
            'forma_de_pago' => $pedido->forma_de_pago ?? 'No especificada',
            'fecha' => $pedido->created_at ? $pedido->created_at->format('d/m/Y') : date('d/m/Y'),
            'fecha_creacion' => $pedido->created_at ? $pedido->created_at->format('d/m/Y') : date('d/m/Y'),
            'observaciones' => $pedido->observaciones ?? '',
            'prendas' => [],
            'total_items' => 0,
        ];

        // Procesar prendas
        foreach ($pedido->prendas as $prenda) {
            $cantidadTotal = 0;
            $colores = [];
            $telas = [];
            $referencias = [];
            $especificaciones = [];  // Nuevas especificaciones

            // Contar cantidad total desde variantes y extraer especificaciones
            foreach ($prenda->variantes as $variante) {
                $cantidadTotal += $variante->cantidad ?? 0;
                
                // Extraer especificaciones de la variante
                $spec = [
                    'talla' => $variante->talla ?? '',
                    'cantidad' => $variante->cantidad ?? 0,
                    'manga' => null,
                    'manga_obs' => $variante->manga_obs ?? '',
                    'broche' => null,
                    'broche_obs' => $variante->broche_boton_obs ?? '',
                    'bolsillos' => $variante->tiene_bolsillos ?? false,
                    'bolsillos_obs' => $variante->bolsillos_obs ?? '',
                ];
                
                // Obtener nombre del tipo de manga
                if ($variante->tipo_manga_id) {
                    try {
                        $manga = \DB::table('tipos_manga')
                            ->where('id', $variante->tipo_manga_id)
                            ->value('nombre');
                        $spec['manga'] = $manga;
                    } catch (\Exception $e) {
                        \Log::debug('[FACTURA] Error obteniendo tipo de manga: ' . $e->getMessage());
                    }
                }
                
                // Obtener nombre del tipo de broche/botón
                if ($variante->tipo_broche_boton_id) {
                    try {
                        $broche = \DB::table('tipos_broche_boton')
                            ->where('id', $variante->tipo_broche_boton_id)
                            ->value('nombre');
                        $spec['broche'] = $broche;
                    } catch (\Exception $e) {
                        \Log::debug('[FACTURA] Error obteniendo tipo de broche: ' . $e->getMessage());
                    }
                }
                
                $especificaciones[] = $spec;
                
                // Obtener tela y referencia
                if ($variante->tela_id) {
                    try {
                        $telaData = \DB::table('telas_prenda')
                            ->where('id', $variante->tela_id)
                            ->select('nombre', 'referencia')
                            ->first();
                        
                        if ($telaData) {
                            if ($telaData->nombre && !in_array($telaData->nombre, $telas)) {
                                $telas[] = $telaData->nombre;
                            }
                            if ($telaData->referencia && !in_array($telaData->referencia, $referencias)) {
                                $referencias[] = $telaData->referencia;
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::debug('[FACTURA] Error obteniendo tela y referencia: ' . $e->getMessage());
                    }
                }
                
                // Obtener color
                if ($variante->color_id) {
                    try {
                        $color = \DB::table('colores_prenda')
                            ->where('id', $variante->color_id)
                            ->value('nombre');
                        if ($color && !in_array($color, $colores)) {
                            $colores[] = $color;
                        }
                    } catch (\Exception $e) {
                        \Log::debug('[FACTURA] Error obteniendo color: ' . $e->getMessage());
                    }
                }
            }

            // Obtener foto principal
            $foto = $prenda->fotos->first();
            
            // Obtener fotos de telas (con verificación)
            $fotoTelas = [];
            if ($prenda->fotosTelas && $prenda->fotosTelas->count() > 0) {
                $fotoTelas = $prenda->fotosTelas->map(fn($f) => $f->url)->toArray();
            }
            
            // Obtener todas las fotos de prenda
            $fotosPrend = $prenda->fotos->map(fn($f) => $f->url)->toArray();
            
            \Log::info('[FACTURA] Fotos de prenda: ' . json_encode([
                'nombre_prenda' => $prenda->nombre_prenda,
                'fotos_prenda_count' => $prenda->fotos->count(),
                'fotos_telas_count' => $prenda->fotosTelas ? $prenda->fotosTelas->count() : 0,
                'fotos_telas' => $fotoTelas,
            ]));

            // Tallas desde JSON (cantidad_talla es string JSON en la BD)
            $tallas = [];
            if ($prenda->cantidad_talla) {
                if (is_array($prenda->cantidad_talla)) {
                    $tallas = $prenda->cantidad_talla;
                } else if (is_string($prenda->cantidad_talla)) {
                    $tallas = json_decode($prenda->cantidad_talla, true) ?? [];
                }
            }
            
            \Log::info('[FACTURA] Tallas de prenda: ' . json_encode([
                'nombre_prenda' => $prenda->nombre_prenda,
                'cantidad_talla_raw' => $prenda->cantidad_talla,
                'tallas_final' => $tallas,
            ]));

            // Obtener procesos
            $procesos = [];
            foreach ($prenda->procesos as $proc) {
                $procTallas = is_array($proc->cantidad_talla) ? $proc->cantidad_talla : [];
                
                // Ubicaciones puede ser array o string JSON
                $ubicaciones = [];
                if ($proc->ubicaciones) {
                    if (is_array($proc->ubicaciones)) {
                        $ubicaciones = $proc->ubicaciones;
                    } else if (is_string($proc->ubicaciones)) {
                        $ubicaciones = json_decode($proc->ubicaciones, true) ?? [];
                    }
                }
                
                // Obtener imágenes del proceso
                $imagenesProceso = $proc->imagenes ? $proc->imagenes->map(fn($img) => $img->url)->toArray() : [];
                
                $proc_item = [
                    'tipo' => $proc->tipo ?? 'Proceso',
                    'tallas' => $procTallas,
                    'observaciones' => $proc->observaciones ?? '',
                    'ubicaciones' => $ubicaciones,
                    'imagenes' => $imagenesProceso,  // Agregar imágenes del proceso
                ];
                
                $procesos[] = $proc_item;
                
                \Log::info('[FACTURA] Proceso de prenda: ' . json_encode($proc_item));
            }

            // Construir array de prenda
            $prendasFormato = [
                'nombre' => $prenda->nombre_prenda,
                'descripcion' => $prenda->descripcion,
                'imagen' => $foto ? $foto->url : null,  // Usar el accessor 'url' del modelo
                'imagen_tela' => !empty($fotoTelas) ? $fotoTelas[0] : null,  // Primera foto de tela
                'imagenes' => $fotosPrend,  // Todas las fotos de prenda
                'imagenes_tela' => $fotoTelas,  // Todas las fotos de tela
                'tela' => !empty($telas) ? implode(', ', $telas) : null,
                'color' => !empty($colores) ? implode(', ', $colores) : null,
                'ref' => !empty($referencias) ? implode(', ', $referencias) : null,
                'tallas' => $tallas,
                'variantes' => $especificaciones,  // NUEVAS ESPECIFICACIONES
                'procesos' => $procesos,
            ];
            
            \Log::info('[FACTURA] Prenda formateada: ' . json_encode($prendasFormato));

            $datos['prendas'][] = $prendasFormato;
            $datos['total_items'] += $cantidadTotal;
        }

        return $datos;
    }
}
