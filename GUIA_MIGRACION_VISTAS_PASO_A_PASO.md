# 🎨 GUÍA DE MIGRACIÓN DE VISTAS - PASO A PASO

## 📋 RESUMEN

Las vistas actuales funcionan con los **aliases de rutas**, pero se recomienda actualizar gradualmente para usar las nuevas rutas y respuestas JSON del nuevo sistema.

---

## 🔄 CAMBIOS PRINCIPALES

### 1. Endpoints Antiguos vs Nuevos

| Función | Endpoint Antiguo | Endpoint Nuevo | Método |
|---------|-----------------|-----------------|--------|
| Listar | GET `/cotizaciones` | GET `/asesores/cotizaciones` | `index()` |
| Crear | POST `/asesores/cotizaciones/guardar` | POST `/asesores/cotizaciones` | `store()` |
| Ver | GET `/cotizaciones/{id}/detalle` | GET `/asesores/cotizaciones/{id}` | `show()` |
| Cambiar Estado | PATCH `/asesores/cotizaciones/{id}/estado/{estado}` | PATCH `/asesores/cotizaciones/{id}/estado/{estado}` | `cambiarEstado()` |
| Aceptar | POST `/asesores/cotizaciones/{id}/aceptar` | POST `/asesores/cotizaciones/{id}/aceptar` | `aceptar()` |
| Eliminar | DELETE `/asesores/cotizaciones/{id}` | DELETE `/asesores/cotizaciones/{id}` | `destroy()` |

---

## 📝 CAMBIOS EN VISTAS

### Vista: `cotizaciones/index.blade.php`

#### Antes (Línea 68)
```javascript
fetch(`/cotizaciones/${cotizacionId}/detalle`)
```

#### Después
```javascript
fetch(`/asesores/cotizaciones/${cotizacionId}`)
```

---

### Vista: Formulario de Creación

#### Antes
```html
<form action="{{ route('asesores.cotizaciones.guardar') }}" method="POST">
```

#### Después (Opcional - ambas funcionan)
```html
<form action="{{ route('asesores.cotizaciones.store') }}" method="POST">
```

---

### Vista: Botón Eliminar

#### Antes
```javascript
fetch(`/asesores/cotizaciones/${id}`, {
    method: 'DELETE'
})
```

#### Después (Agregar CSRF)
```javascript
fetch(`/asesores/cotizaciones/${id}`, {
    method: 'DELETE',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
```

---

## 🎯 CAMBIOS EN RESPUESTAS JSON

### Respuesta Antigua
```json
{
    "success": true,
    "data": {
        "id": 1,
        "cliente": "Acme Corp",
        "prendas_cotizaciones": [...]
    }
}
```

### Respuesta Nueva
```json
{
    "success": true,
    "message": "Operación exitosa",
    "data": {
        "id": 1,
        "numero_cotizacion": "COT-00001",
        "estado": "BORRADOR",
        "cliente": "Acme Corp",
        "asesora": "María García",
        "es_borrador": true,
        "fecha_inicio": "2025-12-10 11:30:00",
        "fecha_envio": null,
        "prendas": [],
        "logo": null
    }
}
```

---

## 🔧 CAMBIOS EN JAVASCRIPT

### Actualizar Fetch Calls

#### Listar Cotizaciones

**Antes:**
```javascript
fetch('/cotizaciones')
    .then(r => r.json())
    .then(data => {
        data.forEach(cot => {
            console.log(cot.cliente);
        });
    });
```

**Después:**
```javascript
fetch('/asesores/cotizaciones')
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            data.data.forEach(cot => {
                console.log(cot.cliente);
            });
        }
    });
```

#### Obtener Cotización

**Antes:**
```javascript
fetch(`/cotizaciones/${id}/detalle`)
    .then(r => r.json())
    .then(data => {
        console.log(data.data.cliente);
    });
```

**Después:**
```javascript
fetch(`/asesores/cotizaciones/${id}`)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            console.log(data.data.cliente);
        }
    });
```

#### Cambiar Estado

**Antes:**
```javascript
fetch(`/asesores/cotizaciones/${id}/estado/ENVIADA_CONTADOR`, {
    method: 'PATCH'
})
```

**Después:**
```javascript
fetch(`/asesores/cotizaciones/${id}/estado/ENVIADA_CONTADOR`, {
    method: 'PATCH',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
.then(r => r.json())
.then(data => {
    if (data.success) {
        console.log('Estado:', data.data.estado);
    } else {
        console.error('Error:', data.message);
    }
});
```

#### Aceptar Cotización

**Antes:**
```javascript
fetch(`/asesores/cotizaciones/${id}/aceptar`, {
    method: 'POST'
})
```

**Después:**
```javascript
fetch(`/asesores/cotizaciones/${id}/aceptar`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
.then(r => r.json())
.then(data => {
    if (data.success) {
        console.log('Aceptada');
    } else {
        console.error('Error:', data.message);
    }
});
```

#### Eliminar Cotización

**Antes:**
```javascript
fetch(`/asesores/cotizaciones/${id}`, {
    method: 'DELETE'
})
```

**Después:**
```javascript
fetch(`/asesores/cotizaciones/${id}`, {
    method: 'DELETE',
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
.then(r => r.json())
.then(data => {
    if (data.success) {
        console.log('Eliminada');
    } else {
        console.error('Error:', data.message);
    }
});
```

