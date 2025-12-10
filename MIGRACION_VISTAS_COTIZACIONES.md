# 🎨 MIGRACIÓN DE VISTAS - COTIZACIONES DDD

## 📋 RESUMEN

Las vistas del frontend siguen funcionando con las rutas antiguas gracias a los **aliases de rutas**. Sin embargo, se recomienda actualizar gradualmente para usar las nuevas rutas.

---

## 🔄 RUTAS ANTIGUAS vs NUEVAS

| Acción | Ruta Antigua | Ruta Nueva | Método |
|--------|-------------|-----------|--------|
| Listar | GET `/asesores/cotizaciones` | GET `/asesores/cotizaciones` | `index()` |
| Crear | POST `/asesores/cotizaciones/guardar` | POST `/asesores/cotizaciones` | `store()` |
| Ver | GET `/asesores/cotizaciones/{id}` | GET `/asesores/cotizaciones/{id}` | `show()` |
| Editar | GET `/asesores/cotizaciones/{id}/editar-borrador` | GET `/asesores/cotizaciones/{id}` | `show()` |
| Eliminar | DELETE `/asesores/cotizaciones/{id}` | DELETE `/asesores/cotizaciones/{id}` | `destroy()` |
| Cambiar Estado | PATCH `/asesores/cotizaciones/{id}/estado/{estado}` | PATCH `/asesores/cotizaciones/{id}/estado/{estado}` | `cambiarEstado()` |
| Aceptar | POST `/asesores/cotizaciones/{id}/aceptar` | POST `/asesores/cotizaciones/{id}/aceptar` | `aceptar()` |

---

## ✅ ESTADO ACTUAL

### Rutas que YA funcionan

✅ `route('asesores.cotizaciones.index')`
✅ `route('asesores.cotizaciones.show', $id)`
✅ `route('asesores.cotizaciones.guardar')`
✅ `route('asesores.cotizaciones.destroy', $id)`
✅ `route('asesores.cotizaciones.edit-borrador', $id)`
✅ `route('asesores.cotizaciones.filtros.valores')`

### Respuestas JSON

Todas las respuestas siguen el formato:

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

## 🎯 CAMBIOS RECOMENDADOS EN VISTAS

### 1. Formulario de Creación

**Antes:**
```html
<form action="{{ route('asesores.cotizaciones.guardar') }}" method="POST">
```

**Después (opcional):**
```html
<form action="{{ route('asesores.cotizaciones.store') }}" method="POST">
```

**Nota:** Ambas funcionan actualmente.

### 2. Botón Eliminar

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
```

### 3. Cambiar Estado

**Nuevo método disponible:**
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
        console.log('Estado cambiado:', data.data.estado);
    }
})
```

### 4. Aceptar Cotización

**Nuevo método disponible:**
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
        console.log('Cotización aceptada');
    }
})
```

---

## 📝 CHECKLIST DE MIGRACIÓN

### Fase 1: Verificación (Actual)
- [x] Rutas antiguas funcionan con aliases
- [x] Respuestas JSON correctas
- [x] Autorización funcionando
- [x] Transiciones de estado validadas

### Fase 2: Actualización Gradual (Próxima)
- [ ] Actualizar formularios a nuevas rutas
- [ ] Actualizar llamadas AJAX
- [ ] Actualizar validaciones frontend
- [ ] Actualizar mensajes de error

### Fase 3: Limpieza (Futura)
- [ ] Remover aliases de rutas
- [ ] Remover código legacy
- [ ] Actualizar documentación

---

## 🚨 ERRORES COMUNES

### Error: "CSRF token mismatch"

**Solución:** Agregar header CSRF en AJAX:
```javascript
headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```

### Error: "No se puede transicionar de BORRADOR a ACEPTADA"

**Solución:** Seguir el flujo correcto de estados:
```
BORRADOR → ENVIADA_CONTADOR → APROBADA_CONTADOR 
→ ENVIADA_APROBADOR → APROBADA_APROBADOR → ACEPTADA
```

### Error: "No tienes permiso para acceder a esta cotización"

**Solución:** Verificar que el usuario es propietario de la cotización.

---

## 📚 EJEMPLOS COMPLETOS

### Crear Cotización (Vanilla JS)

```javascript
async function crearCotizacion() {
    const response = await fetch('/asesores/cotizaciones', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            tipo: 'P',
            cliente: 'Acme Corp',
            asesora: 'María García',
            es_borrador: true,
            productos: []
        })
    });
    
    const data = await response.json();
    if (data.success) {
        console.log('Cotización creada:', data.data.id);
    }
}
```

### Listar Cotizaciones (Fetch API)

```javascript
async function listarCotizaciones() {
    const response = await fetch('/asesores/cotizaciones');
    const data = await response.json();
    
    if (data.success) {
        data.data.forEach(cot => {
            console.log(`${cot.numero_cotizacion} - ${cot.cliente}`);
        });
    }
}
```

### Cambiar Estado (Axios)

```javascript
axios.patch(`/asesores/cotizaciones/${id}/estado/ENVIADA_CONTADOR`)
    .then(response => {
        if (response.data.success) {
            console.log('Estado:', response.data.data.estado);
        }
    })
    .catch(error => {
        console.error('Error:', error.response.data.message);
    });
```

---

## ✅ VERIFICACIÓN

Para verificar que todo funciona correctamente:

```bash
# Ejecutar tests E2E
php artisan test tests/Feature/Cotizacion/CotizacionE2ETest.php

# Ver logs
tail -f storage/logs/laravel.log
```

---

**Última actualización:** 10 de Diciembre de 2025
**Estado:** ✅ Listo para migración gradual
