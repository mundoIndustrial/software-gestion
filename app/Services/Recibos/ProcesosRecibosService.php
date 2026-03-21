<?php

namespace App\Services\Recibos;

use App\Models\ConsecutivoReciboPedido;
use App\Models\ProcesoPrenda;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Application Service para gestión de Procesos en Recibos
 * 
 * Responsabilidades:
 * - Crear procesos asociados a recibos
 * - Actualizar procesos existentes
 * - Validar reglas de negocio de procesos
 */
class ProcesosRecibosService
{
    /**
     * Validar datos de proceso antes de guardar
     * 
     * @param array $datos
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validar(array $datos): array
    {
        $errores = [];
        $estadosValidos = ['Pendiente', 'En Progreso', 'Completado', 'Pausado'];

        // Validar proceso
        if (empty($datos['proceso'])) {
            $errores[] = 'El proceso es requerido';
        }

        // Validar encargado
        if (empty($datos['encargado'])) {
            $errores[] = 'El encargado es requerido';
        }

        // Validar estado
        if (!empty($datos['estado_proceso']) && !in_array($datos['estado_proceso'], $estadosValidos)) {
            $errores[] = 'Estado inválido. Válidos: ' . implode(', ', $estadosValidos);
        }

        // Validar que el pedido existe
        if (empty($datos['numero_pedido'])) {
            $errores[] = 'El número de pedido es requerido';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Crear o actualizar un proceso
     * 
     * @param int $numeroPedido
     * @param array $datos
     * @return array ['success' => bool, 'action' => 'creado|actualizado', 'proceso' => array, 'mensaje' => string]
     */
    public function guardarProceso(int $numeroPedido, array $datos): array
    {
        // Validar
        $validacion = $this->validar($datos);
        if (!$validacion['valido']) {
            throw new \InvalidArgumentException(implode('; ', $validacion['errores']));
        }

        $numeroPedido = $datos['numero_pedido'] ?? $numeroPedido;
        $prendaId = $datos['prenda_pedido_id'] ?? null;
        $proceso = $datos['proceso'];

        // Buscar si ya existe proceso para esta prenda y proceso
        $procesoExistente = ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->where('proceso', $proceso);
        
        if ($prendaId) {
            $procesoExistente->where('prenda_pedido_id', $prendaId);
        }
        
        $procesoExistente = $procesoExistente->first();

        if ($procesoExistente) {
            // Actualizar
            $procesoExistente->update([
                'encargado' => $datos['encargado'],
                'estado_proceso' => $datos['estado_proceso'] ?? $procesoExistente->estado_proceso,
                'observaciones' => $datos['observaciones'] ?? $procesoExistente->observaciones,
                'fecha_de_asignacion_encargado' => now(),
                'updated_at' => now(),
            ]);

            return [
                'success' => true,
                'action' => 'actualizado',
                'proceso' => $procesoExistente->toArray(),
                'mensaje' => "Proceso {$proceso} actualizado correctamente"
            ];
        } else {
            // Crear
            $proceed = ProcesoPrenda::create([
                'numero_pedido' => $numeroPedido,
                'prenda_pedido_id' => $prendaId,
                'proceso' => $proceso,
                'encargado' => $datos['encargado'],
                'estado_proceso' => $datos['estado_proceso'] ?? 'Pendiente',
                'observaciones' => $datos['observaciones'] ?? null,
                'fecha_de_asignacion_encargado' => now(),
            ]);

            return [
                'success' => true,
                'action' => 'creado',
                'proceso' => $proceed->toArray(),
                'mensaje' => "Proceso {$proceso} agregado correctamente"
            ];
        }
    }

    /**
     * Obtener procesos de un pedido
     * 
     * @param int $numeroPedido
     * @return array Array de procesos
     */
    public function obtenerProcesos(int $numeroPedido): array
    {
        return ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->get()
            ->map(function($proceso) {
                return [
                    'id' => $proceso->id,
                    'proceso' => $proceso->proceso,
                    'encargado' => $proceso->encargado,
                    'estado_proceso' => $proceso->estado_proceso,
                    'fecha_inicio' => $proceso->fecha_inicio?->format('Y-m-d H:i'),
                    'fecha_fin' => $proceso->fecha_fin?->format('Y-m-d H:i'),
                    'created_at' => $proceso->created_at->format('Y-m-d H:i'),
                    'updated_at' => $proceso->updated_at->format('Y-m-d H:i'),
                ];
            })
            ->toArray();
    }

    /**
     * Obtener encargados que han sido asignados a procesos
     * 
     * @return array Array de nombres de encargados
     */
    public function obtenerEncargados(): array
    {
        // Obtener encargados únicos desde procesos realizados
        return ProcesoPrenda::whereNotNull('encargado')
            ->distinct()
            ->pluck('encargado')
            ->toArray();
    }

    /**
     * Obtener tipos de procesos configurados
     * 
     * @return array
     */
    public function obtenerProcesosDisponibles(): array
    {
        return ProcesoPrenda::distinct()
            ->pluck('proceso')
            ->toArray();
    }

    /**
     * Marcar proceso como completado
     * 
     * @param int $procesoId
     * @return array ['success' => bool, 'mensaje' => string]
     */
    public function marcarCompletado(int $procesoId): array
    {
        $proceso = ProcesoPrenda::findOrFail($procesoId);
        $proceso->update([
            'estado_proceso' => 'Completado',
            'fecha_fin' => now(),
            'updated_at' => now(),
        ]);

        return [
            'success' => true,
            'mensaje' => "Proceso {$proceso->proceso} marcado como completado"
        ];
    }
}
