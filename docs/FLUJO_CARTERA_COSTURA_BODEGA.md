# FLUJO: Generación de Consecutivo COSTURA-BODEGA por Rol CARTERA

## Resumen
Cuando el rol **CARTERA** aprueba un pedido, se genera automáticamente un consecutivo COSTURA-BODEGA para ese pedido. Este flujo asegura que todo pedido aprobado por cartera está listo para el módulo bodeguero.

---

## Archivo Modificado: CarteraPedidosController.php

### Método Principal
- **`aprobarPedido($id, Request $request)`** (línea 121)
  - Cuando CARTERA aprueba un pedido:
    1. Genera número de pedido secuencial
    2. Cambia estado a `PENDIENTE_SUPERVISOR`
    3. **Llama a `generarConsecutivoCosturaBodega($pedido)` ← NUEVA LÓGICA**

### Nuevo Método Privado
- **`generarConsecutivoCosturaBodega(PedidoProduccion $pedido)`** (línea 314)

```php
private function generarConsecutivoCosturaBodega(PedidoProduccion $pedido): void
```

#### Lógica:
1. **Verificar si ya existe** COSTURA-BODEGA para este pedido
   - Si existe → Log info y retorna (evita duplicados)
   - Si no existe → Continúa

2. **Obtener consecutivo actual** de `consecutivos_recibos` tabla
   - Usa `lockForUpdate()` para evitar race conditions
   - Verifica que el registro exista

3. **Incrementar consecutivo** en `consecutivos_recibos`
   - `nuevoConsecutivo = consecutivo_actual + 1`
   - Actualiza la tabla para la próxima aprobación

4. **Insertar registro** en `consecutivos_recibos_pedidos`
   - `tipo_recibo` = 'COSTURA-BODEGA'
   - `consecutivo_actual` = `consecutivo_inicial` = valor incrementado
   - `activo` = 1
   - `pedido_produccion_id` = ID del pedido
   - `prenda_id` = null (es recibo de bodega, no por prenda)

5. **Registrar en logs**
   - Info: Consecutivo generado exitosamente
   - Warning: Si no existe configuración de COSTURA-BODEGA
   - Error: Si falla la inserción

---

## Tablas Involucradas

### 1. consecutivos_recibos
```sql
-- Tabla maestra de consecutivos por tipo de recibo
id | tipo_recibo    | consecutivo_actual | año  | activo
8  | COSTURA-BODEGA | 0                  | 2026 | 1
```

**Acción:** 
- Se incrementa `consecutivo_actual` cada vez que se aprueba un pedido

### 2. consecutivos_recibos_pedidos
```sql
-- Tabla de consecutivos por pedido
id | pedido_produccion_id | tipo_recibo    | consecutivo_actual | activo
N  | {pedido_id}          | COSTURA-BODEGA | {valor_incremental}| 1
```

**Acción:**
- Se inserta NUEVO registro cuando CARTERA aprueba el pedido
- Si ya existe → Se evita duplicado

### 3. pedidos_produccion
```sql
-- Tabla de pedidos
id | numero_pedido | estado             | aprobado_por_usuario_cartera | aprobado_por_cartera_en
N  | {secuencial}  | PENDIENTE_SUPERVISOR | {user_id}                 | {timestamp}
```

**Estados involucrados:**
- `pendiente_cartera` → CARTERA espera aprobación
- `PENDIENTE_SUPERVISOR` → Después de aprobación por CARTERA
- (El COSTURA-BODEGA se crea EN ESTE PUNTO)

---

## Flujo Completo

### 1. CARTERA abre módulo de Cartera
```
/cartera/pedidos
```
- Ve lista de pedidos con estado `pendiente_cartera`

### 2. CARTERA selecciona un pedido y hace clic en "Aprobar"
```
POST /api/cartera/pedidos/{id}/aprobar
```

### 3. CarteraPedidosController::aprobarPedido()
```
DB::transaction():
  ├─ Buscar pedido por ID
  ├─ Generar número secuencial (via PedidoSequenceService)
  ├─ Actualizar pedido:
  │  ├─ numero_pedido = {secuencial}
  │  ├─ estado = PENDIENTE_SUPERVISOR
  │  ├─ aprobado_por_usuario_cartera = {user_id}
  │  └─ aprobado_por_cartera_en = now()
  │
  └─ ► generarConsecutivoCosturaBodega($pedido)
      ├─ Verificar duplicado
      ├─ Obtener consecutivo actual COSTURA-BODEGA
      ├─ Incrementar consecutivo_actual
      ├─ Actualizar consecutivos_recibos
      ├─ Insertar consecutivos_recibos_pedidos
      └─ Registrar logs
```

