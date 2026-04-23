# 📋 Cómo Se Registra el Asesor y el Pedido en Cada Error

## Resumen

El sistema **captura automáticamente**:
- **Asesor (Usuario)**: Quién estaba usando la plataforma cuando ocurrió el error
- **Pedido**: En qué pedido ocurrió el error (si es aplicable)

No requiere configuración adicional — funciona automáticamente.

---

## 🔍 Cómo Se Obtiene el Asesor (Usuario ID)

### Orden de Búsqueda

El sistema busca el `usuario_id` en este orden (primera coincidencia gana):

```javascript
1. Meta tag en HTML
   <meta name="user-id" content="5">
   
2. Variable global en JavaScript
   window.usuarioId = 5;
   // o
   window.AUTH.user.id = 5;
   
3. Data attribute en body
   <body data-user-id="5">
   
4. localStorage (fallback)
   localStorage.setItem('current_user_id', '5');
```

### Implementación Recomendada (Blade)

En tu layout principal (`resources/views/layouts/app.blade.php`):

```blade
<!-- Opción 1: Meta tag (más simple) -->
@auth
    <meta name="user-id" content="{{ auth()->id() }}">
@endauth

<!-- O Opción 2: Data attribute en body -->
<body data-user-id="{{ auth()->id() ?? '' }}">
```

---

## 📦 Cómo Se Obtiene el Pedido ID

### Orden de Búsqueda

El sistema busca el `pedido_id` en este orden:

```javascript
1. Variables globales (establecidas por JavaScript)
   window.pedidoEditarId = 123;
   // o
   window.pedidoId = 123;
   // o
   window.currentPedidoId = 123;
   
2. De la URL actual
   /asesores/pedidos/123/editar      → extrae 123
   /pedidos/456                      → extrae 456
   
3. De query parameters
   ?edit=123                         → extrae 123
   ?pedido_id=456                    → extrae 456
   
4. Data attribute en body
   <body data-pedido-id="123">
```

### Implementación Automática

Si estás en una URL como:
```
/asesores/pedidos/123/editar
```

Se extrae automáticamente `pedido_id = 123` de la URL.

### Para Crear Pedido (sin ID en URL)

En tu view de creación (`crear-pedido-nuevo.blade.php`):

```blade
<!-- Cuando guardas como borrador, la respuesta contiene el nuevo ID -->
<!-- Luego, en la próxima carga, el ID estará en la URL de edición -->
```

---

## 📊 Flujo Completo: Error en Edición de Pedido

```
┌─────────────────────────────────────────────────┐
│ Admin abre: /asesores/pedidos/123/editar        │
└─────────────────────────────────────────────────┘
             ↓
┌─────────────────────────────────────────────────┐
│ error-logger-service.js:                        │
│  - _obtenerUsuarioId() → busca en meta tag      │
│  - _obtenerPedidoId()  → extrae de URL (/123)   │
└─────────────────────────────────────────────────┘
             ↓
┌─────────────────────────────────────────────────┐
│ Usuario intenta cargar imagen > 3MB             │
│ Error: FILE_TOO_LARGE                           │
└─────────────────────────────────────────────────┘
             ↓
┌─────────────────────────────────────────────────┐
│ registrarErrorImagen() es llamado               │
│ Se captura automáticamente:                     │
│  - tipo: ERROR_IMAGEN                           │
│  - usuario_id: 5 (del meta tag)                 │
│  - pedido_id: 123 (de la URL)                   │
│  - timestamp: 2026-04-23T15:30:45.123Z          │
└─────────────────────────────────────────────────┘
             ↓
┌─────────────────────────────────────────────────┐
│ POST /api/errores/registrar                     │
│ {                                               │
│   tipo: 'ERROR_IMAGEN',                         │
│   mensaje: 'FILE_TOO_LARGE: 5MB > 3MB',         │
│   contexto: {                                   │
│     usuario_id: 5,                              │
│     pedido_id: 123                              │
│   },                                            │
│   ...                                           │
│ }                                               │
└─────────────────────────────────────────────────┘
             ↓
┌─────────────────────────────────────────────────┐
│ Laravel Backend:                                │
│ ErrorLogController → SystemError::create()     │
│                                                 │
│ Extrae del contexto:                           │
│  usuario_id: 5                                  │
│  pedido_id: 123                                 │
└─────────────────────────────────────────────────┘
             ↓
┌─────────────────────────────────────────────────┐
│ Base de Datos (system_errors):                  │
│ ID  │ Tipo            │ Usuario │ Pedido │ Hora │
│ 1   │ ERROR_IMAGEN    │ 5       │ 123    │ ...  │
└─────────────────────────────────────────────────┘
             ↓
┌─────────────────────────────────────────────────┐
│ Admin ve en /admin/errores:                     │
│                                                 │
│ ❌ ERROR_IMAGEN                                 │
│    Asesor: Juan Pérez (juan@empresa.com)       │
│    Pedido: #123 (Cliente: Acme Corp)           │
│    Hora: hace 5 minutos                        │
└─────────────────────────────────────────────────┘
```

