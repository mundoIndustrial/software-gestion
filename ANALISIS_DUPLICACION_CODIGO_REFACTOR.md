# 🔍 ANÁLISIS DE DUPLICACIÓN DE CÓDIGO - REFACTOR NECESARIO

**Fecha:** 20 de Enero 2026  
**Estado:** ⚠️ CRÍTICO - Código altamente duplicado  
**Líneas de código:** +1000 (solo en index.blade.php)

---

## 📊 RESUMEN EJECUTIVO

El proyecto tiene **4 capas de duplicación**:
1. **Funciones modales duplicadas** (abrirModal, cerrarModal, etc.)
2. **Helpers de notificaciones fragmentados** (Swal.fire repetido en 10+ archivos)
3. **Servicios backend sin centralización** (AsesoresController con 16 servicios inyectados)
4. **Lógica de eliminación/confirmación repetida** (7+ variantes)

---

## 🔴 DUPLICACIONES CRÍTICAS ENCONTRADAS

### 1. **FUNCIONES MODALES (Frontend)**

#### 📍 Archivos afectados:
- `resources/views/asesores/pedidos/index.blade.php` (líneas 70-200)
- `public/js/asesores/pedidos-modal.js` (líneas 60-102)
- `public/js/asesores/cotizaciones-show.js` (líneas 52-397)
- `public/js/asesores/invoice-from-list.js` (líneas 331, 685)
- `public/js/inventario-telas/inventario.js` (líneas 156+)
- `public/js/users.js` (líneas 18+)
- `resources/views/asesores/clientes/index.blade.php` (líneas 112+)

#### 🎯 Funciones duplicadas:

```javascript
// ❌ PATRÓN DUPLICADO 1: Abrir/Cerrar Modal Genérico
function abrirModal[X]() {
    const modal = document.getElementById('modal[X]');
    modal.style.display = 'flex';
}

function cerrarModal[X]() {
    const modal = document.getElementById('modal[X]');
    modal.style.display = 'none';
}

// Encontrado en: pedidos-modal.js, cotizaciones-show.js, clientes/index, users.js
```

#### 📋 Duplicados específicos:

| Función | Ubicaciones | Variantes |
|---------|-----------|----------|
| `abrirModalDescripcion()` | index.blade.php (line 112) | Construye HTML dinámico |
| `abrirModalCelda()` | index.blade.php (line 370) | Modal mejorado con contenido |
| `cerrarModalCelda()` | index.blade.php (line 450) | Cierre con animación |
| `confirmarEliminarPedido()` | index.blade.php (line 515) | Modal de confirmación |
| `eliminarPedido()` | index.blade.php (line 625), pedidos-list.js | Lógica DELETE al backend |
| `verMotivoanulacion()` | index.blade.php (line 88) | Modal de motivo |

---

### 2. **HELPERS DE NOTIFICACIONES (Frontend)**

#### 📍 Archivos afectados:
- `public/js/modulos/crear-pedido/utilidades/helpers-pedido-editable.js` (87 líneas de helpers)
- `public/js/inventario-telas/inventario.js` (líneas 400+)
- `public/js/dashboard/dashboard.js` (líneas 494+)
- `public/js/users.js` (Swal.fire inline)
- `public/js/asesores/cotizaciones-index.js` (Swal.fire inline)
- `resources/views/asesores/reportes/index.blade.php` (inline)

#### 🎯 Funciones duplicadas:

```javascript
// ✅ CONSOLIDAR EN: public/js/utilidades/modal-helpers.js

// Confirmación de eliminación
function confirmarEliminacion(titulo, mensaje, callback) { ... }

// Notificaciones Swal
function mostrarExito(titulo, mensaje, duracion = 2000) { ... }
function mostrarError(titulo, mensaje) { ... }
function mostrarAdvertencia(titulo, mensaje, duracion = 2000) { ... }
function mostrarInfo(titulo, mensaje, duracion = 3000) { ... }

// Toast personalizados
function mostrarToastExito(mensaje) { ... }
function mostrarToastError(mensaje) { ... }
function mostrarNotificacion(mensaje, tipo = 'info') { ... }
```

#### 📊 Métrica de duplicación:
- **Líneas duplicadas:** ~180 líneas de código Swal.fire/Toast repetido
- **Archivos:** 7 archivos tienen su propia versión
- **Variantes:** 5+ versiones diferentes del mismo código

