<?php

namespace App\Domain\Bodega\ValueObjects;

final class AreaBodega
{
    public const CREACION_ORDEN = 'Creación Orden';
    public const INSUMOS = 'Insumos';
    public const CORTE = 'Corte';
    public const BORDADO = 'Bordado';
    public const ESTAMPADO = 'Estampado';
    public const COSTURA = 'Costura';
    public const POLOS = 'Polos';
    public const TALLER = 'Taller';
    public const ARREGLOS = 'Arreglos';
    public const CONTROL_CALIDAD = 'Control-Calidad';
    public const ENTREGA = 'Entrega';
    public const DESPACHOS = 'Despachos';

    private string $valor;

    private function __construct(string $valor)
    {
        $areasValidas = [
            self::CREACION_ORDEN,
            self::INSUMOS,
            self::CORTE,
            self::BORDADO,
            self::ESTAMPADO,
            self::COSTURA,
            self::POLOS,
            self::TALLER,
            self::ARREGLOS,
            self::CONTROL_CALIDAD,
            self::ENTREGA,
            self::DESPACHOS,
        ];

        if (!in_array($valor, $areasValidas, true)) {
            throw new \InvalidArgumentException("Área inválida: {$valor}");
        }

        $this->valor = $valor;
    }

    public static function desde(string $valor): self
    {
        return new self($valor);
    }

    public static function creacionOrden(): self
    {
        return new self(self::CREACION_ORDEN);
    }

    public function valor(): string
    {
        return $this->valor;
    }

    public function obtenerCampoFecha(): ?string
    {
        $mapeo = [
            self::CREACION_ORDEN => 'fecha_de_creacion_de_orden',
            self::INSUMOS => 'insumos_y_telas',
            self::CORTE => 'corte',
            self::BORDADO => 'bordado',
            self::ESTAMPADO => 'estampado',
            self::COSTURA => 'costura',
            self::POLOS => 'costura',
            self::TALLER => 'costura',
            self::ARREGLOS => 'arreglos',
            self::CONTROL_CALIDAD => 'control_de_calidad',
            self::ENTREGA => 'entrega',
            self::DESPACHOS => 'despacho',
        ];

        return $mapeo[$this->valor] ?? null;
    }

    public function __toString(): string
    {
        return $this->valor;
    }
}
