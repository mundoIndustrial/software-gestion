<?php

namespace App\Domain\Pedidos\ValueObjects;

use App\Domain\Pedidos\Enums\GeneroPrenda;
use App\Domain\Pedidos\Enums\TipoTalla;

/**
 * Value Object para manejo de tallas de prendas
 * 
 * Encapsula la lógica compleja de procesamiento de tallas:
 * - Detección automática de género
 * - Normalización de formatos (letra/número)
 * - Validación de cantidades
 * - Manejo de sobremedida
 */
class TallaPrenda
{
    private array $tallasPorGenero;
    private array $sobremedida;
    private GeneroPrenda $generoPrincipal;
    private TipoTalla $tipoTalla;

    public function __construct(array $tallasData = [])
    {
        $this->tallasPorGenero = [
            GeneroPrenda::DAMA->value => [],
            GeneroPrenda::CABALLERO->value => [],
            GeneroPrenda::UNISEX->value => [],
            'SOBREMEDIDA' => []
        ];
        
        $this->sobremedida = [];
        $this->generoPrincipal = GeneroPrenda::DAMA;
        $this->tipoTalla = TipoTalla::LETRA;
        
        if (!empty($tallasData)) {
            $this->procesarTallas($tallasData);
        }
    }

    /**
     * Procesar tallas desde diferentes fuentes de datos
     */
    public function procesarTallas(array $tallasData): self
    {
        // Detectar tipo de talla y procesar según la estructura
        if (isset($tallasData['cantidad_talla'])) {
            return $this->procesarDesdeCantidadTalla($tallasData['cantidad_talla']);
        }
        
        if (isset($tallasData['tallas']) && is_array($tallasData['tallas'])) {
            return $this->procesarDesdeArrayTallas($tallasData['tallas'], $tallasData['variantes'] ?? []);
        }
        
        if (isset($tallasData['procesos'])) {
            return $this->procesarDesdeProcesos($tallasData['procesos']);
        }

        return $this;
    }

    /**
     * Procesar tallas desde cantidad_talla (formato: {DAMA: {S: 20}, CABALLERO: {M: 10}})
     */
    private function procesarDesdeCantidadTalla(array $cantidadTalla): self
    {
        foreach ($cantidadTalla as $generoKey => $tallasObj) {
            $genero = $this->normalizarGenero($generoKey);
            
            if ($genero === 'SOBREMEDIDA') {
                $this->procesarSobremedida($tallasObj);
                continue;
            }

            if (is_array($tallasObj)) {
                $tallasLimpias = $this->extraerSobremedidaDeGenero($tallasObj, $genero);
                $this->tallasPorGenero[$genero] = $tallasLimpias;
            }
        }

        return $this;
    }

    /**
     * Procesar tallas desde array de tallas (formato BD)
     */
    private function procesarDesdeArrayTallas(array $tallas, array $variantes = []): self
    {
        // Agrupar tallas por género
        $tallasAgrupadas = [];
        $generosEnVariantes = $this->extraerGenerosDeVariantes($variantes);

        foreach ($tallas as $tallaObj) {
            $genero = $this->determinarGeneroDesdeTallaObj($tallaObj);
            
            if (!isset($tallasAgrupadas[$genero])) {
                $tallasAgrupadas[$genero] = [];
            }
            
            $tallasAgrupadas[$genero][] = $tallaObj;
        }

        // Procesar cada género
        foreach ($tallasAgrupadas as $genero => $tallasList) {
            $this->tallasPorGenero[$genero] = $this->convertirArrayAAsociativo($tallasList);
        }

        // Duplicar tallas para géneros faltantes según variantes
        $this->duplicarTallasParaGenerosFaltantes($tallasAgrupadas, $generosEnVariantes);

        return $this;
    }