---

### 3. **LÓGICA DE ELIMINACIÓN (Frontend)**

#### 📍 Ubicaciones:

```javascript
// VARIANTE 1: index.blade.php (líneas 515-567)
function confirmarEliminarPedido(pedidoId, numeroPedido) {
    // Modal de confirmación personalizado
    // Fetch DELETE con CSRF
    // Validación de estado
}

// VARIANTE 2: pedidos-list.js (línea 190)
async function eliminarPedido(pedido) {
    // Confirmación simple
    // Fetch DELETE
}

// VARIANTE 3: cotizaciones-index.js (línea 209)
function eliminarCotizacion(id) {
    // Lógica duplicada
}

// VARIANTE 4: cotizaciones-index.js (línea 421)
function eliminarCotizacion(id, numeroCotizacion) {
    // Otra variante (¡SIN DEDUPLICACIÓN!)
}

// VARIANTE 5: clientes/index.blade.php
function eliminarCliente(id) {
    // Confirmación + Fetch DELETE
}

// VARIANTE 6: usuarios
function deleteUser(id) { ... }

// VARIANTE 7: inventario-telas
function eliminarTela(telaId, telaNombre) { ... }
```

---

### 4. **SERVICIOS BACKEND (PHP)**

#### 📍 Archivos afectados:
- `app/Http/Controllers/AsesoresController.php` (16 servicios inyectados)
- `app/Http/Controllers/Asesores/CrearPedidoEditableController.php`
- `app/Http/Controllers/SupervisorPedidosController.php`

#### 🎯 Problema: God Object Pattern en AsesoresController

```php
// ❌ AsesoresController inyecta 16 servicios:
class AsesoresController extends Controller {
    public function __construct(
        PedidoProduccionRepository $pedidoProduccionRepository,
        DashboardService $dashboardService,
        NotificacionesService $notificacionesService,
        PerfilService $perfilService,
        EliminarPedidoService $eliminarPedidoService,
        ObtenerFotosService $obtenerFotosService,
        AnularPedidoService $anularPedidoService,
        ObtenerPedidosService $obtenerPedidosService,
        ObtenerProximoPedidoService $obtenerProximoPedidoService,
        ObtenerDatosFacturaService $obtenerDatosFacturaService,
        ObtenerDatosRecibosService $obtenerDatosRecibosService,
        ProcesarFotosTelasService $procesarFotosTelasService,
        GuardarPedidoLogoService $guardarPedidoLogoService,
        GuardarPedidoProduccionService $guardarPedidoProduccionService,
        ConfirmarPedidoService $confirmarPedidoService,
        ActualizarPedidoService $actualizarPedidoService,
        ObtenerPedidoDetalleService $obtenerPedidoDetalleService
    ) { ... }
}
```

**Problema:** El controlador es responsable de TOO MUCH - violaría Single Responsibility Principle

#### 🔴 Métodos duplicados:

```php
// En AsesoresController
public function guardarPedido()
public function actualizarPedido()  
public function deletePedido()

// En CrearPedidoEditableController
public function agregarItem()
public function eliminarItem()
public function actualizarItem()

// Lógica potencialmente duplicada entre ambos
```

---

### 5. **RUTAS DE GENERACIÓN DE CONTENIDO DUPLICADAS**

#### Endpoints que retornan HTML/JSON similar:

```php
// AsesoresController
GET  /asesores/pedidos/{pedidoId}/recibos-datos      // Retorna prendas + procesos
GET  /asesores/pedidos/{pedidoId}/datos-edicion      // Retorna datos completos

// CrearPedidoEditableController  
GET  /asesores/pedidos-produccion/{pedidoId}/datos-edicion

// Ambos hacen la MISMA COSA pero en diferentes endpoints
```

---

## 🛠️ PLAN DE REFACTORIZACIÓN

### **FASE 1: Centralizar Helpers de UI (URGENTE)**

