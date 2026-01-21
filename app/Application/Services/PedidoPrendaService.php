<?php

namespace App\Application\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Application\Services\ColorGeneroMangaBrocheService;
use App\Domain\PedidoProduccion\Services\ColorTelaService;
use App\Domain\PedidoProduccion\Services\CaracteristicasPrendaService;
use App\Domain\PedidoProduccion\Services\PrendaImagenService;
use App\Domain\PedidoProduccion\Services\TelaImagenService;
use App\Domain\PedidoProduccion\Services\PrendaTallaService;
use App\Domain\PedidoProduccion\Services\PrendaVarianteService;
use App\Domain\PedidoProduccion\Services\PrendaProcesoService;
use App\Domain\PedidoProduccion\Services\PrendaDataNormalizerService;
use App\Domain\PedidoProduccion\Services\VariacionesPrendaProcessorService;
use App\Domain\PedidoProduccion\Services\PrendaBaseCreatorService;
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
 * - OCP: Fácil de extender
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
     * Guardar prendas en pedido
     */
    public function guardarPrendasEnPedido(PedidoProduccion $pedido, array $prendas): void
    {
        Log::info(' [PedidoPrendaService::guardarPrendasEnPedido] INICIO - Análisis completo', [
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
                
                // CRÍTICO: Convertir DTO a array si es necesario
                if (is_object($prendaData) && method_exists($prendaData, 'toArray')) {
                    $prendaData = $prendaData->toArray();
                }
                
                $this->guardarPrenda($pedido, $prendaData, $index);
                
                //  VERIFICAR QUE LA PRENDA SE CREÓ CON UN ID ÚNICO
                $ultimaPrenda = PrendaPedido::where('pedido_produccion_id', $pedido->id)
                    ->orderBy('id', 'desc')
                    ->first();
                
                \Log::info(" [PRENDA #{$index}] DESPUÉS - Prenda creada con ID", [
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
    private function guardarPrenda(PedidoProduccion $pedido, mixed $prendaData, int $index = 1): void
    {
        // Normalizar datos de prenda (DTO → array)
        $prendaData = $this->dataNormalizer->normalizarPrendaData($prendaData);

        //  LOG: Ver qué datos llegan
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

        //  SOLO GUARDAR LA DESCRIPCIÓN QUE ESCRIBIÓ EL USUARIO
        // NO formatear ni armar descripciones automáticas
        $descripcionFinal = $prendaData['descripcion'] ?? '';
        
        // Obtener la PRIMERA tela de múltiples telas para los campos principales
        // (tela_id, color_id se guardan en la prenda para referencia rápida)
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
        
        //  PROCESAR GÉNEROS (puede ser single string o array de múltiples géneros)
        $generoProcesado = $this->dataNormalizer->procesarGenero($prendaData['genero'] ?? '');
        
        //  PROCESAR CANTIDADES: Soportar múltiples géneros
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
        // ACTUALIZACIÓN [16/01/2026]: Usar pedido_produccion_id en lugar de numero_pedido
        $prenda = $this->prendaBaseCreator->crearPrendaBase(
            $pedido->id,
            $prendaData,
            $cantidadTallaFinal,
            $generoProcesado,
            $index
        );

        // 2.  CREAR VARIANTES en prenda_pedido_variantes desde cantidad_talla
        // IMPORTANTE: Crear variante incluso si cantidad_talla está vacío
        // La variante es el registro de características de la prenda
        {
            // Parsear variaciones si viene como JSON string
            $variacionesParsed = $prendaData['variaciones'];
            if (is_string($variacionesParsed)) {
                $variacionesParsed = json_decode($variacionesParsed, true) ?? [];
            }
            
            // Obtener/crear manga y broche desde nombres si es necesario
            $tipoMangaId = $prendaData['tipo_manga_id'] ?? null;
            if (empty($tipoMangaId) && !empty($prendaData['manga'])) {
                $tipoMangaId = $this->caracteristicasService->obtenerOCrearManga($prendaData['manga']);
            }
            
            $tipoBrocheBotonId = $prendaData['tipo_broche_boton_id'] ?? null;
            if (empty($tipoBrocheBotonId) && !empty($prendaData['broche'])) {
                $tipoBrocheBotonId = $this->caracteristicasService->obtenerOCrearBroche($prendaData['broche']);
            }
            
            $this->crearVariantesDesdeCantidadTalla(
                $prenda->id, 
                $prendaData['cantidad_talla'] ?? [],
                $prendaData['color_id'] ?? null,
                $prendaData['tela_id'] ?? null,
                $tipoMangaId,
                $tipoBrocheBotonId,
                $prendaData['obs_manga'] ?? $prendaData['manga_obs'] ?? '',
                $prendaData['obs_broche'] ?? $prendaData['broche_obs'] ?? '',
                (bool)($prendaData['tiene_bolsillos'] ?? false),
                $prendaData['obs_bolsillos'] ?? $prendaData['bolsillos_obs'] ?? ''
            );
        }

        // 2b.  GUARDAR TALLAS CON CANTIDADES en prenda_tallas_ped (LEGACY)
        if (!empty($prendaData['cantidades'])) {
            $this->guardarTallasPrenda($prenda, $prendaData['cantidades']);
        }

        // 3. Guardar fotos de la prenda (copiar URLs de cotización)
        if (!empty($prendaData['fotos'])) {
            $this->guardarFotosPrenda($prenda, $prendaData['fotos']);
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
        
        // Si no hay telas múltiples, usar los campos de variantes individuales
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
        $this->prendaProcesoService->guardarProcesosPrenda(
            $prenda->id,
            $prenda->pedido_produccion_id,
            $procesos
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
            $tipoMangaId,
            $tipoBrocheBotonId,
            $mangaObs,
            $brocheObs,
            $tieneBolsillos,
            $bolsillosObs
        );
    }

    /**
     * LEGACY: Métodos privados originales mantenidos para compatibilidad
     * Estos métodos ya no se usan, la lógica está en los servicios especializados
     */
    private function guardarFotosPrendaLegacy(PrendaPedido $prenda, array $fotos): void
    {
        Log::info(' [PedidoPrendaService::guardarFotosPrenda] Guardando fotos de prenda', [
            'prenda_id' => $prenda->id,
            'cantidad_fotos' => count($fotos),
        ]);

        // Obtener color y tela de variantes si existen (para asociar imágenes)
        $colorId = null;
        $telaId = null;
        $variante = DB::table('prenda_pedido_variantes')
            ->where('prenda_pedido_id', $prenda->id)
            ->first();
        
        if ($variante) {
            $colorId = $variante->color_id ?? null;
            $telaId = $variante->tela_id ?? null;
        }

        foreach ($fotos as $index => $foto) {
            try {
                // CASO 1: UploadedFile (archivo real subido)
                if ($foto instanceof UploadedFile) {
                    Log::info(' [guardarFotosPrenda] Procesando UploadedFile', [
                        'prenda_id' => $prenda->id,
                        'index' => $index,
                        'nombre_archivo' => $foto->getClientOriginalName(),
                        'tamaño' => $foto->getSize(),
                        'mime_type' => $foto->getMimeType(),
                        'pedido_id' => $prenda->pedido_produccion_id,
                    ]);
                    
                    //  NO REQUIERE procesoDetalle - fotos son independientes
                    $resultado = $this->guardarArchivoImagenEnWeb(
                        $foto,
                        null,  // sin procesoDetalleId
                        $index,
                        'prenda',
                        $prenda->pedido_produccion_id  // pasar pedidoId directo
                    );
                    $rutaRelativa = $resultado['ruta_relativa'];
                    
                    Log::info(' [guardarFotosPrenda] Archivo procesado exitosamente', [
                        'ruta_relativa' => $rutaRelativa,
                    ]);
                    
                    // Asegurar que la ruta sea absoluta (comience con /)
                    $rutaAbsoluta = $rutaRelativa && !str_starts_with($rutaRelativa, '/') ? '/' . $rutaRelativa : $rutaRelativa;
                    
                    DB::table('prenda_fotos_pedido')->insert([
                        'prenda_pedido_id' => $prenda->id,
                        'ruta_original' => $foto->getClientOriginalName(),
                        'ruta_webp' => $rutaAbsoluta,
                        'orden' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info(" Foto de prenda guardada (UploadedFile)", [
                        'prenda_id' => $prenda->id,
                        'index' => $index,
                        'ruta_absoluta' => $rutaAbsoluta,
                    ]);
                }
                // CASO 2: Array con UploadedFile
                elseif (is_array($foto) && isset($foto['archivo']) && $foto['archivo'] instanceof UploadedFile) {
                    //  NO REQUIERE procesoDetalle - fotos son independientes
                    $resultado = $this->guardarArchivoImagenEnWeb(
                        $foto['archivo'],
                        null,  // sin procesoDetalleId
                        $index,
                        'prenda',
                        $prenda->pedido_produccion_id  // pasar pedidoId directo
                    );
                    $rutaRelativa = $resultado['ruta_relativa'];
                    
                    DB::table('prenda_fotos_pedido')->insert([
                        'prenda_pedido_id' => $prenda->id,
                        'ruta_original' => $foto['archivo']->getClientOriginalName(),
                        'ruta_webp' => $rutaRelativa,
                        'orden' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info(" Foto de prenda guardada (Array con UploadedFile)", [
                        'prenda_id' => $prenda->id,
                        'index' => $index,
                        'ruta_relativa' => $rutaRelativa,
                    ]);
                }
                // CASO 3: URL directa o ruta (string)
                elseif (is_string($foto) && !empty($foto)) {
                    // Aceptar URLs completas o rutas relativas (/storage/...)
                    DB::table('prenda_fotos_pedido')->insert([
                        'prenda_pedido_id' => $prenda->id,
                        'ruta_original' => $foto,
                        'ruta_webp' => $foto,
                        'orden' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info(" Foto de prenda guardada (string/ruta)", [
                        'prenda_id' => $prenda->id,
                        'index' => $index,
                        'ruta' => $foto,
                    ]);
                }
                // CASO 4: Array con URL o ruta
                elseif (is_array($foto) && (isset($foto['url']) || isset($foto['ruta_original']) || isset($foto['ruta_webp']))) {
                    $ruta = $foto['url'] ?? $foto['ruta_original'] ?? $foto['ruta_webp'] ?? null;
                    if ($ruta) {
                        DB::table('prenda_fotos_pedido')->insert([
                            'prenda_pedido_id' => $prenda->id,
                            'ruta_original' => $ruta,
                            'ruta_webp' => $ruta,
                            'orden' => $index + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        
                        Log::info(" Foto de prenda guardada (array con ruta)", [
                            'prenda_id' => $prenda->id,
                            'index' => $index,
                            'ruta' => $ruta,
                            'tipo' => $foto['tipo'] ?? null,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error(' Error guardando foto de prenda', [
                    'prenda_id' => $prenda->id,
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Guardar logos de la prenda
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
                'tamaño' => $logo['tamaño'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
