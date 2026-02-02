# 📋 ANÁLISIS: Lógica de Costura-Reflectivo

## ✅ RESUMEN: ¿Existe la lógica?

**SÍ, la lógica existe y está implementada**, pero con algunas peculiaridades:

---

## 🔍 FLUJO ACTUAL (Lo que está implementado)

### 1️⃣ **FASE 1: Creación de Pedido desde Cotización Reflectivo**

**Ubicación:** [app/Domain/Pedidos/Services/ProcesosPedidoService.php](app/Domain/Pedidos/Services/ProcesosPedidoService.php#L15-L73)

Cuando se crea un pedido desde una cotización tipo **REFLECTIVO**:

```
Pedido Reflectivo Creado
         ↓
Procesos se crean automáticamente para CADA prenda:

1. Proceso: "Creación de Orden"
   - Estado: "En Progreso"
   - Encargado: [Nombre de la asesora logueada]
   - Fecha: Ahora

2. Proceso: "Costura" ⭐
   - Estado: "En Progreso"
   - Encargado: "Ramiro" (HARDCODED - NO ES DINÁMICO)
   - Observaciones: "Asignado automáticamente a Ramiro para cotización reflectivo"
```

---

### 2️⃣ **FASE 2: Aprobación por Supervisor de Pedidos**

**Ubicación:** [app/Http/Controllers/SupervisorPedidosController.php](app/Http/Controllers/SupervisorPedidosController.php#L421-L460)

Cuando el supervisor aprueba un pedido **REFLECTIVO**:

```php
if ($esReflectivo) {
    // Para pedidos reflectivos: estado "En Ejecución" y área "Costura"
    $orden->update([
        'aprobado_por_supervisor_en' => now(),
        'estado' => 'En Ejecución',
        'area' => 'Costura',
    ]);
}
```

El pedido pasa directamente a:
- ✅ Estado: **"En Ejecución"**
- ✅ Área: **"Costura"**
- ✅ Saltando la fase de INSUMOS

---

### 3️⃣ **FASE 3: Usuario Especial "Costura-Reflectivo"**

**Ubicación:** [database/seeders/CrearUsuarioCosturaReflectivoSeeder.php](database/seeders/CrearUsuarioCosturaReflectivoSeeder.php#L1-L50)

Existe un usuario creado específicamente:
- **Nombre:** `Costura-Reflectivo`
- **Email:** `costura-reflectivo@mundoindustrial.com`
- **Rol:** `costurero`

---

### 4️⃣ **FASE 4: Filtrado de Pedidos para Usuario "Costura-Reflectivo"**

**Ubicación:** [app/Application/Operario/Services/ObtenerPedidosOperarioService.php](app/Application/Operario/Services/ObtenerPedidosOperarioService.php#L26-L145)

Cuando el usuario **"Costura-Reflectivo"** inicia sesión:

```php
if (strtolower(trim($usuario->name)) === 'costura-reflectivo') {
    return $this->obtenerPedidosCosturaReflectivo($usuario);
}
```

Se ejecuta una lógica especial que **filtra pedidos por:**
1. ✅ Área = `"Costura"` (en tabla `pedidos_produccion`)
2. ✅ Estado = `"En Ejecución"`
3. ✅ Tengan proceso `"Costura"` con encargado = `"Ramiro"`

```php
private function tieneProcesoRamiro($pedido): bool
{
    $procesos = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
        ->where('proceso', 'Costura')
        ->get();

    foreach ($procesos as $proceso) {
        if (strtolower(trim($proceso->encargado)) === 'ramiro') {
            return true;
        }
    }

    return false;
}
```

---

## ⚠️ PROBLEMAS IDENTIFICADOS

### 1. **Proceso "Costura" está HARDCODEADO a "Ramiro"**

```php
// ProcesosPedidoService.php (línea 53)
$procsCostura = ProcesoPrenda::create([
    'numero_pedido' => $pedido->numero_pedido,
    'prenda_pedido_id' => $prenda->id,
    'proceso' => 'Costura',
    'encargado' => 'Ramiro',  // ⚠️ HARDCODEADO
    'estado_proceso' => 'En Progreso',
    'fecha_inicio' => now(),
    'observaciones' => 'Asignado automáticamente a Ramiro para cotización reflectivo',
]);
```

**Debería ser:**
```php
'encargado' => 'Costura-Reflectivo',  // O usar el usuario del sistema
```

### 2. **Inconsistencia en la Lógica de Filtrado**

En `ObtenerPedidosOperarioService.php`, busca procesos con `encargado = 'Ramiro'`:

```php
$procesos = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
    ->where('proceso', 'Costura')
    ->get();

foreach ($procesos as $proceso) {
    if (strtolower(trim($proceso->encargado)) === 'ramiro') {
        return true;
    }
}
```

**Pero el usuario "Costura-Reflectivo" NO es "Ramiro"**, entonces:
- ❌ Los procesos están asignados a `"Ramiro"`
- ❌ El usuario especial es `"Costura-Reflectivo"`
- ❌ **Mismatch entre lo que se crea y lo que se filtra**

---

## 🎯 RECOMENDACIONES

### Opción 1: USAR EL USUARIO ESPECIAL (RECOMENDADO)

**Cambiar:**
```php
// ProcesosPedidoService.php - línea 53
'encargado' => 'Costura-Reflectivo',  // En lugar de 'Ramiro'
```

**Y actualizar el filtrado:**
```php
// ObtenerPedidosOperarioService.php
foreach ($procesos as $proceso) {
    if (strtolower(trim($proceso->encargado)) === 'costura-reflectivo') {
        return true;
    }
}
```

### Opción 2: USAR DATOS DINÁMICOS DEL USUARIO

Buscar el usuario "Costura-Reflectivo" en la BD y usar su ID:
```php
$costuraReflectivo = User::where('name', 'Costura-Reflectivo')->first();

$procsCostura = ProcesoPrenda::create([
    'numero_pedido' => $pedido->numero_pedido,
    'prenda_pedido_id' => $prenda->id,
    'proceso' => 'Costura',
    'encargado' => $costuraReflectivo?->name ?? 'Costura-Reflectivo',
    'usuario_asignado_id' => $costuraReflectivo?->id,  // Nuevo campo
    // ...
]);
```

---

## 📊 TABLA PROCESOS_PRENDA - Ejemplo

Para un pedido reflectivo aprobado:

| id | numero_pedido | prenda_pedido_id | proceso | encargado | estado_proceso | fecha_inicio | observaciones |
|---|---|---|---|---|---|---|---|
| 1 | 45807 | 100 | Creación de Orden | Ana García | En Progreso | 2026-02-01 | Asignado automáticamente... |
| 2 | 45807 | 100 | Costura | **Ramiro** ⚠️ | En Progreso | 2026-02-01 | Asignado automáticamente a Ramiro... |

**Debería ser:**

| id | numero_pedido | prenda_pedido_id | proceso | encargado | estado_proceso | fecha_inicio | observaciones |
|---|---|---|---|---|---|---|---|
| 1 | 45807 | 100 | Creación de Orden | Ana García | En Progreso | 2026-02-01 | Asignado automáticamente... |
| 2 | 45807 | 100 | Costura | **Costura-Reflectivo** ✅ | En Progreso | 2026-02-01 | Asignado automáticamente... |

---

## 🔗 ARCHIVOS RELACIONADOS

1. **Creación de procesos:** [app/Domain/Pedidos/Services/ProcesosPedidoService.php](app/Domain/Pedidos/Services/ProcesosPedidoService.php)
2. **Aprobación supervisor:** [app/Http/Controllers/SupervisorPedidosController.php](app/Http/Controllers/SupervisorPedidosController.php#L421-L460)
3. **Usuario especial:** [database/seeders/CrearUsuarioCosturaReflectivoSeeder.php](database/seeders/CrearUsuarioCosturaReflectivoSeeder.php)
4. **Filtrado de pedidos:** [app/Application/Operario/Services/ObtenerPedidosOperarioService.php](app/Application/Operario/Services/ObtenerPedidosOperarioService.php#L26-L145)

---

## ✨ CONCLUSIÓN

**La lógica existe pero está INCOMPLETA.**

El flujo es:
1. ✅ Supervisor aprueba pedido reflectivo → Pasa a estado "En Ejecución", área "Costura"
2. ✅ Procesos se crean automáticamente
3. ❌ **PERO el encargado "Ramiro" NO coincide con el usuario "Costura-Reflectivo"**

**Necesita corrección para que el usuario "Costura-Reflectivo" realmente reciba los pedidos reflectivos.**
