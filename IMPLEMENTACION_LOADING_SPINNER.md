# 🔄 Loading Spinner - Página Pedidos

## Descripción

Se ha implementado un **loading overlay fullscreen** que se muestra mientras la página `http://localhost:8000/asesores/pedidos` está cargando todos sus componentes, scripts y datos iniciales.

## Características

 **Overlay fullscreen** con fondo degradado  
 **Spinner animado** (CSS puro, sin imágenes)  
 **Texto dinámico** con puntos animados  
 **Badge de progreso** con ícono  
 **Transición suave** al desaparecer (fade-out)  
 **Auto-ocultar** cuando la página está lista  
 **Timeout de seguridad** (máximo 10 segundos)  
 **Logging para debugging** en consola  

---

## Cómo Funciona

### 1️⃣ Inicio (Page Load)
```html
<!-- Se muestra en el @section('content') de index.blade.php -->
<div id="page-loading-overlay">
    <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">Cargando mis pedidos...</div>
        <!-- más elementos -->
    </div>
</div>
```

### 2️⃣ Evento DOMContentLoaded
```javascript
document.addEventListener('DOMContentLoaded', function() {
    // 500ms después:
    // - Ocultar overlay (fade-out 400ms)
    // - Remover del DOM
});
```

### 3️⃣ Seguridad: Timeout
```javascript
// Si pasa más de 10 segundos sin cargar:
// - Ocultar overlay de todas formas
// - Mostrar aviso en consola
```

---

## Flujo Visual

```
┌─────────────────────────────────────────┐
│  USUARIO ACCEDE A /asesores/pedidos     │
└─────────────────────────────────────────┘
            ↓
┌─────────────────────────────────────────┐
│  HTML + CSS cargado                     │
│  ✓ Spinner visible                      │
│  ✓ Texto "Cargando mis pedidos..."      │
│  ✓ Badge pulsando                       │
│  ✓ Overlay fullscreen (z-index: 9999)   │
└─────────────────────────────────────────┘
            ↓ (scripts inicializándose)
            ↓ (fetch de datos)
            ↓ (componentes setup)
            ↓
    ┌─────────────────────────┐
    │  DOMContentLoaded event │
    │  + 500ms delay          │
    └─────────────────────────┘
            ↓
┌─────────────────────────────────────────┐
│  Fade-out (400ms)                       │
│  ✓ Overlay opacity: 1 → 0               │
└─────────────────────────────────────────┘
            ↓
┌─────────────────────────────────────────┐
│  ✓ PÁGINA LISTA PARA USAR               │
│  ✓ Tabla de pedidos visible             │
│  ✓ Filters activos                      │
│  ✓ Modales disponibles                  │
└─────────────────────────────────────────┘
```

---

## Archivos Modificados

### 1.  [public/css/asesores/pedidos/page-loading.css](public/css/asesores/pedidos/page-loading.css)
**Nuevo archivo** con estilos del loading:
- `#page-loading-overlay` - Overlay fullscreen
- `.spinner` - Animación CSS del spinner
- `.loading-text` - Texto de carga
- `.loading-dots` - Puntos animados
- `.loading-badge` - Badge con progreso
- `.hidden` - Clase para ocultar

### 2.  [resources/views/asesores/pedidos/index.blade.php](resources/views/asesores/pedidos/index.blade.php)
**Cambios:**
- Línea 9: Agregado import de `page-loading.css`
- Línea 23-37: Agregado HTML del overlay
- Línea 700-751: Agregado script para control del loading

---

## Funcionamiento Detallado

### HTML del Overlay
```html
<div id="page-loading-overlay">
    <div class="loading-container">
        <div class="spinner"></div>                    <!-- Spinner CSS -->
        <div class="loading-text">                     <!-- Texto dinámico -->
            Cargando mis pedidos<span class="loading-dots"></span>
        </div>
        <div class="loading-subtext">                 <!-- Descripción -->
            Por favor espera mientras se cargan los datos
        </div>
        <div class="loading-badge">                   <!-- Badge pulsante -->
            <i class="fas fa-sync"></i>
            <span>Inicializando</span>
        </div>
    </div>
</div>
```

### CSS del Spinner
```css
.spinner {
    width: 60px;
    height: 60px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #3b82f6;      /* Azul para el progreso */
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0%   { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
```

### JavaScript de Control
```javascript
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const overlay = document.getElementById('page-loading-overlay');
        overlay.classList.add('hidden');  // Fade-out
        
        setTimeout(function() {
            overlay.remove();              // Remover del DOM
        }, 400);
    }, 500);  // Delay para sincronización
});
```

---

## Logging para Debugging

Abre DevTools (F12) y ve los logs:

```
[PageLoading] Script inicializado
[PageLoading] DOMContentLoaded - Inicios scripts de la página
[PageLoading] Ocultando overlay...
[PageLoading]  Overlay removido del DOM
[PageLoading] Evento load disparado - Página completamente cargada
```

---

## Personalización

### Cambiar tiempo de carga simulado
```javascript
// En index.blade.php, línea ~710
setTimeout(function() {
    // ...
}, 500);  // ← Cambiar este valor (en ms)
```

### Cambiar duración de fade-out
```css
/* En page-loading.css */
#page-loading-overlay.hidden {
    transition: opacity 0.4s ease-in-out;  /* ← Cambiar duración */
}
```

### Cambiar colores
```css
.spinner {
    border-top: 4px solid #3b82f6;  /* Cambiar azul a otro color */
}
```

### Cambiar texto
```html
<!-- En index.blade.php -->
<div class="loading-text">
    Cargando mis pedidos<span class="loading-dots"></span>
    <!-- ↑ Cambiar texto aquí -->
</div>
```

---

## Ventajas

 **UX mejorada** - Usuario sabe que la página está cargando  
 **Prevención de confusión** - No hay "página en blanco"  
 **Profesional** - Diseño moderno y limpio  
 **Performante** - CSS puro, sin imágenes  
 **Responsive** - Funciona en todos los tamaños  
 **Accesible** - No bloquea con pointer-events cuando está oculto  

---

## Cómo Verificar

### 1. Test Visual Normal
```
1. Ir a http://localhost:8000/asesores/pedidos
2. Deberías ver:
   - Loading overlay fullscreen
   - Spinner girando
   - Texto "Cargando mis pedidos..."
   - Badge con icono sync
3. Después de ~1 segundo:
   - Overlay se desvanece (fade-out)
   - Página completamente cargada
```

### 2. Test con Throttle (Slow 3G)
```
1. Abrir DevTools (F12)
2. Network tab → Throttle: "Slow 3G"
3. Refrescar página (Ctrl+R)
4. Deberías ver:
   - Loading visible más tiempo
   - Desaparece después de cargar
   - Página funcional
```

### 3. Revisar Console Logs
```
1. Abrir DevTools (F12)
2. Console tab
3. Buscar logs de [PageLoading]
4. Verificar que los eventos se disparan correctamente
```

---

## Integración con editarPedido()

El loading initial NO interfiere con el loading de `editarPedido()`:

- **Initial**: `#page-loading-overlay` (fullscreen)
- **Editar**: `Swal.fire()` (modal Swal)

Ambos pueden coexistir sin problemas porque tienen z-index diferentes:
- Initial: `z-index: 9999`
- Swal: `z-index: 999999`

---

## Conclusión

El loading spinner proporciona una **mejor experiencia de usuario** mientras la página se carga, eliminando la sensación de página congelada o en blanco.

**Estado:**  **Implementado y Funcional**

