# ⏱️ Auto Loading Spinner - Guía Completa

## 📋 Descripción

El **Auto Loading Spinner** muestra automáticamente el spinner si una operación se demora **más de 3 segundos**. No necesitas hacer nada, funciona automáticamente con:

- ✅ Fetch API
- ✅ XMLHttpRequest (AJAX)
- ✅ jQuery AJAX
- ✅ Axios

## 🚀 Instalación

### Paso 1: Incluir el componente en el layout

En `resources/views/layouts/app.blade.php`:

```blade
<!-- Antes del cierre </body> -->
<x-loading-spinner />
```

**¡Eso es todo!** El auto-spinner ya está incluido automáticamente.

## 🎯 Cómo Funciona

1. **Operación comienza** → Se inicia un temporizador de 3 segundos
2. **Si tarda < 3 segundos** → El spinner NUNCA aparece (rápido)
3. **Si tarda > 3 segundos** → El spinner aparece automáticamente
4. **Operación termina** → El spinner desaparece

## 📊 Diagrama de Flujo

```
Inicio de Operación
        ↓
    [Temporizador: 3 segundos]
        ↓
    ¿Operación terminó?
    /                \
   SÍ (< 3s)         NO (> 3s)
   ↓                  ↓
Sin spinner      Mostrar spinner
   ↓                  ↓
Fin                Fin
```

## 💻 Ejemplos

### Ejemplo 1: Fetch API (Automático)

```javascript
// No necesitas hacer nada especial, funciona automáticamente
fetch('/api/datos')
    .then(r => r.json())
    .then(data => {
        console.log(data);
        // Si tardó > 3s, el spinner desaparece automáticamente
    })
    .catch(e => console.error(e));
```

### Ejemplo 2: AJAX jQuery (Automático)

```javascript
// jQuery AJAX automáticamente muestra/oculta el spinner
$.ajax({
    url: '/api/actualizar',
    type: 'POST',
    data: { id: 123 },
    success: function(data) {
        console.log('Actualizado');
        // Si tardó > 3s, el spinner desaparece automáticamente
    }
});
```

### Ejemplo 3: Axios (Automático)

```javascript
// Axios automáticamente muestra/oculta el spinner
axios.post('/api/guardar', {
    nombre: 'Juan',
    email: 'juan@example.com'
})
.then(response => {
    console.log('Guardado');
    // Si tardó > 3s, el spinner desaparece automáticamente
})
.catch(error => console.error(error));
```

### Ejemplo 4: XMLHttpRequest (Automático)

```javascript
// XMLHttpRequest automáticamente muestra/oculta el spinner
const xhr = new XMLHttpRequest();
xhr.open('POST', '/api/guardar');
xhr.onload = function() {
    console.log('Completado');
    // Si tardó > 3s, el spinner desaparece automáticamente
};
xhr.send(JSON.stringify({ nombre: 'Juan' }));
```

## 🔧 Configuración

### Cambiar el Delay (tiempo de espera)

Por defecto es 3 segundos. Para cambiar:

```javascript
// Cambiar a 2 segundos
setSpinnerConfig({ delay: 2000 });

// Cambiar a 5 segundos
setSpinnerConfig({ delay: 5000 });
```

### Deshabilitar el Auto-Spinner

```javascript
// Deshabilitar
setSpinnerConfig({ enabled: false });

// Habilitar de nuevo
setSpinnerConfig({ enabled: true });
```

### Ver Configuración Actual

```javascript
const config = getSpinnerConfig();
console.log(config);
// { DELAY: 3000, ENABLED: true }
```

## 📝 Casos de Uso

### Caso 1: Búsqueda Lenta

```javascript
document.getElementById('btnBuscar').addEventListener('click', async () => {
    const query = document.getElementById('inputBusqueda').value;
    
    // Si la búsqueda tarda > 3s, aparece el spinner automáticamente
    const response = await fetch(`/api/buscar?q=${query}`);
    const resultados = await response.json();
    
    mostrarResultados(resultados);
});
```

### Caso 2: Carga de Datos Pesados

```javascript
async function cargarDatos() {
    // Si tarda > 3s, aparece el spinner automáticamente
    const response = await fetch('/api/datos-pesados');
    const data = await response.json();
    
    renderizarTabla(data);
}
```

### Caso 3: Envío de Formulario

```javascript
document.getElementById('miFormulario').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    // Si el envío tarda > 3s, aparece el spinner automáticamente
    const response = await fetch('/api/guardar', {
        method: 'POST',
        body: new FormData(this)
    });
    
    const resultado = await response.json();
    alert('¡Guardado!');
});
```

