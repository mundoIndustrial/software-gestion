# 🛡️ PRESERVACIÓN DE PRENDAS EN FORMULARIO DE CREACIÓN

## Problema

Al editar un pedido en el formulario `/asesores/pedidos/crear-nuevo`:
- Usuario abre un borrador con 14+ prendas
- Edita, agrega nuevas prendas
- Si hay error, navegación o cierre de modal
- ❌ Las prendas desaparecen del formulario

## Solución: Persistencia en SessionStorage

Nuevo servicio: `GestorPrendasPersistencia`
- Guarda automáticamente el estado de prendas en sessionStorage
- Si las prendas se pierden → las restaura automáticamente
- Notifica al usuario cuando ocurre una restauración

## 🔧 Integración

### 1. Cargar el Script en la Vista

**En tu blade template** (vista de crear pedido):

```html
<script src="{{ asset('js/modulos/crear-pedido/services/gestor-prendas-persistencia.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/edicion/cargar-datos-edicion.js') }}"></script>
<!-- Otros scripts... -->
```

**Importante:** `gestor-prendas-persistencia.js` DEBE cargar ANTES de los scripts que usan el gestor.

### 2. Integrar en Puntos Clave

#### A. Cuando se cargan prendas (ya implementado)

```javascript
// En cargar-datos-edicion.js (YA HECHO)
if (window.gestorPrendasPersistencia && typeof window.gestorPrendasPersistencia.guardarEstado === 'function') {
    window.gestorPrendasPersistencia.guardarEstado(window.gestorPrendaSinCotizacion);
}
```

#### B. Cuando se agrega una prenda (necesita implementar)

```javascript
// En el gestor de prendas o en donde se agreguen prendas:
function agregarPrenda(prendaData) {
    // ... código existente para agregar ...
    
    // 🔧 NUEVO: Guardar estado
    if (window.gestorPrendasPersistencia) {
        window.gestorPrendasPersistencia.guardarEstado(window.gestorPrendaSinCotizacion);
    }
}
```

#### C. Cuando se elimina una prenda

```javascript
function eliminarPrenda(index) {
    // ... código existente ...
    
    // 🔧 NUEVO: Guardar estado
    if (window.gestorPrendasPersistencia) {
        window.gestorPrendasPersistencia.guardarEstado(window.gestorPrendaSinCotizacion);
    }
}
```

#### D. Cuando se edita una prenda

```javascript
function editarPrenda(index, prendaData) {
    // ... código existente ...
    
    // 🔧 NUEVO: Guardar estado
    if (window.gestorPrendasPersistencia) {
        window.gestorPrendasPersistencia.guardarEstado(window.gestorPrendaSinCotizacion);
    }
}
```

#### E. Antes de renderizar el gestor

```javascript
// Validar y restaurar si es necesario
function validarGestorAntes() {
    if (window.gestorPrendasPersistencia && window.gestorPrendaSinCotizacion) {
        window.gestorPrendasPersistencia.validarYRestaurarSiNecesario(window.gestorPrendaSinCotizacion);
    }
}

// Llamar en DOMReady o antes de renderizar
validarGestorAntes();
```

#### F. Cuando se guarda exitosamente

```javascript
// En el callback de éxito del guardado:
async function guardarBorradorExitoso() {
    // ... respuesta exitosa ...
    
    // 🔧 NUEVO: Limpiar backup
    if (window.gestorPrendasPersistencia) {
        window.gestorPrendasPersistencia.limpiarBackup();
    }
}
```

## 📊 Flujo Completo

