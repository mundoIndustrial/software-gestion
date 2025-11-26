<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar tipos de prenda base si la tabla está vacía
        $tiposExistentes = DB::table('tipos_prenda')->count();
        
        if ($tiposExistentes === 0) {
            DB::table('tipos_prenda')->insert([
                [
                    'nombre' => 'CAMISA',
                    'codigo' => 'CAMISA',
                    'palabras_clave' => json_encode(['CAMISA', 'POLO', 'CAMISETA']),
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nombre' => 'PANTALÓN',
                    'codigo' => 'PANTALON',
                    'palabras_clave' => json_encode(['PANTALÓN', 'JEAN', 'JEANS', 'PANTALON']),
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nombre' => 'FALDA',
                    'codigo' => 'FALDA',
                    'palabras_clave' => json_encode(['FALDA']),
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nombre' => 'CHAQUETA',
                    'codigo' => 'CHAQUETA',
                    'palabras_clave' => json_encode(['CHAQUETA', 'SACO', 'BLAZER']),
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'nombre' => 'OTRO',
                    'codigo' => 'OTRO',
                    'palabras_clave' => json_encode(['OTRO', 'GENERICO', 'GENERAL']),
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada en down para no perder datos
    }
};
