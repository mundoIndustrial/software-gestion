<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Domain\Pedidos\Services\PrendaTransformadorServiceContract;

use App\Models\TipoManga;
use App\Models\TipoBrocheBoton;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PrendaTransformadorService
 * 
 * Servicio que encapsula la lógica de transformación de prendas para edición.
 * 
 * Responsabilidades:
 * - Enriquecer variantes con nombres de tipos (manga, broche)
 * - Cargar tallas con colores por prenda
 * - Preparar datos para formulario de edición
 */
class PrendaTransformadorService implements PrendaTransformadorServiceContract
{
    /**
     * Transforma variantes enriqueciendo con nombres de tipos
     */
    public function enriquecerVariantes(object $prenda): void
    {
        if (!$prenda->variantes) {
            return;
        }

        foreach ($prenda->variantes as $variante) {
            // Cargar nombre de manga si existe
            if ($variante->tipo_manga_id && $variante->manga_nombre === null) {
                try {
                    $manga = TipoManga::find($variante->tipo_manga_id);
                    $variante->manga_nombre = $manga?->nombre;
                } catch (\Exception $e) {
                    Log::debug('[PrendaTransformadorService] Error obtener manga', [
                        'tipo_manga_id' => $variante->tipo_manga_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Cargar nombre de broche si existe
            if ($variante->tipo_broche_boton_id && $variante->broche_nombre === null) {
                try {
                    $broche = TipoBrocheBoton::find($variante->tipo_broche_boton_id);
                    $variante->broche_nombre = $broche?->nombre;
                } catch (\Exception $e) {
                    Log::debug('[PrendaTransformadorService] Error obtener broche', [
                        'tipo_broche_boton_id' => $variante->tipo_broche_boton_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Carga talla_colores para una prenda desde la BD
     */
    public function cargarTallaColores(object $prenda): void
    {
        try {
            $tallaColores = DB::table('prenda_pedido_talla_colores as ptc')
                ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
                ->where('pt.prenda_pedido_id', $prenda->id)
                ->select([
                    'ptc.id',
                    'ptc.prenda_pedido_talla_id',
                    'pt.genero',
                    'pt.talla',
                    'ptc.tela_id',
                    'ptc.tela_nombre',
                    'ptc.color_id',
                    'ptc.color_nombre',
                    'ptc.cantidad',
                    'ptc.referencia',
                    'ptc.observaciones',
                    'ptc.imagen_ruta',
                ])
                ->get()
                ->toArray();

            $tallaColores = array_map(function ($color) {
                if (!empty($color->imagen_ruta)) {
                    $ruta = str_replace('\\', '/', $color->imagen_ruta);

                    if (!str_starts_with($ruta, '/storage/')) {
                        if (str_starts_with($ruta, 'storage/')) {
                            $ruta = '/' . $ruta;
                        } elseif (!str_starts_with($ruta, '/')) {
                            $ruta = '/storage/' . $ruta;
                        }
                    }

                    $color->imagen_ruta = $ruta;
                }

                return $color;
            }, $tallaColores);

            $prenda->talla_colores = $tallaColores;

            Log::debug('[PrendaTransformadorService] Talla colores cargados', [
                'prenda_id' => $prenda->id,
                'cantidad' => count($tallaColores)
            ]);

        } catch (\Exception $e) {
            Log::warning('[PrendaTransformadorService] Error cargando talla_colores', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage()
            ]);
            $prenda->talla_colores = [];
        }
    }

    /**
     * Enriquece todas las prendas con datos de edición
     */
    public function enriquecerPrendas($prendas): void
    {
        if (!$prendas) {
            return;
        }

        foreach ($prendas as $prenda) {
            $this->enriquecerVariantes($prenda);
            $this->cargarTallaColores($prenda);
        }
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {PrendaTransformadorService}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
