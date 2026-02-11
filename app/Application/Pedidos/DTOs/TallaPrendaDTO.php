<?php

namespace App\Application\Pedidos\DTOs;

/**
 * Data Transfer Object para tallas de prendas procesadas
 */
class TallaPrendaDTO
{
    public function __construct(
        public readonly array $tallas_por_genero,
        public readonly array $sobremedida,
        public readonly string $genero_principal,
        public readonly string $tipo_talla,
        public readonly array $total_por_genero,
        public readonly int $total_general,
        public readonly ?array $validacion,
        public readonly array $tallas_desde_cotizacion,
        public readonly bool $tiene_sobremedida,
        public readonly array $generos_activos
    ) {}

    /**
     * Crear desde array crudo
     */
    public static function fromArray(array $data): self
    {
        return new self(
            tallas_por_genero: $data['tallas_por_genero'] ?? [],
            sobremedida: $data['sobremedida'] ?? [],
            genero_principal: $data['genero_principal'] ?? 'DAMA',
            tipo_talla: $data['tipo_talla'] ?? 'letra',
            total_por_genero: $data['total_por_genero'] ?? [],
            total_general: $data['total_general'] ?? 0,
            validacion: $data['validacion'] ?? null,
            tallas_desde_cotizacion: $data['tallas_desde_cotizacion'] ?? [],
            tiene_sobremedida: $data['tiene_sobremedida'] ?? false,
            generos_activos: $data['generos_activos'] ?? []
        );
    }

    /**
     * Convertir a array para respuesta JSON
     */
    public function toArray(): array
    {
        return [
            'tallas_por_genero' => $this->tallas_por_genero,
            'sobremedida' => $this->sobremedida,
            'genero_principal' => $this->genero_principal,
            'tipo_talla' => $this->tipo_talla,
            'total_por_genero' => $this->total_por_genero,
            'total_general' => $this->total_general,
            'validacion' => $this->validacion,
            'tallas_desde_cotizacion' => $this->tallas_desde_cotizacion,
            'tiene_sobremedida' => $this->tiene_sobremedida,
            'generos_activos' => $this->generos_activos,
            'resumen' => [
                'total_tallas_distintas' => $this->contarTallasDistintas(),
                'generos_con_cantidad' => count(array_filter($this->total_por_genero, fn($total) => $total > 0)),
                'es_valido' => $this->validacion ? $this->validacion['valid'] : true
            ]
        ];
    }

    /**
     * Contar tallas distintas en todos los géneros
     */
    private function contarTallasDistintas(): int
    {
        $tallasDistintas = [];
        
        foreach ($this->tallas_por_genero as $genero => $tallas) {
            if ($genero !== 'SOBREMEDIDA' && is_array($tallas)) {
                $tallasDistintas = array_merge($tallasDistintas, array_keys($tallas));
            }
        }
        
        return count(array_unique($tallasDistintas));
    }

    /**
     * Obtener tallas para un género específico
     */
    public function getTallasPorGenero(string $genero): array
    {
        return $this->tallas_por_genero[$genero] ?? [];
    }

    /**
     * Obtener total para un género específico
     */
    public function getTotalPorGenero(string $genero): int
    {
        return $this->total_por_genero[$genero] ?? 0;
    }

    /**
     * Verificar si tiene tallas en un género
     */
    public function tieneTallasEnGenero(string $genero): bool
    {
        return !empty($this->tallas_por_genero[$genero]);
    }

    /**
     * Verificar si la validación es exitosa
     */
    public function esValido(): bool
    {
        return $this->validacion ? $this->validacion['valid'] : true;
    }

    /**
     * Obtener errores de validación
     */
    public function getErroresValidacion(): array
    {
        return $this->validacion['errores'] ?? [];
    }
}
