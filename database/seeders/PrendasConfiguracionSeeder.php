<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PrendasConfiguracionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Categorías
        $categorias = [
            [
                'nombre' => 'Parte Superior',
                'slug' => 'parte-superior',
                'descripcion' => 'Prendas para la parte superior del cuerpo',
                'orden' => 1
            ],
            [
                'nombre' => 'Parte Inferior',
                'slug' => 'parte-inferior',
                'descripcion' => 'Prendas para la parte inferior del cuerpo',
                'orden' => 2
            ],
            [
                'nombre' => 'Conjunto Completo',
                'slug' => 'conjunto-completo',
                'descripcion' => 'Conjuntos y uniformes completos',
                'orden' => 3
            ],
            [
                'nombre' => 'Personalizado',
                'slug' => 'personalizado',
                'descripcion' => 'Configuración personalizada',
                'orden' => 4
            ],
        ];

        foreach ($categorias as $categoria) {
            \DB::table('categorias_prendas')->insert($categoria);
        }

        // Tipos de Prendas - PARTE SUPERIOR
        $tiposSuperiores = [
            ['nombre' => 'Camisa', 'slug' => 'camisa', 'opciones_disponibles' => json_encode(['cuello', 'manga', 'bolsillos', 'puños', 'cierre'])],
            ['nombre' => 'Polo', 'slug' => 'polo', 'opciones_disponibles' => json_encode(['cuello', 'manga', 'bolsillos'])],
            ['nombre' => 'Camiseta', 'slug' => 'camiseta', 'opciones_disponibles' => json_encode(['cuello', 'manga'])],
            ['nombre' => 'Chaqueta', 'slug' => 'chaqueta', 'opciones_disponibles' => json_encode(['cuello', 'manga', 'bolsillos', 'puños', 'cierre'])],
            ['nombre' => 'Chaleco', 'slug' => 'chaleco', 'opciones_disponibles' => json_encode(['cuello', 'bolsillos', 'cierre'])],
            ['nombre' => 'Blusa', 'slug' => 'blusa', 'opciones_disponibles' => json_encode(['cuello', 'manga', 'cierre'])],
            ['nombre' => 'Sudadera', 'slug' => 'sudadera', 'opciones_disponibles' => json_encode(['cuello', 'manga', 'bolsillos', 'puños'])],
        ];

        $categoriaSuperId = \DB::table('categorias_prendas')->where('slug', 'parte-superior')->value('id');
        foreach ($tiposSuperiores as $index => $tipo) {
            \DB::table('tipos_prendas')->insert([
                'categoria_id' => $categoriaSuperId,
                'nombre' => $tipo['nombre'],
                'slug' => $tipo['slug'],
                'opciones_disponibles' => $tipo['opciones_disponibles'],
                'orden' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Tipos de Prendas - PARTE INFERIOR
        $tiposInferiores = [
            ['nombre' => 'Pantalón', 'slug' => 'pantalon', 'opciones_disponibles' => json_encode(['bolsillos', 'cierre'])],
            ['nombre' => 'Jean', 'slug' => 'jean', 'opciones_disponibles' => json_encode(['bolsillos', 'cierre'])],
            ['nombre' => 'Short', 'slug' => 'short', 'opciones_disponibles' => json_encode(['bolsillos', 'cierre'])],
            ['nombre' => 'Falda', 'slug' => 'falda', 'opciones_disponibles' => json_encode(['cierre'])],
            ['nombre' => 'Leggins', 'slug' => 'leggins', 'opciones_disponibles' => json_encode([])],
        ];

        $categoriaInferId = \DB::table('categorias_prendas')->where('slug', 'parte-inferior')->value('id');
        foreach ($tiposInferiores as $index => $tipo) {
            \DB::table('tipos_prendas')->insert([
                'categoria_id' => $categoriaInferId,
                'nombre' => $tipo['nombre'],
                'slug' => $tipo['slug'],
                'opciones_disponibles' => $tipo['opciones_disponibles'],
                'orden' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Tipos de Prendas - CONJUNTO COMPLETO
        $tiposConjuntos = [
            ['nombre' => 'Overol', 'slug' => 'overol', 'opciones_disponibles' => json_encode(['cuello', 'manga', 'bolsillos', 'cierre'])],
            ['nombre' => 'Traje de Bioseguridad', 'slug' => 'traje-bioseguridad', 'opciones_disponibles' => json_encode(['cuello', 'manga', 'cierre'])],
            ['nombre' => 'Uniforme Completo', 'slug' => 'uniforme-completo', 'opciones_disponibles' => json_encode(['cuello', 'manga', 'bolsillos', 'cierre'])],
            ['nombre' => 'Pijama', 'slug' => 'pijama', 'opciones_disponibles' => json_encode(['cuello', 'manga', 'bolsillos'])],
            ['nombre' => 'Buzo Deportivo', 'slug' => 'buzo-deportivo', 'opciones_disponibles' => json_encode(['cuello', 'manga', 'bolsillos', 'puños', 'cierre'])],
        ];

        $categoriaConjuntoId = \DB::table('categorias_prendas')->where('slug', 'conjunto-completo')->value('id');
        foreach ($tiposConjuntos as $index => $tipo) {
            \DB::table('tipos_prendas')->insert([
                'categoria_id' => $categoriaConjuntoId,
                'nombre' => $tipo['nombre'],
                'slug' => $tipo['slug'],
                'opciones_disponibles' => $tipo['opciones_disponibles'],
                'orden' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
