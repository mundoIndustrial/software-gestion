<?php

namespace App\Services;

use App\Enums\EstadoCotizacion;
use App\Events\CotizacionCreada;
use App\Models\Cotizacion;
use App\Models\HistorialCambiosCotizacion;
use App\Jobs\AsignarNumeroCotizacionJob;
use App\Jobs\EnviarCotizacionAAprobadorJob;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class CotizacionEstadoService extends BaseService
{
    /**
     * Enviar cotización a Contador
     * BORRADOR → ENVIADA_CONTADOR
     */
    public function enviarACOntador(Cotizacion $cotizacion): bool
    {
        try {
            // Validar transición
            if (!$this->validarTransicion($cotizacion, EstadoCotizacion::ENVIADA_CONTADOR)) {
                throw new Exception("No se puede enviar una cotización que no está en borrador");
            }

            $estadoAnterior = $cotizacion->estado;

            // Asignar número de cotización si no lo tiene
            if ($cotizacion->numero_cotizacion === null) {
                $this->asignarNumeroCotizacion($cotizacion);
            }

            // Cambiar estado
            $cotizacion->update([
                'estado' => EstadoCotizacion::ENVIADA_CONTADOR->value,
                'fecha_envio' => Carbon::now('America/Bogota'),
            ]);

            // Registrar en historial
            $this->registrarCambioEstado(
                $cotizacion,
                $estadoAnterior,
                EstadoCotizacion::ENVIADA_CONTADOR->value,
                "Cotización enviada a contador para revisión"
            );

            // Disparar notificación a Contador (Job)
            dispatch(new \App\Jobs\EnviarCotizacionAContadorJob($cotizacion));

            // Broadcast realtime para que aparezca inmediatamente en el módulo Contador
            try {
                $cotizacion->loadMissing(['cliente', 'asesor']);
                $payload = $cotizacion->toArray();
                $payload['asesora'] = $cotizacion->asesor?->name;
                $payload['usuario'] = [
                    'name' => $cotizacion->asesor?->name,
                ];
                $payload['nombre_cliente'] = $cotizacion->cliente?->nombre;

                Log::info('[BROADCAST-BORRADOR] Emitiendo CotizacionCreada por envío a contador', [
                    'cotizacion_id' => $cotizacion->id,
                    'estado' => $cotizacion->estado,
                    'asesor_id' => $cotizacion->asesor_id,
                    'tipo_cotizacion_id' => $cotizacion->tipo_cotizacion_id,
                ]);

                broadcast(new CotizacionCreada(
                    $cotizacion->id,
                    $cotizacion->asesor_id,
                    $cotizacion->estado,
                    $payload
                ));
            } catch (Exception $e) {
                Log::warning('[BROADCAST-BORRADOR] Falló broadcast al enviar a contador', [
                    'cotizacion_id' => $cotizacion->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return true;
        } catch (Exception $e) {
            \Log::error("Error al enviar cotización a contador: " . $e->getMessage(), [
                'cotizacion_id' => $cotizacion->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Aprobar cotización como Contador
     * ENVIADA_CONTADOR → APROBADA_CONTADOR
     * Asigna el número de cotización via Job
     */
    public function aprobarComoContador(Cotizacion $cotizacion): bool
    {
        try {
            // Validar transición
            if (!$this->validarTransicion($cotizacion, EstadoCotizacion::APROBADA_CONTADOR)) {
                throw new Exception("La cotización debe estar en estado ENVIADA_CONTADOR");
            }

            $estadoAnterior = $cotizacion->estado;

            // Cambiar estado
            $cotizacion->update([
                'estado' => EstadoCotizacion::APROBADA_CONTADOR->value,
                'aprobada_por_contador_en' => now(),
            ]);

            // Registrar en historial
            $this->registrarCambioEstado(
                $cotizacion,
                $estadoAnterior,
                EstadoCotizacion::APROBADA_CONTADOR->value,
                "Cotización aprobada por contador"
            );

            // Disparar Job para asignar número y enviar a Aprobador
            dispatch(new AsignarNumeroCotizacionJob($cotizacion));

            return true;
        } catch (Exception $e) {
            \Log::error("Error al aprobar cotización como contador: " . $e->getMessage(), [
                'cotizacion_id' => $cotizacion->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Aprobar cotización corregida como Contador
     * EN_CORRECCION → APROBADA_CONTADOR (re-envía al aprobador)
     */
    public function aprobarCotizacionCorregida(Cotizacion $cotizacion): bool
    {
        try {
            // Validar que esté en corrección
            if ($cotizacion->estado !== 'EN_CORRECCION') {
                throw new Exception("La cotización debe estar en estado EN_CORRECCION");
            }

            $estadoAnterior = $cotizacion->estado;

            // Cambiar estado a APROBADA_CONTADOR para que vuelva al aprobador
            $cotizacion->update([
                'estado' => EstadoCotizacion::APROBADA_CONTADOR->value,
                'aprobada_por_contador_en' => now(),
            ]);

            // Registrar en historial
            $this->registrarCambioEstado(
                $cotizacion,
                $estadoAnterior,
                EstadoCotizacion::APROBADA_CONTADOR->value,
                "Cotización corregida y re-enviada al aprobador para revisión final"
            );

            return true;
        } catch (Exception $e) {
            \Log::error("Error al aprobar cotización corregida: " . $e->getMessage(), [
                'cotizacion_id' => $cotizacion->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Aprobar cotización como Aprobador de Cotizaciones
     * APROBADA_CONTADOR → APROBADA_COTIZACIONES
     */
    public function aprobarComoAprobador(Cotizacion $cotizacion): bool
    {
        try {
            // Validar transición
            if (!$this->validarTransicion($cotizacion, EstadoCotizacion::APROBADA_COTIZACIONES)) {
                throw new Exception("La cotización debe estar en estado APROBADA_CONTADOR");
            }

            $estadoAnterior = $cotizacion->estado;

            // Cambiar estado
            $cotizacion->update([
                'estado' => EstadoCotizacion::APROBADA_COTIZACIONES->value,
                'aprobada_por_aprobador_en' => now(),
            ]);

            // Registrar en historial
            $this->registrarCambioEstado(
                $cotizacion,
                $estadoAnterior,
                EstadoCotizacion::APROBADA_COTIZACIONES->value,
                "Cotización aprobada por aprobador de cotizaciones - Lista para crear pedido"
            );

            return true;
        } catch (Exception $e) {
            \Log::error("Error al aprobar cotización como aprobador: " . $e->getMessage(), [
                'cotizacion_id' => $cotizacion->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Marcar cotización como convertida a pedido
     * APROBADA_COTIZACIONES → CONVERTIDA_PEDIDO
     */
    public function marcarComoConvertidaAPedido(Cotizacion $cotizacion): bool
    {
        try {
            // Validar transición
            if (!$this->validarTransicion($cotizacion, EstadoCotizacion::CONVERTIDA_PEDIDO)) {
                throw new Exception("La cotización debe estar en estado APROBADA_COTIZACIONES");
            }

            $estadoAnterior = $cotizacion->estado;

            // Cambiar estado
            $cotizacion->update([
                'estado' => EstadoCotizacion::CONVERTIDA_PEDIDO->value,
            ]);

            // Registrar en historial
            $this->registrarCambioEstado(
                $cotizacion,
                $estadoAnterior,
                EstadoCotizacion::CONVERTIDA_PEDIDO->value,
                "Cotización convertida a pedido de producción"
            );

            return true;
        } catch (Exception $e) {
            \Log::error("Error al marcar cotización como convertida a pedido: " . $e->getMessage(), [
                'cotizacion_id' => $cotizacion->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Marcar cotización como finalizada
     * CONVERTIDA_PEDIDO → FINALIZADA
     */
    public function marcarComoFinalizada(Cotizacion $cotizacion): bool
    {
        try {
            // Validar transición
            if (!$this->validarTransicion($cotizacion, EstadoCotizacion::FINALIZADA)) {
                throw new Exception("La cotización debe estar en estado CONVERTIDA_PEDIDO");
            }

            $estadoAnterior = $cotizacion->estado;

            // Cambiar estado
            $cotizacion->update([
                'estado' => EstadoCotizacion::FINALIZADA->value,
            ]);

            // Registrar en historial
            $this->registrarCambioEstado(
                $cotizacion,
                $estadoAnterior,
                EstadoCotizacion::FINALIZADA->value,
                "Cotización finalizada - Pedido completo"
            );

            return true;
        } catch (Exception $e) {
            \Log::error("Error al marcar cotización como finalizada: " . $e->getMessage(), [
                'cotizacion_id' => $cotizacion->id,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Obtener el estado actual de una cotización
     */
    public function obtenerEstadoActual(Cotizacion $cotizacion): ?string
    {
        return $cotizacion->estado;
    }

    /**
     * Obtener historial de cambios de estado
     */
    public function obtenerHistorial(Cotizacion $cotizacion): Collection
    {
        return $cotizacion->historialCambios()
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Validar si la transición de estado es permitida
     */
    public function validarTransicion(Cotizacion $cotizacion, EstadoCotizacion $estadoNuevo): bool
    {
        $estadoActual = EstadoCotizacion::tryFrom($cotizacion->estado);

        if (!$estadoActual) {
            return false;
        }

        return $estadoActual->puedePasar($estadoNuevo);
    }

    /**
     * Registrar un cambio de estado en el historial
     */
    protected function registrarCambioEstado(
        Cotizacion $cotizacion,
        ?string $estadoAnterior,
        string $estadoNuevo,
        ?string $razonCambio = null
    ): HistorialCambiosCotizacion {
        $usuario = Auth::user();

        return HistorialCambiosCotizacion::create([
            'cotizacion_id' => $cotizacion->id,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'usuario_id' => $usuario?->id,
            'usuario_nombre' => $usuario?->name ?? 'Sistema',
            'rol_usuario' => $usuario?->roles_ids[0] ?? 'Sistema',
            'razon_cambio' => $razonCambio,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'datos_adicionales' => [
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'cliente' => $cotizacion->cliente,
            ],
            'created_at' => now(),
        ]);
    }

    /**
     * Obtener el siguiente número de cotización disponible
     */
    public function obtenerSiguienteNumeroCotizacion(): int
    {
        $numeroMaximo = Cotizacion::whereNotNull('numero_cotizacion')
            ->max('numero_cotizacion');

        $numero = $numeroMaximo ? (int) $numeroMaximo : 0;
        return $numero + 1;
    }

    /**
     * Asignar número de cotización
     */
    public function asignarNumeroCotizacion(Cotizacion $cotizacion): void
    {
        if ($cotizacion->numero_cotizacion === null) {
            $numero = $this->obtenerSiguienteNumeroCotizacion();
            $cotizacion->update(['numero_cotizacion' => $numero]);

            // Registrar en historial que se asignó el número
            HistorialCambiosCotizacion::create([
                'cotizacion_id' => $cotizacion->id,
                'estado_anterior' => EstadoCotizacion::APROBADA_CONTADOR->value,
                'estado_nuevo' => EstadoCotizacion::APROBADA_CONTADOR->value,
                'usuario_id' => Auth::id() ?? null,
                'usuario_nombre' => Auth::user()?->name ?? 'Sistema',
                'rol_usuario' => Auth::user()?->roles_ids[0] ?? 'Sistema',
                'razon_cambio' => "Se asignó número de cotización: {$numero}",
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'datos_adicionales' => ['numero_asignado' => $numero],
                'created_at' => now(),
            ]);

            \Log::info("Número de cotización asignado", [
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $numero
            ]);
        }
    }
}
