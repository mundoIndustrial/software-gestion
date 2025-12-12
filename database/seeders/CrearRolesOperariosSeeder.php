<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class CrearRolesOperariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear rol Cortador
        Role::firstOrCreate(
            ['name' => 'cortador'],
            [
                'description' => 'Operario encargado del área de corte',
                'requires_credentials' => false,
            ]
        );

        // Crear rol Costurero
        Role::firstOrCreate(
            ['name' => 'costurero'],
            [
                'description' => 'Operario encargado del área de costura',
                'requires_credentials' => false,
            ]
        );

        $this->command->info('✅ Roles de operarios creados exitosamente');
    }
}
