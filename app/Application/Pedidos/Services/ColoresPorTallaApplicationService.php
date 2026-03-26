<?php

namespace App\Application\Pedidos\Services;

use App\Infrastructure\Repositories\Pedidos\ColoresPorTallaRepository;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de aplicación para gestionar asignaciones de colores por talla.
 *
 * Aunque hoy reutiliza el repositorio existente, esta clase representa mejor
 * un flujo CRUD/orquestación que un servicio de dominio puro.
 */
class ColoresPorTallaApplicationService
{
    public function __construct(
        private ColoresPorTallaRepository $repository
    ) {}

    public function obtenerAsignaciones(array $filters = []): array
    {
        try {
            Log::info('Obteniendo asignaciones de colores', ['filters' => $filters]);

            $asignaciones = $this->repository->obtenerAsignaciones($filters);

            $resultado = [];
            foreach ($asignaciones as $asignacion) {
                $resultado[] = [
                    'id' => $asignacion->id,
                    'genero' => $asignacion->genero,
                    'talla' => $asignacion->talla,
                    'tipo_talla' => $asignacion->tipo_talla,
                    'tela' => $asignacion->tela,
                    'colores' => $this->formatearColores($asignacion->colores),
                    'total_unidades' => $this->calcularTotalUnidades($asignacion->colores),
                    'created_at' => $asignacion->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $asignacion->updated_at->format('Y-m-d H:i:s'),
                ];
            }

            Log::info('Asignaciones obtenidas exitosamente', ['total' => count($resultado)]);

            return $resultado;
        } catch (\Exception $e) {
            Log::error('Error obteniendo asignaciones', [
                'filters' => $filters,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function guardarAsignacion(array $datos): array
    {
        try {
            Log::info('Guardando asignación de colores', ['datos' => $datos]);

            $existente = $this->repository->buscarPorGeneroYTalla(
                $datos['genero'],
                $datos['talla'],
                $datos['tipo_talla']
            );

            if ($existente) {
                $asignacion = $this->actualizarAsignacionExistente($existente, $datos);
            } else {
                $asignacion = $this->crearNuevaAsignacion($datos);
            }

            Log::info('Asignación guardada exitosamente', ['id' => $asignacion->id]);

            return $this->formatearAsignacion($asignacion);
        } catch (\Exception $e) {
            Log::error('Error guardando asignación', [
                'datos' => $datos,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function actualizarAsignacion(int $id, array $datos): array
    {
        try {
            Log::info('Actualizando asignación', ['id' => $id, 'datos' => $datos]);

            $asignacion = $this->repository->buscarPorId($id);

            if (!$asignacion) {
                throw new \Exception('Asignación no encontrada');
            }

            $this->actualizarColores($asignacion, $datos['colores']);

            if (isset($datos['tela'])) {
                $asignacion->tela = $datos['tela'];
            }

            if (isset($datos['cantidad'])) {
                $asignacion->cantidad = $datos['cantidad'];
            }

            $this->repository->guardar($asignacion);

            Log::info('Asignación actualizada exitosamente', ['id' => $id]);

            return $this->formatearAsignacion($asignacion);
        } catch (\Exception $e) {
            Log::error('Error actualizando asignación', [
                'id' => $id,
                'datos' => $datos,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function eliminarAsignacion(int $id): bool
    {
        try {
            Log::info('Eliminando asignación', ['id' => $id]);

            $asignacion = $this->repository->buscarPorId($id);

            if (!$asignacion) {
                return false;
            }

            $this->repository->eliminar($asignacion);

            Log::info('Asignación eliminada exitosamente', ['id' => $id]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error eliminando asignación', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function obtenerColoresDisponibles(string $genero, string $talla): array
    {
        try {
            Log::info('Obteniendo colores disponibles', ['genero' => $genero, 'talla' => $talla]);

            return [
                'BLANCO', 'NEGRO', 'GRIS', 'AZUL', 'ROJO', 'VERDE', 'AMARILLO',
                'NARANJA', 'ROSADO', 'MORADO', 'CAFÉ', 'BEIGE', 'CREMA',
                'ARENA', 'TERRACOTA', 'CELESTE', 'TURQUESA', 'FUCSIA', 'LILA',
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo colores disponibles', [
                'genero' => $genero,
                'talla' => $talla,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function obtenerTallasDisponibles(string $genero): array
    {
        try {
            Log::info('Obteniendo tallas disponibles', ['genero' => $genero]);

            $tallas = [
                'dama' => [
                    'Letra' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
                    'Número' => ['6', '8', '10', '12', '14', '16', '18', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'],
                ],
                'caballero' => [
                    'Letra' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
                    'Número' => ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'],
                ],
                'unisex' => [
                    'Letra' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
                    'Número' => ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'],
                ],
            ];

            return $tallas[strtolower($genero)] ?? [];
        } catch (\Exception $e) {
            Log::error('Error obteniendo tallas disponibles', [
                'genero' => $genero,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function procesarAsignacionWizard(array $datos): array
    {
        try {
            Log::info('Procesando asignación wizard', ['datos' => $datos]);

            $resultado = [];
            $totalUnidades = 0;

            foreach ($datos['tallas'] as $tallaData) {
                $asignacionData = [
                    'genero' => $datos['genero'],
                    'talla' => $tallaData['talla'],
                    'tipo_talla' => $datos['tipo_talla'],
                    'tela' => $datos['tela'],
                    'colores' => $tallaData['colores'],
                ];

                $asignacion = $this->guardarAsignacion($asignacionData);
                $resultado[] = $asignacion;
                $totalUnidades += $asignacion['total_unidades'];
            }

            Log::info('Asignación wizard procesada', [
                'total_asignaciones' => count($resultado),
                'total_unidades' => $totalUnidades,
            ]);

            return $resultado;
        } catch (\Exception $e) {
            Log::error('Error procesando asignación wizard', [
                'datos' => $datos,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function crearNuevaAsignacion(array $datos)
    {
        $asignacion = new \stdClass();
        $asignacion->genero = $datos['genero'];
        $asignacion->talla = $datos['talla'];
        $asignacion->tipo_talla = $datos['tipo_talla'];
        $asignacion->tela = $datos['tela'];
        $asignacion->colores = json_encode($datos['colores']);
        $asignacion->created_at = now();
        $asignacion->updated_at = now();

        return $this->repository->crear($asignacion);
    }

    private function actualizarAsignacionExistente($asignacion, array $datos)
    {
        $this->actualizarColores($asignacion, $datos['colores']);

        if (isset($datos['tela'])) {
            $asignacion->tela = $datos['tela'];
        }

        $asignacion->updated_at = now();

        return $this->repository->guardar($asignacion);
    }

    private function actualizarColores($asignacion, array $colores): void
    {
        $coloresActuales = json_decode($asignacion->colores, true) ?? [];

        foreach ($colores as $nuevoColor) {
            $existente = array_search($nuevoColor['color'], array_column($coloresActuales, 'color'));

            if ($existente !== false) {
                $coloresActuales[$existente]['cantidad'] = $nuevoColor['cantidad'];
            } else {
                $coloresActuales[] = [
                    'color' => $nuevoColor['color'],
                    'cantidad' => $nuevoColor['cantidad'],
                    'fecha' => now()->format('Y-m-d H:i:s'),
                ];
            }
        }

        $asignacion->colores = json_encode($coloresActuales);
    }

    private function formatearColores(string $coloresJson): array
    {
        $colores = json_decode($coloresJson, true) ?? [];

        return array_map(function ($color) {
            return [
                'color' => $color['color'] ?? '',
                'cantidad' => $color['cantidad'] ?? 0,
                'fecha' => $color['fecha'] ?? '',
            ];
        }, $colores);
    }

    private function calcularTotalUnidades(string $coloresJson): int
    {
        $colores = json_decode($coloresJson, true) ?? [];

        return array_sum(array_column($colores, 'cantidad'));
    }

    private function formatearAsignacion($asignacion): array
    {
        return [
            'id' => $asignacion->id,
            'genero' => $asignacion->genero,
            'talla' => $asignacion->talla,
            'tipo_talla' => $asignacion->tipo_talla,
            'tela' => $asignacion->tela,
            'colores' => $this->formatearColores($asignacion->colores),
            'total_unidades' => $this->calcularTotalUnidades($asignacion->colores),
            'created_at' => $asignacion->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $asignacion->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
