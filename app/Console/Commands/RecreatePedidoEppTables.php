<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecreatePedidoEppTables extends Command
{
    protected $signature = 'pedido-epp:recreate';
    protected $description = 'Recrea las tablas pedido_epp y pedido_epp_imagenes';

    public function handle()
    {
        $this->info(' Recreando tablas de pedido_epp...\n');

        // Eliminar los registros de migraciones para que se puedan ejecutar de nuevo
        $migracionesAEliminar = [
            '2026_01_17_create_pedido_epp_table',
            '2026_01_17_create_pedido_epp_imagenes_table',
            '2026_01_17_create_pedido_epps_table',
        ];

        foreach ($migracionesAEliminar as $migracion) {
            DB::table('migrations')->where('migration', $migracion)->delete();
            $this->line(" Eliminado registro: $migracion");
        }

        $this->info("\nEjecutando migraciones...\n");

        // Ejecutar la migración
        $this->call('migrate', ['--path' => 'database/migrations/2026_01_17_create_pedido_epp_table.php']);
        $this->call('migrate', ['--path' => 'database/migrations/2026_01_17_create_pedido_epp_imagenes_table.php']);

        $this->info("\n Tablas recreadas exitosamente");
    }
}
