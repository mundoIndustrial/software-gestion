# ARQUITECTURA DEL SISTEMA DE PEDIDOS DE PRODUCCIÓN

##  ARQUITECTURA ACTUAL - SISTEMA DE ÍTEMS CON SPLIT DE PROCESOS

**Última actualización:** Enero 2026

---

##  ÍNDICE
1. [Concepto Principal](#concepto-principal)
2. [Regla de Split de Ítems](#regla-de-split-de-ítems)
3. [Estructura de Datos](#estructura-de-datos)
4. [Flujo de Trabajo](#flujo-de-trabajo)
5. [Categorías de Ítems](#categorías-de-ítems)
6. [Módulos JavaScript](#módulos-javascript)
7. [Backend y Base de Datos](#backend-y-base-de-datos)
8. [Ejemplos Prácticos](#ejemplos-prácticos)

---

##  CONCEPTO PRINCIPAL

### **1 Prenda con Proceso = 2 Ítems Separados**

Esta es la regla fundamental del sistema:

```
PRENDA: Polo con Bordado
         ↓
    SE DIVIDE EN:
         ↓
┌────────────────────────────┐
│ ÍTEM 1: Polo BASE          │
│ - Origen: Bodega/Confección│
│ - Procesos: []             │
│ - Categoría: COSTURA       │
└────────────────────────────┘
         +
┌────────────────────────────┐
│ ÍTEM 2: Polo PROCESO       │
│ - Origen: Bodega/Confección│
│ - Procesos: [Bordado]      │
│ - Categoría: BORDADO       │
│ - es_proceso: true         │
└────────────────────────────┘
```

### **¿Por qué 2 ítems?**

1. **Separación de responsabilidades**: Costura y procesos son áreas diferentes
2. **Trazabilidad**: Cada área puede gestionar su parte independientemente
3. **Flexibilidad**: Permite que bodega entregue la prenda base mientras procesos trabaja en paralelo

---

## 🔄 REGLA DE SPLIT DE ÍTEMS

### Caso 1: Prenda SIN Procesos
```javascript
// INPUT: Polo sin procesos
{
  prenda: { nombre: 'Polo', cantidad: 50 },
  origen: 'bodega',
  procesos: []
}

// OUTPUT: 1 ítem
[
  {
    prenda: { nombre: 'Polo', cantidad: 50 },
    origen: 'bodega',
    procesos: [],
    es_proceso: false
  }
]
```

### Caso 2: Prenda CON Procesos
```javascript
// INPUT: Polo con Bordado
{
  prenda: { nombre: 'Polo', cantidad: 50 },
  origen: 'bodega',
  procesos: ['Bordado']
}

// OUTPUT: 2 ítems
[
  // ÍTEM 1: BASE
  {
    prenda: { nombre: 'Polo', cantidad: 50 },
    origen: 'bodega',
    procesos: [],
    es_proceso: false
  },
  // ÍTEM 2: PROCESO
  {
    prenda: { nombre: 'Polo', cantidad: 50 },
    origen: 'bodega',
    procesos: ['Bordado'],
    es_proceso: true
  }
]
```

### Caso 3: Prenda CON Múltiples Procesos
```javascript
// INPUT: Polo con Bordado + Estampado
{
  prenda: { nombre: 'Polo', cantidad: 50 },
  origen: 'confeccion',
  procesos: ['Bordado', 'Estampado']
}

// OUTPUT: 2 ítems
[
  // ÍTEM 1: BASE
  {
    prenda: { nombre: 'Polo', cantidad: 50 },
    origen: 'confeccion',
    procesos: [],
    es_proceso: false
  },
  // ÍTEM 2: PROCESOS COMBINADOS
  {
    prenda: { nombre: 'Polo', cantidad: 50 },
    origen: 'confeccion',
    procesos: ['Bordado', 'Estampado'],
    es_proceso: true
  }
]
```

---

##  ESTRUCTURA DE DATOS

### Estructura de Ítem Completa

```javascript
{
  // Identificación
  tipo: 'cotizacion',        // 'cotizacion' o 'nuevo'
  id: 100,                   // ID de la cotización (si aplica)
  numero: 'COT-2024-001',    // Número de cotización
  cliente: 'Empresa XYZ',    // Nombre del cliente
  
  // Prenda
  prenda: {
    nombre: 'Polo',          // Nombre de la prenda
    tipo: 'PRENDA',          // Tipo: PRENDA, REFLECTIVO, etc.
    tallas: [                // Array de tallas con cantidades
      { talla: 'S', cantidad: 10 },
      { talla: 'M', cantidad: 20 },
      { talla: 'L', cantidad: 15 }
    ],
    cantidad: 45,            // Total calculado
    data: {...}              // Datos completos de la prenda
  },
  
  // Origen y Procesos
  origen: 'bodega',          // 'bodega' o 'confeccion'
  procesos: ['Bordado'],     // Array de procesos aplicados
  es_proceso: false,         // true solo para ítems de proceso
  
  // Metadata
  data: {...}                // Datos completos de la cotización
}
```

### Prendas Técnicas de Logo (Cotizaciones LOGO)

```javascript
{
  id: 1,
  nombre_prenda: 'CAMISA DRILL',
  tipo_logo_nombre: 'Bordado',  // Nombre del proceso
  talla_cantidad: [             // Array de objetos talla-cantidad
    { talla: 'S', cantidad: 10 },
    { talla: 'M', cantidad: 20 }
  ],
  cantidad_total: 30,
  fotos: [...],
  ubicaciones: [...],
  observaciones: '...'
}
```

---

## 🔄 FLUJO DE TRABAJO

### Flujo Desde Cotización

```
1. Usuario selecciona cotización
         ↓
2. Sistema carga datos de cotización
   - Prendas normales (prendas)
   - Prendas técnicas de logo (prendas_tecnicas)
         ↓
3. Modal muestra todas las prendas
   ┌─────────────────────────────────┐
   │ Prendas de COT-2024-001         │
   ├─────────────────────────────────┤
   │ ☑ Polo                          │
   │    50 unidades                │
   │    Procesos: Bordado          │
   │    Origen: ○ Bodega ○ Confec. │
   │                                 │
   │ ☑ Camisa Drill                  │
   │    30 unidades                │
   │    Procesos: Estampado        │
   │    Origen: ○ Bodega ○ Confec. │
   │                                 │
   │ [Agregar Prendas Seleccionadas] │
   └─────────────────────────────────┘
         ↓
4. Usuario selecciona:
   - Qué prendas agregar (checkbox)
   - Origen para cada prenda (radio)
         ↓
5. Sistema aplica regla de split:
   - Si tiene procesos → 2 ítems
   - Si no tiene procesos → 1 ítem
         ↓
6. Ítems se agregan a window.itemsPedido
         ↓
7. Lista de ítems se actualiza visualmente
```

### Detección de Procesos

El sistema detecta procesos automáticamente desde:

#### Para Prendas Normales:
```javascript
// Desde variantes de la prenda
if (variante.aplica_bordado) procesos.push('Bordado');
if (variante.aplica_estampado) procesos.push('Estampado');
if (variante.tiene_reflectivo) procesos.push('Reflectivo');
```

#### Para Prendas Técnicas de Logo:
```javascript
// Desde tipo_logo_nombre
const tipoLogo = prenda.tipo_logo_nombre.toLowerCase();
if (tipoLogo.includes('bordado')) procesos.push('Bordado');
if (tipoLogo.includes('estampado')) procesos.push('Estampado');
if (tipoLogo.includes('dtf')) procesos.push('DTF');
if (tipoLogo.includes('sublimado')) procesos.push('Sublimado');
if (tipoLogo.includes('reflectivo')) procesos.push('Reflectivo');
```

---

## 🏷️ CATEGORÍAS DE ÍTEMS

El sistema categoriza automáticamente cada ítem:

| Categoría | Descripción | Color |
|-----------|-------------|-------|
| **COSTURA-BODEGA** | Prenda de bodega sin procesos | Amarillo |
| **COSTURA-CONFECCIÓN** | Prenda confeccionada sin procesos | Verde |
| **BORDADO** | Con proceso de bordado | Azul |
| **ESTAMPADO** | Con estampado/DTF/sublimado | Rosa |
| **REFLECTIVO** | Con proceso reflectivo | Amarillo oscuro |
| **COMBINADO** | Múltiples procesos | Morado |
| **OTRO** | Otros casos | Gris |

### Lógica de Categorización

```javascript
function determinarCategoria(item) {
    // Sin procesos
    if (!item.procesos || item.procesos.length === 0) {
        return item.origen === 'bodega' 
            ? 'COSTURA-BODEGA' 
            : 'COSTURA-CONFECCIÓN';
    }
    
    // Múltiples procesos
    if (item.procesos.length > 1) {
        return 'COMBINADO';
    }
    
    // Un solo proceso
    const proceso = item.procesos[0].toLowerCase();
    if (proceso.includes('bordado')) return 'BORDADO';
    if (proceso.includes('estampado') || proceso.includes('dtf') || proceso.includes('sublimado')) 
        return 'ESTAMPADO';
    if (proceso.includes('reflectivo')) return 'REFLECTIVO';
    
    return 'OTRO';
}
```

---

## 💻 MÓDULOS JAVASCRIPT

### Arquitectura Modular

El sistema está dividido en módulos JavaScript independientes:

```
public/js/modulos/crear-pedido/
├── gestion-items-pedido.js       # Gestión de ítems
└── modal-seleccion-prendas.js    # Modal de selección
```

### 1. gestion-items-pedido.js

**Responsabilidad:** Gestionar el array de ítems y su renderizado

**Funciones principales:**
```javascript
// Array global de ítems
window.itemsPedido = [];

// Actualizar vista de ítems
window.actualizarVistaItems()

// Renderizar lista de ítems
renderizarItems()

// Determinar categoría del ítem
determinarCategoria(item)

// Obtener colores según categoría
obtenerColorCategoria(categoria)

// Eliminar ítem
window.eliminarItem(index)

// Obtener ítems del pedido
window.obtenerItemsPedido()

// Verificar si hay ítems
window.tieneItems()
```

### 2. modal-seleccion-prendas.js

**Responsabilidad:** Manejar el modal de selección de prendas

**Funciones principales:**
```javascript
// Abrir modal con prendas de cotización
window.abrirModalSeleccionPrendas(cotizacion)

// Renderizar prendas en el modal
renderizarPrendasModal()

// Detectar procesos de una prenda
detectarProcesos(prenda)

// Toggle selección de prenda
window.togglePrendaSeleccion(index)

// Actualizar origen de prenda
window.actualizarOrigenPrenda(index, origen)

// Cerrar modal
window.cerrarModalPrendas()

// Agregar prendas seleccionadas (con split de procesos)
window.agregarPrendasSeleccionadas()

// Calcular cantidad total de una prenda
calcularCantidadTotal(prenda)
```

### Carga de Módulos

Los módulos se cargan en el Blade antes del script principal:

```blade
<!-- Módulos refactorizados -->
<script src="{{ asset('js/modulos/crear-pedido/gestion-items-pedido.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/modal-seleccion-prendas.js') }}"></script>

<!-- Script principal -->
<script src="{{ asset('js/crear-pedido-editable.js') }}"></script>
```

---

## 🗄️ BACKEND Y BASE DE DATOS

### Endpoint Principal

```php
// PedidosProduccionController.php

/**
 * Obtener datos completos de una cotización
 * Retorna prendas normales y prendas técnicas de logo
 */
public function obtenerDatosCotizacion(int $cotizacionId): JsonResponse
{
    $cotizacion = Cotizacion::with([
        'prendas.variantes',
        'prendas.tallas',
        'prendas.fotos',
        'logoCotizacion.prendas.tipoLogo',
        'logoCotizacion.prendas.fotos',
    ])->findOrFail($cotizacionId);
    
    return response()->json([
        'prendas' => $cotizacion->prendas,           // Prendas normales
        'prendas_tecnicas' => $cotizacion->logoCotizacion 
            ? $cotizacion->logoCotizacion->prendas 
            : [],                                     // Prendas técnicas de logo
    ]);
}
```

### Estructura de Respuesta

```json
{
  "id": 100,
  "numero": "COT-2024-001",
  "cliente": "Empresa XYZ",
  "prendas": [
    {
      "id": 1,
      "nombre_producto": "Polo",
      "cantidad": 50,
      "tallas": ["S", "M", "L"],
      "variantes": {
        "aplica_bordado": true,
        "aplica_estampado": false
      }
    }
  ],
  "prendas_tecnicas": [
    {
      "id": 1,
      "nombre_prenda": "CAMISA DRILL",
      "tipo_logo_nombre": "Bordado",
      "talla_cantidad": [
        {"talla": "S", "cantidad": 10},
        {"talla": "M", "cantidad": 20}
      ]
    }
  ]
}
```

---

## 📚 EJEMPLOS PRÁCTICOS

### Ejemplo 1: Pedido Simple (Sin Procesos)

**Entrada:**
- 1 Polo de bodega
- Sin procesos

**Resultado:**
```javascript
itemsPedido = [
  {
    tipo: 'cotizacion',
    prenda: { nombre: 'Polo', cantidad: 50 },
    origen: 'bodega',
    procesos: [],
    es_proceso: false
  }
]
```

**Visualización:**
```
1. Polo
   🏪 BASE (Bodega)
    50 unidades
   🏷️ COSTURA-BODEGA
    Sin procesos
```

### Ejemplo 2: Pedido con Proceso (Split)

**Entrada:**
- 1 Camisa con Bordado
- Origen: Confección

**Resultado:**
```javascript
itemsPedido = [
  // ÍTEM 1: BASE
  {
    tipo: 'cotizacion',
    prenda: { nombre: 'Camisa', cantidad: 30 },
    origen: 'confeccion',
    procesos: [],
    es_proceso: false
  },
  // ÍTEM 2: PROCESO
  {
    tipo: 'cotizacion',
    prenda: { nombre: 'Camisa', cantidad: 30 },
    origen: 'confeccion',
    procesos: ['Bordado'],
    es_proceso: true
  }
]
```

**Visualización:**
```
1. Camisa
   ✂️ BASE (Confección)
    30 unidades
   🏷️ COSTURA-CONFECCIÓN
    Sin procesos

2. Camisa (PROCESO)
   ✂️ PROCESO (Confección)
    30 unidades
   🏷️ BORDADO
    Bordado
```

### Ejemplo 3: Pedido Combinado (Múltiples Prendas)

**Entrada:**
- 1 Polo de bodega con Bordado
- 1 Camisa confeccionada con Estampado + Reflectivo
- 1 Pantalón de bodega sin procesos

**Resultado:**
```javascript
itemsPedido = [
  // Polo BASE
  { prenda: 'Polo', origen: 'bodega', procesos: [], es_proceso: false },
  // Polo PROCESO
  { prenda: 'Polo', origen: 'bodega', procesos: ['Bordado'], es_proceso: true },
  
  // Camisa BASE
  { prenda: 'Camisa', origen: 'confeccion', procesos: [], es_proceso: false },
  // Camisa PROCESOS
  { prenda: 'Camisa', origen: 'confeccion', procesos: ['Estampado', 'Reflectivo'], es_proceso: true },
  
  // Pantalón (sin split)
  { prenda: 'Pantalón', origen: 'bodega', procesos: [], es_proceso: false }
]
```

**Total:** 5 ítems (2 + 2 + 1)

---

##  MANTENIMIENTO Y DEBUGGING

### Logs Importantes

El sistema genera logs detallados en cada paso:

```javascript
// Al cargar cotización
console.log(' Datos recibidos del backend:', data);
console.log(' Tiene prendas normales:', data.prendas?.length);
console.log(' Tiene prendas técnicas (logo):', data.prendas_tecnicas?.length);

// Al calcular cantidades
console.log('🔢 Calculando cantidad total para:', nombrePrenda);
console.log('   Usando cantidad directa:', prenda.cantidad);

// Al agregar ítems
console.log('➕ Agregando prendas seleccionadas. Total checkboxes:', checkboxes.length);
console.log(' itemsPedido antes de agregar:', itemsPedido.length);
console.log(' itemsPedido después de agregar:', itemsPedido.length);

// Al renderizar
console.log(' Renderizando ítems. Total:', itemsPedido.length);
console.log('  🔸 Renderizando ítem 1:', item.prenda?.nombre);
```

### Verificación de Funcionamiento

Para verificar que el sistema funciona correctamente:

1. **Abrir consola del navegador** (F12)
2. **Seleccionar una cotización**
3. **Verificar logs:**
   -  Datos cargados correctamente
   -  Prendas renderizadas en modal
   -  Cantidades calculadas correctamente
   -  Ítems agregados al array
   -  Lista renderizada en el DOM

---

##  NOTAS FINALES

### Ventajas del Sistema Actual

1. **Separación clara** entre prenda base y procesos
2. **Trazabilidad** independiente por área
3. **Flexibilidad** para gestionar cada parte por separado
4. **Escalabilidad** fácil agregar nuevos procesos
5. **Modularidad** código JavaScript organizado en módulos

### Limitaciones Conocidas

1. El código duplicado en el Blade aún no se ha eliminado completamente
2. La detección de procesos depende de la estructura de datos de cotizaciones
3. No hay validación de conflictos entre procesos

### Próximas Mejoras

1. Eliminar código duplicado del Blade
2. Agregar validación de procesos compatibles
3. Implementar edición de ítems después de agregarlos
4. Agregar drag & drop para reordenar ítems
5. Implementar guardado automático de borradores

---

**Documento generado:** Enero 2026  
**Versión:** 2.0  
**Estado:** Implementado y en producción
