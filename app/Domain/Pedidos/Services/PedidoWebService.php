<?php

namespace App\Domain\Pedidos\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaPedidoTalla;
use App\Models\PrendaVariantePed;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidosProcesosPrendaTalla;
use App\Models\PedidosProcessImagenes;
use App\Models\PedidoEpp;
use App\Models\PedidoEppImagen;
use App\Models\TipoPrenda;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use App\Application\Services\ImageUploadService;
use App\Domain\Pedidos\Services\ProcesoImagenService;
use App\Domain\Pedidos\Services\PedidoSequenceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * PedidoWebService
 * 
 * Servicio unificado para crear pedidos completos con todas sus relaciones
 * Guarda en todas las tablas: prendas, tallas, variantes, procesos, imágenes
 * 
 * REFACTORIZADO: Ahora usa guardarImagenDirecta() sin carpetas temporales
 */
class PedidoWebService
{
    private const STORAGE_DISK = 'public';
    private PrendaImagenService $prendaImagenService;
    private TelaImagenService $telaImagenService;
    private ProcesoImagenService $procesoImagenService;
    private ImageUploadService $imageUploadService;
    private PedidoSequenceService $pedidoSequenceService;

    public function __construct(
        PrendaImagenService $prendaImagenService = null,
        TelaImagenService $telaImagenService = null,
        ProcesoImagenService $procesoImagenService = null,
        ImageUploadService $imageUploadService = null,
        PedidoSequenceService $pedidoSequenceService = null
    ) {
        $this->prendaImagenService = $prendaImagenService ?? app(PrendaImagenService::class);
        $this->telaImagenService = $telaImagenService ?? app(TelaImagenService::class);
        $this->procesoImagenService = $procesoImagenService ?? app(ProcesoImagenService::class);
        $this->imageUploadService = $imageUploadService ?? app(ImageUploadService::class);
        $this->pedidoSequenceService = $pedidoSequenceService ?? app(PedidoSequenceService::class);
    }

