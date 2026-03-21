<?php

namespace App\Application\UseCases\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * DTO para entrada de ObtenerRecibosReflectivoUseCase
 * 
 * Responsabilidad: Encapsular parámetros y filtros para obtener recibos de reflectivo
 * Patrón: Transfer Object
 */
class ObtenerRecibosReflectivoInput
{
    public function __construct(
        public array $filtros = [],
        public bool $es_ajax = false,
        public ?string $tipo_respuesta = 'json' // 'json' o 'view'
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     */
    public static function fromRequest(Request $request): self
    {
        // Obtener todos los tipos de filtros desde la solicitud
        $filtros = [];
        $tiposFiltro = [
            'estado', 'numero_recibo', 'cliente', 'dia_entrega'
        ];

        foreach ($tiposFiltro as $tipo) {
            $valor = $request->input($tipo, []);
            if (is_string($valor)) {
                $valor = json_decode($valor, true) ?? [];
            }
            if (!empty($valor)) {
                $filtros[$tipo] = $valor;
            }
        }

        return new self(
            filtros: $filtros,
            es_ajax: $request->ajax(),
            tipo_respuesta: ($request->ajax() || $request->wantsJson()) ? 'json' : 'view'
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'filtros' => $this->filtros,
            'es_ajax' => $this->es_ajax,
            'tipo_respuesta' => $this->tipo_respuesta,
        ];
    }

    /**
     * Obtener un filtro específico
     */
    public function getFiltro(string $clave, $default = null)
    {
        return $this->filtros[$clave] ?? $default;
    }

    /**
     * Verificar si hay filtros aplicados
     */
    public function tieneFiltros(): bool
    {
        return !empty($this->filtros);
    }
}
