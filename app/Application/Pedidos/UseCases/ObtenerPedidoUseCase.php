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
 * MEJORADO: Carga completa de imágenes con ambas rutas (ruta_webp y ruta_original)
 * 
 * Responsabilidades:
 * - Obtener pedido por ID con todas sus relaciones
 * - Cargar fotos de prendas, telas y procesos
 * - Ordenar todas las fotos por campo "orden"
 * - Transformar datos a JSON para frontend
 * - Retornar arrays vacíos en lugar de null
 * - Evitar errores 500 con try-catch completos
 * 
 * Query Side - CQRS básico
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
            'incluirProcesos' => true,
            'incluirImagenes' => true,
        ];
    }

    /**
     * Personalización: Construir respuesta DTO con lógica de enriquecimiento compleja
     * 
     * Nota: $pedidoId es el ID del pedido. Cargamos el modelo Eloquent aquí con relaciones
     */
    protected function construirRespuesta(array $datosEnriquecidos, $pedidoId): mixed
    {
        try {
            // Cargar modelo Eloquent completo con relaciones (solo si es necesario)
            $modeloEloquent = \App\Models\PedidoProduccion::with([
                'prendas' => function($q) {
                    $q->withTrashed() // INCLUIR SOFT-DELETED
                      ->with([
                          'tallas',
                          'variantes.tipoManga',      // CARGAR TIPO MANGA
                          'variantes.tipoBroche',      // CARGAR TIPO BROCHE
                          'fotos' => function($q2) {
                              // FOTOS DE PRENDA - ORDENADAS POR ORDEN
                              $q2->orderBy('orden', 'asc');
                          },
                          'coloresTelas' => function($q2) {
                              $q2->with([
                                  'color', 
                                  'tela',
                                  'fotos' => function($q3) {
                                      // FOTOS DE TELA - ORDENADAS POR ORDEN
                                      $q3->orderBy('orden', 'asc');
                                  }
                              ]);
                          },
                          'procesos' => function($q3) {
                              $q3->withTrashed() // INCLUIR SOFT-DELETED
                                 ->with([
                                     'tipoProceso',
                                     'tallas',  // NUEVO: Cargar tallas del proceso
                                     'imagenes' => function($q4) {
                                         // FOTOS DE PROCESOS - ORDENADAS POR ORDEN
                                         $q4->orderBy('orden', 'asc');
                                     }
                                 ])
                                 ->orderBy('created_at', 'desc');
                          }
                      ]);
                },
                'epps' => function($q) {
                    $q->with([
                        'epp',
                        'imagenes' => function($q2) {
                            // FOTOS DE EPP - ORDENADAS POR ORDEN
                            $q2->orderBy('orden', 'asc');
                        }
                    ]);
                }
            ])->find($pedidoId);

            if (!$modeloEloquent) {
                Log::warning('Pedido no encontrado', ['pedido_id' => $pedidoId]);
                return new PedidoResponseDTO(
                    id: null,
                    numero: null,
                    clienteId: null,
                    cliente: null,
                    asesor: null,
                    estado: null,
                    descripcion: null,
                    totalPrendas: 0,
                    totalArticulos: 0,
                    prendas: [],
                    epps: [],
                    formaDePago: null,
                    mensaje: 'Pedido no encontrado'
                );
            }

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
                formaDePago: $datosEnriquecidos['forma_de_pago'] ?? null,
                mensaje: 'Pedido obtenido exitosamente'
            );

        } catch (\Exception $e) {
            Log::error('Error construyendo respuesta de pedido', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Obtener prendas completas enriquecidas desde el modelo cargado
     * 
     * Incluye:
     * - Fotos de prenda (ruta_webp, ruta_original, orden)
     * - Fotos de tela (ruta_webp, ruta_original, orden)
     * - Procesos con imágenes (ruta_webp, ruta_original, orden, es_principal)
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

                // Construir estructura de tallas
                $tallasEstructuradas = $this->construirEstructuraTallas($prenda);
                
                // Obtener variantes
                $variantes = $this->obtenerVariantes($prenda);
                
                // Obtener color y tela
                $colorTela = $this->obtenerColorYTela($prenda);
                
                // OBTENER FOTOS DE PRENDA (ambas rutas)
                $imagenes = $this->obtenerImagenesPrenda($prenda);
                
                // OBTENER FOTOS DE TELAS (ambas rutas, estructuradas por color-tela)
                $imagenesTela = $this->obtenerImagenesTela($prenda);
                
                // OBTENER PROCESOS CON IMÁGENES ORDENADAS
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
                    'imagenes' => $imagenes, // Array con estructura completa
                    'imagenes_tela' => $imagenesTela, // Array con estructura completa
                    'procesos' => $procesos, // Array con imágenes ordenadas
                    'manga' => $variantes[0]['manga'] ?? null,
                    'obs_manga' => $variantes[0]['manga_obs'] ?? null,
                    'broche' => $variantes[0]['broche'] ?? null,
                    'obs_broche' => $variantes[0]['broche_obs'] ?? null,
                    'tiene_bolsillos' => isset($variantes[0]) ? (bool)$variantes[0]['bolsillos'] : false,
                    'obs_bolsillos' => $variantes[0]['bolsillos_obs'] ?? null,
                    'tiene_reflectivo' => false,
                ];
            }

            Log::info('Prendas procesadas exitosamente', [
                'pedido_id' => $modeloEloquent->id,
                'cantidad' => count($prendasArray)
            ]);
            return $prendasArray;

        } catch (\Exception $e) {
            Log::error('Error obteniendo prendas completas', [
                'pedido_id' => $modeloEloquent?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return []; // Retornar array vacío en lugar de null
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
     * Obtener variantes (manga, broche, bolsillos) - Una por cada TALLA
     * 
     * CORRECCIÓN: Itera sobre TALLAS (tienen talla y cantidad)
     * Obtiene especificaciones de VARIANTES (manga, broche, bolsillos)
     */
    private function obtenerVariantes($prenda): array
    {
        $variantes = [];

        try {
            // Obtener especificaciones globales de la PRIMERA variante
            $especificaciones = [
                'manga' => null,
                'manga_obs' => '',
                'broche' => null,
                'broche_obs' => '',
                'bolsillos' => false,
                'bolsillos_obs' => '',
            ];
            
            Log::debug('[VARIANTES] Prenda inicio', [
                'prenda_id' => $prenda->id,
                'tiene_variantes' => $prenda->variantes ? $prenda->variantes->count() : 0,
            ]);
            
            if ($prenda->variantes && $prenda->variantes->count() > 0) {
                $primeraVariante = $prenda->variantes->first();
                
                Log::debug('[VARIANTES] Primera variante', [
                    'variante_id' => $primeraVariante->id,
                    'tipo_manga_id' => $primeraVariante->tipo_manga_id,
                    'tipoManga_exists' => $primeraVariante->tipoManga ? true : false,
                    'tipoManga_value' => $primeraVariante->tipoManga ? $primeraVariante->tipoManga->nombre : 'NULL',
                    'tipo_broche_id' => $primeraVariante->tipo_broche_boton_id,
                    'tipoBroche_exists' => $primeraVariante->tipoBroche ? true : false,
                    'tipoBroche_value' => $primeraVariante->tipoBroche ? $primeraVariante->tipoBroche->nombre : 'NULL',
                ]);
                
                if ($primeraVariante->tipo_manga_id && $primeraVariante->tipoManga) {
                    $especificaciones['manga'] = $primeraVariante->tipoManga->nombre;
                }

                if ($primeraVariante->tipo_broche_boton_id && $primeraVariante->tipoBroche) {
                    $especificaciones['broche'] = $primeraVariante->tipoBroche->nombre;
                }
                
                $especificaciones['manga_obs'] = $primeraVariante->manga_obs ?? '';
                $especificaciones['broche_obs'] = $primeraVariante->broche_boton_obs ?? '';
                $especificaciones['bolsillos'] = (bool)($primeraVariante->tiene_bolsillos ?? false);
                $especificaciones['bolsillos_obs'] = $primeraVariante->bolsillos_obs ?? '';
                
                Log::debug('[VARIANTES] Especificaciones finales', $especificaciones);
            }
            
            // ITERAR SOBRE TALLAS (tienen talla y cantidad)
            if ($prenda->tallas && $prenda->tallas->count() > 0) {
                foreach ($prenda->tallas as $talla) {
                    $variantes[] = [
                        'talla' => $talla->talla,
                        'cantidad' => (int)$talla->cantidad,
                        'manga' => $especificaciones['manga'],
                        'manga_obs' => $especificaciones['manga_obs'],
                        'broche' => $especificaciones['broche'],
                        'broche_obs' => $especificaciones['broche_obs'],
                        'bolsillos' => $especificaciones['bolsillos'],
                        'bolsillos_obs' => $especificaciones['bolsillos_obs'],
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error obteniendo variantes', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $variantes;
    }

    /**
     * Obtener color y tela de la prenda (primera combinación)
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
     * MEJORADO: Obtener imágenes de prenda con AMBAS rutas (ruta_webp y ruta_original)
     * 
     * Retorna array de objetos con estructura completa para el frontend
     * Ordenadas por campo "orden"
     * Incluye fallbacks si faltan campos
     * Retorna array vacío si no hay fotos (no null)
     */
    private function obtenerImagenesPrenda($prenda): array
    {
        $imagenes = [];

        try {
            if ($prenda->fotos && $prenda->fotos->count() > 0) {
                // Ya viene ordenada por la query, pero aseguramos en caso
                foreach ($prenda->fotos as $foto) {
                    $rutaWebp = $foto->ruta_webp ?? $foto->url ?? null;
                    $rutaOriginal = $foto->ruta_original ?? $rutaWebp ?? null;
                    
                    $imagenes[] = [
                        'id' => $foto->id ?? null,
                        'ruta_webp' => $this->normalizarRutaImagen($rutaWebp),
                        'ruta_original' => $this->normalizarRutaImagen($rutaOriginal),
                        'orden' => (int)($foto->orden ?? 0),
                    ];
                }
                
                // Ordenar por orden
                usort($imagenes, function($a, $b) {
                    return $a['orden'] <=> $b['orden'];
                });
            }

            Log::debug('[ObtenerPedidoUseCase] Imágenes de prenda obtenidas', [
                'prenda_id' => $prenda->id,
                'total_imagenes' => count($imagenes),
            ]);

        } catch (\Exception $e) {
            Log::warning('Error obteniendo imágenes de prenda', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return $imagenes; // Retorna [] si hay error, nunca null
    }

    /**
     * MEJORADO: Obtener imágenes de telas (color-tela)
     * 
     * Retorna array estructurado de imágenes por color-tela
     * Cada imagen incluye ruta_webp, ruta_original, orden
     * Ordenadas por campo "orden"
     * Retorna array vacío si no hay fotos (no null)
     */
    private function obtenerImagenesTela($prenda): array
    {
        $imagenes = [];

        try {
            if ($prenda->coloresTelas) {
                foreach ($prenda->coloresTelas as $ct) {
                    if ($ct->fotos && $ct->fotos->count() > 0) {
                        foreach ($ct->fotos as $foto) {
                            $rutaWebp = $foto->ruta_webp ?? $foto->url ?? null;
                            $rutaOriginal = $foto->ruta_original ?? $rutaWebp ?? null;
                            
                            $imagenes[] = [
                                'id' => $foto->id ?? null,
                                'color_tela_id' => $ct->id ?? null,
                                'color' => $ct->color?->nombre ?? null,
                                'tela' => $ct->tela?->nombre ?? null,
                                'ruta_webp' => $this->normalizarRutaImagen($rutaWebp),
                                'ruta_original' => $this->normalizarRutaImagen($rutaOriginal),
                                'orden' => (int)($foto->orden ?? 0),
                            ];
                        }
                    }
                }
                
                // Ordenar por orden
                usort($imagenes, function($a, $b) {
                    return $a['orden'] <=> $b['orden'];
                });
            }

            Log::debug('[ObtenerPedidoUseCase] Imágenes de tela obtenidas', [
                'prenda_id' => $prenda->id,
                'total_imagenes' => count($imagenes),
            ]);

        } catch (\Exception $e) {
            Log::warning('Error obteniendo imágenes de tela', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $imagenes; // Retorna [] si hay error, nunca null
    }

    /**
     * MEJORADO: Obtener procesos con imágenes
     * 
     * Incluye imágenes de cada proceso ordenadas por "orden"
     * Cada imagen tiene ruta_webp, ruta_original, orden, es_principal
     * Retorna array vacío si no hay procesos (no null)
     */
    private function obtenerProcesosDelaPrenda($prenda): array
    {
        $procesos = [];

        try {
            if ($prenda->procesos && $prenda->procesos->count() > 0) {
                foreach ($prenda->procesos as $proceso) {
                    $imagenes = [];
                    
                    // Obtener imágenes del proceso ordenadas
                    if ($proceso->imagenes && $proceso->imagenes->count() > 0) {
                        foreach ($proceso->imagenes as $imagen) {
                            $rutaWebp = $imagen->ruta_webp ?? $imagen->url ?? null;
                            $rutaOriginal = $imagen->ruta_original ?? $rutaWebp ?? null;
                            
                            $imagenes[] = [
                                'id' => $imagen->id ?? null,
                                'ruta_webp' => $this->normalizarRutaImagen($rutaWebp),
                                'ruta_original' => $this->normalizarRutaImagen($rutaOriginal),
                                'orden' => (int)($imagen->orden ?? 0),
                                'es_principal' => (bool)($imagen->es_principal ?? false),
                            ];
                        }
                        
                        // Ordenar por orden
                        usort($imagenes, function($a, $b) {
                            return $a['orden'] <=> $b['orden'];
                        });
                    }
                    
                    // Transformar tallas del proceso (desde la relación PedidosProcesosPrendaTalla)
                    $tallasTransformadas = [
                        'dama' => [],
                        'caballero' => [],
                        'unisex' => []
                    ];
                    
                    if ($proceso->tallas && $proceso->tallas->count() > 0) {
                        foreach ($proceso->tallas as $tallaProceso) {
                            $genero = strtolower($tallaProceso->genero ?? 'dama');
                            if (!isset($tallasTransformadas[$genero])) {
                                $tallasTransformadas[$genero] = [];
                            }
                            $tallasTransformadas[$genero][$tallaProceso->talla] = (int)$tallaProceso->cantidad;
                        }
                    }

                    $procesos[] = [
                        'id' => $proceso->id,
                        'tipo_proceso' => $proceso->tipoProceso?->nombre ?? null,
                        'tipo_proceso_id' => $proceso->tipo_proceso_id ?? null,
                        'descripcion' => $proceso->descripcion,
                        'ubicaciones' => $proceso->ubicaciones ? json_decode($proceso->ubicaciones, true) : [],
                        'observaciones' => $proceso->observaciones,
                        'tallas' => $tallasTransformadas,  // NUEVO: Agregar tallas transformadas
                        'imagenes' => $imagenes, // Array ordenado con estructura completa
                        'estado' => $proceso->estado ?? 'PENDIENTE',
                    ];
                }
            }

            Log::info('Procesos obtenidos', [
                'prenda_id' => $prenda->id,
                'cantidad' => count($procesos)
            ]);

        } catch (\Exception $e) {
            Log::warning('Error obteniendo procesos', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $procesos; // Retorna [] si hay error, nunca null
    }

    /**
     * Normalizar ruta de imagen para asegurar que siempre comience con /storage/
     */
    private function normalizarRutaImagen(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        // Si ya comienza con /storage/, retornar tal cual
        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }
        // Si comienza con storage/ (sin /), agregar / al inicio
        else if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }
        // Si no comienza con ninguno, agregar /storage/
        else {
            return '/storage/' . $ruta;
        }
    }

    /**
     * Obtener EPPs del pedido enriquecidos
     * 
     * Incluye imágenes de EPP ordenadas por "orden"
     * Retorna array vacío si no hay EPPs (no null)
     */
    private function obtenerEppsCompletos($modeloEloquent): array
    {
        $epps = [];

        try {
            if (!$modeloEloquent || !$modeloEloquent->epps) {
                return [];
            }

            foreach ($modeloEloquent->epps as $epp) {
                $imagenes = [];
                
                // Obtener imágenes del EPP ordenadas
                if ($epp->imagenes && $epp->imagenes->count() > 0) {
                    foreach ($epp->imagenes as $imagen) {
                        $rutaWebp = $imagen->ruta_webp ?? $imagen->ruta_web ?? $imagen->url ?? null;
                        $rutaOriginal = $imagen->ruta_original ?? $rutaWebp ?? null;
                        
                        $imagenes[] = [
                            'id' => $imagen->id ?? null,
                            'ruta_webp' => $this->normalizarRutaImagen($rutaWebp),
                            'ruta_original' => $this->normalizarRutaImagen($rutaOriginal),
                            'orden' => (int)($imagen->orden ?? 0),
                        ];
                    }
                    
                    // Ordenar por orden
                    usort($imagenes, function($a, $b) {
                        return $a['orden'] <=> $b['orden'];
                    });
                }

                $epps[] = [
                    'id' => $epp->id,
                    'pedido_epp_id' => $epp->id,
                    'epp_id' => $epp->epp_id,
                    'nombre' => $epp->epp?->nombre_completo ?? $epp->epp?->nombre ?? '',
                    'nombre_completo' => $epp->epp?->nombre_completo ?? $epp->epp?->nombre ?? '',
                    'epp_nombre' => $epp->epp?->nombre_completo ?? $epp->epp?->nombre ?? null,
                    'cantidad' => $epp->cantidad,
                    'observaciones' => $epp->observaciones,
                    'imagenes' => $imagenes, // Array ordenado con estructura completa
                ];
            }

            Log::info('EPPs procesados exitosamente', [
                'pedido_id' => $modeloEloquent->id,
                'cantidad' => count($epps)
            ]);

        } catch (\Exception $e) {
            Log::warning('Error obteniendo EPPs', [
                'pedido_id' => $modeloEloquent?->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $epps; // Retorna [] si hay error, nunca null
    }
}