    /**
     * Crear pedido completo con todas sus prendas, procesos e imágenes
     */
    public function crearPedidoCompleto(array $datosValidados, int $asesorId): PedidoProduccion
    {
        $tiempoInicio = microtime(true);
        
        return DB::transaction(function () use ($datosValidados, $asesorId, &$tiempoInicio) {
            // 1. Crear pedido base
            $tiempoInicioBase = microtime(true);
            $pedido = $this->crearPedidoBase($datosValidados, $asesorId);
            $tiempoBase = (microtime(true) - $tiempoInicioBase) * 1000;
            
            Log::info('[PedidoWebService] Pedido base creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido, // null hasta aprobación de cartera
                'area_guardada' => $pedido->area,
                'estado' => $pedido->estado,
                'tiempo_base_ms' => round($tiempoBase, 2),
            ]);

            // 2. Crear prendas con todas sus relaciones
            if (isset($datosValidados['items']) && is_array($datosValidados['items'])) {
                foreach ($datosValidados['items'] as $itemIndex => $itemData) {
                    $this->crearItemCompleto($pedido, $itemData, $itemIndex);
                }
            }

            Log::info('[PedidoWebService] Pedido completo creado', [
                'pedido_id' => $pedido->id,
                'cantidad_prendas' => $pedido->prendas()->count(),
                'area_final' => $pedido->area,
                'tiempo_total_ms' => round((microtime(true) - $tiempoInicio) * 1000, 2),
            ]);

            return $pedido;
        });
    }

    /**
     * Crear pedido base
     */
    private function crearPedidoBase(array $datos, int $asesorId): PedidoProduccion
    {
        //  EXTRAER ÁREA CON DEFAULT
        $area = $datos['area'] ?? $datos['estado_area'] ?? 'Creación Orden';
        if (is_string($area)) {
            $area = trim($area);
            $area = empty($area) ? 'Creación Orden' : $area;
        } else {
            $area = 'creacion de pedido';
        }

        Log::info('[PedidoWebService] Creando pedido base sin número (se asignará al aprobar cartera)', [
            'area' => $area,
            'estado' => 'pendiente_cartera',
        ]);

        return PedidoProduccion::create([
            'numero_pedido' => null, // Se asignará cuando cartera apruebe
            'cliente' => $datos['cliente'] ?? 'SIN NOMBRE',
            'asesor_id' => $asesorId,
            'cliente_id' => $datos['cliente_id'] ?? null,
            'forma_de_pago' => $datos['forma_de_pago'] ?? 'CONTADO',
            'novedades' => $datos['descripcion'] ?? null,
            'estado' => 'pendiente_cartera',
            'cantidad_total' => 0,
            'area' => $area,  // AHORA SE GUARDA EL ÁREA CORRECTAMENTE
            'fecha_de_creacion_de_orden' => now(), // Fecha actual de creación de la orden
        ]);
    }

    /**
     * Crear item (prenda) completo con tallas, variantes, procesos e imágenes
     */
    private function crearItemCompleto(PedidoProduccion $pedido, array $itemData, int $itemIndex): PrendaPedido
    {
        // CREAR TIPO DE PRENDA SI NO EXISTE
        $nombrePrenda = $itemData['nombre_prenda'] ?? 'SIN NOMBRE';
        $this->crearOObtenerTipoPrenda($nombrePrenda);

        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => $nombrePrenda,
            'descripcion' => $itemData['descripcion'] ?? null,
            'de_bodega' => $itemData['de_bodega'] ?? 0,
        ]);

        Log::info('[PedidoWebService] Prenda creada', [
            'prenda_id' => $prenda->id,
            'nombre' => $prenda->nombre_prenda,
        ]);

        // Crear tallas
        if (isset($itemData['cantidad_talla']) && is_array($itemData['cantidad_talla'])) {
            $this->crearTallasPrenda($prenda, $itemData['cantidad_talla']);
        }

        // Crear variantes
        if (isset($itemData['variaciones']) && is_array($itemData['variaciones'])) {
            $this->crearVariantesPrenda($prenda, $itemData['variaciones']);
        }

        // 🔍 DEBUG: Verificar telas
        $tieneTelas = isset($itemData['telas']) && is_array($itemData['telas']) && count($itemData['telas']) > 0;
        $tieneTelasAntiguo = isset($itemData['prenda_pedido_colores_telas']) && is_array($itemData['prenda_pedido_colores_telas']) && count($itemData['prenda_pedido_colores_telas']) > 0;
        
        if ($tieneTelas || $tieneTelasAntiguo) {
            \Log::info('[PedidoWebService] 🧵 Creando telas', [
                'prenda_id' => $prenda->id,
                'telas_count' => $tieneTelas ? count($itemData['telas']) : ($tieneTelasAntiguo ? count($itemData['prenda_pedido_colores_telas']) : 0),
                'tipo' => $tieneTelasAntiguo ? 'ANTIGUO' : 'NUEVO',
            ]);
        } else {
            \Log::warning('[PedidoWebService]  SIN TELAS para prenda ' . $prenda->id);
        }

        // Crear colores y telas - intenta tanto del formulario antiguo como del nuevo
        if (isset($itemData['prenda_pedido_colores_telas']) && is_array($itemData['prenda_pedido_colores_telas'])) {
            $this->crearColoresTelas($prenda, $itemData['prenda_pedido_colores_telas']);
        } elseif (isset($itemData['telas']) && is_array($itemData['telas'])) {
            $this->crearTelasDesdeFormulario($prenda, $itemData['telas']);
        }

        //  DESHABILITADO: Imágenes se procesan en CrearPedidoEditableController::procesarYAsignarImagenes()
        // Las imágenes YA NO se procesan aquí para evitar duplicación
        // if (isset($itemData['imagenes']) && is_array($itemData['imagenes'])) {
        //     $this->guardarImagenesPrenda($prenda, $itemData['imagenes']);
        // }

        // 🔍 DEBUG: Verificar procesos
        $tieneProc = isset($itemData['procesos']) && is_array($itemData['procesos']) && count($itemData['procesos']) > 0;
        if ($tieneProc) {
            \Log::info('[PedidoWebService]  Creando procesos', [
                'prenda_id' => $prenda->id,
                'procesos_count' => count($itemData['procesos']),
                'procesos_keys' => array_keys($itemData['procesos']),
            ]);
        } else {
            \Log::warning('[PedidoWebService]  SIN PROCESOS para prenda ' . $prenda->id);
        }

        // Crear procesos
        if (isset($itemData['procesos']) && is_array($itemData['procesos'])) {
            $this->crearProcesosCompletos($prenda, $itemData['procesos']);
        }

        return $prenda;
    }

    /**
     * Crear tallas para una prenda
     */
    private function crearTallasPrenda(PrendaPedido $prenda, array $cantidadTalla): void
    {
        // cantidadTalla puede ser:
        // 1. Normal: { DAMA: {S: 10, M: 20}, CABALLERO: {...} }
        // 2. Sobremedida: { SOBREMEDIDA: {CABALLERO: 100, DAMA: 50} }
        // 3. Mixta: { DAMA: {S: 10}, SOBREMEDIDA: {CABALLERO: 100} }
        
        foreach ($cantidadTalla as $generoOEspecial => $contenido) {
            if (!is_array($contenido) || empty($contenido)) {
                continue;
            }
            
            // CASO ESPECIAL: SOBREMEDIDA
            if (strtoupper($generoOEspecial) === 'SOBREMEDIDA') {
                // Estructura: {SOBREMEDIDA: {CABALLERO: 32432, DAMA: 100}}
                // generoOEspecial = "SOBREMEDIDA"
                // contenido = {CABALLERO: 32432, DAMA: 100}
                
                foreach ($contenido as $genero => $cantidad) {
                    if ($cantidad > 0) {
                        PrendaPedidoTalla::create([
                            'prenda_pedido_id' => $prenda->id,
                            'genero' => strtoupper($genero),  // El género CORRECTO es la clave interna
                            'talla' => null,                 // Sobremedida no tiene talla específica
                            'cantidad' => (int)$cantidad,
                            'es_sobremedida' => true,        // Marcar como sobremedida
                        ]);
                    }
                }
                
                Log::info('[PedidoWebService] Sobremedida creada', [
                    'prenda_id' => $prenda->id,
                    'generos_sobremedida' => count($contenido),
                ]);
            } else {
                // CASO NORMAL: Género con tallas
                // Estructura: {DAMA: {S: 10, M: 20}}
                // generoOEspecial = "DAMA"
                // contenido = {S: 10, M: 20}
                
                foreach ($contenido as $talla => $cantidad) {
                    if ($cantidad > 0) {
                        PrendaPedidoTalla::create([
                            'prenda_pedido_id' => $prenda->id,
                            'genero' => strtoupper($generoOEspecial),
                            'talla' => $talla,
                            'cantidad' => (int)$cantidad,
                        ]);
                    }
                }
            }
        }

        Log::info('[PedidoWebService] Tallas creadas', [
            'prenda_id' => $prenda->id,
            'cantidad_generos' => count($cantidadTalla),
        ]);
    }

    /**
     * Crear variantes para una prenda
     */
    private function crearVariantesPrenda(PrendaPedido $prenda, array $variaciones): void
    {
        Log::info('[PedidoWebService]  Creando variantes', [
            'prenda_id' => $prenda->id,
            'variaciones' => $variaciones
        ]);
        
        try {
            PrendaVariantePed::create([
                'prenda_pedido_id' => $prenda->id,
                'tipo_manga_id' => $variaciones['tipo_manga_id'] ?? null,
                'tipo_broche_boton_id' => $variaciones['tipo_broche_boton_id'] ?? null,
                'manga_obs' => $variaciones['obs_manga'] ?? null,
                'broche_boton_obs' => $variaciones['obs_broche'] ?? null,
                'tiene_bolsillos' => $variaciones['tiene_bolsillos'] ?? 0,
                'bolsillos_obs' => $variaciones['obs_bolsillos'] ?? null,
            ]);

            Log::info('[PedidoWebService]  Variantes creadas exitosamente', [
                'prenda_id' => $prenda->id,
                'tipo_manga_id' => $variaciones['tipo_manga_id'] ?? null,
                'tipo_broche_boton_id' => $variaciones['tipo_broche_boton_id'] ?? null,
                'tiene_bolsillos' => $variaciones['tiene_bolsillos'] ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::error('[PedidoWebService] ❌ Error creando variantes', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage(),
                'variaciones' => $variaciones
            ]);
        }
    }

    /**
     * Crear colores y telas
     */
    private function crearColoresTelas(PrendaPedido $prenda, array $coloresTelas): void
    {
        foreach ($coloresTelas as $colorTela) {
            PrendaPedidoColorTela::create([
                'prenda_pedido_id' => $prenda->id,
                'color_id' => $colorTela['color_id'] ?? null,
                'tela_id' => $colorTela['tela_id'] ?? null,
                'referencia' => $colorTela['referencia'] ?? null,
            ]);
        }
    }

    /**
     * Crear telas desde formulario frontend (mapeo de nombres a IDs)
     */
    private function crearTelasDesdeFormulario(PrendaPedido $prenda, array $telas): void
    {
        \Log::info('[PedidoWebService] 🧵 crearTelasDesdeFormulario INICIADA', [
            'prenda_id' => $prenda->id,
            'telas_count' => count($telas),
            'telas_estructura' => array_keys($telas[0] ?? []),
            'telas_data' => json_encode($telas)
        ]);

        $telasCreadasCount = 0;

        foreach ($telas as $telaData) {
            Log::info('[PedidoWebService] 🔍 Procesando tela individual', [
                'tela_data_original' => $telaData,
                'tiene_tela_id' => isset($telaData['tela_id']),
                'tiene_color_id' => isset($telaData['color_id']),
                'tiene_tela' => isset($telaData['tela']),
                'tiene_color' => isset($telaData['color']),
                'tela_id' => $telaData['tela_id'] ?? 'NO EXISTE',
                'color_id' => $telaData['color_id'] ?? 'NO EXISTE',
                'tela' => $telaData['tela'] ?? 'NO EXISTE',
                'color' => $telaData['color'] ?? 'NO EXISTE',
            ]);

            // Si tela_id y color_id ya están presentes y son válidos (> 0), usarlos directamente
            if (isset($telaData['tela_id']) && isset($telaData['color_id']) && 
                $telaData['tela_id'] > 0 && $telaData['color_id'] > 0) {
                $colorTela = PrendaPedidoColorTela::create([
                    'prenda_pedido_id' => $prenda->id,
                    'color_id' => $telaData['color_id'],
                    'tela_id' => $telaData['tela_id'],
                    'referencia' => $telaData['referencia'] ?? null,
                ]);
                $telasCreadasCount++;

                \Log::info('[PedidoWebService] Tela creada (directo)', [
                    'prenda_id' => $prenda->id,
                    'tela_id' => $telaData['tela_id'],
                    'color_id' => $telaData['color_id'],
                    'referencia' => $telaData['referencia'] ?? null,
                    'color_tela_id' => $colorTela->id,
                ]);

                //  DESHABILITADO: Imágenes se procesan en CrearPedidoEditableController::procesarYAsignarImagenes()
                // Guardar imágenes de tela si existen
                // if (isset($telaData['imagenes']) && is_array($telaData['imagenes'])) {
                //     $this->guardarImagenesTela($colorTela, $telaData['imagenes'], $prenda->pedido_produccion_id);
                // }
            } else {
                //  MEJORADO: Usar ColorTelaService para obtener o crear colores/telas
                $telaId = null;
                $colorId = null;

                try {
                    // MEJORADO: Buscar tanto en 'tela' como en 'tela_nombre'
                    $telaNombre = $telaData['tela'] ?? $telaData['tela_nombre'] ?? null;
                    if ($telaNombre) {
                        // Usar ColorTelaService para obtener o crear tela
                        $colorTelaService = app(\App\Application\Services\ColorTelaService::class);
                        $telaId = $colorTelaService->obtenerOCrearTela($telaNombre);
                        
                        Log::info('[PedidoWebService] 🧵 Tela procesada', [
                            'tela_nombre' => $telaNombre,
                            'tela_id' => $telaId,
                        ]);
                    }

                    // MEJORADO: Buscar tanto en 'color' como en 'color_nombre'
                    $colorNombre = $telaData['color'] ?? $telaData['color_nombre'] ?? null;
                    if ($colorNombre && !empty($colorNombre)) {
                        // Usar ColorTelaService para obtener o crear color
                        $colorTelaService = app(\App\Application\Services\ColorTelaService::class);
                        $colorId = $colorTelaService->obtenerOCrearColor($colorNombre);
                        
                        Log::info('[PedidoWebService]  Color procesado', [
                            'color_nombre' => $colorNombre,
                            'color_id' => $colorId,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('[PedidoWebService] ❌ Error procesando color/tela con servicio', [
                        'error' => $e->getMessage(),
                        'tela_data' => $telaData,
                    ]);
                    
                    //  FALLBACK: Buscar directamente en BD si el servicio falla
                    try {
                        $telaNombre = $telaData['tela'] ?? $telaData['tela_nombre'] ?? null;
                        if ($telaNombre && !$telaId) {
                            $tela = TelaPrenda::where('nombre', 'like', '%' . $telaNombre . '%')->first();
                            if ($tela) {
                                $telaId = $tela->id;
                                Log::info('[PedidoWebService] 🧵 Tela encontrada por fallback', [
                                    'tela_nombre' => $telaNombre,
                                    'tela_id' => $telaId,
                                ]);
                            }
                        }
                        
                        $colorNombre = $telaData['color'] ?? $telaData['color_nombre'] ?? null;
                        if ($colorNombre && !empty($colorNombre) && !$colorId) {
                            $color = ColorPrenda::where('nombre', 'like', '%' . $colorNombre . '%')->first();
                            if ($color) {
                                $colorId = $color->id;
                                Log::info('[PedidoWebService]  Color encontrado por fallback', [
                                    'color_nombre' => $colorNombre,
                                    'color_id' => $colorId,
                                ]);
                            }
                        }
                    } catch (\Exception $fallbackError) {
                        Log::error('[PedidoWebService] ❌ Error en fallback también', [
                            'error' => $fallbackError->getMessage(),
                            'tela_data' => $telaData,
                        ]);
                    }
                }

                //  MEJORADO: Crear tela si solo hay nombre (sin requerir color) - PERO NO crear genérica si solo hay color
                $telaNombreGeneral = $telaData['nombre'] ?? $telaData['tela'] ?? $telaData['tela_nombre'] ?? null;
                
                Log::info('[PedidoWebService] 🔍 DIAGNÓSTICO - Verificando búsqueda de tela', [
                    'colorId' => $colorId,
                    'telaId' => $telaId,
                    'tiene_nombre_tela' => !empty($telaNombreGeneral),
                    'nombre_tela_valor' => $telaNombreGeneral ?? 'NO EXISTE',
                    'condicion_tela_sin_color' => (!$telaId && !empty($telaNombreGeneral))
                ]);
                
                // Si no hay telaId pero hay nombre de tela, buscar o crear la tela
                if (!$telaId && !empty($telaNombreGeneral)) {
                    // Buscar tela con el mismo nombre
                    $telaExistente = TelaPrenda::where('nombre', 'like', '%' . $telaNombreGeneral . '%')
                                                ->where('activo', true)
                                                ->first();
                    if ($telaExistente) {
                        $telaId = $telaExistente->id;
                        Log::info('[PedidoWebService] 🧵 Tela encontrada por nombre', [
                            'tela_nombre_busqueda' => $telaNombreGeneral,
                            'tela_encontrada' => $telaExistente->nombre,
                            'tela_id' => $telaId,
                        ]);
                    } else {
                        // Si no encuentra tela con ese nombre, crear una nueva
                        $telaPorDefecto = TelaPrenda::create([
                            'nombre' => $telaNombreGeneral ?: 'Tela Genérica',
                            'referencia' => 'GEN-' . time(),
                            'descripcion' => 'Tela creada automáticamente',
                            'activo' => true,
                        ]);
                        $telaId = $telaPorDefecto->id;
                        Log::info('[PedidoWebService] 🧵 Tela creada (no existía)', [
                            'tela_nombre' => $telaPorDefecto->nombre,
                            'tela_id' => $telaId,
                        ]);
                    }
                }

                // Crear registro de tela con color si hay telaId (color es opcional)
                // ✅ Si solo hay color sin tela → se crea el registro con tela_id = NULL
                // ✅ Si hay tela con o sin color → se crea el registro
                if ($telaId || $colorId) {
                    $colorTela = PrendaPedidoColorTela::create([
                        'prenda_pedido_id' => $prenda->id,
                        'color_id' => $colorId ?? null,  // Color es opcional
                        'tela_id' => $telaId ?? null,    // Tela es opcional
                        'referencia' => $telaData['referencia'] ?? null,
                    ]);
                    $telasCreadasCount++;

                    \Log::info('[PedidoWebService] Tela/Color registrado', [
                        'prenda_id' => $prenda->id,
                        'tela_nombre' => $telaData['tela'] ?? $telaData['tela_nombre'] ?? 'N/A',
                        'tela_id' => $telaId ?? null,
                        'color_nombre' => $telaData['color'] ?? $telaData['color_nombre'] ?? 'N/A',
                        'color_id' => $colorId ?? null,
                        'referencia' => $telaData['referencia'] ?? null,
                        'tipo_registro' => !$telaId && $colorId ? 'solo_color' : (!$colorId && $telaId ? 'solo_tela' : 'tela_y_color'),
                    ]);

                    //  DESHABILITADO: Imágenes se procesan en CrearPedidoEditableController::procesarYAsignarImagenes()
                    // Guardar imágenes de tela si existen
                    // if (isset($telaData['imagenes']) && is_array($telaData['imagenes'])) {
                    //     $this->guardarImagenesTela($colorTela, $telaData['imagenes'], $prenda->pedido_produccion_id);
                    // }
                }
            }
        }

        \Log::info('[PedidoWebService] 🧵 crearTelasDesdeFormulario TERMINADA', [
            'prenda_id' => $prenda->id,
            'telas_creadas' => $telasCreadasCount,
        ]);
    }

    /**
     * Guardar imágenes de tela
     * Nota: imagenes son rutas guardadas (strings), no UploadedFile
     */
    private function guardarImagenesTela(PrendaPedidoColorTela $colorTela, array $imagenes, int $pedidoId): void
    {
        if (empty($imagenes)) {
            return;
        }

        try {
            // 1. Relocalizar imágenes de temp a estructura final
            $rutasFinales = $this->imagenRelocalizadorService->relocalizarImagenes(
                $pedidoId,
                $imagenes,
                'telas'
            );

            if (empty($rutasFinales)) {
                Log::warning('[PedidoWebService] No se pudieron relocalizar imágenes de tela', [
                    'color_tela_id' => $colorTela->id,
                    'cantidad_originales' => count($imagenes),
                ]);
                return;
            }

            // 2. Guardar referencias en BD usando TelaImagenService
            $telaData = [
                'color_id' => $colorTela->color_id,
                'tela_id' => $colorTela->tela_id,
                'fotos' => $rutasFinales,
            ];

            $this->telaImagenService->guardarFotosTelas(
                $colorTela->prenda_pedido_id,
                $pedidoId,
                [$telaData]
            );

            Log::info('[PedidoWebService] Imágenes tela relocalizadas y guardadas', [
                'color_tela_id' => $colorTela->id,
                'pedido_id' => $pedidoId,
                'cantidad_originales' => count($imagenes),
                'cantidad_finales' => count($rutasFinales),
            ]);
        } catch (\Exception $e) {
            Log::error('[PedidoWebService] Error guardando imágenes tela', [
                'color_tela_id' => $colorTela->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Guardar imágenes de prenda
     * Nota: imagenes son rutas guardadas (strings), pueden estar en temp o finales
     * 1. Relocaliza desde temp a pedidos/{pedido_id}/prendas/
     * 2. Usa PrendaImagenService para guardar referencias en BD
     */
    private function guardarImagenesPrenda(PrendaPedido $prenda, array $imagenes): void
    {
        if (empty($imagenes)) {
            return;
        }

        try {
            // 1. Relocalizar imágenes de temp a estructura final
            $rutasFinales = $this->imagenRelocalizadorService->relocalizarImagenes(
                $prenda->pedido_produccion_id,
                $imagenes
            );

            if (empty($rutasFinales)) {
                Log::warning('[PedidoWebService] No se pudieron relocalizar imágenes de prenda', [
                    'prenda_id' => $prenda->id,
                    'cantidad_originales' => count($imagenes),
                ]);
                return;
            }

            // 2. Guardar referencias en BD usando PrendaImagenService
            $this->prendaImagenService->guardarFotosPrenda(
                $prenda->id,
                $prenda->pedido_produccion_id,
                $rutasFinales
            );

            Log::info('[PedidoWebService] Imágenes prenda relocalizadas y guardadas', [
                'prenda_id' => $prenda->id,
                'pedido_id' => $prenda->pedido_produccion_id,
                'cantidad_originales' => count($imagenes),
                'cantidad_finales' => count($rutasFinales),
            ]);
        } catch (\Exception $e) {
            Log::error('[PedidoWebService] Error guardando imágenes prenda', [
                'prenda_id' => $prenda->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Crear procesos completos con detalles e imágenes
     * 
     * Los procesos llegan ya deserializados desde CrearPedidoCompletoRequest
     * Estructura esperada: { reflectivo: { tipo: 'reflectivo', datos: { ubicaciones, tallas, imagenes, ... } } }
     */
    private function crearProcesosCompletos(PrendaPedido $prenda, array $procesos): void
    {
        \Log::info('[PedidoWebService]  crearProcesosCompletos INICIADA', [
            'prenda_id' => $prenda->id,
            'procesos_count' => count($procesos),
            'procesos_keys' => array_keys($procesos),
        ]);

        foreach ($procesos as $tipoProceso => $procesoData) {
            // Validar que procesoData sea array
            if (!is_array($procesoData)) {
                Log::warning('[PedidoWebService] Datos de proceso no es array', [
                    'tipo' => $tipoProceso,
                    'tipo_datos' => gettype($procesoData),
                ]);
                continue;
            }

            \Log::info('[PedidoWebService] 🔍 Procesando tipo: ' . $tipoProceso, [
                'estructura_procesar' => array_keys($procesoData),
                'tiene_datos_key' => isset($procesoData['datos']) ? 'SÍ' : 'NO',
                'tiene_tallas_en_datos' => isset($procesoData['datos']['tallas']) ? 'SÍ' : 'NO',
                'tiene_tallas_directo' => isset($procesoData['tallas']) ? 'SÍ' : 'NO',
            ]);

            // Extraer datos del proceso - AHORA PRIMERO INTENTA DIRECTO, LUEGO EN 'datos'
            $datosProceso = $procesoData['datos'] ?? $procesoData;
            
            // Validar que datosProceso sea array
            if (!is_array($datosProceso)) {
                Log::warning('[PedidoWebService] datosProceso no es array', [
                    'tipo' => $tipoProceso,
                    'tipo_datos' => gettype($datosProceso),
                ]);
                continue;
            }
            
            // Obtener tipo_proceso_id
            $tipoProcesoId = $this->obtenerTipoProcesoId($tipoProceso);
            if (!$tipoProcesoId) {
                Log::warning('[PedidoWebService] Tipo de proceso no encontrado', [
                    'tipo' => $tipoProceso,
                ]);
                continue;
            }

            //  EXTRACCIÓN ROBUSTA DE UBICACIONES Y OBSERVACIONES
            // Buscar en múltiples niveles de anidación
            $ubicaciones = $datosProceso['ubicaciones'] ?? $procesoData['ubicaciones'] ?? [];
            $observaciones = $datosProceso['observaciones'] ?? $procesoData['observaciones'] ?? null;
            
            // Validar que ubicaciones sea array
            if (!is_array($ubicaciones)) {
                $ubicaciones = is_string($ubicaciones) ? [$ubicaciones] : [];
            }
            
            // Limpiar string de observaciones
            if (is_string($observaciones)) {
                $observaciones = trim($observaciones);
                $observaciones = empty($observaciones) ? null : $observaciones;
            }

            // Obtener UID del proceso (IMPORTANTE para mapeo posterior de imágenes)
            $procesoUID = $procesoData['uid'] ?? $datosProceso['uid'] ?? null;

            // ASEGURAR que el UID se incluya en $datosProceso para guardarlo
            if ($procesoUID && !isset($datosProceso['uid'])) {
                $datosProceso['uid'] = $procesoUID;
            }

            Log::debug('[PedidoWebService] Creando proceso', [
                'tipo' => $tipoProceso,
                'uid' => $procesoUID,
                'uid_en_procesoData' => isset($procesoData['uid']),
                'uid_en_datosProceso_antes' => isset($datosProceso['uid']),
                'ubicaciones_raw' => $ubicaciones,
                'observaciones_raw' => $observaciones,
                'tallas_count' => isset($datosProceso['tallas']) ? count($datosProceso['tallas']) : 0,
                'imagenes_count' => isset($datosProceso['imagenes']) ? count($datosProceso['imagenes']) : 0,
            ]);

            // VERIFICAR si el proceso ya existe para evitar duplicados
            $procesoExistente = PedidosProcesosPrendaDetalle::where('prenda_pedido_id', $prenda->id)
                ->where('tipo_proceso_id', $tipoProcesoId)
                ->first();

            if ($procesoExistente) {
                Log::warning('[PedidoWebService] Proceso ya existe, eliminando el anterior', [
                    'prenda_pedido_id' => $prenda->id,
                    'tipo_proceso_id' => $tipoProcesoId,
                    'proceso_id' => $procesoExistente->id,
                ]);
                $procesoExistente->delete();
            }

            // CREAR CON DATOS EXTRACTADOS Y VALIDADOS
            $procesoPrenda = PedidosProcesosPrendaDetalle::create([
                'prenda_pedido_id' => $prenda->id,
                'tipo_proceso_id' => $tipoProcesoId,
                'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : json_encode([]),
                'observaciones' => $observaciones,
                'datos_adicionales' => json_encode($datosProceso),
                'estado' => 'PENDIENTE',
            ]);

            // Recargar para que Eloquent aplique los casts correctamente
            $procesoPrenda = $procesoPrenda->fresh();

            // Verificar que UID se guardó correctamente
            $datosGuardados = $procesoPrenda->datos_adicionales ?? [];
            \Log::info('[PedidoWebService] Verificación de UID guardado', [
                'proceso_id' => $procesoPrenda->id,
                'uid_solicitado' => $procesoUID,
                'uid_en_datos_adicionales' => $datosGuardados['uid'] ?? 'NO ENCONTRADO',
            ]);

            Log::info('[PedidoWebService] Proceso creado', [
                'proceso_id' => $procesoPrenda->id,
                'tipo' => $tipoProceso,
                'uid' => $procesoUID,
                'ubicaciones_guardadas' => $procesoPrenda->ubicaciones,
                'observaciones_guardadas' => $procesoPrenda->observaciones,
            ]);

            // Crear tallas del proceso
            if (isset($datosProceso['tallas']) && is_array($datosProceso['tallas'])) {
                \Log::info('[PedidoWebService]  Llamando crearTallasProceso', [
                    'proceso_id' => $procesoPrenda->id,
                    'tallas_estructura' => array_keys($datosProceso['tallas']),
                ]);
                $this->crearTallasProceso($procesoPrenda, $datosProceso['tallas']);
            } else {
                \Log::warning('[PedidoWebService]  NO HAY TALLAS para proceso ' . $tipoProceso, [
                    'tiene_tallas_key' => isset($datosProceso['tallas']) ? 'SÍ' : 'NO',
                    'es_array' => is_array($datosProceso['tallas'] ?? null) ? 'SÍ' : 'NO',
                ]);
            }

            // Crear imágenes del proceso usando ProcesoImagenService
            if (isset($datosProceso['imagenes']) && is_array($datosProceso['imagenes']) && !empty($datosProceso['imagenes'])) {
                \Log::info('[PedidoWebService] Guardando imágenes del proceso', [
                    'proceso_id' => $procesoPrenda->id,
                    'cantidad_imagenes' => count($datosProceso['imagenes']),
                ]);
                
                try {
                    $procesoImagenService = app(ProcesoImagenService::class);
                    $procesoImagenService->guardarImagenesProcesos(
                        $procesoPrenda->id,
                        $prenda->pedido_produccion_id,
                        $datosProceso['imagenes']
                    );
                } catch (\Exception $e) {
                    \Log::error('[PedidoWebService] Error guardando imágenes del proceso', [
                        'proceso_id' => $procesoPrenda->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        \Log::info('[PedidoWebService]  crearProcesosCompletos TERMINADA', [
            'prenda_id' => $prenda->id,
        ]);
    }

    /**
     * Crear tallas para un proceso
     * 
     * Estructura esperada:
     * {
     *   "dama": { "S": 10, "M": 20 },
     *   "caballero": { "L": 15, "XL": 5 },
     *   "sobremedida": { "CABALLERO": 100, "DAMA": 50 }
     * }
     */
    private function crearTallasProceso(PedidosProcesosPrendaDetalle $proceso, array $tallas): void
    {
        \Log::info('[PedidoWebService]  crearTallasProceso INICIADA', [
            'proceso_id' => $proceso->id,
            'tallas_estructura' => json_encode($tallas),
        ]);

        $tallasCreadas = 0;
        $generoMap = ['dama' => 'DAMA', 'caballero' => 'CABALLERO', 'unisex' => 'UNISEX'];

        foreach ($tallas as $generoBD => $tallasCant) {
            if (!is_array($tallasCant) || empty($tallasCant)) {
                continue;
            }

            // CASO ESPECIAL: SOBREMEDIDA
            if (strtolower($generoBD) === 'sobremedida') {
                // Estructura: {sobremedida: {CABALLERO: 100, DAMA: 50}}
                foreach ($tallasCant as $generoParaSobremedida => $cantidad) {
                    $cantidad = (int)$cantidad;
                    
                    if ($cantidad > 0) {
                        $generoEnum = strtoupper($generoParaSobremedida);
                        
                        PedidosProcesosPrendaTalla::create([
                            'proceso_prenda_detalle_id' => $proceso->id,
                            'genero' => $generoEnum,
                            'talla' => null,
                            'cantidad' => $cantidad,
                            'es_sobremedida' => true,
                        ]);
                        $tallasCreadas++;
                    }
                }
            } else {
                // CASO NORMAL: Género con tallas específicas
                $generoEnum = $generoMap[strtolower($generoBD)] ?? null;
                if (!$generoEnum) {
                    continue;
                }

                foreach ($tallasCant as $talla => $cantidad) {
                    $cantidad = (int)$cantidad;
                    
                    if ($cantidad > 0) {
                        PedidosProcesosPrendaTalla::create([
                            'proceso_prenda_detalle_id' => $proceso->id,
                            'genero' => $generoEnum,
                            'talla' => $talla,
                            'cantidad' => $cantidad,
                        ]);
                        $tallasCreadas++;
                    }
                }
            }
        }

        \Log::info('[PedidoWebService]  crearTallasProceso TERMINADA', [
            'proceso_id' => $proceso->id,
            'tallas_creadas' => $tallasCreadas,
        ]);
    }

    /**
     * Guardar imágenes de proceso usando sistema directo (sin relocalización)
     * 
     * REFACTORIZADO: Ya no usa relocalización
     * Si recibe UploadedFile, guarda directo con ImageUploadService
     * Si recibe strings (rutas ya guardadas), las guarda en BD
     * 
     * @param PedidosProcesosPrendaDetalle $proceso
     * @param array $imagenes Array de UploadedFile o strings (rutas)
     */
    private function guardarImagenesProceso(PedidosProcesosPrendaDetalle $proceso, array $imagenes): void
    {
        if (empty($imagenes)) {
            return;
        }

        try {
            $prenda = $proceso->prenda;
            if (!$prenda) {
                Log::warning('[PedidoWebService] No se pudo obtener prenda para guardar imágenes proceso');
                return;
            }

            $pedidoId = $prenda->pedido_produccion_id;
            $nombreProceso = $proceso->proceso->nombre ?? 'proceso';

            Log::debug('[PedidoWebService] guardarImagenesProceso: processing', [
                'proceso_id' => $proceso->id,
                'pedido_id' => $pedidoId,
                'imagenes_count' => count($imagenes),
            ]);

            // Procesar y guardar imágenes en directorio específico del pedido
            foreach ($imagenes as $imagen) {
                if ($imagen instanceof \Illuminate\Http\UploadedFile && $imagen->isValid()) {
                    $rutaGuardada = $imagen->store("pedido/{$pedidoId}/procesos/{$nombreProceso}", 'public');
                    
                    Log::info('[PedidoWebService] Imagen proceso guardada', [
                        'nombre' => $imagen->getClientOriginalName(),
                        'ruta' => $rutaGuardada,
                        'tamaño' => $imagen->getSize(),
                    ]);
                } elseif (is_string($imagen)) {
                    // Si es ya una ruta guardada, solo registrar
                    Log::debug('[PedidoWebService] Imagen proceso (ruta string)', ['ruta' => $imagen]);
                }
            }

            return;
            
            //  CÓDIGO OBSOLETO COMENTADO - NO USAR
            // Procesar cada imagen
            /*
            foreach ($imagenes as $index => $imagen) {
                // Si es UploadedFile, guardar directamente
                if ($imagen instanceof UploadedFile) {
                    $resultado = $this->imageUploadService->guardarImagenDirecta(
                        $imagen,
                        $pedidoId,
                        'procesos',
                        $nombreProceso, // subcarpeta: ESTAMPADO, BORDADO, etc.
                        null // filename autogenerado
                    );

                    PedidosProcessImagenes::create([
                        'proceso_prenda_detalle_id' => $proceso->id,
                        'ruta_original' => $resultado['original'],
                        'ruta_webp' => $resultado['webp'],
                        'orden' => $index + 1,
                        'es_principal' => $index === 0 ? 1 : 0,
                    ]);
                }
                // Si es string (ruta ya guardada), solo guardar en BD
                elseif (is_string($imagen)) {
                    $rutaWebp = str_replace(['.jpg', '.png', '.jpeg'], '.webp', $imagen);
                    
                    PedidosProcessImagenes::create([
                        'proceso_prenda_detalle_id' => $proceso->id,
                        'ruta_original' => $imagen,
                        'ruta_webp' => $rutaWebp,
                        'orden' => $index + 1,
                        'es_principal' => $index === 0 ? 1 : 0,
                    ]);
                }
            }

            Log::info('[PedidoWebService] Imágenes proceso guardadas directamente', [
                'proceso_id' => $proceso->id,
                'pedido_id' => $pedidoId,
                'nombre_proceso' => $nombreProceso,
                'cantidad' => count($imagenes),
            ]);
            */
        } catch (\Exception $e) {
            Log::error('[PedidoWebService] Error guardando imágenes proceso', [
                'proceso_id' => $proceso->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Guardar archivo en storage con formato centralizado
     * @deprecated Usar ImageUploadService::processAndSaveImage() en su lugar
     */
    private function guardarArchivo(UploadedFile $archivo, string $carpeta): string
    {
        $nombreArchivo = time() . '_' . uniqid() . '.' . $archivo->getClientOriginalExtension();
        $tempUuid = \Illuminate\Support\Str::uuid()->toString();
        
        // NUEVO: Formato centralizado temp/{uuid}/{carpeta}/
        $ruta = $archivo->storeAs("temp/{$tempUuid}/{$carpeta}", $nombreArchivo, self::STORAGE_DISK);

        Log::warning('[PedidoWebService] Usando método guardarArchivo() deprecado', [
            'carpeta' => $carpeta,
            'ruta' => $ruta,
            'sugerencia' => 'Usar ImageUploadService::processAndSaveImage() para WebP y mejor estructura',
        ]);

        return $ruta;
    }

    /**
     * Convertir imagen a WebP
     */
    private function convertirAWebp(string $ruta): string
    {
        // Por ahora retornar la misma ruta
        // En producción, usar intervención image o similar
        return str_replace(
            '.' . pathinfo($ruta, PATHINFO_EXTENSION),
            '.webp',
            $ruta
        );
    }

    /**
     * Obtener ID del tipo de proceso
     */
    private function obtenerTipoProcesoId(string $tipoProceso): ?int
    {
        $tipos = [
            'reflectivo' => 1,
            'bordado' => 2,
            'estampado' => 3,
            'dtf' => 4,
            'sublimado' => 5,
        ];

        return $tipos[strtolower($tipoProceso)] ?? null;
    }

    /**
     * Crear o obtener un tipo de prenda
     * Si no existe, crea una nueva entrada en la tabla tipos_prenda
     * 
     * @param string $nombrePrenda
     * @return TipoPrenda|null
     */
    private function crearOObtenerTipoPrenda(string $nombrePrenda): ?TipoPrenda
    {
        try {
            $nombreUpper = strtoupper(trim($nombrePrenda));
            
            // Buscar la prenda existente
            $tipoPrenda = TipoPrenda::whereRaw('UPPER(nombre) = ?', [$nombreUpper])
                                    ->first();
            
            if ($tipoPrenda) {
                Log::info('[PedidoWebService] Tipo de prenda encontrado', [
                    'tipo_prenda_id' => $tipoPrenda->id,
                    'nombre' => $tipoPrenda->nombre
                ]);
                return $tipoPrenda;
            }
            
            // Crear nueva prenda si no existe
            $tipoPrenda = TipoPrenda::create([
                'nombre' => $nombreUpper,
                'codigo' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $nombrePrenda), 0, 10)),
                'descripcion' => "Prenda creada automáticamente desde pedido",
                'activo' => true,
                'palabras_clave' => []
            ]);
            
            Log::info('[PedidoWebService] Tipo de prenda creado', [
                'tipo_prenda_id' => $tipoPrenda->id,
                'nombre' => $tipoPrenda->nombre
            ]);
            
            return $tipoPrenda;
            
        } catch (\Exception $e) {
            Log::warning('[PedidoWebService] Error creando tipo de prenda', [
                'error' => $e->getMessage(),
                'nombre_prenda' => $nombrePrenda
            ]);
            
            // Si hay error, no fallar el flujo, solo loguear
            return null;
        }
    }
}
