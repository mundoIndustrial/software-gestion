# 🔄 MIGRACIÓN FRONTEND - COTIZACIONES DDD

**Fecha:** 10 de Diciembre de 2025
**Estado:** 📋 GUÍA DE MIGRACIÓN
**Versión:** 1.0

---

## 🎯 OBJETIVO

Migrar el frontend para usar las nuevas rutas y arquitectura DDD del módulo de cotizaciones.

---

## 📋 CAMBIOS NECESARIOS

### 1. RUTAS ANTIGUAS → RUTAS NUEVAS

#### Cotizaciones Prenda

| Antiguo | Nuevo | Tipo |
|---------|-------|------|
| `/cotizaciones-prenda/crear` | `/cotizaciones-prenda/crear` | ✅ IGUAL |
| `/cotizaciones-prenda` (POST) | `/cotizaciones-prenda` (POST) | ✅ IGUAL |
| `/cotizaciones-prenda` (GET) | `/cotizaciones-prenda` (GET) | ✅ IGUAL |
| `/cotizaciones-prenda/{id}/edit` | `/cotizaciones-prenda/{id}/editar` | ⚠️ CAMBIAR |
| `/cotizaciones-prenda/{id}` (PUT) | `/cotizaciones-prenda/{id}` (PUT) | ✅ IGUAL |
| `/cotizaciones-prenda/{id}/enviar` | `/cotizaciones-prenda/{id}/enviar` | ✅ IGUAL |
| `/cotizaciones-prenda/{id}` (DELETE) | `/cotizaciones-prenda/{id}` (DELETE) | ✅ IGUAL |

#### Cotizaciones Bordado

| Antiguo | Nuevo | Tipo |
|---------|-------|------|
| `/cotizaciones-bordado/crear` | `/cotizaciones-bordado/crear` | ✅ IGUAL |
| `/cotizaciones-bordado` (POST) | `/cotizaciones-bordado` (POST) | ✅ IGUAL |
| `/cotizaciones-bordado` (GET) | `/cotizaciones-bordado` (GET) | ✅ IGUAL |
| `/cotizaciones-bordado/{id}/edit` | `/cotizaciones-bordado/{id}/editar` | ⚠️ CAMBIAR |
| `/cotizaciones-bordado/{id}` (PUT) | `/cotizaciones-bordado/{id}` (PUT) | ✅ IGUAL |
| `/cotizaciones-bordado/{id}/enviar` | `/cotizaciones-bordado/{id}/enviar` | ✅ IGUAL |
| `/cotizaciones-bordado/{id}` (DELETE) | `/cotizaciones-bordado/{id}` (DELETE) | ✅ IGUAL |

---

## 🔍 ARCHIVOS A ACTUALIZAR

### Vistas Blade

```
resources/views/cotizaciones/
├── prenda/
│   ├── create.blade.php       ✅ REVISAR
│   ├── edit.blade.php         ⚠️ CAMBIAR RUTA
│   └── lista.blade.php        ✅ REVISAR
├── bordado/
│   ├── create.blade.php       ✅ REVISAR
│   ├── edit.blade.php         ⚠️ CAMBIAR RUTA
│   └── lista.blade.php        ✅ REVISAR
└── index.blade.php            ✅ REVISAR
```

### JavaScript

```
public/js/asesores/cotizaciones/
├── cotizaciones.js            ✅ REVISAR
├── modules/
│   └── CotizacionPrendaApp.js ✅ REVISAR
└── test-guardado-cotizacion.js ✅ REVISAR
```

---

## 🔧 CAMBIOS ESPECÍFICOS

### 1. En Vistas Blade - Links de Edición

**ANTES:**
```blade
<a href="{{ route('cotizaciones-prenda.edit', $cot->id) }}">Editar</a>
```

**DESPUÉS:**
```blade
<a href="{{ route('cotizaciones-prenda.edit', $cot->id) }}">Editar</a>
<!-- ✅ IGUAL - No cambiar -->
```

### 2. En Vistas Blade - Formularios

**ANTES:**
```blade
<form action="{{ route('cotizaciones-prenda.store') }}" method="POST">
    @csrf
    <!-- ... -->
</form>
```

**DESPUÉS:**
```blade
<form action="{{ route('cotizaciones-prenda.store') }}" method="POST">
    @csrf
    <!-- ... -->
</form>
<!-- ✅ IGUAL - No cambiar -->
```

### 3. En JavaScript - Envío de Datos

**ANTES:**
```javascript
const response = await fetch('/cotizaciones-prenda', {
    method: 'POST',
    body: formData
});
```

