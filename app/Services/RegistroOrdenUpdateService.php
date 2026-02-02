<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Models\News;
use App\Models\Festivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * RegistroOrdenUpdateService
 * 
 * Responsabilidad: Lógica de actualización de órdenes existentes
 * Cumple con SRP: Centraliza toda la lógica de update, validación delegada a ValidationService
 * Cumple con OCP: Extensible para nuevos tipos de actualizaciones
 * Cumple con LSP: Puede reemplazar lógica inline en controlador
 */
class RegistroOrdenUpdateService
{
    protected $cacheService;
    protected $festivosService;

    public function __construct(
        CacheCalculosService $cacheService = null,
        FestivosColombiaService $festivosService = null
    ) {
        $this->cacheService = $cacheService;
        $this->festivosService = $festivosService;
    }

    /**
     * Actualizar orden con datos validados
     */
    public function updateOrder(PedidoProduccion $orden, array $validatedData): array
    {
        DB::beginTransaction();

        try {
            $updates = [];
            $oldStatus = $orden->estado;
            $oldArea = $orden->area;

            // Procesar estado si está presente
            if (array_key_exists('estado', $validatedData)) {
                $updates['estado'] = $validatedData['estado'];
            }

            // Procesar área si está presente (crea/actualiza proceso)
            if (array_key_exists('area', $validatedData)) {
                $this->handleAreaUpdate($orden->numero_pedido, $validatedData['area']);
                // 🆕 IMPORTANTE: Añadir el área a los updates para que se persista en BD
                $updates['area'] = $validatedData['area'];
            }

            // Procesar día de entrega si está presente
            if (array_key_exists('dia_de_entrega', $validatedData)) {
                $updates = array_merge($updates, $this->handleDeliveryDayUpdate($orden, $validatedData['dia_de_entrega']));
            }

            // Procesar fechas (convertir formato d/m/Y a Y-m-d)
            $dateColumns = $this->getDateColumns();
            foreach ($validatedData as $key => $value) {
                if (in_array($key, $dateColumns) && !empty($value)) {
                    $updates[$key] = $this->parseDateFormat($value);
                } elseif (!in_array($key, ['estado', 'area', 'dia_de_entrega']) && !empty($value)) {
                    $updates[$key] = $value;
                }
            }

            // Ejecutar actualizaciones
            if (!empty($updates)) {
                $orden->update($updates);
                $this->invalidateCacheDays($orden->numero_pedido);
            }

            // Registrar cambios en News
            if (isset($updates['estado']) && $updates['estado'] !== $oldStatus) {
                $this->logStatusChange($orden->numero_pedido, $oldStatus, $updates['estado']);
            }

            if (array_key_exists('area', $validatedData) && $validatedData['area'] !== $oldArea) {
                $this->logAreaChange($orden->numero_pedido, $oldArea, $validatedData['area']);
            }

            DB::commit();

            return $this->prepareUpdateResponse($orden, $updates, $validatedData);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Manejar actualización de área (crea o actualiza proceso)
     * Cuando se cambia el área, se crea automáticamente un nuevo proceso con estado 'Pendiente'
     */
    private function handleAreaUpdate(int $numeroPedido, string $nuevaArea): void
    {
        $procesoExistente = ProcesoPrenda::where('numero_pedido', $numeroPedido)
            ->where('proceso', $nuevaArea)
            ->first();
        
        if (!$procesoExistente) {
            // 🆕 Crear nuevo proceso CON estado_proceso establecido
            ProcesoPrenda::create([
                'numero_pedido' => $numeroPedido,
                'proceso' => $nuevaArea,
                'fecha_inicio' => now()->toDateTimeString(),
                'estado_proceso' => 'Pendiente',  // 🆕 Establecer estado como Pendiente
                'encargado' => auth()->user()->name ?? 'Sistema'
            ]);
            \Log::info("Proceso CREADO para pedido {$numeroPedido}: {$nuevaArea}", [
                'estado_proceso' => 'Pendiente',
                'usuario' => auth()->user()->name ?? 'Sistema'
            ]);
        } else {
            $procesoExistente->update([
                'fecha_inicio' => now()->toDateTimeString(),
                'encargado' => auth()->user()->name ?? 'Sistema'
            ]);
            \Log::info("Proceso ACTUALIZADO para pedido {$numeroPedido}: {$nuevaArea}");
        }
    }

    /**
     * Manejar actualización de día de entrega
     */
    private function handleDeliveryDayUpdate(PedidoProduccion $orden, ?int $diaEntrega): array
    {
        $updates = [];

        //  SIEMPRE actualizar si el campo fue enviado, incluso si es null (deseleccionar)
        $updates['dia_de_entrega'] = $diaEntrega;
        
        if ($diaEntrega !== null) {
            // Recalcular fecha_estimada_de_entrega
            $orden->dia_de_entrega = $diaEntrega;
            $fechaEstimada = $orden->calcularFechaEstimada();
            
            if ($fechaEstimada) {
                $updates['fecha_estimada_de_entrega'] = $fechaEstimada->format('Y-m-d');
            }
            
            \Log::info("Día de entrega actualizado para pedido {$orden->numero_pedido}: {$diaEntrega}");
        } else {
            // Si es null, también limpiar fecha_estimada_de_entrega
            $updates['fecha_estimada_de_entrega'] = null;
            \Log::info("Día de entrega DESELECCIONADO para pedido {$orden->numero_pedido}");
        }

        return $updates;
    }

    /**
     * Parsear formato de fecha d/m/Y a Y-m-d
     */
    private function parseDateFormat(string $value): string
    {
        try {
            $date = Carbon::createFromFormat('d/m/Y', $value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            // Si no se puede parsear, devolver como está
            return $value;
        }
    }

    /**
     * Invalidar caché de días calculados
     */
    private function invalidateCacheDays(int $numeroPedido): void
    {
        $hoy = now()->format('Y-m-d');
        $currentYear = now()->year;
        $festivos = FestivosColombiaService::obtenerFestivos($currentYear);
        $festivosCacheKey = md5(serialize($festivos));
        
        $estados = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];
        
        foreach ($estados as $estado) {
            $cacheKey = "orden_dias_{$numeroPedido}_{$estado}_{$hoy}_{$festivosCacheKey}";
            Cache::forget($cacheKey);
        }
        
        // También invalidar para días anteriores (últimos 7 días)
        for ($i = 1; $i <= 7; $i++) {
            $fecha = now()->subDays($i)->format('Y-m-d');
            foreach ($estados as $estado) {
                $cacheKey = "orden_dias_{$numeroPedido}_{$estado}_{$fecha}_{$festivosCacheKey}";
                Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Registrar cambio de estado
     */
    private function logStatusChange(int $numeroPedido, string $oldStatus, string $newStatus): void
    {
        News::create([
            'event_type' => 'order_status_changed',
            'description' => "Estado de orden cambió: {$oldStatus} → {$newStatus} (Pedido {$numeroPedido})",
            'user_id' => auth()->id(),
            'pedido' => $numeroPedido,
            'metadata' => ['old_status' => $oldStatus, 'new_status' => $newStatus]
        ]);
    }

    /**
     * Registrar cambio de área
     */
    private function logAreaChange(int $numeroPedido, string $oldArea, string $newArea): void
    {
        News::create([
            'event_type' => 'order_area_changed',
            'description' => "Área de orden cambió: {$oldArea} → {$newArea} (Pedido {$numeroPedido})",
            'user_id' => auth()->id(),
            'pedido' => $numeroPedido,
            'metadata' => ['old_area' => $oldArea, 'new_area' => $newArea]
        ]);
    }

    /**
     * Preparar respuesta de actualización
     */
    private function prepareUpdateResponse(PedidoProduccion $orden, array $updates, array $validatedData): array
    {
        $orden->refresh();
        $ordenData = $orden->toArray();

        // Formatear fechas a DD/MM/YYYY para respuesta
        $dateColumns = $this->getDateColumns();
        foreach ($dateColumns as $column) {
            if (isset($ordenData[$column]) && $ordenData[$column] !== null && $ordenData[$column] !== '') {
                try {
                    $ordenData[$column] = Carbon::parse($ordenData[$column])->format('d/m/Y');
                } catch (\Exception $e) {
                    // Mantener valor original si falla el parseo
                }
            }
        }

        // Obtener festivos para cálculo de días
        $festivos = Festivo::pluck('fecha')->toArray();
        $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch([$orden], $festivos);

        return [
            'success' => true,
            'order' => $ordenData,
            'totalDiasCalculados' => $totalDiasCalculados,
            'changes' => array_keys($updates)
        ];
    }

    /**
     * Obtener columnas de tipo fecha
     */
    private function getDateColumns(): array
    {
        return [
            'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'insumos_y_telas', 'corte', 'costura',
            'lavanderia', 'arreglos', 'control_de_calidad', 'entrega', 'despacho'
        ];
    }

    /**
     * Broadcast evento de actualización
     */
    public function broadcastOrderUpdated(PedidoProduccion $orden, array $validatedData): void
    {
        // Si se actualizó el área, obtener el último proceso
        if (array_key_exists('area', $validatedData)) {
            $ultimoProceso = DB::table('procesos_prenda')
                ->where('numero_pedido', $orden->numero_pedido)
                ->orderBy('updated_at', 'desc')
                ->first();
            
            if ($ultimoProceso) {
                $orden->area = $ultimoProceso->proceso;
            }
        }

        $changedFields = array_keys($validatedData);
        broadcast(new \App\Events\OrdenUpdated($orden, 'updated', $changedFields));

        // Broadcast evento específico para Control de Calidad
        if (array_key_exists('area', $validatedData)) {
            if ($validatedData['area'] === 'Control-Calidad') {
                broadcast(new \App\Events\ControlCalidadUpdated($orden, 'added', 'pedido'));
            }
        }
    }
}
