<?php

/**
 * Analizador de Artículos - Extractor PDF y Detector de Duplicaciones
 * Compatible con Laravel
 */

class AnalizadorArticulos
{
    private $datos = [];
    private $encabezados = [];
    
    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Procesa datos desde CSV
     */
    public function procesarCSV($contenido)
    {
        $lineas = array_filter(explode("\n", $contenido));
        if (empty($lineas)) return false;

        $this->encabezados = str_getcsv(array_shift($lineas));
        
        foreach ($lineas as $linea) {
            $valores = str_getcsv($linea);
            if (count($valores) === count($this->encabezados)) {
                $fila = [];
                foreach ($this->encabezados as $i => $encabezado) {
                    $fila[$encabezado] = $valores[$i] ?? '';
                }
                $this->datos[] = $fila;
            }
        }

        return true;
    }

    /**
     * Procesa datos desde JSON
     */
    public function procesarJSON($contenido)
    {
        $datos = json_decode($contenido, true);
        if (!is_array($datos)) return false;

        if (!empty($datos)) {
            $this->encabezados = array_keys($datos[0]);
            $this->datos = $datos;
        }

        return true;
    }

    /**
     * Analiza duplicaciones
     */
    public function analizarDuplicaciones()
    {
        $campos_relevantes = $this->detectarCamposRelevantes();
        
        if (empty($campos_relevantes)) {
            return [
                'grupos_duplicados' => [],
                'total_duplicaciones' => 0,
                'campos_detectados' => []
            ];
        }

        $grupos = [];

        foreach ($this->datos as $indice => $item) {
            $clave = '';
            foreach ($campos_relevantes as $campo) {
                $clave .= ($item[$campo] ?? 'N/A') . '|';
            }

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [];
            }
            $grupos[$clave][] = array_merge(['_indice' => $indice], $item);
        }

        // Filtrar solo los grupos duplicados (más de 1 item)
        $duplicados = array_filter($grupos, function($items) {
            return count($items) > 1;
        });

        // Ordenar por cantidad descendente
        usort($duplicados, function($a, $b) {
            return count($b) - count($a);
        });

        // Formatear resultado
        $resultado_duplicados = [];
        foreach ($duplicados as $clave => $items) {
            $criterios = [];
            foreach ($campos_relevantes as $campo) {
                $criterios[$campo] = $items[0][$campo] ?? 'N/A';
            }

            $resultado_duplicados[] = [
                'cantidad' => count($items),
                'criterios' => $criterios,
                'articulos' => $items
            ];
        }

        return [
            'grupos_duplicados' => $resultado_duplicados,
            'total_duplicaciones' => count($resultado_duplicados),
            'campos_detectados' => $campos_relevantes
        ];
    }

    /**
     * Detecta campos relevantes (color, marca, material, etc)
     */
    private function detectarCamposRelevantes()
    {
        $palabras_clave = ['color', 'marca', 'material', 'talla', 'taille', 'medida', 'medidas', 'tamaño'];
        $campos_encontrados = [];

        foreach ($this->encabezados as $encabezado) {
            $encabezado_lower = strtolower($encabezado);
            foreach ($palabras_clave as $palabra) {
                if (strpos($encabezado_lower, $palabra) !== false) {
                    $campos_encontrados[] = $encabezado;
                    break;
                }
            }
        }

        return $campos_encontrados;
    }

    /**
     * Obtiene estadísticas
     */
    public function obtenerEstadisticas()
    {
        return [
            'total_articulos' => count($this->datos),
            'total_campos' => count($this->encabezados),
            'campos' => $this->encabezados
        ];
    }

    /**
     * Obtiene los datos
     */
    public function obtenerDatos()
    {
        return [
            'encabezados' => $this->encabezados,
            'datos' => $this->datos
        ];
    }

    /**
     * Maneja la solicitud HTTP
     */
    public function manejarSolicitud()
    {
        try {
            $accion = $_GET['accion'] ?? $_POST['accion'] ?? null;
            $contenido = $_POST['contenido'] ?? file_get_contents('php://input');

            if (!$accion) {
                return $this->respuesta(['error' => 'Acción no especificada'], 400);
            }

            switch ($accion) {
                case 'procesar':
                    return $this->procesarContenido($contenido);
                
                case 'estadisticas':
                    return $this->respuesta($this->obtenerEstadisticas());
                
                case 'datos':
                    return $this->respuesta($this->obtenerDatos());
                
                case 'duplicaciones':
                    return $this->respuesta($this->analizarDuplicaciones());
                
                default:
                    return $this->respuesta(['error' => 'Acción desconocida'], 400);
            }
        } catch (Exception $e) {
            return $this->respuesta(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Procesa el contenido (CSV o JSON)
     */
    private function procesarContenido($contenido)
    {
        $contenido = trim($contenido);

        // Intentar como JSON
        if (($contenido[0] ?? '') === '{' || ($contenido[0] ?? '') === '[') {
            if ($this->procesarJSON($contenido)) {
                return $this->respuesta([
                    'exito' => true,
                    'tipo' => 'JSON',
                    'total' => count($this->datos),
                    'estadisticas' => $this->obtenerEstadisticas()
                ]);
            }
        }

        // Intentar como CSV
        if ($this->procesarCSV($contenido)) {
            return $this->respuesta([
                'exito' => true,
                'tipo' => 'CSV',
                'total' => count($this->datos),
                'estadisticas' => $this->obtenerEstadisticas()
            ]);
        }

        return $this->respuesta(['error' => 'No se pudo procesar el contenido'], 400);
    }

    /**
     * Envía respuesta JSON
     */
    private function respuesta($datos, $codigo = 200)
    {
        http_response_code($codigo);
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

// Ejecutar
$analizador = new AnalizadorArticulos();
$analizador->manejarSolicitud();