### Caso 4: Descarga de Reportes

```javascript
async function descargarReporte() {
    // Si tarda > 3s, aparece el spinner automáticamente
    const response = await fetch('/api/reportes/generar');
    const blob = await response.blob();
    
    // Descargar archivo
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'reporte.pdf';
    a.click();
}
```

## 🎯 Ventajas

✅ **Automático**: No necesitas escribir código
✅ **Inteligente**: Solo muestra si tarda > 3 segundos
✅ **Compatible**: Funciona con Fetch, AJAX, jQuery, Axios
✅ **Configurable**: Puedes cambiar el delay
✅ **No invasivo**: No interfiere con tu código
✅ **Profesional**: Mejora la experiencia del usuario

## ⚡ Performance

- **Overhead mínimo**: < 1KB de código
- **Sin impacto en operaciones rápidas**: Si tarda < 3s, no hay overhead
- **Eficiente**: Usa temporizadores nativos de JavaScript
- **Optimizado**: Cancela temporizadores cuando es necesario

## 🐛 Troubleshooting

### El spinner no aparece

1. Verifica que el componente esté en el layout
2. Abre la consola (F12)
3. Verifica que veas: `✅ Auto Loading Spinner inicializado`
4. Prueba una operación que tarde > 3 segundos

### El spinner aparece pero no desaparece

1. Verifica que la operación termine correctamente
2. Abre la consola (F12)
3. Busca errores de JavaScript
4. Prueba manualmente: `hideLoadingSpinner()`

### Quiero mostrar el spinner manualmente

Puedes hacerlo en cualquier momento:

```javascript
// Mostrar manualmente
showLoadingSpinner('Procesando...');

// Ocultar manualmente
hideLoadingSpinner();
```

## 📚 API Completa

| Función | Descripción | Ejemplo |
|---------|-------------|---------|
| `showLoadingSpinner(msg)` | Mostrar spinner manualmente | `showLoadingSpinner('Cargando...')` |
| `hideLoadingSpinner()` | Ocultar spinner manualmente | `hideLoadingSpinner()` |
| `setLoadingMessage(msg)` | Cambiar mensaje | `setLoadingMessage('Nuevo...')` |
| `startSpinnerTimer(msg)` | Iniciar temporizador | `startSpinnerTimer('Esperando...')` |
| `stopSpinnerTimer()` | Detener temporizador | `stopSpinnerTimer()` |
| `setSpinnerConfig(opts)` | Configurar | `setSpinnerConfig({ delay: 2000 })` |
| `getSpinnerConfig()` | Ver configuración | `getSpinnerConfig()` |

## 🔍 Monitoreo

Para ver qué está pasando, abre la consola (F12):

```javascript
// Ver logs
console.log('✅ Auto Loading Spinner inicializado (delay: 3000ms)');

// Cuando inicia una operación
console.log('Temporizador iniciado...');

// Cuando termina
console.log('Temporizador detenido');
```

## 🎓 Ejemplo Completo

```blade
@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <h1>Búsqueda de Datos</h1>
    
    <form id="formularioBusqueda">
        <input type="text" id="inputBusqueda" placeholder="Buscar..." required>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>
    
    <div id="resultados"></div>
</div>

<script>
    document.getElementById('formularioBusqueda').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const query = document.getElementById('inputBusqueda').value;
        
        // Si tarda > 3s, aparece el spinner automáticamente
        const response = await fetch(`/api/buscar?q=${query}`);
        const data = await response.json();
        
        // Mostrar resultados
        document.getElementById('resultados').innerHTML = 
            data.map(item => `<p>${item.nombre}</p>`).join('');
    });
</script>
@endsection
```

## ✅ Checklist

- [ ] Incluir `<x-loading-spinner />` en layout
- [ ] Probar una operación que tarde > 3 segundos
- [ ] Verificar que el spinner aparece
- [ ] Verificar que el spinner desaparece
- [ ] Probar con Fetch API
- [ ] Probar con AJAX jQuery (si usas)
- [ ] Probar con Axios (si usas)
- [ ] Cambiar delay a 2 segundos (opcional)
- [ ] Probar en móvil

## 🎉 ¡Listo!

El auto-spinner está completamente funcional. Ahora todas tus operaciones que tarden > 3 segundos mostrarán automáticamente el spinner.

**No necesitas hacer nada más, funciona automáticamente.**

---

**Versión**: 1.0
**Última actualización**: Diciembre 2025
**Estado**: ✅ Listo para Producción
