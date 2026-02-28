# ✅ Checklist de Instalación - Nuevo Modal EPP

## 📋 Verificación Pre-Implementación

Antes de usar el nuevo modal, verifica que tu sistema tenga:

### 1️⃣ Archivo del Modal Actualizado
- [x] Archivo: `resources/views/asesores/pedidos/modals/modal-agregar-epp.blade.php`
- [x] Cambios realizados:
  - ✅ Nueva tabla de EPP disponibles con checkbox
  - ✅ Nueva tabla de EPP seleccionados
  - ✅ Buscador integrado
  - ✅ Funciones JavaScript para selección múltiple

### 2️⃣ Servicios Disponibles

Verifica en la consola del navegador:

```javascript
// Debe retornar true
console.log(typeof window.eppService !== 'undefined');
console.log(typeof window.eppService.obtenerEPP === 'function');

// O alternativa:
console.log(typeof window.eppLibrary !== 'undefined');
console.log(typeof window.eppLibrary.obtenerEPP === 'function');
```

**Si ambos retornan `false`**, necesitas implementar el servicio.

### 3️⃣ Estructura de EPP Retornada

El servicio debe retornar EPP con esta estructura:

```javascript
[
  {
    id: 1,                              // ✅ Requerido
    nombre_completo: "CASCO ROJO",     // ✅ Requerido
    nombre: "Casco",                   // ⚠️ Alternativa si no hay nombre_completo
    referencia: "CSC-001",             // ⚠️ Opcional (para mostrar en tabla)
    imagen: "/storage/epp/casco.jpg"   // ⚠️ Opcional (para miniaturas)
  }
]
```

### 4️⃣ Item Manager Compatible

Verifica que exista al menos uno:

```javascript
// Para vista normal (cotización)
console.log(typeof window.eppItemManager !== 'undefined');
console.log(typeof window.eppItemManager.crearItem === 'function');

// O para vista de nuevo pedido:
console.log(typeof window.eppItemManagerNuevo !== 'undefined');
console.log(typeof window.eppItemManagerNuevo.crearItem === 'function');
```

### 5️⃣ Estilos Tailwind CSS

Verifica que Tailwind esté incluido en el documento:

```html
<!-- En <head> -->
<link href="..." rel="stylesheet"> <!-- Tailwind CSS -->
```

Comprueba en consola:
```javascript
// Debe ser true (Tailwind aplica estilos)
getComputedStyle(document.body).fontFamily.includes('sans')
```

---

## 🚀 Pasos de Instalación/Actualización

### Step 1: Backup del Archivo Antiguo
```bash
cp resources/views/asesores/pedidos/modals/modal-agregar-epp.blade.php \
   modal-agregar-epp.blade.php.backup
```

### Step 2: Descargar/Actualizar el Nuevo Modal
Reemplaza el archivo con la versión actualizada.

### Step 3: Limpiar Cache (si es necesario)
```bash
php artisan view:clear
php artisan cache:clear
```

### Step 4: Verificar en el Navegador
```javascript
// Abre la consola del navegador (F12)
// Navega a la página con el modal

// Prueba 1: Verificar funciones
console.log(typeof abrirModalAgregarEPP);  // 'function'

// Prueba 2: Abrir modal
abrirModalAgregarEPP();

// Prueba 3: Verificar datos
console.log(eppDisponiblesList.length);  // > 0
console.log(eppAgregadosList.length);    // 0 (al inicio)
```

---

## 🔧 Solución de Problemas

### Problema 1: "El modal no abre"
```javascript
// ❌ Error:
// Uncaught ReferenceError: abrirModalAgregarEPP is not defined

// ✅ Solución:
// 1. Verifica que el archivo se actualizó correctamente
// 2. Recarga la página (Ctrl+F5)
// 3. Vacía el cache del navegador
```

