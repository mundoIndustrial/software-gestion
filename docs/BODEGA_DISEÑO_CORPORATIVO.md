# 🏭 MÓDULO BODEGA - DISEÑO CORPORATIVO INDUSTRIAL

##  Descripción

Sistema de gestión de pedidos para bodegueros con interfaz corporativa, industrial y profesional. Estructura jerárquica con agrupación por número de pedido.

## ✨ Características del Diseño

###  Visual Corporativo
- **Colores Slate/Gris** para sensación industrial
- **Bordes definidos** (border-2) - Sin diseño plano
- **Tipografía pesada** (font-black, uppercase, tracking-widest)
- **No sombras exageradas** - Bordes visuales claros
- **Sensación de control administrativo**

###  Estructura Jerárquica (3 Niveles)

```
ENCABEZADO (THEAD)
├─ Fondo gris claro bg-slate-100
├─ Bordes definidos border-2
├─ Tipografía: text-[11px] font-black uppercase
└─ Columnas con bordes separadores

FILA SEPARADORA (Pedido)
├─ Fondo más oscuro bg-slate-200
├─ Línea vertical izquierda
├─ Info: PEDIDO #1025 | 30-01-2024
├─ Estado badge (PENDIENTE / ENTREGADO / RETRASADO)
└─ Ocupa todas las columnas (colspan=7)

FILAS DE ARTÍCULOS
├─ Fondo blanco normal
├─ Hover gris claro
├─ Inputs editables (Observaciones, Fecha)
├─ Botón ENTREGAR o Badge OK
└─ Si está entregado: fondo azul claro
```

## 🎯 Estados Visuales

