<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RevisorEntregasRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'revisor_entregas'],
            [
                'name' => 'revisor_entregas',
                'description' => 'Revisor de Entregas - Visualización y aprobación de entregas',
                'requires_credentials' => true,
            ]
        );
    }
}
