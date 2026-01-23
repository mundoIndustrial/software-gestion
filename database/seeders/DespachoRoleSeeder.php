<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class DespachoRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear rol Despacho si no existe
        Role::firstOrCreate(
            ['name' => 'Despacho'],
            [
                'description' => 'Usuario responsable de controlar entregas parciales de prendas y EPP',
                'requires_credentials' => false,
            ]
        );

        echo "✅ Rol Despacho creado/verificado correctamente\n";
    }
}
