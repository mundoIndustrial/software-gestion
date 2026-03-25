<?php

namespace App\Domain\Services;

use Illuminate\Database\Eloquent\Builder;

/**
 * Domain Service para lógica de filtrado
 * Responsable de:
 * - Aplicar reglas de negocio al filtrar
 * - Validar filtros
 * - Construir criterios complejos de búsqueda
 */
class OrderFilteringService
{
    /**
     * Validar criterios de filtro
     */
    public function validarFiltros(array $filtros): bool
    {
        // Validar que no haya campos inesperados
        $camposValidos = [
            'estado', 'area', 'dia_entrega', 'pedido', 'cliente',
            'asesor', 'forma_pago', 'fecha_creacion', 'fecha_estimada',
            'novedades', 'encargado', 'descripcion', 'cantidad', 'total_dias'
        ];

        foreach (array_keys($filtros) as $campo) {
            if (!in_array($campo, $camposValidos)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Construir criterios de búsqueda desde filtros
     */
    public function construirCriterios(array $filtros): array
    {
        $criterios = [];

        foreach ($filtros as $campo => $valor) {
            if (empty($valor)) {
                continue;
            }

            $criterios[$campo] = $this->normalizarValor($campo, $valor);
        }

        return $criterios;
    }

    /**
     * Normalizar valores de filtro según el tipo de campo
     */
    private function normalizarValor(string $campo, $valor): array
    {
        if (!is_array($valor)) {
            $valor = [$valor];
        }

        match($campo) {
            'dia_entrega' => array_map(fn($v) => (int)str_replace(' Dias', '', $v), $valor),
            'estado', 'area', 'forma_pago' => array_map('trim', $valor),
            default => $valor
        };

        return $valor;
    }

    /**
     * Determinar si debe aplicarse filtro por defecto
     */
    public function debeAplicarFiltroDefault(array $filtros): bool
    {
        return empty($filtros) || !isset($filtros['estado']);
    }
}
