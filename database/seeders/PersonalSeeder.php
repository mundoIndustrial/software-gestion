<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Personal;
use Illuminate\Support\Facades\DB;

class PersonalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cargar datos del archivo JSON
        $json_file = base_path('personas_data.json');
        
        if (file_exists($json_file)) {
            $personas_data = json_decode(file_get_contents($json_file), true);
            
            if (is_array($personas_data)) {
                // Desactivar verificación de claves foráneas temporalmente
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                
                // Limpiar tabla existente
                Personal::truncate();
                
                // Insertar datos
                foreach ($personas_data as $id_persona => $nombre_persona) {
                    Personal::create([
                        'id_persona' => $id_persona,
                        'nombre_persona' => $nombre_persona,
                    ]);
                }
                
                // Reactivar verificación de claves foráneas
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                
                echo count($personas_data) . " registros de personal insertados correctamente.\n";
            } else {
                echo "El archivo JSON no contiene un array válido.\n";
            }
        } else {
            echo "El archivo personas_data.json no existe en: $json_file\n";
        }
    }
}
