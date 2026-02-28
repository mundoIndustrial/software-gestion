# ✅ SOLUCIÓN: Prevención de Duplicación de Procesos en Recibos

## 🎯 Problema Identificado
En ambas URLs (`/recibos-costura` y `/recibos-reflectivo`), cuando se hacía clic en "Agregar Proceso", el sistema **siempre creaba un proceso nuevo** sin verificar si ya existía uno del mismo tipo para esa prenda, generando duplicados.

**Ubicación**: Endpoint `/seguimiento-proceso/guardar` en `ProcesoSeguimientoController`

---

## 💡 Solución Implementada: Patrón "Upsert"

### 1️⃣ **Backend - Lógica de Verificación (PHP)**

**Archivo**: [`app/Http/Controllers/ProcesoSeguimientoController.php`](../../app/Http/Controllers/ProcesoSeguimientoController.php)

**Cambio**: Método `guardar()` ahora:
1. **Verifica si existe un proceso activo** del mismo tipo (área) para esa prenda:
```php
$procesoExistente = ProcesoPrenda::where([
    ['numero_pedido', '=', $request->pedido_produccion_id],
    ['prenda_pedido_id', '=', $request->prenda_id],
    ['proceso', '=', $request->area],
    ['estado_proceso', '!=', 'Completado']  // ← Ignora completados
])->first();
```

2. **Si existe**: Actualiza el proceso existente (encargado, estado, observaciones)
3. **Si NO existe**: Crea uno nuevo

4. **Retorna**: Un indicador de qué acción se realizó (`'creado'` o `'actualizado'`)

### 2️⃣ **Frontend - Feedback Mejorado (JavaScript)**

**Archivos actualizados**:
- [`resources/views/registros/recibos-reflectivo.blade.php`](../../resources/views/registros/recibos-reflectivo.blade.php) - Línea ~870
- [`resources/views/registros/recibos-costura.blade.php`](../../resources/views/registros/recibos-costura.blade.php) - Línea ~1080
- [`public/js/ordersjs/tracking-modal-handler.js`](../../public/js/ordersjs/tracking-modal-handler.js) - Línea ~2591

**Cambio**: El message ahora varía según la acción:
```javascript
const mensaje = result.action === 'actualizado' 
    ? 'Proceso actualizado correctamente' 
    : 'Proceso agregado correctamente';
showSuccess(mensaje);
```

### 3️⃣ **Database - Protección a Nivel de BD**

**Archivo**: [`database/migrations/2026_02_27_add_unique_proceso_prenda.php`](./2026_02_27_add_unique_proceso_prenda.php)

**Lo que hace**:
- Crea un índice UNIQUE en la tabla `procesos_prenda`:
  - Columnas: `numero_pedido`, `prenda_pedido_id`, `proceso`
  - Efecto: Solo permite **un proceso activo por área/proceso por prenda**
- Limpia duplicados existentes (mantiene el más reciente)

---

## 🔄 Comportamiento Después de la Solución

### Escenario 1: Primer agregado
```
Usuario hace clic en "Agregar Proceso" → "Costura"
✅ Se CREA un nuevo proceso en procesos_prenda
✅ Muestra: "Proceso agregado correctamente"
```

### Escenario 2: Ya existe
```
Usuario intenta agregar el mismo proceso (misma área) para la prenda
✅ Se ACTUALIZA el proceso existente (encargado, estado)
✅ Muestra: "Proceso actualizado correctamente"
✅ NO se crea duplicado
```

### Escenario 3: Diferente área
```
Usuario agrega "Bordado" cuando ya existe "Costura"
✅ Se CREA un nuevo proceso (son procesos diferentes)
✅ Se pueden tener múltiples procesos de diferentes áreas
```

---

## 🚀 Cómo Aplicar

### Opción A: Migración Automática
```bash
php artisan migrate
```

### Opción B: Manual (si prefieres revisar primero)
1. Ejecutar la migración: `php artisan migrate --path=database/migrations/2026_02_27_add_unique_proceso_prenda.php`
2. El sistema está 100% funcional incluso sin la migración (la lógica PHP es la protección principal)

---

## ✨ Beneficios

| Aspecto | Antes | Después |
|--------|-------|---------|
| Duplicados | ❌ Se creaban siempre | ✅ Se reutilizan y actualizan |
| UX | Confuso | ✅ Mensajes claros |
| Base de Datos | ❌ Inconsistente | ✅ Protegida por índice único |
| Performance | Más registros = más lento | ✅ Menos registros, más rápido |

---

## 📋 Testing Manual

Para verificar que funciona correctamente:

1. **En `/recibos-costura` o `/recibos-reflectivo`**:
   - Haz clic en agregar proceso para un área
   - Verifica el mensaje: "Proceso agregado correctamente"
   - Vuelve a intentar para la MISMA área y prenda
   - Verifica el mensaje: "Proceso actualizado correctamente"
   - Verifica en BD que solo hay UN registro, no dos

2. **En la BD**:
   ```sql
   SELECT COUNT(*), numero_pedido, prenda_pedido_id, proceso 
   FROM procesos_prenda 
   GROUP BY numero_pedido, prenda_pedido_id, proceso 
   HAVING COUNT(*) > 1;
   ```
   Debería retornar 0 filas después de la migración.

---

## 🔍 Logs para Debugging

Si necesitas verificar qué pasó:
```bash
tail -f storage/logs/laravel.log | grep ProcesoSeguimientoController
```

Busca las líneas que dicen:
- `ACTUALIZANDO proceso existente` ← Se reutilizó
- `CREANDO nuevo proceso` ← Se creó nuevo
