<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddVisualizadorTalleresRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $exists = DB::table('roles')->where('name', 'visualizador_talleres')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'visualizador_talleres',
                'description' => 'Visualizador de Talleres - Solo puede visualizar talleres sin modificar datos',
                'requires_credentials' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command?->info('Rol "visualizador_talleres" creado exitosamente');
        } else {
            $this->command?->warn('El rol "visualizador_talleres" ya existe');
        }
    }
}
