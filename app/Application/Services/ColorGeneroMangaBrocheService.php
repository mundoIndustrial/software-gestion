<?php

namespace App\Application\Services;

use App\Models\ColorPrenda;
use App\Models\GeneroPrenda;
use App\Models\TipoManga;
use App\Models\TipoBroche;

class ColorGeneroMangaBrocheService
{
    /**
     * Obtener o crear color
     */
    public function obtenerOCrearColor(string $nombre): ColorPrenda
    {
        if (empty($nombre)) {
            return null;
        }

        $nombreNormalizado = ucfirst(strtolower(trim($nombre)));

        return ColorPrenda::firstOrCreate(
            ['nombre' => $nombreNormalizado],
            [
                'nombre' => $nombreNormalizado,
                'codigo' => strtoupper(str_replace(' ', '_', $nombreNormalizado)),
                'activo' => true,
            ]
        );
    }

    /**
     * Obtener o crear género
     */
    public function obtenerOCrearGenero(string $nombre): GeneroPrenda
    {
        if (empty($nombre)) {
            return null;
        }

        $nombreNormalizado = ucfirst(strtolower(trim($nombre)));

        return GeneroPrenda::firstOrCreate(
            ['nombre' => $nombreNormalizado],
            [
                'nombre' => $nombreNormalizado,
                'activo' => true,
            ]
        );
    }

    /**
     * Obtener o crear manga por ID o nombre (case-insensitive)
     */
    public function obtenerOCrearManga($idONombre): TipoManga
    {
        if (empty($idONombre)) {
            return null;
        }

        // Si es numérico, buscar por ID
        if (is_numeric($idONombre)) {
            $manga = TipoManga::find($idONombre);
            if ($manga) {
                return $manga;
            }
        }

        // Buscar case-insensitive primero
        $nombreTrim = trim($idONombre);
        $manga = TipoManga::whereRaw('LOWER(nombre) = LOWER(?)', [$nombreTrim])->first();
        
        if ($manga) {
            return $manga;
        }

        // Si no existe, crear con normalización
        $nombreNormalizado = ucfirst(strtolower($nombreTrim));
        return TipoManga::create([
            'nombre' => $nombreNormalizado,
            'activo' => true,
        ]);
    }

    /**
     * Obtener o crear broche por ID o nombre (case-insensitive)
     */
    public function obtenerOCrearBroche($idONombre): TipoBroche
    {
        if (empty($idONombre)) {
            return null;
        }

        // Si es numérico, buscar por ID
        if (is_numeric($idONombre)) {
            $broche = TipoBroche::find($idONombre);
            if ($broche) {
                return $broche;
            }
        }

        // Buscar case-insensitive primero
        $nombreTrim = trim($idONombre);
        $broche = TipoBroche::whereRaw('LOWER(nombre) = LOWER(?)', [$nombreTrim])->first();
        
        if ($broche) {
            return $broche;
        }

        // Si no existe, crear con normalización
        $nombreNormalizado = ucfirst(strtolower($nombreTrim));
        return TipoBroche::create([
            'nombre' => $nombreNormalizado,
            'activo' => true,
        ]);
    }

    /**
     * Obtener todos los colores
     */
    public function obtenerColores(): array
    {
        return ColorPrenda::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    /**
     * Obtener todos los géneros
     */
    public function obtenerGeneros(): array
    {
        return GeneroPrenda::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    /**
     * Obtener todas las mangas
     */
    public function obtenerMangas(): array
    {
        return TipoManga::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    /**
     * Obtener todos los broches
     */
    public function obtenerBroches(): array
    {
        return TipoBroche::where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->toArray();
    }

    /**
     * Crear múltiples colores
     */
    public function crearColores(array $nombres): array
    {
        return array_map(
            fn($nombre) => $this->obtenerOCrearColor($nombre),
            $nombres
        );
    }

    /**
     * Crear múltiples géneros
     */
    public function crearGeneros(array $nombres): array
    {
        return array_map(
            fn($nombre) => $this->obtenerOCrearGenero($nombre),
            $nombres
        );
    }

    // ✅ ALIAS PARA COMPATIBILIDAD CON EL CONTROLADOR

    /**
     * Buscar o crear color (alias de obtenerOCrearColor)
     */
    public function buscarOCrearColor($nombre): ColorPrenda
    {
        return $this->obtenerOCrearColor($nombre);
    }

    /**
     * Buscar o crear tela (búsqueda case-insensitive en tabla colores)
     * Nota: Las telas se guardan como colores con una categoría o prefijo
     */
    public function buscarOCrearTela($nombre): ColorPrenda
    {
        if (empty($nombre)) {
            return null;
        }

        // Buscar case-insensitive primero
        $nombreTrim = trim($nombre);
        $tela = ColorPrenda::whereRaw('LOWER(nombre) = LOWER(?)', [$nombreTrim])->first();
        
        if ($tela) {
            return $tela;
        }

        // Si no existe, crear con normalización
        $nombreNormalizado = ucfirst(strtolower($nombreTrim));
        return ColorPrenda::create([
            'nombre' => $nombreNormalizado,
            'codigo' => strtoupper(str_replace(' ', '_', $nombreNormalizado)),
            'activo' => true,
        ]);
    }

    /**
     * Buscar o crear manga (alias de obtenerOCrearManga)
     */
    public function buscarOCrearManga($nombre): TipoManga
    {
        return $this->obtenerOCrearManga($nombre);
    }

    /**
     * Buscar o crear broche (alias de obtenerOCrearBroche)
     */
    public function buscarOCrearBroche($nombre): TipoBroche
    {
        return $this->obtenerOCrearBroche($nombre);
    }
}
