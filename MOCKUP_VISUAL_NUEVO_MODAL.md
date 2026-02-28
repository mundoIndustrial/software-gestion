# 📊 Mockup Visual - Nuevo Modal EPP con Selección Múltiple

## Vista General del Modal

```
╔═════════════════════════════════════════════════════════════════════════════╗
║  Agregar EPP al Pedido - Selección Múltiple                          [✕]   ║
╠═════════════════════════════════════════════════════════════════════════════╣
║                                                                             ║
║  ┌─────────────────────────────────────────┬──────────────────────────┐    ║
║  │  🔍 Búscar EPP                          │  [+ Nuevo EPP]           │    ║
║  │  ├─ Nombre o referencia...              └──────────────────────────┘    ║
║  └─────────────────────────────────────────┘                               ║
║                                                                             ║
║  ┌─────────────────────────────────────────────────────────────────────┐   ║
║  │ 🛒 EPP Disponibles - Selecciona e ingresa cantidad                 │   ║
║  ├──────┬──────────────────────────────────────┬───────────┬──────────┤   ║
║  │ ☐☑️  │ EPP                                 │ Cantidad  │ Acción   │   ║
║  ├──────┼──────────────────────────────────────┼───────────┼──────────┤   ║
║  │ ☑️   │ [🛡️] CASCO DE SEGURIDAD ROJO       │    1  ┃  │ [Agregar]│   ║
║  │ __   │      Referencia: CSC-001            │ [↕]  ┃  │ ────────│   ║
║  │ ☐    │                                    │           │          │   ║
║  ├──────┼──────────────────────────────────────┼───────────┼──────────┤   ║
║  │ ☐    │ [👕] GUANTES DE NITRILO             │    1  ┃  │ [Agregar]│   ║
║  │      │      Referencia: GNT-005            │ [↕]  ┃  │ ────────│   ║
║  │      │                                    │           │          │   ║
║  ├──────┼──────────────────────────────────────┼───────────┼──────────┤   ║
║  │ ☐    │ [👢] BOTAS DE SEGURIDAD             │    2  ┃  │ [Agregar]│   ║
║  │      │      Referencia: BTS-012            │ [↕]  ┃  │ ────────│   ║
║  │      │                                    │           │          │   ║
║  └──────┴──────────────────────────────────────┴───────────┴──────────┘   ║
║                                                                             ║
║  ┌─────────────────────────────────────────────────────────────────────┐   ║
║  │ 📋 EPP Seleccionados (3)                                            │   ║
║  ├──────────────────────────────────────┬──────┬────────────┬───┬──────┤   ║
║  │ EPP                                  │ Cant.│Observaciones│Fotos│Acción│ ║
║  ├──────────────────────────────────────┼──────┼────────────┼───┼──────┤   ║
║  │ CASCO DE SEGURIDAD ROJO              │ [2]  │ [Notas...] │[🖼]│ [🗑] │   ║
║  ├──────────────────────────────────────┼──────┼────────────┼───┼──────┤   ║
║  │ GUANTES DE NITRILO                   │ [5]  │ [Notas...] │    │ [🗑] │   ║
║  ├──────────────────────────────────────┼──────┼────────────┼───┼──────┤   ║
║  │ BOTAS DE SEGURIDAD                   │ [1]  │ [Notas...] │[🖼]│ [🗑] │   ║
║  └──────────────────────────────────────┴──────┴────────────┴───┴──────┘   ║
║                                                                             ║
╠═════════════════════════════════════════════════════════════════════════════╣
║                                      3 EPP seleccionados                    ║
║  [Cancelar]                                                    [Finalizar] ║
╚═════════════════════════════════════════════════════════════════════════════╝
```

---

## 🔄 Flujo de Interacción

### 1️⃣ Usuario Abre Modal
```
┌─────────────────────────────┐
│  abrirModalAgregarEPP()     │
│  ↓                          │
│  - Limpiar datos previos    │
│  - cargarEPPDisponibles()   │
│  - Llenar tabla de arriba   │
│  - Renderizar tablas        │
└─────────────────────────────┘
```

