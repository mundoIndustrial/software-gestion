<?php

namespace App\Domain\Pedidos\Services;

use App\Domain\Pedidos\Repositories\ColoresPorTallaRepository;
use App\Domain\Pedidos\ValueObjects\AsignacionColor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service de Dominio para Colores por Talla
 * Contiene toda la lógica de negocio de asignación de colores a tallas
 */
class ColoresPorTallaService
{
    private ColoresPorTallaRepository $repository;

    public function __construct(ColoresPorTallaRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Obtener asignaciones con filtros opcionales
     */
    public function obtenerAsignaciones(array $filters = []): array
    {
        try {
            Log::info('Obteniendo asignaciones de colores', ['filters' => $filters]);
            
            $asignaciones = $this->repository->obtenerAsignaciones($filters);
            
            // Transformar datos para la respuesta
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
                    'updated_at' => $asignacion->updated_at->format('Y-m-d H:i:s')
                ];
            }
            
            Log::info('Asignaciones obtenidas exitosamente', ['total' => count($resultado)]);
            
            return $resultado;
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo asignaciones', [
                'filters' => $filters,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Guardar una nueva asignación de colores
     */
    public function guardarAsignacion(array $datos): array
    {
        try {
            Log::info('Guardando asignación de colores', ['datos' => $datos]);
            
            // Validar que no exista una asignación duplicada
            $existente = $this->repository->buscarPorGeneroYTalla(
                $datos['genero'], 
                $datos['talla'], 
                $datos['tipo_talla']
            );
            
            if ($existente) {
                // Actualizar asignación existente
                $asignacion = $this->actualizarAsignacionExistente($existente, $datos);
            } else {
                // Crear nueva asignación
                $asignacion = $this->crearNuevaAsignacion($datos);
            }
            
            Log::info('Asignación guardada exitosamente', ['id' => $asignacion->id]);
            
            return $this->formatearAsignacion($asignacion);
            
        } catch (\Exception $e) {
            Log::error('Error guardando asignación', [
                'datos' => $datos,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Actualizar una asignación existente
     */
    public function actualizarAsignacion(int $id, array $datos): array
    {
        try {
            Log::info('Actualizando asignación', ['id' => $id, 'datos' => $datos]);
            
            $asignacion = $this->repository->buscarPorId($id);
            
            if (!$asignacion) {
                throw new \Exception('Asignación no encontrada');
            }
            
            // Actualizar colores
            $this->actualizarColores($asignacion, $datos['colores']);
            
            // Actualizar otros campos si es necesario
            if (isset($datos['tela'])) {
                $asignacion->tela = $datos['tela'];
            }
            
            if (isset($datos['cantidad'])) {
                $asignacion->cantidad = $datos['cantidad'];
            }
            
            // Guardar cambios
            $this->repository->guardar($asignacion);
            
            Log::info('Asignación actualizada exitosamente', ['id' => $id]);
            
            return $this->formatearAsignacion($asignacion);
            
        } catch (\Exception $e) {
            Log::error('Error actualizando asignación', [
                'id' => $id,
                'datos' => $datos,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Eliminar una asignación
     */
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
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Obtener colores disponibles para una talla
     */
    public function obtenerColoresDisponibles(string $genero, string $talla): array
    {
        try {
            Log::info('Obteniendo colores disponibles', ['genero' => $genero, 'talla' => $talla]);
            
            // Aquí podrías tener lógica para obtener colores de un catálogo
            // Por ahora, devolvemos colores predefinidos
            $colores = [
                'BLANCO', 'NEGRO', 'GRIS', 'AZUL', 'ROJO', 'VERDE', 'AMARILLO',
                'NARANJA', 'ROSADO', 'MORADO', 'CAFÉ', 'BEIGE', 'CREMA',
                'ARENA', 'TERRACOTA', 'CELESTE', 'TURQUESA', 'FUCSIA', 'LILA'
            ];
            
            return $colores;
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo colores disponibles', [
                'genero' => $genero,
                'talla' => $talla,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Obtener tallas disponibles para un género
     */
    public function obtenerTallasDisponibles(string $genero): array
    {
        try {
            Log::info('Obteniendo tallas disponibles', ['genero' => $genero]);
            
            $tallas = [
                'dama' => [
                    'Letra' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
                    'Número' => ['34', '36', '38', '40', '42', '44', '46', '48']
                ],
                'caballero' => [
                    'Letra' => ['S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
                    'Número' => ['36', '38', '40', '42', '44', '46', '48', '50', '52']
                ],
                'unisex' => [
                    'Letra' => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
                    'Número' => ['34', '36', '38', '40', '42', '44', '46', '48', '50']
                ]
            ];
            
            return $tallas[strtolower($genero)] ?? [];
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo tallas disponibles', [
                'genero' => $genero,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Procesar asignación del wizard (múltiples tallas)
     */
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
                    'colores' => $tallaData['colores']
                ];
                
                $asignacion = $this->guardarAsignacion($asignacionData);
                $resultado[] = $asignacion;
                $totalUnidades += $asignacion['total_unidades'];
            }
            
            Log::info('Asignación wizard procesada', [
                'total_asignaciones' => count($resultado),
                'total_unidades' => $totalUnidades
            ]);
            
            return $resultado;
            
        } catch (\Exception $e) {
            Log::error('Error procesando asignación wizard', [
                'datos' => $datos,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Crear una nueva asignación
     */
    private function crearNuevaAsignacion(array $datos)
    {
        $asignacion = new \stdClass(); // Reemplazar con el modelo real
        $asignacion->genero = $datos['genero'];
        $asignacion->talla = $datos['talla'];
        $asignacion->tipo_talla = $datos['tipo_talla'];
        $asignacion->tela = $datos['tela'];
        $asignacion->colores = json_encode($datos['colores']);
        $asignacion->created_at = now();
        $asignacion->updated_at = now();
        
        return $this->repository->crear($asignacion);
    }

    /**
     * Actualizar asignación existente
     */
    private function actualizarAsignacionExistente($asignacion, array $datos)
    {
        $this->actualizarColores($asignacion, $datos['colores']);
        
        // Actualizar otros campos si es necesario
        if (isset($datos['tela'])) {
            $asignacion->tela = $datos['tela'];
        }
        
        $asignacion->updated_at = now();
        
        return $this->repository->guardar($asignacion);
    }

    /**
     * Actualizar colores de una asignación
     */
    private function actualizarColores($asignacion, array $colores)
    {
        $coloresActuales = json_decode($asignacion->colores, true) ?? [];
        
        // Fusionar colores existentes con nuevos
        foreach ($colores as $nuevoColor) {
            $existente = array_search($nuevoColor['color'], array_column($coloresActuales, 'color'));
            
            if ($existente !== false) {
                // Actualizar cantidad
                $coloresActuales[$existente]['cantidad'] = $nuevoColor['cantidad'];
            } else {
                // Agregar nuevo color
                $coloresActuales[] = [
                    'color' => $nuevoColor['color'],
                    'cantidad' => $nuevoColor['cantidad'],
                    'fecha' => now()->format('Y-m-d H:i:s')
                ];
            }
        }
        
        $asignacion->colores = json_encode($coloresActuales);
    }

    /**
     * Formatear colores para respuesta
     */
    private function formatearColores(string $coloresJson): array
    {
        $colores = json_decode($coloresJson, true) ?? [];
        
        return array_map(function ($color) {
            return [
                'color' => $color['color'] ?? '',
                'cantidad' => $color['cantidad'] ?? 0,
                'fecha' => $color['fecha'] ?? ''
            ];
        }, $colores);
    }

    /**
     * Calcular total de unidades
     */
    private function calcularTotalUnidades(string $coloresJson): int
    {
        $colores = json_decode($coloresJson, true) ?? [];
        
        return array_sum(array_column($colores, 'cantidad'));
    }

    /**
     * Formatear asignación para respuesta
     */
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
            'updated_at' => $asignacion->updated_at->format('Y-m-d H:i:s')
        ];
    }
}
