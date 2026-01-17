<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            RolesSeeder::class,
            CrearRolesMixtoSeeder::class,
            HorarioPorRolesSeeder::class,
            OperariosCortadoresSeeder::class, // Operarios de corte con IDs fijos (3, 4, 5)
            HorasSeeder::class,
            MaquinasTelasSeeder::class, // Seeder consolidado para máquinas, telas y tiempos de ciclo
            ComponentePrendaSeeder::class, // Seeder para componentes de prendas
            CotizacionSeeder::class, // Seeder para cotizaciones del módulo contador
            FormatoCotizacionSeeder::class, // Seeder para formatos de cotización
            InventarioTelasSeeder::class, // Seeder para inventario de telas con stock
            EppSeeder::class, // Seeder para EPP con categorías e imágenes
        ]);
    }
}
