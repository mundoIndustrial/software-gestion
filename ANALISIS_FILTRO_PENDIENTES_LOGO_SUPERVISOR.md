# 🎨 ANÁLISIS: Filtro "Pendientes Logo" en Módulo Supervisor-Asesores

## 📋 Resumen Ejecutivo

Se requiere agregar funcionalidad para que el supervisor pueda filtrar y ver solo los **pedidos en estado PENDIENTE_SUPERVISOR que estén relacionados a cotizaciones de tipo LOGO**.

---

## 🔍 Análisis Actual

### Estructura Actual

**Archivo**: `resources/views/components/sidebars/sidebar-supervisor-asesores.blade.php`

```php
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

**Archivo**: `app/Http/Controllers/SupervisorPedidosController.php` (líneas 130-160)

```php
public function index(Request $request)
{
    $query = PedidoProduccion::with(['asesora', 'prendas', 'cotizacion']);

    // FILTRO DE APROBACIÓN: Mostrar solo órdenes según su estado de aprobación
    if ($request->filled('aprobacion')) {
        if ($request->aprobacion === 'pendiente') {
            // Órdenes PENDIENTES DE SUPERVISOR: solo las que tienen estado 'PENDIENTE_SUPERVISOR'
            $query->where('estado', 'PENDIENTE_SUPERVISOR');
            
            // Filtrar solo órdenes con cotización de logo si el parámetro tipo=logo está presente
            if ($request->filled('tipo') && $request->tipo === 'logo') {
                $query->whereHas('cotizacion', function($q) {
                    $q->where('tipo', 'logo');
                });
            }
        }
    }
    // ... resto del código
}
```

**Observación Importante**: El controlador YA tiene soporte para filtrar por LOGO, solo necesita que se use el parámetro `aprobacion=pendiente&tipo=logo`

---

## 💡 Solución Propuesta

### 1️⃣ Agregar Botón "Pendientes Logo" al Sidebar

**Ubicación**: `resources/views/components/sidebars/sidebar-supervisor-asesores.blade.php`

Agregar un nuevo ítem de menú en la sección "Pedidos":

```php
<div class="menu-section">
    <span class="menu-section-title">Pedidos</span>
    <ul class="menu-list" role="navigation">
        <!-- EXISTENTE -->
        <li class="menu-item">
            <a href="{{ route('supervisor-asesores.pedidos.index') }}"
               class="menu-link {{ request()->routeIs('supervisor-asesores.pedidos.*') ? 'active' : '' }}">
                <span class="material-symbols-rounded">shopping_cart</span>
                <span class="menu-label">Todos los Pedidos</span>
            </a>
        </li>
        
        <!-- NUEVO: Pendientes Logo -->
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

**Análisis**:
- ✅ Usa la ruta existente `supervisor-asesores.pedidos.index`
- ✅ Pasa parámetros URL: `aprobacion=pendiente&tipo=logo`
- ✅ Activa el clase `active` cuando está en ese filtro
- ✅ Usa ícono `palette` que representa diseño/logo

---

### 2️⃣ Flujo de Filtrado Actual (YA FUNCIONA)

```
Usuario toca "Pendientes Logo" en sidebar
         ↓
URL: /supervisor-asesores/pedidos?aprobacion=pendiente&tipo=logo
         ↓
SupervisorPedidosController::index() recibe request
         ↓
if ($request->filled('aprobacion')) {
    if ($request->aprobacion === 'pendiente') {
        $query->where('estado', 'PENDIENTE_SUPERVISOR')
        
        if ($request->filled('tipo') && $request->tipo === 'logo') {
            $query->whereHas('cotizacion', function($q) {
                $q->where('tipo', 'logo')  // ← Tipo es 'logo'
            });
        }
    }
}
         ↓
Retorna solo pedidos PENDIENTE_SUPERVISOR 
+ cotización tipo LOGO
         ↓
Vista muestra resultados filtrados
```

---

### 3️⃣ Verificación del Filtro en BD

El controlador busca:

```sql
SELECT * FROM pedidos_produccion
WHERE estado = 'PENDIENTE_SUPERVISOR'
  AND cotizacion_id IN (
    SELECT id FROM cotizaciones 
    WHERE tipo = 'logo'  -- ← Filtra por tipo
  );
```

**Nota**: La columna `tipo` en tabla `cotizaciones` debe contener `'logo'`

Para verificar:
```sql
SELECT DISTINCT tipo FROM cotizaciones;
-- Resultados esperados: 'PL', 'L', 'RF', etc.
```

---

## 📊 Estructura de Datos

### Tabla: `cotizaciones`
```sql
id | numero_cotizacion | tipo | estado | ...
1  | 001               | PL   | ...    | ...
2  | 002               | L    | ...    | ...  ← LOGO
3  | 003               | RF   | ...    | ...
```

### Tabla: `pedidos_produccion`
```sql
id | numero_pedido | cotizacion_id | estado              | ...
1  | PED-001       | 1             | PENDIENTE_SUPERVISOR| ...
2  | PED-002       | 2             | PENDIENTE_SUPERVISOR| ...  ← LOGO
3  | PED-003       | 3             | PENDIENTE_SUPERVISOR| ...
```

