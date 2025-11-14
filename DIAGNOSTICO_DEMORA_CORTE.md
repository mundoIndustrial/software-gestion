# 🔍 Diagnóstico de Demora en Corte - Guía de Debugging

## Cómo Identificar Dónde Está la Demora

Se han añadido **timing detallados** en el frontend y backend para identificar exactamente dónde se está perdiendo tiempo.

### Paso 1: Abre la Consola del Navegador (F12)

1. Presiona **F12** en el navegador
2. Abre tab **"Console"**
3. Ve a **tableros > sección Corte**

### Paso 2: Edita un Campo (Hora, Operario, Máquina o Tela)

Cuando edites, verás logs como estos:

```
🕐 TIMINGS TOTALES:
- Búsqueda: 50.45ms
- Cache hit: N/A
- PATCH request: 127.38ms
- TOTAL: 204.12ms
```

### Interpretación de Timings

**Si ves esto es RÁPIDO (< 200ms total):**
```
- Búsqueda: 45ms          ← OK (servidor rápido)
- PATCH request: 120ms    ← OK (PATCH rápido)
```

**Si ves esto es LENTO (> 500ms):**
```
- Búsqueda: 500ms         ← ⚠️ LA BÚSQUEDA ES LENTA
- PATCH request: 800ms    ← ⚠️ EL PATCH ES LENTO
```

**Si ves esto es CACHED (instantáneo):**
```
- Cache hit: 2ms          ← ✅ VIENE DEL CACHÉ
- PATCH request: 150ms
- TOTAL: 152ms
```

---

## Análisis por Casos

### Caso 1: Búsqueda es Lenta (> 300ms)

**Posibles causas:**
1. La BD está lenta (índices faltantes)
2. Hay muchas comparaciones en la búsqueda
3. La tabla tiene muchos registros duplicados

**Verificar:**
```bash
# En MySQL/MariaDB:
SELECT * FROM INFORMATION_SCHEMA.STATISTICS 
WHERE TABLE_NAME IN ('horas', 'users', 'maquinas', 'telas') 
AND COLUMN_NAME IN ('hora', 'name', 'nombre_maquina', 'nombre_tela');

# Si hay índices, debería mostrar filas para cada tabla
```

### Caso 2: PATCH es Lento (> 500ms)

**Posibles causas:**
1. El UPDATE en BD es lento
2. El recálculo de valores derivados es lento
3. El broadcast está bloqueando

**Verificar en Laravel logs:**
```bash
tail -f storage/logs/laravel.log | grep "TablerosController::update"
```

Debería mostrar:
```
[2024-11-14] local.INFO: TablerosController::update - Solo relaciones 
{"duration_ms":127.5,"registro_id":123,"section":"corte"}
```

Si `duration_ms` es > 500, el backend es lento.

### Caso 3: Todo es Rápido Excepto la Primera Vez

**Normal y esperado:**
- Primera búsqueda: 80-150ms (búsqueda en BD)
- Segunda búsqueda del mismo valor: 0-5ms (caché)

Esto es **correcto y no hay problema**.

---

## Cheklist de Verificación

### ✅ Verificar Índices

```bash
# En la consola MySQL:
mysql> SHOW INDEXES FROM horas;
# Debería mostrar:
# - idx_horas_hora_unique en columna 'hora'

mysql> SHOW INDEXES FROM users;
# Debería mostrar índices en 'name' o 'email'

mysql> SHOW INDEXES FROM maquinas;
# Debería mostrar índices en 'nombre_maquina'

mysql> SHOW INDEXES FROM telas;
# Debería mostrar índices en 'nombre_tela'
```

### ✅ Verificar Migraciones Aplicadas

```bash
php artisan migrate:status
# Debería mostrar DONE para:
# - 2024_11_14_add_unique_index_horas_table
# - 2024_11_14_add_indexes_to_registro_piso_corte
```

### ✅ Verificar Caché Funciona

En Console, edita la MISMA hora dos veces:

```
Primera edición:
📤 Enviando PATCH a /tableros/123
⏱️ TIMINGS: Búsqueda: 82.34ms

Segunda edición (misma hora):
📤 Enviando PATCH a /tableros/124
✅ hora obtenido del caché (0.45ms)
⏱️ TIMINGS: Cache hit: 0.45ms ← ✅ CACHÉ FUNCIONANDO
```

---

## Logs Server-Side

### Ver logs de timing en server

```bash
# Terminal, en raíz del proyecto:
tail -f storage/logs/laravel.log

# Cuando edites en Corte, verás:
TablerosController::update - Solo relaciones {"duration_ms":127.38,...}
```

Si `duration_ms` es siempre > 500, hay un problema en el servidor.

---

## Possibles Soluciones Según el Diagnóstico

### Si Búsqueda es Lenta

**Solución 1: Verificar índices**
```bash
php artisan migrate
```

**Solución 2: Limpiar duplicados**
```sql
-- En MySQL:
SELECT hora, COUNT(*) FROM horas GROUP BY hora;
-- Si hay duplicados, eliminar:
DELETE FROM horas WHERE hora IN (
  SELECT hora FROM (
    SELECT hora FROM horas GROUP BY hora HAVING COUNT(*) > 1
  ) t
);
```

### Si PATCH es Lento

**Solución 1: Verificar que solo editas relaciones**
- Cuando editas hora/operario/máquina/tela, NO debería recalcular
- Los logs deben mostrar: "Solo relaciones"

**Solución 2: Desabilitar el recálculo temporalmente**
Si el backend está muy lento, podemos saltar el recálculo para tests.

### Si hay Lag en Tiempo Real

**Verificar WebSocket:**
```bash
# En console del navegador:
echo "¿WebSocket conectado?"; 
# Si conectó, deberías ver mensajes en Console al editar en otra ventana
```

---

## Próximos Pasos

Después de obtener los timings:

1. **Comparte los logs de Console** (copia toda la salida de los TIMINGS)
2. **Comparte la salida de:**
   ```bash
   tail storage/logs/laravel.log
   ```
3. **Describe exactamente cuándo es lento:**
   - ¿Primera edición en la sesión?
   - ¿Siempre para el mismo campo?
   - ¿Para campo específico (solo hora)?

Con esa información podemos hacer optimizaciones más específicas.

---

## Resumen Rápido

**Instrucciones en 30 segundos:**

1. F12 → Console
2. Edita un campo en Corte
3. Copia los logs de "TIMINGS TOTALES"
4. Comparte conmigo

Con eso identificamos el cuello de botella exacto.

