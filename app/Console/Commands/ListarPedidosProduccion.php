<?php

namespace App\Console\Commands;

use App\Models\PedidoProduccion;
use Illuminate\Console\Command;

class ListarPedidosProduccion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedidos:listar {--deleted : Incluir pedidos eliminados}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Lista todos los pedidos de producción con sus IDs y números';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $incluirEliminados = $this->option('deleted');

        // Construir query
        $query = PedidoProduccion::query();

        if ($incluirEliminados) {
            $query->withTrashed();
            $this->info(' Listando pedidos (incluyendo eliminados)');
        } else {
            $this->info(' Listando pedidos activos');
        }

        // Obtener pedidos
        $pedidos = $query->orderBy('id', 'asc')->get();

        if ($pedidos->isEmpty()) {
            $this->warn('  No hay pedidos');
            return;
        }

        // Tabla
        $headers = ['ID', 'Número Pedido', 'Cliente', 'Estado', 'Creado'];
        $rows = [];

        foreach ($pedidos as $pedido) {
            $rows[] = [
                $pedido->id,
                $pedido->numero_pedido ?? '-',
                $pedido->cliente ?? '-',
                $pedido->estado ?? '-',
                $pedido->created_at?->format('Y-m-d H:i') ?? '-',
            ];
        }

        $this->table($headers, $rows);

        // Estadísticas
        $this->newLine();
        $this->info(' ESTADÍSTICAS:');
        $this->info("   Total de pedidos: {$pedidos->count()}");
        
        if ($incluirEliminados) {
            $eliminados = $pedidos->filter(fn($p) => $p->trashed())->count();
            $activos = $pedidos->count() - $eliminados;
            $this->info("   - Pedidos activos: {$activos}");
            $this->info("   - Pedidos eliminados: {$eliminados}");
        }

        // IDs para debugging
        $this->newLine();
        $this->info(' IDs PARA DEBUGGING:');
        $ids = $pedidos->pluck('id')->implode(', ');
        $this->info("   {$ids}");

        // Números para debugging
        $this->newLine();
        $this->info(' NÚMEROS DE PEDIDO:');
        $numeros = $pedidos->pluck('numero_pedido')->implode(', ');
        $this->info("   {$numeros}");
    }
}
