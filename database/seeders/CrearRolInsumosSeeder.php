<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrearRolInsumosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear rol de insumos si no existe
        DB::table('roles')->updateOrInsert(
            ['name' => 'insumos'],
            [
                'name' => 'insumos',
                'description' => 'Rol para gestión de insumos y materiales',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        echo "Rol 'insumos' creado o actualizado correctamente.\n";
    }
}
