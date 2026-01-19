<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PorteriaRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear el rol "porteria"
        $rolId = DB::table('roles')->insertGetId([
            'name' => 'porteria',
            'description' => 'Personal de portería - Trabaja corrido desde entrada hasta salida',
            'requires_credentials' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear el horario para el rol "porteria"
        DB::table('horario_por_roles')->insert([
            'id_rol' => $rolId,
            'entrada_manana' => '08:00:00',
            'salida_manana' => null,
            'entrada_tarde' => null,
            'salida_tarde' => '18:00:00',
            'entrada_sabado' => '07:00:00',
            'salida_sabado' => '15:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Rol "porteria" y su horario han sido creados exitosamente.');
    }
}
