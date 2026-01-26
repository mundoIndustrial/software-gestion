<?php

namespace App\Application\Pedidos\Services;

use App\Models\PrendaPedido;
use App\Models\TipoManga;
use App\Models\TipoBrocheBoton;
use Illuminate\Support\Collection;

/**
 * Servicio para transformar datos de prendas
 * 
 * Responsabilidades:
 * - Traducir IDs a nombres (manga, broche, etc.)
 * - Preparar datos para factura
 * - Asegurar que procesos sea siempre array
 * - Enriquecer prendas con relaciones completas
 */
final class PrendaTransformerService
{
    /**
     * Transformar prenda completa para API/Frontend
     * Asegura que todas las relaciones estén cargadas como arrays
     */
    public function transformarPrendaCompleta(PrendaPedido $prenda): array
    {
        $prenda->load([
            'variantes',
            'tallas',
            'fotos',
            'coloresTelas.color',
            'coloresTelas.tela',
            'procesos.imagenes',
            'fotosTelas',
        ]);

        return [
            'id' => $prenda->id,
            'pedido_produccion_id' => $prenda->pedido_produccion_id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'descripcion' => $prenda->descripcion,
            'de_bodega' => (bool) $prenda->de_bodega,
            'variantes' => $this->transformarVariantes($prenda->variantes),
            'tallas' => $this->transformarTallas($prenda->tallas),
            'fotos' => $this->transformarFotos($prenda->fotos),
            'colores_telas' => $this->transformarColoresTelas($prenda->coloresTelas),
            'procesos' => $this->transformarProcesos($prenda->procesos), // Siempre array
            'fotos_telas' => $this->transformarFotosTelas($prenda->fotosTelas),
            'created_at' => $prenda->created_at?->toIso8601String(),
            'updated_at' => $prenda->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Transformar variantes con traducción de IDs a nombres
     */
    private function transformarVariantes(Collection $variantes): array
    {
        return $variantes->map(function($variante) {
            $manga = $variante->tipo_manga_id 
                ? TipoManga::find($variante->tipo_manga_id)
                : null;
            
            $broche = $variante->tipo_broche_boton_id
                ? TipoBrocheBoton::find($variante->tipo_broche_boton_id)
                : null;

            return [
                'id' => $variante->id,
                'tipo_manga_id' => $variante->tipo_manga_id,
                'tipo_manga_nombre' => $manga?->nombre,
                'manga_obs' => $variante->manga_obs,
                'tipo_broche_boton_id' => $variante->tipo_broche_boton_id,
                'tipo_broche_boton_nombre' => $broche?->nombre,
                'broche_boton_obs' => $variante->broche_boton_obs,
                'tiene_bolsillos' => (bool) $variante->tiene_bolsillos,
                'bolsillos_obs' => $variante->bolsillos_obs,
                'created_at' => $variante->created_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Transformar tallas agrupadas por género
     */
    private function transformarTallas(Collection $tallas): array
    {
        $agrupadas = $tallas->groupBy('genero');
        
        $resultado = [];
        foreach ($agrupadas as $genero => $tallasPorGenero) {
            $resultado[$genero] = $tallasPorGenero->mapWithKeys(function($talla) {
                return [$talla->talla => $talla->cantidad];
            })->toArray();
        }
        
        return $resultado;
    }

    /**
     * Transformar fotos con rutas correctas
     */
    private function transformarFotos(Collection $fotos): array
    {
        return $fotos->map(function($foto) {
            return [
                'id' => $foto->id,
                'ruta_original' => $foto->ruta_original,
                'ruta_webp' => $foto->ruta_webp,
                'orden' => $foto->orden,
                'url_webp' => $this->generarUrlStorage($foto->ruta_webp),
                'url_original' => $this->generarUrlStorage($foto->ruta_original),
            ];
        })->toArray();
    }

    /**
     * Transformar colores y telas
     */
    private function transformarColoresTelas(Collection $coloresTelas): array
    {
        return $coloresTelas->map(function($ct) {
            return [
                'id' => $ct->id,
                'color_id' => $ct->color_id,
                'color_nombre' => $ct->color?->nombre,
                'tela_id' => $ct->tela_id,
                'tela_nombre' => $ct->tela?->nombre,
            ];
        })->toArray();
    }

    /**
     * Transformar procesos - SIEMPRE array (incluso vacío)
     */
    private function transformarProcesos(Collection $procesos): array
    {
        return $procesos->map(function($proceso) {
            return [
                'id' => $proceso->id,
                'tipo_proceso_id' => $proceso->tipo_proceso_id,
                'tipo_proceso' => $proceso->tipoProceso?->nombre,
                'ubicaciones' => $proceso->ubicaciones 
                    ? json_decode($proceso->ubicaciones, true)
                    : [],
                'observaciones' => $proceso->observaciones,
                'estado' => $proceso->estado,
                'imagenes' => $this->transformarImagenesProceso($proceso->imagenes),
                'tallas' => $this->transformarTallasProceso($proceso->tallas),
                'created_at' => $proceso->created_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Transformar imágenes de procesos
     */
    private function transformarImagenesProceso(Collection $imagenes): array
    {
        return $imagenes->map(function($imagen) {
            return [
                'id' => $imagen->id,
                'ruta_original' => $imagen->ruta_original,
                'ruta_webp' => $imagen->ruta_webp,
                'orden' => $imagen->orden,
                'es_principal' => (bool) $imagen->es_principal,
                'url_webp' => $this->generarUrlStorage($imagen->ruta_webp),
                'url_original' => $this->generarUrlStorage($imagen->ruta_original),
            ];
        })->toArray();
    }

    /**
     * Transformar tallas del proceso
     */
    private function transformarTallasProceso(Collection $tallas): array
    {
        return $tallas->groupBy('genero')->map(function($tallasPorGenero) {
            return $tallasPorGenero->mapWithKeys(function($talla) {
                return [$talla->talla => $talla->cantidad];
            })->toArray();
        })->toArray();
    }

    /**
     * Transformar fotos de telas
     */
    private function transformarFotosTelas(Collection $fotosTelas): array
    {
        return $fotosTelas->map(function($foto) {
            return [
                'id' => $foto->id,
                'prenda_pedido_colores_telas_id' => $foto->prenda_pedido_colores_telas_id,
                'ruta_original' => $foto->ruta_original,
                'ruta_webp' => $foto->ruta_webp,
                'orden' => $foto->orden,
                'url_webp' => $this->generarUrlStorage($foto->ruta_webp),
                'url_original' => $this->generarUrlStorage($foto->ruta_original),
            ];
        })->toArray();
    }

    /**
     * Generar URL completa para storage
     */
    private function generarUrlStorage(string $ruta): string
    {
        if (!$ruta) {
            return '';
        }

        // Si ya es una URL completa, devolverla
        if (str_starts_with($ruta, 'http')) {
            return $ruta;
        }

        // Si no comienza con /, agregarlo
        if (!str_starts_with($ruta, '/')) {
            $ruta = '/' . $ruta;
        }

        return url('storage' . $ruta);
    }

    /**
     * Preparar prenda para factura/resumen
     * Incluye solo lo necesario con nombres descriptivos
     */
    public function transformarPrendaParaFactura(PrendaPedido $prenda): array
    {
        $prenda->load(['variantes', 'tallas', 'coloresTelas.color', 'coloresTelas.tela']);

        $variantes = $prenda->variantes->map(function($variante) {
            $manga = $variante->tipo_manga_id 
                ? TipoManga::find($variante->tipo_manga_id)
                : null;
            
            $broche = $variante->tipo_broche_boton_id
                ? TipoBrocheBoton::find($variante->tipo_broche_boton_id)
                : null;

            $observaciones = [];
            if ($variante->manga_obs) {
                $observaciones[] = "Manga: {$variante->manga_obs}";
            }
            if ($variante->broche_boton_obs) {
                $observaciones[] = "Broche/Botón: {$variante->broche_boton_obs}";
            }
            if ($variante->tiene_bolsillos && $variante->bolsillos_obs) {
                $observaciones[] = "Bolsillos: {$variante->bolsillos_obs}";
            }

            return [
                'manga' => $manga?->nombre,
                'broche_boton' => $broche?->nombre,
                'tiene_bolsillos' => (bool) $variante->tiene_bolsillos,
                'observaciones' => $observaciones,
            ];
        })->first() ?? [];

        return [
            'id' => $prenda->id,
            'nombre' => $prenda->nombre_prenda,
            'descripcion' => $prenda->descripcion,
            'manga' => $variantes['manga'] ?? null,
            'broche_boton' => $variantes['broche_boton'] ?? null,
            'tiene_bolsillos' => $variantes['tiene_bolsillos'] ?? false,
            'observaciones' => $variantes['observaciones'] ?? [],
            'tallas' => $this->formatearTallasParaFactura($prenda->tallas),
            'colores_telas' => $prenda->coloresTelas->map(function($ct) {
                return "{$ct->color?->nombre} - {$ct->tela?->nombre}";
            })->toArray(),
        ];
    }

    /**
     * Formatear tallas para factura (string legible)
     */
    private function formatearTallasParaFactura(Collection $tallas): string
    {
        $agrupadas = $tallas->groupBy('genero');
        
        $partes = [];
        foreach ($agrupadas as $genero => $tallasPorGenero) {
            $tallasStr = $tallasPorGenero->map(function($t) {
                return "{$t->talla} ({$t->cantidad})";
            })->implode(', ');
            
            $partes[] = "{$genero}: {$tallasStr}";
        }

        return implode(' | ', $partes);
    }
}
