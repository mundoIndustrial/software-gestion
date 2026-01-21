<?php

/**
 * Procesador de Listado Desestructurado de Artículos
 */

$datosRudos = <<<EOD
"SEÑALIZACION DE DISTANCIAMIENTO SOCIAL 26*21"
""
"ALCOHOL 1000ML"
""
"ALCOHOL ANTISEPTICO DE 700 ML"
""
"CASQUETE PORTAVISOR AMARILLO C/RATCHET"
"STEELPRO"
""
"BOT TORINO CAFE C/P T:42"
""
"ESCOBAS SIN PALO"
""
EOD;

class ProcesadorArticulos {
    private $datos = [];
    
    public function procesar($texto) {
        // Limpiar líneas
        $lineas = array_filter(
            explode("\n", $texto),
            fn($l) => trim($l) !== '""' && trim($l) !== ''
        );
        
        $articulos = [];
        $actual = [];
        
        foreach ($lineas as $linea) {
            $linea = trim($linea, '" ');
            
            if (empty($linea)) continue;
            
            // Detectar si es una línea de continuación (marca, referencia, etc)
            if ($this->esContinuacion($linea)) {
                if (!empty($actual)) {
                    $actual['detalles'][] = $linea;
                }
            } else {
                // Es un nuevo artículo
                if (!empty($actual)) {
                    $articulos[] = $this->estructurar($actual);
                }
                $actual = ['nombre' => $linea, 'detalles' => []];
            }
        }
        
        if (!empty($actual)) {
            $articulos[] = $this->estructurar($actual);
        }
        
        return $articulos;
    }
    
    private function esContinuacion($linea) {
        $linea = strtoupper($linea);
        
        // Palabras clave que indican continuación
        $palabras = ['STEELPRO', 'ARMADURA', 'MARCA', 'REF', 'RFC:', 'C/P', 'S/P', 'M/L', 'CAB', 'DAM', 'TALLA', 'T:', 'DAMA', 'CABALLERO'];
        
        foreach ($palabras as $palabra) {
            if (strpos($linea, $palabra) !== false) {
                return true;
            }
        }
        
        // Si tiene números y es corto, probablemente sea marca/ref
        if (strlen($linea) < 50 && preg_match('/\d/', $linea)) {
            return true;
        }
        
        return false;
    }
    
    private function estructurar($item) {
        $nombre = $item['nombre'];
        $detalles = implode(' ', $item['detalles']);
        
        // Extraer información
        $color = $this->extraerColor($nombre . ' ' . $detalles);
        $marca = $this->extraerMarca($detalles);
        $material = $this->extraerMaterial($nombre . ' ' . $detalles);
        $talla = $this->extraerTalla($nombre . ' ' . $detalles);
        $medida = $this->extraerMedida($nombre);
        $referencia = $this->extraerReferencia($nombre . ' ' . $detalles);
        
        return [
            'nombre' => trim($nombre),
            'marca' => $marca,
            'color' => $color,
            'material' => $material,
            'talla' => $talla,
            'medida' => $medida,
            'referencia' => $referencia,
            'detalles' => $detalles
        ];
    }
    
    private function extraerColor($texto) {
        $colores = ['NEGRO', 'BLANCO', 'ROJO', 'AZUL', 'AMARILLO', 'VERDE', 'GRIS', 'NARANJA', 'CAFE', 'MARRÓN', 'ROSA', 'VINOTINTO', 'A/MARINO', 'TURQUEZA', 'ORO', 'CHOCOLATE', 'MARRON'];
        
        foreach ($colores as $color) {
            if (stripos($texto, $color) !== false) {
                return $color;
            }
        }
        return '';
    }
    
    private function extraerMarca($texto) {
        $marcas = ['STEELPRO', 'NIKE', 'SAGA', 'KONDOR', 'GRULLA', 'WARRIOR', 'ARMADURA', 'BRAHMA', 'WELDER', 'NORSEG', 'INDIANA', 'ROGER', 'ROBUSTA', 'TROOPER', 'TOLEDO'];
        
        foreach ($marcas as $marca) {
            if (stripos($texto, $marca) !== false) {
                return $marca;
            }
        }
        return '';
    }
    
    private function extraerMaterial($texto) {
        $materiales = ['ALGODÓN', 'VAQUETA', 'CUERO', 'CAUCHO', 'LATEX', 'NITRILO', 'NEOPRENO', 'POLIURETANO', 'DRIL', 'LONA', 'CARNAZA', 'ACERO', 'POLYCARBONATO'];
        
        foreach ($materiales as $material) {
            if (stripos($texto, $material) !== false) {
                return $material;
            }
        }
        return '';
    }
    
    private function extraerTalla($texto) {
        // Buscar patrones como T:XX o TALLA XX
        if (preg_match('/T[:\s]+(\d+|S|M|L|XL|XXL|XXXL)/i', $texto, $matches)) {
            return strtoupper($matches[1]);
        }
        return '';
    }
    
    private function extraerMedida($texto) {
        // Buscar medidas como 26*21, 14", etc
        if (preg_match('/(\d+[\*x]\d+)|(\d+")|(\d+\s*cm)/i', $texto, $matches)) {
            return $matches[0];
        }
        return '';
    }
    
    private function extraerReferencia($texto) {
        // Buscar REF: 12345 o REF.12345 o referencias
        if (preg_match('/REF[:\.\s]+([A-Z0-9\-\.]+)/i', $texto, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
    
    public function aCSV($articulos) {
        $csv = "nombre,marca,color,material,talla,medida,referencia,detalles\n";
        
        foreach ($articulos as $art) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $art['nombre']),
                str_replace('"', '""', $art['marca']),
                str_replace('"', '""', $art['color']),
                str_replace('"', '""', $art['material']),
                str_replace('"', '""', $art['talla']),
                str_replace('"', '""', $art['medida']),
                str_replace('"', '""', $art['referencia']),
                str_replace('"', '""', $art['detalles'])
            );
        }
        
        return $csv;
    }
}

// Procesar datos
$procesador = new ProcesadorArticulos();
$articulos = $procesador->procesar($datosRudos);
$csv = $procesador->aCSV($articulos);

// Guardar archivo
$archivo = 'C:\\Users\\Usuario\\Documents\\mundoindustrial\\articulos_procesados.csv';
file_put_contents($archivo, $csv);

echo "<h1>✅ Datos procesados exitosamente</h1>";
echo "<p><strong>Total de artículos:</strong> " . count($articulos) . "</p>";
echo "<p><strong>Archivo guardado:</strong> articulos_procesados.csv</p>";
echo "<hr>";
echo "<h2>Primeros 10 artículos:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Nombre</th><th>Marca</th><th>Color</th><th>Material</th><th>Talla</th><th>Medida</th></tr>";

for ($i = 0; $i < min(10, count($articulos)); $i++) {
    $art = $articulos[$i];
    echo sprintf(
        "<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>",
        htmlspecialchars($art['nombre']),
        htmlspecialchars($art['marca']),
        htmlspecialchars($art['color']),
        htmlspecialchars($art['material']),
        htmlspecialchars($art['talla']),
        htmlspecialchars($art['medida'])
    );
}

echo "</table>";
echo "<hr>";
echo "<p>📥 <strong>CSV generado:</strong> Cópialo y pégalo en el analizador</p>";
echo "<textarea readonly style='width:100%; height:300px;'>" . htmlspecialchars($csv) . "</textarea>";
?>
