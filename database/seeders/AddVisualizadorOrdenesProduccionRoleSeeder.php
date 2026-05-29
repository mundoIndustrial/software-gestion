<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddVisualizadorOrdenesProduccionRoleSeeder extends Seeder
{
    public function run(): void
    {
        $exists = DB::table('roles')->where('name', 'visualizador_ordenes_produccion')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'visualizador_ordenes_produccion',
                'description' => 'Visualizador de ordenes de produccion (costura/reflectivo) en modo solo lectura',
                'requires_credentials' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command?->info(' Rol "visualizador_ordenes_produccion" creado exitosamente');
            return;
        }

        $this->command?->warn(' El rol "visualizador_ordenes_produccion" ya existe');
    }
}
