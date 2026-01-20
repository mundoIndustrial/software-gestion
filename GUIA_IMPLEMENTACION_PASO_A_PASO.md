# 🚀 GUÍA DE IMPLEMENTACIÓN DEL REFACTOR - PASO A PASO

**Versión:** 1.0  
**Fecha:** 20 de Enero 2026  
**Estado:** 🔴 LISTO PARA INICIAR

---

##  PREREQUISITOS

-  Backup del proyecto
-  Git branch creado: `refactor/consolidate-modales`
-  Entender el análisis previo
-  Tener acceso a todos los archivos

---

## 🔄 FASES DE IMPLEMENTACIÓN

### **FASE 0: Preparación (1-2 horas)**

#### Paso 1: Crear rama de trabajo
```bash
git checkout -b refactor/consolidate-modales
git pull origin main
```

#### Paso 2: Verificar archivos base
```bash
# Crear carpeta de utilidades si no existe
mkdir -p public/js/utilidades

# Verificar archivos necesarios
ls -la public/js/utilidades/
# Debe contener:
# - ui-modal-service.js  (ya creado)
# - deletion-service.js  (ya creado)
```

#### Paso 3: Documentar estado actual
```bash
# Contar líneas de código actual
wc -l index.blade.php pedidos-modal.js helpers-pedido-editable.js
# Guardar resultado para comparar después
```

---

### **FASE 1: Cargar nuevos servicios en el proyecto (30 mins)**

#### Paso 1: Verificar que los servicios estén cargados

En `index.blade.php`, agregar ANTES de otros scripts (`<script>`):

**Buscar:**
```php
@push('scripts')
<script>
    // Configurar variables globales para los modales
    window.fetchUrl = '/registros';
    window.modalContext = 'pedidos';
```

**Reemplazar por:**
```php
@push('scripts')
<!--  NUEVOS SERVICIOS CENTRALIZADOS (CARGAR PRIMERO) -->
<script src="{{ asset('js/utilidades/ui-modal-service.js') }}"></script>
<script src="{{ asset('js/utilidades/deletion-service.js') }}"></script>

<script>
    // Configurar variables globales para los modales
    window.fetchUrl = '/registros';
    window.modalContext = 'pedidos';
    
    //  Verificar que los servicios estén disponibles
    console.log(' UIModalService disponible:', !!window.UI);
    console.log(' DeletionService disponible:', !!window.Deletion);
```

#### Paso 2: Verificar en el navegador
```javascript
// En consola del navegador:
// Debe retornar: true, true
console.log(!!window.UI, !!window.Deletion)
```

---

### **FASE 2: Refactorizar Funciones de Eliminación (1-2 horas)**

#### Objetivo
Reemplazar todas las variantes de `eliminarPedido()`, `eliminarCotizacion()`, etc. con `DeletionService`

#### Paso 1: Refactorizar `index.blade.php` (líneas 515-625)

**Buscar y reemplazar:**

```javascript
//  VIEJO: 100+ líneas
function confirmarEliminarPedido(pedidoId, numeroPedido) {
    // ... modal HTML completo ...
}

function cerrarConfirmModal() {
    // ...
}

let isDeleting = false;

function eliminarPedido(pedidoId) {
    // ... lógica de DELETE ...
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    // ...
}
```

**Por:**

```javascript
//  NUEVO: 5 líneas
function confirmarEliminarPedido(pedidoId, numeroPedido) {
    Deletion.eliminarPedido(pedidoId, numeroPedido);
}

// Nota: mostrarNotificacion() ya no es necesaria
// (DeletionService usa UI.toastExito/Error)
```

#### Paso 2: Refactorizar `pedidos-list.js` (línea 190)

**Buscar:**
```javascript
async function eliminarPedido(pedido) {
    if (!confirm(`¿Deseas eliminar el pedido #${pedido.numero_pedido}?`)) return;
    
    try {
        const response = await fetch(`/pedidos/${pedido.id}`, { ... });
        // ...
    } catch (error) {
        // ...
    }
}
```

**Reemplazar por:**
```javascript
function eliminarPedido(pedido) {
    Deletion.eliminarPedido(pedido.id, pedido.numero_pedido);
}
```

#### Paso 3: Refactorizar `cotizaciones-index.js`

**Buscar:**
```javascript
function eliminarCotizacion(id) {
    if (confirm('¿Eliminar cotización?')) {
        fetch(`/asesores/cotizaciones/${id}`, { ... })
        .then(r => r.json())
        .then(d => { ... });
    }
}

