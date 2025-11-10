<?php

namespace App\Traits;

use App\Models\News;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the auditable trait for a model.
     */
    public static function bootAuditable()
    {
        // Evento: Creación
        static::created(function ($model) {
            $model->auditCreate();
        });

        // Evento: Actualización
        static::updated(function ($model) {
            $model->auditUpdate();
        });

        // Evento: Eliminación
        static::deleted(function ($model) {
            $model->auditDelete();
        });
    }

    /**
     * Registrar creación de registro
     */
    protected function auditCreate()
    {
        $tableName = $this->getTable();
        $modelName = class_basename($this);
        $identifier = $this->getAuditIdentifier();

        News::create([
            'event_type' => 'record_created',
            'table_name' => $tableName,
            'record_id' => $this->getKey(),
            'description' => $this->generateFriendlyCreateDescription($identifier),
            'user_id' => Auth::id(),
            'pedido' => $this->getPedidoAttribute(),
            'metadata' => [
                'table' => $tableName,
                'model' => $modelName,
                'record_id' => $this->getKey(),
                'action' => 'created',
                'data' => $this->getAuditableAttributes()
            ]
        ]);
    }

    /**
     * Registrar actualización de registro
     */
    protected function auditUpdate()
    {
        $tableName = $this->getTable();
        $modelName = class_basename($this);
        $identifier = $this->getAuditIdentifier();
        
        // Obtener cambios
        $changes = $this->getChanges();
        $original = $this->getOriginal();
        
        // Filtrar cambios significativos (excluir timestamps si no son relevantes)
        $significantChanges = array_filter($changes, function ($key) {
            return !in_array($key, ['updated_at']);
        }, ARRAY_FILTER_USE_KEY);

        if (empty($significantChanges)) {
            return; // No registrar si solo cambió updated_at
        }

        // Generar descripción amigable
        $description = $this->generateFriendlyUpdateDescription($significantChanges, $original, $identifier);

        News::create([
            'event_type' => 'record_updated',
            'table_name' => $tableName,
            'record_id' => $this->getKey(),
            'description' => $description,
            'user_id' => Auth::id(),
            'pedido' => $this->getPedidoAttribute(),
            'metadata' => [
                'table' => $tableName,
                'model' => $modelName,
                'record_id' => $this->getKey(),
                'action' => 'updated',
                'changes' => $significantChanges,
                'original' => array_intersect_key($original, $significantChanges)
            ]
        ]);
    }

    /**
     * Registrar eliminación de registro
     */
    protected function auditDelete()
    {
        $tableName = $this->getTable();
        $modelName = class_basename($this);
        $identifier = $this->getAuditIdentifier();

        News::create([
            'event_type' => 'record_deleted',
            'table_name' => $tableName,
            'record_id' => $this->getKey(),
            'description' => $this->generateFriendlyDeleteDescription($identifier),
            'user_id' => Auth::id(),
            'pedido' => $this->getPedidoAttribute(),
            'metadata' => [
                'table' => $tableName,
                'model' => $modelName,
                'record_id' => $this->getKey(),
                'action' => 'deleted',
                'data' => $this->getAuditableAttributes()
            ]
        ]);
    }

    /**
     * Obtener identificador legible para auditoría
     */
    protected function getAuditIdentifier(): string
    {
        // Intentar obtener un identificador significativo
        if (isset($this->attributes['pedido'])) {
            return "Pedido #{$this->attributes['pedido']}";
        }
        
        if (isset($this->attributes['name'])) {
            return $this->attributes['name'];
        }
        
        if (isset($this->attributes['nombre'])) {
            return $this->attributes['nombre'];
        }

        if (isset($this->attributes['prenda'])) {
            return "Prenda: {$this->attributes['prenda']}";
        }

        // Para registros de tableros, incluir más información
        if (isset($this->attributes['modulo'])) {
            $info = "Módulo {$this->attributes['modulo']}";
            if (isset($this->attributes['fecha'])) {
                $info .= " - {$this->attributes['fecha']}";
            }
            return $info;
        }

        if (isset($this->attributes['orden_produccion'])) {
            return "Orden {$this->attributes['orden_produccion']}";
        }

        // Fallback al ID con más contexto
        return "Registro #{$this->getKey()}";
    }

    /**
     * Obtener pedido si existe en el modelo
     */
    protected function getPedidoAttribute()
    {
        return $this->attributes['pedido'] ?? null;
    }

    /**
     * Obtener atributos relevantes para auditoría
     */
    protected function getAuditableAttributes(): array
    {
        // Excluir campos sensibles o innecesarios
        $excluded = ['password', 'remember_token', 'created_at', 'updated_at'];
        
        return array_diff_key($this->attributes, array_flip($excluded));
    }

    /**
     * Generar descripción amigable para actualizaciones
     */
    protected function generateFriendlyUpdateDescription($changes, $original, $identifier): string
    {
        $tableName = $this->getTable();
        
        // Mapeo de nombres de campos a nombres amigables
        $fieldNames = [
            'estado' => 'Estado',
            'area' => 'Área',
            'modulo' => 'Módulo',
            'producida' => 'Cantidad producida',
            'eficiencia' => 'Eficiencia',
            'meta' => 'Meta',
            'tiempo_disponible' => 'Tiempo disponible',
            'cantidad' => 'Cantidad',
            'cliente' => 'Cliente',
            'descripcion' => 'Descripción',
            'nombre' => 'Nombre',
        ];

        // Mapeo de tablas a módulos del sistema
        $tableContexts = [
            'tabla_original' => 'Registro de Órdenes',
            'registro_piso_produccion' => 'Tablero Producción',
            'registro_piso_corte' => 'Tablero Corte',
            'registro_piso_polo' => 'Tablero Polos',
            'entrega_pedido_corte' => 'Entregas Corte',
            'entrega_pedido_costura' => 'Entregas Costura',
            'entrega_bodega_corte' => 'Bodega Corte',
            'entrega_bodega_costura' => 'Bodega Costura',
            'balanceos' => 'Balanceo',
        ];

        $context = $tableContexts[$tableName] ?? 'Sistema';

        // Si es un cambio simple de un solo campo, usar formato corto
        if (count($changes) == 1) {
            $field = array_key_first($changes);
            $fieldName = $fieldNames[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $oldValue = $original[$field] ?? 'N/A';
            $newValue = $changes[$field];

            return "Registro actualizado en {$context} {$identifier}: {$fieldName} cambió de {$oldValue} → {$newValue}";
        }

        // Si son múltiples cambios, listar los principales
        $changesList = [];
        foreach ($changes as $field => $newValue) {
            $fieldName = $fieldNames[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $oldValue = $original[$field] ?? 'N/A';
            $changesList[] = "{$fieldName}: {$oldValue} → {$newValue}";
        }

        return "Registro actualizado en {$context} {$identifier}. Cambios: " . implode(', ', array_slice($changesList, 0, 2)) . 
               (count($changesList) > 2 ? ' y ' . (count($changesList) - 2) . ' más' : '');
    }

    /**
     * Generar descripción amigable para creaciones
     */
    protected function generateFriendlyCreateDescription($identifier): string
    {
        $tableName = $this->getTable();
        
        $tableContexts = [
            'tabla_original' => 'Registro de Órdenes',
            'registro_piso_produccion' => 'Tablero Producción',
            'registro_piso_corte' => 'Tablero Corte',
            'registro_piso_polo' => 'Tablero Polos',
            'entrega_pedido_costura' => 'Entregas Costura',
            'entrega_pedido_corte' => 'Entregas Corte',
            'entrega_bodega_corte' => 'Bodega Corte',
            'entrega_bodega_costura' => 'Bodega Costura',
            'balanceos' => 'Balanceo',
        ];

        $context = $tableContexts[$tableName] ?? 'Sistema';

        return "Registro creado en {$context}: {$identifier}";
    }

    /**
     * Generar descripción amigable para eliminaciones
     */
    protected function generateFriendlyDeleteDescription($identifier): string
    {
        $tableName = $this->getTable();
        
        $tableContexts = [
            'tabla_original' => 'Registro de Órdenes',
            'registro_piso_produccion' => 'Tablero Producción',
            'registro_piso_corte' => 'Tablero Corte',
            'registro_piso_polo' => 'Tablero Polos',
            'entrega_pedido_corte' => 'Entregas Corte',
            'entrega_pedido_costura' => 'Entregas Costura',
            'entrega_bodega_corte' => 'Bodega Corte',
            'entrega_bodega_costura' => 'Bodega Costura',
            'balanceos' => 'Balanceo',
        ];

        $context = $tableContexts[$tableName] ?? 'Sistema';

        return "Registro eliminado de {$context}: {$identifier}";
    }
}
