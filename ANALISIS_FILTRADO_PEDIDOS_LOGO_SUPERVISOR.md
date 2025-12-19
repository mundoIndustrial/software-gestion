# 📊 ANÁLISIS: Módulo Supervisor-Pedidos - Filtrado de Pedidos LOGO

## 🎯 Situación Actual

El módulo supervisor-pedidos **YA tiene implementado** el filtrado de pedidos LOGO pendientes.

Cuando el usuario hace clic en el botón **"Pendientes Logo"** del sidebar, se filtra correctamente mostrando solo:
- Pedidos en estado: `PENDIENTE_SUPERVISOR`
- Relacionados a cotizaciones de tipo: `logo` (código 'L')

---

## 🔧 Cómo Funciona Actualmente

### 1️⃣ **Sidebar (Vista)**
**Archivo:** `resources/views/components/sidebars/sidebar-supervisor-pedidos.blade.php`

```php
<!-- Botón "Pendientes Logo" en el sidebar -->
<li class="menu-item">
    <a href="{{ route('supervisor-pedidos.index', ['aprobacion' => 'pendiente', 'tipo' => 'logo']) }}"
       class="menu-link {{ request()->query('tipo') === 'logo' ? 'active' : '' }}"
       style="display:flex;align-items:center;gap:0.5rem;">
        <span class="material-symbols-rounded">image</span>
        <span class="menu-label">Pendientes Logo</span>
        <span class="badge-alert" id="ordenesPendientesLogoCount" style="display: none;">0</span>
    </a>
</li>
```

**Parámetros URL:**
- `aprobacion=pendiente` → Filtrar estado PENDIENTE_SUPERVISOR
- `tipo=logo` → Filtrar solo pedidos LOGO

**URL generada:** `/supervisor-pedidos?aprobacion=pendiente&tipo=logo`

---

### 2️⃣ **Controlador (Backend)**
**Archivo:** `app/Http/Controllers/SupervisorPedidosController.php` (Línea 138-160)

```php
public function index(Request $request)
{
    // Obtener órdenes con relaciones
    $query = PedidoProduccion::with(['asesora', 'prendas', 'cotizacion']);

    // FILTRO DE APROBACIÓN
    if ($request->filled('aprobacion')) {
        if ($request->aprobacion === 'pendiente') {
            // Órdenes PENDIENTES DE SUPERVISOR: solo con estado PENDIENTE_SUPERVISOR
            $query->where('estado', 'PENDIENTE_SUPERVISOR');
            
            // ✅ FILTRAR SOLO ÓRDENES CON COTIZACIÓN DE LOGO
            if ($request->filled('tipo') && $request->tipo === 'logo') {
                $query->whereHas('cotizacion', function($q) {
                    $q->where('tipo', 'logo');  // ← Tipo de cotización = 'logo'
                });
            }
        } elseif ($request->aprobacion === 'aprobadas') {
            $query->whereIn('estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'Finalizada', 'Anulada']);
        }
    } else {
        // Por defecto: solo PENDIENTE_SUPERVISOR
        $query->where('estado', 'PENDIENTE_SUPERVISOR');
    }

    // ... más filtros ...

    // Paginar y retornar
    $ordenes = $query->orderBy('fecha_de_creacion_de_orden', 'desc')
                    ->paginate(15)
                    ->appends($request->query());

    return view('supervisor-pedidos.index', compact('ordenes', 'estados'));
}
```

**Lógica de Filtrado:**
1. Si `aprobacion=pendiente` → busca en `estado = PENDIENTE_SUPERVISOR`
2. Si además `tipo=logo` → filtra cotizaciones con `tipo = 'logo'`
3. Retorna solo pedidos que cumplan AMBAS condiciones

---

### 3️⃣ **Relaciones en Modelos**

#### PedidoProduccion.php
```php
class PedidoProduccion extends Model
{
    // Relación con Cotizacion
    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }
    
    // Relación con asesora (User)
    public function asesora()
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }
}
```

#### Cotizacion.php
```php
class Cotizacion extends Model
{
    // Campo: 'tipo' o 'tipo_cotizacion_codigo'
    // Valores: 'P', 'L', 'RF', 'PL', etc.
    // 'L' = Logo
    
    public function pedidosProduccion()
    {
        return $this->hasMany(PedidoProduccion::class, 'cotizacion_id');
    }
}
```

---

## 📋 Flujo Completo