**DESPUÉS:**
```javascript
const response = await fetch(
    document.querySelector('form').action || '/cotizaciones-prenda',
    {
        method: 'POST',
        body: formData
    }
);
```

---

## ✅ CHECKLIST DE MIGRACIÓN

### Vistas
- [ ] Revisar `resources/views/cotizaciones/prenda/create.blade.php`
- [ ] Revisar `resources/views/cotizaciones/prenda/edit.blade.php`
- [ ] Revisar `resources/views/cotizaciones/prenda/lista.blade.php`
- [ ] Revisar `resources/views/cotizaciones/bordado/create.blade.php`
- [ ] Revisar `resources/views/cotizaciones/bordado/edit.blade.php`
- [ ] Revisar `resources/views/cotizaciones/bordado/lista.blade.php`
- [ ] Revisar `resources/views/cotizaciones/index.blade.php`

### JavaScript
- [ ] Revisar `public/js/asesores/cotizaciones/cotizaciones.js`
- [ ] Revisar `public/js/asesores/cotizaciones/modules/CotizacionPrendaApp.js`
- [ ] Revisar `public/js/asesores/cotizaciones/test-guardado-cotizacion.js`

### Funcionalidad
- [ ] Crear cotización prenda
- [ ] Guardar cotización como borrador
- [ ] Editar cotización
- [ ] Actualizar cotización
- [ ] Enviar cotización
- [ ] Eliminar cotización
- [ ] Listar cotizaciones
- [ ] Crear cotización bordado
- [ ] Guardar cotización bordado como borrador
- [ ] Editar cotización bordado
- [ ] Actualizar cotización bordado
- [ ] Enviar cotización bordado
- [ ] Eliminar cotización bordado
- [ ] Listar cotizaciones bordado

---

## 🔐 SEGURIDAD

### CSRF Token
```blade
@csrf  <!-- Obligatorio en todos los formularios -->
```

### Method Spoofing
```blade
@method('PUT')    <!-- Para actualizaciones -->
@method('DELETE') <!-- Para eliminaciones -->
```

### Autorización
- ✅ Solo usuarios autenticados pueden acceder
- ✅ Solo asesores pueden crear/editar/eliminar
- ✅ Solo propietario puede editar su cotización

---

## 📊 RESUMEN DE CAMBIOS

| Elemento | Cambios | Impacto |
|----------|---------|--------|
| **Rutas** | Mínimos | 🟢 BAJO |
| **Vistas** | Revisar | 🟡 MEDIO |
| **JavaScript** | Revisar | 🟡 MEDIO |
| **Funcionalidad** | Igual | 🟢 BAJO |

---

## 🚀 PASOS DE MIGRACIÓN

### Paso 1: Revisar Vistas
1. Abrir cada archivo `.blade.php`
2. Verificar que usen `route()` helper
3. Verificar que tengan `@csrf`
4. Verificar que tengan `@method()` si es necesario

### Paso 2: Revisar JavaScript
1. Abrir cada archivo `.js`
2. Buscar URLs hardcodeadas
3. Reemplazar por `route()` o `form.action`
4. Verificar que envíen FormData correctamente

### Paso 3: Testear
1. Crear cotización prenda
2. Guardar como borrador
3. Editar cotización
4. Enviar cotización
5. Eliminar cotización
6. Repetir para bordado

### Paso 4: Validar
1. Verificar que se guardan datos correctamente
2. Verificar que se envían imágenes correctamente
3. Verificar que se generan números de cotización
4. Verificar que se cambian estados correctamente

---

## 📝 NOTAS IMPORTANTES

1. **Las rutas son prácticamente iguales**
   - Solo cambio menor: `edit` → `editar`
   - El resto de rutas son idénticas

2. **Usar `route()` helper**
   - Evita hardcodear URLs
   - Facilita cambios futuros
   - Más seguro

3. **FormData es obligatorio**
   - Para enviar imágenes
   - Para enviar archivos
   - Mejor que JSON

4. **Respuestas JSON**
   - El backend retorna JSON
   - Validar `response.success`
   - Mostrar mensajes de error

---

## 🟢 ESTADO

**Migración:** 📋 GUÍA CREADA
**Vistas:** ⏳ PENDIENTE DE REVISAR
**JavaScript:** ⏳ PENDIENTE DE REVISAR
**Testing:** ⏳ PENDIENTE

---

**Guía creada:** 10 de Diciembre de 2025
**Estado:** 📋 LISTO PARA IMPLEMENTACIÓN
