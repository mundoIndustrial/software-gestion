# 🚀 GUÍA DE EJECUCIÓN - Normalización de Prendas

## ⚡ RESUMEN RÁPIDO

Tu tabla `prendas_pedido` tiene campos **MEZCLADOS** (variantes + datos básicos). Vamos a **NORMALIZARLA** creando una tabla hija `prenda_variantes`.

```
ANTES:  prendas_pedido (1 registro = 1 prenda con color/tela/manga/broche/bolsillos mezclados)
DESPUÉS: 
  - prendas_pedido (1 registro = 1 prenda, datos básicos)
  - prenda_variantes (N registros por prenda = combinaciones específicas)
```

---

## 📋 PRE-REQUISITOS (5 min)

### 1️⃣ Backup de Seguridad

```bash
# En terminal/PowerShell
cd C:\Users\Usuario\Documents\mundoindustrial

# Backup completo de la BD
mysqldump -u root mundoindustrial > backup_2026_01_16.sql

# Verificar que se creó
ls -la backup_2026_01_16.sql
```

### 2️⃣ Verificar Estructura Actual

```bash
# En terminal
php artisan tinker

# Ejecutar
DB::table('prendas_pedido')->count()
DB::table('prendas_pedido')->first()
```

Debería retornar registros con campos como:
- `numero_pedido` (INT)
- `cantidad_talla` (JSON)
- `color_id`, `tela_id`, `tipo_manga_id`, etc.

### 3️⃣ Verificar que NO existe tabla prenda_variantes

```bash
php artisan tinker

# Ejecutar
Schema::hasTable('prenda_variantes')  # Debe retornar false
```

---

## ⚙️ EJECUCIÓN (10-15 min)

### PASO 1: Ejecutar Migraciones

```bash
php artisan migrate
```

✅ Verifica que ALL 3 migraciones se ejecuten en orden:
```
2026_01_16_normalize_prendas_pedido ........... [OK]
2026_01_16_create_prenda_variantes_table ..... [OK]
2026_01_16_migrate_prenda_variantes_data ..... [OK]
```

### PASO 2: Monitorear Logs

```bash
# En otra terminal, ver logs en tiempo real
tail -f storage/logs/laravel.log | grep -i "migration\|variante\|error"
```

Deberías ver:
```
✅ [Migración] Tabla prendas_pedido normalizada exitosamente
✅ [Migración] Tabla prenda_variantes creada exitosamente
🔄 [Migración de Datos] Iniciando migración de variantes a tabla hija...
📋 Procesando prenda: CAMISA POLO (ID: 1)
✅ [Migración de Datos] Completada
```

---

## ✅ VALIDACIÓN POST-MIGRACIÓN (10 min)

### 1️⃣ Verificar Estructura

```bash
php artisan tinker

# Verificar tabla prendas_pedido
DB::table('prendas_pedido')->first()
# Debe tener: pedido_produccion_id, nombre_prenda, descripcion, genero, de_bodega
# NO debe tener: numero_pedido, color_id, tela_id, cantidad, etc.

# Verificar tabla prenda_variantes
DB::table('prenda_variantes')->first()
# Debe tener: prenda_pedido_id, talla, cantidad, color_id, tela_id, etc.

# Contar registros
DB::table('prendas_pedido')->count()     # N prendas
DB::table('prenda_variantes')->count()   # M variantes (usualmente M > N)
```

### 2️⃣ Verificar Relaciones

```bash
php artisan tinker

# Importar modelos
use App\Models\PrendaPedido;
use App\Models\PedidoProduccion;

# Test relación
$prenda = PrendaPedido::first();
$prenda->variantes()->count()        # ✅ Debe retornar N
$prenda->pedidoProduccion->numero_pedido  # ✅ Debe retornar número

$pedido = PedidoProduccion::first();
$pedido->prendasPed()->count()       # ✅ Debe retornar M
```

### 3️⃣ Verificar Datos Migrados

```bash
php artisan tinker

# Ver una prenda con sus variantes
$prenda = PrendaPedido::with('variantes.color', 'variantes.tela')->first();

# Imprimir
$prenda->nombre_prenda
$prenda->cantidad_total  # Accessor (suma de variantes)
$prenda->variantes

# Ver una variante
$variante = PrendaVariante::first();
$variante->talla
$variante->cantidad
$variante->color->nombre
$variante->tela->nombre
```

---

## 🔍 VALIDACIÓN DETALLADA (Opcional)

### Integridad Referencial

```bash
php artisan tinker

# Debe retornar 0 (no hay orfandades)
DB::table('prendas_pedido')
  ->whereNotNull('pedido_produccion_id')
  ->leftJoin('pedidos_produccion', 'prendas_pedido.pedido_produccion_id', '=', 'pedidos_produccion.id')
  ->whereNull('pedidos_produccion.id')
  ->count()

# Debe retornar 0
DB::table('prenda_variantes')
  ->whereNotNull('prenda_pedido_id')
  ->leftJoin('prendas_pedido', 'prenda_variantes.prenda_pedido_id', '=', 'prendas_pedido.id')
  ->whereNull('prendas_pedido.id')
  ->count()
```

### Contar Variantes por Prenda

```bash
php artisan tinker

DB::table('prenda_variantes')
  ->select('prenda_pedido_id', DB::raw('COUNT(*) as variantes'))
  ->groupBy('prenda_pedido_id')
  ->having('variantes', '>', 5)  # Prendas con muchas variantes
  ->get()
```

