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
            OperariosCortadoresSeeder::class, // Operarios de corte con IDs fijos (3, 4, 5)
            HorasSeeder::class,
            MaquinasTelasSeeder::class, // Seeder consolidado para máquinas, telas y tiempos de ciclo
        ]);
    }
}
