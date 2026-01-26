<?php

namespace App\Domain\Pedidos\Services;

/**
 * Servicio de dominio para construcción de descripciones de prendas
 * Responsabilidad Ãºnica: Generar descripciones formateadas
 */
class DescripcionService
{
    /**
     * Construir descripción para prenda de pedido
     * Formato: Un pÃ¡rrafo Ãºnico con descripción + todas las variaciones + observaciones
     */
    public function construirDescripcionPrenda(int $numeroPrenda, array $producto, array $cantidadesPorTalla): string
    {
        $lineas = [];
        
        // NÃºmero de prenda
        $lineas[] = "PRENDA {$numeroPrenda}";
        
        // Descripción del producto
        if (!empty($producto['descripcion'])) {
            $lineas[] = strtoupper($producto['descripcion']);
        }
        
        // Variaciones
        $variaciones = $this->armarDescripcionVariaciones($producto['variantes'] ?? []);
        if ($variaciones) {
            $lineas[] = $variaciones;
        }
        
        return implode(' ', $lineas);
    }

    /**
     * Construir descripción para prenda sin cotización tipo PRENDA
     */
    public function construirDescripcionPrendaSinCotizacion(array $prenda, array $cantidadesPorTalla): string
    {
        $lineas = [];
        
        // Nombre del producto
        if (!empty($prenda['nombre_producto'])) {
            $lineas[] = strtoupper($prenda['nombre_producto']);
        }
        
        // Descripción
        if (!empty($prenda['descripcion'])) {
            $lineas[] = strtoupper($prenda['descripcion']);
        }
        
        // Color
        if (!empty($prenda['color'])) {
            $lineas[] = 'COLOR: ' . strtoupper($prenda['color']);
        }
        
        // Tela
        if (!empty($prenda['tela'])) {
            $lineas[] = 'TELA: ' . strtoupper($prenda['tela']);
        }
        
        // Referencia
        if (!empty($prenda['referencia'])) {
            $lineas[] = 'REF: ' . strtoupper($prenda['referencia']);
        }
        
        // Variaciones
        if (!empty($prenda['variantes'])) {
            $variaciones = $this->armarDescripcionVariacionesPrendaSinCotizacion($prenda['variantes']);
            if ($variaciones) {
                $lineas[] = $variaciones;
            }
        }
        
        return implode(' | ', $lineas);
    }

    /**
     * Construir descripción para reflectivo sin cotización
     */
    public function construirDescripcionReflectivoSinCotizacion(array $prenda, array $cantidadesPorTalla): string
    {
        $lineas = [];

        // Tipo de prenda
        if (!empty($prenda['tipo'])) {
            $lineas[] = strtoupper($prenda['tipo']);
        }

        // Descripción
        if (!empty($prenda['descripcion'])) {
            $lineas[] = strtoupper($prenda['descripcion']);
        }

        // Ubicaciones del reflectivo
        if (!empty($prenda['ubicaciones']) && is_array($prenda['ubicaciones'])) {
            $ubicacionesTexto = implode(', ', array_map('strtoupper', $prenda['ubicaciones']));
            $lineas[] = 'UBICACIONES: ' . $ubicacionesTexto;
        }

        // GÃ©nero
        if (!empty($prenda['genero'])) {
            $generoTexto = $prenda['genero'] === 'dama' ? 'DAMA' : 'CABALLERO';
            $lineas[] = 'GÃ‰NERO: ' . $generoTexto;
        }

        // Cantidades por talla
        if (!empty($cantidadesPorTalla)) {
            $tallasTexto = [];
            foreach ($cantidadesPorTalla as $talla => $cantidad) {
                if ($cantidad > 0) {
                    $tallasTexto[] = strtoupper($talla) . ': ' . $cantidad;
                }
            }
            if (!empty($tallasTexto)) {
                $lineas[] = 'TALLAS: ' . implode(', ', $tallasTexto);
            }
        }

        return implode(' | ', $lineas);
    }

    /**
     * Armar descripción de variaciones
     */
    private function armarDescripcionVariaciones(array $variantes): ?string
    {
        if (empty($variantes)) {
            return null;
        }

        $partes = [];

        foreach ($variantes as $variante) {
            // Manga
            if (!empty($variante['tipo_manga'])) {
                $partes[] = strtoupper($variante['tipo_manga']);
            }

            // Bolsillos
            if (!empty($variante['bolsillos'])) {
                $partes[] = strtoupper($variante['bolsillos']);
            }

            // Broche
            if (!empty($variante['tipo_broche'])) {
                $partes[] = strtoupper($variante['tipo_broche']);
            }

            // PuÃ±o
            if (!empty($variante['puno'])) {
                $partes[] = strtoupper($variante['puno']);
            }

            // Color
            if (!empty($variante['color'])) {
                $partes[] = 'COLOR: ' . strtoupper($variante['color']);
            }

            // Tela
            if (!empty($variante['tela'])) {
                $partes[] = 'TELA: ' . strtoupper($variante['tela']);
            }
        }

        return !empty($partes) ? implode(' | ', $partes) : null;
    }

    /**
     * Armar descripción de variaciones para prendas sin cotización
     */
    private function armarDescripcionVariacionesPrendaSinCotizacion(array $variantes): ?string
    {
        if (empty($variantes) || !is_array($variantes)) {
            return null;
        }

        $partes = [];

        // Manga
        if (!empty($variantes['manga'])) {
            $partes[] = 'MANGA: ' . strtoupper($variantes['manga']);
        }

        // Bolsillos
        if (!empty($variantes['bolsillos'])) {
            $partes[] = 'BOLSILLOS: ' . strtoupper($variantes['bolsillos']);
        }

        // Broche/Botón
        if (!empty($variantes['broche'])) {
            $partes[] = 'BROCHE: ' . strtoupper($variantes['broche']);
        }

        // PuÃ±o
        if (!empty($variantes['puno'])) {
            $partes[] = 'PUÃ‘O: ' . strtoupper($variantes['puno']);
        }

        return !empty($partes) ? implode(' | ', $partes) : null;
    }
}

