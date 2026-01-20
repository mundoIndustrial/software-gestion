<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddAprobadorCotizacionesRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si el rol ya existe
        $exists = DB::table('roles')->where('name', 'aprobador_cotizaciones')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'aprobador_cotizaciones',
                'description' => 'Aprobador de Cotizaciones - Puede aprobar o rechazar cotizaciones pendientes',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info(' Rol aprobador_cotizaciones agregado exitosamente');
        } else {
            $this->command->warn(' El rol aprobador_cotizaciones ya existe');
        }
    }
}
