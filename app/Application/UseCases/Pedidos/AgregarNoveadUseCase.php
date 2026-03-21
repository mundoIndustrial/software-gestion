<?php

namespace App\Application\UseCases\Pedidos;

use App\Application\UseCases\Pedidos\DTOs\AgregarNoveadInput;
use App\Application\UseCases\Pedidos\DTOs\AgregarNoveadOutput;
use App\Events\OrdenUpdated;
use App\Models\PedidoProduccion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * UseCase: Agregar Nueva Novedad a una Orden
 * 
 * Responsabilidad: Orquestar agregación de nueva novedad con formato usuario-fecha
 * Patrón: UseCase (Application Service)
 */
class AgregarNoveadUseCase
{
    public function __construct()
    {}

    /**
     * Ejecutar: Agregar novedad de forma atómica
     * 
     * @throws ModelNotFoundException Si la orden no existe
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    public function execute(AgregarNoveadInput $input): AgregarNoveadOutput
    {
        try {
            \Log::info('AgregarNoveadUseCase iniciado', [
                'numero_pedido' => $input->numero_pedido,
                'usuario' => $input->usuario,
            ]);

            // Validar entrada
            if (!$input->isValid()) {
                throw new \InvalidArgumentException('Novedad vacía o inválida');
            }

            // Iniciar transacción
            DB::beginTransaction();

            try {
                // Obtener orden existente
                $orden = PedidoProduccion::where('numero_pedido', $input->numero_pedido)
                    ->firstOrFail();

                // Formatear novedad con usuario y fecha-hora
                $fechaHora = Carbon::now()->format('d-m-Y h:i:s A');
                $usuarioFormato = $input->usuario ?? 'Sistema';
                $novedadFormato = "[{$usuarioFormato} - {$fechaHora}] " . $input->novedad;

                // Obtener novedades actuales
                $novedadesActuales = $orden->novedades ?? '';

                // Concatenar con salto de línea si hay novedades anteriores
                if (!empty($novedadesActuales)) {
                    $novedadesNuevas = $novedadesActuales . "\n\n" . $novedadFormato;
                } else {
                    $novedadesNuevas = $novedadFormato;
                }

                // Actualizar novedades
                $orden->update([
                    'novedades' => $novedadesNuevas,
                ]);

                // Registrar en auditoria si existe
                if (class_exists('App\Models\AuditLog')) {
                    try {
                        \App\Models\AuditLog::create([
                            'user_id' => auth()?->id(),
                            'action' => 'add_novedad',
                            'auditable_type' => PedidoProduccion::class,
                            'auditable_id' => $orden->id,
                            'changes' => [
                                'novedad_agregada' => $novedadFormato,
                            ]
                        ]);
                    } catch (\Exception $auditError) {
                        \Log::warning('Error creando AuditLog en AgregarNoveadUseCase', [
                            'numero_pedido' => $input->numero_pedido,
                            'error' => $auditError->getMessage(),
                        ]);
                    }
                }

                DB::commit();

                // Recargar orden
                $orden->fresh();

                // Broadcast del evento (con fallback)
                try {
                    broadcast(new OrdenUpdated($orden, 'updated', ['novedades']));
                } catch (\Exception $broadcastError) {
                    \Log::warning('Error broadcasting OrdenUpdated en AgregarNoveadUseCase', [
                        'numero_pedido' => $input->numero_pedido,
                        'error' => $broadcastError->getMessage(),
                    ]);
                }

                \Log::info('AgregarNoveadUseCase completado', [
                    'numero_pedido' => $input->numero_pedido,
                    'usuario' => $usuarioFormato,
                ]);

                return new AgregarNoveadOutput(
                    numero_pedido: $input->numero_pedido,
                    mensaje: 'Novedad agregada correctamente',
                    novedad_agregada: $novedadFormato,
                    novedades_completas: $novedadesNuevas,
                    metadata: [
                        'usuario' => $usuarioFormato,
                        'fecha_hora' => $fechaHora,
                        'timestamp' => now()->format('Y-m-d H:i:s'),
                    ],
                );
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ModelNotFoundException $e) {
            \Log::error('Orden no encontrada en AgregarNoveadUseCase', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Error en AgregarNoveadUseCase', [
                'numero_pedido' => $input->numero_pedido,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
