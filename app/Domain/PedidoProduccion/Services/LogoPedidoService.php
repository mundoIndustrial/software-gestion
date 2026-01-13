<?php

namespace App\Domain\PedidoProduccion\Services;

use App\Models\Cotizacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de dominio para lógica de negocio de Logo Pedidos
 * Responsabilidad: Crear y gestionar pedidos de tipo LOGO
 */
class LogoPedidoService
{
    public function __construct(
        private NumeracionService $numeracionService
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

            \Log::info('✅ Logo pedido creado exitosamente', [
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
}
