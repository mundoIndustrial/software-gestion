<?php

namespace App\Helpers;

class DescripcionPrendaHelper
{
    /**
     * Genera descripción formateada de una prenda según template especificado
     * 
     * @param array $prenda Array con estructura: [
     *      'numero' => int,
     *      'tipo' => string (nombre_prenda),
     *      'color' => string,
     *      'tela' => string,
     *      'ref' => string (referencia tela),
     *      'manga' => string,
     *      'logo' => string,
     *      'bolsillos' => array de strings,
     *      'reflectivos' => array de strings,
     *      'otros' => array de strings,
     *      'tallas' => array ['talla' => cantidad]
     * ]
     * @return string
     */
    public static function generarDescripcion(array $prenda): string
    {
        // Validar que tengamos los datos mínimos
        $numero = $prenda['numero'] ?? 1;
        $tipo = strtoupper($prenda['tipo'] ?? '');
        $color = $prenda['color'] ?? '';
        $tela = $prenda['tela'] ?? '';
        $ref = $prenda['ref'] ?? '';
        $manga = $prenda['manga'] ?? '';
        $logo = $prenda['logo'] ?? '';
        
        // Procesar listas
        $bolsillos = $prenda['bolsillos'] ?? [];
        $reflectivos = $prenda['reflectivos'] ?? [];
        $otros = $prenda['otros'] ?? [];
        $tallas = $prenda['tallas'] ?? [];

        // Formatear bolsillos
        $bolsillosFormato = '';
        if (!empty($bolsillos)) {
            $bolsillosLista = array_map(function($b) {
                return "• " . trim($b);
            }, $bolsillos);
            $bolsillosFormato = implode("\n", $bolsillosLista);
        }

        // Formatear reflectivos
        $reflectivosFormato = '';
        if (!empty($reflectivos)) {
            $reflectivosLista = array_map(function($r) {
                return "• " . trim($r);
            }, $reflectivos);
            $reflectivosFormato = implode("\n", $reflectivosLista);
        }

        // Formatear otros
        $otrosFormato = '';
        if (!empty($otros)) {
            $otrosLista = array_map(function($o) {
                return "• " . trim($o);
            }, $otros);
            $otrosFormato = implode("\n", $otrosLista);
        }

        // Formatear tallas
        $tallasFormato = '';
        if (!empty($tallas) && is_array($tallas)) {
            $tallasList = [];
            foreach ($tallas as $talla => $cant) {
                if ($cant > 0) {
                    $tallasList[] = "- {$talla}: {$cant}";
                }
            }
            if (!empty($tallasList)) {
                $tallasFormato = implode("\n", $tallasList);
            }
        }

        // Construir referencia de tela
        $telaRef = $tela;
        if ($ref) {
            $telaRef .= " {$ref}";
        }

        // Construir descripción completa
        $descripcion = "{$numero}: {$tipo}";
        
        if ($color || $tela || $manga) {
            $atributos = [];
            if ($color) $atributos[] = "Color: {$color}";
            if ($telaRef) $atributos[] = "Tela: {$telaRef}";
            if ($manga) $atributos[] = "Manga: {$manga}";
            $descripcion .= "\n" . implode(" | ", $atributos);
        }

        $descripcion .= "\n\nDESCRIPCIÓN:";
        if ($logo) {
            $descripcion .= "\n- Logo: {$logo}";
        }

        if ($bolsillosFormato) {
            $descripcion .= "\n\nBolsillos:\n{$bolsillosFormato}";
        }

        if ($reflectivosFormato) {
            $descripcion .= "\n\nReflectivo:\n{$reflectivosFormato}";
        }

        if ($otrosFormato) {
            $descripcion .= "\n\nOtros detalles:\n{$otrosFormato}";
        }

        if ($tallasFormato) {
            $descripcion .= "\n\nTALLAS:\n{$tallasFormato}";
        }

        return trim($descripcion);
    }
}

use App\Helpers\DescripcionPrendaHelper;

/**
 * Script de prueba para validar el funcionamiento del Helper
 * Ejecutar: php demo-descripcion.php
 */

// Ejemplo 1: Descripción completa
echo "=== EJEMPLO 1: Descripción Completa ===\n\n";

$prenda1 = [
    'numero' => 1,
    'tipo' => 'Camisa Drill',
    'color' => 'Naranja',
    'tela' => 'Drill Borneo',
    'ref' => 'REF-DB-001',
    'manga' => 'Larga',
    'logo' => 'Logo bordado en espalda',
    'bolsillos' => ['Pecho', 'Espalda'],
    'reflectivos' => ['Mangas', 'Puños'],
    'otros' => ['Refuerzo en cuello', 'Costuras reforzadas'],
    'tallas' => ['S' => 50, 'M' => 50, 'L' => 50],
];

echo DescripcionPrendaHelper::generarDescripcion($prenda1);
echo "\n\n";

// Ejemplo 2: Descripción mínima
echo "=== EJEMPLO 2: Descripción Mínima ===\n\n";

$prenda2 = [
    'numero' => 2,
    'tipo' => 'Jeans',
    'color' => 'Azul',
    'tela' => 'Denim',
    'ref' => '',
    'manga' => 'Larga',
    'logo' => '',
    'bolsillos' => [],
    'reflectivos' => [],
    'otros' => [],
    'tallas' => ['S' => 100, 'L' => 80],
];

echo DescripcionPrendaHelper::generarDescripcion($prenda2);
echo "\n\n";

// Ejemplo 3: Descripción con solo algunos detalles
echo "=== EJEMPLO 3: Descripción Parcial ===\n\n";

$prenda3 = [
    'numero' => 3,
    'tipo' => 'Polo',
    'color' => 'Blanco',
    'tela' => 'Pique',
    'ref' => 'REF-PQ-100',
    'manga' => 'Corta',
    'logo' => 'Bordado pecho izquierdo',
    'bolsillos' => ['Pecho'],
    'reflectivos' => [],
    'otros' => ['Botones de concha'],
    'tallas' => ['XS' => 25, 'S' => 50, 'M' => 75, 'L' => 50, 'XL' => 25],
];

echo DescripcionPrendaHelper::generarDescripcion($prenda3);
echo "\n";

echo "✅ Script de prueba completado exitosamente\n";

// Ejemplo 4: Ejemplo Real - Camisa Drill con tus datos
echo "=== EJEMPLO 4: Tu Caso Real - Camisa Drill ===\n\n";

$prenda4 = [
    'numero' => 1,
    'tipo' => 'CAMISA DRILL',
    'color' => 'Naranja',
    'tela' => 'Drill',
    'ref' => 'REF:ref-222',
    'manga' => 'PRUEBA DE MANGA',
    'logo' => '',
    'bolsillos' => ['SI - LLEVA BOLSILLOS CON TAPA BOTON Y OJAL CON LOGOS BORDADOS DENTRO DEL BOLSILLO DERECHO "TRANSPORTE"  BOLSILLO IZQUIERDO "AN'],
    'reflectivos' => ['SI - CON REFLECTIVO GRIS 2" DE 25 CICLOS EN H EN LA PARTE DELANTERA Y TRASERA 2 VUELTAS EN CADA BRAZO  Y UNA LINEA A LA ALTURA DEL OMBLIGO'],
    'otros' => [],
    'tallas' => ['XS' => 50, 'S' => 50],
];

echo DescripcionPrendaHelper::generarDescripcion($prenda4);
echo "\n";

