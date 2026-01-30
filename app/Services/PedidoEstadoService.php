<?php

namespace App\Services;

use App\Enums\EstadoPedido;
use App\Models\PedidoProduccion;
use App\Models\HistorialCambiosPedido;
use App\Jobs\AsignarNumeroPedidoJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class PedidoEstadoService extends BaseService
{
    /**
     * Aprobar pedido como Supervisor
     * PENDIENTE_SUPERVISOR → APROBADO_SUPERVISOR
     * Asigna el número de pedido via Job
     */
    public function aprobarComoSupervisor(PedidoProduccion $pedido): bool
    {
        try {
            // Validar transición
            if (!$this->validarTransicion($pedido, EstadoPedido::APROBADO_SUPERVISOR)) {
                throw new Exception("El pedido debe estar en estado PENDIENTE_SUPERVISOR");
            }

            $estadoAnterior = $pedido->estado;

            // Cambiar estado
            $pedido->update([
                'estado' => EstadoPedido::APROBADO_SUPERVISOR->value,
                'aprobado_por_supervisor_en' => now(),
            ]);

            // Registrar en historial
            $this->registrarCambioEstado(
                $pedido,
                $estadoAnterior,
                EstadoPedido::APROBADO_SUPERVISOR->value,
                "Pedido aprobado por supervisor - Enviando a asignación de número"
            );

            // Disparar Job para asignar número y enviar a Producción
            dispatch(new AsignarNumeroPedidoJob($pedido));

            return true;
        } catch (Exception $e) {
            \Log::error("Error al aprobar pedido como supervisor: " . $e->getMessage(), [
                'pedido_id' => $pedido->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Cambiar estado a EN_PRODUCCION
     * APROBADO_SUPERVISOR → EN_PRODUCCION
     * (Este cambio se hace desde el Job después de asignar número)
     */
    public function enviarAProduccion(PedidoProduccion $pedido): bool
    {
        try {
            // Validar transición
            if (!$this->validarTransicion($pedido, EstadoPedido::EN_PRODUCCION)) {
                throw new Exception("El pedido debe estar en estado APROBADO_SUPERVISOR");
            }

            $estadoAnterior = $pedido->estado;

            // Cambiar estado
            $pedido->update([
                'estado' => EstadoPedido::EN_PRODUCCION->value,
            ]);

            // Crear automáticamente el primer proceso (Corte) en procesos_prenda
            $this->crearProcesoInicial($pedido);

            // Registrar en historial
            $this->registrarCambioEstado(
                $pedido,
                $estadoAnterior,
                EstadoPedido::EN_PRODUCCION->value,
                "Pedido enviado a producción"
            );

            return true;
        } catch (Exception $e) {
            \Log::error("Error al enviar pedido a producción: " . $e->getMessage(), [
                'pedido_id' => $pedido->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Marcar pedido como finalizado
     * EN_PRODUCCION → FINALIZADO
     */
    public function marcarComoFinalizado(PedidoProduccion $pedido): bool
    {
        try {
            // Validar transición
            if (!$this->validarTransicion($pedido, EstadoPedido::FINALIZADO)) {
                throw new Exception("El pedido debe estar en estado EN_PRODUCCION");
            }

            $estadoAnterior = $pedido->estado;

            // Cambiar estado
            $pedido->update([
                'estado' => EstadoPedido::FINALIZADO->value,
            ]);

            // Registrar en historial
            $this->registrarCambioEstado(
                $pedido,
                $estadoAnterior,
                EstadoPedido::FINALIZADO->value,
                "Pedido finalizado - Todos los procesos completados"
            );

            // Actualizar cotización a FINALIZADA si existe
            if ($pedido->cotizacion) {
                $cotizacionService = new CotizacionEstadoService();
                $cotizacionService->marcarComoFinalizada($pedido->cotizacion);
            }

            return true;
        } catch (Exception $e) {
            \Log::error("Error al marcar pedido como finalizado: " . $e->getMessage(), [
                'pedido_id' => $pedido->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Obtener el estado actual de un pedido
     */
    public function obtenerEstadoActual(PedidoProduccion $pedido): ?string
    {
        return $pedido->estado;
    }

    /**
     * Obtener historial de cambios de estado
     */
    public function obtenerHistorial(PedidoProduccion $pedido): Collection
    {
        return $pedido->historialCambios()
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Validar si la transición de estado es permitida
     */
    public function validarTransicion(PedidoProduccion $pedido, EstadoPedido $estadoNuevo): bool
    {
        $estadoActual = EstadoPedido::tryFrom($pedido->estado);

        if (!$estadoActual) {
            return false;
        }

        return $estadoActual->puedePasar($estadoNuevo);
    }

    /**
     * Registrar un cambio de estado en el historial
     */
    protected function registrarCambioEstado(
        PedidoProduccion $pedido,
        ?string $estadoAnterior,
        string $estadoNuevo,
        ?string $razonCambio = null
    ): HistorialCambiosPedido {
        $usuario = Auth::user();

        return HistorialCambiosPedido::create([
            'pedido_id' => $pedido->id,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'usuario_id' => $usuario?->id,
            'usuario_nombre' => $usuario?->name ?? 'Sistema',
            'rol_usuario' => $usuario?->roles_ids[0] ?? 'Sistema',
            'razon_cambio' => $razonCambio,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'datos_adicionales' => [
                'numero_pedido' => $pedido->numero_pedido,
                'numero_cotizacion' => $pedido->numero_cotizacion,
                'cliente' => $pedido->cliente,
            ],
            'created_at' => now(),
        ]);
    }

    /**
     * Obtener el siguiente número de pedido disponible
     */
    public function obtenerSiguienteNumeroPedido(): int
    {
        $numeroMaximo = PedidoProduccion::whereNotNull('numero_pedido')
            ->max('numero_pedido');

        $numero = $numeroMaximo ? (int) $numeroMaximo : 0;
        return $numero + 1;
    }

    /**
     * Método eliminado - ya no se asigna numero_pedido desde Supervisor
     * El numero_pedido ahora solo lo genera Cartera al aprobar
     */

    /**
     * Crear el primer proceso (Corte) cuando se envía a producción
     */
    private function crearProcesoInicial(PedidoProduccion $pedido): void
    {
        try {
            // Verificar si ya existe un proceso para este pedido
            $procesoExistente = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                ->first();

            if ($procesoExistente) {
                \Log::info("Proceso ya existe para el pedido", [
                    'numero_pedido' => $pedido->numero_pedido
                ]);
                return;
            }

            // Crear el primer proceso (Corte)
            \App\Models\ProcesoPrenda::create([
                'numero_pedido' => $pedido->numero_pedido,
                'proceso' => 'Corte',
                'fecha_inicio' => now(),
                'estado_proceso' => 'En Proceso',
                'observaciones' => 'Proceso iniciado automáticamente al enviar a producción',
                'encargado' => null, // Sin asignar hasta que un cortador lo tome
            ]);

            \Log::info("Primer proceso (Corte) creado automáticamente", [
                'numero_pedido' => $pedido->numero_pedido
            ]);
        } catch (Exception $e) {
            \Log::error("Error al crear proceso inicial: " . $e->getMessage(), [
                'numero_pedido' => $pedido->numero_pedido,
                'exception' => $e
            ]);
            // No lanzar excepción para no bloquear el envío a producción
        }
    }
}
