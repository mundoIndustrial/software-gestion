# 📱 GUÍA FRONTEND - COTIZACIONES DDD

**Fecha:** 10 de Diciembre de 2025
**Versión:** 1.0
**Estado:** ✅ LISTO PARA USAR

---

## 🎯 OBJETIVO

Guía completa para que el frontend use correctamente las nuevas rutas y arquitectura DDD del módulo de cotizaciones.

---

## 🔗 RUTAS DISPONIBLES

### Cotizaciones Tipo PRENDA

```php
// Crear nueva cotización
route('cotizaciones-prenda.create')     // GET /cotizaciones-prenda/crear

// Guardar cotización
route('cotizaciones-prenda.store')      // POST /cotizaciones-prenda

// Listar mis cotizaciones
route('cotizaciones-prenda.lista')      // GET /cotizaciones-prenda

// Editar cotización
route('cotizaciones-prenda.edit', $id)  // GET /cotizaciones-prenda/{id}/editar

// Actualizar cotización
route('cotizaciones-prenda.update', $id) // PUT /cotizaciones-prenda/{id}

// Enviar cotización
route('cotizaciones-prenda.enviar', $id) // POST /cotizaciones-prenda/{id}/enviar

// Eliminar cotización
route('cotizaciones-prenda.destroy', $id) // DELETE /cotizaciones-prenda/{id}
```

### Cotizaciones Tipo BORDADO/LOGO

```php
// Crear nueva cotización
route('cotizaciones-bordado.create')     // GET /cotizaciones-bordado/crear

// Guardar cotización
route('cotizaciones-bordado.store')      // POST /cotizaciones-bordado

// Listar mis cotizaciones
route('cotizaciones-bordado.lista')      // GET /cotizaciones-bordado

// Editar cotización
route('cotizaciones-bordado.edit', $id)  // GET /cotizaciones-bordado/{id}/editar

// Actualizar cotización
route('cotizaciones-bordado.update', $id) // PUT /cotizaciones-bordado/{id}

// Enviar cotización
route('cotizaciones-bordado.enviar', $id) // POST /cotizaciones-bordado/{id}/enviar

// Eliminar cotización
route('cotizaciones-bordado.destroy', $id) // DELETE /cotizaciones-bordado/{id}
```

---

## 📝 EJEMPLOS DE USO EN BLADE

### 1. Botón para Crear Cotización Prenda

```blade
<a href="{{ route('cotizaciones-prenda.create') }}" class="btn btn-primary">
    <i class="fas fa-plus"></i> Nueva Cotización Prenda
</a>
```

### 2. Listar Cotizaciones Prenda

```blade
<a href="{{ route('cotizaciones-prenda.lista') }}" class="btn btn-info">
    <i class="fas fa-list"></i> Mis Cotizaciones Prenda
</a>
```

### 3. Formulario para Guardar Cotización Prenda

```blade
<form action="{{ route('cotizaciones-prenda.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    
    <div class="form-group">
        <label>Cliente</label>
        <input type="text" name="cliente" class="form-control" required>
    </div>
    
    <div class="form-group">
        <label>Productos</label>
        <textarea name="productos" class="form-control"></textarea>
    </div>
    
    <button type="submit" name="action" value="borrador" class="btn btn-secondary">
        Guardar como Borrador
    </button>
    
    <button type="submit" name="action" value="enviar" class="btn btn-success">
        Enviar Cotización
    </button>
</form>
```

### 4. Editar Cotización Prenda

```blade
<a href="{{ route('cotizaciones-prenda.edit', $cotizacion->id) }}" class="btn btn-warning btn-sm">
    <i class="fas fa-edit"></i> Editar
</a>
```

### 5. Actualizar Cotización Prenda

```blade
<form action="{{ route('cotizaciones-prenda.update', $cotizacion->id) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="form-group">
        <label>Cliente</label>
        <input type="text" name="cliente" class="form-control" value="{{ $cotizacion->cliente }}" required>
    </div>
    
    <button type="submit" class="btn btn-primary">Actualizar</button>
</form>
```

### 6. Enviar Cotización Prenda

```blade
<form action="{{ route('cotizaciones-prenda.enviar', $cotizacion->id) }}" method="POST" style="display: inline;">
    @csrf
    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Enviar cotización?')">
        <i class="fas fa-paper-plane"></i> Enviar
    </button>
</form>
```

### 7. Eliminar Cotización Prenda

```blade
<form action="{{ route('cotizaciones-prenda.destroy', $cotizacion->id) }}" method="POST" style="display: inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar cotización?')">
        <i class="fas fa-trash"></i> Eliminar
    </button>
</form>
```

---

## 🎨 TABLA DE COTIZACIONES

