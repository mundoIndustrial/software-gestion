<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\UseCases\Base\AbstractObtenerUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Application\Pedidos\DTOs\PedidoResponseDTO;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
    /**
     * @param int $pedidoId ID del pedido
     * @param bool $filtrarProcesosPendientes Si true, oculta procesos con estado PENDIENTE (para vista /registros)
     */
    public function ejecutar(int $pedidoId, bool $filtrarProcesosPendientes = false): PedidoResponseDTO
    {
        $this->filtrarProcesosPendientes = $filtrarProcesosPendientes;
        return $this->obtenerYEnriquecer($pedidoId);
    }

    /**
     * Flag para controlar si debe filtrar procesos pendientes
     */
    private bool $filtrarProcesosPendientes = false;

    /**
     * Normalizar ruta de imagen para asegurar que comience con /storage/
     */
    private function normalizarRutaImagen(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        $ruta = str_replace('\\', '/', $ruta);

        if (str_starts_with($ruta, 'http')) {
            return $ruta;
        }

        if (str_starts_with($ruta, '/storage/')) {
            return $ruta;
        }

        if (str_starts_with($ruta, 'storage/')) {
            return '/' . $ruta;
        }

        return '/storage/' . ltrim($ruta, '/');
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
            // Obtener el usuario autenticado
            $usuario = Auth::user();
            $esCortador = $usuario && $usuario->hasRole('cortador');

            $esApiOperario = false;
            try {
                $esApiOperario = request()->is('api/operario/*');
            } catch (\Exception $e) {
                $esApiOperario = false;
            }

            // Cargar modelo Eloquent completo con relaciones (solo si es necesario)
            $modeloEloquent = \App\Models\PedidoProduccion::with([
                'prendas' => function($q) use ($esCortador, $usuario) {
                    // NO incluir prendas eliminadas (SoftDeletes aplica automáticamente)
                    
                    // FILTRO: Si el usuario es CORTADOR, excluir prendas de bodega (de_bodega = TRUE)
                    $esApiOperarioInner = false;
                    try {
                        $esApiOperarioInner = request()->is('api/operario/*');
                    } catch (\Exception $e) {
                        $esApiOperarioInner = false;
                    }

                    if ($esCortador && !$esApiOperarioInner) {
                        $q->where('de_bodega', false);
                        Log::info('[ObtenerPedidoUseCase] Filtrando prendas de bodega para CORTADOR', [
                            'usuario' => $usuario->name,
                        ]);
                    }
                    
                    $q
                      ->with([
                          'tallas',
                          'variantes.tipoManga',      // CARGAR TIPO MANGA
                          'variantes.tipoBroche',      // CARGAR TIPO BROCHE
                          'anchoMetraje',              // CARGAR ANCHO Y METRAJE
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
                              // NO incluir procesos eliminados (SoftDeletes aplica automáticamente)
                              $q3->with([
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
                    // Incluir EPPs DELETADOS para procesar historial de homologaciones
                    $q->withTrashed()
                      ->with([
                          'epp',
                          'imagenes' => function($q2) {
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
                    fechaCreacion: null,
                    area: null,
                    mensaje: 'Pedido no encontrado'
                );
            }

            $prendasCompletas = $this->obtenerPrendasCompletas($modeloEloquent, $modeloEloquent->estado);
            
            // Cargar EPPs incluyendo soft-deleted (para mostrar EPPs homologados)
            $eppsTrashedCollection = $modeloEloquent->eppsConTrashed()->get();
            // Reemplazar la colección de EPPs con la que incluye soft-deleted
            $modeloEloquent->setRelation('epps', $eppsTrashedCollection);
            
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
                fechaCreacion: $modeloEloquent->created_at 
                    ? $modeloEloquent->created_at->format('d/m/Y')
                    : ($modeloEloquent->created_at ? $modeloEloquent->created_at->format('d/m/Y') : null),
                area: $modeloEloquent->area ?? 'Sin especificar',
                mensaje: 'Pedido obtenido exitosamente'
            );

        } catch (\Error|\RuntimeException $e) {
            return new PedidoResponseDTO(
                id: $datosEnriquecidos['id'] ?? null,
                numero: $datosEnriquecidos['numero'] ?? null,
                clienteId: $datosEnriquecidos['clienteId'] ?? null,
                estado: $datosEnriquecidos['estado'] ?? null,
                descripcion: $datosEnriquecidos['descripcion'] ?? null,
                totalPrendas: $datosEnriquecidos['totalPrendas'] ?? 0,
                totalArticulos: $datosEnriquecidos['totalArticulos'] ?? 0,
                cliente: null,
                asesor: null,
                prendas: $datosEnriquecidos['prendas'] ?? [],
                epps: $datosEnriquecidos['epps'] ?? [],
                formaDePago: $datosEnriquecidos['forma_de_pago'] ?? null,
                fechaCreacion: null,
                area: null,
                mensaje: 'Pedido obtenido exitosamente'
            );
        } catch (\Throwable $e) {
            return new PedidoResponseDTO(
                id: $datosEnriquecidos['id'] ?? null,
                numero: $datosEnriquecidos['numero'] ?? null,
                clienteId: $datosEnriquecidos['clienteId'] ?? null,
                estado: $datosEnriquecidos['estado'] ?? null,
                descripcion: $datosEnriquecidos['descripcion'] ?? null,
                totalPrendas: $datosEnriquecidos['totalPrendas'] ?? 0,
                totalArticulos: $datosEnriquecidos['totalArticulos'] ?? 0,
                cliente: null,
                asesor: null,
                prendas: $datosEnriquecidos['prendas'] ?? [],
                epps: $datosEnriquecidos['epps'] ?? [],
                formaDePago: $datosEnriquecidos['forma_de_pago'] ?? null,
                fechaCreacion: null,
                area: null,
                mensaje: 'Pedido obtenido exitosamente'
            );
        }
    }

    /**
     * Obtener prendas completas enriquecidas desde el modelo cargado
     * 
     * Incluye:
     * - Fotos de prenda (ruta_webp, ruta_original, orden)
     * - Fotos de tela (ruta_webp, ruta_original, orden)
     * - Procesos con imágenes (ruta_webp, ruta_original, orden, es_principal)
     * 
     * @param $modeloEloquent El modelo del pedido
     * @param string|null $estadoPedido Estado del pedido para filtrar procesos
     */
    private function obtenerPrendasCompletas($modeloEloquent, ?string $estadoPedido = null): array
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
                
                // OBTENER COLORES Y TELAS COMPLETOS (con fotos incluidas)
                $coloresTelas = $this->obtenerColoresTelasCompletos($prenda);
                
                // OBTENER PROCESOS CON IMÁGENES ORDENADAS
                // Si el pedido está en estado PENDIENTE, NO mostrar procesos
                $procesos = $this->obtenerProcesosDelaPrenda($prenda, $estadoPedido);
                
                // OBTENER ANCHO Y METRAJE
                $anchoMetraje = [];
                if ($prenda->anchoMetraje) {
                    $anchoMetraje = [
                        'ancho' => $prenda->anchoMetraje->ancho,
                        'metraje' => $prenda->anchoMetraje->metraje,
                        'metrajes_por_color' => [], // TODO: Cargar desde tabla si existe
                        'tipo_modo' => $prenda->anchoMetraje->tipo_modo,
                        'contenido_mano' => $prenda->anchoMetraje->contenido_mano,
                        'observaciones' => $prenda->anchoMetraje->observaciones ?? null,
                    ];
                } else {
                    $anchoMetraje = [
                        'ancho' => null,
                        'metraje' => null,
                        'metrajes_por_color' => [],
                        'tipo_modo' => null,
                        'contenido_mano' => null,
                        'observaciones' => null,
                    ];
                }

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
                    'talla_colores' => $this->obtenerTallaColoresDelaPrenda($prenda), // 🎨 NUEVO: Colores por talla
                    'variantes' => $variantes,
                    'imagenes' => $imagenes, // Array con estructura completa
                    'imagenes_tela' => $imagenesTela, // Array con estructura completa
                    'colores_telas' => $coloresTelas, // Estructura completa de coloresTelas con fotos
                    'telas_array' => $coloresTelas, //  ALIAS: telas_array para compatibilidad con factura
                    'procesos' => $procesos, // Array con imágenes ordenadas
                    'manga' => $variantes[0]['manga'] ?? null,
                    'obs_manga' => $variantes[0]['manga_obs'] ?? null,
                    'broche' => $variantes[0]['broche'] ?? null,
                    'obs_broche' => $variantes[0]['broche_obs'] ?? null,
                    'tiene_bolsillos' => isset($variantes[0]) ? (bool)$variantes[0]['bolsillos'] : false,
                    'obs_bolsillos' => $variantes[0]['bolsillos_obs'] ?? null,
                    'tiene_reflectivo' => false,
                    'ancho_metraje' => $anchoMetraje,
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
     * Construir estructura de tallas: { GENERO: { TALLA: cantidad } }
     * Simplificado para que sea compatible con el frontend
     */
    private function construirEstructuraTallas($prenda): array
    {
        $tallasPorGenero = [
            'DAMA' => [],
            'CABALLERO' => [],
            'UNISEX' => []
        ];
        
        // Obtener tallas desde prenda_pedido_talla_colores (flujo 2)
        $tallasColores = \DB::table('prenda_pedido_talla_colores as pptc')
            ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->where('ppt.prenda_pedido_id', $prenda->id)
            ->select(
                'ppt.genero',
                'ppt.talla',
                'pptc.color_nombre',
                'pptc.cantidad'
            )
            ->get();
            
        if ($tallasColores->isNotEmpty()) {
            // Agrupar por cantidad total por talla (sin desglose de colores dentro de la estructura)
            $tallasAgrupadas = [];
            foreach ($tallasColores as $tallaColor) {
                $genero = strtoupper($tallaColor->genero);
                $talla = $tallaColor->talla;
                $cantidad = (int)$tallaColor->cantidad;
                
                $key = $genero . '|' . $talla;
                if (!isset($tallasAgrupadas[$key])) {
                    $tallasAgrupadas[$key] = [
                        'genero' => $genero,
                        'talla' => $talla,
                        'cantidad' => 0
                    ];
                }
                $tallasAgrupadas[$key]['cantidad'] += $cantidad;
            }
            
            // Construir estructura devolviendo cantidades simples
            foreach ($tallasAgrupadas as $item) {
                $genero = $item['genero'];
                $talla = $item['talla'];
                $cantidad = $item['cantidad'];
                $tallasPorGenero[$genero][$talla] = $cantidad;
            }
        } else {
            // Si no hay datos en flujo 2, intentar desde prenda_pedido_tallas (flujo 1)
            try {
                if ($prenda->tallas) {
                    foreach ($prenda->tallas as $talla) {
                        $genero = strtoupper($talla->genero ?? 'GENERAL');
                        $tallaPorGenero = $talla->talla;
                        $cantidad = (int)$talla->cantidad;
                        
                        $tallasPorGenero[$genero][$tallaPorGenero] = $cantidad;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error construyendo estructura de tallas (flujo 1)', [
                    'prenda_id' => $prenda->id ?? null,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $tallasPorGenero;
    }

    /**
     * Obtener variantes (manga, broche, bolsillos) - Una por cada TALLA
     * 
     * CORRECCIÓN: Itera sobre TALLAS (tienen talla y cantidad)
     * Obtiene especificaciones de VARIANTES (manga, broche, bolsillos)
     * INCLUYE: Información de color por talla desde prenda_pedido_talla_colores
     */
    private function obtenerVariantes($prenda): array
    {
        $variantes = [];

        try {
            // Obtener especificaciones globales de la PRIMERA variante
            $especificaciones = [
                'tipo_manga_id' => null,  // ID que siempre se devuelve
                'manga' => null,
                'manga_obs' => '',
                'tipo_broche_boton_id' => null,  // ID que siempre se devuelve
                'broche' => null,
                'broche_obs' => '',
                'tiene_bolsillos' => false,  // Booleano que siempre se devuelve
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
                
                // MANGA - Siempre incluir el ID si existe
                if ($primeraVariante->tipo_manga_id) {
                    $especificaciones['tipo_manga_id'] = $primeraVariante->tipo_manga_id;
                    if ($primeraVariante->tipoManga) {
                        $especificaciones['manga'] = $primeraVariante->tipoManga->nombre;
                    }
                }

                // BROCHE - Siempre incluir el ID si existe
                if ($primeraVariante->tipo_broche_boton_id) {
                    $especificaciones['tipo_broche_boton_id'] = $primeraVariante->tipo_broche_boton_id;
                    if ($primeraVariante->tipoBroche) {
                        $especificaciones['broche'] = $primeraVariante->tipoBroche->nombre;
                    }
                }
                
                $especificaciones['manga_obs'] = $primeraVariante->manga_obs ?? '';
                $especificaciones['broche_obs'] = $primeraVariante->broche_boton_obs ?? '';
                $especificaciones['tiene_bolsillos'] = (bool)($primeraVariante->tiene_bolsillos ?? false);
                $especificaciones['bolsillos'] = (bool)($primeraVariante->tiene_bolsillos ?? false);
                $especificaciones['bolsillos_obs'] = $primeraVariante->bolsillos_obs ?? '';
                
                Log::debug('[VARIANTES] Especificaciones finales', $especificaciones);
            }
            
            // OBTENER COLORES POR TALLA desde prenda_pedido_talla_colores
            $coloresPorTalla = [];
            try {
                $coloresTalla = \DB::table('prenda_pedido_talla_colores as pptc')
                    ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                    ->where('ppt.prenda_pedido_id', $prenda->id)
                    ->select(
                        'pptc.id as talla_color_id',
                        'ppt.id as talla_id',
                        'ppt.talla',
                        'ppt.genero',
                        'pptc.color_nombre',
                        'pptc.cantidad'
                    )
                    ->get();
                    
                foreach ($coloresTalla as $colorTalla) {
                    $clave = $colorTalla->talla_id;
                    if (!isset($coloresPorTalla[$clave])) {
                        $coloresPorTalla[$clave] = [];
                    }
                    $coloresPorTalla[$clave][] = [
                        'talla_color_id' => $colorTalla->talla_color_id,
                        'color' => $colorTalla->color_nombre,
                        'cantidad' => $colorTalla->cantidad
                    ];
                }
                
                Log::debug('[VARIANTES] Colores por talla obtenidos', [
                    'prenda_id' => $prenda->id,
                    'colores_por_talla' => $coloresPorTalla
                ]);
            } catch (\Exception $e) {
                Log::warning('Error obteniendo colores por talla', [
                    'prenda_id' => $prenda->id,
                    'error' => $e->getMessage()
                ]);
            }
            
            // ITERAR SOBRE TALLAS (tienen talla y cantidad)
            if ($prenda->tallas && $prenda->tallas->count() > 0) {
                foreach ($prenda->tallas as $talla) {
                    $tallaId = $talla->id;
                    $coloresEspecificos = $coloresPorTalla[$tallaId] ?? [];
                    
                    // Construir string de colores y cantidades
                    $colorInfo = '';
                    if (!empty($coloresEspecificos)) {
                        $partesColor = [];
                        foreach ($coloresEspecificos as $color) {
                            $partesColor[] = "{$color['cantidad']}-{$color['color']}";
                        }
                        $colorInfo = implode(', ', $partesColor);
                    }
                    
                    $variantes[] = [
                        'talla_id' => $tallaId,  //  Agregar ID de la talla
                        'talla' => $talla->talla,
                        'genero' => $talla->genero,  //  Agregar género
                        'cantidad' => (int)$talla->cantidad,
                        'es_sobremedida' => (bool) ($talla->es_sobremedida ?? false),
                        'color_info' => $colorInfo,  // NUEVO: información de colores por talla
                        'colores_detalle' => $coloresEspecificos,  // NUEVO: array detallado de colores
                        'tipo_manga_id' => $especificaciones['tipo_manga_id'] ?? null,  // ID de manga SIEMPRE
                        'manga' => $especificaciones['manga'],
                        'manga_obs' => $especificaciones['manga_obs'],
                        'tipo_broche_boton_id' => $especificaciones['tipo_broche_boton_id'] ?? null,  // ID de broche SIEMPRE
                        'broche' => $especificaciones['broche'],
                        'broche_obs' => $especificaciones['broche_obs'],
                        'tiene_bolsillos' => $especificaciones['tiene_bolsillos'] ?? false,  // Booleano SIEMPRE
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
     * Obtener imágenes de telas con estructura completa (ruta_webp + ruta_original + url + orden).
     *
     * FUENTES:
     * - Flujo normal/piezas: prenda->coloresTelas->fotos
     * - Flujo talla-color: prenda_pedido_talla_colores.imagen_ruta
     */
    private function obtenerImagenesTela($prenda): array
    {
        $imagenes = [];

        try {
            // 1) Flujo normal/piezas: fotos asociadas a colores-telas
            if ($prenda->coloresTelas && $prenda->coloresTelas->count() > 0) {
                foreach ($prenda->coloresTelas as $ct) {
                    if (!$ct) continue;
                    if (!isset($ct->fotos) || !$ct->fotos) {
                        try {
                            $ct->load('fotos');
                        } catch (\Exception $e) {
                            // silencioso
                        }
                    }

                    if ($ct->fotos && $ct->fotos->count() > 0) {
                        foreach ($ct->fotos as $foto) {
                            $rutaWebp = $foto->ruta_webp ?? $foto->url ?? null;
                            $rutaOriginal = $foto->ruta_original ?? $rutaWebp ?? null;
                            $url = $this->normalizarRutaImagen($rutaWebp ?: $rutaOriginal);
                            if (!$url) continue;

                            $imagenes[] = [
                                'id' => $foto->id ?? null,
                                'ruta_webp' => $this->normalizarRutaImagen($rutaWebp),
                                'ruta_original' => $this->normalizarRutaImagen($rutaOriginal),
                                'url' => $url,
                                'orden' => (int)($foto->orden ?? 0),
                                'es_principal' => (bool)($foto->es_principal ?? false),
                            ];
                        }
                    }
                }
            }

            // 2) Flujo talla-color: imágenes guardadas por talla/color
            try {
                $raw = \DB::table('prenda_pedido_talla_colores as ptc')
                    ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
                    ->where('pt.prenda_pedido_id', $prenda->id)
                    ->whereNotNull('ptc.imagen_ruta')
                    ->pluck('ptc.imagen_ruta')
                    ->toArray();

                foreach ($raw as $item) {
                    if (!$item) continue;

                    // Puede venir como JSON array en string
                    if (is_string($item)) {
                        $trim = trim($item);
                        if ($trim !== '' && ($trim[0] === '[' || $trim[0] === '{')) {
                            $decoded = json_decode($trim, true);
                            if (is_array($decoded)) {
                                foreach ($decoded as $v) {
                                    if (!is_string($v)) continue;
                                    $u = $this->normalizarRutaImagen($v);
                                    if ($u) {
                                        $imagenes[] = [
                                            'id' => null,
                                            'ruta_webp' => $u,
                                            'ruta_original' => $u,
                                            'url' => $u,
                                            'orden' => 0,
                                            'es_principal' => false,
                                        ];
                                    }
                                }
                                continue;
                            }
                        }

                        $u = $this->normalizarRutaImagen($trim);
                        if ($u) {
                            $imagenes[] = [
                                'id' => null,
                                'ruta_webp' => $u,
                                'ruta_original' => $u,
                                'url' => $u,
                                'orden' => 0,
                                'es_principal' => false,
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                // silencioso
            }

            // Deduplicar por URL (mantener primer match)
            $seen = [];
            $dedup = [];
            foreach ($imagenes as $img) {
                $key = $img['url'] ?? null;
                if (!$key) continue;
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $dedup[] = $img;
            }
            $imagenes = $dedup;

            // Ordenar por orden
            usort($imagenes, function($a, $b) {
                return ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0);
            });
        } catch (\Exception $e) {
            return [];
        }

        return $imagenes;
    }

    /**
     * Obtener procesos de la prenda con imágenes normalizadas.
     *
     * Reglas:
     * - Si $this->filtrarProcesosPendientes = true, ocultar procesos "pendiente" (para /registros)
     * - Si el pedido está en estado PENDIENTE, retornar []
     */
    private function obtenerProcesosDelaPrenda($prenda, ?string $estadoPedido = null): array
    {
        try {
            $estadoPedidoNorm = strtolower(trim((string)($estadoPedido ?? '')));
            if ($estadoPedidoNorm === 'pendiente') {
                return [];
            }

            if (!isset($prenda->procesos) || !$prenda->procesos) {
                return [];
            }

            $procesosOut = [];

            foreach ($prenda->procesos as $proceso) {
                if (!$proceso) continue;

                $estadoProc = strtolower(trim((string)($proceso->estado ?? $proceso['estado'] ?? '')));
                if ($this->filtrarProcesosPendientes && $estadoProc === 'pendiente') {
                    continue;
                }

                $tipo = null;
                if (isset($proceso->tipoProceso) && $proceso->tipoProceso) {
                    $tipo = $proceso->tipoProceso->nombre ?? null;
                }
                if (!$tipo) {
                    $tipo = $proceso->tipo_proceso ?? $proceso->nombre_proceso ?? $proceso->nombre ?? null;
                }

                // Imágenes del proceso
                $imagenes = [];
                try {
                    if (isset($proceso->imagenes) && $proceso->imagenes) {
                        foreach ($proceso->imagenes as $img) {
                            $rutaWebp = $img->ruta_webp ?? $img->ruta_web ?? $img->url ?? null;
                            $rutaOriginal = $img->ruta_original ?? $rutaWebp ?? null;
                            $url = $this->normalizarRutaImagen($rutaWebp ?: $rutaOriginal);
                            if (!$url) continue;

                            $imagenes[] = [
                                'id' => $img->id ?? null,
                                'ruta_webp' => $this->normalizarRutaImagen($rutaWebp),
                                'ruta_original' => $this->normalizarRutaImagen($rutaOriginal),
                                'url' => $url,
                                'orden' => (int)($img->orden ?? 0),
                                'es_principal' => (bool)($img->es_principal ?? false),
                            ];
                        }
                    }

                    usort($imagenes, function($a, $b) {
                        return ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0);
                    });
                } catch (\Exception $e) {
                    $imagenes = [];
                }

                // Tallas del proceso (si vienen cargadas)
                $tallas = [];
                if (isset($proceso->tallas) && $proceso->tallas) {
                    try {
                        // Transformar colección de tallas a estructura procesada
                        $tallas = $this->construirEstructuraTallasDelProceso($proceso->tallas);
                    } catch (\Exception $e) {
                        Log::warning('Error construyendo tallas del proceso', [
                            'proceso_id' => $proceso->id ?? null,
                            'error' => $e->getMessage()
                        ]);
                        $tallas = [];
                    }
                }

                $procesosOut[] = [
                    'id' => $proceso->id ?? null,
                    'tipo_proceso_id' => $proceso->tipo_proceso_id ?? ($proceso->tipoProceso->id ?? null),
                    'tipo_proceso' => $tipo,
                    'nombre_proceso' => $tipo,
                    'estado' => $proceso->estado ?? null,
                    'observaciones' => $proceso->observaciones ?? '',
                    'ubicaciones' => $proceso->ubicaciones ?? [],
                    'modo_tallas' => $proceso->modo_tallas ?? 'general',
                    'tallas' => $tallas,
                    'imagenes' => $imagenes,
                ];
            }

            return $procesosOut;
        } catch (\Exception $e) {
            return [];
        }
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
                        'url' => $this->normalizarRutaImagen($rutaWebp ?: $rutaOriginal),
                        'orden' => (int)($foto->orden ?? 0),
                        'es_principal' => (bool)($foto->es_principal ?? false),
                    ];
                }
            }

            // Ordenar por orden
            usort($imagenes, function($a, $b) {
                return ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0);
            });
        } catch (\Exception $e) {
            return [];
        }

        return $imagenes; // Retorna [] si hay error, nunca null
    }

    /**
     *  NUEVO: Obtener estructura completa de coloresTelas con fotos
     * 
     * Retorna array de coloresTelas con toda la información:
     * - id, color_id, tela_id, referencia
     * - color_nombre, color_codigo
     * - tela_nombre, tela_referencia
     * - fotos (array de fotos con ruta_webp, ruta_original, etc)
     */
    private function obtenerColoresTelasCompletos($prenda): array
    {
        $coloresTelas = [];

        try {
            Log::info('[obtenerColoresTelasCompletos] Iniciando obtención de colores y telas', [
                'prenda_id' => $prenda->id,
                'prenda_nombre' => $prenda->nombre_prenda,
                'colorTelas_count' => $prenda->coloresTelas ? $prenda->coloresTelas->count() : 0,
            ]);
            
            if ($prenda->coloresTelas && $prenda->coloresTelas->count() > 0) {
                foreach ($prenda->coloresTelas as $idx => $ct) {
                    // CARGAR RELACIONES EXPLÍCITAMENTE si no están disponibles
                    if (!isset($ct->color) || !$ct->color) {
                        $ct->load('color');
                    }
                    if (!isset($ct->tela) || !$ct->tela) {
                        $ct->load('tela');
                    }
                    if (!isset($ct->fotos) || !$ct->fotos) {
                        $ct->load('fotos');
                    }
                    $fotos = [];
                    if ($ct->fotos && $ct->fotos->count() > 0) {
                        foreach ($ct->fotos as $foto) {
                            $rutaWebp = $foto->ruta_webp ?? $foto->url ?? null;
                            $rutaOriginal = $foto->ruta_original ?? $rutaWebp ?? null;
                            $fotos[] = [
                                'id' => $foto->id ?? null,
                                'ruta_webp' => $this->normalizarRutaImagen($rutaWebp),
                                'ruta_original' => $this->normalizarRutaImagen($rutaOriginal),
                                'url' => $this->normalizarRutaImagen($rutaWebp ?: $rutaOriginal),
                                'orden' => (int)($foto->orden ?? 0),
                                'es_principal' => (bool)($foto->es_principal ?? false),
                            ];
                        }

                        usort($fotos, function($a, $b) {
                            return ($a['orden'] ?? 0) <=> ($b['orden'] ?? 0);
                        });
                    }

                    $coloresTelas[] = [
                        'id' => $ct->id,
                        'prenda_pedido_id' => $ct->prenda_pedido_id ?? ($prenda->id ?? null),
                        'color_id' => $ct->color_id,
                        'color_nombre' => $ct->color?->nombre ?? null,
                        'color_codigo' => $ct->color?->codigo ?? null,
                        'tela_id' => $ct->tela_id,
                        'tela_nombre' => $ct->tela?->nombre ?? null,
                        'tela_referencia' => $ct->tela?->referencia ?? ($ct->referencia ?? null),
                        'referencia' => $ct->referencia ?? null,
                        'fotos' => $fotos,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('[obtenerColoresTelasCompletos] Error', [
                'prenda_id' => $prenda->id ?? null,
                'error' => $e->getMessage()
            ]);
        }

        return $coloresTelas;
    }

    /**
     * Retorna array vacío si no hay EPPs (no null)
     */
    private function obtenerEppsCompletos($modeloEloquent): array
    {
        $epps = [];

        try {
            if (!$modeloEloquent || !$modeloEloquent->epps) {
                \Log::debug('[obtenerEppsCompletos] Sin EPPs en modelo', [
                    'modelo_existe' => $modeloEloquent ? true : false,
                    'epps_existe' => $modeloEloquent && $modeloEloquent->epps ? true : false
                ]);
                return [];
            }

            \Log::debug('[obtenerEppsCompletos] EPPs a procesar', [
                'cantidad' => count($modeloEloquent->epps),
                'ids' => $modeloEloquent->epps->pluck('id')->toArray(),
                'nombres' => $modeloEloquent->epps->map(fn($e) => $e->epp?->nombre ?? 'sin nombre')->toArray(),
                'deleted_at' => $modeloEloquent->epps->pluck('deleted_at')->toArray()
            ]);

            foreach ($modeloEloquent->epps as $epp) {
                \Log::debug('[obtenerEppsCompletos] Procesando EPP', [
                    'id' => $epp->id,
                    'nombre' => $epp->epp?->nombre ?? 'sin nombre',
                    'deleted_at' => $epp->deleted_at
                ]);
                
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

    /**
     * 🎨 NUEVO: Obtener tallas con colores desde prenda_pedido_talla_colores
     * 
     * Estructura: [{genero, talla, color_nombre, tela_nombre, cantidad}, ...]
     * Para ser usada en Formatters.transformarTallaColoresAEstructura()
     */
    private function obtenerTallaColoresDelaPrenda($prenda): array
    {
        try {
            $tallasColores = \DB::table('prenda_pedido_talla_colores as pptc')
                ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                ->where('ppt.prenda_pedido_id', $prenda->id)
                ->select([
                    'ppt.genero',
                    'ppt.talla',
                    'pptc.tela_nombre',
                    'pptc.color_nombre',
                    'pptc.cantidad',
                    'pptc.referencia',
                    'pptc.observaciones',
                    'pptc.imagen_ruta'
                ])
                ->get()
                ->toArray();
                
            Log::debug('[obtenerTallaColoresDelaPrenda] Tallas con colores obtenidas', [
                'prenda_id' => $prenda->id,
                'cantidad_registros' => count($tallasColores),
                'datos' => $tallasColores
            ]);
            
            return $tallasColores;
            
        } catch (\Exception $e) {
            Log::warning('[obtenerTallaColoresDelaPrenda] Error obteniendo colores por talla', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Transformar colección de tallas del proceso a estructura { GENERO: { TALLA: cantidad } }
     * Input: Colección de PedidosProcesosPrendaTalla (id, proceso_prenda_detalle_id, genero, talla, cantidad, ...)
     * Output: { DAMA: {L: 3, M: 5}, CABALLERO: {XL: 2}, UNISEX: {} }
     */
    private function construirEstructuraTallasDelProceso($tallasColeccion): array
    {
        $tallasPorGenero = [
            'DAMA' => [],
            'CABALLERO' => [],
            'UNISEX' => []
        ];
        
        if (!$tallasColeccion) {
            return $tallasPorGenero;
        }

        try {
            $tallasArray = $tallasColeccion instanceof \Illuminate\Support\Collection
                ? $tallasColeccion->toArray()
                : (is_array($tallasColeccion) ? $tallasColeccion : []);

            foreach ($tallasArray as $talla) {
                // Normalizar datos
                if (is_array($talla)) {
                    $genero = strtoupper($talla['genero'] ?? '');
                    $tallaNombre = $talla['talla'] ?? '';
                    $cantidad = (int)($talla['cantidad'] ?? 0);
                } else {
                    $genero = strtoupper($talla->genero ?? '');
                    $tallaNombre = $talla->talla ?? '';
                    $cantidad = (int)($talla->cantidad ?? 0);
                }

                // Validar género
                if (!in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'])) {
                    continue;
                }

                // Agregar a estructura
                if ($cantidad > 0) {
                    $tallasPorGenero[$genero][$tallaNombre] = $cantidad;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error transformando tallas del proceso', [
                'error' => $e->getMessage()
            ]);
        }

        return $tallasPorGenero;
    }
}





