<?php

namespace Database\Seeders;

use App\Models\TipoPrenda;
use App\Models\PrendaVariacionesDisponibles;
use Illuminate\Database\Seeder;

class TipoPrendaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // JEAN
        $jean = TipoPrenda::create([
            'nombre' => 'JEAN',
            'codigo' => 'JEANS',
            'palabras_clave' => ['JEAN', 'JEANS', 'NAPOLES', 'DRILL', 'OXFORD'],
            'descripcion' => 'Pantalón de jean',
            'activo' => true
        ]);

        PrendaVariacionesDisponibles::create([
            'tipo_prenda_id' => $jean->id,
            'tiene_manga' => false,
            'tiene_bolsillos' => false, // JEAN ya los tiene definidos
            'tiene_broche' => true,     // SOLO ESTO
            'tiene_reflectivo' => false,
            'tiene_cuello' => false
        ]);

        // CAMISA
        $camisa = TipoPrenda::create([
            'nombre' => 'CAMISA',
            'codigo' => 'SHIRT',
            'palabras_clave' => ['CAMISA', 'SHIRT', 'DRILL', 'OXFORD', 'LINO'],
            'descripcion' => 'Camisa de trabajo',
            'activo' => true
        ]);

        PrendaVariacionesDisponibles::create([
            'tipo_prenda_id' => $camisa->id,
            'tiene_manga' => true,
            'tiene_bolsillos' => true,
            'tiene_broche' => true,
            'tiene_reflectivo' => true,
            'tiene_cuello' => true
        ]);

        // CAMISETA
        $camiseta = TipoPrenda::create([
            'nombre' => 'CAMISETA',
            'codigo' => 'TSHIRT',
            'palabras_clave' => ['CAMISETA', 'TSHIRT', 'JERSEY', 'ALGODÓN'],
            'descripcion' => 'Camiseta básica',
            'activo' => true
        ]);

        PrendaVariacionesDisponibles::create([
            'tipo_prenda_id' => $camiseta->id,
            'tiene_manga' => true,
            'tiene_bolsillos' => false,
            'tiene_broche' => false,
            'tiene_reflectivo' => true,
            'tiene_cuello' => false
        ]);

        // PANTALÓN
        $pantalon = TipoPrenda::create([
            'nombre' => 'PANTALÓN',
            'codigo' => 'PANTS',
            'palabras_clave' => ['PANTALÓN', 'PANTS', 'DRILL', 'OXFORD'],
            'descripcion' => 'Pantalón de trabajo',
            'activo' => true
        ]);

        PrendaVariacionesDisponibles::create([
            'tipo_prenda_id' => $pantalon->id,
            'tiene_manga' => false,
            'tiene_bolsillos' => true,
            'tiene_broche' => true,
            'tiene_reflectivo' => true,
            'tiene_cuello' => false
        ]);

        // POLO
        $polo = TipoPrenda::create([
            'nombre' => 'POLO',
            'codigo' => 'POLO',
            'palabras_clave' => ['POLO', 'PIQUE', 'JERSEY'],
            'descripcion' => 'Polo de trabajo',
            'activo' => true
        ]);

        PrendaVariacionesDisponibles::create([
            'tipo_prenda_id' => $polo->id,
            'tiene_manga' => true,
            'tiene_bolsillos' => false,
            'tiene_broche' => true,
            'tiene_reflectivo' => true,
            'tiene_cuello' => true
        ]);
    }
}
