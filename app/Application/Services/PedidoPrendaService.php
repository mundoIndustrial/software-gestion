<?php

namespace App\Application\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Application\Services\ColorGeneroMangaBrocheService;
use App\Domain\Pedidos\Services\ColorTelaService;
use App\Domain\Pedidos\Services\CaracteristicasPrendaService;
use App\Domain\Pedidos\Services\PrendaImagenService;
use App\Domain\Pedidos\Services\TelaImagenService;
use App\Domain\Pedidos\Services\PrendaTallaService;
use App\Domain\Pedidos\Services\PrendaVarianteService;
use App\Domain\Pedidos\Services\PrendaProcesoService;
use App\Domain\Pedidos\Services\PrendaDataNormalizerService;
use App\Domain\Pedidos\Services\VariacionesPrendaProcessorService;
use App\Domain\Pedidos\Services\PrendaBaseCreatorService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PedidoPrendaService
 * 
 * Responsabilidad: Guardar prendas de pedidos en tablas normalizadas
 * Equivalente a CotizacionPrendaService pero para pedidos
 * 
 * Cumple:
 * - SRP: Solo guarda prendas
 * - DIP: Inyecta dependencias
 * - OCP: FÃ¡cil de extender
 */
class PedidoPrendaService
{
    private ColorGeneroMangaBrocheService $colorGeneroService;
    private ColorTelaService $colorTelaService;
    private CaracteristicasPrendaService $caracteristicasService;
    private PrendaImagenService $prendaImagenService;
    private TelaImagenService $telaImagenService;
    private PrendaTallaService $prendaTallaService;
    private PrendaVarianteService $prendaVarianteService;
    private PrendaProcesoService $prendaProcesoService;
    private PrendaDataNormalizerService $dataNormalizer;
    private VariacionesPrendaProcessorService $variacionesProcessor;
    private PrendaBaseCreatorService $prendaBaseCreator;

    public function __construct(
        ColorGeneroMangaBrocheService $colorGeneroService,
        ColorTelaService $colorTelaService = null,
        CaracteristicasPrendaService $caracteristicasService = null,
        PrendaImagenService $prendaImagenService = null,
        TelaImagenService $telaImagenService = null,
        PrendaTallaService $prendaTallaService = null,
        PrendaVarianteService $prendaVarianteService = null,
        PrendaProcesoService $prendaProcesoService = null,
        PrendaDataNormalizerService $dataNormalizer = null,
        VariacionesPrendaProcessorService $variacionesProcessor = null,
        PrendaBaseCreatorService $prendaBaseCreator = null
    )
    {
        $this->colorGeneroService = $colorGeneroService;
        $this->colorTelaService = $colorTelaService ?? app(ColorTelaService::class);
        $this->caracteristicasService = $caracteristicasService ?? app(CaracteristicasPrendaService::class);
        $this->prendaImagenService = $prendaImagenService ?? app(PrendaImagenService::class);
        $this->telaImagenService = $telaImagenService ?? app(TelaImagenService::class);
        $this->prendaTallaService = $prendaTallaService ?? app(PrendaTallaService::class);
        $this->prendaVarianteService = $prendaVarianteService ?? app(PrendaVarianteService::class);
        $this->prendaProcesoService = $prendaProcesoService ?? app(PrendaProcesoService::class);
        $this->dataNormalizer = $dataNormalizer ?? app(PrendaDataNormalizerService::class);
        $this->variacionesProcessor = $variacionesProcessor ?? app(VariacionesPrendaProcessorService::class);
        $this->prendaBaseCreator = $prendaBaseCreator ?? app(PrendaBaseCreatorService::class);
    }

    /**
     * Guardar UNA prenda en pedido (usado por CommandHandlers)
     * 
     * @param PedidoProduccion $pedido
     * @param array $prendaData
     * @return PrendaPedido
     */
    public function guardarUnaPrendaEnPedido(PedidoProduccion $pedido, array $prendaData): PrendaPedido
    {
        Log::info(' [PedidoPrendaService::guardarUnaPrendaEnPedido] Guardando prenda individual', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'nombre_prenda' => $prendaData['nombre_producto'] ?? $prendaData['nombre_prenda'] ?? 'Sin nombre',
        ]);