```blade
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Número</th>
            <th>Estado</th>
            <th>Fecha</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($cotizaciones as $cot)
            <tr>
                <td>{{ $cot->id }}</td>
                <td>{{ $cot->cliente }}</td>
                <td>{{ $cot->numero_cotizacion ?? 'Borrador' }}</td>
                <td>
                    <span class="badge badge-{{ $cot->es_borrador ? 'warning' : 'success' }}">
                        {{ $cot->es_borrador ? 'Borrador' : 'Enviada' }}
                    </span>
                </td>
                <td>{{ $cot->created_at->format('d/m/Y') }}</td>
                <td>
                    <a href="{{ route('cotizaciones-prenda.edit', $cot->id) }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i>
                    </a>
                    @if($cot->es_borrador)
                        <form action="{{ route('cotizaciones-prenda.enviar', $cot->id) }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    @endif
                    <form action="{{ route('cotizaciones-prenda.destroy', $cot->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center text-muted">No hay cotizaciones</td>
            </tr>
        @endforelse
    </tbody>
</table>
```

---

## 🔄 FLUJO DE COTIZACIÓN

```
1. CREAR
   └─ GET /cotizaciones-prenda/crear
      └─ Mostrar formulario

2. GUARDAR COMO BORRADOR
   └─ POST /cotizaciones-prenda
      └─ action=borrador
      └─ Guardar y permitir edición

3. EDITAR
   └─ GET /cotizaciones-prenda/{id}/editar
      └─ Mostrar formulario con datos

4. ACTUALIZAR
   └─ PUT /cotizaciones-prenda/{id}
      └─ Actualizar datos

5. ENVIAR
   └─ POST /cotizaciones-prenda/{id}/enviar
      └─ Cambiar estado a ENVIADA
      └─ Generar número de cotización

6. ELIMINAR (solo borradores)
   └─ DELETE /cotizaciones-prenda/{id}
      └─ Eliminar cotización
```

---

## 📤 ENVÍO DE DATOS

### Crear/Actualizar Cotización

```javascript
// Datos esperados por el backend
{
    cliente: "Nombre del cliente",
    asesora: "Nombre de la asesora",  // Se obtiene automáticamente
    productos: [...],                  // Array de productos
    logo: [...],                       // Array de logos (si aplica)
    tipo_venta: "M",                   // M, D, X
    especificaciones: "...",
    observaciones_generales: "...",
    imagenes: [...]                    // Archivos de imagen
}
```

### Respuesta Exitosa

```json
{
    "success": true,
    "message": "Cotización guardada correctamente",
    "cotizacion_id": 123,
    "numero_cotizacion": "COT-00123"
}
```

### Respuesta de Error

```json
{
    "success": false,
    "message": "Error al guardar cotización: ...",
    "errors": {
        "cliente": ["El cliente es requerido"]
    }
}
```

---

## 🔐 SEGURIDAD

### Autenticación
- ✅ Todas las rutas requieren `auth`
- ✅ Solo usuarios con rol `asesor` pueden acceder

### Autorización
- ✅ Solo el propietario puede editar su cotización
- ✅ Solo el propietario puede eliminar su cotización
- ✅ Solo el propietario puede enviar su cotización

### CSRF Protection
```blade
@csrf  <!-- Agregar en todos los formularios -->
```

### Method Spoofing
```blade
@method('PUT')    <!-- Para PUT requests -->
@method('DELETE') <!-- Para DELETE requests -->
```

---

## 🎯 CHECKLIST PARA FRONTEND

- [ ] Actualizar links a nuevas rutas
- [ ] Cambiar `cotizaciones-prenda` en lugar de `cotizaciones`
- [ ] Cambiar `cotizaciones-bordado` en lugar de `cotizaciones-logo`
- [ ] Agregar `@csrf` en todos los formularios
- [ ] Usar `@method('PUT')` para actualizaciones
- [ ] Usar `@method('DELETE')` para eliminaciones
- [ ] Validar respuestas JSON
- [ ] Mostrar mensajes de error
- [ ] Manejar estados de carga
- [ ] Confirmar acciones destructivas

---

## 📚 REFERENCIAS

- **Rutas:** `RUTAS_COTIZACIONES_DDD.md`
- **Arquitectura:** `REFACTORIZACION_DDD_COMPLETADA.md`
- **Controllers:** `CotizacionPrendaController.php`, `CotizacionBordadoController.php`

---

## 🟢 ESTADO

**Guía:** ✅ COMPLETA
**Ejemplos:** ✅ INCLUIDOS
**Seguridad:** ✅ DOCUMENTADA
**Listo para:** 🚀 IMPLEMENTACIÓN

---

**Guía creada:** 10 de Diciembre de 2025
**Versión:** 1.0
**Estado:** ✅ LISTO PARA USAR
