<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateMigration extends Command
{
    protected $signature = 'migrate:validate';
    protected $description = 'Valida que la migración de datos se completó correctamente';

    public function handle()
    {
        $this->info("\n");
        $this->info(str_repeat("=", 120));
        $this->info("VALIDACIÓN DE MIGRACIÓN COMPLETADA");
        $this->info(str_repeat("=", 120) . "\n");

        // Contar datos
        $usuariosCount = DB::table('users')->count();
        $clientesCount = DB::table('clientes')->count();
        $pedidosCount = DB::table('pedidos_produccion')->count();
        $prendasCount = DB::table('prendas_pedido')->count();
        $procesosCount = DB::table('procesos_prenda')->count();

        $this->info("📊 ESTADÍSTICAS DE MIGRACIÓN:\n");
        $this->line("   Usuarios (Asesoras): $usuariosCount");
        $this->line("   Clientes: $clientesCount");
        $this->line("   Pedidos: $pedidosCount");
        $this->line("   Prendas: $prendasCount");
        $this->line("   Procesos: $procesosCount\n");

        // Verificar relaciones
        $this->info("🔗 VERIFICACIÓN DE RELACIONES:\n");

        // Pedidos sin asesor
        $pedidosSinAsesor = DB::table('pedidos_produccion')->whereNull('asesor_id')->count();
        $this->line("   Pedidos sin asesor asignado: $pedidosSinAsesor");
        if ($pedidosSinAsesor > 0) {
            $this->warn("   ⚠️  Hay pedidos sin asesor");
        }

        // Pedidos sin cliente
        $pedidosSinCliente = DB::table('pedidos_produccion')->whereNull('cliente_id')->count();
        $this->line("   Pedidos sin cliente asignado: $pedidosSinCliente");
        if ($pedidosSinCliente > 0) {
            $this->warn("   ⚠️  Hay pedidos sin cliente");
        }

        // Prendas sin pedido
        $prendosSinPedido = DB::table('prendas_pedido')->whereNull('pedido_produccion_id')->count();
        $this->line("   Prendas sin pedido asignado: $prendosSinPedido");
        if ($prendosSinPedido > 0) {
            $this->warn("   ⚠️  Hay prendas sin pedido");
        }

        // Procesos sin prenda
        $proceosSinPrenda = DB::table('procesos_prenda')->whereNull('prenda_pedido_id')->count();
        $this->line("   Procesos sin prenda asignada: $proceosSinPrenda\n");
        if ($proceosSinPrenda > 0) {
            $this->warn("   ⚠️  Hay procesos sin prenda");
        }

        // Verificar datos completos
        $this->info("✅ INTEGRIDAD DE DATOS:\n");

        $pedidosCompletos = DB::table('pedidos_produccion')
            ->whereNotNull('asesor_id')
            ->whereNotNull('cliente_id')
            ->count();

        $porcentajePedidos = $pedidosCount > 0 ? round(($pedidosCompletos / $pedidosCount) * 100, 2) : 0;
        $this->line("   Pedidos con datos completos: $pedidosCompletos / $pedidosCount ($porcentajePedidos%)\n");

        // Resumen final
        $this->info(str_repeat("=", 120));
        
        if ($pedidosSinAsesor == 0 && $pedidosSinCliente == 0 && $prendosSinPedido == 0) {
            $this->info("✅ MIGRACIÓN VALIDADA EXITOSAMENTE");
            $this->info("   Todos los datos se migraron correctamente y las relaciones son válidas.");
        } else {
            $this->warn("⚠️  MIGRACIÓN CON ADVERTENCIAS");
            $this->line("   Se encontraron inconsistencias que podrían afectar la funcionalidad.");
        }

        $this->info(str_repeat("=", 120) . "\n");

        return 0;
    }
}
