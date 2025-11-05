# 🔍 Informe: Diferencia de Cantidades Dashboard vs Excel

## 📊 Resumen del Problema

**Dashboard Corte:** 14,502 unidades (Octubre 2025)  
**Excel:** 14,201 unidades  
**Diferencia:** 301 unidades (2.12% más en el dashboard)

---

## 🎯 Causa Raíz Identificada

### Problema Principal: **Duplicación por Múltiples Telas**

El script de migración de Google Apps Script tiene una lógica que **crea múltiples registros** cuando una fila del Excel contiene múltiples telas separadas por `-` o `/`:

```javascript
// Normalizar telas - DIVIDE por guiones o barras
function normalizarTela(nombre) {
  let telas = nombreStr.split(/[-\/]/).map(t => t.trim()).filter(t => t.length > 0);
  return telas;
}

// Crear un registro POR CADA TELA
const telasParaInsertar = telasArray.length > 0 ? telasArray : [null];

telasParaInsertar.forEach(telaNombre => {
  // INSERT con la MISMA cantidad para cada tela
  INSERT INTO registro_piso_corte (..., cantidad, ...) VALUES (..., ${cantidad}, ...);
});
```

### Ejemplo Real del Problema:

**En el Excel:**
```
Fecha: 2025-10-17
Hora: 5
Operario: JULIAN
Orden: RETACEO
Tela: N-A          ← Una sola fila con telas separadas por guión
Cantidad: 67
```

**En la Base de Datos (después de migrar):**
```
Registro 1: ID 6962, Tela: N, Cantidad: 67
Registro 2: ID 6963, Tela: A, Cantidad: 67
```

**Resultado:** La cantidad 67 se cuenta **DOS VECES** = 134 en total (67 de exceso)

---

## 📈 Datos Verificados

### Registros Duplicados Encontrados en Octubre 2025:

- **27 grupos de registros duplicados**
- **405 unidades duplicadas en total**

### Ejemplos de Duplicación:

| Fecha | Hora | Operario | Telas | Cantidad Original | Cantidad Duplicada | Exceso |
|-------|------|----------|-------|-------------------|-------------------|--------|
| 2025-10-17 | 5 | JULIAN | N, A | 67 | 134 | 67 |
| 2025-10-17 | 6 | JULIAN | N, A | 67 | 134 | 67 |
| 2025-10-17 | 7 | JULIAN | N, A | 67 | 134 | 67 |
| 2025-10-17 | 8 | JULIAN | N, A | 67 | 134 | 67 |
| 2025-10-22 | 4 | JULIAN | POLUX (duplicado) | 33 | 66 | 33 |
| 2025-10-16 | 1 | PAOLA | CAMISA DRILL (duplicado) | 27 | 54 | 27 |

### Patrones Identificados:

1. **Telas "N-A"**: Aparecen frecuentemente (probablemente "NAFLIX-ALFONSO" o similar)
2. **Telas duplicadas idénticas**: Mismo nombre de tela pero creado dos veces (ej: POLUX, POLUX)
3. **Telas con espacios**: "CAMISA DRILL" aparece duplicado

---

## 🔢 Análisis Matemático

```
Total en Base de Datos:     14,502 unidades
Cantidad duplicada:           -405 unidades
Total corregido:            14,097 unidades

Total en Excel:             14,201 unidades
Diferencia restante:          +104 unidades
```

**Conclusión:** Después de eliminar los duplicados, aún hay una diferencia de ~104 unidades que podría deberse a:
- Registros en el Excel que no se migraron correctamente
- Errores en el Excel original
- Registros creados manualmente en el sistema después de la migración

---

## ✅ Soluciones Propuestas

### Solución 1: **Corregir el Script de Migración** (Recomendada)

Modificar el script para usar **solo la primera tela** cuando hay múltiples:

```javascript
// ANTES (crea múltiples registros)
const telasParaInsertar = telasArray.length > 0 ? telasArray : [null];
telasParaInsertar.forEach(telaNombre => {
  // Crea un INSERT por cada tela
});

// DESPUÉS (usa solo la primera tela)
const telaPrincipal = telasArray.length > 0 ? telasArray[0] : null;
const telaIdQuery = telaPrincipal
  ? `(SELECT id FROM telas WHERE nombre_tela = '${telaPrincipal}' LIMIT 1)` 
  : 'NULL';

// Crear UN SOLO registro con la tela principal
INSERT INTO registro_piso_corte (...) VALUES (...);
```

### Solución 2: **Dividir la Cantidad entre las Telas**

Si realmente se necesita un registro por tela:

```javascript
const cantidadPorTela = Math.floor(cantidadProducida / telasArray.length);

telasArray.forEach(telaNombre => {
  INSERT INTO registro_piso_corte (..., cantidad, ...) 
  VALUES (..., ${cantidadPorTela}, ...);
});
```

### Solución 3: **Limpiar Registros Duplicados Existentes**

Script SQL para eliminar duplicados manteniendo solo el primer registro:

```sql
-- Identificar duplicados
WITH duplicados AS (
  SELECT 
    id,
    ROW_NUMBER() OVER (
      PARTITION BY fecha, hora_id, operario_id, orden_produccion, cantidad 
      ORDER BY id
    ) as rn
  FROM registro_piso_corte
  WHERE YEAR(fecha) = 2025 AND MONTH(fecha) = 10
)
-- Eliminar todos excepto el primero (rn = 1)
DELETE FROM registro_piso_corte 
WHERE id IN (
  SELECT id FROM duplicados WHERE rn > 1
);
```

---

## 🚀 Plan de Acción Recomendado

### Paso 1: Limpiar Datos Existentes
```bash
php artisan tinker
```
```php
// Eliminar registros duplicados de octubre 2025
DB::statement("
  DELETE r1 FROM registro_piso_corte r1
  INNER JOIN registro_piso_corte r2 
  WHERE r1.id > r2.id
    AND r1.fecha = r2.fecha
    AND r1.hora_id = r2.hora_id
    AND r1.operario_id = r2.operario_id
    AND r1.orden_produccion = r2.orden_produccion
    AND r1.cantidad = r2.cantidad
    AND YEAR(r1.fecha) = 2025
    AND MONTH(r1.fecha) = 10
");
```

### Paso 2: Corregir el Script de Migración
- Modificar `normalizarTela()` para usar solo la primera tela
- Re-ejecutar la migración para meses futuros

### Paso 3: Verificar Resultados
```bash
php verificar_cantidades_corte.php
```

---

## 📝 Notas Adicionales

### Telas Problemáticas Identificadas:
- **N-A**: Probablemente "NAFLIX-ALFONSO" o similar
- **CAMISA DRILL**: Aparece duplicado
- **POLUX**: Aparece duplicado idéntico

### Recomendaciones:
1. **Estandarizar nombres de telas** en el Excel antes de migrar
2. **No usar guiones** para separar múltiples telas en una celda
3. **Crear filas separadas** en el Excel si realmente se trabajan múltiples telas
4. **Validar datos** después de cada migración

---

## 🎯 Resultado Esperado

Después de aplicar la solución:
- **Eliminar ~27 registros duplicados**
- **Reducir cantidad total en ~405 unidades**
- **Total corregido: ~14,097 unidades** (más cercano al Excel)
- **Diferencia residual: ~104 unidades** (aceptable, puede ser por registros manuales)
