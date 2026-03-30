<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerPrendasInput;
use App\Domain\Prendas\Repositories\TipoPrendaRepository;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerPrendasAutocompleteUseCase
 *  RESPONSABILIDAD ÚNICA: Buscar prendas por término y retornar para autocomplete
 */
class ObtenerPrendasAutocompleteUseCase
{
    public function __construct(
        private TipoPrendaRepository $tipoPrendaRepository,
    ) {}

    /**
     * Ejecutar búsqueda
     * @param ObtenerPrendasInput $input
     * @return object (con propiedad: prendas)
     */
    public function ejecutar(ObtenerPrendasInput $input): object
    {
        Log::info('[ObtenerPrendasAutocompleteUseCase] Iniciado', [
            'busqueda' => $input->busqueda,
            'limite' => $input->limite,
        ]);

        try {
            // Buscar prendas activas que coincidan
            $prendas = $this->tipoPrendaRepository->buscarActivas(
                $input->busqueda,
                $input->limite
            );

            // Formatear para autocomplete
            $prendasFormateadas = $prendas->map(function ($prenda) {
                return [
                    'id' => $prenda->id,
                    'nombre' => $prenda->nombre,
                    'codigo' => $prenda->codigo,
                    'descripcion' => $prenda->descripcion,
                ];
            })->toArray();

            Log::info('[ObtenerPrendasAutocompleteUseCase] Completado', [
                'resultados' => count($prendasFormateadas),
            ]);

            return (object) ['prendas' => $prendasFormateadas];

        } catch (\Exception $e) {
            Log::error('[ObtenerPrendasAutocompleteUseCase] Error', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