    /**
     * Procesar tallas desde procesos
     */
    private function procesarDesdeProcesos(array $procesos): self
    {
        foreach ($procesos as $procesoData) {
            if (isset($procesoData['talla_cantidad'])) {
                $tallasArray = $this->convertirTallaCantidadAArray($procesoData['talla_cantidad']);
                
                if (!empty($tallasArray)) {
                    $genero = $this->determinarGeneroPrincipal();
                    $this->tallasPorGenero[$genero] = $this->convertirArrayAAsociativo($tallasArray);
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Extraer y procesar sobremedida
     */
    private function procesarSobremedida(array $sobremedidaData): void
    {
        foreach ($sobremedidaData as $key => $valor) {
            if (is_numeric($valor)) {
                // Es cantidad para un género específico
                $this->sobremedida[$key] = $valor;
            } elseif (is_array($valor)) {
                // Es estructura anidada de sobremedida
                foreach ($valor as $genero => $cantidad) {
                    $this->sobremedida[$genero] = $cantidad;
                }
            }
        }
    }

    /**
     * Extraer sobremedida de un género específico
     */
    private function extraerSobremedidaDeGenero(array $tallasObj, string $genero): array
    {
        $tallasLimpias = [];
        
        foreach ($tallasObj as $talla => $valor) {
            if ($talla === 'SOBREMEDIDA') {
                if (is_numeric($valor)) {
                    $this->sobremedida[$genero] = $valor;
                } elseif (is_array($valor)) {
                    foreach ($valor as $gen => $cantidad) {
                        $this->sobremedida[$gen] = $cantidad;
                    }
                }
            } else {
                $tallasLimpias[$talla] = $valor;
            }
        }
        
        return $tallasLimpias;
    }

    /**
     * Extraer géneros desde variantes
     */
    private function extraerGenerosDeVariantes(array $variantes): array
    {
        $generos = new \Doctrine\Common\Collections\ArrayCollection();
        
        foreach ($variantes as $variante) {
            if (isset($variante['genero_id'])) {
                $generosIds = $this->parsearGeneroId($variante['genero_id']);
                
                foreach ($generosIds as $generoId) {
                    $genero = $this->mapearGeneroId($generoId);
                    if ($genero && !$generos->contains($genero)) {
                        $generos->add($genero);
                    }
                }
            }
        }
        
        return $generos->toArray();
    }

    /**
     * Determinar género desde objeto talla
     */
    private function determinarGeneroDesdeTallaObj(array $tallaObj): string
    {
        if (isset($tallaObj['genero']['id'])) {
            return $this->mapearGeneroId($tallaObj['genero']['id']);
        }
        
        if (isset($tallaObj['genero']['nombre'])) {
            return strtoupper($tallaObj['genero']['nombre']);
        }
        
        if (isset($tallaObj['genero_id'])) {
            return $this->mapearGeneroId($tallaObj['genero_id']);
        }
        
        return GeneroPrenda::DAMA->value;
    }

    /**
     * Convertir array de objetos a asociativo
     */
    private function convertirArrayAAsociativo(array $tallasList): array
    {
        $asociativo = [];
        
        foreach ($tallasList as $tallaObj) {
            $talla = $tallaObj['talla'] ?? '';
            $cantidad = $tallaObj['cantidad'] ?? 0;
            
            if ($talla && $cantidad > 0) {
                $asociativo[$talla] = $cantidad;
            }
        }
        
        return $asociativo;
    }

    /**
     * Duplicar tallas para géneros faltantes
     */
    private function duplicarTallasParaGenerosFaltantes(array $tallasAgrupadas, array $generosEnVariantes): void
    {
        if (empty($generosEnVariantes)) {
            return;
        }

        $generosConTallas = array_keys($tallasAgrupadas);
        $primerGeneroConTallas = $generosConTallas[0] ?? null;

        foreach ($generosEnVariantes as $genero) {
            if (!in_array($genero, $generosConTallas) && $primerGeneroConTallas) {
                $this->tallasPorGenero[$genero] = $this->tallasPorGenero[$primerGeneroConTallas];
            }
        }
    }

    /**
     * Convertir talla_cantidad a array estandarizado
     */
    private function convertirTallaCantidadAArray($tallaCantidad): array
    {
        if (is_array($tallaCantidad)) {
            if (isset($tallaCantidad[0]['talla'])) {
                return $tallaCantidad;
            }
            
            return array_map(
                fn($talla, $cantidad) => ['talla' => $talla, 'cantidad' => $cantidad],
                array_keys($tallaCantidad),
                array_values($tallaCantidad)
            );
        }
        
        return [];
    }

    /**
     * Normalizar género
     */
    private function normalizarGenero(string $genero): string
    {
        $genero = strtoupper($genero);
        
        return match($genero) {
            'DAMA', 'FEMENINO', 'MUJER' => GeneroPrenda::DAMA->value,
            'CABALLERO', 'MASCULINO', 'HOMBRE' => GeneroPrenda::CABALLERO->value,
            'UNISEX', 'UNISEXO' => GeneroPrenda::UNISEX->value,
            'SOBREMEDIDA', 'SOBRE MEDIDA' => 'SOBREMEDIDA',
            default => GeneroPrenda::DAMA->value
        };
    }

    /**
     * Parsear género_id (puede ser string JSON o número)
     */
    private function parsearGeneroId($generoId): array
    {
        if (is_numeric($generoId)) {
            return [(int)$generoId];
        }
        
        if (is_string($generoId)) {
            $parsed = json_decode($generoId, true);
            
            if (is_array($parsed)) {
                return array_map('intval', $parsed);
            }
            
            return [(int)$generoId];
        }
        
        return [];
    }

    /**
     * Mapear género ID a nombre
     */
    private function mapearGeneroId(int $generoId): ?string
    {
        return match($generoId) {
            1 => GeneroPrenda::DAMA->value,
            2 => GeneroPrenda::CABALLERO->value,
            3 => GeneroPrenda::UNISEX->value,
            default => null
        };
    }

    /**
     * Determinar género principal
     */
    private function determinarGeneroPrincipal(): string
    {
        foreach ($this->tallasPorGenero as $genero => $tallas) {
            if (!empty($tallas)) {
                return $genero;
            }
        }
        
        return GeneroPrenda::DAMA->value;
    }

    /**
     * Validar cantidades totales
     */
    public function validarCantidadTotal(int $totalEsperado): bool
    {
        $totalCalculado = 0;
        
        foreach ($this->tallasPorGenero as $genero => $tallas) {
            if ($genero !== 'SOBREMEDIDA') {
                $totalCalculado += array_sum($tallas);
            }
        }
        
        return $totalCalculado === $totalEsperado;
    }

    /**
     * Obtener total de tallas por género
     */
    public function getTotalPorGenero(string $genero): int
    {
        return array_sum($this->tallasPorGenero[$genero] ?? []);
    }

    /**
     * Obtener tallas procesadas
     */
    public function getTallasPorGenero(): array
    {
        return $this->tallasPorGenero;
    }

    /**
     * Obtener sobremedida
     */
    public function getSobremedida(): array
    {
        return $this->sobremedida;
    }

    /**
     * Obtener género principal
     */
    public function getGeneroPrincipal(): string
    {
        return $this->generoPrincipal;
    }

    /**
     * Obtener tipo de talla
     */
    public function getTipoTalla(): TipoTalla
    {
        return $this->tipoTalla;
    }

    /**
     * Convertir a array para DTO
     */
    public function toArray(): array
    {
        return [
            'tallas_por_genero' => $this->tallasPorGenero,
            'sobremedida' => $this->sobremedida,
            'genero_principal' => $this->generoPrincipal,
            'tipo_talla' => $this->tipoTalla->value,
            'total_por_genero' => array_map(
                fn($tallas) => array_sum($tallas),
                $this->tallasPorGenero
            )
        ];
    }
}