---

## 📋 CHECKLIST DE MIGRACIÓN

### Paso 1: Actualizar Endpoints
- [ ] Cambiar `/cotizaciones/{id}/detalle` a `/asesores/cotizaciones/{id}`
- [ ] Cambiar `/cotizaciones/guardar` a `/asesores/cotizaciones`
- [ ] Verificar que otros endpoints ya están correctos

### Paso 2: Actualizar Respuestas JSON
- [ ] Agregar verificación de `data.success`
- [ ] Acceder a datos en `data.data` en lugar de `data`
- [ ] Agregar manejo de `data.message` para errores

### Paso 3: Agregar Headers CSRF
- [ ] Agregar `X-CSRF-TOKEN` en DELETE
- [ ] Agregar `X-CSRF-TOKEN` en PATCH
- [ ] Agregar `X-CSRF-TOKEN` en POST (si no usa form)

### Paso 4: Actualizar Manejo de Errores
- [ ] Verificar `data.success` antes de procesar
- [ ] Mostrar `data.message` en caso de error
- [ ] Agregar try-catch para errores de red

### Paso 5: Probar en Staging
- [ ] Crear cotización
- [ ] Listar cotizaciones
- [ ] Ver cotización
- [ ] Cambiar estado
- [ ] Aceptar cotización
- [ ] Eliminar cotización

---

## 🔍 ARCHIVOS A ACTUALIZAR

### Vistas Blade
```
resources/views/cotizaciones/index.blade.php
resources/views/cotizaciones/pendientes.blade.php
resources/views/cotizaciones/por-corregir.blade.php
resources/views/cotizaciones/partials/cotizacion-modal.blade.php
```

### JavaScript
```
public/js/asesores/cotizaciones/cotizaciones.js
public/js/asesores/cotizaciones/guardado.js
public/js/asesores/cotizaciones/tallas.js
```

---

## 💡 EJEMPLOS COMPLETOS

### Ejemplo 1: Listar y Mostrar Cotizaciones

**Antes:**
```javascript
async function loadCotizaciones() {
    const response = await fetch('/cotizaciones');
    const cotizaciones = await response.json();
    
    cotizaciones.forEach(cot => {
        console.log(`${cot.numero_cotizacion} - ${cot.cliente}`);
    });
}
```

**Después:**
```javascript
async function loadCotizaciones() {
    const response = await fetch('/asesores/cotizaciones');
    const result = await response.json();
    
    if (result.success) {
        result.data.forEach(cot => {
            console.log(`${cot.numero_cotizacion} - ${cot.cliente}`);
        });
    } else {
        console.error('Error:', result.message);
    }
}
```

### Ejemplo 2: Crear Cotización

**Antes:**
```javascript
async function crearCotizacion(datos) {
    const response = await fetch('/asesores/cotizaciones/guardar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(datos)
    });
    
    return await response.json();
}
```

**Después:**
```javascript
async function crearCotizacion(datos) {
    const response = await fetch('/asesores/cotizaciones', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(datos)
    });
    
    const result = await response.json();
    
    if (result.success) {
        console.log('Cotización creada:', result.data.id);
        return result.data;
    } else {
        console.error('Error:', result.message);
        throw new Error(result.message);
    }
}
```

### Ejemplo 3: Cambiar Estado con Validación

**Antes:**
```javascript
async function cambiarEstado(id, nuevoEstado) {
    const response = await fetch(`/asesores/cotizaciones/${id}/estado/${nuevoEstado}`, {
        method: 'PATCH'
    });
    
    return await response.json();
}
```

**Después:**
```javascript
async function cambiarEstado(id, nuevoEstado) {
    try {
        const response = await fetch(`/asesores/cotizaciones/${id}/estado/${nuevoEstado}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('Estado actualizado a:', result.data.estado);
            return result.data;
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error al cambiar estado:', error.message);
        alert(`Error: ${error.message}`);
        throw error;
    }
}
```

---

## ✅ VERIFICACIÓN

### Después de actualizar, verificar:

```bash
# 1. Abrir consola del navegador (F12)
# 2. Ejecutar en consola:

// Listar
fetch('/asesores/cotizaciones')
    .then(r => r.json())
    .then(d => console.log(d))

// Ver
fetch('/asesores/cotizaciones/1')
    .then(r => r.json())
    .then(d => console.log(d))
```

---

## 🚀 PLAN DE MIGRACIÓN RECOMENDADO

### Fase 1: Preparación (Día 1)
- [ ] Revisar todas las vistas
- [ ] Identificar endpoints a cambiar
- [ ] Crear rama de desarrollo

### Fase 2: Actualización (Días 2-3)
- [ ] Actualizar endpoints
- [ ] Actualizar respuestas JSON
- [ ] Agregar headers CSRF
- [ ] Actualizar manejo de errores

### Fase 3: Testing (Día 4)
- [ ] Probar en staging
- [ ] Verificar todos los flujos
- [ ] Revisar logs

### Fase 4: Producción (Día 5)
- [ ] Deploy a producción
- [ ] Monitorear logs
- [ ] Estar disponible para soporte

---

**Última actualización:** 10 de Diciembre de 2025
**Estado:** ✅ Listo para migración