### 4. Response al frontend
```json
{
  "success": true,
  "message": "Pedido aprobado correctamente",
  "numero_pedido": "{secuencial_generado}"
}
```

### 5. Estado final del pedido
```
✓ Pedido tienen número secuencial
✓ Estado = PENDIENTE_SUPERVISOR (listo para siguiente rol)
✓ COSTURA-BODEGA registro creado ← bodeguero puede verlo
✓ Logs registrados para auditoría
```

---

## Seguridad y Manejo de Errores

### Prevención de Race Conditions
- Usa `lockForUpdate()` en consecutivos_recibos
- Dentro de transacción DB para atomicidad

### Validaciones
- Verifica que pedido exista
- Verifica que configuración COSTURA-BODEGA exista
- Previene duplicados (revisa si ya existe registro)

### Logs por Nivel
- **INFO**: Operaciones exitosas (para auditoría)
- **WARNING**: Configuración faltante
- **ERROR**: Excepciones y fallas (con contexto completo)

### Manejo de Excepciones
```php
try {
    // Lógica
} catch (\Exception $e) {
    \Log::error('[CARTERA] Error...', ['exception' => $e]);
    // Transacción se revierte automáticamente
    // El frontend recibe error 500 con mensaje
}
```

---

## Verificación Post-Implementación

### ✓ Cumplido (Implementación)
1.  Método `generarConsecutivoCosturaBodega()` agregado a CarteraPedidosController
2.  Se llama automáticamente cuando CARTERA aprueba
3.  Incrementa consecutivo en `consecutivos_recibos`
4.  Inserta registro en `consecutivos_recibos_pedidos`
5.  Registra logs para auditoría
6.  Maneja duplicados
7.  Usa transacciones para atomicidad
8.  Sintaxis PHP validada (sin errores)

###  Por Probar (Testing)
1. ⏳ Aprobar un pedido desde módulo de Cartera
2. ⏳ Verificar que COSTURA-BODEGA se crea en BD
3. ⏳ Verificar que consecutivo se incrementa
4. ⏳ Verificar que bodeguero ve el pedido en dashboard
5. ⏳ Verificar logs en `storage/logs/`

---

## Ejemplo de Ejecución

### Base de datos ANTES de aprobación
```sql
-- consecutivos_recibos
SELECT * FROM consecutivos_recibos WHERE tipo_recibo = 'COSTURA-BODEGA';
-- Resultado: consecutivo_actual = 0

-- pedidos_produccion
SELECT id, numero_pedido, estado FROM pedidos_produccion WHERE id = 123;
-- Resultado: 123 | NULL | pendiente_cartera

-- consecutivos_recibos_pedidos
SELECT * FROM consecutivos_recibos_pedidos WHERE pedido_produccion_id = 123 AND tipo_recibo = 'COSTURA-BODEGA';
-- Resultado: (no hay registro)
```

### CARTERA aprueba pedido 123
```
POST /api/cartera/pedidos/123/aprobar
```

### Base de datos DESPUÉS de aprobación
```sql
-- consecutivos_recibos (INCREMENTADO)
SELECT * FROM consecutivos_recibos WHERE tipo_recibo = 'COSTURA-BODEGA';
-- Resultado: consecutivo_actual = 1

-- pedidos_produccion (ACTUALIZADO)
SELECT id, numero_pedido, estado FROM pedidos_produccion WHERE id = 123;
-- Resultado: 123 | 100 | PENDIENTE_SUPERVISOR

-- consecutivos_recibos_pedidos (NUEVO REGISTRO)
SELECT * FROM consecutivos_recibos_pedidos WHERE pedido_produccion_id = 123 AND tipo_recibo = 'COSTURA-BODEGA';
-- Resultado: 
-- id | pedido_produccion_id | tipo_recibo    | consecutivo_actual | consecutivo_inicial | activo | created_at
-- N  | 123                  | COSTURA-BODEGA | 1                  | 1                   | 1      | 2026-02-04...
```

---

## Conclusión

La lógica está completamente implementada. El flujo es:

1. **CARTERA aprueba pedido** → `CarteraPedidosController::aprobarPedido()`
2. **Se ejecuta automáticamente** → `generarConsecutivoCosturaBodega()`
3. **Se crea registro COSTURA-BODEGA** → En `consecutivos_recibos_pedidos`
4. **Bodeguero lo ve** → En el dashboard filtrando por `tipo_recibo = 'COSTURA-BODEGA'`

Todo está integrado, sin necesidad de pasos manuales adicionales.
