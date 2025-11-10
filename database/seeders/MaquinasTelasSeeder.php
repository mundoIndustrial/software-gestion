<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Maquina;
use App\Models\Tela;
use App\Models\TiempoCiclo;

class MaquinasTelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar tablas existentes
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        TiempoCiclo::truncate();
        Tela::truncate();
        Maquina::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Crear máquinas
        $banana = Maquina::create(['nombre_maquina' => 'BANANA']);
        $vertical = Maquina::create(['nombre_maquina' => 'VERTICAL']);
        $tijeras = Maquina::create(['nombre_maquina' => 'TIJERAS']);

        // GRUPO 1: BANANA = 97, VERTICAL = 130, TIJERAS = 97
        $telasGrupo1 = [
            'NAFLIX',
            'POLUX',
            'POLO',
            'SHELSY',
            'HIDROTECH',
            'ALFONSO',
            'MADRIGAL',
            'SPORTWEAR',
            'NATIVA',
            'SUDADERA',
            'OXFORD VESTIR',
            'PANTALON DE VESTIR',
            'BRAGAS',
            'CONJUNTO ANTIFLUIDO',
            'BRAGAS DRILL',
            'SPEED',
            'PIQUE',
            'IGNIFUGO',
            'COFIAS',
            'BOLSA QUIRURGICA',
            'FORROS',
            'TOP PLUX',
            'NOVACRUM',
            'CEDACRON',
            'DACRON',
            'ENTRETELA',
            'NAUTICA',
            'CHAQUETA ORION',
            'MICRO TITAN',
            'SPRAY RIB',
            'DOBLE PUNTO',
        ];

        foreach ($telasGrupo1 as $nombreTela) {
            $tela = Tela::create(['nombre_tela' => $nombreTela]);
            
            // Crear tiempos de ciclo para cada máquina
            TiempoCiclo::create([
                'tela_id' => $tela->id,
                'maquina_id' => $banana->id,
                'tiempo_ciclo' => 97
            ]);
            
            TiempoCiclo::create([
                'tela_id' => $tela->id,
                'maquina_id' => $vertical->id,
                'tiempo_ciclo' => 130
            ]);
            
            TiempoCiclo::create([
                'tela_id' => $tela->id,
                'maquina_id' => $tijeras->id,
                'tiempo_ciclo' => 97
            ]);
        }

        // GRUPO 2: BANANA = 45, VERTICAL = 114, TIJERAS = 45
        $telasGrupo2 = [
            'OXFORD',
            'DRILL',
            'GOLIAT',
            'BOLSILLO',
            'SANSON',
            'PANTALON ORION',
            'SEGAL WIKING',
            'JEANS',
            'SHAMBRAIN',
            'NAPOLES',
            'DACRUM',
            'RETACEO DRILL',
        ];

        foreach ($telasGrupo2 as $nombreTela) {
            $tela = Tela::create(['nombre_tela' => $nombreTela]);
            
            // Crear tiempos de ciclo para cada máquina
            TiempoCiclo::create([
                'tela_id' => $tela->id,
                'maquina_id' => $banana->id,
                'tiempo_ciclo' => 45
            ]);
            
            TiempoCiclo::create([
                'tela_id' => $tela->id,
                'maquina_id' => $vertical->id,
                'tiempo_ciclo' => 114
            ]);
            
            TiempoCiclo::create([
                'tela_id' => $tela->id,
                'maquina_id' => $tijeras->id,
                'tiempo_ciclo' => 45
            ]);
        }

        $this->command->info('Seeder ejecutado exitosamente!');
        $this->command->info('Máquinas creadas: 3');
        $this->command->info('Telas creadas: ' . (count($telasGrupo1) + count($telasGrupo2)));
        $this->command->info('Tiempos de ciclo creados: ' . ((count($telasGrupo1) + count($telasGrupo2)) * 3));
    }
}
