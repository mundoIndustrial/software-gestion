# Optimización de Performance - Supervisor Pedidos

## SQL para ejecutar en la VPS

Ejecuta estos comandos SQL en tu base de datos `mundo_bd5`:

```sql
-- ============================================
-- ÍNDICES PARA OPTIMIZAR SUPERVISOR-PEDIDOS
-- ============================================

-- 1. Índice en prendas_pedido para subconsultas de conteo
-- Permite búsquedas rápidas de prendas por pedido
ALTER TABLE `prendas_pedido` ADD INDEX `prendas_pedido_pedido_produccion_id_index` (`pedido_produccion_id`);

-- 2. Índice compuesto en pedido_anexos_historial
-- Optimiza la subconsulta de última actividad
ALTER TABLE `pedido_anexos_historial` ADD INDEX `pedido_anexos_historial_pedido_produccion_id_created_at_index` (`pedido_produccion_id`, `created_at`);

-- ============================================
-- VERIFICAR QUE LOS ÍNDICES SE CREARON
-- ============================================

-- Ver índices en prendas_pedido
SHOW INDEX FROM prendas_pedido WHERE Key_name = 'prendas_pedido_pedido_produccion_id_index';

-- Ver índices en pedido_anexos_historial
SHOW INDEX FROM pedido_anexos_historial WHERE Key_name = 'pedido_anexos_historial_pedido_produccion_id_created_at_index';

-- Ver todos los índices de ambas tablas (para verificación completa)
SHOW INDEX FROM prendas_pedido;
SHOW INDEX FROM pedido_anexos_historial;
```

## Cómo ejecutar en la VPS

### Opción 1: PhpMyAdmin
1. Abre PhpMyAdmin
2. Selecciona la base de datos `mundo_bd5`
3. Ve a la pestaña "SQL"
4. Copia y pega el SQL anterior
5. Haz clic en "Ejecutar"

### Opción 2: Línea de comandos (SSH)
```bash
mysql -u usuario -p mundo_bd5 < OPTIMIZACION_PERFORMANCE.sql
```

O directamente:
```bash
mysql -u usuario -p mundo_bd5 -e "ALTER TABLE prendas_pedido ADD INDEX prendas_pedido_pedido_produccion_id_index (pedido_produccion_id);"
mysql -u usuario -p mundo_bd5 -e "ALTER TABLE pedido_anexos_historial ADD INDEX pedido_anexos_historial_pedido_produccion_id_created_at_index (pedido_produccion_id, created_at);"
```

### Opción 3: Laravel Artisan (Ya ejecutado)
```bash
php artisan migrate --path=database/migrations/2026_04_22_150045_add_indexes_for_performance.php
```

## Cambios en el código PHP

Se modificaron dos métodos en `app/Application/SupervisorPedidos/Services/PedidoProduccionReadService.php`:

### 1. `listOrders()` - Línea 34-52
**Cambio:** Reemplazó `withCount(['prendas', 'epps'])` con subconsultas eficientes

### 2. `orderAndPaginate()` - Línea 693-710  
**Cambio:** Reemplazó LEFT JOIN con subconsulta más eficiente

## Resultados esperados

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Carga de 15 registros | 824ms | 150-250ms | 3-5x más rápido |
| Renderizado en frontend | 3.9ms | 3.9ms | Sin cambios |
| Tiempo total tabla lista | 839ms | 250ms | 70% de mejora |

## Verificación

Después de ejecutar el SQL, recarga la página en `http://localhost:8000/supervisor-pedidos` y revisa la consola del navegador:

```javascript
// Deberías ver logs como estos:
📡 Cargando página 1...
🔌 API respondió en ~200-300ms  // ← Antes era 824ms
🎨 Renderizado: 15 filas en 3.90ms
✅ Tabla lista en ~250ms  // ← Antes era 839ms
```

## Rollback (si es necesario)

Si necesitas deshacer los cambios:

```sql
ALTER TABLE `prendas_pedido` DROP INDEX `prendas_pedido_pedido_produccion_id_index`;
ALTER TABLE `pedido_anexos_historial` DROP INDEX `pedido_anexos_historial_pedido_produccion_id_created_at_index`;
```
