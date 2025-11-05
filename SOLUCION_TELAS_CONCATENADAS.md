# ✅ Solución: Telas Concatenadas en un Solo Registro

## 🎯 Objetivo

Cuando el Excel tiene múltiples telas en una celda (ej: `NAFLIX-POLO` o `DRILL/OXFORD`), crear **UN SOLO registro** en la base de datos con el nombre completo de la tela, sin duplicar la cantidad.

---

## 📋 Comportamiento del Script Corregido

### Antes (Script Original - ❌ INCORRECTO)

**Excel:**
```
Tela: NAFLIX-POLO
Cantidad: 100
```

**Base de Datos:**
```
Registro 1: tela_id = NAFLIX, cantidad = 100
Registro 2: tela_id = POLO,   cantidad = 100
Total: 200 ❌ (duplicado)
```

### Después (Script Concatenado - ✅ CORRECTO)

**Excel:**
```
Tela: NAFLIX-POLO
Cantidad: 100
```

**Base de Datos:**
```
Registro 1: tela_id = NAFLIX-POLO, cantidad = 100
Total: 100 ✅ (correcto)
```

---

## 🔧 Cambios Implementados

### 1. Nueva Función: `normalizarTelaConcatenada()`

```javascript
function normalizarTelaConcatenada(nombre) {
  if (!nombre) return null;
  
  let nombreStr = nombre.toString().trim().toUpperCase();
  
  // Si no tiene separadores, devolver tal cual
  if (!nombreStr.includes('-') && !nombreStr.includes('/')) {
    return aplicarVariaciones(nombreStr);
  }
  
  // Separar por guiones o barras
  let telas = nombreStr.split(/[-\/]/).map(t => t.trim()).filter(t => t.length > 0);
  
  // Aplicar variaciones a cada tela
  telas = telas.map(tela => aplicarVariaciones(tela));
  
  // Concatenar con guión
  return telas.join('-');
}
```

### 2. Ejemplos de Normalización

| Excel Original | Normalizado | Descripción |
|---------------|-------------|-------------|
| `NAFLIX` | `NAFLIX` | Sin cambios |
| `NAFLIX-POLO` | `NAFLIX-POLO` | Mantiene el guión |
| `NAFLIX/POLO` | `NAFLIX-POLO` | Convierte `/` a `-` |
| `DRILL - OXFORD` | `DRILL-OXFORD` | Elimina espacios extras |
| `N-A` | `N-A` | Mantiene formato corto |

### 3. Creación de Telas en Base de Datos

El script crea automáticamente las telas concatenadas:

```sql
-- Si el Excel tiene "NAFLIX-POLO", se crea:
INSERT IGNORE INTO telas (nombre_tela, created_at, updated_at)
SELECT 'NAFLIX-POLO', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM telas WHERE nombre_tela = 'NAFLIX-POLO');
```

### 4. Tiempo de Ciclo

Para telas concatenadas, usa la **primera tela** para determinar el tiempo de ciclo:

```javascript
// "NAFLIX-POLO" → usa tiempo de ciclo de "NAFLIX"
const primeraTela = telaNombreConcatenado.split('-')[0];
const tiempoCiclo = obtenerTiempoCiclo(primeraTela, maquinaNombre);
```

---

## 📊 Impacto en los Datos

### Antes de Aplicar (Octubre 2025)

- **Total registros:** 676
- **Total cantidad:** 14,502
- **Registros duplicados:** 27
- **Cantidad duplicada:** 405

### Después de Aplicar

- **Total registros:** ~649 (676 - 27 duplicados)
- **Total cantidad:** ~14,097 (14,502 - 405)
- **Más cercano al Excel:** 14,201

---

## 🚀 Cómo Usar el Script Corregido

### Paso 1: Reemplazar el Script en Google Apps Script

1. Abre tu hoja de Google Sheets
2. Ve a **Extensiones → Apps Script**
3. Reemplaza el código actual con el contenido de:
   ```
   scripts/google-apps-script-corte-CONCATENADO.js
   ```
4. Guarda el script

### Paso 2: Ejecutar la Migración

1. Ejecuta la función `generarYGuardarSQLenDrive()`
2. El script generará un archivo SQL con:
   - Telas concatenadas (ej: `NAFLIX-POLO`)
   - Un solo registro por fila del Excel
   - Cantidades sin duplicar

### Paso 3: Importar el SQL

```bash
# En tu servidor MySQL
mysql -u usuario -p nombre_base_datos < archivo_generado.sql
```

---

## 🔍 Verificación

### Verificar que no hay duplicados:

```bash
php verificar_duplicados_telas.php
```

Debería mostrar:
```
✅ No se encontraron registros duplicados
```

### Verificar cantidades:

```bash
php verificar_cantidades_corte.php
```

Debería mostrar cantidades más cercanas al Excel.

---

## 📝 Ejemplos Reales

### Caso 1: Telas "N-A"

**Excel:**
```
Fecha: 2025-10-17
Operario: JULIAN
Tela: N-A
Cantidad: 67
```

**Base de Datos (ANTES - duplicado):**
```
ID 6962: tela = N, cantidad = 67
ID 6963: tela = A, cantidad = 67
Total: 134 ❌
```

**Base de Datos (DESPUÉS - correcto):**
```
ID 6962: tela = N-A, cantidad = 67
Total: 67 ✅
```

### Caso 2: Telas "CAMISA DRILL"

**Excel:**
```
Fecha: 2025-10-17
Operario: PAOLA
Tela: CAMISA DRILL
Cantidad: 19
```

**Base de Datos (DESPUÉS):**
```
ID 6949: tela = CAMISA DRILL, cantidad = 19
Total: 19 ✅ (un solo registro)
```

---

## ⚠️ Consideraciones

### 1. Telas Existentes

Si ya tienes telas individuales en la base de datos (ej: `NAFLIX`, `POLO`), el script creará nuevas telas concatenadas (ej: `NAFLIX-POLO`). Esto es correcto y no afecta los registros existentes.

### 2. Búsquedas

Para buscar registros con telas concatenadas:

```sql
-- Buscar registros con NAFLIX (individual o concatenado)
SELECT * FROM registro_piso_corte r
JOIN telas t ON r.tela_id = t.id
WHERE t.nombre_tela LIKE '%NAFLIX%';
```

### 3. Reportes

Los reportes mostrarán el nombre completo de la tela:
- `NAFLIX-POLO`
- `DRILL-OXFORD`
- `N-A`

---

## 🎯 Resultado Final

✅ **Un registro por fila del Excel**  
✅ **Cantidades sin duplicar**  
✅ **Telas concatenadas con guión**  
✅ **Más cercano a los datos originales del Excel**

---

## 📞 Soporte

Si encuentras algún problema:

1. Ejecuta `verificar_duplicados_telas.php` para identificar duplicados
2. Revisa el archivo `INFORME_DIFERENCIA_CANTIDADES.md`
3. Usa `limpiar_duplicados_corte.php` para eliminar duplicados existentes
