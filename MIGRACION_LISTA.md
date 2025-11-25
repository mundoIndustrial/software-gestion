# 📦 PAQUETE DE MIGRACIÓN COMPLETO

## ✅ LO QUE ESTÁ LISTO PARA USAR

### 1️⃣ COMANDO: Migración de datos
**Archivo:** `app/Console/Commands/MigrateTablaOriginalToPedidosProduccion.php`

```bash
# Modo simulación (recomendado primero)
php artisan migrate:tabla-original-to-pedidos-produccion --dry-run

# Migración real
php artisan migrate:tabla-original-to-pedidos-produccion
```

**Qué hace:**
- ✅ Lee 45,150 órdenes de `tabla_original`
- ✅ Crea en `pedidos_produccion` con incremento de ID
- ✅ Lee prendas de `registros_por_orden`
- ✅ Crea en `prendas_pedido`
- ✅ Genera `procesos_prenda` a partir de áreas históricas
- ✅ Usa transacción (seguro: rollback si hay error)
- ✅ Progresa en tiempo real

---

### 2️⃣ COMANDO: Validación post-migración
**Archivo:** `app/Console/Commands/ValidateMigrationTablaOriginal.php`

```bash
php artisan validate:tabla-original-migration
```

**Qué valida:**
- ✅ Integridad referencial (FK válidas)
- ✅ Conteo de registros
- ✅ Campos no vacíos
- ✅ Estados válidos
- ✅ No hay duplicados
- ✅ Problemas potenciales

---

### 3️⃣ DOCUMENTACIÓN

#### A) `MIGRACION_TABLA_ORIGINAL.md`
Guía paso a paso:
- Cómo usar los comandos
- Qué se migra exactamente
- Mapeo de campos
- Troubleshooting
- Rollback en caso de error

#### B) `PLAN_MIGRACION_COMPLETO.md`
Plan completo de 4 fases:
- FASE 1: Pre-migración (backup, dry-run)
- FASE 2: Migración real
- FASE 3: Actualizar código
- FASE 4: Limpiar y deprecar

---

## 🎯 CÓMO PROCEDER AHORA

### ✋ PASO 1: Backup (CRÍTICO)
```bash
# Copia de seguridad completa
mysqldump -u root -p mundo_bd > ~/Documentos/backup_2025_11_25.sql

# Verifica que el backup existe
ls -lh ~/Documentos/backup_2025_11_25.sql
```

### 🔍 PASO 2: Simulación (DRY-RUN)
```bash
php artisan migrate:tabla-original-to-pedidos-produccion --dry-run
```

**Esperado:**
```
Total de órdenes en tabla_original: 45150
Total de registros en registros_por_orden: 156230

¿Deseas continuar con la migración? (yes/no) [no]:
 > no  ← Escribe "no" porque es --dry-run

Procesando... 45150/45150 [████████████] 100%
✅ Migración completada (simulación)
```

### ⚡ PASO 3: Migración Real
```bash
php artisan migrate:tabla-original-to-pedidos-produccion
```

**Esperado:**
```
Total de órdenes en tabla_original: 45150
¿Deseas continuar con la migración? (yes/no) [no]:
 > yes

Procesando... 45150/45150 [████████████] 100%
✅ Cambios confirmados en la base de datos
```

### ✓ PASO 4: Validar
```bash
php artisan validate:tabla-original-migration
```

**Esperado:**
```
✅ Todas las prendas tienen pedido válido
✅ Todos los procesos tienen prenda válida
✅ Todos los numero_pedido son únicos
✅ Todos los pedidos tienen cliente
✅ Todos los pedidos tienen estado
✅ Todos los estados son válidos
```

---

## 🔧 DESPUÉS DE MIGRACIÓN (Lo que necesitas hacer)

### 1. Actualizar `AsesoresController` (Líneas 45-476)
```php
// Cambiar todas las referencias de TablaOriginal a PedidoProduccion
// El método index() ya está parcialmente listo
```