### Problema 2: "La tabla de EPP está vacía"
```javascript
// ❌ Visible en consola:
// [cargarEPPDisponibles] EPPs obtenidos: 0

// ✅ Solución:
// 1. Verifica que window.eppService existe
// 2. Prueba manualmente:
window.eppService.obtenerEPP().then(data => {
  console.log('EPP obtenidos:', data);
  if (data.length === 0) {
    console.warn('El servicio retorna array vacío');
  }
});

// 3. Si el servicio no existe, implementa uno:
window.eppService = {
  obtenerEPP: async function() {
    const response = await fetch('/api/epp');
    return response.json();
  }
};
```

### Problema 3: "Los botones no funcionan"
```javascript
// ❌ Error en consola:
// Uncaught ReferenceError: agregarEPPDesdeTabla is not defined

// ✅ Solución:
// Verifica que las funciones están en el scope global:
console.log(typeof window.agregarEPPDesdeTabla);  // 'function'

// Si retorna 'undefined', agrega al final del script:
window.agregarEPPDesdeTabla = agregarEPPDesdeTabla;
window.filterarTablaEPP = filtrarTablaEPP;
// ... etc (ya está hecho en el nuevo archivo)
```

### Problema 4: "Los EPP no se agregan a la tabla inferior"
```javascript
// ❌ La tabla de seleccionados no se actualiza

// ✅ Solución:
// 1. Verifica que eppAgregadosList se está llenando:
// Abre la consola
// Marca un EPP en la tabla
console.log(eppAgregadosList);  // Debe tener elementos

// 2. Si está vacío pero esperabas datos:
// La función renderizarTablaEPPAgregados() podría fallar
// Busca errores en la consola (panel de errores)

// 3. Verifica manualmente:
agregarEPPDesdeTabla(1, document.querySelector('button'));
console.log(eppAgregadosList);  // Debe actualizarse
```

### Problema 5: "El buscador no funciona"
```javascript
// ❌ Escribo en el buscador pero nada cambia

// ✅ Solución:
// Verifica que el elemento existe:
console.log(document.getElementById('inputBuscadorEPPTabla'));  // No null

// Prueba manualmente:
document.getElementById('inputBuscadorEPPTabla').value = 'Casco';
filtrarTablaEPP('Casco');  // Debe filtrar

// Si usas otra versión del archivo:
// El ID podría ser 'inputBuscadorEPP' en lugar de 'inputBuscadorEPPTabla'
```

### Problema 6: "El contador no actualiza"
```javascript
// ❌ Dice "0 EPP seleccionados" aunque agregué varios

// ✅ Solución:
// Verifica que actualizarSeleccionEPP() se llama:
// Abre DevTools → Sources
// Busca breakpoint en actualizarSeleccionEPP()
// Interactúa con el modal

// O prueba manualmente:
eppAgregadosList.push({id: 1, nombre_completo: "TEST"});
actualizarSeleccionEPP();
console.log(document.getElementById('totalSeleccionados').textContent);
// Debe actualizar a "1"
```

---

## 🧪 Testing

### Test 1: Carga Inicial
```javascript
// Abre el modal
abrirModalAgregarEPP();

// Espera 1 segundo y verifica:
setTimeout(() => {
  console.log('✅ EPP cargados:', eppDisponiblesList.length);
  console.log('✅ Tabla visible:', 
    document.getElementById('tablaEPPDisponibles').style.display !== 'none'
  );
}, 1000);
```

### Test 2: Búsqueda
```javascript
// Simula búsqueda
filtrarTablaEPP('casco');

// Cuenta filas visibles
const filasVisibles = document.querySelectorAll(
  '#cuerpoTablaEPPDisponibles tr:not([style*="display: none"])'
).length;

console.log('✅ Filas visibles después de filtro:', filasVisibles);
// Debe ser > 0 si existen EPP con 'casco' en el nombre
```

### Test 3: Agregar EPP
```javascript
// Obtén el ID del primer EPP de la tabla
const primerEPP = document.querySelector('[data-epp-id]');
const eppId = primerEPP.getAttribute('data-epp-id');

// Agrega EPP
agregarEPPDesdeTabla(eppId);

console.log('✅ EPP agregados:', eppAgregadosList.length);
// Debe ser >= 1

// Verifica tabla inferior:
const tablaAgregados = document.getElementById('listaEPPAgregados');
console.log('✅ Tabla de agregados visible:', 
  window.getComputedStyle(tablaAgregados).display !== 'none'
);
```

