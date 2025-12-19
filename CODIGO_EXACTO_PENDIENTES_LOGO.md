# 📝 CÓDIGO EXACTO - "Pendientes Logo"

## 📁 Archivo Modificado

### `resources/views/components/sidebars/sidebar-supervisor-asesores.blade.php`

---

## 🔄 ANTES (Original)

```blade
        <div class="menu-section">
            <span class="menu-section-title">Pedidos</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('supervisor-asesores.pedidos.index') }}"
                       class="menu-link {{ request()->routeIs('supervisor-asesores.pedidos.*') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">shopping_cart</span>
                        <span class="menu-label">Todos los Pedidos</span>
                    </a>
                </li>
            </ul>
        </div>
```

---

## ✨ DESPUÉS (Con Cambios)

```blade
        <div class="menu-section">
            <span class="menu-section-title">Pedidos</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item">
                    <a href="{{ route('supervisor-asesores.pedidos.index') }}"
                       class="menu-link {{ request()->routeIs('supervisor-asesores.pedidos.*') && !request('aprobacion') && !request('tipo') ? 'active' : '' }}">
                        <span class="material-symbols-rounded">shopping_cart</span>
                        <span class="menu-label">Todos los Pedidos</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="{{ route('supervisor-asesores.pedidos.index', ['aprobacion' => 'pendiente', 'tipo' => 'logo']) }}"
                       class="menu-link {{ request('aprobacion') === 'pendiente' && request('tipo') === 'logo' ? 'active' : '' }}">
                        <span class="material-symbols-rounded">palette</span>
                        <span class="menu-label">Pendientes Logo</span>
                    </a>
                </li>
            </ul>
        </div>
```

---

## 📋 DIFERENCIAS CLAVE

### Cambio 1: Mejora en "Todos los Pedidos"
```blade
<!-- ANTES -->
class="menu-link {{ request()->routeIs('supervisor-asesores.pedidos.*') ? 'active' : '' }}"

<!-- DESPUÉS -->
class="menu-link {{ request()->routeIs('supervisor-asesores.pedidos.*') && !request('aprobacion') && !request('tipo') ? 'active' : '' }}"
```
**Razón**: Asegurar que "Todos los Pedidos" solo es `active` cuando NO hay filtros aplicados

---

### Cambio 2: Nuevo Item "Pendientes Logo"
```blade
<!-- NUEVO ÍTEM -->
<li class="menu-item">
    <a href="{{ route('supervisor-asesores.pedidos.index', ['aprobacion' => 'pendiente', 'tipo' => 'logo']) }}"
       class="menu-link {{ request('aprobacion') === 'pendiente' && request('tipo') === 'logo' ? 'active' : '' }}">
        <span class="material-symbols-rounded">palette</span>
        <span class="menu-label">Pendientes Logo</span>
    </a>
</li>
```

**Desglose**:
- `route(...)`: Genera URL `/supervisor-asesores/pedidos?aprobacion=pendiente&tipo=logo`
- `['aprobacion' => 'pendiente']`: Parámetro para filtro de estado
- `['tipo' => 'logo']`: Parámetro para filtro de tipo
- `request('aprobacion') === 'pendiente'`: Verifica si está en ese filtro
- `request('tipo') === 'logo'`: Verifica si está en ese tipo
- `palette`: Ícono que representa diseño/logo
- `Pendientes Logo`: Etiqueta visible

---

## 🎯 LÍNEAS EXACTAS

**Archivo**: `resources/views/components/sidebars/sidebar-supervisor-asesores.blade.php`

**Ubicación**: Sección "Pedidos" (después del primer `</li>`)

**Insertar**:
```blade
                <li class="menu-item">
                    <a href="{{ route('supervisor-asesores.pedidos.index', ['aprobacion' => 'pendiente', 'tipo' => 'logo']) }}"
                       class="menu-link {{ request('aprobacion') === 'pendiente' && request('tipo') === 'logo' ? 'active' : '' }}">
                        <span class="material-symbols-rounded">palette</span>
                        <span class="menu-label">Pendientes Logo</span>
                    </a>
                </li>
```

---

## 🔐 No Modificar

### Estos archivos NO necesitan cambios:

#### 1. `app/Http/Controllers/SupervisorPedidosController.php`
```php
// ✓ La lógica ya existe (línea 148-151):
if ($request->filled('tipo') && $request->tipo === 'logo') {
    $query->whereHas('cotizacion', function($q) {
        $q->where('tipo', 'logo');
    });
}
```

