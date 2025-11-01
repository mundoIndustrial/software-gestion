# 🎯 Casos Especiales Manejados por el Script

## 📋 Resumen

El script de Google Apps Script está preparado para manejar automáticamente todos los casos especiales que pueden aparecer en los datos del Excel.

## ✅ Casos Especiales Implementados

### 1. **Múltiples Telas en un Solo Registro**

**Ejemplo:** `shambray-drill`, `POLUX-NAFLIX`, `OXFORD/DRILL`

**Comportamiento:**
- El script detecta automáticamente cuando hay múltiples telas separadas por `-` o `/`
- Crea **un registro separado por cada tela**
- Cada tela se crea en la tabla `telas` si no existe
- Se generan tiempos de ciclo para cada combinación tela-máquina

**Ejemplo de datos:**
```
TELA: shambray-drill
MAQUINA: VERTICAL
CANTIDAD: 50
```

**Resultado en BD:**
```sql
-- Se crean 2 registros:
-- Registro 1: SHAMBRAIN + VERTICAL + 50 unidades
-- Registro 2: DRILL + VERTICAL + 50 unidades
```

### 2. **Máquina "N.A" (No Aplica)**

**Ejemplo:** `N.A`, `N/A`, `NA`, `NINGUNA`

**Comportamiento:**
- Se detecta automáticamente
- `maquina_id` se guarda como `NULL` en la base de datos
- NO se crea registro en la tabla `maquinas`
- NO se crea `tiempo_ciclo` (porque no hay máquina)

**Ejemplo de datos:**
```
MAQUINA: N.A
ACTIVIDAD: EXTENDER/TRAZAR
```

**Resultado en BD:**
```sql
INSERT INTO registro_piso_corte (..., maquina_id, ...) 
VALUES (..., NULL, ...);
```

### 3. **Formato de Hora "HORA 01", "HORA 02", etc.**

**Ejemplo:** `HORA 07`, `HORA 08`, `HORA 01`

**Comportamiento:**
- Se normaliza automáticamente al formato del seeder
- Se extrae el número de hora
- Se convierte al rango completo

**Mapeo automático:**
```
HORA 01 → 08:00am - 09:00am (hora = 1)
HORA 02 → 09:00am - 10:00am (hora = 2)
HORA 03 → 10:00am - 11:00am (hora = 3)
HORA 04 → 11:00am - 12:00pm (hora = 4)
HORA 05 → 12:00pm - 01:00pm (hora = 5)
HORA 06 → 01:00pm - 02:00pm (hora = 6)
HORA 07 → 02:00pm - 03:00pm (hora = 7)
HORA 08 → 03:00pm - 04:00pm (hora = 8)
HORA 09 → 04:00pm - 05:00pm (hora = 9)
HORA 10 → 05:00pm - 06:00pm (hora = 10)
HORA 11 → 06:00pm - 07:00pm (hora = 11)
HORA 12 → 07:00pm - 08:00pm (hora = 12)
```

### 4. **Variaciones en Nombres de Telas**

**Ejemplo:** `shambray` vs `SHAMBRAIN`, `Polux` vs `POLUX`

**Comportamiento:**
- Todo se convierte a MAYÚSCULAS automáticamente
- Variaciones conocidas se normalizan:
  - `shambray` → `SHAMBRAIN`
  - `shambre` → `SHAMBRAIN`

**Puedes agregar más variaciones en el código:**
```javascript
const variaciones = {
  'SHAMBRAY': 'SHAMBRAIN',
  'SHAMBRE': 'SHAMBRAIN',
  'TU_VARIACION': 'NOMBRE_CORRECTO'
};
```

### 5. **Números con Comas Decimales**

**Ejemplo:** `3,480` en lugar de `3480` o `3.480`

**Comportamiento:**
- Las comas se convierten automáticamente a puntos
- Se limpian caracteres no numéricos
- Se preservan hasta 9 decimales

**Función `limpiarNumero()`:**
```javascript
"3,480" → "3.480" → 3.48
"1.234,56" → "1234.56" → 1234.56
```

### 6. **Paradas con Variaciones de Mayúsculas**

**Ejemplo:** `APUNTES`, `Apuntes`, `apuntes`

**Comportamiento:**
- Se guardan tal cual (respetando el formato original)
- Se escapan comillas simples para evitar errores SQL

### 7. **Operarios Nuevos (No en el Seeder)**

**Ejemplo:** Un operario llamado `MARIA` que no está en el seeder

**Comportamiento:**
- Se crea automáticamente en la tabla `users`
- Se asigna `role_id = 3` (cortador)
- Email generado: `maria@mundoindustrial.com`
- Password genérico hasheado

**SQL generado:**
```sql
INSERT IGNORE INTO users (name, email, password, role_id, created_at, updated_at)
SELECT 'MARIA', 'maria@mundoindustrial.com', '$2y$10$...', 
       (SELECT id FROM roles WHERE name = 'cortador' LIMIT 1),
       NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE UPPER(name) = 'MARIA');
```

### 8. **Telas Nuevas (No en el Seeder)**

**Ejemplo:** Una tela llamada `NUEVA_TELA` que no está en el seeder

**Comportamiento:**
- Se crea automáticamente en la tabla `telas`
- Se calcula el tiempo de ciclo:
  - Si está en Grupo 1 → tiempos específicos
  - Si está en Grupo 2 → tiempos específicos
  - Si es nueva → tiempo por defecto = 97