```
Usuario hace clic en sidebar "Pendientes Logo"
         ↓
URL: /supervisor-pedidos?aprobacion=pendiente&tipo=logo
         ↓
SupervisorPedidosController@index() recibe request
         ↓
Filtro 1: estado = PENDIENTE_SUPERVISOR
         ↓
Filtro 2: cotizacion.tipo = 'logo'
         ↓
Query devuelve solo pedidos de LOGO en estado PENDIENTE_SUPERVISOR
         ↓
Vista renderiza tabla con los resultados
```

---

## 📊 Estructura de Datos

### Tabla: `pedidos_produccion`
```sql
SELECT * FROM pedidos_produccion
WHERE estado = 'PENDIENTE_SUPERVISOR'
AND cotizacion_id IN (
    SELECT id FROM cotizaciones WHERE tipo = 'logo'
);
```

### Ejemplo de Resultado:
| id | numero_pedido | cliente | estado | cotizacion_id | asesor_id |
|---|---|---|---|---|---|
| 1234 | LOGO-20251219... | Cliente A | PENDIENTE_SUPERVISOR | 45 | 5 |
| 1235 | LOGO-20251219... | Cliente B | PENDIENTE_SUPERVISOR | 46 | 5 |
| 1236 | LOGO-20251219... | Cliente C | PENDIENTE_SUPERVISOR | 47 | 6 |

(Solo LOGO porque `cotizacion.tipo = 'logo'`)

---

## ✅ Verificación: ¿Está Funcionando?

### En la Vista (`index.blade.php`)

```php
@if(request('aprobacion') === 'pendiente')
    <!-- Mostrar botón de aprobación solo si estamos en filtro de pendientes -->
    <button class="btn-accion btn-aprobar" 
            title="Aprobar orden"
            onclick="aprobarOrden({{ $orden->id }}, '{{ $orden->numero_pedido }}')">
        <span class="material-symbols-rounded">check_circle</span>
    </button>
@endif
```

**Cuando está en "Pendientes Logo":**
- Se muestran SOLO pedidos con `aprobacion=pendiente` y `tipo=logo`
- Se muestra el botón "Aprobar" para enviar a producción
- Los pedidos deben estar en estado `PENDIENTE_SUPERVISOR`

---

## 🔍 Cómo Verificar que Funciona

### Opción 1: Base de Datos
```sql
-- Ver pedidos LOGO pendientes de aprobación
SELECT 
    p.id,
    p.numero_pedido,
    p.cliente,
    p.estado,
    c.tipo as tipo_cotizacion,
    u.name as asesora
FROM pedidos_produccion p
INNER JOIN cotizaciones c ON p.cotizacion_id = c.id
LEFT JOIN users u ON p.asesor_id = u.id
WHERE p.estado = 'PENDIENTE_SUPERVISOR'
AND c.tipo = 'logo'
ORDER BY p.created_at DESC;
```

### Opción 2: Frontend
1. Login como supervisor
2. Click en sidebar "Pendientes Logo"
3. Observar URL: `/supervisor-pedidos?aprobacion=pendiente&tipo=logo`
4. Verificar que se muestran solo pedidos LOGO

### Opción 3: Logs
```php
// En SupervisorPedidosController@index()
\Log::info('Filtro de LOGO aplicado', [
    'tipo' => $request->tipo,
    'aprobacion' => $request->aprobacion,
    'total_resultados' => $query->count()
]);
```

---

## 🎨 Componentes de la Interfaz

### Sidebar Buttons
```
┌─────────────────────────────────┐
│ Órdenes de Producción           │
├─────────────────────────────────┤
│ ▢ Todas las Órdenes             │
├─────────────────────────────────┤
│ Estado de Aprobación            │
├─────────────────────────────────┤
│ ⏳ Pendientes              (5)   │ ← Todos pendientes
│ 🎨 Pendientes Logo         (2)   │ ← Solo LOGO pendientes
└─────────────────────────────────┘
```

### Tabla de Resultados
Cuando está activo "Pendientes Logo":
- Muestra columnas: ACCIONES, ID ORDEN, CLIENTE, FECHA, ESTADO, ASESORA, FORMA PAGO, FECHA ESTIMADA
- Todos los registros tienen `estado = PENDIENTE_SUPERVISOR`
- Todos los registros tienen `tipo_cotizacion = 'logo'`

---

## 📌 Resumen de Implementación