### 2️⃣ Usuario Busca (Opcional)
```
┌──────────────────────────────┐
│  Escribe en buscador         │
│  "Casco"                     │
│           ↓                  │
│  filtrarTablaEPP()           │
│           ↓                  │
│  Tabla filtra en tiempo real │
│  Muestra solo: CASCO...      │
└──────────────────────────────┘
```

### 3️⃣ Usuario Selecciona EPP
```
┌────────────────────────────────────┐
│  Opción A: Marcar checkbox         │
│  ☑️ CASCO DE SEGURIDAD             │
│           ↓ onchange               │
│  actualizarSeleccionEPP()          │
│           ↓                        │
│  Agrega a eppAgregadosList         │
│  Renderiza tabla inferior          │
└────────────────────────────────────┘

┌────────────────────────────────────┐
│  Opción B: Ingresar cantidad       │
│  Cantidad: [5]                     │
│  Botón: [Agregar]                  │
│           ↓ onclick                │
│  agregarEPPDesdeTabla()            │
│           ↓                        │
│  Agrega a eppAgregadosList         │
│  Renderiza tabla inferior          │
└────────────────────────────────────┘
```

### 4️⃣ Usuario Edita (Opcional)
```
┌────────────────────────────────────┐
│  Tabla de seleccionados             │
│                                     │
│  Cantidad editable:                 │
│  [5] → [10]  onchange →             │
│  actualizarCantidadEPP()            │
│           ↓                         │
│  Actualiza eppAgregadosList         │
│                                     │
│  Observaciones editable:            │
│  [Campo]  onchange →                │
│  actualizarObservacionesEPP()       │
│           ↓                         │
│  Actualiza eppAgregadosList         │
│                                     │
│  Eliminar: [🗑]  onclick →          │
│  eliminarEPPDeLista()               │
│           ↓                         │
│  Remueve de eppAgregadosList        │
│  Actualiza tabla                    │
└────────────────────────────────────┘
```

### 5️⃣ Usuario Finaliza
```
┌──────────────────────────────────┐
│  Botón: [Finalizar]              │
│           ↓ onclick               │
│  finalizarAgregarEPP()           │
│           ↓                      │
│  - Valida eppAgregadosList       │
│  - Convierte imágenes            │
│  - Agrega a window.itemsPedido   │
│  - Actualiza UI externa          │
│  - Cierra modal                  │
└──────────────────────────────────┘
```

---

## 📊 Estructura de Datos

### eppAgregadosList (Array de Objetos)
```javascript
eppAgregadosList = [
  {
    id: 1,
    nombre_completo: "CASCO DE SEGURIDAD ROJO",
    cantidad: 2,
    observaciones: "Aplicar logo empresa",
    imagenes: [],           // Array de imágenes
    imagen: "/storage/..."  // Imagen del EPP
  },
  {
    id: 2,
    nombre_completo: "GUANTES DE NITRILO",
    cantidad: 5,
    observaciones: "Talla grande",
    imagenes: [],
    imagen: ""
  }
  // ... más EPPs
]
```

### eppDisponiblesList (Array de Objetos)
```javascript
eppDisponiblesList = [
  {
    id: 1,
    nombre_completo: "CASCO DE SEGURIDAD ROJO",
    nombre: "Casco",
    referencia: "CSC-001",
    imagen: "/storage/epp/casco.jpg"
  },
  {
    id: 2,
    nombre_completo: "GUANTES DE NITRILO",
    nombre: "Guantes",
    referencia: "GNT-005",
    imagen: "/storage/epp/guantes.jpg"
  }
  // ... más EPPs
]
```

---

## 🎨 Componentes Principales