---

## ⚠️ ROLLBACK (Si algo falla)

### Opción 1: Rollback automático

```bash
php artisan migrate:rollback --step=3
```

Esto revierte las 3 migraciones en orden inverso.

### Opción 2: Restaurar desde backup

```bash
# Detener servidor
# En terminal
mysql -u root mundoindustrial < backup_2026_01_16.sql

# Reiniciar
php artisan serve
```

---

## 🛠️ ACTUALIZAR CÓDIGO

Después de migración, actualizar cualquier código que use prendas:

### Buscar en Controllers/Services

```bash
# Buscar usos de numero_pedido en prendas
grep -r "numero_pedido" app/ --include="*.php" | grep -i prenda

# Cambiar por pedido_produccion_id
# Ej: from ->where('numero_pedido', $num) to ->where('pedido_produccion_id', $id)
```

### Ejemplo de Cambio

**ANTES:**
```php
$prendas = PrendaPedido::where('numero_pedido', $numeroPedido)->get();
```

**DESPUÉS:**
```php
$prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();
// O mejor aún:
$prendas = $pedido->prendasPed;
```

---

## 📊 CONSULTAS ÚTILES POST-MIGRACIÓN

### Obtener Todas las Prendas de un Pedido

```php
$pedido = PedidoProduccion::find($id);
$prendas = $pedido->prendasPed()->with('variantes.color', 'variantes.tela')->get();

// O con Eloquent
PrendaPedido::where('pedido_produccion_id', $id)
    ->with('variantes.color', 'variantes.tela')
    ->get();
```

### Obtener Cantidad Total de Prendas

```php
$prenda = PrendaPedido::find($id);
$cantidad = $prenda->cantidad_total;  // Accessor (suma automática)
```

### Obtener Tallas Disponibles

```php
$prenda = PrendaPedido::find($id);
$tallas = $prenda->obtenerTallasDisponibles();  // Collection de tallas únicas
```

### Obtener Cantidades por Talla

```php
$cantidadesPorTalla = $prenda->obtenerCantidadesPorTalla();
// Retorna: ['S' => 50, 'M' => 40, 'L' => 30, ...]
```

---

## 📞 TROUBLESHOOTING

### ❌ Error: "Referential integrity constraint violated"

**Causa**: Hay registros en `prendas_pedido` con `numero_pedido` que no existen en `pedidos_produccion`

**Solución**:
```bash
php artisan tinker

# Identificar orfandades
DB::table('prendas_pedido')
  ->leftJoin('pedidos_produccion', 'prendas_pedido.numero_pedido', '=', 'pedidos_produccion.numero_pedido')
  ->whereNull('pedidos_produccion.id')
  ->pluck('prendas_pedido.id')  # Ver IDs problemáticos

# Eliminar o asignar a pedido válido
```

### ❌ Error: "Table doesn't exist: prenda_variantes"

**Causa**: La migración 2 no se ejecutó

**Solución**:
```bash
php artisan migrate:status  # Ver qué migraciones faltaron
php artisan migrate          # Ejecutar todas
```

### ❌ Variantes no se crearon

**Causa**: Probablemente `cantidad_talla` estaba vacío

**Solución**:
```bash
php artisan tinker

DB::table('prendas_pedido')
  ->where('cantidad_talla', '!=', null)
  ->where('cantidad_talla', '!=', '{}')
  ->where('cantidad_talla', '!=', '[]')
  ->count()

# Ver ejemplos
DB::table('prendas_pedido')
  ->where('cantidad_talla', '!=', null)
  ->limit(5)
  ->pluck('cantidad_talla')
```

---

## 📚 DOCUMENTACIÓN COMPLETA

Ver archivos:
- `docs/REFACTORIZACION_PRENDAS_NORMALIZADAS.md` - Detalles técnicos
- `docs/CHECKLIST_IMPLEMENTACION_PRENDAS.md` - Checklist completo

---

## ✨ RESUMEN DE CAMBIOS

| Aspecto | ANTES | DESPUÉS |
|---------|-------|---------|
| **Estructura** | Mezclada (variantes + datos) | Normalizada (separado) |
| **FK Prenda** | `numero_pedido` (INT, no FK) | `pedido_produccion_id` (BIGINT FK) |
| **Variantes** | En `prendas_pedido` | En `prenda_variantes` (tabla hija) |
| **Cantidad** | Campo `cantidad` (redundante) | Calculada desde variantes (accessor) |
| **Escalabilidad** | Limitada | Excelente (múltiples variantes) |
| **Reflectivo** | Campo en tabla | Eliminado (OUT OF SCOPE) |

---

## ✅ CHECKLISTS FINALES

### Antes de Ejecutar

- [ ] Backup creado: `backup_2026_01_16.sql`
- [ ] Verificado: `prendas_pedido` tiene datos
- [ ] Verificado: `prenda_variantes` no existe
- [ ] Ambiente: No hay usuarios usando el sistema

### Después de Ejecutar

- [ ] Migraciones ejecutadas sin errores
- [ ] Tabla estructura verificada
- [ ] Relaciones testeadas
- [ ] Datos migrados correctamente
- [ ] Código actualizado (numero_pedido → pedido_produccion_id)
- [ ] Tests pasados
- [ ] Usuarios notificados

---

**¡Listo para ejecutar!** 🚀

Cualquier duda, revisar los documentos de soporte o ver logs en `storage/logs/laravel.log`
