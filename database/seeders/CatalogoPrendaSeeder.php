<?php

namespace Database\Seeders;

use App\Models\ColorPrenda;
use App\Models\TelaPrenda;
use App\Models\GeneroPrenda;
use App\Models\TipoManga;
use App\Models\TipoBroche;
use Illuminate\Database\Seeder;

class CatalogoPrendaSeeder extends Seeder
{
    public function run(): void
    {
        // Colores
        $colores = ['Azul', 'Negro', 'Gris', 'Blanco', 'Naranja', 'Rojo', 'Verde', 'Amarillo'];
        foreach ($colores as $color) {
            ColorPrenda::firstOrCreate(['nombre' => $color]);
        }

        // Telas
        $telas = [
            ['nombre' => 'NAPOLES', 'referencia' => 'REF-NAP-001'],
            ['nombre' => 'DRILL BORNEO', 'referencia' => 'REF-DB-001'],
            ['nombre' => 'OXFORD', 'referencia' => 'REF-OX-001'],
            ['nombre' => 'JERSEY', 'referencia' => 'REF-JER-001'],
            ['nombre' => 'LINO', 'referencia' => 'REF-LIN-001'],
        ];
        foreach ($telas as $tela) {
            TelaPrenda::firstOrCreate(['nombre' => $tela['nombre']], $tela);
        }

        // Géneros
        $generos = ['Dama', 'Caballero', 'Unisex'];
        foreach ($generos as $genero) {
            GeneroPrenda::firstOrCreate(['nombre' => $genero]);
        }

        // Tipos de Manga
        $mangas = ['Larga', 'Corta', '3/4'];
        foreach ($mangas as $manga) {
            TipoManga::firstOrCreate(['nombre' => $manga]);
        }

        // Tipos de Broche
        $broches = ['Broche', 'Botón'];
        foreach ($broches as $broche) {
            TipoBroche::firstOrCreate(['nombre' => $broche]);
        }
    }
}
