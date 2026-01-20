<?php

namespace App\Domain\PedidoProduccion\Services;

use App\Models\Cotizacion;
use App\Domain\Shared\DomainEventDispatcher;
use App\Domain\PedidoProduccion\Events\LogoPedidoCreado;
use App\Domain\PedidoProduccion\Aggregates\LogoPedidoAggregate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de dominio para lógica de negocio de Logo Pedidos
 * 
 * Responsabilidades:
 * - Crear logo pedidos desde cotizaciones
 * - Guardar datos completos de logo pedidos
 * - Procesar fotos y información técnica
 * - Emitir eventos de dominio
 * - Encapsular toda la lógica del método guardarLogoPedido() del controller
 * 
 * Encapsula la lógica que estaba dispersa en PedidosProduccionController::guardarLogoPedido()
 * (~200 líneas de lógica de BD y cálculos)
 */
class LogoPedidoService
{
    public function __construct(
        private NumeracionService $numeracionService,
        private DomainEventDispatcher $eventDispatcher,
    ) {}

    /**
     * Crear logo pedido desde cotización
     */
    public function crearDesdeCotizacion(Cotizacion $cotizacion): int
    {
        return DB::transaction(function () use ($cotizacion) {
            // Obtener logo_cotizacion_id
            $logoCotizacionId = DB::table('logo_cotizaciones')
                ->where('cotizacion_id', $cotizacion->id)
                ->value('id');
            
            if (!$logoCotizacionId) {
                throw new \RuntimeException('No se encontró logo_cotizacion para esta cotización');
            }

            // Generar número LOGO
            $numeroLogoPedido = $this->numeracionService->generarNumeroLogoPedido();

            // Obtener datos del logo_cotizacion
            $logoCotizacion = \App\Models\LogoCotizacion::find($logoCotizacionId);
            
            // Preparar datos para inserción
            $seccionesJson = $logoCotizacion->ubicaciones 
                ? (is_string($logoCotizacion->ubicaciones) 
                    ? $logoCotizacion->ubicaciones 
                    : json_encode($logoCotizacion->ubicaciones))
                : json_encode([]);
            
            $observacionesJson = $logoCotizacion->observaciones
                ? (is_string($logoCotizacion->observaciones)
                    ? $logoCotizacion->observaciones
                    : json_encode($logoCotizacion->observaciones))
                : json_encode([]);

            // Extraer forma de pago
            $formaPago = '';
            if (is_array($cotizacion->especificaciones) && isset($cotizacion->especificaciones['forma_pago'])) {
                $formaPagoArray = $cotizacion->especificaciones['forma_pago'];
                if (is_array($formaPagoArray) && count($formaPagoArray) > 0) {
                    $formaPago = $formaPagoArray[0]['valor'] ?? '';
                }
            }

            // Crear registro en logo_pedidos
            $logoPedidoId = DB::table('logo_pedidos')->insertGetId([
                'pedido_id' => null,
                'logo_cotizacion_id' => $logoCotizacionId,
                'numero_pedido' => $numeroLogoPedido,
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero,
                'cliente' => $cotizacion->cliente->nombre ?? 'Sin nombre',
                'asesora' => Auth::user()?->name,
                'forma_de_pago' => $formaPago,
                'secciones' => $seccionesJson,
                'observaciones' => $observacionesJson,
                'estado' => 'PENDIENTE_SUPERVISOR',
                'fecha_de_creacion_de_orden' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Copiar prendas técnicas
            $this->copiarPrendasTecnicas($logoCotizacionId, $logoPedidoId);

            \Log::info(' Logo pedido creado exitosamente', [
                'logo_pedido_id' => $logoPedidoId,
                'numero_pedido' => $numeroLogoPedido,
                'cotizacion_id' => $cotizacion->id
            ]);

            return $logoPedidoId;
        });
    }

    /**
     * Guardar logo pedido desde request
     */
    public function guardarDesdeRequest(array $data): int
    {
        return DB::transaction(function () use ($data) {
            $pedidoId = $data['pedido_id'] ?? null;
            $cotizacionId = $data['cotizacion_id'] ?? null;
            $logoCotizacionId = $data['logo_cotizacion_id'] ?? null;

            // Buscar o crear logo_pedido
            $logoPedidoExistente = DB::table('logo_pedidos')
                ->where('pedido_id', $pedidoId)
                ->orWhere('cotizacion_id', $cotizacionId)
                ->first();

            if (!$logoPedidoExistente) {
                // Crear nuevo logo_pedido
                $numeroLogoPedido = $this->numeracionService->generarNumeroLogoPedido();
                
                $logoPedidoId = DB::table('logo_pedidos')->insertGetId([
                    'pedido_id' => $pedidoId,
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'numero_pedido' => $numeroLogoPedido,
                    'cotizacion_id' => $cotizacionId,
                    'cliente' => $data['cliente'] ?? '',
                    'asesora' => $data['asesora'] ?? Auth::user()->name,
                    'forma_de_pago' => $data['forma_de_pago'] ?? '',
                    'secciones' => json_encode($data['secciones'] ?? []),
                    'observaciones' => json_encode($data['observaciones'] ?? []),
                    'estado' => 'PENDIENTE_SUPERVISOR',
                    'fecha_de_creacion_de_orden' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Actualizar existente
                $logoPedidoId = $logoPedidoExistente->id;
                
                DB::table('logo_pedidos')
                    ->where('id', $logoPedidoId)
                    ->update([
                        'secciones' => json_encode($data['secciones'] ?? []),
                        'observaciones' => json_encode($data['observaciones'] ?? []),
                        'updated_at' => now(),
                    ]);
            }

            // Actualizar prendas técnicas
            if (!empty($data['prendas'])) {
                $this->actualizarPrendasTecnicas($logoPedidoId, $data['prendas']);
            }

            return $logoPedidoId;
        });
    }

    /**
     * Copiar prendas técnicas de logo_cotizacion a logo_pedido
     */
    private function copiarPrendasTecnicas(int $logoCotizacionId, int $logoPedidoId): void
    {
        $prendasTecnicas = DB::table('prendas_tecnicas_logo')
            ->where('logo_cotizacion_id', $logoCotizacionId)
            ->get();

        foreach ($prendasTecnicas as $prenda) {
            DB::table('prendas_tecnicas_logo_pedido')->insert([
                'logo_pedido_id' => $logoPedidoId,
                'nombre_prenda' => $prenda->nombre_prenda,
                'tipo_logo_id' => $prenda->tipo_logo_id,
                'cantidad_total' => $prenda->cantidad_total,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Actualizar prendas técnicas del logo pedido
     */
    private function actualizarPrendasTecnicas(int $logoPedidoId, array $prendas): void
    {
        // Eliminar prendas existentes
        DB::table('prendas_tecnicas_logo_pedido')
            ->where('logo_pedido_id', $logoPedidoId)
            ->delete();

        // Insertar nuevas prendas
        foreach ($prendas as $prenda) {
            DB::table('prendas_tecnicas_logo_pedido')->insert([
                'logo_pedido_id' => $logoPedidoId,
                'nombre_prenda' => $prenda['nombre_prenda'] ?? '',
                'tipo_logo_id' => $prenda['tipo_logo_id'] ?? null,
                'cantidad_total' => $prenda['cantidad_total'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * ===== NUEVO MÉTODO =====
     * Guardar datos completos de logo pedido (EXTRAE LÓGICA DEL CONTROLLER)
     * 
     * Encapsula la lógica que estaba en controller::guardarLogoPedido() (línea 240-430)
     * - Búsqueda de logo_pedido existente por múltiples criterios
     * - Creación si no existe
     * - Actualización de datos complejos
     * - Procesamiento de fotos
     * - Obtención de datos completos
     * 
     * @param int $pedidoId ID del pedido (puede ser logo_pedido ID o pedido_produccion ID)
     * @param string $logoCotizacionId ID de la cotización de logo
     * @param int $cantidad Cantidad total (suma de tallas)
     * @param int|null $cotizacionId ID de cotización (opcional)
     * @param array $datos Datos adicionales (cliente, asesora, forma_pago, descripción, etc)
     * @return array Resultado con datos guardados
     * @throws \Exception
     */
    public function guardarDatos(
        int $pedidoId,
        string $logoCotizacionId,
        int $cantidad,
        ?int $cotizacionId,
        array $datos = []
    ): array {
        try {
            DB::beginTransaction();

            Log::info(' [LogoPedidoService::guardarDatos] Iniciando', [
                'pedido_id' => $pedidoId,
                'logo_cotizacion_id' => $logoCotizacionId,
                'cantidad' => $cantidad,
                'cotizacion_id' => $cotizacionId,
            ]);

            // Extraer datos de entrada
            $numeroCotizacion = $datos['numero_cotizacion'] ?? null;
            $cliente = $datos['cliente'] ?? null;
            $asesora = $datos['asesora'] ?? Auth::user()?->name;
            $formaPago = $datos['forma_de_pago'] ?? 'Por definir';

            // Si cliente no viene, intentar obtenerlo de cotización
            if (!$cliente && $cotizacionId) {
                $cotizacion = DB::table('cotizaciones')
                    ->where('id', $cotizacionId)
                    ->select('id', 'numero', 'cliente_id')
                    ->first();

                if ($cotizacion) {
                    $numeroCotizacion = $cotizacion->numero;
                    $clienteObj = DB::table('clientes')->where('id', $cotizacion->cliente_id)->first();
                    $cliente = $clienteObj?->nombre ?? 'Sin nombre';

                    Log::info(' [LogoPedidoService] Cliente obtenido de cotización', [
                        'cliente' => $cliente,
                    ]);
                }
            }

            // ===== LÓGICA DE BÚSQUEDA/CREACIÓN (ANTES LÍNEA 317-340 DEL CONTROLLER) =====
            // Buscar logo_pedido existente por ID primaria o FK
            $logoPedidoExistente = null;
            if (is_numeric($pedidoId)) {
                $logoPedidoExistente = DB::table('logo_pedidos')->find($pedidoId);
                if (!$logoPedidoExistente) {
                    $logoPedidoExistente = DB::table('logo_pedidos')
                        ->where('pedido_id', $pedidoId)
                        ->first();
                }
            }

            Log::info(' [LogoPedidoService] Búsqueda completada', [
                'encontrado' => $logoPedidoExistente ? 'SÍ' : 'NO',
            ]);

            if (!$logoPedidoExistente) {
                // ===== CREAR NUEVO (ANTES LÍNEA 343-390 DEL CONTROLLER) =====
                $numeroLogoPedido = $this->numeracionService->generarNumeroLogoPedido();

                $logoPedidoId = DB::table('logo_pedidos')->insertGetId([
                    'pedido_id' => $pedidoId,
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'numero_pedido' => $numeroLogoPedido,
                    'cotizacion_id' => $cotizacionId,
                    'numero_cotizacion' => $numeroCotizacion,
                    'cliente' => $cliente,
                    'asesora' => $asesora,
                    'forma_de_pago' => $formaPago,
                    'encargado_orden' => $asesora,
                    'fecha_de_creacion_de_orden' => now(),
                    'estado' => 'pendiente',
                    'descripcion' => $datos['descripcion'] ?? '',
                    'cantidad' => $cantidad,
                    'tecnicas' => json_encode($datos['tecnicas'] ?? []),
                    'observaciones_tecnicas' => $datos['observaciones_tecnicas'] ?? '',
                    'ubicaciones' => json_encode($datos['ubicaciones'] ?? []),
                    'observaciones' => $datos['observaciones'] ?? '',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                Log::info(' [LogoPedidoService] Logo pedido CREADO', [
                    'logo_pedido_id' => $logoPedidoId,
                    'numero_pedido' => $numeroLogoPedido,
                ]);

                $pedidoId = $logoPedidoId;
            } else {
                // ===== ACTUALIZAR EXISTENTE (ANTES LÍNEA 391-415 DEL CONTROLLER) =====
                $logoPedidoId = $logoPedidoExistente->id;

                $updateData = [
                    'logo_cotizacion_id' => $logoCotizacionId,
                    'descripcion' => $datos['descripcion'] ?? '',
                    'cantidad' => $cantidad,
                    'tecnicas' => json_encode($datos['tecnicas'] ?? []),
                    'observaciones_tecnicas' => $datos['observaciones_tecnicas'] ?? '',
                    'ubicaciones' => json_encode($datos['ubicaciones'] ?? []),
                    'observaciones' => $datos['observaciones'] ?? '',
                    'updated_at' => now(),
                ];

                if ($cotizacionId) {
                    $updateData['cotizacion_id'] = $cotizacionId;
                }
                if ($numeroCotizacion) {
                    $updateData['numero_cotizacion'] = $numeroCotizacion;
                }

                $updated = DB::table('logo_pedidos')
                    ->where('id', $logoPedidoId)
                    ->update($updateData);

                if (!$updated) {
                    throw new \Exception("No se encontró logo_pedido con ID: $logoPedidoId");
                }

                Log::info(' [LogoPedidoService] Logo pedido ACTUALIZADO', [
                    'logo_pedido_id' => $logoPedidoId,
                ]);
            }

            // ===== PROCESAR FOTOS (ANTES LÍNEA 420-432 DEL CONTROLLER) =====
            $fotos = $datos['fotos'] ?? [];
            if (!empty($fotos)) {
                foreach ($fotos as $index => $fotoId) {
                    DB::table('logo_pedido_fotos')->insertOrIgnore([
                        'logo_pedido_id' => $logoPedidoId,
                        'logo_foto_cotizacion_id' => $fotoId,
                        'orden' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                Log::info(' [LogoPedidoService] Fotos agregadas', [
                    'total_fotos' => count($fotos),
                ]);
            }

            DB::commit();

            // ===== OBTENER DATOS ACTUALIZADOS (ANTES LÍNEA 435-450 DEL CONTROLLER) =====
            $logoPedido = DB::table('logo_pedidos')->find($logoPedidoId);

            // Si es COMBINADA (tiene pedido_id), obtener también datos del pedido de prendas
            $pedidoPrendas = null;
            if ($logoPedido->pedido_id) {
                $pedidoPrendas = DB::table('pedidos_produccion')
                    ->where('id', $logoPedido->pedido_id)
                    ->select('id', 'numero_pedido')
                    ->first();
            }

            Log::info(' [LogoPedidoService::guardarDatos] Completado', [
                'logo_pedido_id' => $logoPedidoId,
                'cantidad' => $cantidad,
            ]);

            // ===== EMITIR EVENTO DE DOMINIO =====
            $event = new LogoPedidoCreado(
                pedidoId: $logoPedido->pedido_id ?? $logoPedidoId,
                logoPedidoId: $logoPedidoId,
                logoCotizacionId: $logoPedido->logo_cotizacion_id,
                cantidad: $cantidad,
                cotizacionId: $logoPedido->cotizacion_id,
            );
            $this->eventDispatcher->dispatch($event);
            Log::info(' Evento LogoPedidoCreado emitido', [
                'evento' => $event->getEventName(),
                'logo_pedido_id' => $logoPedidoId,
            ]);

            return [
                'success' => true,
                'message' => 'LOGO Pedido guardado correctamente',
                'logo_pedido' => $logoPedido,
                'pedido_produccion' => $pedidoPrendas,
                'numero_pedido_produccion' => $pedidoPrendas?->numero_pedido,
                'numero_pedido_logo' => $logoPedido->numero_pedido
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error(' [LogoPedidoService::guardarDatos] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
