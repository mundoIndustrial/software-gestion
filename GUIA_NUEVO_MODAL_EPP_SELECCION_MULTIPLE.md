# Guía: Nuevo Modal EPP con Selección Múltiple

## Cambios Realizados

Se ha rediseñado completamente el modal "Agregar EPP al Pedido" de [resources/views/asesores/pedidos/modals/modal-agregar-epp.blade.php] para una experiencia más amigable con soporte de selección múltiple.

### 📋 Estructura del Nuevo Modal

#### **Sección 1: Buscador y Controles**
- Input de búsqueda para filtrar EPP por nombre o referencia
- Botón "Nuevo EPP" para crear EPP sobre la marcha
- Formulario emergente para crear nuevos EPP

```html
<!-- Búsqueda en tiempo real -->
<input type="text" id="inputBuscadorEPPTabla" onkeyup="filtrarTablaEPP(this.value)">
```

#### **Sección 2: Tabla de EPP Disponibles (NUEVA)**
- Tabla con checkbox de selección múltiple
- Incluye:
  - ✅ Checkbox para seleccionar/deseleccionar EPP
  - 📦 Nombre e imagen del EPP
  - 🔢 Campo de cantidad editable
  - ➕ Botón "Agregar" para añadir al carrito
- Checkbox "Seleccionar todo"
- Búsqueda en tiempo real que filtra la tabla

```html
<table id="tablaEPPDisponibles">
  <thead>
    <tr>
      <th>Seleccionar</th>
      <th>EPP</th>
      <th>Cantidad</th>
      <th>Acción</th>
    </tr>
  </thead>
  <tbody id="cuerpoTablaEPPDisponibles"></tbody>
</table>
```

#### **Sección 3: Tabla de EPP Seleccionados**
- Muestra los EPP que han sido agregados
- Permite:
  - ✏️ Editar cantidad
  - 📝 Agregar observaciones
  - 📸 Ver miniaturas de fotos
  - ❌ Eliminar EPP
- Contador automático de EPP seleccionados

```html
<div id="listaEPPAgregados">
  <table id="tablaEPPAgregados">
    <tbody id="cuerpoTablaEPP"></tbody>
  </table>
</div>
```

#### **Footer mejorado**
- Muestra contador de "X EPP seleccionados"
- Botones: Cancelar, Finalizar
- En modo edición: botón "Guardar Cambios"

---

## 🔑 Nuevas Funciones JavaScript

### Variables Globales
```javascript
let eppAgregadosList = [];      // EPP seleccionados para agregar
let eppDisponiblesList = [];    // Lista de EPP disponibles
```

### Funciones Principales

#### 1. **cargarEPPDisponibles()**
```javascript
// Carga los EPP disponibles desde el servicio
// Llamada automáticamente al abrir el modal
async function cargarEPPDisponibles()
```
- Obtiene EPP del servicio `window.eppService.obtenerEPP()`
- Renderiza en la tabla de disponibles
- Maneja errores internamente

#### 2. **filtrarTablaEPP(valor)**
```javascript
// Filtra la tabla en tiempo real según búsqueda
function filtrarTablaEPP(valor)
```
- **Entrada**: Texto a buscar
- **Funcionalidad**: Busca en nombres y referencias
- **Resultado**: Muestra/oculta filas dinámicamente

#### 3. **agregarEPPDesdeTabla(eppId, button)**
```javascript
// Agrega EPP a la lista seleccionada
function agregarEPPDesdeTabla(eppId, button)
```
- **Entrada**: ID del EPP e instancia del botón
- **Acciones**:
  - Marca el checkbox
  - Lee la cantidad
  - Agrega/actualiza en `eppAgregadosList`
  - Renderiza tabla de seleccionados
  - Actualiza contadores

#### 4. **seleccionarTodosEPP(checkbox)**
```javascript
// Selecciona/deselecciona todos los EPP visibles
function seleccionarTodosEPP(checkbox)
```
- **Si está marcado**: Agrega todos los EPP visibles (respeta filtro)
- **Si está desmarcado**: Limpia la lista

#### 5. **renderizarTablaEPPAgregados()**
```javascript
// Re-renderiza la tabla de EPP seleccionados
function renderizarTablaEPPAgregados()
```
- Muestra cantidad actual
- Permite editar cantidad y observaciones
- Muestra miniaturas de fotos
- Botón de eliminar

#### 6. **eliminaEPPDeLista(eppId)**
```javascript
// Elimina un EPP de la lista seleccionada
function eliminarEPPDeLista(eppId)
```
- Remueve de `eppAgregadosList`
- Desmarca el checkbox
- Actualiza tabla y contadores

#### 7. **actualizarSeleccionEPP()**
```javascript
// Actualiza UI: contador, botones, visibilidad
function actualizarSeleccionEPP()
```
- Actualiza `#totalSeleccionados`
- Habilita/deshabilita botón "Finalizar"
- Muestra/oculta tabla de agregados

