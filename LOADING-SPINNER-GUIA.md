# 🎯 Loading Spinner Profesional - Guía de Uso

## 📋 Descripción

Loading spinner profesional con paleta de colores azul y blanco que dice **"Espere, es posible"**. Incluye:

- ✅ Spinner animado con gradiente azul
- ✅ Puntos decorativos pulsantes
- ✅ Barra de progreso animada
- ✅ Texto personalizable
- ✅ Overlay semi-transparente con blur
- ✅ Animaciones suaves
- ✅ Responsive (desktop, tablet, móvil)
- ✅ Tema oscuro soportado
- ✅ Funciones JavaScript para controlar

## 📁 Archivos Creados

```
resources/views/components/loading-spinner.blade.php
public/css/components/loading-spinner.css
```

## 🚀 Instalación

### 1. Incluir en el Layout Principal

Agrega el componente en `resources/views/layouts/app.blade.php`:

```blade
<!-- Antes del cierre </body> -->
<x-loading-spinner />
```

### 2. Incluir CSS (Opcional, si no está en el componente)

En la sección `<head>`:

```blade
<link rel="stylesheet" href="{{ asset('css/components/loading-spinner.css') }}">
```

## 💻 Uso en JavaScript

### Mostrar el Spinner

```javascript
// Mostrar con mensaje por defecto
showLoadingSpinner();

// Mostrar con mensaje personalizado
showLoadingSpinner('Cargando datos...');
showLoadingSpinner('Guardando cambios...');
showLoadingSpinner('Procesando solicitud...');
```

### Ocultar el Spinner

```javascript
hideLoadingSpinner();
```

### Cambiar Mensaje

```javascript
setLoadingMessage('Nuevo mensaje aquí');
```

## 📝 Ejemplos Prácticos

### Ejemplo 1: Formulario con Envío

```html
<form id="miFormulario">
    <input type="text" name="nombre" required>
    <button type="submit">Enviar</button>
</form>

<script>
    document.getElementById('miFormulario').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Mostrar spinner
        showLoadingSpinner('Guardando información...');
        
        // Simular envío
        fetch('/api/guardar', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.json())
        .then(data => {
            hideLoadingSpinner();
            alert('¡Guardado exitosamente!');
        })
        .catch(error => {
            hideLoadingSpinner();
            alert('Error: ' + error.message);
        });
    });
</script>
```

### Ejemplo 2: Carga de Datos

```javascript
async function cargarDatos() {
    showLoadingSpinner('Cargando datos...');
    
    try {
        const response = await fetch('/api/datos');
        const data = await response.json();
        
        // Procesar datos
        console.log(data);
        
        hideLoadingSpinner();
    } catch (error) {
        hideLoadingSpinner();
        console.error('Error:', error);
    }
}

// Llamar función
cargarDatos();
```

### Ejemplo 3: Operación Larga

```javascript
async function procesarArchivo() {
    showLoadingSpinner('Procesando archivo...');
    
    // Simular operación de 3 segundos
    await new Promise(resolve => setTimeout(resolve, 3000));
    
    hideLoadingSpinner();
    alert('¡Archivo procesado!');
}
```

### Ejemplo 4: Con AJAX

```javascript
$.ajax({
    url: '/api/actualizar',
    type: 'POST',
    data: { id: 123, nombre: 'Nuevo nombre' },
    beforeSend: function() {
        showLoadingSpinner('Actualizando...');
    },
    success: function(data) {
        hideLoadingSpinner();
        alert('¡Actualizado!');
    },
    error: function() {
        hideLoadingSpinner();
        alert('Error en la solicitud');
    }
});
```

### Ejemplo 5: Con Axios

```javascript
axios.post('/api/guardar', {
    nombre: 'Juan',
    email: 'juan@example.com'
})
.then(response => {
    hideLoadingSpinner();
    console.log('Guardado:', response.data);
})
.catch(error => {
    hideLoadingSpinner();
    console.error('Error:', error);
})
.finally(() => {
    // Siempre se ejecuta
});

// Mostrar antes de la solicitud
showLoadingSpinner('Guardando datos...');
```

## 🎨 Personalización

### Cambiar Colores

Edita las variables CSS en `loading-spinner.css`:

```css
:root {
    --spinner-primary: #3498db;      /* Azul principal */
    --spinner-dark: #2c3e50;         /* Azul oscuro */
    --spinner-light: #ecf0f1;        /* Gris claro */
    --spinner-white: #ffffff;        /* Blanco */
    --spinner-overlay: rgba(44, 62, 80, 0.95); /* Overlay */
}
```

### Cambiar Tamaño del Spinner

En `loading-spinner.css`, línea 57:

