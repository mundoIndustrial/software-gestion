<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Cliente;
use App\Models\PedidoProduccion;

class VerificarMapeoAsesoresClientes extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'verificar:mapeo-asesores-clientes';

    /**
     * The console command description.
     */
    protected $description = 'Verifica que el mapeo de asesoras y clientes esté correcto.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE MAPEO ===');
        $this->newLine();

        // 1. VERIFICAR ASESORAS EN TABLA ORIGINAL
        $this->info('1️⃣  Asesoras en tabla_original');
        
        $asesoras_original = DB::table('tabla_original')
            ->whereNotNull('asesora')
            ->where('asesora', '!=', '')
            ->distinct()
            ->count();

        $this->line("   Total únicas: $asesoras_original");

        // 2. VERIFICAR ASESORAS EN USERS
        $this->newLine();
        $this->info('2️⃣  Usuarios en tabla users');
        
        $total_users = User::count();
        $users_creados = User::where('created_at', '>=', now()->subHours(2))->count();

        $this->line("   Total: $total_users");
        $this->line("   Creados en últimas 2 horas: $users_creados");

        // Listar las asesoras
        $asesor_names = User::pluck('name')->toArray();
        $this->line("   Nombres:");
        foreach (array_slice($asesor_names, 0, 10) as $name) {
            $this->line("     - $name");
        }
        if (count($asesor_names) > 10) {
            $this->line("     ... y " . (count($asesor_names) - 10) . " más");
        }

        // 3. VERIFICAR CLIENTES EN TABLA ORIGINAL
        $this->newLine();
        $this->info('3️⃣  Clientes en tabla_original');
        
        $clientes_original = DB::table('tabla_original')
            ->whereNotNull('cliente')
            ->where('cliente', '!=', '')
            ->distinct()
            ->count();

        $this->line("   Total únicas: $clientes_original");

        // 4. VERIFICAR CLIENTES EN TABLA CLIENTES
        $this->newLine();
        $this->info('4️⃣  Clientes en tabla clientes');
        
        $total_clientes = Cliente::count();
        $clientes_creados = Cliente::where('created_at', '>=', now()->subHours(2))->count();

        $this->line("   Total: $total_clientes");
        $this->line("   Creados en últimas 2 horas: $clientes_creados");
        $this->line("   Con user_id NULL: " . Cliente::whereNull('user_id')->count());

        // 5. VERIFICAR MAPEO EN PEDIDOS_PRODUCCION
        $this->newLine();
        $this->info('5️⃣  Mapeo en pedidos_produccion');
        
        $total_pedidos = PedidoProduccion::count();
        $con_user_id = PedidoProduccion::whereNotNull('user_id')->count();
        $con_cliente_id = PedidoProduccion::whereNotNull('cliente_id')->count();

        $this->line("   Total pedidos: $total_pedidos");
        $this->line("   Con user_id mapeado: $con_user_id");
        $this->line("   Con cliente_id mapeado: $con_cliente_id");

        // 6. VER PEDIDOS CON MAPEO COMPLETO
        $this->newLine();
        $this->info('6️⃣  Pedidos con mapeo completo');
        
        $pedidos_completos = PedidoProduccion::whereNotNull('user_id')
            ->whereNotNull('cliente_id')
            ->with(['asesora', 'clienteRelacion'])
            ->limit(5)
            ->get();

        if ($pedidos_completos->isEmpty()) {
            $this->warn("   Sin pedidos con mapeo completo");
        } else {
            foreach ($pedidos_completos as $pedido) {
                $asesor = $pedido->asesora?->name ?? 'N/A';
                $cliente = $pedido->clienteRelacion?->nombre ?? 'N/A';
                $this->line("   Pedido #{$pedido->numero_pedido}:");
                $this->line("     └─ Asesora: $asesor (user_id: {$pedido->user_id})");
                $this->line("     └─ Cliente: $cliente (cliente_id: {$pedido->cliente_id})");
            }
        }

        // 7. RESUMEN FINAL
        $this->newLine();
        $this->info('=== RESUMEN ===');

        if ($clientes_creados > 0) {
            $this->line("✓ Se crearon $clientes_creados clientes");
        }
        
        if ($users_creados > 0) {
            $this->line("✓ Se crearon $users_creados usuarios");
        }

        if ($con_user_id > 0 && $con_cliente_id > 0) {
            $this->line("✓ Se mapearon $con_user_id pedidos con asesoras");
            $this->line("✓ Se mapearon $con_cliente_id pedidos con clientes");
            $this->info("\n✓ Mapeo completado exitosamente!");
        } else {
            $this->warn("\n⚠️ No hay mapeos aún. Ejecuta:");
            $this->warn("   php artisan mapear:asesoras-clientes-tabla-original");
        }

        // 8. ADVERTENCIAS
        if ($total_clientes < 900) {
            $this->warn("\n⚠️ Menos de 900 clientes. Se esperaban ~949");
        }

        if ($con_user_id === 0 && $con_cliente_id === 0 && $total_pedidos > 0) {
            $this->warn("\n⚠️ Hay $total_pedidos pedidos pero sin mapeo. Revisa el comando.");
        }
    }
}