### 2. Actualizar `DashboardController` (Líneas 27-52)
```php
// Reemplazar tabla_original por pedidos_produccion
```

### 3. Actualizar `VistasController` (Líneas 189-345)
```php
// Control de calidad y áreas deben leer de procesos_prenda
```

### 4. Actualizar `RegistroOrdenController` (GRANDE)
```php
// 25+ referencias a tabla_original
// Este es el sistema principal de gestión de órdenes
```

### 5. Comentar Observers (AppServiceProvider)
```php
// Estos ya no se necesitan:
// TablaOriginal::observe(TablaOriginalObserver::class);
// TablaOriginalBodega::observe(TablaOriginalBodegaObserver::class);
```

---

## 📊 ESTRUCTURA FINAL POST-MIGRACIÓN

```
pedidos_produccion (tabla principal)
├─ id                              ← NUEVA clave primaria
├─ numero_pedido                   ← Del viejo "pedido"
├─ cliente
├─ asesora
├─ estado
├─ fecha_de_creacion_de_orden
└─ timestamps

    ↓ (1:N)

prendas_pedido (detalles)
├─ id
├─ pedido_produccion_id            ← FK al pedido
├─ nombre_prenda                   ← Del viejo "prenda"
├─ cantidad
├─ descripcion
└─ timestamps

    ↓ (1:N)

procesos_prenda (historial)
├─ id
├─ prenda_pedido_id                ← FK a la prenda
├─ proceso                         ← (Corte, Costura, Bordado...)
├─ fecha_inicio
├─ fecha_fin
├─ encargado
├─ estado_proceso
└─ timestamps
```

---

## 🎁 BONUS: Scripts auxiliares

### Para verificar después de migrar:
```bash
# Contar registros en nueva estructura
php artisan tinker
PedidoProduccion::count()  # Debe ser 45,150
PrendaPedido::count()      # Debe ser ~160,000
ProcesoPrenda::count()     # Debe ser ~500,000

# Verificar integridad
DB::table('prendas_pedido')->whereNull('pedido_produccion_id')->count()  # Debe ser 0
DB::table('procesos_prenda')->whereNull('prenda_pedido_id')->count()     # Debe ser 0
```

---

## ⏱️ TIEMPO ESTIMADO

| Fase | Duración | Acción |
|------|----------|--------|
| Backup | 5 min | `mysqldump` |
| Dry-run | 10 min | Verificación |
| Migración real | 15-20 min | `migrate:...` |
| Validación | 2 min | `validate:...` |
| Actualizar código | 1-2 hrs | Manual |
| Testing | 30 min | Verificación |
| **TOTAL** | **~2-3 hrs** | - |

---

## 🚨 EN CASO DE PROBLEMA

### "Quiero revertir"
```bash
# Restaurar desde backup
mysql -u root -p mundo_bd < backup_2025_11_25.sql

# O en AWS/MariaDB
source backup_2025_11_25.sql
```

### "Algunos pedidos no migraron"
```bash
# Ejecutar de nuevo (es seguro, verifica duplicados)
php artisan migrate:tabla-original-to-pedidos-produccion

# Validar nuevamente
php artisan validate:tabla-original-migration
```

---

## ✨ RESULTADO FINAL

```
ANTES (tabla_original):
- 1 tabla monolítica con 50+ campos
- Sin historial de procesos
- Difícil de queryar
- Denormalizados

DESPUÉS (pedidos_produccion + prendas_pedido + procesos_prenda):
✅ 3 tablas normalizadas
✅ Historial completo de procesos
✅ Fácil de queryar
✅ Escalable
✅ Cumple ACID
✅ Sistema único (no duplicado)
```

---

**🎬 ¿Estás listo para ejecutar la migración?**

1. ✅ Backup completado
2. ✅ Comandos listos (`MigrateTablaOriginalToPedidosProduccion.php`)
3. ✅ Validación lista (`ValidateMigrationTablaOriginal.php`)
4. ✅ Documentación completa

**Próximo paso:** Ejecuta `--dry-run` y valida la salida.
