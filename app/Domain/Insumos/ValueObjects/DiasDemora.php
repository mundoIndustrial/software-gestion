<?php

namespace App\Domain\Insumos\ValueObjects;

use InvalidArgumentException;

/**
 * ValueObject para representar los días de demora de un material
 * Encapsula la lógica de cálculo y estado de demora
 * 
 * DDD: ValueObject - No tiene identidad, es inmutable
 */
class DiasDemora
{
    private int $dias;
    private string $estado;
    private array $colores;

    const ESTADO_RAPIDO = 'rapido';
    const ESTADO_NORMAL = 'normal';
    const ESTADO_LENTO = 'lento';
    const ESTADO_CRITICO = 'critico';

    /**
     * Constructor
     * @param int $dias Número de días de demora
     * @throws InvalidArgumentException
     */
    public function __construct(int $dias)
    {
        if ($dias < 0) {
            throw new InvalidArgumentException('Los días de demora no pueden ser negativos');
        }

        $this->dias = $dias;
        $this->estado = $this->calcularEstado();
        $this->colores = $this->obtenerColores();
    }

    /**
     * Crear desde dos fechas
     * @param string|\DateTime $fechaInicio
     * @param string|\DateTime $fechaFin
     * @return self
     */
    public static function desdeFechas($fechaInicio, $fechaFin): self
    {
        $dias = app('App\Services\CalculadorDiasService')->calcularDiasHabiles($fechaInicio, $fechaFin);
        return new self($dias ?? 0);
    }

    /**
     * Calcular el estado según los días
     * @return string
     */
    private function calcularEstado(): string
    {
        if ($this->dias <= 5) {
            return self::ESTADO_RAPIDO;
        } elseif ($this->dias <= 10) {
            return self::ESTADO_NORMAL;
        } elseif ($this->dias <= 20) {
            return self::ESTADO_LENTO;
        }
        
        return self::ESTADO_CRITICO;
    }

    /**
     * Obtener los colores (Tailwind) según el estado
     * @return array ['bg' => clase, 'text' => clase]
     */
    private function obtenerColores(): array
    {
        return match ($this->estado) {
            self::ESTADO_RAPIDO => [
                'bg' => 'bg-green-100',
                'text' => 'text-green-700',
                'badge' => 'bg-green-500',
                'hex' => '#10b981'
            ],
            self::ESTADO_NORMAL => [
                'bg' => 'bg-yellow-100',
                'text' => 'text-yellow-700',
                'badge' => 'bg-yellow-500',
                'hex' => '#f59e0b'
            ],
            self::ESTADO_LENTO => [
                'bg' => 'bg-orange-100',
                'text' => 'text-orange-700',
                'badge' => 'bg-orange-500',
                'hex' => '#f97316'
            ],
            self::ESTADO_CRITICO => [
                'bg' => 'bg-red-100',
                'text' => 'text-red-700',
                'badge' => 'bg-red-500',
                'hex' => '#ef4444'
            ],
            default => [
                'bg' => 'bg-gray-100',
                'text' => 'text-gray-700',
                'badge' => 'bg-gray-500',
                'hex' => '#6b7280'
            ]
        };
    }

    /**
     * Obtener los días como número
     */
    public function getDias(): int
    {
        return $this->dias;
    }

    /**
     * Obtener el estado
     */
    public function getEstado(): string
    {
        return $this->estado;
    }

    /**
     * Obtener los colores Tailwind
     */
    public function getColores(): array
    {
        return $this->colores;
    }

    /**
     * Obtener el color de fondo (Tailwind)
     */
    public function getClaseBg(): string
    {
        return $this->colores['bg'];
    }

    /**
     * Obtener el color de texto (Tailwind)
     */
    public function getClaseText(): string
    {
        return $this->colores['text'];
    }

    /**
     * Obtener el color hex para gráficos
     */
    public function getColorHex(): string
    {
        return $this->colores['hex'];
    }

    /**
     * Obtener representación en texto
     */
    public function __toString(): string
    {
        $plural = $this->dias === 1 ? 'día' : 'días';
        return "{$this->dias} {$plural}";
    }

    /**
     * Obtener como array para JSON
     */
    public function toArray(): array
    {
        return [
            'dias' => $this->dias,
            'estado' => $this->estado,
            'texto' => (string) $this,
            'colores' => $this->colores,
            'clase_bg' => $this->getClaseBg(),
            'clase_text' => $this->getClaseText(),
            'color_hex' => $this->getColorHex(),
        ];
    }

    /**
     * Comparar dos ValueObjects (por valor, no por identidad)
     */
    public function equals(DiasDemora $other): bool
    {
        return $this->dias === $other->dias;
    }
}
