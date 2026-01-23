<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Obtener Pedido
 * 
 * Query Side - CQRS básico
 * Obtiene un pedido existente por ID con todas sus prendas y detalles enriquecidos
 * 
 * Estructura de BD utilizada:
 * - pedidos_produccion (tabla principal)
 * - prendas_pedido (prendas del pedido)
 * - prenda_pedido_tallas (tallas de cada prenda)
 * - prenda_pedido_variantes (variantes: manga, broche, bolsillos)
 * - prenda_pedido_colores_telas (colores y telas)
 * - prenda_fotos_pedido (fotos de prendas)
 * - prenda_fotos_tela_pedido (fotos de telas)
 * - pedido_epp (EPPs del pedido)
 * - tipos_manga, tipos_broche_boton (catálogos)
 */
class ObtenerPedidoUseCase
{
    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(int $pedidoId): PedidoResponseDTO
    {
        $pedido = $this->pedidoRepository->porId($pedidoId);

        if (!$pedido) {
            throw new \DomainException("Pedido $pedidoId no encontrado");
        }

        // Obtener prendas completas desde la base de datos
        $prendasCompletas = $this->obtenerPrendasCompletas($pedidoId);
        
        // Obtener EPPs del pedido
        $eppsCompletos = $this->obtenerEpps($pedidoId);

        return new PedidoResponseDTO(
            id: $pedido->id(),
            numero: (string)$pedido->numero(),
            clienteId: $pedido->clienteId(),
            estado: $pedido->estado()->valor(),
            descripcion: $pedido->descripcion(),
            totalPrendas: $pedido->totalPrendas(),
            totalArticulos: $pedido->totalArticulos(),
            prendas: $prendasCompletas,
            epps: $eppsCompletos,
            mensaje: 'Pedido obtenido exitosamente'
        );
    }