function eliminarCotizacion(id, numeroCotizacion) {
    //  OTRA VARIANTE - DEDUPLICAR
    // ...
}
```

**Reemplazar por:**
```javascript
function eliminarCotizacion(id, numeroCotizacion) {
    Deletion.eliminarCotizacion(id, numeroCotizacion);
}
// Nota: ELIMINAR la otra variante (línea 209)
```

#### Paso 4: Refactorizar `clientes/index.blade.php`

**Buscar:**
```javascript
function eliminarCliente(id) {
    if (confirm('¿Estás seguro?')) {
        fetch(`/asesores/clientes/${id}`, {
            method: 'DELETE',
            // ...
        })
        // ...
    }
}
```

**Reemplazar por:**
```javascript
function eliminarCliente(id, nombreCliente) {
    Deletion.eliminarCliente(id, nombreCliente);
}
```

#### Paso 5: Refactorizar `inventario.js`

**Buscar:**
```javascript
function eliminarTela(telaId, telaNombre) {
    if (confirm('¿Eliminar tela?')) {
        fetch(`/asesores/telas/${telaId}`, { ... })
        // ...
    }
}
```

**Reemplazar por:**
```javascript
function eliminarTela(telaId, telaNombre) {
    Deletion.eliminarTela(telaId, telaNombre);
}
```

#### Paso 6: Refactorizar `users.js`

**Buscar:**
```javascript
function deleteUser(id) {
    if (confirm('¿Eliminar usuario?')) {
        fetch(`/users/${id}`, { ... })
        // ...
    }
}
```

**Reemplazar por:**
```javascript
function deleteUser(id, userEmail) {
    Deletion.eliminarUsuario(id, userEmail);
}
```

#### Verificar en navegador
```javascript
// Ir a cualquier página con eliminación (ej: Mis Pedidos)
// Hacer click en eliminar
// Debe aparecer modal de Swal mejorado con animación
```

---

### **FASE 3: Refactorizar Notificaciones (1-2 horas)**

#### Objetivo
Reemplazar `mostrarExito()`, `mostrarError()`, `mostrarAdvertencia()` en todos los archivos

#### Paso 1: Refactorizar `helpers-pedido-editable.js`

Este archivo será casi completamente reemplazado por `UIModalService`.

**Mantener solo funciones que NO estén en UIModalService:**
- Helpers de validación
- Helpers de transformación de datos
- etc.

**Eliminar completamente:**
```javascript
//  REMOVER - Ya en UIModalService
function confirmarEliminacion(titulo, mensaje, callback) { ... }
function mostrarExito(titulo, mensaje, duracion) { ... }
function mostrarError(titulo, mensaje) { ... }
function mostrarAdvertencia(titulo, mensaje, duracion) { ... }
function mostrarInfo(titulo, mensaje, duracion) { ... }
function confirmarAccion(mensaje) { ... }
```

**Reemplazar uso en archivos por:**
```javascript
//  NUEVO
UI.exito('Título', 'Mensaje');
UI.error('Título', 'Mensaje');
UI.advertencia('Título', 'Mensaje');
UI.info('Título', 'Mensaje');
UI.toastExito('Mensaje corto');
UI.toastError('Mensaje corto');
```

#### Paso 2: Buscar y reemplazar en TODOS los archivos

```bash
# Buscar todas las instancias
grep -r "mostrarExito\|mostrarError\|mostrarAdvertencia" public/js/ --include="*.js"

# Resultado esperado: (después del refactor)
# Cero coincidencias
```

**Script de búsqueda y reemplazo:**

```javascript
// En cada archivo .js encontrado:

// ANTES:
mostrarExito('Guardado', 'Los datos se guardaron correctamente');
mostrarError('Error', 'No se pudo guardar');
mostrarAdvertencia('Aviso', 'Operación completada');

