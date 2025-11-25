# 📋 Script de Migración: tabla_original → pedidos_produccion

## 🎯 Propósito

Migrar **todos los datos históricos** de `tabla_original` a la nueva estructura normalizada:
- `pedidos_produccion` (pedidos principales)
- `prendas_pedido` (detalles de prendas)
- `procesos_prenda` (historial de procesos)

## 📊 Qué se migra

### De `tabla_original`:
- ✅ `pedido` → `numero_pedido`
- ✅ `cliente`
- ✅ `asesora`
- ✅ `forma_de_pago`
- ✅ `estado`
- ✅ `fecha_de_creacion_de_orden`
- ✅ `dia_de_entrega`
- ✅ `fecha_estimada_de_entrega`
- ✅ `novedades`

### De `registros_por_orden`:
- ✅ `prenda` → `nombre_prenda` en `prendas_pedido`
- ✅ `cantidad`
- ✅ `descripcion`

### Campos de Proceso (generados automáticamente):
```
corte          → ProcesoPrenda(proceso='Corte')
bordado        → ProcesoPrenda(proceso='Bordado')
estampado      → ProcesoPrenda(proceso='Estampado')
costura        → ProcesoPrenda(proceso='Costura')
reflectivo     → ProcesoPrenda(proceso='Reflectivo')
lavanderia     → ProcesoPrenda(proceso='Lavandería')
arreglos       → ProcesoPrenda(proceso='Arreglos')
control_de_calidad → ProcesoPrenda(proceso='Control Calidad')
entrega        → ProcesoPrenda(proceso='Entrega')
despacho       → ProcesoPrenda(proceso='Despacho')
```

## 🚀 Cómo usar

### 1️⃣ Modo DRY-RUN (recomendado primero)
```bash
php artisan migrate:tabla-original-to-pedidos-produccion --dry-run
```

✅ **Ventajas:**
- Simula la migración sin cambiar la BD
- Muestra cantidad de registros a migrar
- Identifica errores antes de ejecutar

### 2️⃣ Ejecutar la migración real
```bash
php artisan migrate:tabla-original-to-pedidos-produccion
```

⚠️ **Advertencia:**
- Hará cambios reales en la BD
- Se usa transacción (rollback si hay error)
- Toma tiempo con muchos registros

## 📈 Ejemplo de salida

```
╔════════════════════════════════════════════════════════╗
║  Migración: tabla_original → pedidos_produccion       ║
╚════════════════════════════════════════════════════════╝

📊 Analizando datos...

Total de órdenes en tabla_original: 45,150
Total de registros en registros_por_orden: 156,230

¿Deseas continuar con la migración? (yes/no) [no]:
 > yes

Procesando... 45150/45150 [████████████████████] 100%

═══════════════════════════════════════════════════════
✅ Migración completada
═══════════════════════════════════════════════════════
Órdenes migradas: 45,150
Errores: 0

✅ Cambios confirmados en la base de datos
```

## ⚙️ Lógica del script

### 1. Lectura de `tabla_original`
Obtiene cada orden con sus campos principales.

### 2. Creación de `PedidoProduccion`
```php
PedidoProduccion::create([
    'numero_pedido' => $orden->pedido,
    'cliente' => $orden->cliente,
    'asesora' => $orden->asesora,
    // ... otros campos
]);
```

### 3. Lectura de `registros_por_orden`
Para cada prenda del pedido, obtiene detalles.

### 4. Creación de `PrendaPedido`
```php
PrendaPedido::create([
    'pedido_produccion_id' => $pedido->id,
    'nombre_prenda' => $registro->prenda,
    'cantidad' => $registro->cantidad,
]);
```

### 5. Generación de `ProcesoPrenda`
Reconvierte los campos de área/fechas en procesos:

```php
// Creación Orden
ProcesoPrenda::create(['proceso' => 'Creación Orden', ...]);

// Luego, para cada campo con fecha (ej: corte, costura)
if ($orden->corte) {
    ProcesoPrenda::create(['proceso' => 'Corte', 'fecha_inicio' => $orden->corte, ...]);
}
```

## 🔄 Después de la migración

### Paso 1: Verificar datos
```bash
# Contar registros migrados
php artisan tinker
PedidoProduccion::count()  // Debe ser = a TablaOriginal::count()
```

### Paso 2: Actualizar controladores
Cambiar referencias de `TablaOriginal` a `PedidoProduccion`:
- ❌ `$pedidos = TablaOriginal::all();`
- ✅ `$pedidos = PedidoProduccion::all();`

### Paso 3: Deprecar tabla_original
Opciones:
1. **Mantener como historial:** Agregar middleware de solo lectura
2. **Eliminar:** Si no necesitas historial
3. **Archivar:** Mover a tabla separada `tabla_original_backup`

## ⚠️ Consideraciones importantes

### ❌ Qué NO se migra
- `cotizacion_id` (será null en los históricos)
- Relaciones con cotizaciones
- Datos que no existe mapeo

### ✅ Validaciones
- No migra registros duplicados (verifica `numero_pedido`)
- Usa transacciones (si falla, rollback)
- Crea procesos automáticamente desde áreas

### 🔒 Integridad referencial
- Las FK se crean correctamente
- Si falla una prenda, se registra el error
- Continúa con el siguiente pedido

## 🐛 Troubleshooting

### "No hay datos para migrar"
```bash
# Verificar si tabla_original está vacía
php artisan tinker
TablaOriginal::count()
```

### "Error: Duplicate entry"
```bash
# Algunos pedidos ya fueron migrados
# Ejecutar de nuevo es seguro (verifica duplicados)
```

### "Memory exceeded"
```bash
# Ejecutar en chunks si es muy grande
# El script ya lo hace (chunks de 100)
# Si aún falla, dividir por rango de fechas
```

## 📋 Rollback (si es necesario)

Si necesitas revertir:
```bash
# Opción 1: Restaurar backup
mysql mundo_bd < backup.sql

# Opción 2: Eliminar datos migrados
DELETE FROM procesos_prenda;
DELETE FROM prendas_pedido;
DELETE FROM pedidos_produccion WHERE cotizacion_id IS NULL;
```

## 🎯 Siguiente paso

Una vez migrado exitosamente:
1. Actualizar todos los controladores
2. Deprecar `TablaOriginal` model
3. Eliminar references en vistas
4. Dropear o archivar `tabla_original`

---

**Creado:** 2025-11-25  
**Versión:** 1.0  
**Comando:** `migrate:tabla-original-to-pedidos-produccion`
