# Análisis del Problema de Diferencia de Cantidades

## Problema Identificado

**Dashboard muestra:** 14,502 unidades (octubre 2025)
**Excel muestra:** 14,201 unidades
**Diferencia:** 301 unidades (2.12% más en el dashboard)

## Datos Verificados en Base de Datos

### Octubre 2025 (2025-10)
- Total registros: 676
- Cantidad total: 14,502
- Meta total: 11,207.25

### Detalle por Hora (Octubre 2025)
```
HORA 1: 1,518 unidades (Meta: 1,250.01)
HORA 2: 1,740 unidades (Meta: 1,318.90)
HORA 3: 2,206 unidades (Meta: 1,724.73)
HORA 4: 1,931 unidades (Meta: 1,571.60)
HORA 5: 1,894 unidades (Meta: 1,430.96)
HORA 6: 1,739 unidades (Meta: 1,239.78)
HORA 7: 1,730 unidades (Meta: 1,261.93)
HORA 8: 1,744 unidades (Meta: 1,409.34)
```

## Posibles Causas de la Diferencia

### 1. **Registros Duplicados por Múltiples Telas**

El script de migración tiene esta lógica:

```javascript
// Si hay múltiples telas, crear un registro por cada tela
const telasParaInsertar = telasArray.length > 0 ? telasArray : [null];

telasParaInsertar.forEach(telaNombre => {
  // Crear un INSERT por cada tela
});
```

**Problema:** Si en el Excel una fila tiene múltiples telas separadas por `-` o `/`, el script crea **múltiples registros** en la base de datos, uno por cada tela, pero con la **misma cantidad**.

**Ejemplo:**
- Excel: `NAFLIX-POLO` con cantidad 100
- Base de datos: 
  - Registro 1: NAFLIX, cantidad 100
  - Registro 2: POLO, cantidad 100
  - **Total en BD: 200** (duplicado)

### 2. **Normalización de Telas**

```javascript
function normalizarTela(nombre) {
  // Separar por guiones o barras si hay múltiples telas
  let telas = nombreStr.split(/[-\/]/).map(t => t.trim()).filter(t => t.length > 0);
  return telas;
}
```

Esto divide telas como:
- `NAFLIX-POLO` → `['NAFLIX', 'POLO']`
- `DRILL/OXFORD` → `['DRILL', 'OXFORD']`

### 3. **Máquinas NULL**

El script maneja máquinas como NULL:

```javascript
function normalizarMaquina(nombre) {
  if (['N.A', 'N/A', 'NA', 'N.A.', 'NINGUNA', 'NINGUNO'].includes(nombreUpper)) {
    return null;
  }
  return nombreUpper;
}
```

Pero luego crea registros con `maquina_id = NULL`, lo cual es correcto.

## Verificación Necesaria

Para confirmar la causa, necesitamos:

1. **Contar cuántos registros tienen múltiples telas en el Excel**
2. **Verificar si hay registros duplicados con diferentes `tela_id` pero misma fecha/hora/operario/cantidad**
3. **Sumar las cantidades de estos registros duplicados**

## Solución Propuesta

### Opción 1: Dividir la Cantidad entre las Telas

Si una fila tiene 2 telas, dividir la cantidad entre 2:

```javascript
const cantidadPorTela = Math.floor(cantidad / telasArray.length);
```

### Opción 2: Usar Solo la Primera Tela

Ignorar las telas adicionales y usar solo la primera:

```javascript
const telaPrincipal = telasArray[0];
// Crear solo un registro con la tela principal
```

### Opción 3: Crear un Campo de Telas Múltiples

Almacenar todas las telas en un campo JSON o concatenado:

```sql
telas_json = '["NAFLIX", "POLO"]'
```

## Recomendación

**Opción 2** es la más simple y evita duplicación de cantidades. Si el Excel tiene `NAFLIX-POLO`, usar solo `NAFLIX` y la cantidad completa.
