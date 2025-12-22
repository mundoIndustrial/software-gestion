# 📦 Implementación: Tabla Procesos Pedidos Logo y Tabs de Filtrado

## ✅ Cambios Realizados

### 1. **Nueva Tabla: `procesos_pedidos_logo`**

Se creó la migración `2025_12_20_create_procesos_pedidos_logo_table.php` que incluye:

- **Campos:**
  - `id` - Primary Key
  - `logo_pedido_id` - FK a `logo_pedidos`
  - `area` - Enum con valores: `Creacion de orden`, `pendiente_confirmar_diseño`, `en_diseño`, `logo`, `estampado`
  - `observaciones` - Notas adicionales del proceso
  - `fecha_entrada` - Fecha en que pasó a esa área
  - `usuario_id` - FK a `users` (quién cambió el estado)
  - `timestamps` - created_at, updated_at

- **Índices:** 
  - FK a `logo_pedidos` con cascada en delete
  - FK a `users` con set null en delete

### 2. **Nuevo Modelo: `ProcesosPedidosLogo`**

Ubicación: `app/Models/ProcesosPedidosLogo.php`

**Métodos Útiles:**
```php
// Crear proceso inicial al crear un pedido logo
ProcesosPedidosLogo::crearProcesoInicial($logoPedidoId, $usuarioId);

// Cambiar el área de un pedido
ProcesosPedidosLogo::cambiarArea($logoPedidoId, 'en_diseño', 'En diseño del logo', $usuarioId);

// Obtener el área actual
$areaActual = ProcesosPedidosLogo::obtenerAreaActual($logoPedidoId);

// Obtener historial de áreas
$historial = $logoPedido->procesos()->orderBy('created_at', 'asc')->get();
```

### 3. **Actualización: Modelo `LogoPedido`**

Se agregó:
- **Relación:** `procesos()` para acceder a todos los procesos del pedido
- **Atributo:** `areaActual` para obtener el área actual del pedido

### 4. **Actualización: Controlador `PedidoProduccionController`**

En el método `crearLogoPedidoDesdeAnullCotizacion()`:
- Se crea automáticamente el proceso inicial con área `Creacion de orden` cuando se crea un pedido logo

En el método `index()`:
- Se agregó filtro `tipo='prendas'` para mostrar solo pedidos de prendas
- Se mantiene el filtro `tipo='logo'` para mostrar solo pedidos de logo
- Sin filtro de tipo muestra todos los pedidos (combinados)

### 5. **Actualización: Vista `asesores/pedidos/index.blade.php`**

**Nuevos Tabs:**
1. **Filtro por Tipo (nuevo):**
   - ✅ Todos - Muestra pedidos de prendas + logo
   - ✅ Prendas - Muestra solo pedidos de prendas
   - ✅ Logo - Muestra solo pedidos de logo

2. **Filtro por Estado (mejorado):**
   - Se reorganizó para mejor UX
   - Funcionan independientemente del filtro de tipo

**Área mejorada:**
- Si es pedido LOGO: muestra el área actual del pedido logo (del nuevo table procesos_pedidos_logo)
- Si es pedido normal: muestra el proceso actual del pedido de prendas

### 6. **Command para Inicializar Datos Existentes**

Ubicación: `app/Console/Commands/InitializeLogoPedidoProcesses.php`

Crea procesos iniciales para todos los pedidos logo existentes.

---

## 🚀 Instrucciones de Ejecución

### Paso 1: Ejecutar las migraciones

```bash
php artisan migrate
```

Esto creará la tabla `procesos_pedidos_logo`.

### Paso 2: Inicializar procesos para pedidos logo existentes

```bash
php artisan app:initialize-logo-pedido-processes
```

Esto creará un proceso inicial `Creacion de orden` para todos los pedidos logo que ya existen en la BD.

### Paso 3: Verificar en la vista

1. Ir a `asesores/pedidos`
2. Verás los nuevos tabs:
   - **Todos** (por defecto - muestra prendas + logos)
   - **Prendas** (solo prendas)
   - **Logo** (solo logos)
3. En la columna "Área" verás:
   - Para pedidos LOGO: "Creacion de orden" (u otra área)
   - Para pedidos normales: el proceso actual

---

## 📊 Flujo de Datos

```
Usuario crea pedido LOGO desde cotización
  ↓
PedidoProduccionController::crearLogoPedidoDesdeAnullCotizacion()
  ↓
Crea registro en logo_pedidos
  ↓
Crea proceso inicial con:
  - area: "Creacion de orden"
  - fecha_entrada: now()
  - usuario_id: auth()->id()
  ↓
Vista index.blade.php muestra el área en la columna "Área"
```

---

## 🔄 Cambiar el Área de un Pedido Logo

Cuando necesites cambiar el área de un pedido logo a otra (ej: "en_diseño"):

```php
use App\Models\ProcesosPedidosLogo;

ProcesosPedidosLogo::cambiarArea(
    $logoPedidoId, 
    'en_diseño',  // Nueva área
    'Se inició el diseño del logo',  // Observaciones opcionales
    $usuarioId // Usuario opcional
);
```

---

## 📋 Campos de Área Disponibles

```
'Creacion de orden'              ← Por defecto al crear
'pendiente_confirmar_diseño'     ← Esperando confirmación
'en_diseño'                      ← Se está diseñando
'logo'                           ← En producción de logo
'estampado'                      ← En estampado/impresión
```

---

## ✨ Ventajas

✅ **Separación clara:** Tabs para ver prendas o logos por separado  
✅ **Visualización combinada:** Por defecto ves todos los pedidos  
✅ **Rastreo de procesos:** Historial completo de áreas por las que pasó un pedido logo  
✅ **Sin romper vista:** La vista anterior sigue funcionando normalmente  
✅ **Escalable:** Fácil de agregar más áreas o campos en el futuro  

---

## 🛠️ Próximos Pasos (Opcionales)

1. **Panel de Control:** Crear vista para cambiar el área de un pedido logo
2. **Historial Visual:** Mostrar línea de tiempo con historial de áreas
3. **Notificaciones:** Alertar cuando un pedido cambia de área
4. **Reportes:** Generar reportes por área

