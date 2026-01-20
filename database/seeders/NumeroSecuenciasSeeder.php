<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NumeroSecuenciasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Inicializar secuencias para cotizaciones
        $secuencias = [
            ['tipo' => 'cotizaciones_prenda', 'siguiente' => 1],
            ['tipo' => 'cotizaciones_bordado', 'siguiente' => 1],
            ['tipo' => 'cotizaciones_general', 'siguiente' => 1],
        ];

        foreach ($secuencias as $secuencia) {
            $existe = DB::table('numero_secuencias')
                ->where('tipo', $secuencia['tipo'])
                ->first();
            
            if (!$existe) {
                DB::table('numero_secuencias')->insert([
                    'tipo' => $secuencia['tipo'],
                    'siguiente' => $secuencia['siguiente'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        echo " Secuencias de cotizaciones inicializadas\n";
    }
}