| Estado | Badge | Color | Botón |
|--------|-------|-------|-------|
| **PENDIENTE** |  PENDIENTE | Ámbar (#f59e0b) | ENTREGAR (border-black) |
| **ENTREGADO** | ✓ OK | Azul (#2563eb) | Deshabilitado (badge azul) |
| **RETRASADO** | ⚠ RETRASADO | Rojo (#dc2626) | ENTREGAR (borde rojo) |

## 📱 Componentes Principales

### 1. Encabezado Principal
```blade
<!-- Logo, usuario, rol -->
<!-- Tipografía: font-black tracking-tight -->
<!-- Border-b-2 border-slate-300 -->
```

### 2. Panel de Filtros
```blade
<!-- Buscador, Filtro Asesor, Filtro Estado -->
<!-- Border-2 border-slate-300 -->
<!-- Inputs: border-2, text-[11px], bg-slate-50 -->
```

### 3. Tabla Corporativa
```blade
<!-- THEAD: bg-slate-100, border-2 -->
<!-- Fila separadora: bg-slate-200, colspan="7" -->
<!-- Filas de datos: hover:bg-slate-50 -->
<!-- Inputs inline: border-2, font-mono -->
```

### 4. Estadísticas
```blade
<!-- 4 tarjetas: Total, Pendientes, Entregados, Retrasados -->
<!-- Border-2, sin sombras, hover:-translate-y-0.5 -->
```

### 5. Toast Notifications
```blade
<!-- bg-slate-900, border-2 border-slate-700 -->
<!-- Tipografía: font-bold uppercase tracking-wider -->
```

##  Instalación

### 1. Registrar Rutas
En `routes/web.php`:
```php
require base_path('routes/bodega.php');
```

### 2. Ejecutar Migración
```bash
php artisan migrate
```

### 3. Crear Permisos
```bash
php artisan tinker
```
```php
use Spatie\Permission\Models\Permission, Role;

$bodeguero = Role::create(['name' => 'bodeguero']);
$permissions = ['view-bodega-pedidos', 'marcar-entregado', 'editar-observaciones', 'editar-fecha-entrega'];
foreach ($permissions as $p) {
    $perm = Permission::create(['name' => $p]);
    $bodeguero->givePermissionTo($perm);
}
```

### 4. Asignar Rol a Usuario
```php
$user = User::find(1);
$user->assignRole('bodeguero');
```

### 5. Incluir CSS en Layout
En `resources/views/layouts/app.blade.php`:
```blade
<head>
    <!-- ... -->
    <link rel="stylesheet" href="{{ asset('css/bodega.css') }}">
</head>
```

### 6. Incluir Meta CSRF
```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

## 📂 Archivos Generados

```
resources/views/bodega/
├── pedidos.blade.php               Vista corporativa

public/css/
├── bodega.css                      Estilos industriales

public/js/
├── bodega-pedidos.js               JavaScript vanilla

app/Http/Controllers/Bodega/
├── PedidosController.php           Lógica backend

app/Models/
├── ReciboPrenda.php                Modelo con scopes

database/migrations/
├── *_create_recibo_prendas_table.php   Estructura BD

database/seeders/
├── ReciboPrendaSeeder.php          Datos de ejemplo

routes/
├── bodega.php                      Rutas del módulo

tests/Feature/Bodega/
├── PedidosControllerTest.php       Tests unitarios
```

##  Personalización de Colores

### Cambiar Color Primario (Azul → Verde)

En `public/css/bodega.css`:
```css
:root {
    --blue-600: #16a34a; /* Verde en lugar de azul */
}
```

En `resources/views/bodega/pedidos.blade.php`:
- Busca: `bg-blue-600` → Reemplaza con `bg-green-600`
- Busca: `#dbeafe` → Reemplaza con `#dcfce7`

### Cambiar Fuentes

En `public/css/bodega.css`:
```css
body {
    font-family: 'Your Font', sans-serif;
}
```

## 🔐 Seguridad

 **CSRF Protection** - Todos los POST llevan token
 **Autorización** - Requiere rol `bodeguero`
 **Permisos** - Validación granular por acción
 **Validación** - Backend y frontend
 **Auditoría** - Activity log automático
 **Sanitización** - Inputs escapados

##  Funcionalidades AJAX

### Entregar Pedido
```javascript
POST /bodega/pedidos/{id}/entregar
Response: { success: true, data: {...} }
```

### Actualizar Observaciones
```javascript
POST /bodega/pedidos/observaciones
Body: { id: 1, observaciones: "texto" }
```

### Actualizar Fecha
```javascript
POST /bodega/pedidos/fecha
Body: { id: 1, fecha_entrega: "2026-02-15" }
```

##  Testing

Ejecutar tests:
```bash
php artisan test tests/Feature/Bodega/PedidosControllerTest.php
```

## 📈 Optimizaciones

### Sticky Header
El THEAD está fijo (`sticky top-0 z-20`) para scroll largo.

### Inputs Inline
Edición directa sin modales - UX rápida.

### Estadísticas en Tiempo Real
Se actualizan al filtrar o cambiar estados.

### Detección Automática de Retrasados
Si fecha < hoy, automáticamente marca como "RETRASADO".

## 🐛 Solución de Problemas

### "Meta CSRF token not found"
En `layouts/app.blade.php`, dentro de `<head>`:
```blade
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### Estilos no cargan
Verifica que el CSS esté incluido:
```blade
<link rel="stylesheet" href="{{ asset('css/bodega.css') }}">
```

### AJAX retorna 401
El usuario no tiene rol `bodeguero`:
```bash
php artisan tinker
>>> User::find(1)->assignRole('bodeguero');
```

### Tabla vacía
Cargar datos de prueba:
```bash
php artisan db:seed ReciboPrendaSeeder
```

## 📝 Notas Importantes

1. **No usa sombras** - Solo bordes. Más industrial.
2. **Tipografía pesada** - Font-black, uppercase, tracking-widest
3. **Bordes definidos** - border-2 border-slate-300/400
4. **Colores corporativos** - Slate (gris), no colores vibrantes
5. **Sin animaciones exageradas** - Solo transiciones suaves
6. **Mobile-friendly** - Responsive en tablets/phones
7. **Accesible** - Focus visible, ratios de contraste, labels

##  Próximas Mejoras

- [ ] Exportar a Excel con formatos
- [ ] Bulk actions (entregar múltiples)
- [ ] Historial de cambios por pedido
- [ ] Notificaciones en tiempo real (Reverb)
- [ ] Columna "Responsable Bodeguero"
- [ ] Indicador de retraso en encabezado
- [ ] Búsqueda avanzada con múltiples filtros
- [ ] Impresión optimizada para etiquetas

## 📞 Soporte

Para issues o mejoras:
1. Revisar logs: `storage/logs/laravel.log`
2. DevTools: F12 → Console → Network
3. Verificar permisos del usuario
4. Ejecutar: `php artisan cache:clear`

---

**Versión:** 2.0 Corporativo
**Estado:** Production Ready 
**Último update:** Febrero 2026