// DESPUÉS:
UI.exito('Guardado', 'Los datos se guardaron correctamente');
UI.error('Error', 'No se pudo guardar');
UI.advertencia('Aviso', 'Operación completada');
```

#### Paso 3: Refactorizar Toast/Notificaciones en `inventario.js`

**Buscar:**
```javascript
function mostrarNotificacion(mensaje, tipo = 'info') {
    const notificacion = document.createElement('div');
    // ... 50+ líneas de código ...
    document.body.appendChild(notificacion);
}
```

**Reemplazar por:**
```javascript
// Usar UI.toastExito, UI.toastError, UI.toastInfo
```

---

### **FASE 4: Consolidar Modales Genéricos (2-3 horas)**

#### Objetivo
Unificar todos los modales dinámicos en UIModalService

#### Paso 1: Refactorizar `abrirModalDescripcion()` en `index.blade.php`

**Antes (líneas 112-200):**
```javascript
async function abrirModalDescripcion(pedidoId, tipo) {
    try {
        const loadingModal = document.createElement('div');
        // ... 80+ líneas de HTML + lógica ...
        
        let htmlContenido = '';
        if (data.prendas && Array.isArray(data.prendas)) {
            htmlContenido += '<div>...</div>';
            // ... generación de HTML ...
        }
        
        abrirModalCelda('Prendas y Procesos', htmlContenido, true);
    } catch (error) {
        // ...
    }
}
```

**Después:**
```javascript
async function abrirModalDescripcion(pedidoId, tipo) {
    UI.cargando('Cargando...', 'Por favor espera');
    
    try {
        const response = await fetch(`/asesores/pedidos/${pedidoId}/recibos-datos`);
        const data = await response.json();
        
        // Generar contenido (mantener lógica de construcción)
        let htmlContenido = construirDescripcionComoPrenda(...);
        
        Swal.close(); // Cerrar modal de carga
        
        //  Usar UIModalService
        UI.contenido({
            titulo: 'Prendas y Procesos',
            html: htmlContenido,
            ancho: '800px'
        });
    } catch (error) {
        Swal.close();
        UI.error('Error', 'No se pudo cargar la información');
    }
}
```

#### Paso 2: Refactorizar `abrirModalCelda()` en `index.blade.php`

**Antes (líneas 370-450):**
```javascript
function abrirModalCelda(titulo, contenido, isHtml = false) {
    // Crear modal personalizado de 80+ líneas
    const modalHTML = `
        <div id="celdaModal" style="...">
            <!-- HTML completo -->
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function cerrarModalCelda() {
    const modal = document.getElementById('celdaModal');
    if (modal) {
        modal.style.animation = '...';
        setTimeout(() => modal.remove(), 300);
    }
}
```

**Después:**
```javascript
function abrirModalCelda(titulo, contenido, isHtml = false) {
    UI.contenido({
        titulo: titulo,
        html: contenido,
        ancho: '600px'
    });
}

// Nota: cerrarModalCelda() ya no es necesaria
// (Swal maneja el cierre automáticamente)
```

#### Paso 3: Refactorizar Modales de Edición en `index.blade.php`

**Mantener funciones específicas pero simplificar:**

```javascript
//  Mantener pero simplificar
function abrirEditarDatos() {
    const datos = window.datosEdicionPedido;
    const html = `
        <input id="editCliente" value="${datos.cliente}">
        <input id="editFormaPago" value="${datos.forma_de_pago}">
        <textarea id="editObservaciones">${datos.observaciones}</textarea>
    `;
    
    UI.contenido({
        titulo: 'Editar Datos Generales',
        html: html,
        ancho: '600px'
    });
}

function abrirEditarEPP() {
    const datos = window.datosEdicionPedido;
    const epp = datos.epp || [];
    
    let htmlListaEPP = '';
    epp.forEach((item, idx) => {
        htmlListaEPP += `<button onclick="abrirEditarEPPEspecifico(${idx})">${item.nombre}</button>`;
    });
    
    UI.contenido({
        titulo: 'Selecciona un EPP para Editar',
        html: htmlListaEPP,
        ancho: '600px'
    });
}
```

---

### **FASE 5: Backend - Consolidar Controladores (2-3 horas)**

#### Paso 1: Crear `PedidosController.php` consolidado

**Crear archivo:** `app/Http/Controllers/Asesores/PedidosController.php`

```php
<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use Illuminate\Http\Request;

class PedidosController extends Controller
{
    public function __construct(
        private PedidoService $pedidoService,
        private ItemPedidoService $itemService,
    ) {}

    //  Todos los métodos documentados en REFACTOR_EJEMPLOS_ANTES_DESPUES.md
    
    public function index() { ... }
    public function show($id) { ... }
    public function datosEdicion($id) { ... }
    public function store(Request $request) { ... }
    public function update($id, Request $request) { ... }
    public function destroy($id) { ... }
    public function agregarItem(Request $request) { ... }
    // ... etc
}
```

#### Paso 2: Actualizar rutas en `routes/web.php`

**Buscar:**
```php
//  VIEJO: 2 controladores para pedidos
Route::resource('pedidos', 'AsesoresController');
Route::resource('pedidos-produccion', 'CrearPedidoEditableController');
```

**Reemplazar por:**
```php
//  NUEVO: 1 controlador consolidado
Route::prefix('asesores')->middleware('auth')->group(function () {
    // Rutas RESTful
    Route::apiResource('pedidos', Asesores\PedidosController::class);
    
    // Rutas especiales
    Route::get('pedidos/{id}/datos-edicion', [Asesores\PedidosController::class, 'datosEdicion']);
    Route::get('pedidos/{id}/datos-factura', [Asesores\PedidosController::class, 'datosFactura']);
    Route::post('pedidos/{id}/anular', [Asesores\PedidosController::class, 'anular']);
    Route::post('pedidos/{id}/confirmar', [Asesores\PedidosController::class, 'confirmar']);
    
    // Items
    Route::post('pedidos/{pedidoId}/items', [Asesores\PedidosController::class, 'agregarItem']);
    Route::put('pedidos/{pedidoId}/items/{itemId}', [Asesores\PedidosController::class, 'actualizarItem']);
    Route::delete('pedidos/{pedidoId}/items/{itemId}', [Asesores\PedidosController::class, 'eliminarItem']);
});
```

#### Paso 3: Actualizar URLs en Frontend

**Buscar todas las URLs antiguas:**

```javascript
// ANTES
fetch('/asesores/pedidos-produccion/...')
fetch('/pedidos/...')
fetch('/asesores/pedidos/...')

// DESPUÉS (Consolidado)
fetch('/asesores/pedidos/...')
```

**Script de búsqueda:**
```bash
grep -r "pedidos-produccion\|/pedidos/" public/js/ resources/ --include="*.js" --include="*.blade.php"
```

**Reemplazar en:**
- `public/js/asesores/pedidos-list.js`
- `public/js/asesores/pedidos.js`
- `public/js/asesores/invoice-from-list.js`
- `resources/views/asesores/pedidos/index.blade.php`
- etc.

---

### **FASE 6: Testing y Validación (2-3 horas)**

#### Paso 1: Testing Manual

```javascript
// 1. Probar Eliminación de Pedido
// - Ir a Mis Pedidos
// - Click en "Eliminar"
// - Confirmar en modal Swal
// - Verificar que se elimina

// 2. Probar Notificaciones
// - Ejecutar en consola: UI.toastExito('Test')
// - Ejecutar en consola: UI.error('Test', 'Test message')
// - Verificar que aparecen animaciones

// 3. Probar Modales
// - Ejecutar: UI.contenido({titulo: 'Test', html: '<p>Test</p>'})
// - Verificar que aparece modal
```

#### Paso 2: Verificar en Navegador

```javascript
// Consola:
console.log('Services cargados:', !!UI, !!Deletion);
console.log('Config UIModalService:', UI.getConfig());
```

#### Paso 3: Tests Unitarios (Opcional)

```javascript
// test/ui-modal-service.test.js
describe('UIModalService', () => {
    it('debe mostrar toast de éxito', () => {
        const toast = UI.toastExito('Test');
        expect(toast).toBeDefined();
        expect(toast.style.background).toContain('#10b981');
    });
    
    it('debe confirmar eliminación', async () => {
        const result = await UI.confirmarEliminacion('Test', 'ID-123');
        expect(result).toBeDefined();
    });
});
```

---

### **FASE 7: Limpieza Final (30 mins)**

#### Paso 1: Remover archivos obsoletos

```bash
#  Estos archivos pueden ser eliminados o reducidos
rm public/js/modulos/crear-pedido/utilidades/helpers-pedido-editable.js  # SI NO SE USA EN OTROS LADOS

# O simplemente dejar como fallback
```

#### Paso 2: Verificar que no hay conflictos

```bash
# Buscar funciones duplicadas
grep -r "function eliminarPedido\|function mostrarExito" public/js/ resources/ --include="*.js"
# Resultado esperado: 0 coincidencias
```

#### Paso 3: Documentar cambios en CHANGELOG

```markdown
## Versión X.X.X - Refactor de Modales y Notificaciones

###  Cambios
- Centralizado UIModalService para todos los modales y notificaciones
- Centralizado DeletionService para operaciones de eliminación
- Consolidado PedidosController (AsesoresController + CrearPedidoEditableController)
- Eliminado 500+ líneas de código duplicado
- Mejorada consistencia visual en modales

###  Métricas
- Líneas eliminadas: ~650
- Archivos simplificados: 10+
- Controladores consolidados: 2 → 1

###  Notas
- Los servicios se cargan automáticamente en index.blade.php
- Las URLs de endpoints ahora son consistentes
```

---

##  CHECKLIST FINAL

### Antes de hacer commit:

- [ ]  UIModalService cargado y funcional
- [ ]  DeletionService cargado y funcional
- [ ]  Todas las funciones de eliminación refactorizadas
- [ ]  Todas las notificaciones usando UI.*
- [ ]  Modales consolidados en UIModalService
- [ ]  Backend consolidado (PedidosController)
- [ ]  URLs actualizadas en frontend
- [ ]  No hay código duplicado (verificado con grep)
- [ ]  Testing manual completado sin errores
- [ ]  Consola del navegador sin errores

### Antes de hacer merge a main:

- [ ]  Code Review realizado
- [ ]  Tests E2E pasaron
- [ ]  Ningún breakage en funcionalidad
- [ ]  Documentación actualizada
- [ ]  CHANGELOG actualizado

---

## 📞 RESOLUCIÓN DE PROBLEMAS

### Error: "UI is not defined"

**Causa:** Los servicios no se cargaron

**Solución:** Verificar que en `index.blade.php` esté:
```php
<script src="{{ asset('js/utilidades/ui-modal-service.js') }}"></script>
<script src="{{ asset('js/utilidades/deletion-service.js') }}"></script>
```

### Error: "DeletionService is not defined"

**Causa:** Mismo que arriba

**Solución:** Cargar ambos servicios ANTES de otros scripts

### Modal aparece pero no cierra

**Causa:** Falta de evento de cierre

**Solución:** Los nuevos servicios cierran automáticamente, no hace falta `onclick="cerrarModal()"`

### Endpoint 404 al eliminar

**Causa:** URLs no actualizadas

**Solución:** Buscar y reemplazar URLs antiguas con nuevas

```bash
grep -r "pedidos-produccion" public/js/ | head -20
```

---

##  TIEMPO TOTAL ESTIMADO

| Fase | Tiempo |
|------|--------|
| Fase 0: Preparación | 1-2h |
| Fase 1: Cargar servicios | 0.5h |
| Fase 2: Refactor eliminación | 1-2h |
| Fase 3: Refactor notificaciones | 1-2h |
| Fase 4: Consolidar modales | 2-3h |
| Fase 5: Backend consolidado | 2-3h |
| Fase 6: Testing | 2-3h |
| Fase 7: Limpieza | 0.5h |
| **TOTAL** | **10-18 horas** |

---

## 📚 REFERENCIAS

- Análisis: `ANALISIS_DUPLICACION_CODIGO_REFACTOR.md`
- Ejemplos: `REFACTOR_EJEMPLOS_ANTES_DESPUES.md`
- UIModalService: `public/js/utilidades/ui-modal-service.js`
- DeletionService: `public/js/utilidades/deletion-service.js`

