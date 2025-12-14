<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class AddSupervisorAsesoresAndCostureroRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Inserta los roles:
     * - supervisor_asesores: Supervisor de Asesores
     * - costurero: Operario de costura
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'supervisor_asesores',
                'description' => 'Supervisor de Asesores - Gestión de cotizaciones y pedidos de todos los asesores',
                'requires_credentials' => true,
            ],
            [
                'name' => 'costurero',
                'description' => 'Operario de costura',
                'requires_credentials' => false,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'description' => $roleData['description'],
                    'requires_credentials' => $roleData['requires_credentials'],
                ]
            );
        }

        echo "\n✅ Roles insertados correctamente:\n";
        echo "   - supervisor_asesores\n";
        echo "   - costurero\n\n";
    }
}
