<?php

namespace App\Application\Cotizacion\Services;

use App\Application\Cotizacion\DTOs\ActualizarBorradorCotizacionRequest;
use App\Application\Cotizacion\DTOs\CotizacionResponse;
use App\Application\Cotizacion\Exceptions\CotizacionPermisoDenegadoException;
use App\Application\Cotizacion\Exceptions\CotizacionNoBorradorException;
use App\Models\Cotizacion;
use App\Models\Cliente;
use App\Models\LogoCotizacion;
use App\Models\LogoCotizacionTecnicaPrendaFoto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Caso de Uso: Actualizar borrador de Cotización de Bordado
 * Responsabilidades:
 * - Validar que el borrador pertenece al usuario
 * - Actualizar cliente si cambió
 * - Sincronizar técnicas y prendas
 * - Procesar nuevas imágenes
 * - Borrar imágenes marcadas
 * - Cambiar estado a ENVIADA si es envío
 * - Generar número si es enviada (primer envío)
 */
class ActualizarBorradorCotizacionService
{
    public function __construct(
        private readonly GenerarNumeroCotizacionService $generarNumeroCotizacionService,
        private readonly ProcesarTecnicasBordadoService $procesarTecnicasService,
        private readonly ProcesarTelasBordadoService $procesarTelasService,
        private readonly BorrarArchivoService $borrarArchivoService,
    ) {
    }

    /**
     * Ejecutar caso de uso
     */
    public function ejecutar(ActualizarBorradorCotizacionRequest $request): CotizacionResponse
    {
        return DB::transaction(function () use ($request) {
            try {
                Log::info(' ActualizarBorradorCotizacionService - Iniciando actualización', [
                    'cotizacion_id' => $request->cotizacion_id,
                    'es_envio' => $request->es_envio,
                    'asesor_id' => Auth::id(),
                ]);

                // 1. Validar y obtener cotización
                $cotizacion = $this->validarYObtenerCotizacion(
                    $request->cotizacion_id,
                    $request->editar_cotizacion
                );

                // 2. Obtener o crear cliente
                $clienteId = $this->obtenerOCrearCliente($request->cliente_id, $request->cliente);

                // 3. Actualizar datos de cotización
                $this->actualizarCotizacion($cotizacion, $request, $clienteId);

                // 4. Actualizar LogoCotizacion y sincronizar datos
                $logoCotizacion = $this->actualizarLogoCotizacion($request);
                $this->sincronizarTecnicasYTelas($request, $logoCotizacion);

                // 5. Procesar envío si es necesario
                $this->procesarEnvio($request);

                // 6. Borrar imágenes marcadas
                $this->borrarImagenesPostTransaccion(
                    $request->imagenes_a_borrar,
                    $request->tecnicas_fotos_a_borrar
                );

                // 7. Recargar y retornar
                $cotizacionCompleta = $cotizacion->load([
                    'cliente',
                    'logoCotizacion' => function ($query) {
                        $query->with(['fotos', 'prendas', 'prendas.tipoLogo']);
                    }
                ]);

                return CotizacionResponse::fromModel($cotizacionCompleta);

            } catch (\Exception $e) {
                Log::error('✗ Error en ActualizarBorradorCotizacionService', [
                    'error' => $e->getMessage(),
                    'cotizacion_id' => $request->cotizacion_id,
                ]);
                throw $e;
            }
        }, attempts: 3);
    }

    /**
     * Actualizar datos de la cotización
     */
    private function actualizarCotizacion(
        Cotizacion $cotizacion,
        ActualizarBorradorCotizacionRequest $request,
        ?int $clienteId
    ): void {
        $numeroCotizacion = $cotizacion->numero_cotizacion;
        $datosActualizar = [];

        // Agregar cliente si cambió
        if ($clienteId) {
            $datosActualizar['cliente_id'] = $clienteId;
        }

        // Agregar especificaciones
        if ($request->especificaciones) {
            $datosActualizar['especificaciones'] = json_encode($request->especificaciones);
        }

        // Procesar datos de envío
        if ($request->es_envio) {
            $numeroCotizacion = $this->generarNumeroCotizacionSiNecesario($numeroCotizacion);
            $datosActualizar['numero_cotizacion'] = $numeroCotizacion;
            $datosActualizar['es_borrador'] = false;
            $datosActualizar['estado'] = 'ENVIADA_CONTADOR';
            $datosActualizar['fecha_envio'] = now();
        }

        if (!empty($datosActualizar)) {
            $cotizacion->update($datosActualizar);
            Log::info('✓ Cotización actualizada', ['datos' => array_keys($datosActualizar)]);
        }
    }