#### Crear: `public/js/utilidades/ui-modal-service.js`
```javascript
/**
 * UIModalService - Gestión centralizada de modales y notificaciones
 * SOLID: Single Responsibility - Solo manejo de UI
 */

class UIModalService {
    // Modal handlers
    static abrirModal(id, config = {}) { ... }
    static cerrarModal(id) { ... }
    static cerrarTodasLos() { ... }
    
    // Confirmaciones
    static confirmar(titulo, mensaje, callback) { ... }
    static confirmarEliminacion(item, callback) { ... }
    
    // Notificaciones Swal
    static exito(titulo, mensaje, duracion = 2000) { ... }
    static error(titulo, mensaje) { ... }
    static advertencia(titulo, mensaje, duracion = 2000) { ... }
    static info(titulo, mensaje, duracion = 3000) { ... }
    
    // Toasts
    static toastExito(mensaje) { ... }
    static toastError(mensaje) { ... }
    static toastInfo(mensaje) { ... }
}

// Exponer globalmente
window.UI = UIModalService;
```

**Archivos a refactorizar:**
- ✅ `helpers-pedido-editable.js` → Usar `UIModalService`
- ✅ `inventario.js` → Usar `UIModalService`
- ✅ `dashboard.js` → Usar `UIModalService`
- ✅ `pedidos-modal.js` → Usar `UIModalService`
- ✅ `index.blade.php` → Usar `UIModalService`

---

### **FASE 2: Consolidar lógica de eliminación**

#### Crear: `public/js/utilidades/deletion-service.js`
```javascript
/**
 * DeletionService - Gestión centralizada de eliminación de recursos
 */

class DeletionService {
    /**
     * Eliminar un recurso genérico
     * @param {string} endpoint - URL del endpoint DELETE
     * @param {string} resourceName - Nombre del recurso (para el mensaje)
     * @param {string} identifier - Identificador (número de pedido, etc.)
     * @param {Function} onSuccess - Callback de éxito
     */
    static async eliminar(endpoint, resourceName, identifier, onSuccess) {
        const confirmed = await UI.confirmarEliminacion(
            `Eliminar ${resourceName}`,
            `¿Estás seguro de que deseas eliminar ${resourceName} #${identifier}?`
        );
        
        if (!confirmed) return;
        
        try {
            const response = await fetch(endpoint, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                UI.toastExito(`${resourceName} eliminado correctamente`);
                onSuccess?.(data);
            } else {
                UI.toastError(data.message || 'Error al eliminar');
            }
        } catch (error) {
            console.error('Error:', error);
            UI.toastError('Error de conexión');
        }
    }
    
    static getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }
}
```

**Uso:**
```javascript
// Antes (repetido en 7 lugares):
function eliminarPedido(pedidoId) {
    if(!confirm(...)) return;
    fetch(...).then(...);
}

// Después (centralizado):
function eliminarPedido(pedidoId) {
    DeletionService.eliminar(
        `/asesores/pedidos-produccion/${pedidoId}`,
        'Pedido',
        pedidoId,
        () => location.reload()
    );
}
```

---

### **FASE 3: Refactorizar Backend - Consolidar Controladores**

#### Crear: `app/Http/Controllers/Asesores/PedidosController.php`

```php
<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * CONTROLADOR CONSOLIDADO para gestión de PEDIDOS
 * Agrupa funcionalidades de:
 * - AsesoresController (pedidos relacionados)
 * - CrearPedidoEditableController (creación)
 */
class PedidosController extends Controller
{
    public function __construct(
        private PedidoService $pedidoService,
        private PedidoItemService $itemService,
    ) {}

    // =============== LISTAR Y OBTENER ===============
    public function index() { ... }
    public function show(int $pedidoId) { ... }
    public function datosEdicion(int $pedidoId) { ... } // ✅ UN SOLO ENDPOINT
    public function datosFactura(int $pedidoId) { ... }
    public function datosRecibos(int $pedidoId) { ... }
    
    // =============== CREAR Y ACTUALIZAR ===============
    public function store(Request $request) { ... }
    public function update(int $pedidoId, Request $request) { ... }
    public function destroy(int $pedidoId) { ... }
    
    // =============== ITEMS (PRENDAS, EPP, ETC) ===============
    public function agregarItem(Request $request) { ... }
    public function actualizarItem(int $pedidoId, int $itemId, Request $request) { ... }
    public function eliminarItem(int $pedidoId, int $itemId) { ... }
    
