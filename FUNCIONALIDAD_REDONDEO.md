# Funcionalidad de Redondeo en Métricas Globales

## Fecha: 2025-11-04

## Descripción

Se agregó un botón toggle para activar/desactivar el **redondeo automático** de valores decimales en las métricas de producción.

## Ubicación

**Vista:** `resources/views/balanceo/partials/tabla-metricas-globales.blade.php`

## Características

### Botón de Redondeo

```
┌─────────────────────────┐
│ 🧮 Exacto / Redondeado  │
└─────────────────────────┘
```

**Estados:**
- **Exacto** (por defecto): Muestra valores con decimales precisos
- **Redondeado**: Redondea valores al entero más cercano

**Indicadores visuales:**
- Color naranja (#ff9d58) cuando está desactivado
- Color verde (#43e97b) cuando está activado
- Icono `calculate` de Material Symbols
- Tooltip descriptivo al pasar el mouse

### Valores Afectados

El redondeo se aplica **solo** a:

#### 1. **Meta Real (90%)**
- **Exacto:** `321.30` (2 decimales)
- **Redondeado:** `321` (entero)

**Ejemplo:**
```
Meta Teórica: 357
Meta Real (90%): 
  - Exacto: 321.30
  - Redondeado: 321
```

#### 2. **Meta Real (Cuello de Botella)**
En la vista de análisis de cuello de botella:
- **Exacto:** `45.67` (2 decimales)
- **Redondeado:** `46` (entero)

### Valores NO Afectados

Los siguientes valores **NO se redondean** (siempre se muestran exactos):

- Total de operarios
- Turnos de trabajo
- Horas/turno
- T. Disponible en Horas
- T. Disponible en Segundos
- SAM Total
- **Meta Teórica** (siempre entero)
- Operario cuello de botella
- Tiempo cuello de botella
- SAM Real
- **Meta Sugerida 85%** (siempre entero)

## Implementación Técnica

### Alpine.js Variable

```javascript
x-data="{ 
    mostrarCuelloBotella: false, 
    redondearValores: false  // Nueva variable
}"
```

### Lógica de Redondeo

```javascript
// Vista Simple - Meta Real (90%)
x-text="metricas.meta_real ? 
    (redondearValores ? 
        Math.round(parseFloat(metricas.meta_real)) :  // Redondeado
        parseFloat(metricas.meta_real).toFixed(2)      // Exacto
    ) : 'N/A'"
```

### Función de Redondeo

**JavaScript `Math.round()`:**
- `45.4` → `45`
- `45.5` → `46` ✅
- `45.6` → `46`
- `321.30` → `321`

## Interfaz de Usuario

### Layout de Botones

```
┌──────────────────────────────────────────────────────────┐
│  📊 Métricas Globales de Producción                      │
│                                    [🧮 Exacto] [📊 C.B.] │
└──────────────────────────────────────────────────────────┘
```

Dos botones en la esquina superior derecha:
1. **Botón Redondeo** (izquierda)
2. **Botón Cuello de Botella** (derecha)

### Indicador Visual

Cuando el redondeo está activo, aparece un indicador en la nota inferior:

```
Nota: Los campos editables actualizan automáticamente 
todas las métricas calculadas. • Valores redondeados activos
```

## Casos de Uso

### Caso 1: Presentación Ejecutiva
**Activar redondeo** para mostrar valores enteros más fáciles de comunicar:
- Meta Real: `321` en lugar de `321.30`

### Caso 2: Análisis Técnico
**Desactivar redondeo** para ver valores precisos:
- Meta Real: `321.30` (precisión de 2 decimales)

### Caso 3: Planificación de Producción
**Activar redondeo** para establecer metas realistas:
- Si Meta Real es `45.5`, redondea a `46` unidades

## Ejemplos Visuales

### Modo Exacto (Por Defecto)
```
Meta teórica:        357
Meta Real (90%):     321.30  ← 2 decimales
```

### Modo Redondeado
```
Meta teórica:        357
Meta Real (90%):     321     ← Entero
```

## Ventajas

1. ✅ **Flexibilidad:** El usuario decide qué formato ver
2. ✅ **Precisión:** Modo exacto para análisis detallado
3. ✅ **Simplicidad:** Modo redondeado para comunicación
4. ✅ **Visual:** Indicadores claros del estado activo
5. ✅ **Persistencia:** El estado se mantiene durante la sesión

## Notas Técnicas

- El redondeo es **solo visual** (frontend)
- Los valores en la base de datos permanecen sin cambios
- El cálculo siempre usa valores exactos
- El redondeo se aplica solo en la presentación final

## Compatibilidad

- ✅ Compatible con Alpine.js
- ✅ Compatible con todos los navegadores modernos
- ✅ No requiere cambios en el backend
- ✅ No afecta los cálculos existentes

## Futuras Mejoras

Posibles extensiones:
- [ ] Guardar preferencia de redondeo en localStorage
- [ ] Aplicar redondeo a más métricas (configurable)
- [ ] Exportar reportes con valores redondeados
- [ ] Configuración global de redondeo por usuario
