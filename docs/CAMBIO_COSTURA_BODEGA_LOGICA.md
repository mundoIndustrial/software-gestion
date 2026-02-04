# CAMBIO IMPORTANTE: Lógica de COSTURA-BODEGA en CarteraPedidosController

## Problema Identificado
Cuando CARTERA aprobaba un pedido, el método `generarConsecutivoCosturaBodega()` verificaba si COSTURA-BODEGA ya existía y retornaba temprano sin incrementar el consecutivo.

**Log anterior:**
```
[CARTERA] COSTURA-BODEGA ya existe para este pedido
```

Esto ocurrió porque el seeder `AgregarCosturaBodegaRecibosSeeder` pobló todos los pedidos con COSTURA-BODEGA=0 anteriormente.

---

## Solución Implementada

### Nueva Lógica (CarteraPedidosController::generarConsecutivoCosturaBodega)

**SIEMPRE** incrementa el consecutivo cuando CARTERA aprueba, independientemente de si ya existe:

```
1. Obtener consecutivo actual de COSTURA-BODEGA de "consecutivos_recibos"
2. Incrementar: nuevoConsecutivo = consecutivo_actual + 1
3. Actualizar consecutivo en "consecutivos_recibos"
4. Verificar si existe registro para este pedido en "consecutivos_recibos_pedidos"
   - SI existe → ACTUALIZAR consecutivo_actual al nuevo valor
   - NO existe → INSERTAR nuevo registro con nuevo consecutivo
5. Registrar logs diferenciados
```

### Cambios de Comportamiento

| Escenario | Antes | Después |
|-----------|-------|---------|
| 1ª aprobación | Inserta con consecutivo=1 | Inserta con consecutivo=1 |
| 2ª aprobación (mismo pedido) | Retorna (no hace nada) | **Actualiza a consecutivo=2** |
| Cada aprobación | Nada | **Incrementa consecutivo_actual** |

### Logs Ahora Diferenciados

**Primera aprobación:**
```
[CARTERA] Consecutivo COSTURA-BODEGA creado (nuevo)
```

**Subsecuentes aprobaciones:**
```
[CARTERA] Consecutivo COSTURA-BODEGA actualizado (ya existía)
```

---

## Flujo de Datos

### Base de Datos (tabla consecutivos_recibos)

```sql
-- ANTES de primera aprobación
SELECT consecutivo_actual FROM consecutivos_recibos WHERE tipo_recibo='COSTURA-BODEGA';
-- Resultado: 0

-- DESPUÉS de primera aprobación
-- Resultado: 1

-- DESPUÉS de segunda aprobación
-- Resultado: 2
```

### Tabla consecutivos_recibos_pedidos

```sql
-- PRIMER pedido aprobado
INSERT: {pedido_id: 2, tipo_recibo: COSTURA-BODEGA, consecutivo_actual: 1, consecutivo_inicial: 1}

-- MISMO pedido aprobado nuevamente
UPDATE: {consecutivo_actual: 2}  -- Incrementado

-- SEGUNDO pedido aprobado
INSERT: {pedido_id: 3, tipo_recibo: COSTURA-BODEGA, consecutivo_actual: 2, consecutivo_inicial: 2}
```

---

## Archivos Modificados

### app/Http/Controllers/CarteraPedidosController.php

**Método:** `private function generarConsecutivoCosturaBodega(PedidoProduccion $pedido): void`

**Cambios:**
1. ✅ Eliminó verificación temprana (`$existe`)
2. ✅ Ahora siempre incrementa consecutivo
3. ✅ Distingue entre CREATE vs UPDATE
4. ✅ Logs diferenciados por acción

**Líneas:** 314-391 (aprox.)

---

## Limpieza Realizada

Se ejecutó `reset-costura-bodega.php` que:
1. ✅ Eliminó 6 registros COSTURA-BODEGA con consecutivo_actual=0 (del seeder antiguo)
2. ✅ Reseteó consecutivo COSTURA-BODEGA a 0 en "consecutivos_recibos"
3. ✅ Base de datos lista para nueva aprobación

---

## Próximo Paso

### Para probar:
1. Abrir módulo CARTERA
2. Aprobar un pedido en estado "pendiente_cartera"
3. Verificar logs:
   ```
   [CARTERA] Consecutivo COSTURA-BODEGA creado (nuevo) {consecutivo: 1}
   ```
4. Aprobar el MISMO pedido nuevamente
5. Verificar logs:
   ```
   [CARTERA] Consecutivo COSTURA-BODEGA actualizado (ya existía) {consecutivo_anterior: 1, consecutivo_nuevo: 2}
   ```

---

## Validación

✅ Sintaxis PHP verificada (sin errores)
✅ Base de datos limpiada
✅ Lógica lista para flujo real

