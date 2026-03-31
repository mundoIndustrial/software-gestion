<?php

namespace App\Application\Cotizacion\Services;

use App\Application\Cotizacion\DTOs\CrearCotizacionBordadoRequest;
use App\Application\Cotizacion\DTOs\CotizacionResponse;
use App\Models\Cotizacion;
use App\Models\Cliente;
use App\Models\TipoCotizacion;
use App\Models\LogoCotizacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Caso de Uso: Crear nueva Cotización de Bordado
 * 
 * Responsabilidades:
 * - Validar datos de entrada
 * - Obtener o crear cliente
 * - Generar número de cotización si es enviada
 * - Crear agregado Cotizacion con sus dependencias
 * - Procesar técnicas y telas
 * - Persistir cambios
 * - Emitir eventos de dominio
 */
class CrearCotizacionBordadoService
{
    public function __construct(
        private readonly GenerarNumeroCotizacionService $generarNumeroCotizacionService,
        private readonly ProcesarTecnicasBordadoService $procesarTecnicasService,
        private readonly ProcesarTelasBordadoService $procesarTelasService,
    ) {
    }

    /**
     * Ejecutar caso de uso
     * 
     * @return CotizacionResponse
     * @throws \Exception
     */
    public function ejecutar(CrearCotizacionBordadoRequest $request): CotizacionResponse
    {
        return DB::transaction(function () use ($request) {
            try {
                Log::info(' CrearCotizacionBordadoService - Iniciando creación', [
                    'es_borrador' => $request->es_borrador,
                    'asesor_id' => Auth::id(),
                ]);

                // 1. Obtener o crear cliente
                $clienteId = $this->obtenerOCrearCliente(
                    $request->cliente_id,
                    $request->cliente
                );

                // 2. Generar número si es enviada (no borrador)
                $numeroCotizacion = null;
                if (!$request->es_borrador) {
                    $numeroCotizacion = $this->generarNumeroCotizacionService
                        ->generarNumeroCotizacionFormateado(Auth::id());
                    Log::info('✓ Número generado', ['numero' => $numeroCotizacion]);
                }

                // 3. Obtener tipo de cotización "Logo/Bordado"
                $tipoBordado = $this->obtenerTipoCotizacion();

                // 4. Crear agregado Cotizacion
                $cotizacion = Cotizacion::create([
                    'asesor_id' => Auth::id(),
                    'cliente_id' => $clienteId,
                    'numero_cotizacion' => $numeroCotizacion,
                    'tipo_cotizacion_id' => $tipoBordado->id,
                    'tipo_venta' => $request->tipo_venta,
                    'es_borrador' => $request->es_borrador,
                    'estado' => $request->es_borrador ? 'BORRADOR' : 'ENVIADA_CONTADOR',
                    'fecha_envio' => !$request->es_borrador ? now() : null,
                    'especificaciones' => json_encode($request->especificaciones),
                ]);

                Log::info('✓ Cotización creada', [
                    'cotizacion_id' => $cotizacion->id,
                    'numero' => $numeroCotizacion,
                ]);

                // 5. Crear LogoCotizacion
                $logoCotizacion = LogoCotizacion::create([
                    'cotizacion_id' => $cotizacion->id,
                    'observaciones_generales' => json_encode($request->observaciones_generales ?? []),
                    'tipo_venta' => $request->tipo_venta,
                ]);

                Log::info('✓ LogoCotizacion creado', ['logo_id' => $logoCotizacion->id]);

                // 6. Procesar técnicas con prendas
                if (!empty($request->tecnicas)) {
                    $this->procesarTecnicasService->ejecutar(
                        $logoCotizacion->id,
                        $cotizacion->id,
                        $request->tecnicas,
                        $request->archivos_tecnicas,
                    );
                    Log::info('✓ Técnicas procesadas', [
                        'count' => count($request->tecnicas),
                    ]);
                }

                // 7. Procesar telas
                if (!empty($request->tecnicas)) {
                    $this->procesarTelasService->ejecutar(
                        $logoCotizacion->id,
                        $request->tecnicas,
                    );
                    Log::info('✓ Telas procesadas');
                }

                // 8. Encolar job de envío si es necesario
                if (!$request->es_borrador) {
                    \App\Jobs\ProcesarEnvioCotizacionJob::dispatch(
                        $cotizacion->id,
                        2 // tipo_cotizacion_id para Logo/Bordado
                    )->onQueue('cotizaciones');

                    Log::info('✓ Job de envío encolado', [
                        'cotizacion_id' => $cotizacion->id,
                    ]);
                }

                // 9. Recargar y retornar
                $cotizacionCompleta = $cotizacion->fresh();

                return CotizacionResponse::fromModel($cotizacionCompleta);

            } catch (\Exception $e) {
                Log::error('✗ Error en CrearCotizacionBordadoService', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                throw $e;
            }
        }, attempts: 3);
    }

    /**
     * Obtener o crear cliente
     */
    private function obtenerOCrearCliente(?int $clienteId, ?string $nombreCliente): ?int
    {
        if ($clienteId) {
            return $clienteId;
        }

        if (!$nombreCliente) {
            return null;
        }

        $cliente = Cliente::firstOrCreate(
            ['nombre' => $nombreCliente],
            ['nombre' => $nombreCliente]
        );

        Log::info('✓ Cliente procesado', [
            'cliente_id' => $cliente->id,
            'nombre' => $nombreCliente,
        ]);

        return $cliente->id;
    }

    /**
     * Obtener tipo de cotización "Logo/Bordado"
     */
    private function obtenerTipoCotizacion(): TipoCotizacion
    {
        $tipoBordado = TipoCotizacion::where('codigo', 'L')->first();

        if (!$tipoBordado) {
            throw new \Exception('TIPO_COTIZACION_LOGO_NO_ENCONTRADO');
        }

        return $tipoBordado;
    }
}