### Tabla Superior (EPP Disponibles)
```html
<table id="tablaEPPDisponibles">
  <!-- Estructura -->
  <tr id="fila-epp-{id}">
    <td>
      <input type="checkbox" class="checkbox-epp" data-epp-id="{id}">
    </td>
    <td>
      <img src="{imagen}">
      <p>{nombre_completo}</p>
    </td>
    <td>
      <input type="number" class="cantidad-epp" data-epp-id="{id}">
    </td>
    <td>
      <button onclick="agregarEPPDesdeTabla({id})">Agregar</button>
    </td>
  </tr>
</table>
```

### Tabla Inferior (EPP Seleccionados)
```html
<table id="tablaEPPAgregados">
  <!-- Estructura -->
  <tr>
    <td>{nombre_completo}</td>
    <td>
      <input type="number" onchange="actualizarCantidadEPP({id})">
    </td>
    <td>
      <input type="text" onchange="actualizarObservacionesEPP({id})">
    </td>
    <td>
      <!-- Miniaturas de fotos -->
      <img src="{foto1}">
      <img src="{foto2}">
    </td>
    <td>
      <button onclick="eliminarEPPDeLista({id})">🗑</button>
    </td>
  </tr>
</table>
```

---

## 🔑 Métodos de Interacción

| Método | Entrada | Salida | Efecto |
|--------|---------|--------|--------|
| `cargarEPPDisponibles()` | - | Promise | Carga EPP en tabla superior |
| `filtrarTablaEPP(valor)` | string | - | Filtra tabla en tiempo real |
| `agregarEPPDesdeTabla(id)` | number | - | Agrega EPP a seleccionados |
| `seleccionarTodosEPP(checkbox)` | HTMLElement | - | Selecciona como grupo |
| `renderizarTablaEPPAgregados()` | - | - | Re-renderiza tabla inferior |
| `actualizarCantidadEPP(id, qty)` | number, number | - | Actualiza cantidad |
| `actualizarObservacionesEPP(id, text)` | number, string | - | Actualiza notas |
| `eliminarEPPDeLista(id)` | number | - | Elimina EPP |
| `actualizarSeleccionEPP()` | - | - | Actualiza UI (contadores) |
| `finalizarAgregarEPP()` | - | Promise | Guarda y cierra |

---

## ✨ Ventajas del Nuevo Diseño

✅ **Interfaz más intuitiva**: Tabla de doble nivel (disponibles vs. seleccionados)

✅ **Selección múltiple eficiente**: Checkbox + cantidad editable

✅ **Feedback visual inmediato**: Contadores y tabla se actualizan en tiempo real

✅ **Búsqueda integrada**: Filtra mientras escribes

✅ **Edición en la tabla**: No necesitas abrir formularios separados

✅ **Interfaz responsive**: Scroll en tablas largas

✅ **Compatible con modo edición**: El formulario antiguo aún funciona

✅ **Código más limpio**: Funciones especializadas y reutilizables

✅ **Mejor UX**: Menos clics para agregar múltiples EPPs

---

## 🎯 Casos de Uso

### Caso 1: Agregar 5 EPPs rápidamente
```
1. Usuario abre modal
2. Ve tabla de EPP disponibles
3. Marca checkboxes de 5 EPPs
4. Hace clic "Finalizar"
   ✅ HECHO (antes: 10 clics mínimo)
```

### Caso 2: Agregar con cantidad variable
```
1. Usuario ingresa cantidad en cada EPP
2. Hace clic "Agregar"
3. EPP aparece en tabla inferior
4. Repite para otros EPPs
5. Hace clic "Finalizar"
   ✅ HECHO
```

### Caso 3: Editar cantidades
```
1. Usuario selecciona EPPs
2. Edita cantidad en tabla inferior
3. Hace clic "Finalizar"
   ✅ HECHO (Antes: abrirse modal de edición)
```

---

## 📈 Mejoras de Rendimiento

- **Renderizado eficiente**: Solo se actualiza lo necesario
- **Filtrado en cliente**: No requiere llamadas al servidor
- **Array bidireccional**: Sincronización entre tablas
- **Eventos optimizados**: Onchange en lugar de oninput donde sea posible