**SQL generado:**
```sql
INSERT IGNORE INTO telas (nombre_tela, created_at, updated_at)
SELECT 'NUEVA_TELA', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM telas WHERE nombre_tela = 'NUEVA_TELA');

INSERT IGNORE INTO tiempo_ciclos (tela_id, maquina_id, tiempo_ciclo, ...)
SELECT 
  (SELECT id FROM telas WHERE nombre_tela = 'NUEVA_TELA' LIMIT 1),
  (SELECT id FROM maquinas WHERE nombre_maquina = 'BANANA' LIMIT 1),
  97,
  NOW(), NOW()
WHERE NOT EXISTS (...);
```

### 9. **Máquinas Nuevas**

**Ejemplo:** Una máquina llamada `CORTADORA_NUEVA`

**Comportamiento:**
- Se crea automáticamente en la tabla `maquinas`
- Se generan tiempos de ciclo para todas las telas existentes

### 10. **Valores Vacíos o NULL**

**Comportamiento:**
- Campos numéricos vacíos → `0`
- Campos de texto vacíos → `''` (cadena vacía)
- Foreign keys sin valor → `NULL`

## 📊 Ejemplos de Datos Reales

### Ejemplo 1: Tela Simple con Máquina
```
FECHA: 30/10/2025
ORDEN: 44971-44978
HORA: HORA 08
OPERARIO: JULIAN
MAQUINA: VERTICAL
TELA: DRILL
CANTIDAD: 29
```

**Resultado:**
- 1 registro en `registro_piso_corte`
- DRILL → ID de tela
- VERTICAL → ID de máquina
- HORA 08 → rango "03:00pm - 04:00pm"

### Ejemplo 2: Múltiples Telas
```
FECHA: 31/10/2025
ORDEN: 45034
HORA: HORA 07
OPERARIO: JULIAN
MAQUINA: VERTICAL
TELA: shambray-drill
CANTIDAD: 35
```

**Resultado:**
- 2 registros en `registro_piso_corte`:
  1. SHAMBRAIN + VERTICAL + 35 unidades
  2. DRILL + VERTICAL + 35 unidades

### Ejemplo 3: Sin Máquina (N.A)
```
FECHA: 31/10/2025
ORDEN: 44971
HORA: HORA 01
OPERARIO: JULIAN
MAQUINA: N.A
TELA: IGNIFUGO
ACTIVIDAD: EXTENDER/TRAZAR
```

**Resultado:**
- 1 registro con `maquina_id = NULL`
- Actividad de preparación (no corte)

### Ejemplo 4: Operario Nuevo
```
OPERARIO: CARLOS
```

**Resultado:**
```sql
-- Se crea automáticamente
INSERT INTO users (name, email, password, role_id)
VALUES ('CARLOS', 'carlos@mundoindustrial.com', '...', 3);
```

## 🔧 Personalización

### Agregar Variaciones de Telas

Edita la función `normalizarTela()`:

```javascript
const variaciones = {
  'SHAMBRAY': 'SHAMBRAIN',
  'SHAMBRE': 'SHAMBRAIN',
  'TU_VARIACION_1': 'NOMBRE_CORRECTO_1',
  'TU_VARIACION_2': 'NOMBRE_CORRECTO_2',
};
```

### Agregar Más Rangos de Horas

Edita la función `normalizarRangoHora()`:

```javascript
const mapeoRangos = {
  1: '08:00am - 09:00am',
  2: '09:00am - 10:00am',
  // ... existentes
  13: '08:00pm - 09:00pm',  // Nueva hora
  14: '09:00pm - 10:00pm',  // Nueva hora
};
```

### Modificar Tiempos de Ciclo por Defecto

Edita la función `obtenerTiempoCiclo()`:

```javascript
// Cambiar el valor por defecto de 97 a otro
return 97; // ← Cambiar aquí
```

## ⚠️ Validaciones Implementadas

1. **Fecha y Orden de Producción obligatorias**
   - Si faltan, se registra error y se omite la fila

2. **Prevención de duplicados**
   - `INSERT IGNORE` para evitar errores
   - `WHERE NOT EXISTS` para verificar antes de insertar

3. **Escape de caracteres especiales**
   - Comillas simples se escapan automáticamente
   - Previene errores de sintaxis SQL

4. **Validación de tipos de datos**
   - Números se convierten correctamente
   - Fechas se formatean a `YYYY-MM-DD`

## 📝 Notas Importantes

1. **Múltiples telas crean múltiples registros**
   - Si tienes `TELA1-TELA2-TELA3`, se crearán 3 registros
   - Todos con los mismos datos excepto la tela

2. **Separadores soportados**
   - Guión: `-`
   - Barra: `/`
   - Ejemplo: `TELA1-TELA2` o `TELA1/TELA2`

3. **Case-insensitive**
   - `julian`, `Julian`, `JULIAN` → todos se tratan igual
   - `drill`, `Drill`, `DRILL` → todos se normalizan a `DRILL`

4. **Idempotencia**
   - Puedes ejecutar el mismo SQL múltiples veces
   - No se crearán duplicados

## 🎉 Beneficios

✅ **Flexibilidad total** - Acepta cualquier formato de datos
✅ **Sin errores** - Maneja todos los casos especiales
✅ **Automático** - No requiere configuración manual
✅ **Escalable** - Fácil agregar nuevos casos
✅ **Robusto** - Previene duplicados y errores SQL