---

## 🎯 Checklist de Setup

### Para que el Usuario se registre:

- [ ] Meta tag `<meta name="user-id" content="{{ auth()->id() }}">` en layout
  O
- [ ] Data attribute `<body data-user-id="{{ auth()->id() }}">` en layout

**Verificar:**
```javascript
// En consola
ErrorLoggerService._obtenerUsuarioId()
// Debe retornar el ID del usuario (ej: 5)
```

### Para que el Pedido se registre:

- [ ] Si estás en URL de edición: `/asesores/pedidos/123/editar`
  → Se extrae automáticamente

- [ ] Si es crear pedido sin ID inicial:
  → Se captura el `pedido_id` DESPUÉS de guardar (en respuesta del servidor)

**Verificar:**
```javascript
// En consola
ErrorLoggerService._obtenerPedidoId()
// Debe retornar el ID del pedido (ej: 123)
// O null si no estás en edición
```

---

## 📝 Ejemplos de Errores Registrados

### Ejemplo 1: Error en Creación de Pedido

```
Error: ERROR_VALIDACION
Asesor: María García (maria@empresa.com)
Pedido: Sin pedido (aún no se creó)
Causa: Cliente vacío
Hora: 15:30:45 del 23/04/2026
```

### Ejemplo 2: Error en Edición de Pedido

```
Error: ERROR_IMAGEN
Asesor: Juan Pérez (juan@empresa.com)
Pedido: #123 (Cliente: Acme Corp)
Causa: Archivo muy grande (8.5MB > 3MB)
Hora: 15:32:10 del 23/04/2026
```

### Ejemplo 3: Error de Red

```
Error: ERROR_RED
Asesor: Carlos López (carlos@empresa.com)
Pedido: #456 (Cliente: TechCorp)
Causa: 500 Internal Server Error
Intento: 2 de 3 (reintentó y falló)
Hora: 15:35:22 del 23/04/2026
```

---

## 🔐 Consideraciones de Privacidad

- ✅ Solo usuarios **autenticados** generan logs
- ✅ La información es interna (no se expone públicamente)
- ✅ El admin puede ver todos los errores de todos los usuarios
- ✅ Datos sensibles se protegen según Laravel's auth

---

## 🐛 Troubleshooting

### "Usuario no aparece en la tabla"

1. Verificar meta tag:
   ```blade
   <!-- En View, presiona F12 y busca -->
   <meta name="user-id" content="5">
   ```

2. O verificar data attribute:
   ```
   <body data-user-id="5">
   ```

3. Si no está, agregar en `resources/views/layouts/app.blade.php`:
   ```blade
   @auth
       <meta name="user-id" content="{{ auth()->id() }}">
   @endauth
   ```

### "Pedido no aparece en la tabla"

1. Si estás en URL de edición (`/pedidos/123/editar`):
   - Debe extraerse automáticamente
   - Verificar en consola: `ErrorLoggerService._obtenerPedidoId()`

2. Si es crear (sin ID aún):
   - El `pedido_id` será null hasta que se guarde
   - Después de guardar, aparecerá en logs futuros

3. Si la URL es diferente:
   - Ajustar el regex en `error-logger-service.js`
   - Buscar: `const urlRegex = /\/pedidos\/(\d+)/`

---

## 📈 Análisis SQL

### Top errores por asesor (últimas 24h)

```sql
SELECT 
    u.name as asesor,
    COUNT(*) as total_errores,
    COUNT(DISTINCT se.pedido_id) as pedidos_afectados
FROM system_errors se
LEFT JOIN users u ON se.usuario_id = u.id
WHERE se.ocurrido_en >= NOW() - INTERVAL 24 HOUR
GROUP BY se.usuario_id
ORDER BY total_errores DESC;
```

### Errores por pedido

```sql
SELECT 
    se.pedido_id,
    COUNT(*) as total_errores,
    GROUP_CONCAT(DISTINCT se.tipo) as tipos,
    u.name as ultimo_asesor
FROM system_errors se
LEFT JOIN users u ON se.usuario_id = u.id
WHERE se.pedido_id IS NOT NULL
GROUP BY se.pedido_id
ORDER BY total_errores DESC;
```

---

**Status:** ✅ Usuario y Pedido se registran automáticamente en cada error.

No requiere configuración — solo agregar el meta tag en el layout y listo.