### Test 4: Editar Cantidad
```javascript
// Encuentra un EPP en la tabla de agregados
const inputCantidad = document.querySelector('[onchange*="actualizarCantidadEPP"]');

if (inputCantidad) {
  // Cambia la cantidad
  inputCantidad.value = 10;
  inputCantidad.dispatchEvent(new Event('change'));
  
  console.log('✅ Cantidad actualizada');
} else {
  console.warn('⚠️ Input de cantidad no encontrado');
}
```

### Test 5: Eliminar EPP
```javascript
// Si hay EPP agregados
if (eppAgregadosList.length > 0) {
  const eppIdA Eliminar = eppAgregadosList[0].id;
  
  // Elimina
  eliminarEPPDeLista(eppIdAEliminar);
  
  console.log('✅ EPP eliminado, cantidad restante:', eppAgregadosList.length);
}
```

### Test 6: Finalizar
```javascript
// Agrega algunos EPP (ver Test 3)

// Luego finaliza
finalizarAgregarEPP();

// Verifica que se agregaron a window.itemsPedido:
console.log('✅ Items en pedido:', window.itemsPedido.length);
```

---

## 📊 Checklist de Funcionalidades

### Tabla de Disponibles
- [ ] Checkbox de seleccionar todos
- [ ] Checkboxes individuales
- [ ] Campo de cantidad editable
- [ ] Botón "Agregar"
- [ ] Búsqueda en tiempo real
- [ ] Mostrar imagen del EPP
- [ ] Mostrar nombre completo
- [ ] Mostrar referencia (si existe)

### Tabla de Seleccionados
- [ ] Muestra EPP agregados
- [ ] Campo cantidad editable
- [ ] Campo observaciones editable
- [ ] Muestra miniaturas de fotos
- [ ] Botón eliminar
- [ ] Se actualiza al agregar
- [ ] Se actualiza al eliminar
- [ ] Contador visible

### Controles
- [ ] Botón "Nuevo EPP" abre formulario
- [ ] Buscador filtra tabla
- [ ] Botón "Finalizar" habilitado/deshabilitado correctamente
- [ ] Contador de EPP seleccionados visible
- [ ] Modal se cierra al hacer clic fuera
- [ ] Modal se cierra al hacer clic "Cancelar"
- [ ] Modal se cierra al hacer clic "Finalizar"

---

## 💡 Optimizaciones Opcionales

### 1. Agregar Sorting a la Tabla
```javascript
// Hacer clickeable el header para ordenar
document.querySelector('th:contains("EPP")').onclick = () => {
  eppDisponiblesList.sort((a, b) => 
    a.nombre_completo.localeCompare(b.nombre_completo)
  );
  renderizarTablaEPPDisponibles(eppDisponiblesList);
};
```

### 2. Agregar Paginación
```javascript
const ITEMS_POR_PAGINA = 10;
let paginaActual = 1;

// ... implementar controles de paginación
```

### 3. Guardar Búsquedas Recientes
```javascript
// En localStorage
localStorage.setItem('ultimaBusquedaEPP', valorBusqueda);
```

### 4. Agregar Más Filtros
```javascript
// Además de búsqueda por nombre:
- Filtrar por categoría
- Filtrar por precio
- Filtrar por disponibilidad
```

---

## 📝 Notas Finales

✅ El nuevo modal es **100% compatible** con el sistema existente

✅ Las funciones antiguas se mantienen para **modo edición**

✅ No requiere cambios en el backend (solo frontend)

✅ Las imágenes y fotos siguen funcionando como antes

⚠️ **Requiere**: `window.eppService.obtenerEPP()` implementado

📞 **Soporte**: Si tienes errores, revisa la consola del navegador (F12)