    /**
     * Generar número de cotización si es necesario
     */
    private function generarNumeroCotizacionSiNecesario(
        ?string $numeroCotizacion
    ): string {
        if (!$numeroCotizacion) {
            $numeroCotizacion = $this->generarNumeroCotizacionService
                ->generarNumeroCotizacionFormateado(Auth::id());
            Log::info('✓ Número generado para envío', ['numero' => $numeroCotizacion]);
        }

        return $numeroCotizacion;
    }

    /**
     * Actualizar LogoCotizacion
     */
    private function actualizarLogoCotizacion(ActualizarBorradorCotizacionRequest $request): LogoCotizacion
    {
        $logoCotizacion = LogoCotizacion::updateOrCreate(
            ['cotizacion_id' => $request->cotizacion_id],
            [
                'observaciones_generales' => json_encode($request->observaciones_generales ?? []),
                'tipo_venta' => $request->tipo_venta,
            ]
        );

        Log::info('✓ LogoCotizacion actualizado', ['logo_id' => $logoCotizacion->id]);

        return $logoCotizacion;
    }

    /**
     * Sincronizar técnicas y telas
     */
    private function sincronizarTecnicasYTelas(
        ActualizarBorradorCotizacionRequest $request,
        LogoCotizacion $logoCotizacion
    ): void {
        $this->procesarTecnicasService->sincronizar(
            $logoCotizacion->id,
            $logoCotizacion->cotizacion_id,
            $request->tecnicas,
            $request->archivos_tecnicas,
            $request->logos_compartidos_metadata,
        );
        Log::info('✓ Técnicas sincronizadas');

        $this->procesarTelasService->ejecutar(
            $logoCotizacion->id,
            $request->tecnicas,
        );
        Log::info('✓ Telas procesadas');
    }

    /**
     * Procesar envío si es necesario
     */
    private function procesarEnvio(ActualizarBorradorCotizacionRequest $request): void
    {
        if (!$request->es_envio) {
            return;
        }

        \App\Jobs\ProcesarEnvioCotizacionJob::dispatch(
            $request->cotizacion_id,
            2 // tipo_cotizacion_id para Logo/Bordado
        )->onQueue('cotizaciones');

        Log::info('✓ Job de envío encolado', [
            'cotizacion_id' => $request->cotizacion_id,
        ]);
    }

    /**
     * Validar que la cotización existe y pertenece al usuario
     */
    private function validarYObtenerCotizacion(int $cotizacionId, bool $permitirEnviada): Cotizacion
    {
        $cotizacion = Cotizacion::findOrFail($cotizacionId);

        // Verificar propiedad
        if ($cotizacion->asesor_id !== Auth::id()) {
            throw new CotizacionPermisoDenegadoException($cotizacionId, Auth::id());
        }

        // Verificar si es borrador o si se permite editarla enviada
        if (!$permitirEnviada && !$cotizacion->es_borrador) {
            throw new CotizacionNoBorradorException($cotizacionId);
        }

        return $cotizacion;
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

        return $cliente->id;
    }

    /**
     * Borrar imágenes DESPUÉS de la transacción (no se revierte)
     */
    private function borrarImagenesPostTransaccion(array $imagenesABorrar, array $tecnicasFotosABorrar): void
    {
        if (!empty($imagenesABorrar)) {
            $this->borrarFotosPrincipales($imagenesABorrar);
        }

        if (!empty($tecnicasFotosABorrar)) {
            $this->borrarFotosTecnicas($tecnicasFotosABorrar);
        }
    }

    /**
     * Borrar fotos principales del logo
     */
    private function borrarFotosPrincipales(array $imagenesABorrar): void
    {
        // LogoFotoCot ya no se usa - tabla no utilizada
        Log::info('Fotos principales descartadas (tabla no utilizada)', ['ids' => $imagenesABorrar]);
    }

    /**
     * Borrar fotos de técnicas
     */
    private function borrarFotosTecnicas(array $tecnicasFotosABorrar): void
    {
        Log::info('Borrando fotos de técnicas', ['ids' => $tecnicasFotosABorrar]);

        $ids = array_map(fn($id) => (int) $id, $tecnicasFotosABorrar);
        
        foreach ($ids as $id) {
            $foto = LogoCotizacionTecnicaPrendaFoto::find($id);
            if ($foto) {
                $this->borrarArchivosDeFoto($foto);
                $foto->forceDelete();
            }
        }

        Log::info('✓ Fotos de técnicas borradas', ['count' => count($ids)]);
    }

    /**
     * Borrar archivos asociados a una foto
     */
    private function borrarArchivosDeFoto($foto): void
    {
        $this->borrarArchivoService->borrar($foto->ruta_original);
        $this->borrarArchivoService->borrar($foto->ruta_webp);
        $this->borrarArchivoService->borrar($foto->ruta_miniatura);
    }
}
