# 📊 Análisis del Commit 71362c0 - El Problema de las Dos Lógicas

**Fecha:** 20 de Enero, 2026
**Commit:** `71362c0` - "ajustes para poder editar un pedido"
**Ruta Afectada:** `/asesores/pedidos-produccion/crear-nuevo`

---

## 🎯 El Problema Identificado

El commit que añadió funcionalidad de **editar pedidos** rompió la funcionalidad de **crear nuevos pedidos**, pero el error NO estaba en el código nuevo. Estaba en el **mismatch entre dos lógicas completamente diferentes**:

### **Dos Lógicas Diferentes**

#### **1️⃣ FRONTEND (JSON) - Crear Nuevo Pedido**
```javascript
// Lo que existe mientras se crea en el navegador
{
  cliente: "",
  forma_de_pago: "",
  prendas: [],  // ← Se agregan prendas UNA POR UNA
  epps: [],     // ← Se agregan EPPs UNA POR UNA
  // TODO está en MEMORIA/localStorage HASTA que se envía al servidor
}
```

**Características:**
- Datos construidos gradualmente en el formulario
- Imágenes temporales con `URL.createObjectURL()`
- Estructura flexible, controlada por JavaScript
- Se guarda en memoria hasta hacer POST/PUT

---

#### **2️⃣ BACKEND (Base de Datos) - Editar Pedido Existente**
```php
// Lo que viene de la BD
$pedido = PedidoProduccion {
  id: 123,
  numero_pedido: 45710,
  cliente: "Cliente X",
  prendas: Collection { // ← Relación Eloquent
    PrendaPedido { id: 1, nombre_prenda: "Polo", ... },
    PrendaPedido { id: 2, nombre_prenda: "Camiseta", ... }
  },
  epps: Collection { // ← Relación Eloquent
    PedidoEpp { id: 1, epp_id: 5, cantidad: 10, ... }
  }
}
```

**Características:**
- Estructura completamente diferente de Eloquent
- Relaciones normalizadas en BD
- IDs, timestamps, foreign keys
- Imágenes guardadas con rutas en servidor

---

## 🔄 El Puente: `ObtenerPedidoDetalleService`

El servicio `ObtenerPedidoDetalleService::obtenerParaEdicion()` es el **puente de conversión**:

```
BD (Eloquent) → obtenerParaEdicion() → Frontend (JSON)
```

**Conversion que hace:**
```php
// DE esto (BD):
$pedido->prendas[0] = PrendaPedido { id: 1, nombre_prenda: "Polo", ... }

// A esto (Frontend):
$prendas[0] = [
    'nombre_prenda' => 'Polo',
    'fotos' => [...],  // Convertidas a URLs
    'procesos' => [...],
    'variantes' => [...],
    // ... más campos que espera JavaScript
]
```

---

##  El Problema en el Commit

**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionViewController.php`

**Antes del commit (línea ~56):**
```php
$datos = [];  //  Array vacío, pero el JS lo inicializa

return view('asesores.pedidos.crear-pedido-nuevo', $datos);
```

**Después del commit (línea ~56):**
```php
$datos = [];  //  Array COMPLETAMENTE vacío

if ($editarId) {
    // Solo si hay $editarId se llena $datos con el servicio
    $datos = $service->obtenerParaEdicion($editarId);
}

// Si NO hay $editarId, $datos sigue vacío []
return view('asesores.pedidos.crear-pedido-nuevo', $datos);
```

**Impacto en la Vista:**
```blade
@if($modoEdicion ?? false)
    <!--  Esto carga SOLO si viene de $datos -->
@endif

<!--  PERO DESPUÉS, accede a variables que no existen si $datos vacío -->
<input value="{{ $pedido->cliente ?? '' }}">  <!-- $pedido undefined si $datos = [] -->
```

---

## 🔧 La Solución Implementada

Ahora el controlador pasa **AMBAS estructuras** correctamente:

```php
// CREAR NUEVO (estructura vacía, pero válida para JS)
$datos = [
    'modoEdicion' => false,
    'pedido' => (object)['cliente' => '', 'forma_de_pago' => '', ...],
    'prendas' => [],
    'epps' => [],
    'estados' => [...],  //  Estados disponibles
    'areas' => [...]     //  Áreas disponibles
];

// EDITAR (estructura convertida de BD por el servicio)
if ($editarId) {
    $datos = $service->obtenerParaEdicion($editarId);
    // El servicio YA incluye: prendas, epps, estados, areas
    $datos['modoEdicion'] = true;
}

return view('asesores.pedidos.crear-pedido-nuevo', $datos);
```

---

## 📊 Diagrama de Flujo

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  USUARIO ACCEDE: /asesores/pedidos-produccion/crear-nuevo  │
│                                                             │
└────────────────────┬────────────────────────────────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
        ▼                         ▼
    ¿Con ?editar=id?         NO - Crear Nuevo
        │
        │ SÍ
        ▼
   ┌─────────────────────────────────────┐
   │ ObtenerPedidoDetalleService         │
   │ obtenerParaEdicion()                │
   │                                     │
   │ Convierte:                          │
   │  BD (Eloquent) → Frontend (JSON)   │
   └─────────────────────────────────────┘
        │
        ▼
   ┌─────────────────────────────────────┐
   │ $datos con estructura BD convertida │
   │ $datos['modoEdicion'] = true        │
   │ $datos['prendas'] = [...] (del JS) │
   │ $datos['epps'] = [...]  (del JS)   │
   └─────────────────────────────────────┘
        │
        ▼
   ┌─────────────────────────────────────┐
   │ Vista Blade: crear-pedido-nuevo     │
   │ Carga script: cargar-datos-edicion  │
   │ (SOLO en modo edición)              │
   └─────────────────────────────────────┘

        │
        └──────────┬──────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
        ▼                     ▼
   CREAR NUEVO         EDITAR EXISTENTE
   (vacío)             (BD → Frontend)
        │                     │
        │ JS inicializa       │ JS carga datos
        │ formulario vacío    │ desde $datos
        │ Usuario comienza    │ Usuario edita
        │ a llenar...         │ lo existente...
```

---

## 🚀 Conclusión

**El problema NO era en el código nuevo**, sino en cómo el commit expuso el **mismatch entre dos flujos**:

1. **Frontend (JSON)**: Datos en construcción mientras se crea
2. **Backend (BD)**: Datos guardados con estructura Eloquent

La solución fue asegurar que **AMBOS flujos reciban la estructura correcta**:
- **Crear nuevo** → Estructura vacía pero válida
- **Editar** → Estructura convertida de BD a Frontend

 **Ya implementado** en `PedidosProduccionViewController::crearFormEditableNuevo()`
