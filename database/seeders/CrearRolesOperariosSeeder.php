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

        // Crear rol Bodeguero
        Role::firstOrCreate(
            ['name' => 'bodeguero'],
            [
                'description' => 'Operario encargado de la bodega - Visualización de recibos',
                'requires_credentials' => false,
            ]
        );

        // Crear rol Costura-Reflectivo
        Role::firstOrCreate(
            ['name' => 'costura-reflectivo'],
            [
                'description' => 'Operario encargado del área de costura reflexiva',
                'requires_credentials' => false,
            ]
        );

        // Crear rol Vista Costura
        Role::firstOrCreate(
            ['name' => 'vista-costura'],
            [
                'description' => 'Operario con acceso a la vista de costura',
                'requires_credentials' => false,
            ]
        );

        // Crear rol Confección Sobremedida
        Role::firstOrCreate(
            ['name' => 'confeccion-sobremedida'],
            [
                'description' => 'Operario encargado de la confección sobremedida',
                'requires_credentials' => false,
            ]
        );

        // Crear rol Lider-Reflectivo
        Role::firstOrCreate(
            ['name' => 'lider-reflectivo'],
            [
                'description' => 'Líder encargado del área de costura reflexiva',
                'requires_credentials' => false,
            ]
        );

        $this->command->info(' Roles de operarios creados exitosamente');
    }
}