```css
.spinner-wrapper {
    width: 120px;  /* Cambiar aquí */
    height: 120px; /* Cambiar aquí */
}
```

### Cambiar Velocidad de Animación

En `loading-spinner.css`:

```css
/* Spinner (línea 91) */
animation: spin 2s linear infinite; /* Cambiar 2s */

/* Puntos (línea 113) */
animation: pulse 1.5s ease-in-out infinite; /* Cambiar 1.5s */

/* Barra (línea 165) */
animation: progress 2s ease-in-out infinite; /* Cambiar 2s */
```

## 🔧 Integración con Eventos

### Mostrar al Cargar Página

```javascript
document.addEventListener('DOMContentLoaded', function() {
    showLoadingSpinner('Inicializando...');
    
    // Simular carga
    setTimeout(() => {
        hideLoadingSpinner();
    }, 2000);
});
```

### Mostrar en Clics de Botones

```javascript
document.querySelectorAll('[data-loading]').forEach(button => {
    button.addEventListener('click', function() {
        const message = this.dataset.loading || 'Procesando...';
        showLoadingSpinner(message);
        
        // El spinner se ocultará cuando la solicitud termine
    });
});
```

HTML:
```html
<button data-loading="Guardando cambios...">Guardar</button>
<button data-loading="Eliminando...">Eliminar</button>
```

## 📱 Responsive

El spinner se adapta automáticamente a:

- **Desktop**: 120px spinner
- **Tablet (768px)**: 100px spinner
- **Móvil (480px)**: 80px spinner

## 🌙 Tema Oscuro

Automáticamente se adapta al tema oscuro del navegador:

```css
@media (prefers-color-scheme: dark) {
    .loading-spinner-overlay {
        --spinner-overlay: rgba(20, 30, 40, 0.98);
    }
}
```

## 🎯 Casos de Uso

✅ Envío de formularios
✅ Carga de datos
✅ Operaciones de base de datos
✅ Procesamiento de archivos
✅ Búsquedas
✅ Actualizaciones en tiempo real
✅ Descarga de reportes
✅ Cualquier operación asincrónica

## ⚡ Performance

- Usa CSS animations (GPU acelerado)
- Sin JavaScript pesado
- Optimizado para móviles
- Tamaño: < 5KB

## 🐛 Troubleshooting

### El spinner no aparece

1. Verifica que el componente esté en el layout
2. Verifica que `loadingSpinner` tenga el ID correcto
3. Abre la consola (F12) y ejecuta: `showLoadingSpinner()`

### El spinner no desaparece

```javascript
// Fuerza ocultamiento
hideLoadingSpinner();

// O agrega clase manualmente
document.getElementById('loadingSpinner').classList.add('hidden');
```

### Los estilos no se aplican

1. Verifica que el CSS esté cargado
2. Verifica que no haya conflictos de CSS
3. Abre DevTools (F12) y busca `.loading-spinner-overlay`

## 📚 Referencia Rápida

| Función | Descripción |
|---------|-------------|
| `showLoadingSpinner()` | Muestra spinner con mensaje por defecto |
| `showLoadingSpinner(msg)` | Muestra spinner con mensaje personalizado |
| `hideLoadingSpinner()` | Oculta el spinner |
| `setLoadingMessage(msg)` | Cambia el mensaje sin ocultar |

## 🎓 Ejemplo Completo

```blade
<!-- En tu vista -->
<button id="btnGuardar" class="btn btn-primary">Guardar</button>

<script>
    document.getElementById('btnGuardar').addEventListener('click', async function() {
        showLoadingSpinner('Guardando datos...');
        
        try {
            const response = await fetch('/api/guardar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nombre: 'Juan',
                    email: 'juan@example.com'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                hideLoadingSpinner();
                alert('¡Guardado exitosamente!');
            } else {
                hideLoadingSpinner();
                alert('Error: ' + data.message);
            }
        } catch (error) {
            hideLoadingSpinner();
            alert('Error: ' + error.message);
        }
    });
</script>
```

## ✅ Checklist de Implementación

- [ ] Incluir componente en layout
- [ ] Incluir CSS (si es necesario)
- [ ] Probar `showLoadingSpinner()`
- [ ] Probar `hideLoadingSpinner()`
- [ ] Integrar en formularios
- [ ] Integrar en AJAX/Fetch
- [ ] Probar en móvil
- [ ] Probar en tema oscuro

## 📞 Soporte

Si tienes problemas:

1. Abre la consola del navegador (F12)
2. Verifica que no haya errores JavaScript
3. Verifica que el elemento `#loadingSpinner` exista
4. Verifica que las funciones globales estén disponibles

---

**Versión**: 1.0
**Última actualización**: Diciembre 2025
**Paleta**: Azul (#3498db) y Blanco
