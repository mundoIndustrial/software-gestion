<?php

namespace App\Application\Services;

use App\Application\Shared\Contracts\TransactionManagerInterface;
use App\Application\Pedidos\Services\PrendaVarianteContextResolver;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Application\Services\ColorGeneroMangaBrocheService;
use App\Infrastructure\Services\Pedidos\PrendaRelationsPersistenceService;
use App\Application\Services\PrendaTallaService;
use App\Application\Services\PrendaVarianteService;
use App\Application\Services\PrendaDataNormalizerService;
use App\Application\Services\VariacionesPrendaProcessorService;
use App\Application\Services\PrendaBaseCreatorService;
use Illuminate\Support\Facades\Log;

/**
 * PedidoPrendaService
 * Responsabilidad: Guardar prendas de pedidos en tablas normalizadas
 * Equivalente a CotizacionPrendaService pero para pedidos*
 * Cumple:
 * - SRP: Solo guarda prendas
 * - DIP: Inyecta dependencias
 * - OCP: facil de extender
 */
class PedidoPrendaService
{
    private ColorGeneroMangaBrocheService $colorGeneroService;
    private PrendaTallaService $prendaTallaService;
    private PrendaVarianteService $prendaVarianteService;
    private PrendaDataNormalizerService $dataNormalizer;
    private VariacionesPrendaProcessorService $variacionesProcessor;
    private PrendaBaseCreatorService $prendaBaseCreator;
    private PrendaVarianteContextResolver $prendaVarianteContextResolver;
    private PrendaRelationsPersistenceService $prendaRelationsPersistenceService;
    private TransactionManagerInterface $transactionManager;

    public function __construct(
        ColorGeneroMangaBrocheService $colorGeneroService,
        ?PedidoPrendaDependencies $dependencies = null,
        ?TransactionManagerInterface $transactionManager = null
    ) {
        $dependencies ??= app(PedidoPrendaDependencies::class);

        $this->colorGeneroService = $colorGeneroService;
        $this->prendaTallaService = $dependencies->prendaTallaService;
        $this->prendaVarianteService = $dependencies->prendaVarianteService;
        $this->dataNormalizer = $dependencies->dataNormalizer;
        $this->variacionesProcessor = $dependencies->variacionesProcessor;
        $this->prendaBaseCreator = $dependencies->prendaBaseCreator;
        $this->prendaVarianteContextResolver = $dependencies->prendaVarianteContextResolver;
        $this->prendaRelationsPersistenceService = $dependencies->prendaRelationsPersistenceService;
        $this->transactionManager = $transactionManager ?? app(TransactionManagerInterface::class);
    }

    /**
     * Guardar UNA prenda en pedido (usado por CommandHandlers)
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
        Log::info(' [PedidoPrendaService::guardarPrendasEnPedido] INICIO - analisis completo', [
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

        $this->transactionManager->run(function () use ($pedido, $prendas) {
            $index = 1;
            $prendasCreadas = [];
            
            \Log::info(' [INICIANDO LOOP] Guardando ' . count($prendas) . ' prendas del pedido ' . $pedido->id);
            
            foreach ($prendas as $prendaIndex => $prendaData) {
                \Log::info(" [PRENDA #{$index}] ANTES - Guardando prenda #{$index} de " . count($prendas), [
                    'prendaIndex' => $prendaIndex,
                    'nombre' => $prendaData['nombre_producto'] ?? 'SIN NOMBRE',
                ]);
                
                // critico: Convertir DTO a array si es necesario
                if (is_object($prendaData) && method_exists($prendaData, 'toArray')) {
                    $prendaData = $prendaData->toArray();
                }
                
                $this->guardarPrenda($pedido, $prendaData, $index);
                
                //  VERIFICAR QUE LA PRENDA SE Crea CON UN ID unico
                $ultimaPrenda = PrendaPedido::where('pedido_produccion_id', $pedido->id)
                    ->orderBy('id', 'desc')
                    ->first();
                
                \Log::info(" [PRENDA #{$index}] Despues- Prenda creada con ID", [
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

            Log::info(' [PedidoPrendaService] Prendas guardadas correctamente', [
                'pedido_id' => $pedido->id,
                'cantidad' => count($prendas),
            ]);
        });
    }

    /**
     * Guardar una prenda con sus relaciones
     * Genera descripción formateada usando DescripcionPrendaLegacyFormatter
     * (Formato compatible con pedidos legacy como 45452)
     */
    private function guardarPrenda(PedidoProduccion $pedido, mixed $prendaData, int $index = 1): PrendaPedido
    {
        // Normalizar datos de prenda (DTO  array)
        $prendaData = $this->dataNormalizer->normalizarPrendaData($prendaData);

        //  LOG: Ver que datos llegan
        \Log::info(' [PedidoPrendaService] Datos recibidos para guardar prenda', [
            'nombre_producto' => $prendaData['nombre_producto'] ?? null,
            'tela_id' => $prendaData['tela_id'] ?? null,
            'color_id' => $prendaData['color_id'] ?? null,
            'tipo_manga_id' => $prendaData['tipo_manga_id'] ?? null,
            'tipo_broche_boton_id' => $prendaData['tipo_broche_boton_id'] ?? null,
            'manga' => $prendaData['manga'] ?? null,
            'broche' => $prendaData['broche'] ?? null,
        ]);

        
        $this->variacionesProcessor->procesarVariaciones($prendaData);

        // Obtener la PRIMERA tela de multiples telas para los campos principales
        // (tela_id, color_id se guardan en la prenda para referencia rapida)
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
        
        //  PROCESAR generos (puede ser single string o array de multiples generos)
        $generoProcesado = $this->dataNormalizer->procesarGenero($prendaData['genero'] ?? '');
        
        //  PROCESAR CANTIDADES: Soportar multiples generos
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
        // Actualización[16/01/2026]: Usar pedido_produccion_id en lugar de numero_pedido
        $prenda = $this->prendaBaseCreator->crearPrendaBase(
            $pedido->id,
            $prendaData,
            $cantidadTallaFinal,
            $generoProcesado,
            $index
        );

        // 2.  CREAR VARIANTES en prenda_pedido_variantes desde cantidad_talla
        // IMPORTANTE: Crear variante incluso si cantidad_talla está vacio
        // La variante es el registro de caracteristicas de la prenda
        {
            $varianteContexto = $this->prendaVarianteContextResolver->resolver($prendaData);

            $this->prendaVarianteService->crearVariantesDesdeCantidadTalla(
                $prenda->id,
                $prendaData['cantidad_talla'] ?? [],
                $varianteContexto['color_id'],
                $varianteContexto['tela_id'],
                $prendaData['referencia'] ?? null,
                $varianteContexto['tipo_manga_id'],
                $varianteContexto['tipo_broche_boton_id'],
                $varianteContexto['obs_manga'],
                $varianteContexto['obs_broche'],
                $varianteContexto['tiene_bolsillos'],
                $varianteContexto['obs_bolsillos']
            );
        }

        // 2b. TALLAS YA GUARDADAS en PrendaBaseCreatorService->crearPrendaBase()
        // NO volver a guardar aquí (causa duplicación)
        // Las tallas se guardan automáticamente en prenda_pedido_tallas cuando se crea la prenda base

        $this->prendaRelationsPersistenceService->guardarRelaciones($prenda, $prendaData);

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
        
        // Si no hay telas multiples, usar los campos de variantes individuales
        return [
            'tela_id' => $prendaData['tela_id'] ?? null,
            'color_id' => $prendaData['color_id'] ?? null,
        ];
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

}
