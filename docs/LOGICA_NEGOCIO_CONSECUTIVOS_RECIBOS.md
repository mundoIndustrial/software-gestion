# Lógica de Negocio: Generación de Consecutivos de Recibos

## 📋 Overview

Este documento describe la lógica de negocio implementada para la generación automática de consecutivos de recibos cuando un pedido cambia a estado `PENDIENTE_INSUMOS`.

## 🎯 Momento de Ejecución

### Disparador
- **Evento**: Cambio de estado del pedido
- **Condición**: `estado_anterior != PENDIENTE_INSUMOS` Y `estado_nuevo == PENDIENTE_INSUMOS`
- **Responsable**: Aprobación del SUPERVISOR_PEDIDOS

### No se ejecuta cuando:
- El asesor crea el pedido
- Cartera aprueba/rechaza el pedido
- Otros cambios de estado
- El pedido ya tiene consecutivos generados

## 🔍 Lógica por Prenda Individual

Cada prenda del pedido genera sus propios consecutivos según las siguientes reglas:

### 1. COSTURA
- **Se genera**: Si `de_bodega = false`
- **No se genera**: Si `de_bodega = true`
- **Razón**: Las prendas de bodega ya vienen hechas, no necesitan costura

### 2. ESTAMPADO
- **Se genera**: Si la prenda tiene proceso de tipo "ESTAMPADO"
- **Independiente de**: `de_bodega`
- **Razón**: El estampado siempre necesita recibo, sin importar el origen de la prenda

### 3. BORDADO
- **Se genera**: Si la prenda tiene proceso de tipo "BORDADO"
- **Independiente de**: `de_bodega`
- **Razón**: El bordado siempre necesita recibo, sin importar el origen de la prenda

### 4. REFLECTIVO
- **Se genera**: Si la prenda tiene proceso de tipo "REFLECTIVO"
- **Independiente de**: `de_bodega`
- **Razón**: El reflectivo siempre necesita recibo, sin importar el origen de la prenda

## 📊 Tabla de Decisiones

| de_bodega | Procesos | COSTURA | ESTAMPADO | BORDADO | REFLECTIVO | Total Consecutivos |
|-----------|----------|---------|-----------|---------|------------|-------------------|
| false | Ninguno | ✅ | ❌ | ❌ | ❌ | 1 |
| false | Estampado | ✅ | ✅ | ❌ | ❌ | 2 |
| false | Bordado, Reflectivo | ✅ | ❌ | ✅ | ✅ | 3 |
| true | Ninguno | ❌ | ❌ | ❌ | ❌ | 0 |
| true | Estampado | ❌ | ✅ | ❌ | ❌ | 1 |
| true | Bordado, Reflectivo | ❌ | ❌ | ✅ | ✅ | 2 |

## 🔄 Flujo de Generación

### 1. Detección del Cambio
```php
// Observer detecta cambio de estado
if ($pedido->wasChanged('estado')) {
    $estadoAnterior = $pedido->getOriginal('estado');
    $estadoNuevo = $pedido->estado;
    
    // Solo ejecutar si cambia a PENDIENTE_INSUMOS
    if ($estadoAnterior !== 'PENDIENTE_INSUMOS' && $estadoNuevo === 'PENDIENTE_INSUMOS') {
        $this->generarConsecutivos($pedido);
    }
}
```

### 2. Análisis por Prenda
```php
foreach ($pedido->prendas as $prenda) {
    $tiposPrenda = [];
    
    // COSTURA: Solo si no es de bodega
    if (!$prenda->de_bodega) {
        $tiposPrenda[] = 'COSTURA';
    }
    
    // Procesos: Siempre generan consecutivos
    foreach ($prenda->procesos as $proceso) {
        switch ($proceso->tipo) {
            case 'ESTAMPADO': $tiposPrenda[] = 'ESTAMPADO'; break;
            case 'BORDADO': $tiposPrenda[] = 'BORDADO'; break;
            case 'REFLECTIVO': $tiposPrenda[] = 'REFLECTIVO'; break;
        }
    }
}
```

### 3. Generación de Consecutivos
```php
foreach ($tiposPorPrenda as $prendaId => $tipos) {
    foreach ($tipos as $tipo) {
        // Bloquear registro maestro (FOR UPDATE)
        $consecutivo = $this->obtenerSiguienteConsecutivo($tipo);
        
        // Insertar registro para el pedido
        DB::table('consecutivos_recibos_pedidos')->insert([
            'pedido_produccion_id' => $pedido->id,
            'tipo_recibo' => $tipo,
            'consecutivo_actual' => $consecutivo,
            // ...
        ]);
    }
}
```

## 💾 Estructura de Datos

### Tablas Involucradas