#### 2. `resources/views/supervisor-asesores/pedidos/index.blade.php`
```php
// ✓ La vista funciona automáticamente con los datos filtrados
// ✓ No necesita cambios
```

#### 3. Rutas
```php
// ✓ Ya existe la ruta:
Route::get('/pedidos', [SupervisorPedidosController::class, 'index'])
    ->name('supervisor-asesores.pedidos.index');
```

---

## 🧪 Verificación de Sintaxis

### Blade Válido ✅
```blade
{{ route('supervisor-asesores.pedidos.index', ['aprobacion' => 'pendiente', 'tipo' => 'logo']) }}
```
↓ Genera:
```
/supervisor-asesores/pedidos?aprobacion=pendiente&tipo=logo
```

### Condicional Válido ✅
```blade
{{ request('aprobacion') === 'pendiente' && request('tipo') === 'logo' ? 'active' : '' }}
```
↓ Retorna:
```
'active' (si parámetros coinciden)
''      (si no coinciden)
```

---

## 📊 Estructura Completa del Sidebar

```blade
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <!-- ... header ... -->
    </div>

    <div class="sidebar-content">
        <div class="menu-section">
            <span class="menu-section-title">Principal</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item"><!-- Dashboard --></li>
            </ul>
        </div>

        <div class="menu-section">
            <span class="menu-section-title">Cotizaciones</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item"><!-- Cotizaciones --></li>
            </ul>
        </div>

        <div class="menu-section">
            <span class="menu-section-title">Pedidos</span>
            <ul class="menu-list" role="navigation">
                <li class="menu-item"><!-- Todos los Pedidos --></li>
                <li class="menu-item"><!-- Pendientes Logo ← NUEVO --></li>
            </ul>
        </div>

        <div class="menu-section">
            <span class="menu-section-title">Información</span>
            <ul class="menu-list">
                <li class="menu-item"><!-- Asesores --></li>
            </ul>
        </div>
    </div>

    <div class="sidebar-footer"></div>
</aside>
```

---

## 🚀 Cómo Copiar el Código

### Opción 1: Copiar el bloque completo
```bash
# Copiar el código del "DESPUÉS" del sidebar
# Ir a: resources/views/components/sidebars/sidebar-supervisor-asesores.blade.php
# Reemplazar sección "Pedidos" completa
```

### Opción 2: Copiar solo el nuevo ítem
```bash
# Copiar solo el nuevo <li>
# Pegar después del </li> de "Todos los Pedidos"
```

---

## 📝 Checklist de Copiar/Pegar

- [ ] Archivo abierto: `sidebar-supervisor-asesores.blade.php`
- [ ] Ubicación correcta: Sección "Pedidos"
- [ ] Indentación correcta (espacios/tabs)
- [ ] HTML válido (abrir y cerrar tags)
- [ ] Blade syntax válido (`{{ }}`, `{{ 'active' }}`, etc.)
- [ ] Archivo guardado
- [ ] Página recargada en navegador
- [ ] Botón visible en sidebar

---

## 🔗 URLs Generadas

### Para "Todos los Pedidos":
```
GET /supervisor-asesores/pedidos
```

### Para "Pendientes Logo":
```
GET /supervisor-asesores/pedidos?aprobacion=pendiente&tipo=logo
```

---

## 💻 Cómo Probar en Navegador

```javascript
// Abrir console (F12) y ejecutar:

// Ver parámetros actuales
console.log(window.location.search);
// Resultado: ?aprobacion=pendiente&tipo=logo

// Ver parámetro específico
const params = new URLSearchParams(window.location.search);
console.log(params.get('aprobacion')); // 'pendiente'
console.log(params.get('tipo'));      // 'logo'
```

---

## 📌 Resumen

| Aspecto | Detalles |
|--------|---------|
| **Archivo** | `sidebar-supervisor-asesores.blade.php` |
| **Sección** | "Pedidos" |
| **Elemento** | Nuevo `<li>` de menú |
| **Ícono** | `palette` |
| **Etiqueta** | "Pendientes Logo" |
| **URL** | `/supervisor-asesores/pedidos?aprobacion=pendiente&tipo=logo` |
| **Líneas** | 8 líneas de código |
| **Cambios BD** | 0 |
| **Cambios Controlador** | 0 |

---

## ✅ Listo para Copiar

El código está listo para ser copiado y pegado directamente en el archivo. Solo asegurate de:

1. ✅ Mantener la indentación
2. ✅ Verificar que los tags HTML cierren correctamente
3. ✅ Guardar el archivo
4. ✅ Recargar la página

¡Eso es todo! 🎉

