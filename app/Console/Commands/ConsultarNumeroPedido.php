<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConsultarNumeroPedido extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pedido:consultar {--estado= : Filtrar por estado} {--cliente= : Filtrar por cliente}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Consulta el número de pedido actual y muestra resumen de pedidos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('               CONSULTA DE PEDIDOS EN PRODUCCIÓN               ');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->line('');

        // Obtener información de auto-increment
        $autoIncrement = DB::select("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos_produccion'");
        
        $proximoNumero = $autoIncrement[0]->AUTO_INCREMENT ?? null;
        $ultimoPedido = DB::table('pedidos_produccion')
            ->max('numero_pedido');

        if ($ultimoPedido) {
            $this->line('<fg=green;options=bold>✓ Último número de pedido: ' . $ultimoPedido . '</>', 'error');
        }
        
        if ($proximoNumero) {
            $this->line('<fg=cyan;options=bold>→ PRÓXIMO NÚMERO SECUENCIAL: ' . $proximoNumero . '</>', 'error');
            $this->line('');
        }

        // Resumen por estado
        $this->info('RESUMEN POR ESTADO:');
        $this->line('');

        $resumen = DB::table('pedidos_produccion')
            ->select('estado', DB::raw('COUNT(*) as cantidad'))
            ->whereNull('deleted_at')
            ->groupBy('estado')
            ->orderBy('cantidad', 'desc')
            ->get();

        if ($resumen->isNotEmpty()) {
            $this->table(
                ['Estado', 'Cantidad'],
                $resumen->map(fn($item) => [$item->estado, $item->cantidad])->toArray()
            );
        } else {
            $this->warn('No hay pedidos registrados');
            return;
        }

        $this->line('');

        // Pedidos más recientes
        $this->info('PEDIDOS MÁS RECIENTES (últimos 5):');
        $this->line('');

        $query = DB::table('pedidos_produccion')
            ->whereNull('deleted_at')
            ->select('numero_pedido', 'cliente', 'estado', 'fecha_estimada_de_entrega', 'created_at');

        if ($this->option('estado')) {
            $query->where('estado', $this->option('estado'));
        }

        if ($this->option('cliente')) {
            $query->where('cliente', 'like', '%' . $this->option('cliente') . '%');
        }

        $recientes = $query
            ->orderBy('numero_pedido', 'desc')
            ->limit(5)
            ->get();

        if ($recientes->isNotEmpty()) {
            $this->table(
                ['# Pedido', 'Cliente', 'Estado', 'Entrega Estimada', 'Creado'],
                $recientes->map(fn($item) => [
                    $item->numero_pedido,
                    substr($item->cliente, 0, 30),
                    $item->estado,
                    $item->fecha_estimada_de_entrega ? \Carbon\Carbon::parse($item->fecha_estimada_de_entrega)->format('d/m/Y') : 'N/A',
                    \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i')
                ])->toArray()
            );
        }

        $this->line('');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->line('');
    }
}
