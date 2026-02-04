<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgregarCosturaBodegaConsecutivoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existe el registro COSTURA-BODEGA
        $exists = DB::table('consecutivos_recibos')
            ->where('tipo_recibo', 'COSTURA-BODEGA')
            ->exists();

        if ($exists) {
            $this->command->info('COSTURA-BODEGA ya existe en consecutivos_recibos');
            return;
        }

        // Insertar el nuevo tipo de recibo COSTURA-BODEGA
        DB::table('consecutivos_recibos')->insert([
            'tipo_recibo' => 'COSTURA-BODEGA',
            'consecutivo_actual' => 0,
            'consecutivo_inicial' => 0,
            'año' => 2026,
            'activo' => 1,
            'notas' => 'Consecutivo para costura bodega - Configurar valor inicial',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('✓ Registro COSTURA-BODEGA agregado a consecutivos_recibos');
    }
}
