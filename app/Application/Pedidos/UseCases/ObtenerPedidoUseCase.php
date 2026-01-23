<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractObtenerUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Obtener Pedido
 * 
 * REFACTORIZADO: Utiliza AbstractObtenerUseCase para obtención y validación
 * 
 * Antes: 316 líneas (185 líneas de lógica + 131 de obtención/validación)
 * Después: 250 líneas (solo lógica de enriquecimiento de datos)
 * Reducción: 21% (la lógica de enriquecimiento es compleja y específica)
 * 
 * Query Side - CQRS básico
 * Obtiene un pedido existente por ID con todas sus prendas y detalles enriquecidos
 */
class ObtenerPedidoUseCase extends AbstractObtenerUseCase
{
    public function ejecutar(int $pedidoId): PedidoResponseDTO
    {
        return $this->obtenerYEnriquecer($pedidoId);
    }

    /**
     * Personalización: Obtener todas las opciones de enriquecimiento
     */
    protected function obtenerOpciones(): array
    {
        return [
            'incluirPrendas' => true,
            'incluirEpps' => true,
            'incluirProcesos' => false,
            'incluirImagenes' => true,
        ];
    }

    /**
     * Personalización: Construir respuesta DTO con lógica de enriquecimiento compleja
     */
    protected function construirRespuesta(array $datosEnriquecidos): mixed
    {
        $modeloPedido = PedidoProduccion::find($datosEnriquecidos['id']);
        $prendasCompletas = $this->obtenerPrendasCompletas($modeloPedido);
        $eppsCompletos = $this->obtenerEppsCompletos($modeloPedido);

        return new PedidoResponseDTO(
            id: $datosEnriquecidos['id'],
            numero: $datosEnriquecidos['numero'],
            clienteId: $datosEnriquecidos['clienteId'],
            estado: $datosEnriquecidos['estado'],
            descripcion: $datosEnriquecidos['descripcion'],
            totalPrendas: $datosEnriquecidos['totalPrendas'],
            totalArticulos: $datosEnriquecidos['totalArticulos'],
            prendas: $prendasCompletas,
            epps: $eppsCompletos,
            mensaje: 'Pedido obtenido exitosamente'
        );
    }

    /**
     * Obtener prendas completas enriquecidas desde la BD
     */
    private function obtenerPrendasCompletas($modeloPedido): array
    {
        try {
            if (!$modeloPedido || !$modeloPedido->prendas) {
                Log::warning('Pedido sin prendas', ['pedido_id' => $modeloPedido?->id]);
                return [];
            }

            $prendasArray = [];

            foreach ($modeloPedido->prendas as $prenda) {
                Log::info('Procesando prenda', ['prenda_id' => $prenda->id, 'nombre' => $prenda->nombre_prenda]);

                $tallasEstructuradas = $this->construirEstructuraTallas($prenda);
                $variantes = $this->obtenerVariantes($prenda);
                $colorTela = $this->obtenerColorYTela($prenda);
                $imagenes = $prenda->fotos ? $prenda->fotos->pluck('ruta_webp')->toArray() : [];
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
                    'tiene_reflectivo' => false,
                ];
            }

            Log::info('Prendas procesadas exitosamente', ['pedido_id' => $modeloPedido->id, 'cantidad' => count($prendasArray)]);
            return $prendasArray;

        } catch (\Exception $e) {
            Log::error('Error obteniendo prendas completas', [
                'pedido_id' => $modeloPedido?->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Construir estructura de tallas: { GENERO: { TALLA: CANTIDAD } }
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
     */
    private function obtenerVariantes($prenda): array
    {
        $variantes = [];

        try {
            if ($prenda->variantes) {
                foreach ($prenda->variantes as $var) {
                    $mangaNombre = null;
                    if ($var->tipo_manga_id && $var->tipoManga) {
                        $mangaNombre = $var->tipoManga->nombre;
                    }

                    $broqueNombre = null;
                    if ($var->tipo_broche_boton_id && $var->tipoBroche) {
                        $broqueNombre = $var->tipoBroche->nombre;
                    }

                    $variantes[] = [
                        'talla' => null,
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
     */
    private function obtenerColorYTela($prenda): array
    {
        $colorTela = ['color' => null, 'tela' => null, 'ref_tela' => null];

        try {
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
     * Obtener EPPs del pedido enriquecidos
     */
    private function obtenerEppsCompletos($modeloPedido): array
    {
        $epps = [];

        try {
            if (!$modeloPedido || !$modeloPedido->epps) {
                return [];
            }

            foreach ($modeloPedido->epps as $epp) {
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

            Log::info('EPPs procesados exitosamente', ['pedido_id' => $modeloPedido->id, 'cantidad' => count($epps)]);
        } catch (\Exception $e) {
            Log::warning('Error obteniendo EPPs', [
                'pedido_id' => $modeloPedido?->id,
                'error' => $e->getMessage()
            ]);
        }

        return $epps;
    }
}