        // Usar método privado que retorna la prenda (SIN transacción aquí, el handler la maneja)
        return $this->guardarPrenda($pedido, $prendaData, 1);
    }

    /**
     * Guardar prendas en pedido
     */
    public function guardarPrendasEnPedido(PedidoProduccion $pedido, array $prendas): void
    {
        Log::info(' [PedidoPrendaService::guardarPrendasEnPedido] INICIO - AnÃ¡lisis completo', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'cantidad_prendas' => count($prendas),
            'prendas_completas' => $prendas,
        ]);
        
        if (empty($prendas)) {
            Log::warning(' [PedidoPrendaService] No hay prendas para guardar', [
                'pedido_id' => $pedido->id,
            ]);
            return;
        }

        DB::beginTransaction();
        try {
            $index = 1;
            $prendasCreadas = [];
            
            \Log::info(' [INICIANDO LOOP] Guardando ' . count($prendas) . ' prendas del pedido ' . $pedido->id);
            
            foreach ($prendas as $prendaIndex => $prendaData) {
                \Log::info(" [PRENDA #{$index}] ANTES - Guardando prenda #{$index} de " . count($prendas), [
                    'prendaIndex' => $prendaIndex,
                    'nombre' => $prendaData['nombre_producto'] ?? 'SIN NOMBRE',
                ]);
                
                // CRÃTICO: Convertir DTO a array si es necesario
                if (is_object($prendaData) && method_exists($prendaData, 'toArray')) {
                    $prendaData = $prendaData->toArray();
                }
                
                $this->guardarPrenda($pedido, $prendaData, $index);
                
                //  VERIFICAR QUE LA PRENDA SE CREÃ“ CON UN ID ÃšNICO
                $ultimaPrenda = PrendaPedido::where('pedido_produccion_id', $pedido->id)
                    ->orderBy('id', 'desc')
                    ->first();
                
                \Log::info(" [PRENDA #{$index}] DESPUÃ‰S - Prenda creada con ID", [
                    'prenda_id_nueva' => $ultimaPrenda->id ?? 'NO ENCONTRADA',
                    'nombre_prenda' => $ultimaPrenda->nombre_prenda ?? 'NO ENCONTRADA',
                    'prendas_en_pedido' => PrendaPedido::where('pedido_produccion_id', $pedido->id)->count(),
                ]);
                
                $prendasCreadas[$index] = $ultimaPrenda->id;
                $index++;
            }
            
            \Log::info(" [RESUMEN] Prendas creadas en este ciclo", [
                'prendas_totales' => count($prendasCreadas),
                'ids_creadas' => $prendasCreadas,
                'ids_unicos' => count(array_unique($prendasCreadas)),
            ]);
            
            DB::commit();
            Log::info(' [PedidoPrendaService] Prendas guardadas correctamente', [
                'pedido_id' => $pedido->id,
                'cantidad' => count($prendas),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(' [PedidoPrendaService] Error guardando prendas', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Guardar una prenda con sus relaciones
     * Genera descripción formateada usando DescripcionPrendaLegacyFormatter
     * (Formato compatible con pedidos legacy como 45452)
     */
    private function guardarPrenda(PedidoProduccion $pedido, mixed $prendaData, int $index = 1): PrendaPedido
    {
        // Normalizar datos de prenda (DTO â†’ array)
        $prendaData = $this->dataNormalizer->normalizarPrendaData($prendaData);

        //  LOG: Ver quÃ© datos llegan
        \Log::info(' [PedidoPrendaService] Datos recibidos para guardar prenda', [
            'nombre_producto' => $prendaData['nombre_producto'] ?? null,
            'tela_id' => $prendaData['tela_id'] ?? null,
            'color_id' => $prendaData['color_id'] ?? null,
            'tipo_manga_id' => $prendaData['tipo_manga_id'] ?? null,
            'tipo_broche_boton_id' => $prendaData['tipo_broche_boton_id'] ?? null,
            'manga' => $prendaData['manga'] ?? null,
            'broche' => $prendaData['broche'] ?? null,
        ]);

        //  PROCESAR VARIACIONES: Crear si no existen
        // Si recibimos nombres (strings) en lugar de IDs, crear o buscar
        \Log::info(' [PedidoPrendaService::guardarPrenda] INICIO - Procesando variaciones', [
            'color_recibido' => $prendaData['color'] ?? 'NO ESPECIFICADO',
            'color_id_recibido' => $prendaData['color_id'] ?? 'NO ESPECIFICADO',
            'tela_recibida' => $prendaData['tela'] ?? 'NO ESPECIFICADA',
            'tela_id_recibido' => $prendaData['tela_id'] ?? 'NO ESPECIFICADO',
            'manga_recibida' => $prendaData['manga'] ?? 'NO ESPECIFICADA',
            'tipo_manga_id_recibido' => $prendaData['tipo_manga_id'] ?? 'NO ESPECIFICADO',
            'broche_recibido' => $prendaData['broche'] ?? 'NO ESPECIFICADO',
            'tipo_broche_boton_id_recibido' => $prendaData['tipo_broche_boton_id'] ?? 'NO ESPECIFICADO',
        ]);
        
        $this->variacionesProcessor->procesarVariaciones($prendaData);

        //  SOLO GUARDAR LA DESCRIPCIÃ“N QUE ESCRIBIÃ“ EL USUARIO
        // NO formatear ni armar descripciones automÃ¡ticas
        $descripcionFinal = $prendaData['descripcion'] ?? '';
        
        // Obtener la PRIMERA tela de mÃºltiples telas para los campos principales
        // (tela_id, color_id se guardan en la prenda para referencia rÃ¡pida)
        $primeraTela = $this->obtenerPrimeraTela($prendaData);
        
        //  LOG: Antes de guardar
        \Log::info(' [PedidoPrendaService] Guardando prenda con observaciones', [
            'numero_pedido' => $pedido->numero_pedido,
            'nombre_prenda' => $prendaData['nombre_producto'] ?? 'Sin nombre',
            'genero' => $prendaData['genero'] ?? '',
            'descripcion_usuario' => $prendaData['descripcion'] ?? null,
            'manga_obs' => $prendaData['obs_manga'] ?? $prendaData['manga_obs'] ?? '',
            'bolsillos_obs' => $prendaData['obs_bolsillos'] ?? $prendaData['bolsillos_obs'] ?? '',
            'broche_obs' => $prendaData['obs_broche'] ?? $prendaData['broche_obs'] ?? '',
            'reflectivo_obs' => $prendaData['obs_reflectivo'] ?? $prendaData['reflectivo_obs'] ?? '',
            'tela_id_principal' => $primeraTela['tela_id'] ?? null,
            'color_id_principal' => $primeraTela['color_id'] ?? null,
            'total_telas' => !empty($prendaData['telas']) ? count($prendaData['telas']) : 0,
            'tipo_manga_id' => $prendaData['tipo_manga_id'] ?? null,
            'tipo_broche_boton_id' => $prendaData['tipo_broche_boton_id'] ?? null,
        ]);
        
        //  PROCESAR GÃ‰NEROS (puede ser single string o array de mÃºltiples gÃ©neros)
        $generoProcesado = $this->dataNormalizer->procesarGenero($prendaData['genero'] ?? '');
        
        //  PROCESAR CANTIDADES: Soportar mÃºltiples gÃ©neros
        // IMPORTANTE: cantidad_talla ya viene procesada desde el controlador/transformador
        $cantidadesInput = $prendaData['cantidad_talla'] ?? $prendaData['cantidades'] ?? null;
        
        \Log::info(' [PedidoPrendaService::guardarPrenda] PROCESANDO CANTIDADES', [
            'cantidad_talla_en_prendaData' => $prendaData['cantidad_talla'] ?? 'NO EXISTE',
            'cantidades_input' => $cantidadesInput,
            'tipo_cantidades' => gettype($cantidadesInput),
            'cantidad_talla_keys' => $cantidadesInput && is_array($cantidadesInput) ? array_keys($cantidadesInput) : 'N/A',
            'cantidad_talla_values' => $cantidadesInput,
        ]);
        
        $cantidadTallaFinal = $this->dataNormalizer->procesarCantidadTalla($cantidadesInput);
        
        \Log::info(' [PedidoPrendaService::guardarPrenda] CANTIDAD_TALLA_FINAL ANTES DE GUARDAR', [
            'cantidad_talla_final' => $cantidadTallaFinal,
            'es_vacio' => empty($cantidadTallaFinal),
        ]);
        
        // Crear prenda principal usando PrendaPedido (tabla correcta)
        // ACTUALIZACIÃ“N [16/01/2026]: Usar pedido_produccion_id en lugar de numero_pedido
        $prenda = $this->prendaBaseCreator->crearPrendaBase(
            $pedido->id,
            $prendaData,
            $cantidadTallaFinal,
            $generoProcesado,
            $index
        );

        // 2.  CREAR VARIANTES en prenda_pedido_variantes desde cantidad_talla
        // IMPORTANTE: Crear variante incluso si cantidad_talla estÃ¡ vacÃ­o
        // La variante es el registro de caracterÃ­sticas de la prenda
        {
            // Parsear variaciones si viene como JSON string
            $variacionesParsed = $prendaData['variaciones'] ?? [];
            if (is_string($variacionesParsed)) {
                $variacionesParsed = json_decode($variacionesParsed, true) ?? [];
            }
            
            // Obtener tipo_manga_id desde variaciones.tipo_manga_id o raíz
            $tipoMangaId = $variacionesParsed['tipo_manga_id'] ?? $prendaData['tipo_manga_id'] ?? null;
            
            // Si no hay ID pero hay nombre, obtener/crear
            if (empty($tipoMangaId)) {
                $nombreManga = $variacionesParsed['tipo_manga'] ?? $prendaData['manga'] ?? null;
                if (!empty($nombreManga)) {
                    $tipoMangaId = $this->caracteristicasService->obtenerOCrearManga($nombreManga);
                }
            }
            
            // Obtener tipo_broche_boton_id desde variaciones o raíz
            $tipoBrocheBotonId = $variacionesParsed['tipo_broche_boton_id'] ?? $prendaData['tipo_broche_boton_id'] ?? null;
            
            // Si no hay ID pero hay nombre, obtener/crear
            if (empty($tipoBrocheBotonId)) {
                $nombreBroche = $variacionesParsed['tipo_broche'] ?? $prendaData['broche'] ?? null;
                if (!empty($nombreBroche)) {
                    $tipoBrocheBotonId = $this->caracteristicasService->obtenerOCrearBroche($nombreBroche);
                }
            }
            
            // Obtener observaciones desde variaciones o raíz
            $obsManga = $variacionesParsed['obs_manga'] ?? $prendaData['obs_manga'] ?? $prendaData['manga_obs'] ?? '';
            $obsBroche = $variacionesParsed['obs_broche'] ?? $prendaData['obs_broche'] ?? $prendaData['broche_obs'] ?? '';
            $tieneBolsillos = (bool)($variacionesParsed['tiene_bolsillos'] ?? $prendaData['tiene_bolsillos'] ?? false);
            $obsBolsillos = $variacionesParsed['obs_bolsillos'] ?? $prendaData['obs_bolsillos'] ?? $prendaData['bolsillos_obs'] ?? '';
            
            // ✅ MEJORADO: Procesar nombres de color/tela si no vienen IDs
            $colorId = $prendaData['color_id'] ?? null;
            $telaId = $prendaData['tela_id'] ?? null;
            
            // Si no hay IDs pero hay nombres, procesarlos
            if ((!$colorId || !$telaId) && isset($prendaData['telas']) && is_array($prendaData['telas']) && count($prendaData['telas']) > 0) {
                try {
                    $colorTelaService = app(\App\Application\Services\ColorTelaService::class);
                    $primeraTela = $prendaData['telas'][0];
                    
                    if (!$colorId && isset($primeraTela['color'])) {
                        $colorId = $colorTelaService->obtenerOCrearColor($primeraTela['color']);
                        Log::info('[PedidoPrendaService] 🎨 Color procesado desde telas', [
                            'color_nombre' => $primeraTela['color'],
                            'color_id' => $colorId,
                        ]);
                    }
                    
                    if (!$telaId && isset($primeraTela['tela'])) {
                        $telaId = $colorTelaService->obtenerOCrearTela($primeraTela['tela']);
                        Log::info('[PedidoPrendaService] 🧵 Tela procesada desde telas', [
                            'tela_nombre' => $primeraTela['tela'],
                            'tela_id' => $telaId,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('[PedidoPrendaService] ❌ Error procesando color/tela', [
                        'error' => $e->getMessage(),
                        'prenda_data' => $prendaData,
                    ]);
                }
            }
            
            $this->crearVariantesDesdeCantidadTalla(
                $prenda->id, 
                $prendaData['cantidad_talla'] ?? [],
                $colorId,
                $telaId,
                $prendaData['referencia'] ?? null,
                $tipoMangaId,
                $tipoBrocheBotonId,
                $obsManga,
                $obsBroche,
                $tieneBolsillos,
                $obsBolsillos
            );
        }

        // 2b. TALLAS YA GUARDADAS en PrendaBaseCreatorService->crearPrendaBase()
        // NO volver a guardar aquí (causa duplicación)
        // Las tallas se guardan automáticamente en prenda_pedido_tallas cuando se crea la prenda base

        // 3. Guardar fotos de la prenda (soporta 'fotos' o 'imagenes')
        $fotosPrenda = $prendaData['fotos'] ?? $prendaData['imagenes'] ?? [];
        if (!empty($fotosPrenda)) {
            Log::info(' [PedidoPrendaService] Guardando fotos de prenda vía PrendaImagenService', [
                'prenda_id' => $prenda->id,
                'cantidad_fotos' => count($fotosPrenda),
            ]);
            $this->prendaImagenService->guardarFotosPrenda(
                $prenda->id,
                $pedido->id,
                $fotosPrenda
            );
        }

        // 4. Guardar logos de la prenda (si existen)
        if (!empty($prendaData['logos'])) {
            $this->guardarLogosPrenda($prenda, $prendaData['logos']);
        }

        // 5. Guardar fotos de telas/colores (si existen)
        Log::info(' [PedidoPrendaService::guardarPrenda] Verificando si hay telas para guardar', [
            'prenda_id' => $prenda->id,
            'tiene_telas' => !empty($prendaData['telas']),
            'cantidad_telas' => !empty($prendaData['telas']) ? count($prendaData['telas']) : 0,
            'telas_data' => $prendaData['telas'] ?? null,
        ]);
        
        if (!empty($prendaData['telas'])) {
            $this->guardarFotosTelas($prenda, $prendaData['telas']);
        } else {
            Log::warning(' [PedidoPrendaService] No hay telas para guardar en esta prenda', [
                'prenda_id' => $prenda->id,
                'prenda_data_keys' => array_keys($prendaData),
            ]);
        }

        // 6.  NUEVO: Guardar procesos de la prenda (si existen)
        Log::info(' [PedidoPrendaService::guardarPrenda] Verificando si hay procesos para guardar', [
            'prenda_id' => $prenda->id,
            'tiene_procesos' => !empty($prendaData['procesos']),
            'cantidad_procesos' => !empty($prendaData['procesos']) ? count($prendaData['procesos']) : 0,
            'procesos_data' => $prendaData['procesos'] ?? null,
        ]);
        
        if (!empty($prendaData['procesos'])) {
            $this->guardarProcesosPrenda($prenda, $prendaData['procesos']);
        } else {
            Log::info(' [PedidoPrendaService] No hay procesos para guardar en esta prenda', [
                'prenda_id' => $prenda->id,
            ]);
        }
        
        return $prenda;
    }

    /**
     * Construir array de datos formateado para el DescripcionPrendaLegacyFormatter
     * Convierte los datos del frontend a la estructura esperada por el formatter
     */
    private function obtenerPrimeraTela(array $prendaData): array
    {
        // Si hay un array de telas, obtener la primera
        if (!empty($prendaData['telas']) && is_array($prendaData['telas'])) {
            $primeraTela = reset($prendaData['telas']);
            if (is_array($primeraTela)) {
                return [
                    'tela_id' => $primeraTela['tela_id'] ?? null,
                    'color_id' => $primeraTela['color_id'] ?? null,
                ];
            }
        }
        
        // Si no hay telas mÃºltiples, usar los campos de variantes individuales
        return [
            'tela_id' => $prendaData['tela_id'] ?? null,
            'color_id' => $prendaData['color_id'] ?? null,
        ];
    }


    /**
     * Armar descripción de variaciones a partir de los datos
     */
    private function armarDescripcionVariaciones(array $prendaData): ?string
    {
        $partes = [];
        
        if (!empty($prendaData['manga'])) {
            $partes[] = "Manga: " . $prendaData['manga'];
        }
        if (!empty($prendaData['manga_obs'])) {
            $partes[] = "Obs Manga: " . $prendaData['manga_obs'];
        }
        if (!empty($prendaData['bolsillos_obs'])) {
            $partes[] = "Bolsillos: " . $prendaData['bolsillos_obs'];
        }
        if (!empty($prendaData['broche'])) {
            $partes[] = "Broche: " . $prendaData['broche'];
        }
        if (!empty($prendaData['reflectivo_obs'])) {
            $partes[] = "Reflectivo: " . $prendaData['reflectivo_obs'];
        }
        
        return implode(' | ', $partes);
    }

    /**
     * Guardar fotos de prenda
     * Delegado a PrendaImagenService
     */
    private function guardarFotosPrenda(PrendaPedido $prenda, array $fotos): void
    {
        $this->prendaImagenService->guardarFotosPrenda(
            $prenda->id,
            $prenda->pedido_produccion_id,
            $fotos
        );
    }

    /**
     * Guardar fotos de telas
     * Delegado a TelaImagenService
     */
    private function guardarFotosTelas(PrendaPedido $prenda, array $telas): void
    {
        $this->telaImagenService->guardarFotosTelas(
            $prenda->id,
            $prenda->pedido_produccion_id,
            $telas
        );
    }

    /**
     * Guardar procesos de prenda
     * Delegado a PrendaProcesoService
     */
    private function guardarProcesosPrenda(PrendaPedido $prenda, array $procesos): void
    {
        // Normalizar estructura de procesos (puede venir con diferentes formatos)
        $procesosNormalizados = [];
        
        foreach ($procesos as $key => $proceso) {
            // Si tiene 'datos' anidado, aplanarlo
            if (isset($proceso['datos']) && is_array($proceso['datos'])) {
                $procesosNormalizados[] = array_merge(
                    ['tipo' => $proceso['tipo'] ?? $key],
                    $proceso['datos']
                );
            } else {
                // Ya está en el formato correcto
                $procesosNormalizados[] = $proceso;
            }
        }
        
        $this->prendaProcesoService->guardarProcesosPrenda(
            $prenda->id,
            $prenda->pedido_produccion_id,
            $procesosNormalizados
        );
    }

    /**
     * Guardar tallas de prenda
     * Delegado a PrendaTallaService
     */
    private function guardarTallasPrenda(PrendaPedido $prenda, mixed $cantidades): void
    {
        $this->prendaTallaService->guardarTallasPrenda(
            $prenda->id,
            $cantidades
        );
    }

    /**
     * Crear variantes desde cantidad_talla
     * Delegado a PrendaVarianteService
     */
    private function crearVariantesDesdeCantidadTalla(
        int $prendaId,
        mixed $cantidadTalla,
        ?int $colorId = null,
        ?int $telaId = null,
        ?string $referencia = null,
        ?int $tipoMangaId = null,
        ?int $tipoBrocheBotonId = null,
        string $mangaObs = '',
        string $brocheObs = '',
        bool $tieneBolsillos = false,
        string $bolsillosObs = ''
    ): void {
        $this->prendaVarianteService->crearVariantesDesdeCantidadTalla(
            $prendaId,
            $cantidadTalla,
            $colorId,
            $telaId,
            $referencia,
            $tipoMangaId,
            $tipoBrocheBotonId,
            $mangaObs,
            $brocheObs,
            $tieneBolsillos,
            $bolsillosObs
        );
    }

    /**
     * Guardar logos de la prenda (tabla: prenda_fotos_logo_pedido)
     */
    private function guardarLogosPrenda(PrendaPedido $prenda, array $logos): void
    {
        foreach ($logos as $index => $logo) {
            DB::table('prenda_fotos_logo_pedido')->insert([
                'prenda_pedido_id' => $prenda->id,
                'ruta_original' => $logo['ruta_original'] ?? $logo['url'] ?? null,
                'ruta_webp' => $logo['ruta_webp'] ?? null,
                'ruta_miniatura' => $logo['ruta_miniatura'] ?? null,
                'orden' => $index + 1,
                'ubicacion' => $logo['ubicacion'] ?? null,
                'ancho' => $logo['ancho'] ?? null,
                'alto' => $logo['alto'] ?? null,
                'tamaÃ±o' => $logo['tamaÃ±o'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

