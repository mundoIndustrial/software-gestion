<?php

namespace App\Console\Commands;

use App\Events\PedidoActualizado;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BroadcastPedidoActualizado extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:pedido-actualizado {pedido_id} {asesor_id} {pedido_data} {asesor_data} {changed_fields} {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía broadcast de pedido actualizado en proceso separado (background)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $pedidoId = $this->argument('pedido_id');
            $asesorId = $this->argument('asesor_id');
            $pedidoDataEnc = $this->argument('pedido_data');
            $asesorDataEnc = $this->argument('asesor_data');
            $changedFieldsEnc = $this->argument('changed_fields');
            $action = $this->argument('action');

            // Decodificar datos base64
            $pedidoData = json_decode(base64_decode($pedidoDataEnc), true);
            $asesorData = json_decode(base64_decode($asesorDataEnc), true);
            $changedFields = json_decode(base64_decode($changedFieldsEnc), true);

            // Obtener modelos actualizados de BD
            $pedido = PedidoProduccion::find($pedidoId);
            $asesor = User::find($asesorId);

            if (!$pedido || !$asesor) {
                Log::warning('BroadcastPedidoActualizado: Pedido o asesor no encontrado', [
                    'pedido_id' => $pedidoId,
                    'asesor_id' => $asesorId,
                ]);
                return Command::FAILURE;
            }

            // AQUÍ se ejecuta el broadcast con timeout normal (no urgente)
            PedidoActualizado::dispatch($pedido, $asesor, $changedFields, $action);

            Log::info('✅ Broadcast PedidoActualizado enviado (background)', [
                'pedido_id' => $pedidoId,
                'asesor_id' => $asesorId,
                'action' => $action,
                'changed_fields' => $changedFields,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('❌ Error en BroadcastPedidoActualizado', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }
}
