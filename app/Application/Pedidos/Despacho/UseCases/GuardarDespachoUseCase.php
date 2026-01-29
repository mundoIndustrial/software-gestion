<?php

namespace App\Application\Pedidos\Despacho\UseCases;

use App\Models\PedidoProduccion;
use App\Domain\Pedidos\Despacho\Services\DespachoValidadorService;
use App\Domain\Pedidos\Despacho\Services\DesparChoParcialesPersistenceService;
use App\Application\Pedidos\Despacho\DTOs\ControlEntregasDTO;
use App\Application\Pedidos\Despacho\DTOs\DespachoParcialesDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * GuardarDespachoUseCase
 * 
 * Use Case (Application Service) para guardar/procesar despachos
 * 
 * Coordina:
 * - Validación de despachos (Domain Service)
 * - Persistencia de despachos (Domain Service)
 * - Transacciones DB
 * - Auditoría/Logs
 */
class GuardarDespachoUseCase
{
    public function __construct(
        private DespachoValidadorService $validador,
        private DesparChoParcialesPersistenceService $persistencia,
    ) {}

    /**
     * Ejecutar: Guardar control de entregas
     * 
     * @param ControlEntregasDTO $control
     * @return array
     * @throws \Exception
     */
    public function ejecutar(ControlEntregasDTO $control): array
    {
        try {
            // Validar que el pedido existe
            $pedido = PedidoProduccion::find($control->pedidoId);
            if (!$pedido) {
                throw new \Exception("Pedido con ID {$control->pedidoId} no encontrado");
            }

            DB::beginTransaction();

            // Convertir snake_case del JS a camelCase para DTO
            $despachos = array_map(function($d) {
                \Log::debug('Datos recibidos del frontend', ['datos_raw' => $d]);
                return new DespachoParcialesDTO(
                    tipo: $d['tipo'],
                    id: $d['id'],
                    tallaId: $d['talla_id'] ?? null,
                    genero: $d['genero'] ?? null,  // ✅ Agregar género
                    pendienteInicial: $d['pendiente_inicial'] ?? 0,
                    parcial1: $d['parcial_1'] ?? 0,
                    pendiente1: $d['pendiente_1'] ?? 0,
                    parcial2: $d['parcial_2'] ?? 0,
                    pendiente2: $d['pendiente_2'] ?? 0,
                    parcial3: $d['parcial_3'] ?? 0,
                    pendiente3: $d['pendiente_3'] ?? 0,
                );
            }, $control->despachos);

            $this->validador->validarMultiplesDespachos($despachos);

            // Procesar cada despacho
            foreach ($despachos as $despacho) {
                $this->validador->procesarDespacho($despacho, $control->clienteEmpresa);
            }

            // Persistir los despachos usando el servicio del dominio
            $usuarioId = Auth::id();
            $despachosPersistidos = $this->persistencia->crearYGuardarMultiples(
                array_map(function ($despacho) use ($control) {
                    return [
                        'pedido_id' => $control->pedidoId,
                        'tipo_item' => $despacho->tipo,
                        'item_id' => $despacho->tallaId ?? $despacho->id,  // ✅ Para prendas: usar tallaId, para EPP: usar id
                        'talla_id' => $despacho->tallaId,
                        'genero' => $despacho->genero,  // ✅ Agregar género
                        'pendiente_inicial' => $despacho->pendienteInicial,
                        'parcial_1' => $despacho->parcial1,
                        'pendiente_1' => $despacho->pendiente1,
                        'parcial_2' => $despacho->parcial2,
                        'pendiente_2' => $despacho->pendiente2,
                        'parcial_3' => $despacho->parcial3,
                        'pendiente_3' => $despacho->pendiente3,
                        'observaciones' => "Cliente empresa: {$control->clienteEmpresa}",
                    ];
                }, $despachos),
                usuarioId: $usuarioId,
            );

            DB::commit();

            \Log::info('Control de entregas guardado correctamente', [
                'pedido_id' => $control->pedidoId,
                'numero_pedido' => $control->numeroPedido,
                'cantidad_items' => count($despachos),
                'cantidad_persistidos' => $despachosPersistidos->count(),
                'fecha_hora' => $control->fechaHora,
                'cliente_empresa' => $control->clienteEmpresa,
                'usuario_id' => $usuarioId,
            ]);

            return [
                'success' => true,
                'message' => 'Control de entregas guardado correctamente',
                'pedido_id' => $control->pedidoId,
                'despachos_procesados' => count($despachos),
                'despachos_persistidos' => $despachosPersistidos->count(),
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error al guardar control de entregas', [
                'pedido_id' => $control->pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
