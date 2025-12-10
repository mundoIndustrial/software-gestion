# 🔗 RUTAS - COTIZACIONES DDD

**Fecha:** 10 de Diciembre de 2025
**Estado:** ✅ COMPLETADA

---

## 📋 RUTAS DISPONIBLES

### COTIZACIONES TIPO PRENDA (P)

**Middleware:** `auth`, `role:asesor`

| Método | Ruta | Nombre | Controlador | Acción |
|--------|------|--------|-------------|--------|
| GET | `/cotizaciones-prenda/crear` | `cotizaciones-prenda.create` | CotizacionPrendaController | Mostrar formulario |
| POST | `/cotizaciones-prenda` | `cotizaciones-prenda.store` | CotizacionPrendaController | Guardar cotización |
| GET | `/cotizaciones-prenda` | `cotizaciones-prenda.lista` | CotizacionPrendaController | Listar cotizaciones |
| GET | `/cotizaciones-prenda/{cotizacion}/editar` | `cotizaciones-prenda.edit` | CotizacionPrendaController | Mostrar edición |
| PUT | `/cotizaciones-prenda/{cotizacion}` | `cotizaciones-prenda.update` | CotizacionPrendaController | Actualizar |
| POST | `/cotizaciones-prenda/{cotizacion}/enviar` | `cotizaciones-prenda.enviar` | CotizacionPrendaController | Enviar |
| DELETE | `/cotizaciones-prenda/{cotizacion}` | `cotizaciones-prenda.destroy` | CotizacionPrendaController | Eliminar |

---

### COTIZACIONES TIPO BORDADO/LOGO (L)

**Middleware:** `auth`, `role:asesor`

| Método | Ruta | Nombre | Controlador | Acción |
|--------|------|--------|-------------|--------|
| GET | `/cotizaciones-bordado/crear` | `cotizaciones-bordado.create` | CotizacionBordadoController | Mostrar formulario |
| POST | `/cotizaciones-bordado` | `cotizaciones-bordado.store` | CotizacionBordadoController | Guardar cotización |
| GET | `/cotizaciones-bordado` | `cotizaciones-bordado.lista` | CotizacionBordadoController | Listar cotizaciones |
| GET | `/cotizaciones-bordado/{cotizacion}/editar` | `cotizaciones-bordado.edit` | CotizacionBordadoController | Mostrar edición |
| PUT | `/cotizaciones-bordado/{cotizacion}` | `cotizaciones-bordado.update` | CotizacionBordadoController | Actualizar |
| POST | `/cotizaciones-bordado/{cotizacion}/enviar` | `cotizaciones-bordado.enviar` | CotizacionBordadoController | Enviar |
| DELETE | `/cotizaciones-bordado/{cotizacion}` | `cotizaciones-bordado.destroy` | CotizacionBordadoController | Eliminar |

---

## 🎯 EJEMPLOS DE USO EN FRONTEND

### Crear Cotización Prenda
```html
<a href="{{ route('cotizaciones-prenda.create') }}" class="btn btn-primary">
    Crear Cotización Prenda
</a>
```

### Listar Cotizaciones Prenda
```html
<a href="{{ route('cotizaciones-prenda.lista') }}" class="btn btn-info">
    Ver Mis Cotizaciones
</a>
```

### Editar Cotización Prenda
```html
<a href="{{ route('cotizaciones-prenda.edit', $cotizacion->id) }}" class="btn btn-warning">
    Editar
</a>
```

### Enviar Cotización Prenda
```html
<form action="{{ route('cotizaciones-prenda.enviar', $cotizacion->id) }}" method="POST">
    @csrf
    <button type="submit" class="btn btn-success">Enviar</button>
</form>
```

### Eliminar Cotización Prenda
```html
<form action="{{ route('cotizaciones-prenda.destroy', $cotizacion->id) }}" method="POST">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-danger">Eliminar</button>
</form>
```

---

## 🔐 SEGURIDAD

### Middleware Aplicado
- ✅ `auth` - Usuario autenticado
- ✅ `role:asesor` - Solo asesores pueden acceder

### Autorización
- ✅ `$this->authorize('update', $cotizacion)` - En métodos edit, update, enviar
- ✅ `$this->authorize('delete', $cotizacion)` - En método destroy

---

## 📊 RESUMEN

| Tipo | Rutas | Métodos |
|------|-------|---------|
| **Prenda** | 7 | 7 |
| **Bordado** | 7 | 7 |
| **Total** | 14 | 14 |

---

## 🟢 ESTADO

**Rutas:** ✅ REGISTRADAS
**Middleware:** ✅ CONFIGURADO
**Autorización:** ✅ IMPLEMENTADA
**Documentación:** ✅ COMPLETA

---

**Rutas agregadas:** 10 de Diciembre de 2025
**Estado:** ✅ LISTO PARA FRONTEND