```
┌──────────────────────────────┐
│ Usuario abre /crear-nuevo    │
│ y carga borrador con prendas │
└────────────┬─────────────────┘
             │
             ▼
┌──────────────────────────────────────┐
│ cargar-datos-edicion.js              │
│ Carga 14+ prendas en gestorPrendas   │
│ → gestorPrendasPersistencia.guardar()│
│ → Almacena en sessionStorage ✅      │
└────────────┬─────────────────────────┘
             │
             ▼
┌──────────────────────────────────────┐
│ Usuario edita prendas:               │
│ - Agrega nueva prenda                │
│ - Edita descripción de prenda         │
│ - Elimina imagen de prenda           │
│ → Cada cambio dispara guardarEstado()│
│ → sessionStorage actualizado ✅      │
└────────────┬─────────────────────────┘
             │
        ┌────┴────────┐
        │             │
    (Éxito)      (Error/Pérdida)
        │             │
        ▼             ▼
┌─────────────┐  ┌──────────────────────┐
│ Guardar     │  │ Gestorprendas pierde │
│ exitoso     │  │ estado (modal cierra,│
│ → Limpiar   │  │ error, navegación)   │
│ backup ✅   │  │ → validarYRestaurar()│
└─────────────┘  │ → Restaura de storage│
                 │ → Notifica usuario ✅│
                 │ → Renderiza prendas  │
                 └──────────────────────┘
```

## 🎯 Casos de Uso Cubiertos

### Caso 1: Cerrar modal sin guardar
```
1. Usuario abre prenda #5 para editar
2. Hace cambios
3. Cierra modal sin guardar
4. Si el gestor se vacía → se restauran las prendas
```

### Caso 2: Error en la red
```
1. Usuario intenta guardar borrador
2. Error 500 del servidor
3. El formulario se reinicia
4. Prendas se restauran automáticamente
```

### Caso 3: Navegar accidentalmente
```
1. Usuario hace clic accidental en otra URL
2. Cierra pestaña
3. Si vuelve a abrir el formulario
4. Prendas se restauran (mismo pedido en sessionStorage)
```

### Caso 4: Agregar múltiples prendas nuevas
```
1. Usuario abre borrador existente (14 prendas)
2. Agrega 5 prendas nuevas
3. Cada adición guarda el estado
4. Si hay error → se recuperan 19 prendas
```

## 🔍 Debugging

```javascript
// En consola del navegador:

// Ver estado actual guardado
console.log(window.gestorPrendasPersistencia.obtenerEstado());

// Simular pérdida de prendas
window.gestorPrendaSinCotizacion.prendas = [];
window.renderizarPrendasSinCotizacion();
// → Debe restaurar automáticamente

// Limpiar backup manualmente
window.gestorPrendasPersistencia.limpiarBackup();
```

## 📱 SessionStorage

**Ubicación:** sessionStorage del navegador (específico por pestaña/ventana)

**Clave:** `pedido_prendas_backup_[PEDIDO_ID]`

**Contenido:**
```json
{
  "prendas": [...],
  "timestamp": "2026-04-23T10:30:00.000Z",
  "version": 42,
  "pedidoId": 100
}
```

**Duración:** Se limpia cuando se cierra la pestaña

**Expiración:** 24 horas (si las prendas están muy antiguas, no restaura)

## ✅ Garantías

✅ **Cero pérdida de prendas** al cerrar modales  
✅ **Recuperación automática** si el gestor se vacía  
✅ **Notificación visual** cuando hay restauración  
✅ **No interfiere** con guardado normal  
✅ **Limpieza automática** al guardar exitosamente  
✅ **Por sesión** (no cruza entre pestañas)  

## 🚀 Checklist de Implementación

- [ ] Agregar script `gestor-prendas-persistencia.js` antes de otros scripts
- [ ] Asegurar que carga en la vista `/asesores/pedidos/crear-nuevo`
- [ ] Verificar que `cargar-datos-edicion.js` llamaa `guardarEstado()`
- [ ] Agregar `guardarEstado()` en función `agregarPrenda()`
- [ ] Agregar `guardarEstado()` en función `editarPrenda()`
- [ ] Agregar `guardarEstado()` en función `eliminarPrenda()`
- [ ] Agregar `validarYRestaurarSiNecesario()` antes de renderizar
- [ ] Agregar `limpiarBackup()` en guardado exitoso
- [ ] Testar: Editar prenda, cerrar modal, verificar que no se borren
- [ ] Testar: Simular pérdida, verificar restauración
- [ ] Testar: Guardar exitosamente, verificar limpieza de backup

---

**Última actualización:** 2026-04-23  
**Versión:** 1.0  
**Estado:** Lista para implementación