1. **consecutivos_recibos** (Maestra)
   - `tipo_recibo`: COSTURA, ESTAMPADO, BORDADO, REFLECTIVO
   - `año`: Año actual
   - `consecutivo_actual`: Último número usado
   - `activo`: Si está en uso

2. **consecutivos_recibos_pedidos** (Detalles)
   - `pedido_produccion_id`: ID del pedido
   - `tipo_recibo`: Tipo de recibo
   - `consecutivo_actual`: Número asignado
   - `created_at`: Fecha de generación

### Formato de Consecutivos
- **Sin prefijos**: Solo números secuenciales
- **Ejemplos**: 45926, 22607, 5
- **Por año**: Reinicia cada año (2026-01-01)

## 🛡️ Características de Seguridad

### Transaccionalidad
- Todo o nada: Si falla algún consecutivo, se revierte todo
- Bloqueo de registros: `FOR UPDATE` previene duplicados
- Atomicidad: El pedido no queda en estado inconsistente

### Concurrencia
- Múltiples supervisores pueden aprobar pedidos simultáneamente
- Cada consecutivo es único y secuencial
- Sin race conditions gracias a los bloqueos

### Idempotencia
- No genera duplicados si se ejecuta múltiples veces
- Verificación previa: `yaTieneConsecutivos()`
- Solo se ejecuta una vez por pedido

## 📈 Ejemplos Prácticos

### Ejemplo 1: Pedido Mixto
```
Pedido #123456
├── Prenda 1: Camisa (de_bodega=false, procesos=Bordado,Estampado)
│   └── Consecutivos: COSTURA + BORDADO + ESTAMPADO = 3
├── Prenda 2: Polo (de_bodega=true, procesos=Bordado)
│   └── Consecutivos: BORDADO = 1
└── Prenda 3: Gorra (de_bodega=true, procesos=ninguno)
    └── Consecutivos: 0
Total: 4 consecutivos
```

### Ejemplo 2: Solo Prendas de Bodega
```
Pedido #123457
├── Prenda 1: Polo (de_bodega=true, procesos=Estampado)
│   └── Consecutivos: ESTAMPADO = 1
└── Prenda 2: Gorra (de_bodega=true, procesos=ninguno)
    └── Consecutivos: 0
Total: 1 consecutivo
```

### Ejemplo 3: Solo Prendas de Producción
```
Pedido #123458
├── Prenda 1: Camisa (de_bodega=false, procesos=ninguno)
│   └── Consecutivos: COSTURA = 1
└── Prenda 2: Pantalón (de_bodega=false, procesos=Reflectivo)
    └── Consecutivos: COSTURA + REFLECTIVO = 2
Total: 3 consecutivos
```

## 🔧 Implementación Técnica

### Archivos Principales
- `app/Services/ConsecutivosRecibosService.php`: Lógica principal
- `app/Observers/PedidoProduccionObserver.php`: Detección de cambios
- `app/Http/Controllers/SupervisorPedidosController.php`: Punto de disparo

### Métodos Clave
- `generarConsecutivosSiAplica()`: Orquestador principal
- `determinarTiposReciboPorPrenda()`: Análisis por prenda
- `obtenerConsecutivosPedido()`: Consulta de resultados

### Logging
- Todos los pasos están logueados para auditoría
- Niveles: INFO (proceso), ERROR (fallos)
- Contexto: pedido_id, numero_pedido, usuario

## 🎯 Reglas de Negocio Resumidas

1. **Disparador Único**: Solo por cambio a `PENDIENTE_INSUMOS`
2. **Por Prenda**: Cada prenda genera sus propios consecutivos
3. **COSTURA Especial**: Solo para prendas que no son de bodega
4. **Procesos Siempre**: Los procesos siempre generan consecutivos
5. **Sin Prefijos**: Solo números secuenciales
6. **Transaccional**: Todo o nada
7. **Único**: No hay duplicados
8. **Anual**: Reinicia cada año

## 📞 Soporte y Mantenimiento

### Monitoreo
- Revisar logs: `storage/logs/laravel.log`
- Buscar: "🔢 Consecutivos"
- Métricas: Tiempo de generación, cantidad por pedido

### Troubleshooting
- **No genera consecutivos**: Verificar estado anterior/nuevo
- **Consecutivos duplicados**: Revisar bloqueos y transacciones
- **Faltan tipos**: Verificar procesos y de_bodega de prendas

### Modificaciones
- Para agregar nuevos tipos: Modificar enum y switch
- Para cambiar formato: Actualizar `formatearConsecutivo()`
- Para modificar disparador: Cambiar observer o controlador

---

**Versión**: 1.0  
**Fecha**: 30/01/2026  
**Autor**: Sistema de Gestión Industrial  
**Estado**: Implementado y Verificado
