# Análisis: Problema de Tallas No Se Capturan

##  CAUSA IDENTIFICADA Y ARREGLADA

El usuario **NO está haciendo clic en los botones "DAMA" o "CABALLERO"** del modal de agregar prenda.

Cuando hace clic en uno de estos botones → se abre modal de selección de tallas → usuario selecciona tallas → se llena `window.tallasRelacionales`

**Si NO hace clic en los botones → `window.tallasRelacionales` queda vacío → Tallas no se capturan**

## 📊 Escenarios Identificados

### Escenario 1: Pedido NUEVO (sin guardar en BD)
```
Usuario crea prenda nueva
    ↓
Modal se abre → usuario completa:
  - Nombre 
  - Descripción 
  - Origen 
  - Imágenes 
  - Telas 
  - **TALLAS: DEBE HACER CLIC EN "DAMA" o "CABALLERO"** ← USUARIO OLVIDÓ ESTO
    ↓ Hace clic
  - Se abre modal de selección de tallas
  - Usuario selecciona tallas (S: 10, M: 15)
  - window.tallasRelacionales se llena: { DAMA: { S: 10, M: 15 } }
    ↓
Collector: construirPrendaDesdeFormulario()
    ↓
prendaData = {
  nombre_prenda: "POLO",
  cantidad_talla: window.tallasRelacionales ← { DAMA: { S: 10, M: 15 } } 
  variantes: {...}
}
    ↓
VALIDACIÓN: Si cantidad_talla vacío → Mostrar error 
    ↓
agregarPrendaAlOrden(prendaData)
    ↓
this.prendas.push(prenda)
    ↓
renderer.actualizar(items)
    ↓
PrendaCardService.generar(prenda) → PrendaDataTransformer.transformar()
    ↓
cantidad_talla: { DAMA: { S: 10, M: 15 } } → generosConTallas 
    ↓
Tarjeta muestra SECCIÓN DE TALLAS 
```

### Escenario 2: Pedido GUARDADO EN BD
```
Backend devuelve prenda con:
  tallas: [
    { genero: "DAMA", talla: "S", cantidad: 10 },
    { genero: "DAMA", talla: "M", cantidad: 15 }
  ]
    ↓
PrendaDataTransformer.transformar() 
    ↓
Convierte tallas[] a generosConTallas + cantidadesPorTalla
    ↓
MUESTRA SECCIÓN DE TALLAS 
```

## 🔧 SOLUCIONES IMPLEMENTADAS

### 1.  Agregar validación en `agregarPrendaNueva()`
Archivo: [gestion-items-pedido.js](public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js#L243)

```javascript
// Validar que al menos haya seleccionado tallas
const tieneTallas = prendaData.cantidad_talla && 
    Object.values(prendaData.cantidad_talla).some(genero => 
        Object.keys(genero).length > 0
    );

if (!tieneTallas) {
    this.notificationService?.advertencia('⚠️ Por favor selecciona al menos una talla para la prenda');
    return;
}
```

**Resultado**: Si el usuario olvida seleccionar tallas, verá un mensaje claro.

### 2.  Arreglar fallback de nombre en transformer
Archivo: [prenda-data-transformer.js](public/js/prendas/utils/prenda-data-transformer.js#L45)

```javascript
nombre_producto: prendaRaw.nombre_producto || prendaRaw.nombre_prenda || prendaRaw.nombre || '',
```

**Resultado**: El nombre se muestra correctamente aunque se guarde como `nombre_prenda`.

##  Flujo correcto para el usuario

1. Abre modal "Agregar Prenda Nueva"
2. Completa datos básicos (nombre, origen, etc.)
3. **Hace clic en "DAMA" o "CABALLERO"** → Se abre modal de tallas
4. Selecciona tallas y cantidades
5. Confirma modal de tallas
6. Agrega telas si lo desea
7. Hace clic en "Guardar Prenda"
   - Si olvidó seleccionar tallas → Error: "Por favor selecciona al menos una talla"
   - Si tiene tallas → Se guarda y aparece tarjeta con tallas visibles 

## Recomendaciones para mejor UX

Podrías considerar:
1. Hacer los botones de género más destacados (ej: rojo si no se seleccionó)
2. Cambiar color del botón cuando se selecciona talla
3. Mostrar contador de tallas seleccionadas
4. Hacer las tallas OPCIONALES en vez de obligatorias (solo mostrar la sección si hay tallas)

