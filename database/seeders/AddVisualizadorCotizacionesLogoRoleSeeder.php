<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddVisualizadorCotizacionesLogoRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si el rol ya existe
        $exists = DB::table('roles')->where('name', 'visualizador_cotizaciones_logo')->exists();

        if (!$exists) {
            DB::table('roles')->insert([
                'name' => 'visualizador_cotizaciones_logo',
                'description' => 'Visualizador de Cotizaciones Logo - Solo puede ver cotizaciones tipo Logo (L) y PDFs de logo de cotizaciones combinadas (PL)',
                'requires_credentials' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✅ Rol visualizador_cotizaciones_logo agregado exitosamente');
        } else {
            $this->command->warn('⚠️ El rol visualizador_cotizaciones_logo ya existe');
        }
    }
}