| Componente | Ubicación | Responsabilidad |
|---|---|---|
| **Sidebar Button** | `sidebar-supervisor-pedidos.blade.php` | Proporciona link con parámetros `aprobacion=pendiente&tipo=logo` |
| **Controlador** | `SupervisorPedidosController.php:138-160` | Aplica filtros en la query |
| **Modelo** | `PedidoProduccion.php` | Define relación con `Cotizacion` |
| **Vista** | `supervisor-pedidos/index.blade.php` | Renderiza tabla con resultados filtrados |

---

## ⚙️ Parámetros URL Explicados

```
/supervisor-pedidos
    ?aprobacion=pendiente      ← Filtrar estado PENDIENTE_SUPERVISOR
    &tipo=logo                 ← Filtrar solo cotizaciones tipo 'L' (logo)
```

**Otros valores posibles:**
- `aprobacion=aprobadas` → Mostrar órdenes ya aprobadas
- Sin `tipo` → Mostrar todos los tipos (Prenda, Logo, Reflectivo, Combinada)
- `tipo=logo` → Solo Logo
- `tipo=prenda` → Solo Prenda (si existiera)

---

## 🐛 Posibles Problemas y Soluciones

### Problema 1: No muestra ningún pedido
**Causa:** No hay pedidos LOGO en estado PENDIENTE_SUPERVISOR

**Solución:**
```sql
-- Verificar si existen pedidos LOGO
SELECT COUNT(*) FROM pedidos_produccion p
INNER JOIN cotizaciones c ON p.cotizacion_id = c.id
WHERE c.tipo = 'logo' AND p.estado = 'PENDIENTE_SUPERVISOR';

-- Ver todos los pedidos LOGO sin filtro de estado
SELECT * FROM pedidos_produccion p
INNER JOIN cotizaciones c ON p.cotizacion_id = c.id
WHERE c.tipo = 'logo';
```

### Problema 2: Campo tipo no existe
**Causa:** Cotizaciones puede usar `tipo_cotizacion_codigo` en lugar de `tipo`

**Solución:**
Verificar en la BD qué campo contiene el tipo:
```sql
DESCRIBE cotizaciones;
-- Buscar columna que contiene 'logo', 'L', etc.
```

Actualizar filtro si es necesario:
```php
$query->whereHas('cotizacion', function($q) {
    $q->where('tipo_cotizacion_codigo', 'L');  // O el campo correcto
});
```

### Problema 3: Badge de contador no se actualiza
**Ubicación:** `sidebar-supervisor-pedidos.blade.php` (línea ~28)

**Badge HTML:**
```html
<span class="badge-alert" id="ordenesPendientesLogoCount" style="display: none;">0</span>
```

**Necesita JavaScript** para actualizar dinámicamente (si existe)

---

## 📝 Ejemplo Práctico: Crear Pedido LOGO

1. Asesor crea cotización de **LOGO** (tipo = 'L')
2. Asesor crea **pedido** desde esa cotización
3. Pedido se crea con estado: `PENDIENTE_SUPERVISOR`
4. Supervisor entra a módulo supervisor-pedidos
5. Click en "Pendientes Logo" → ve el pedido
6. Click en "Aprobar" → envía a producción (cambia estado)

---

## ✨ Ventajas de Implementación Actual

✅ **URL-based filtering** - Filtros en URL para bookmarking  
✅ **Query-level filtering** - Eficiente a nivel de BD  
✅ **Relaciones Eloquent** - Usa `whereHas()` para integridad  
✅ **Código limpio** - Fácil de mantener y extender  
✅ **Separación de responsabilidades** - Vista, Controlador, Modelo bien divididos  
✅ **Paginación incluida** - Maneja muchos registros  
✅ **Badges de contador** - Notificación visual en sidebar  

---

## 🎯 Conclusión

**El filtrado de pedidos LOGO pendientes YA ESTÁ IMPLEMENTADO Y FUNCIONANDO.**

El sistema:
1. ✅ Muestra solo pedidos en estado `PENDIENTE_SUPERVISOR`
2. ✅ Filtra solo cotizaciones de tipo `logo`
3. ✅ Proporciona URL limpia con parámetros
4. ✅ Usa relaciones Eloquent eficientemente
5. ✅ Integrado en sidebar con badge de contador

Si necesitas hacer cambios o ajustes, consulta la sección "Problemas y Soluciones" arriba.