    // =============== OPERACIONES ===============
    public function anular(int $pedidoId, Request $request) { ... }
    public function confirmar(int $pedidoId) { ... }
}
```

**Cambios:**
- ✅ Unifica `AsesoresController` + `CrearPedidoEditableController`
- ✅ Elimina duplicación de endpoints
- ✅ Mejora Single Responsibility

---

### **FASE 4: Frontend - Consolidar Modales Genéricos**

#### Crear: `public/js/componentes/modal-genericos.js`

```javascript
/**
 * Modales Genéricos Reutilizables
 * SOLID: Abierto/Cerrado - Extensible sin modificar
 */

class GenericModals {
    /**
     * Modal de confirmación genérico
     */
    static async confirmar(config = {}) {
        const {
            titulo = 'Confirmar',
            mensaje = '¿Estás seguro?',
            icono = 'question',
            confirmText = 'Sí',
            cancelText = 'Cancelar',
            dangerMode = false
        } = config;
        
        return Swal.fire({
            title: titulo,
            text: mensaje,
            icon: icono,
            showCancelButton: true,
            confirmButtonColor: dangerMode ? '#dc3545' : '#10b981',
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmText,
            cancelButtonText: cancelText
        });
    }
    
    /**
     * Modal de contenido HTML genérico
     */
    static contenido(config = {}) {
        const {
            titulo = '',
            html = '',
            ancho = '600px',
            botones = []
        } = config;
        
        return Swal.fire({
            title: titulo,
            html: html,
            width: ancho,
            showConfirmButton: botones.length === 0,
            confirmButtonText: botones[0]?.texto || 'Aceptar',
            ...config
        });
    }
    
    /**
     * Modal de edición genérica
     */
    static editar(config = {}) { ... }
}
```

**Uso:**
```javascript
// Antes (duplicado en todo el código):
const confirmHTML = `<div>...</div>`;
document.body.insertAdjacentHTML('beforeend', confirmHTML);

// Después:
const result = await GenericModals.confirmar({
    titulo: 'Editar Pedido',
    mensaje: '¿Aplicar cambios?'
});
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### Etapa 1: Preparación
- [ ] Crear `public/js/utilidades/ui-modal-service.js`
- [ ] Crear `public/js/utilidades/deletion-service.js`
- [ ] Crear `public/js/componentes/modal-genericos.js`
- [ ] Actualizar `index.blade.php` para cargar estos servicios PRIMERO

### Etapa 2: Refactorización Frontend
- [ ] Refactorizar `helpers-pedido-editable.js`
- [ ] Refactorizar `inventario.js`
- [ ] Refactorizar `pedidos-modal.js`
- [ ] Refactorizar `index.blade.php` (eliminar 500+ líneas de JS)
- [ ] Refactorizar `pedidos-list.js`
- [ ] Refactorizar `cotizaciones-index.js`

### Etapa 3: Refactorización Backend
- [ ] Crear `PedidosController` consolidado
- [ ] Mover métodos de `AsesoresController`
- [ ] Mover métodos de `CrearPedidoEditableController`
- [ ] Actualizar rutas en `routes/web.php`
- [ ] Actualizar URLs en JS frontend

### Etapa 4: Testing
- [ ] Tests unitarios para `UIModalService`
- [ ] Tests unitarios para `DeletionService`
- [ ] Tests E2E para flujos de edición
- [ ] Verificar que no hay regresiones

---

## 📊 MÉTRICAS ESPERADAS

| Métrica | Antes | Después | Ahorro |
|---------|--------|---------|--------|
| Líneas en index.blade.php | 850+ | 250-300 | **65-70%** |
| Archivos JS de helpers | 7 | 1 | **85%** |
| Duplicación de código | 40%+ | <5% | **87%** |
| Métodos en AsesoresController | 30+ | 8-10 | **70%** |
| Endpoints duplicados | 3+ | 1 | **70%** |

---

## 🚨 IMPACTO DEL NO HACER REFACTOR

1. **Mantenibilidad:** Cada fix debe hacerse en 5-7 lugares
2. **Bugs:** Inconsistencias entre variantes (v1, v2, v3 de eliminarPedido)
3. **Testing:** Multiplicación de test cases innecesarios
4. **Performance:** Carga de JS innecesario (helpers duplicados)
5. **Escalabilidad:** Agregar nuevas vistas es exponencialmente más complejo

---

## 📞 PRÓXIMOS PASOS

1. **Validar prioritización** con el team
2. **Iniciar Fase 1** (Centralizar UI Modal Service)
3. **Testing incremental** después de cada fase
4. **Documentar patrones** para futuro mantenimiento

