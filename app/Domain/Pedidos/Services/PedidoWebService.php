<?php

namespace App\Domain\Pedidos\Services;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaPedidoTalla;
use App\Models\PrendaVariantePed;
use App\Models\PrendaPedidoColorTela;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidosProcesosPrendaTalla;
use App\Models\PedidoEpp;
use App\Models\TipoPrenda;
use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use App\Application\Services\ImageUploadService;
use App\Domain\Pedidos\Services\ProcesoImagenService;
use App\Domain\Pedidos\Services\PedidoSequenceService;
use App\Domain\Pedidos\Services\ImagenRelocalizadorService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    private ImagenRelocalizadorService $imagenRelocalizadorService;

    public function __construct(
        PrendaImagenService $prendaImagenService = null,
        TelaImagenService $telaImagenService = null,
        ProcesoImagenService $procesoImagenService = null,
        ImageUploadService $imageUploadService = null,
        PedidoSequenceService $pedidoSequenceService = null,
        ImagenRelocalizadorService $imagenRelocalizadorService = null
    ) {
        $this->prendaImagenService = $prendaImagenService ?? app(PrendaImagenService::class);
        $this->telaImagenService = $telaImagenService ?? app(TelaImagenService::class);
        $this->procesoImagenService = $procesoImagenService ?? app(ProcesoImagenService::class);
        $this->imageUploadService = $imageUploadService ?? app(ImageUploadService::class);
        $this->pedidoSequenceService = $pedidoSequenceService ?? app(PedidoSequenceService::class);
        $this->imagenRelocalizadorService = $imagenRelocalizadorService ?? app(ImagenRelocalizadorService::class);
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
                'numero_pedido' => $pedido->numero_pedido, // Asignado automáticamente
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

            // 3. Crear EPPs si existen
            if (isset($datosValidados['epps']) && is_array($datosValidados['epps'])) {
                foreach ($datosValidados['epps'] as $eppIndex => $eppData) {
                    $this->crearEppCompleto($pedido, $eppData, $eppIndex);
                }
            }

            Log::info('[PedidoWebService] Pedido completo creado', [
                'pedido_id' => $pedido->id,
                'cantidad_prendas' => $pedido->prendas()->count(),
                'cantidad_epps' => $pedido->epps()->count(),
                'area_final' => $pedido->area,
                'tiempo_total_ms' => round((microtime(true) - $tiempoInicio) * 1000, 2),
            ]);

            return $pedido;
        });
    }

    /**
     * Crear pedido borrador (sin número de pedido)
     * 
     * Similar a crearPedidoCompleto pero sin generar numero_pedido
     * Los borradores tienen estado 'Borrador' y pueden editarse antes de ser finalizados
     */
    public function crearPedidoBorrador(array $datosValidados, int $asesorId): PedidoProduccion
    {
        $tiempoInicio = microtime(true);
        
        return DB::transaction(function () use ($datosValidados, $asesorId, &$tiempoInicio) {
            // 1. Crear pedido base SIN número de pedido
            $tiempoInicioBase = microtime(true);
            $pedido = $this->crearPedidoBaseBorrador($datosValidados, $asesorId);
            $tiempoBase = (microtime(true) - $tiempoInicioBase) * 1000;
            
            Log::info('[PedidoWebService.crearPedidoBorrador]  Pedido borrador base creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido ?? 'NULL (Borrador)',
                'estado' => $pedido->estado,
                'tiempo_base_ms' => round($tiempoBase, 2),
            ]);

            // 2. Crear prendas con todas sus relaciones
            if (isset($datosValidados['items']) && is_array($datosValidados['items'])) {
                foreach ($datosValidados['items'] as $itemIndex => $itemData) {
                    $this->crearItemCompleto($pedido, $itemData, $itemIndex);
                }
            }

            // 3. Crear EPPs si existen
            if (isset($datosValidados['epps']) && is_array($datosValidados['epps'])) {
                foreach ($datosValidados['epps'] as $eppIndex => $eppData) {
                    $this->crearEppCompleto($pedido, $eppData, $eppIndex);
                }
            }

            Log::info('[PedidoWebService.crearPedidoBorrador]  Pedido borrador completo creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido ?? 'NULL (Borrador)',
                'cantidad_prendas' => $pedido->prendas()->count(),
                'cantidad_epps' => $pedido->epps()->count(),
                'estado' => $pedido->estado,
                'tiempo_total_ms' => round((microtime(true) - $tiempoInicio) * 1000, 2),
            ]);

            return $pedido;
        });
    }

    /**
     * Convertir un borrador existente en un pedido real.
     * Solo asigna número consecutivo y cambia el estado.
     * Las prendas e imágenes ya están correctamente guardadas en el borrador.
     */
    public function convertirBorradorEnPedido(PedidoProduccion $borrador, array $datosValidados, int $asesorId): PedidoProduccion
    {
        $tienePrendas = $borrador->prendas()->count() > 0;
        $tieneEpps    = $borrador->epps()->count() > 0;
        $estado = ($tieneEpps && !$tienePrendas) ? 'En Ejecución' : 'pendiente_cartera';

        $numeroPedido = $this->pedidoSequenceService->generarNumeroPedido();

        Log::info('[PedidoWebService] Convirtiendo borrador en pedido real', [
            'borrador_id'   => $borrador->id,
            'numero_pedido' => $numeroPedido,
            'estado'        => $estado,
        ]);

        $borrador->update([
            'numero_pedido'              => $numeroPedido,
            'estado'                     => $estado,
            'cliente'                    => $datosValidados['cliente']      ?? $borrador->cliente,
            'cliente_id'                 => $datosValidados['cliente_id']   ?? $borrador->cliente_id,
            'orden_compra'               => $datosValidados['orden_compra'] ?? $borrador->orden_compra,
            'forma_de_pago'              => $datosValidados['forma_de_pago'] ?? $borrador->forma_de_pago,
            'observaciones'              => $datosValidados['observaciones'] ?? $borrador->observaciones,
            'fecha_de_creacion_de_orden' => now(),
        ]);

        Log::info('[PedidoWebService] Borrador convertido exitosamente', [
            'pedido_id'     => $borrador->id,
            'numero_pedido' => $numeroPedido,
        ]);

        return $borrador->fresh();
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

        // Determinar estado según contenido del pedido
        $tienePrendas = isset($datos['items']) && is_array($datos['items']) && count($datos['items']) > 0;
        $tieneEpps = isset($datos['epps']) && is_array($datos['epps']) && count($datos['epps']) > 0;
        
        // Si solo tiene EPPs (sin prendas), colocar en "En Ejecución"
        // Si tiene prendas o ambos, colocar en "pendiente_cartera"
        $estado = ($tieneEpps && !$tienePrendas) ? 'En Ejecución' : 'pendiente_cartera';

        // Generar número de pedido consecutivo automáticamente
        $numeroPedido = $this->pedidoSequenceService->generarNumeroPedido();

        Log::info('[PedidoWebService] Creando pedido base con número consecutivo', [
            'numero_pedido' => $numeroPedido,
            'area' => $area,
            'estado' => $estado,
            'tiene_prendas' => $tienePrendas,
            'tiene_epps' => $tieneEpps,
            'motivo_estado' => ($tieneEpps && !$tienePrendas) ? 'Solo EPPs - En Ejecución' : 'Con prendas - pendiente_cartera',
        ]);

        $pedido = PedidoProduccion::create([
            'numero_pedido' => $numeroPedido, // Número asignado automáticamente
            'orden_compra' => $datos['orden_compra'] ?? null,
            'cliente' => $datos['cliente'] ?? 'SIN NOMBRE',
            'asesor_id' => $asesorId,
            'cliente_id' => $datos['cliente_id'] ?? null,
            'forma_de_pago' => $datos['forma_de_pago'] ?? 'CONTADO',
            'novedades' => $datos['descripcion'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'estado' => $estado,
            'cantidad_total' => 0,
            'area' => $area,  // AHORA SE GUARDA EL ÁREA CORRECTAMENTE
            'fecha_de_creacion_de_orden' => now(), // Fecha actual de creación de la orden
        ]);

        return $pedido;
    }

    /**
     * Crear pedido base para BORRADOR (sin número de pedido)
     * 
     * Similar a crearPedidoBase pero sin generar numero_pedido
     * Los borradores siempre van con estado 'Borrador'
     */
    private function crearPedidoBaseBorrador(array $datos, int $asesorId): PedidoProduccion
    {
        //  EXTRAER ÁREA CON DEFAULT
        $area = $datos['area'] ?? $datos['estado_area'] ?? 'Creación Orden';
        if (is_string($area)) {
            $area = trim($area);
            $area = empty($area) ? 'Creación Orden' : $area;
        } else {
            $area = 'creacion de pedido';
        }

        // Para borradores, siempre usar estado 'Borrador'
        $estado = 'Borrador';

        Log::info('[PedidoWebService.crearPedidoBaseBorrador]  Creando pedido borrador SIN número consecutivo', [
            'numero_pedido' => 'NULL (Borrador)',
            'area' => $area,
            'estado' => $estado,
        ]);

        $pedido = PedidoProduccion::create([
            'numero_pedido' => null, // NO generar número - es borrador
            'orden_compra' => $datos['orden_compra'] ?? null,
            'cliente' => $datos['cliente'] ?? 'SIN NOMBRE',
            'asesor_id' => $asesorId,
            'cliente_id' => $datos['cliente_id'] ?? null,
            'forma_de_pago' => $datos['forma_de_pago'] ?? 'CONTADO',
            'novedades' => $datos['descripcion'] ?? null,
            'observaciones' => $datos['observaciones'] ?? null,
            'estado' => $estado, // Estado 'Borrador'
            'cantidad_total' => 0,
            'area' => $area,
            'fecha_de_creacion_de_orden' => now(),
        ]);

        return $pedido;
    }

    /**
     * Crear item (prenda) completo con tallas, variantes, procesos e imágenes
     */
    /**
     * Agregar un item (prenda) a un pedido existente
     * Usado por ActualizarBorradorUseCase al añadir prendas en modo edición
     */
    public function agregarItemAPedido(PedidoProduccion $pedido, array $itemData, int $index): PrendaPedido
    {
        return $this->crearItemCompleto($pedido, $itemData, $index);
    }

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
            $asignacionesColores = $itemData['asignacionesColoresPorTalla'] ?? [];
            $flujoTallas = $itemData['flujo'] ?? 'simple';
            
            // DIAGNÓSTICO: Verificar asignaciones recibidas
            \Log::info('[PedidoWebService] 🔍 DIAGNÓSTICO - Asignaciones en crearTallasPrenda:', [
                'asignacionesColores_exists' => isset($itemData['asignacionesColoresPorTalla']),
                'asignacionesColores_count' => count($asignacionesColores),
                'asignacionesColores_keys' => array_keys($asignacionesColores),
                'asignacionesColores_data' => $asignacionesColores,
                'flujo' => $flujoTallas,
            ]);
            
            $this->crearTallasPrenda($prenda, $itemData['cantidad_talla'], $asignacionesColores, $flujoTallas);
        }

        // Crear variantes
        if (isset($itemData['variaciones']) && is_array($itemData['variaciones'])) {
            $this->crearVariantesPrenda($prenda, $itemData['variaciones']);
        }

        //  DEBUG: Verificar telas
        $tieneTelas = isset($itemData['telas']) && is_array($itemData['telas']) && count($itemData['telas']) > 0;
        $tieneTelasAntiguo = isset($itemData['prenda_pedido_colores_telas']) && is_array($itemData['prenda_pedido_colores_telas']) && count($itemData['prenda_pedido_colores_telas']) > 0;
        
        // SEPARACIÓN DE FLUJOS: Si el flujo es 'wizard', las telas ya están guardadas
        // dentro de prenda_pedido_talla_colores (vía asignacionesColoresPorTalla).
        // NO crear registros adicionales en prenda_pedido_colores_telas para evitar duplicación.
        $flujo = $itemData['flujo'] ?? 'simple';
        $esWizard = $flujo === 'wizard';
        
        if ($esWizard) {
            \Log::info('[PedidoWebService] 🔄 FLUJO WIZARD - Telas ya incluidas en asignaciones de colores por talla, saltando creación de prenda_pedido_colores_telas', [
                'prenda_id' => $prenda->id,
                'telas_omitidas' => $tieneTelas ? count($itemData['telas']) : ($tieneTelasAntiguo ? count($itemData['prenda_pedido_colores_telas']) : 0),
            ]);
        } elseif ($tieneTelas || $tieneTelasAntiguo) {
            \Log::info('[PedidoWebService] 🧵 Creando telas (flujo simple)', [
                'prenda_id' => $prenda->id,
                'telas_count' => $tieneTelas ? count($itemData['telas']) : ($tieneTelasAntiguo ? count($itemData['prenda_pedido_colores_telas']) : 0),
                'tipo' => $tieneTelasAntiguo ? 'ANTIGUO' : 'NUEVO',
            ]);
        } else {
            \Log::warning('[PedidoWebService]  SIN TELAS para prenda ' . $prenda->id);
        }

        // Crear colores y telas SOLO en flujo simple (en wizard ya están en prenda_pedido_talla_colores)
        if (!$esWizard) {
            if (isset($itemData['prenda_pedido_colores_telas']) && is_array($itemData['prenda_pedido_colores_telas'])) {
                $this->crearColoresTelas($prenda, $itemData['prenda_pedido_colores_telas']);
            } elseif (isset($itemData['telas']) && is_array($itemData['telas'])) {
                $this->crearTelasDesdeFormulario($prenda, $itemData['telas']);
            }
        }

        //  DESHABILITADO: Imágenes se procesan en CrearPedidoEditableController::procesarYAsignarImagenes()
        // Las imágenes YA NO se procesan aquí para evitar duplicación
        // if (isset($itemData['imagenes']) && is_array($itemData['imagenes'])) {
        //     $this->guardarImagenesPrenda($prenda, $itemData['imagenes']);
        // }

        //  DEBUG: Verificar procesos
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
            $asignacionesProcesos = $itemData['asignacionesColoresPorTalla'] ?? [];
            $flujoProcesos = $itemData['flujo'] ?? 'simple';
            $this->crearProcesosCompletos($prenda, $itemData['procesos'], $asignacionesProcesos, $flujoProcesos);
        }

        return $prenda;
    }

    /**
     * Crear tallas para una prenda
     * 
     * Ahora también procesa asignacionesColoresPorTalla para guardar tela y colores
     */
    private function crearTallasPrenda(PrendaPedido $prenda, array $cantidadTalla, array $asignacionesColores = [], string $flujo = 'simple'): void
    {
        // cantidadTalla puede ser:
        // 1. Normal: { DAMA: {S: 10, M: 20}, CABALLERO: {...} }
        // 2. Sobremedida: { SOBREMEDIDA: {CABALLERO: 100, DAMA: 50} }
        // 3. Mixta: { DAMA: {S: 10}, SOBREMEDIDA: {CABALLERO: 100} }
        
        // asignacionesColores estructura: { "dama-Letra-S": { genero, tela, colores: [...] }, ... }
        // flujo: 'wizard' → cantidad en prenda_pedido_tallas = null (las cantidades reales están en prenda_pedido_talla_colores)
        //        'simple' → cantidad se guarda normalmente
        $esWizard = $flujo === 'wizard';
        
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
                        // Construir posibles claves para buscar en asignacionesColores
                        $generoNormalizado = strtolower(trim($generoOEspecial));
                        $tallaNormalizada = trim((string) $talla);
                        
                        // Las claves pueden ser:
                        // 1. "genero-Letra-talla" (con tipo)
                        // 2. "genero-talla" (sin tipo)
                        // 3. Buscar por objeto con propiedades genero y talla
                        
                        $telaGuardar = null;
                        $claveEncontrada = null;
                        
                        // Método 1: Buscar por clave exacta con tipos comunes
                        $posiblesClaves = [
                            "{$generoNormalizado}-Letra-{$tallaNormalizada}",  // Con tipo Letra
                            "{$generoNormalizado}-Número-{$tallaNormalizada}",  // Con tipo Número
                            "{$generoNormalizado}-{$tallaNormalizada}",  // Sin tipo
                        ];
                        
                        $claveEncontrada = null;
                        foreach ($posiblesClaves as $clave) {
                            if (isset($asignacionesColores[$clave])) {
                                $claveEncontrada = $clave;
                                break;
                            }
                        }
                        
                        // Método 2: Si no encontró por clave, buscar por objeto con propiedades genero/talla
                        if (!$claveEncontrada) {
                            foreach ($asignacionesColores as $clave => $asignacion) {
                                if (is_array($asignacion) && 
                                    isset($asignacion['genero']) && 
                                    isset($asignacion['talla']) &&
                                    strtolower(trim($asignacion['genero'])) === $generoNormalizado &&
                                    trim((string)$asignacion['talla']) === $tallaNormalizada) {
                                    $claveEncontrada = $clave;
                                    break;
                                }
                            }
                        }
                        
                        // Si encontramos la asignación, extraer tela
                        if ($claveEncontrada && isset($asignacionesColores[$claveEncontrada])) {
                            $asignacion = $asignacionesColores[$claveEncontrada];
                            $telaGuardar = $asignacion['tela'] ?? null;
                            
                            Log::info('[PedidoWebService] Asignación de colores encontrada', [
                                'clave_buscada' => $claveEncontrada,
                                'tela' => $telaGuardar,
                                'colores' => $asignacion['colores'] ?? [],
                            ]);
                        }
                        
                        //  CREAR TALLA (colores guardan en tabla relacional)
                        // En flujo wizard: cantidad = null (las cantidades reales están en prenda_pedido_talla_colores)
                        // En flujo simple: cantidad = valor del formulario
                        $cantidadGuardar = ($esWizard && $claveEncontrada) ? null : (int)$cantidad;
                        
                        $prendaPedidoTalla = PrendaPedidoTalla::create([
                            'prenda_pedido_id' => $prenda->id,
                            'genero' => strtoupper($generoOEspecial),
                            'talla' => $talla,
                            'cantidad' => $cantidadGuardar,
                        ]);
                        
                        //  NUEVO: If we have color assignments, save them to the relational table
                        if ($claveEncontrada && isset($asignacionesColores[$claveEncontrada])) {
                            $asignacion = $asignacionesColores[$claveEncontrada];
                            
                            // Buscar tela_id en tabla telas_prenda usando TelaPrenda model
                            $telaId = null;
                            if ($telaGuardar) {
                                $telaRecord = TelaPrenda::where('nombre', $telaGuardar)->first();
                                $telaId = $telaRecord?->id;
                            }
                            
                            // Process each color in the assignment
                            if (isset($asignacion['colores']) && is_array($asignacion['colores'])) {
                                foreach ($asignacion['colores'] as $colorItem) {
                                    $colorNombre = $colorItem['nombre'] ?? null;
                                    $colorCantidad = $colorItem['cantidad'] ?? 1;
                                    
                                    if ($colorNombre) {
                                        // Buscar color_id en tabla colores_prenda usando ColorPrenda model
                                        $colorId = null;
                                        $colorRecord = ColorPrenda::where('nombre', $colorNombre)->first();
                                        $colorId = $colorRecord?->id;
                                        
                                        $colorObservaciones = $colorItem['observaciones'] ?? null;
                                        $colorReferencia = $colorItem['referencia'] ?? null;
                                        $colorImagenRuta = $colorItem['imagen_ruta'] ?? null;
                                        
                                        $prendaPedidoTalla->coloresAsignados()->create([
                                            'tela_id' => $telaId,
                                            'tela_nombre' => $telaGuardar,
                                            'color_id' => $colorId,
                                            'color_nombre' => $colorNombre,
                                            'cantidad' => (int)$colorCantidad,
                                            'observaciones' => $colorObservaciones,
                                            'referencia' => $colorReferencia,
                                            'imagen_ruta' => $colorImagenRuta,
                                        ]);
                                        
                                        Log::info('[PedidoWebService] Color guardado en tabla relacional', [
                                            'talla_id' => $prendaPedidoTalla->id,
                                            'tela_id' => $telaId,
                                            'tela_nombre' => $telaGuardar,
                                            'color_id' => $colorId,
                                            'color_nombre' => $colorNombre,
                                            'cantidad' => $colorCantidad,
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        Log::info('[PedidoWebService] Tallas creadas', [
            'prenda_id' => $prenda->id,
            'cantidad_generos' => count($cantidadTalla),
            'asignaciones_procesadas' => count($asignacionesColores),
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
            Log::error('[PedidoWebService]  Error creando variantes', [
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
            Log::info('[PedidoWebService]  Procesando tela individual', [
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
                    Log::error('[PedidoWebService]  Error procesando color/tela con servicio', [
                        'error' => $e->getMessage(),
                        'tela_data' => $telaData,
                    ]);
                    
                    //  FALLBACK: Buscar directamente en BD si el servicio falla
                    try {
                        $telaNombre = $telaData['tela'] ?? $telaData['tela_nombre'] ?? null;
                        if ($telaNombre && !$telaId) {
                            //  BÚSQUEDA EXACTA: Solo match perfecto
                            $tela = TelaPrenda::where('nombre', $telaNombre)->first();
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
                            //  BÚSQUEDA EXACTA: Solo match perfecto
                            $color = ColorPrenda::where('nombre', $colorNombre)->first();
                            if ($color) {
                                $colorId = $color->id;
                                Log::info('[PedidoWebService]  Color encontrado por fallback', [
                                    'color_nombre' => $colorNombre,
                                    'color_id' => $colorId,
                                ]);
                            }
                        }
                    } catch (\Exception $fallbackError) {
                        Log::error('[PedidoWebService]  Error en fallback también', [
                            'error' => $fallbackError->getMessage(),
                            'tela_data' => $telaData,
                        ]);
                    }
                }

                //  MEJORADO: Crear tela si solo hay nombre (sin requerir color) - PERO NO crear genérica si solo hay color
                $telaNombreGeneral = $telaData['nombre'] ?? $telaData['tela'] ?? $telaData['tela_nombre'] ?? null;
                
                Log::info('[PedidoWebService]  DIAGNÓSTICO - Verificando búsqueda de tela', [
                    'colorId' => $colorId,
                    'telaId' => $telaId,
                    'tiene_nombre_tela' => !empty($telaNombreGeneral),
                    'nombre_tela_valor' => $telaNombreGeneral ?? 'NO EXISTE',
                    'condicion_tela_sin_color' => (!$telaId && !empty($telaNombreGeneral))
                ]);
                
                // Si no hay telaId pero hay nombre de tela, buscar o crear la tela
                if (!$telaId && !empty($telaNombreGeneral)) {
                    //  BÚSQUEDA EXACTA: Solo match perfecto (no LIKE)
                    $telaExistente = TelaPrenda::where('nombre', $telaNombreGeneral)
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
                //  Si solo hay color sin tela → se crea el registro con tela_id = NULL
                //  Si hay tela con o sin color → se crea el registro
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
    private function crearProcesosCompletos(PrendaPedido $prenda, array $procesos, array $asignacionesColores = [], string $flujo = 'simple'): void
    {
        \Log::info('[PedidoWebService]  crearProcesosCompletos INICIADA', [
            'prenda_id' => $prenda->id,
            'procesos_count' => count($procesos),
            'procesos_keys' => array_keys($procesos),
            'procesos_raw' => json_encode($procesos, JSON_PRETTY_PRINT),  // ← DEBUG: Ver estructura exacta
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

            // FIX: Si la clave es numérica (array indexado), extraer el tipo real desde los datos
            if (is_numeric($tipoProceso)) {
                $tipoProceso = strtolower(trim($procesoData['tipo'] ?? $procesoData['nombre'] ?? (string)$tipoProceso));
            }

            \Log::info('[PedidoWebService]  Procesando tipo: ' . $tipoProceso, [
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

            //  NUEVO: Extraer modo_tallas y datos_extendidos
            $modoTallas = $datosProceso['modo_tallas'] ?? $procesoData['modo_tallas'] ?? 'generico';
            $datosExtendidos = $datosProceso['datos_extendidos'] ?? $procesoData['datos_extendidos'] ?? null;
            if (is_string($datosExtendidos)) {
                $datosExtendidos = json_decode($datosExtendidos, true);
            }

            \Log::info('[PedidoWebService]  EXTRACCIÓN DE MODO_TALLAS', [
                'tipo_proceso' => $tipoProceso,
                'modo_tallas_extraido' => $modoTallas,
                'datos_extendidos_presente' => !empty($datosExtendidos) ? 'SÍ' : 'NO',
                'datos_extendidos_keys' => !empty($datosExtendidos) ? array_keys($datosExtendidos) : [],
            ]);

            Log::debug('[PedidoWebService] Creando proceso', [
                'tipo' => $tipoProceso,
                'uid' => $procesoUID,
                'uid_en_procesoData' => isset($procesoData['uid']),
                'uid_en_datosProceso_antes' => isset($datosProceso['uid']),
                'ubicaciones_raw' => $ubicaciones,
                'observaciones_raw' => $observaciones,
                'modo_tallas' => $modoTallas,
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
                'modo_tallas' => $modoTallas,  //  Guardar modo_tallas
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
                'modo_tallas_guardado' => $modoTallas,
            ]);

            //  NUEVO: Manejar tallas según modo
            if ($modoTallas === 'por_tallas' && !empty($datosExtendidos)) {
                // MODO POR_TALLAS: Guardar ubicaciones/observaciones por talla desde datosExtendidos
                \Log::info('[PedidoWebService]  MODO POR_TALLAS: Guardando ubicaciones/observaciones por talla', [
                    'proceso_id' => $procesoPrenda->id,
                    'datos_extendidos_keys' => array_keys($datosExtendidos),
                ]);

                foreach ($datosExtendidos as $genero => $tallasDatos) {
                    if (!is_array($tallasDatos)) continue;
                    
                    // Agrupar tallas por talla real (sin color) para crearlas una sola vez
                    $tallasAgrupadas = [];
                    
                    foreach ($tallasDatos as $tallaKey => $tallaData) {
                        if (!is_array($tallaData)) continue;
                        
                        // Separar TALLA__COLOR si está en ese formato (ej: L__AZUL CIELO)
                        $partes = explode('__', (string)$tallaKey, 2);
                        $tallaReal = trim($partes[0]);
                        $colorNombre = isset($partes[1]) ? trim($partes[1]) : null;
                        
                        $cantidad = $datosProceso['tallas'][$genero][$tallaKey] ?? 0;
                        
                        // Agrupar datos por talla real
                        if (!isset($tallasAgrupadas[$tallaReal])) {
                            $tallasAgrupadas[$tallaReal] = [
                                'totalCantidad' => 0,
                                'colores' => [],
                            ];
                        }
                        $tallasAgrupadas[$tallaReal]['totalCantidad'] += (int)$cantidad;
                        
                        // Registrar color con sus datos específicos
                        $tallasAgrupadas[$tallaReal]['colores'][] = [
                            'nombre' => $colorNombre,
                            'cantidad' => (int)$cantidad,
                            'ubicaciones' => $tallaData['ubicaciones'] ?? [],
                            'observaciones' => $tallaData['observaciones'] ?? '',
                            'imagenes_talla' => [],  // Las imágenes se manejan aparte
                        ];
                    }
                    
                    // Crear tallas y luego los colores en tabla relacional
                    foreach ($tallasAgrupadas as $tallaReal => $tallaAgrupadaData) {
                        // Crear/actualizar entrada en pedidos_procesos_prenda_tallas
                        $tallaProceso = DB::table('pedidos_procesos_prenda_tallas')->updateOrInsert(
                            [
                                'proceso_prenda_detalle_id' => $procesoPrenda->id,
                                'genero' => strtoupper($genero),
                                'talla' => $tallaReal,
                            ],
                            [
                                'cantidad' => (int)$tallaAgrupadaData['totalCantidad'],
                                'updated_at' => now(),
                            ]
                        );
                        
                        // Obtener el ID de la talla que acabamos de crear/actualizar
                        $tallaProcesoId = DB::table('pedidos_procesos_prenda_tallas')
                            ->where('proceso_prenda_detalle_id', $procesoPrenda->id)
                            ->where('genero', strtoupper($genero))
                            ->where('talla', $tallaReal)
                            ->value('id');
                        
                        \Log::debug('[PedidoWebService] Talla guardada en modo por_tallas', [
                            'proceso_id' => $procesoPrenda->id,
                            'talla_proceso_id' => $tallaProcesoId,
                            'genero' => strtoupper($genero),
                            'talla_real' => $tallaReal,
                            'cantidad_total' => (int)$tallaAgrupadaData['totalCantidad'],
                            'colores_count' => count($tallaAgrupadaData['colores']),
                        ]);
                        
                        // Crear colores en pedidos_procesos_prenda_talla_colores
                        foreach ($tallaAgrupadaData['colores'] as $colorData) {
                            if (!empty($colorData['nombre'])) {  // Solo si hay color
                                DB::table('pedidos_procesos_prenda_talla_colores')->updateOrInsert(
                                    [
                                        'pedidos_procesos_prenda_talla_id' => $tallaProcesoId,
                                        'color_nombre' => $colorData['nombre'],
                                    ],
                                    [
                                        'tela_nombre' => null,  // Se puede llenar si está disponible
                                        'ubicaciones' => !empty($colorData['ubicaciones']) ? json_encode($colorData['ubicaciones']) : null,
                                        'observaciones' => $colorData['observaciones'] ?? null,
                                        'cantidad' => (int)$colorData['cantidad'],
                                        'updated_at' => now(),
                                    ]
                                );
                                
                                \Log::debug('[PedidoWebService] Color guardado en talla por_tallas', [
                                    'talla_proceso_id' => $tallaProcesoId,
                                    'color' => $colorData['nombre'],
                                    'cantidad' => (int)$colorData['cantidad'],
                                    'ubicaciones' => $colorData['ubicaciones'],
                                    'observaciones' => $colorData['observaciones'],
                                ]);
                            }
                        }
                    }
                }
            } elseif (isset($datosProceso['tallas']) && is_array($datosProceso['tallas'])) {
                // MODO PARA_TODAS: Usar lógica existente
                \Log::info('[PedidoWebService]  MODO PARA_TODAS: Llamando crearTallasProceso', [
                    'proceso_id' => $procesoPrenda->id,
                    'tallas_estructura' => array_keys($datosProceso['tallas']),
                    'flujo' => $flujo,
                ]);
                // Pasar datosExtendidos para guardar observaciones y ubicaciones por talla
                $datosExtendidos = $datosProceso['datos_extendidos'] ?? $datosProceso['datosExtendidos'] ?? [];
                $this->crearTallasProceso($procesoPrenda, $datosProceso['tallas'], $asignacionesColores, $flujo, $datosExtendidos);
            } else {
                \Log::warning('[PedidoWebService]  NO HAY TALLAS para proceso ' . $tipoProceso, [
                    'modo_tallas' => $modoTallas,
                    'tiene_datos_extendidos' => !empty($datosExtendidos) ? 'SÍ' : 'NO',
                    'tiene_tallas_key' => isset($datosProceso['tallas']) ? 'SÍ' : 'NO',
                    'es_array' => is_array($datosProceso['tallas'] ?? null) ? 'SÍ' : 'NO',
                ]);
            }

            // Crear imágenes del proceso usando ProcesoImagenService
            // Las imágenes por talla-color ya se guardan en el Controller (crearPedidoEditableController)
            // mediante procesarImagenesPorTalla(), así que aquí solo manejamos imágenes generales
            if (isset($datosProceso['imagenes']) && is_array($datosProceso['imagenes']) && !empty($datosProceso['imagenes'])) {
                \Log::info('[PedidoWebService] Guardando imágenes generales del proceso', [
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
    private function crearTallasProceso(PedidosProcesosPrendaDetalle $proceso, array $tallas, array $asignacionesColores = [], string $flujo = 'simple', array $datosExtendidos = []): void
    {
        \Log::info('[PedidoWebService]  crearTallasProceso INICIADA', [
            'proceso_id' => $proceso->id,
            'tallas_estructura' => json_encode($tallas),
            'flujo' => $flujo,
            'asignaciones_count' => count($asignacionesColores),
            'datos_extendidos_count' => count($datosExtendidos),
        ]);

        $tallasCreadas = 0;
        $esWizard = $flujo === 'wizard';
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

                // ====================================================================
                // Detectar si las claves vienen en formato TALLA__COLOR (ej: M__ARENA)
                // Si es así, agrupar por talla real y crear colores en tabla relacional
                // ====================================================================
                $tieneFormatoTallaColor = false;
                foreach (array_keys($tallasCant) as $key) {
                    if (str_contains((string)$key, '__')) {
                        $tieneFormatoTallaColor = true;
                        break;
                    }
                }

                if ($tieneFormatoTallaColor) {
                    // FLUJO CON COLORES EMBEBIDOS: TALLA__COLOR => cantidad
                    // Agrupar por talla real
                    $tallasAgrupadas = [];
                    foreach ($tallasCant as $tallaColorKey => $cantidad) {
                        $cantidad = (int)$cantidad;
                        if ($cantidad <= 0) continue;

                        $partes = explode('__', (string)$tallaColorKey, 2);
                        $tallaReal = trim($partes[0]);
                        $colorNombre = isset($partes[1]) ? trim($partes[1]) : null;

                        if (!isset($tallasAgrupadas[$tallaReal])) {
                            $tallasAgrupadas[$tallaReal] = [
                                'totalCantidad' => 0,
                                'colores' => [],
                            ];
                        }
                        $tallasAgrupadas[$tallaReal]['totalCantidad'] += $cantidad;
                        if ($colorNombre) {
                            $tallasAgrupadas[$tallaReal]['colores'][] = [
                                'nombre' => $colorNombre,
                                'cantidad' => $cantidad,
                            ];
                        }
                    }

                    // Obtener tela desde asignaciones si existe
                    $telaGuardar = null;
                    $generoNormalizado = strtolower(trim($generoBD));
                    foreach ($asignacionesColores as $clave => $asignacion) {
                        if (is_array($asignacion) && 
                            isset($asignacion['genero']) && 
                            strtolower(trim($asignacion['genero'])) === $generoNormalizado &&
                            isset($asignacion['tela'])) {
                            $telaGuardar = $asignacion['tela'];
                            break;
                        }
                    }

                    \Log::info('[PedidoWebService]  crearTallasProceso - Formato TALLA__COLOR detectado', [
                        'proceso_id' => $proceso->id,
                        'genero' => $generoEnum,
                        'tallas_agrupadas' => array_keys($tallasAgrupadas),
                        'tela_detectada' => $telaGuardar,
                    ]);

                    foreach ($tallasAgrupadas as $tallaReal => $data) {
                        $tallaProceso = PedidosProcesosPrendaTalla::create([
                            'proceso_prenda_detalle_id' => $proceso->id,
                            'genero' => $generoEnum,
                            'talla' => $tallaReal,
                            'cantidad' => $data['totalCantidad'], // guardar cantidad total aunque sea desglosada en colores
                        ]);

                        foreach ($data['colores'] as $colorItem) {
                            // Extraer observaciones y ubicaciones desde datosExtendidos
                            // Intentar dos formatos: TALLA__COLOR (específico) o solo TALLA (general)
                            $claveDataExtendidos = "{$tallaReal}__{$colorItem['nombre']}";
                            $observacionesColor = null;
                            $ubicacionesColor = null;
                            
                            if (!empty($datosExtendidos)) {
                                $generoKey = strtolower(trim($generoBD));
                                
                                // INTENTO 1: Buscar con formato TALLA__COLOR (específico por color)
                                if (isset($datosExtendidos[$generoKey][$claveDataExtendidos])) {
                                    $datosColor = $datosExtendidos[$generoKey][$claveDataExtendidos];
                                    $observacionesColor = $datosColor['observaciones'] ?? null;
                                    $ubicacionesColor = $datosColor['ubicaciones'] ?? null;
                                }
                                // INTENTO 2: Fallback a solo TALLA (modo general)
                                elseif (isset($datosExtendidos[$generoKey][$tallaReal])) {
                                    $datosColor = $datosExtendidos[$generoKey][$tallaReal];
                                    $observacionesColor = $datosColor['observaciones'] ?? null;
                                    $ubicacionesColor = $datosColor['ubicaciones'] ?? null;
                                }
                            }
                            
                            $colorCreado = $tallaProceso->coloresAsignados()->create([
                                'color_nombre' => $colorItem['nombre'],
                                'tela_nombre' => $telaGuardar,
                                'cantidad' => (int)$colorItem['cantidad'],
                                'observaciones' => $observacionesColor,
                                'ubicaciones' => !empty($ubicacionesColor) ? json_encode($ubicacionesColor) : null,
                            ]);
                            
                            \Log::debug('[PedidoWebService] Color con observaciones guardado', [
                                'color_id' => $colorCreado->id ?? 'sin_id',
                                'color_nombre' => $colorItem['nombre'],
                                'observaciones' => $observacionesColor,
                                'ubicaciones' => $ubicacionesColor,
                            ]);
                        }

                        \Log::info('[PedidoWebService]  Talla proceso creada con colores', [
                            'talla_id' => $tallaProceso->id,
                            'talla' => $tallaReal,
                            'genero' => $generoEnum,
                            'colores_count' => count($data['colores']),
                            'total_cantidad' => $data['totalCantidad'],
                        ]);

                        $tallasCreadas++;
                    }
                } else {
                    // FLUJO NORMAL: talla => cantidad (sin colores embebidos)
                    foreach ($tallasCant as $talla => $cantidad) {
                        $cantidad = (int)$cantidad;
                        
                        if ($cantidad > 0) {
                            // Buscar asignación de colores para esta talla
                            $generoNormalizado = strtolower(trim($generoBD));
                            $tallaNormalizada = trim((string) $talla);
                            
                            $claveEncontrada = null;
                            $posiblesClaves = [
                                "{$generoNormalizado}-Letra-{$tallaNormalizada}",
                                "{$generoNormalizado}-Número-{$tallaNormalizada}",
                                "{$generoNormalizado}-{$tallaNormalizada}",
                            ];
                            
                            foreach ($posiblesClaves as $clave) {
                                if (isset($asignacionesColores[$clave])) {
                                    $claveEncontrada = $clave;
                                    break;
                                }
                            }
                            
                            if (!$claveEncontrada) {
                                foreach ($asignacionesColores as $clave => $asignacion) {
                                    if (is_array($asignacion) && 
                                        isset($asignacion['genero']) && 
                                        isset($asignacion['talla']) &&
                                        strtolower(trim($asignacion['genero'])) === $generoNormalizado &&
                                        trim((string)$asignacion['talla']) === $tallaNormalizada) {
                                        $claveEncontrada = $clave;
                                        break;
                                    }
                                }
                            }
                            
                            // Extraer observaciones y ubicaciones del datosExtendidos
                            $observacionesTalla = null;
                            $ubicacionesTalla = null;
                            if (!empty($datosExtendidos)) {
                                $generoKey = strtolower(trim($generoBD));
                                if (isset($datosExtendidos[$generoKey][$talla])) {
                                    $datosTalla = $datosExtendidos[$generoKey][$talla];
                                    $observacionesTalla = $datosTalla['observaciones'] ?? null;
                                    $ubicacionesTalla = $datosTalla['ubicaciones'] ?? null;
                                }
                            }
                            
                            // Siempre guardar cantidad para cálculos,
                            // aunque el detalle de colores esté en coloresAsignados
                            $cantidadGuardar = $cantidad;
                            
                            $tallaProceso = PedidosProcesosPrendaTalla::create([
                                'proceso_prenda_detalle_id' => $proceso->id,
                                'genero' => $generoEnum,
                                'talla' => $talla,
                                'cantidad' => $cantidadGuardar,
                                'observaciones' => $observacionesTalla,
                                'ubicaciones' => !empty($ubicacionesTalla) ? json_encode($ubicacionesTalla) : null,
                            ]);
                            
                            // Crear colores asignados si hay asignación wizard
                            if ($claveEncontrada && isset($asignacionesColores[$claveEncontrada])) {
                                $asignacion = $asignacionesColores[$claveEncontrada];
                                $telaGuardar = $asignacion['tela'] ?? null;
                                
                                if (isset($asignacion['colores']) && is_array($asignacion['colores'])) {
                                    foreach ($asignacion['colores'] as $colorItem) {
                                        $colorNombre = $colorItem['nombre'] ?? null;
                                        $colorCantidad = $colorItem['cantidad'] ?? 1;
                                        
                                        if ($colorNombre) {
                                            // Intentar obtener observaciones y ubicaciones específicas del color
                                            $claveColorDataExtendidos = "{$talla}__{$colorNombre}";
                                            $observacionesColor = null;
                                            $ubicacionesColor = null;
                                            
                                            if (!empty($datosExtendidos)) {
                                                $generoKey = strtolower(trim($generoBD));
                                                
                                                // INTENTO 1: Buscar con formato TALLA__COLOR
                                                if (isset($datosExtendidos[$generoKey][$claveColorDataExtendidos])) {
                                                    $datosColor = $datosExtendidos[$generoKey][$claveColorDataExtendidos];
                                                    $observacionesColor = $datosColor['observaciones'] ?? null;
                                                    $ubicacionesColor = $datosColor['ubicaciones'] ?? null;
                                                }
                                                // INTENTO 2: Fallback a solo TALLA (si viene agrupado por talla)
                                                elseif (isset($datosExtendidos[$generoKey][$talla])) {
                                                    $datosTalla = $datosExtendidos[$generoKey][$talla];
                                                    $observacionesColor = $datosTalla['observaciones'] ?? null;
                                                    $ubicacionesColor = $datosTalla['ubicaciones'] ?? null;
                                                }
                                            }
                                            
                                            $colorCreado = $tallaProceso->coloresAsignados()->create([
                                                'color_nombre' => $colorNombre,
                                                'tela_nombre' => $telaGuardar,
                                                'cantidad' => (int)$colorCantidad,
                                                'observaciones' => $observacionesColor,
                                                'ubicaciones' => !empty($ubicacionesColor) ? json_encode($ubicacionesColor) : null,
                                            ]);
                                            
                                            \Log::debug('[PedidoWebService] Color con observaciones guardado (flujo normal)', [
                                                'color_id' => $colorCreado->id ?? 'sin_id',
                                                'color_nombre' => $colorNombre,
                                                'observaciones' => $observacionesColor,
                                                'ubicaciones' => $ubicacionesColor,
                                            ]);
                                        }
                                    }
                                }
                            }
                            
                            $tallasCreadas++;
                        }
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
                if ($imagen instanceof UploadedFile && $imagen->isValid()) {
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

    /**
     * Crear EPP completo con sus datos e imágenes
     */
    private function crearEppCompleto(PedidoProduccion $pedido, array $eppData, int $eppIndex): PedidoEpp
    {
        $nombreEpp = $eppData['nombre'] ?? 'SIN NOMBRE';
        $cantidad = $eppData['cantidad'] ?? 1;
        $observaciones = $eppData['observaciones'] ?? null;
        $eppId = $eppData['epp_id'] ?? null;

        Log::info('[PedidoWebService] Creando EPP', [
            'pedido_id' => $pedido->id,
            'nombre' => $nombreEpp,
            'cantidad' => $cantidad,
            'epp_id' => $eppId,
        ]);

        $epp = PedidoEpp::create([
            'pedido_produccion_id' => $pedido->id,
            'epp_id' => $eppId,
            'cantidad' => $cantidad,
            'observaciones' => $observaciones,
            'nombre' => $nombreEpp, // Guardar nombre también en pedido_epp para referencia
        ]);

        // TODO: Procesar imágenes del EPP si existen
        // Esto se puede implementar más adelante siguiendo el mismo patrón que las prendas

        Log::info('[PedidoWebService] EPP creado', [
            'epp_id' => $epp->id,
            'nombre' => $epp->nombre,
            'cantidad' => $epp->cantidad,
        ]);

        return $epp;
    }
}
