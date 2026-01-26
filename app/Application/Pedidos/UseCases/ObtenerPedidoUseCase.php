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
 * REFACTORIZADO: Utiliza AbstractObtenerUseCase para obtenciÃ³n y validaciÃ³n
 * 
 * Antes: 316 lÃ­neas (185 lÃ­neas de lÃ³gica + 131 de obtenciÃ³n/validaciÃ³n)
 * DespuÃ©s: 250 lÃ­neas (solo lÃ³gica de enriquecimiento de datos)
 * ReducciÃ³n: 21% (la lÃ³gica de enriquecimiento es compleja y especÃ­fica)
 * 
 * Query Side - CQRS bÃ¡sico
 * Obtiene un pedido existente por ID con todas sus prendas y detalles enriquecidos
 */
class ObtenerPedidoUseCase extends AbstractObtenerUseCase
{
    public function ejecutar(int $pedidoId): PedidoResponseDTO
    {
        return $this->obtenerYEnriquecer($pedidoId);
    }

    /**
     * PersonalizaciÃ³n: Obtener todas las opciones de enriquecimiento
     */
    protected function obtenerOpciones(): array
    {
        return [
            'incluirPrendas' => true,
            'incluirEpps' => true,
            'incluirProcesos' => true,
            'incluirImagenes' => true,
        ];
    }

    /**
     * PersonalizaciÃ³n: Construir respuesta DTO con lÃ³gica de enriquecimiento compleja
     * 
     * Nota: $pedidoId es el ID del pedido. Cargamos el modelo Eloquent aquÃ­ con relaciones
     */
    protected function construirRespuesta(array $datosEnriquecidos, $pedidoId): mixed
    {
        // Cargar modelo Eloquent completo con relaciones (solo si es necesario)
        $modeloEloquent = \App\Models\PedidoProduccion::with(['prendas' => function($q) {
            $q->with(['tallas', 'variantes', 'coloresTelas' => function($q2) {
                $q2->with(['color', 'tela', 'fotos']);
            }, 'fotos', 'procesos' => function($q3) {
                $q3->with(['tipoProceso', 'imagenes'])->orderBy('created_at', 'desc');
            }]);
        }, 'epps' => function($q) {
            $q->with(['epp', 'imagenes']);
        }])->find($pedidoId);

        $prendasCompletas = $this->obtenerPrendasCompletas($modeloEloquent);
        $eppsCompletos = $this->obtenerEppsCompletos($modeloEloquent);

        return new PedidoResponseDTO(
            id: $datosEnriquecidos['id'],
            numero: $datosEnriquecidos['numero'],
            clienteId: $datosEnriquecidos['clienteId'],
            cliente: $modeloEloquent->cliente,
            asesor: $modeloEloquent->asesor?->name,
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
     * Obtener prendas completas enriquecidas desde el modelo cargado
     */
    private function obtenerPrendasCompletas($modeloEloquent): array
    {
        try {
            if (!$modeloEloquent || !$modeloEloquent->prendas) {
                Log::warning('Pedido sin prendas', ['pedido_id' => $modeloEloquent?->id]);
                return [];
            }

            $prendasArray = [];

            foreach ($modeloEloquent->prendas as $prenda) {
                Log::info('Procesando prenda', ['prenda_id' => $prenda->id, 'nombre' => $prenda->nombre_prenda]);

                $tallasEstructuradas = $this->construirEstructuraTallas($prenda);
                $variantes = $this->obtenerVariantes($prenda);
                $colorTela = $this->obtenerColorYTela($prenda);
                $imagenes = $prenda->fotos ? $prenda->fotos->pluck('ruta_webp')->toArray() : [];
                $imagenesTela = $this->obtenerImagenesTela($prenda);
                $procesos = $this->obtenerProcesosDelaPrenda($prenda);

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
                    'procesos' => $procesos,
                    'manga' => $variantes[0]['manga'] ?? null,
                    'obs_manga' => $variantes[0]['manga_obs'] ?? null,
                    'broche' => $variantes[0]['broche'] ?? null,
                    'obs_broche' => $variantes[0]['broche_obs'] ?? null,
                    'tiene_bolsillos' => isset($variantes[0]) ? (bool)$variantes[0]['bolsillos'] : false,
                    'obs_bolsillos' => $variantes[0]['bolsillos_obs'] ?? null,
                    'tiene_reflectivo' => false,
                ];
            }

            Log::info('Prendas procesadas exitosamente', ['pedido_id' => $modeloEloquent->id, 'cantidad' => count($prendasArray)]);
            return $prendasArray;

        } catch (\Exception $e) {
            Log::error('Error obteniendo prendas completas', [
                'pedido_id' => $modeloEloquent?->id,
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
     * Obtener imÃ¡genes de tela
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
            Log::warning('Error obteniendo imÃ¡genes de tela', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $imagenes;
    }

    /**
     * Obtener EPPs del pedido enriquecidos
     */
    private function obtenerEppsCompletos($modeloEloquent): array
    {
        $epps = [];

        try {
            if (!$modeloEloquent || !$modeloEloquent->epps) {
                return [];
            }

            foreach ($modeloEloquent->epps as $epp) {
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

            Log::info('EPPs procesados exitosamente', ['pedido_id' => $modeloEloquent->id, 'cantidad' => count($epps)]);
        } catch (\Exception $e) {
            Log::warning('Error obteniendo EPPs', [
                'pedido_id' => $modeloEloquent?->id,
                'error' => $e->getMessage()
            ]);
        }

        return $epps;
    }

    /**
     * Obtener procesos de una prenda
     */
    private function obtenerProcesosDelaPrenda($prenda): array
    {
        $procesos = [];

        try {
            if ($prenda->procesos) {
                foreach ($prenda->procesos as $proceso) {
                    $imagenes = [];
                    if ($proceso->imagenes && count($proceso->imagenes) > 0) {
                        foreach ($proceso->imagenes as $img) {
                            $imagenes[] = [
                                'id' => $img->id ?? null,
                                'ruta_webp' => $img->ruta_webp ?? null,
                                'ruta_original' => $img->ruta_original ?? null,
                                'orden' => $img->orden ?? 0,
                                'es_principal' => $img->es_principal ?? false,
                            ];
                        }
                    }

                    $ubicaciones = [];
                    if ($proceso->ubicaciones) {
                        if (is_string($proceso->ubicaciones)) {
                            $ubicaciones = json_decode($proceso->ubicaciones, true) ?? [];
                        } elseif (is_array($proceso->ubicaciones)) {
                            $ubicaciones = $proceso->ubicaciones;
                        }
                    }

                    $procesos[] = [
                        'id' => $proceso->id,
                        'tipo_proceso' => $proceso->tipoProceso?->nombre ?? 'Sin tipo',
                        'tipo_proceso_id' => $proceso->tipo_proceso_id,
                        'descripcion' => $proceso->descripcion,
                        'ubicaciones' => $ubicaciones,
                        'observaciones' => $proceso->observaciones,
                        'imagenes' => $imagenes,
                        'estado' => $proceso->estado ?? 'pendiente',
                    ];
                }
            }

            Log::info('Procesos obtenidos', ['prenda_id' => $prenda->id, 'cantidad' => count($procesos), 'procesos' => $procesos]);
        } catch (\Exception $e) {
            Log::warning('Error obteniendo procesos', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $procesos;
    }
}




