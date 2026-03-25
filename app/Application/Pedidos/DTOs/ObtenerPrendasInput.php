<?php

namespace App\Application\Pedidos\DTOs;

use Illuminate\Http\Request;

/**
 * ObtenerPrendasInput
 * 
 * ✅ DTO para solicitud de búsqueda autocomplete de prendas
 * 
 * Beneficios:
 * ✅ Validación centralizada
 * ✅ Valores por defecto
 * ✅ Conversión de tipos (string → int)
 * ✅ Documentação de parámetros
 */
class ObtenerPrendasInput
{
    public function __construct(
        public string $busqueda,
        public int $limite = 50,
    ) {}

    /**
     * Factory: Crear desde Request HTTP
     * 
     * @param Request $request
     * @return self
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function fromRequest(Request $request): self
    {
        // Validar
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'limit' => ['integer', 'min:1', 'max:500']
        ]);

        return new self(
            busqueda: trim($validated['q']),
            limite: $validated['limit'] ?? 50,
        );
    }
}