---

## 🎯 Flujo de Uso

### Agregando EPP (Modo Normal)

1️⃣ **Usuario abre modal** 
   - `abrirModalAgregarEPP()` carga los EPP disponibles
   - Tabla se renderiza con todos los EPP

2️⃣ **Usuario busca EPP (opcional)**
   - Escribe en el buscador
   - Tabla se filtra automáticamente con `filtrarTablaEPP()`

3️⃣ **Usuario selecciona EPP**
   - **Opción A**: Marca checkbox
   - **Opción B**: Ingresa cantidad y hace clic en "Agregar"
   - EPP se añade automáticamente a la tabla inferior

4️⃣ **Usuario edita cantidades/observaciones (opcional)**
   - Edita campos en la tabla de seleccionados

5️⃣ **Usuario hace clic "Finalizar"**
   - `finalizarAgregarEPP()` procesa todos los EPP
   - Se agregan a `window.itemsPedido`
   - Se cierr el modal

---

## 🔧 Integración con Sistema Existente

### Pendencias de Integración

El nuevo modal depende de:

1. **Servicio EPP disponible**
   ```javascript
   window.eppService.obtenerEPP()  // Debe retornar array de EPP
   // O
   window.eppLibrary.obtenerEPP()  // Alternativa
   ```
   **Retorna formato esperado:**
   ```javascript
   {
     id: 1,
     nombre_completo: "CASCO DE SEGURIDAD",
     nombre: "Casco",
     referencia: "CSC-001",
     imagen: "/storage/epp/casco.jpg"
   }
   ```

2. **Servicio Item Manager**
   ```javascript
   window.eppItemManager.crearItem()      // Crear nuevo item
   window.eppItemManagerNuevo.crearItem() // Para vista de nuevo pedido
   ```

3. **Estilos Tailwind CSS** (ya incluidos)
   - Colores: blue, green, gray
   - Componentes: tables, inputs, buttons

---

## ✨ Características Principales

| Característica | Implementado | Estado |
|---|---|---|
| Tabla con EPP disponibles | ✅ | Listo |
| Checkbox de selección múltiple | ✅ | Listo |
| Búsqueda/filtro en tiempo real | ✅ | Listo |
| Cantidad editable en tabla | ✅ | Listo |
| Tabla de EPP seleccionados | ✅ | Listo |
| Edición de cantidad en resultado | ✅ | Listo |
| Edición de observaciones | ✅ | Listo |
| Eliminación de EPP | ✅ | Listo |
| Contador automático | ✅ | Listo |
| Botón "Crear nuevo EPP" | ✅ | Listo |
| Modo edición de EPP existente | ⚠️ | Pendiente verificación |
| Fotos de EPP | ⚠️ | Pendiente integración |

---

## 🚀 Próximos Pasos

### 1. Verificar que `eppService` esté disponible
```javascript
console.log(window.eppService); // Debe existir
```

### 2. Pruebas
```javascript
// En consola del navegador
abrirModalAgregarEPP();
// El modal debe abrirse y cargar EPP disponibles
```

### 3. Debugging
```javascript
// Ver lista cargada:
console.log(eppDisponiblesList);

// Ver EPP seleccionados:
console.log(eppAgregadosList);
```

---

## 📝 Notas Importantes

✅ **Compatible con modo edición**: El modal aún permite editar EPP existentes (usa el código antiguo de formulario)

✅ **Mantiene fotos**: Las funciones para manejar fotos (`agregarFotoEPP()`, `manejarSubidaFotosEPP()`) se conservan

✅ **Drag & Drop**: Los contenedores de fotos siguen soportando drag & drop

⚠️ **Requiere actualización de servicio**: Si no existe `eppService.obtenerEPP()`, necesitarás implementarla

---

## 🐛 Solución de Problemas

### Problema: "La tabla de EPP disponibles está vacía"
**Solución**: Verificar que `window.eppService.obtenerEPP()` retorna datos
```javascript
window.eppService.obtenerEPP().then(data => console.log(data));
```

### Problema: "Los EPP no se agregan a la tabla"
**Solución**: Verificar que `eppAgregadosList` tiene elementos
```javascript
console.log(eppAgregadosList);
```

### Problema: "El botón Finalizar no funciona"
**Solución**: Verificar que `eppAgregadosList.length > 0`
```javascript
console.log('Total EPP:', eppAgregadosList.length);
```

---

## 📞 Soporte

Para integrar completamente con tu sistema:
1. Verifica que `window.eppService` está disponible
2. Asegúrate que retorna EPP con los campos esperados
3. Prueba localmente con `cargarEPPDisponibles()`
4. Ajusta formatos de datos si es necesario