    /**
     * Obtener prendas completas enriquecidas desde la BD
     */
    private function obtenerPrendasCompletas(int $pedidoId): array
    {
        try {
            // Obtener el modelo de Eloquent para acceso a relaciones BD
            $modeloPedido = PedidoProduccion::find($pedidoId);
            
            if (!$modeloPedido || !$modeloPedido->prendas) {
                Log::warning('Pedido sin prendas', ['pedido_id' => $pedidoId]);
                return [];
            }

            $prendasArray = [];

            foreach ($modeloPedido->prendas as $prenda) {
                Log::info('Procesando prenda', ['prenda_id' => $prenda->id, 'nombre' => $prenda->nombre_prenda]);

                // Construir estructura completa de tallas { GENERO: { TALLA: CANTIDAD } }
                $tallasEstructuradas = $this->construirEstructuraTallas($prenda);

                // Obtener variantes (manga, broche, bolsillos)
                $variantes = $this->obtenerVariantes($prenda);

                // Obtener color y tela
                $colorTela = $this->obtenerColorYTela($prenda);

                // Obtener imágenes de prenda
                $imagenes = $prenda->fotos ? $prenda->fotos->pluck('ruta_webp')->toArray() : [];

                // Obtener imágenes de tela
                $imagenesTela = $this->obtenerImagenesTela($prenda);

                $prendasArray[] = [
                    'id' => $prenda->id,
                    'prenda_pedido_id' => $prenda->id,
                    'nombre' => $prenda->nombre_prenda,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'numero' => $prenda->numero ?? null,
                    'tela' => $colorTela['tela'] ?? null,
                    'color' => $colorTela['color'] ?? null,
                    'ref' => $colorTela['ref_tela'] ?? null,
                    'origen' => $prenda->origen ?? null,
                    'descripcion' => $prenda->descripcion,
                    'de_bodega' => (bool)$prenda->de_bodega,
                    'tallas' => $tallasEstructuradas,
                    'variantes' => $variantes,
                    'imagenes' => $imagenes,
                    'imagenes_tela' => $imagenesTela,
                    'manga' => $variantes[0]['manga'] ?? null,
                    'obs_manga' => $variantes[0]['manga_obs'] ?? null,
                    'broche' => $variantes[0]['broche'] ?? null,
                    'obs_broche' => $variantes[0]['broche_obs'] ?? null,
                    'tiene_bolsillos' => isset($variantes[0]) ? (bool)$variantes[0]['bolsillos'] : false,
                    'obs_bolsillos' => $variantes[0]['bolsillos_obs'] ?? null,
                    'tiene_reflectivo' => false, // Según las tablas, no existe este campo
                ];
            }

            Log::info('Prendas procesadas exitosamente', ['pedido_id' => $pedidoId, 'cantidad' => count($prendasArray)]);
            return $prendasArray;

        } catch (\Exception $e) {
            Log::error('Error obteniendo prendas completas', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Construir estructura de tallas: { GENERO: { TALLA: CANTIDAD } }
     * Basado en tabla: prenda_pedido_tallas
     */
    private function construirEstructuraTallas($prenda): array
    {
        $tallas = [];

        try {
            if ($prenda->tallas) {
                foreach ($prenda->tallas as $talla) {
                    $genero = $talla->genero ?? 'GENERAL';
                    
                    if (!isset($tallas[$genero])) {
                        $tallas[$genero] = [];
                    }

                    $tallas[$genero][$talla->talla] = (int)$talla->cantidad;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error construyendo estructura de tallas', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $tallas;
    }

    /**
     * Obtener variantes (manga, broche, bolsillos)
     * Basado en tabla: prenda_pedido_variantes
     */
    private function obtenerVariantes($prenda): array
    {
        $variantes = [];

        try {
            if ($prenda->variantes) {
                foreach ($prenda->variantes as $var) {
                    // Obtener nombre de manga si existe tipo_manga_id
                    $mangaNombre = null;
                    if ($var->tipo_manga_id && $var->tipoManga) {
                        $mangaNombre = $var->tipoManga->nombre;
                    }

                    // Obtener nombre de broche si existe tipo_broche_boton_id
                    $broqueNombre = null;
                    if ($var->tipo_broche_boton_id && $var->tipoBroche) {
                        $broqueNombre = $var->tipoBroche->nombre;
                    }

                    $variantes[] = [
                        'talla' => null, // Las variantes no tienen talla específica en la tabla
                        'cantidad' => 0,
                        'manga' => $mangaNombre,
                        'manga_obs' => $var->manga_obs,
                        'broche' => $broqueNombre,
                        'broche_obs' => $var->broche_boton_obs,
                        'bolsillos' => (bool)$var->tiene_bolsillos,
                        'bolsillos_obs' => $var->bolsillos_obs,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error obteniendo variantes', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $variantes;
    }

    /**
     * Obtener color y tela de la prenda
     * Basado en tabla: prenda_pedido_colores_telas
     */
    private function obtenerColorYTela($prenda): array
    {
        $colorTela = [
            'color' => null,
            'tela' => null,
            'ref_tela' => null,
        ];

        try {
            // Obtener la primera relación color-tela (puede haber múltiples)
            if ($prenda->coloresTelas && $prenda->coloresTelas->first()) {
                $ct = $prenda->coloresTelas->first();

                if ($ct->color) {
                    $colorTela['color'] = $ct->color->nombre;
                }

                if ($ct->tela) {
                    $colorTela['tela'] = $ct->tela->nombre;
                    $colorTela['ref_tela'] = $ct->tela->referencia;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error obteniendo color y tela', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $colorTela;
    }

    /**
     * Obtener imágenes de tela
     * Basado en tabla: prenda_fotos_tela_pedido
     */
    private function obtenerImagenesTela($prenda): array
    {
        $imagenes = [];

        try {
            if ($prenda->coloresTelas) {
                foreach ($prenda->coloresTelas as $ct) {
                    if ($ct->fotos) {
                        foreach ($ct->fotos as $foto) {
                            $imagenes[] = $foto->ruta_webp;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error obteniendo imágenes de tela', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $imagenes;
    }

    /**
     * Obtener EPPs del pedido
     * Basado en tabla: pedido_epp
     */
    private function obtenerEpps(int $pedidoId): array
    {
        $epps = [];

        try {
            $modeloPedido = PedidoProduccion::find($pedidoId);
            
            if (!$modeloPedido || !$modeloPedido->epps) {
                return [];
            }

            foreach ($modeloPedido->epps as $epp) {
                // Obtener imágenes del EPP
                $imagenes = $epp->imagenes ? $epp->imagenes->pluck('ruta_web')->toArray() : [];

                $epps[] = [
                    'id' => $epp->id,
                    'pedido_epp_id' => $epp->id,
                    'epp_id' => $epp->epp_id,
                    'epp_nombre' => $epp->epp?->nombre_completo ?? $epp->epp?->nombre ?? null,
                    'cantidad' => $epp->cantidad,
                    'observaciones' => $epp->observaciones,
                    'imagenes' => $imagenes,
                ];
            }

            Log::info('EPPs procesados exitosamente', ['pedido_id' => $pedidoId, 'cantidad' => count($epps)]);
        } catch (\Exception $e) {
            Log::warning('Error obteniendo EPPs', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage()
            ]);
        }

        return $epps;
    }
}