---

## 🎯 Puntos Clave

### ✅ Lo que YA está listo:
1. **Controlador**: Ya tiene la lógica de filtrado
2. **BD**: Relación entre `pedidos_produccion` y `cotizaciones`
3. **Vista**: Ya muestra los pedidos correctamente

### ✅ Lo que FALTA:
1. **UI**: Agregar botón "Pendientes Logo" al sidebar
2. **Etiqueta visual**: Mostrar que está filtrado por LOGO
3. **Validación**: Asegurar que `tipo = 'logo'` en cotizaciones

---

## 🔧 Implementación Paso a Paso

### Paso 1: Modificar Sidebar
**Archivo**: `resources/views/components/sidebars/sidebar-supervisor-asesores.blade.php`

Ubicación: Dentro de la sección "Pedidos", agregar nuevo item de menú

```php
<!-- NUEVO -->
<li class="menu-item">
    <a href="{{ route('supervisor-asesores.pedidos.index', ['aprobacion' => 'pendiente', 'tipo' => 'logo']) }}"
       class="menu-link {{ request('aprobacion') === 'pendiente' && request('tipo') === 'logo' ? 'active' : '' }}">
        <span class="material-symbols-rounded">palette</span>
        <span class="menu-label">Pendientes Logo</span>
    </a>
</li>
```

### Paso 2: Verificar Controlador
**Archivo**: `app/Http/Controllers/SupervisorPedidosController.php`

Verificar que la lógica existe (líneas 140-150):
```php
if ($request->filled('tipo') && $request->tipo === 'logo') {
    $query->whereHas('cotizacion', function($q) {
        $q->where('tipo', 'logo');  // ← Busca tipo = 'logo'
    });
}
```

### Paso 3: Actualizar Vista (Opcional)
**Archivo**: `resources/views/supervisor-asesores/pedidos/index.blade.php`

Si quieres mostrar un badge "FILTRADO POR LOGO" en la vista:

```blade
@if(request('tipo') === 'logo' && request('aprobacion') === 'pendiente')
    <div style="background: #fef3c7; border: 1px solid #f59e0b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
        <strong>🎨 Filtrando por: Pedidos Pendientes de Logo</strong>
        <a href="{{ route('supervisor-asesores.pedidos.index') }}" style="color: #f59e0b; text-decoration: underline; margin-left: 1rem;">Limpiar filtro</a>
    </div>
@endif
```

---

## ✅ Validación

Después de implementar, verificar:

1. **Sidebar actualizó**:
   - [ ] Botón "Pendientes Logo" aparece en sidebar
   - [ ] Ícono es `palette`
   - [ ] Se activa cuando estás en esa sección

2. **Filtrado funciona**:
   - [ ] Click en "Pendientes Logo" → URL contiene `aprobacion=pendiente&tipo=logo`
   - [ ] Solo muestra pedidos PENDIENTE_SUPERVISOR
   - [ ] Solo muestra cotizaciones tipo LOGO
   - [ ] Otros pedidos no aparecen

3. **BD está correcta**:
   ```sql
   SELECT COUNT(*) FROM cotizaciones WHERE tipo = 'L';
   -- Debe retornar > 0
   ```

---

## 🐛 Troubleshooting

| Problema | Solución |
|----------|----------|
| Botón no aparece en sidebar | Verificar que el archivo está guardado y hace reload |
| No filtra por LOGO | Verificar que `tipo = 'logo'` en tabla cotizaciones |
| Muestra demasiados pedidos | Verificar estado `PENDIENTE_SUPERVISOR` en BD |
| URL incorrecta en botón | Verificar nombre de ruta `supervisor-asesores.pedidos.index` |

---

## 📝 Notas Técnicas

### Query Generada
Cuando toca "Pendientes Logo", el SQL ejecutado es:

```sql
SELECT * FROM pedidos_produccion 
WHERE estado = 'PENDIENTE_SUPERVISOR'
  AND cotizacion_id IN (
    SELECT id FROM cotizaciones WHERE tipo = 'logo'
  )
ORDER BY fecha_de_creacion_de_orden DESC
LIMIT 15;
```

### Parámetros URL
```
GET /supervisor-asesores/pedidos?aprobacion=pendiente&tipo=logo
```

Parámetros:
- `aprobacion=pendiente` → Filtra por estado PENDIENTE_SUPERVISOR
- `tipo=logo` → Filtra por cotización tipo LOGO

---

## 🎓 Conclusión

**El sistema YA tiene toda la lógica implementada en el controlador.** Solo necesitas:

1. ✅ Agregar un botón "Pendientes Logo" al sidebar
2. ✅ Que el botón apunte a: `/supervisor-asesores/pedidos?aprobacion=pendiente&tipo=logo`
3. ✅ Listo! El controlador hará el resto automáticamente

La implementación es mínima porque Laravel ya tiene:
- La relación `pedidos_produccion` ↔ `cotizaciones`
- El filtrado por tipo en el controlador
- La vista para mostrar resultados

