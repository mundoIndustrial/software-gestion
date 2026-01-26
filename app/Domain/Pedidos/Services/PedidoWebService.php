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
use App\Application\Services\ImageUploadService;
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

    public function __construct(
        PrendaImagenService $prendaImagenService = null,
        TelaImagenService $telaImagenService = null,
        ProcesoImagenService $procesoImagenService = null,
        ImageUploadService $imageUploadService = null
    ) {
        $this->prendaImagenService = $prendaImagenService ?? app(PrendaImagenService::class);
        $this->telaImagenService = $telaImagenService ?? app(TelaImagenService::class);
        $this->procesoImagenService = $procesoImagenService ?? app(ProcesoImagenService::class);
        $this->imageUploadService = $imageUploadService ?? app(ImageUploadService::class);
    }

    /**
     * Crear pedido completo con todas sus prendas, procesos e imágenes
     */
    public function crearPedidoCompleto(array $datosValidados, int $asesorId): PedidoProduccion
    {
        return DB::transaction(function () use ($datosValidados, $asesorId) {
            // 1. Crear pedido base
            $pedido = $this->crearPedidoBase($datosValidados, $asesorId);

            Log::info('[PedidoWebService] Pedido base creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'area_guardada' => $pedido->area,
                'estado' => $pedido->estado,
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
            ]);

            return $pedido;
        });
    }

    /**
     * Crear pedido base
     */
    private function crearPedidoBase(array $datos, int $asesorId): PedidoProduccion
    {
        $numeroPedido = $this->generarNumeroPedido();

        //  EXTRAER ÁREA CON DEFAULT
        $area = $datos['area'] ?? $datos['estado_area'] ?? 'Creación Orden';
        if (is_string($area)) {
            $area = trim($area);
            $area = empty($area) ? 'Creación Orden' : $area;
        } else {
            $area = 'creacion de pedido';
        }

        return PedidoProduccion::create([
            'numero_pedido' => $numeroPedido,
            'cliente' => $datos['cliente'] ?? 'SIN NOMBRE',
            'asesor_id' => $asesorId,
            'cliente_id' => $datos['cliente_id'] ?? null,
            'forma_de_pago' => $datos['forma_de_pago'] ?? 'CONTADO',
            'novedades' => $datos['descripcion'] ?? null,
            'estado' => 'Pendiente',
            'cantidad_total' => 0,
            'area' => $area,  // AHORA SE GUARDA EL ÁREA CORRECTAMENTE
        ]);
    }

    /**
     * Crear item (prenda) completo con tallas, variantes, procesos e imágenes
     */
    private function crearItemCompleto(PedidoProduccion $pedido, array $itemData, int $itemIndex): PrendaPedido
    {
        $prenda = PrendaPedido::create([
            'pedido_produccion_id' => $pedido->id,
            'nombre_prenda' => $itemData['nombre_prenda'] ?? 'SIN NOMBRE',
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
            \Log::info('[PedidoWebService] ⚙️ Creando procesos', [
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
        // cantidadTalla: { DAMA: {S: 10, M: 20}, CABALLERO: {...} }
        foreach ($cantidadTalla as $genero => $tallas) {
            if (is_array($tallas)) {
                foreach ($tallas as $talla => $cantidad) {
                    if ($cantidad > 0) {
                        PrendaPedidoTalla::create([
                            'prenda_pedido_id' => $prenda->id,
                            'genero' => $genero,
                            'talla' => $talla,
                            'cantidad' => $cantidad,
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
        PrendaVariantePed::create([
            'prenda_pedido_id' => $prenda->id,
            'tipo_manga_id' => $variaciones['tipo_manga_id'] ?? null,
            'tipo_broche_boton_id' => $variaciones['tipo_broche_boton_id'] ?? null,
            'manga_obs' => $variaciones['obs_manga'] ?? null,
            'broche_boton_obs' => $variaciones['obs_broche'] ?? null,
            'tiene_bolsillos' => $variaciones['tiene_bolsillos'] ?? 0,
            'bolsillos_obs' => $variaciones['obs_bolsillos'] ?? null,
        ]);

        Log::info('[PedidoWebService] Variantes creadas', [
            'prenda_id' => $prenda->id,
        ]);
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
            // Si tela_id y color_id ya están presentes, usarlos directamente
            if (isset($telaData['tela_id']) && isset($telaData['color_id'])) {
                $colorTela = PrendaPedidoColorTela::create([
                    'prenda_pedido_id' => $prenda->id,
                    'color_id' => $telaData['color_id'],
                    'tela_id' => $telaData['tela_id'],
                ]);
                $telasCreadasCount++;

                \Log::info('[PedidoWebService] Tela creada (directo)', [
                    'prenda_id' => $prenda->id,
                    'tela_id' => $telaData['tela_id'],
                    'color_id' => $telaData['color_id'],
                    'color_tela_id' => $colorTela->id,
                ]);

                //  DESHABILITADO: Imágenes se procesan en CrearPedidoEditableController::procesarYAsignarImagenes()
                // Guardar imágenes de tela si existen
                // if (isset($telaData['imagenes']) && is_array($telaData['imagenes'])) {
                //     $this->guardarImagenesTela($colorTela, $telaData['imagenes'], $prenda->pedido_produccion_id);
                // }
            } else {
                // Buscar por nombre/referencia si solo hay nombres
                $telaId = null;
                $colorId = null;

                if (isset($telaData['tela'])) {
                    // Buscar tela por nombre o referencia
                    $telaModel = \App\Models\TelaPrenda::where('nombre', $telaData['tela'])
                        ->orWhere('referencia', $telaData['referencia'] ?? null)
                        ->first();
                    $telaId = $telaModel->id ?? null;
                }

                if (isset($telaData['color'])) {
                    // Buscar color por nombre
                    $colorModel = \App\Models\ColorPrenda::where('nombre', $telaData['color'])->first();
                    $colorId = $colorModel->id ?? null;
                }

                if ($telaId && $colorId) {
                    $colorTela = PrendaPedidoColorTela::create([
                        'prenda_pedido_id' => $prenda->id,
                        'color_id' => $colorId,
                        'tela_id' => $telaId,
                    ]);
                    $telasCreadasCount++;

                    \Log::info('[PedidoWebService] Tela creada (búsqueda)', [
                        'prenda_id' => $prenda->id,
                        'tela_nombre' => $telaData['tela'] ?? 'N/A',
                        'color_nombre' => $telaData['color'] ?? 'N/A',
                        'tela_id' => $telaId,
                        'color_id' => $colorId,
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
        \Log::info('[PedidoWebService] ⚙️ crearProcesosCompletos INICIADA', [
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

            Log::debug('[PedidoWebService] Creando proceso', [
                'tipo' => $tipoProceso,
                'ubicaciones_raw' => $ubicaciones,
                'observaciones_raw' => $observaciones,
                'tallas_count' => isset($datosProceso['tallas']) ? count($datosProceso['tallas']) : 0,
                'imagenes_count' => isset($datosProceso['imagenes']) ? count($datosProceso['imagenes']) : 0,
            ]);

            // CREAR CON DATOS EXTRACTADOS Y VALIDADOS
            $procesoPrenda = PedidosProcesosPrendaDetalle::create([
                'prenda_pedido_id' => $prenda->id,
                'tipo_proceso_id' => $tipoProcesoId,
                'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : json_encode([]),
                'observaciones' => $observaciones,
                'datos_adicionales' => json_encode($datosProceso),
                'estado' => 'PENDIENTE',
            ]);

            Log::info('[PedidoWebService] Proceso creado', [
                'proceso_id' => $procesoPrenda->id,
                'tipo' => $tipoProceso,
                'ubicaciones_guardadas' => $procesoPrenda->ubicaciones,
                'observaciones_guardadas' => $procesoPrenda->observaciones,
            ]);

            // Crear tallas del proceso
            if (isset($datosProceso['tallas']) && is_array($datosProceso['tallas'])) {
                \Log::info('[PedidoWebService] 📏 Llamando crearTallasProceso', [
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

            // Crear imágenes del proceso
            if (isset($datosProceso['imagenes']) && is_array($datosProceso['imagenes'])) {
                //  NO LLAMAR: Imágenes ya procesadas en controller
                // $this->guardarImagenesProceso($procesoPrenda, $datosProceso['imagenes']);
            }
        }

        \Log::info('[PedidoWebService] ⚙️ crearProcesosCompletos TERMINADA', [
            'prenda_id' => $prenda->id,
        ]);
    }

    /**
     * Crear tallas para un proceso
     */
    private function crearTallasProceso(PedidosProcesosPrendaDetalle $proceso, array $tallas): void
    {
        \Log::info('[PedidoWebService] 📏 crearTallasProceso INICIADA', [
            'proceso_id' => $proceso->id,
            'tallas_estructura' => json_encode($tallas),
        ]);

        $tallasCreadas = 0;

        foreach ($tallas as $genero => $tallasCant) {
            if (is_array($tallasCant)) {
                foreach ($tallasCant as $talla => $cantidad) {
                    if ($cantidad > 0) {
                        PedidosProcesosPrendaTalla::create([
                            'proceso_prenda_detalle_id' => $proceso->id,
                            'genero' => $genero,
                            'talla' => $talla,
                            'cantidad' => $cantidad,
                        ]);
                        $tallasCreadas++;
                    }
                }
            }
        }

        \Log::info('[PedidoWebService] 📏 crearTallasProceso TERMINADA', [
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
        ];

        return $tipos[strtolower($tipoProceso)] ?? null;
    }

    /**
     * Generar número de pedido único
     */
    private function generarNumeroPedido(): int
    {
        $ultimoPedido = PedidoProduccion::max('numero_pedido') ?? 100000;
        return $ultimoPedido + 1;
    }
}
