# 🚀 PLAN DE MIGRACIÓN COMPLETO: tabla_original → pedidos_produccion

## ✅ Lo que ya está hecho

### 1. Comandos Artisan Creados
- ✅ `migrate:tabla-original-to-pedidos-produccion` - Migra los datos
- ✅ `validate:tabla-original-migration` - Valida la migración
- ✅ Documentación en `docs/MIGRACION_TABLA_ORIGINAL.md`

### 2. Modelos Existentes
- ✅ `PedidoProduccion` - Tabla principal de pedidos
- ✅ `PrendaPedido` - Detalles de prendas
- ✅ `ProcesoPrenda` - Historial de procesos

---

## 📋 PASOS A SEGUIR

### FASE 1: PRE-MIGRACIÓN (Hoy)

**1.1 Hacer Backup de BD**
```bash
# Backup completo
mysqldump -u root -p mundo_bd > backup_pre_migracion_$(date +%Y%m%d_%H%M%S).sql

# O desde Laravel
php artisan db:backup
```

**1.2 Ejecutar en Dry-Run**
```bash
php artisan migrate:tabla-original-to-pedidos-produccion --dry-run
```
📊 Esto te dirá:
- Total de órdenes a migrar
- Total de prendas a crear
- Total de procesos a crear
- Errores potenciales

**1.3 Revisar salida**
Si todo está OK, continuar a FASE 2.

---

### FASE 2: MIGRACIÓN (Producción)

**2.1 Ejecutar migración real**
```bash
php artisan migrate:tabla-original-to-pedidos-produccion
```

⏱️ **Tiempo estimado:**
- 45,000 órdenes: ~15-20 minutos
- Usa transacción (seguro)
- Confirmación al final

**2.2 Validar migración**
```bash
php artisan validate:tabla-original-migration
```

Debe mostrar:
```
✅ Todas las prendas tienen pedido válido
✅ Todos los procesos tienen prenda válida
✅ Todos los numero_pedido son únicos
✅ Todos los pedidos tienen cliente
✅ Todos los pedidos tienen estado
```

---

### FASE 3: ACTUALIZAR CÓDIGO (Aplicación)

**3.1 Actualizar `AsesoresController`**
```php
// ANTES:
$stats = [
    'pedidos_dia' => TablaOriginal::delAsesor($asesoraNombre)->delDia()->count(),
    ...
];

// DESPUÉS:
$stats = [
    'pedidos_dia' => PedidoProduccion::where('asesora', $asesoraNombre)
        ->whereDate('fecha_de_creacion_de_orden', today())->count(),
    ...
];
```

**3.2 Actualizar `DashboardController`**
```php
// Cambiar todas las referencias de tabla_original a pedidos_produccion
// Usar eager loading para procesos
```

**3.3 Actualizar `VistasController`**
```php
// Sistema de áreas debe leer de procesos_prenda
// No de tabla_original.area
```

**3.4 Actualizar `RegistroOrdenController`**
```php
// Este es el más crítico
// Tiene 25+ referencias a tabla_original
```

---

### FASE 4: LIMPIAR (Deprecación)

**4.1 Desactivar Observers**
```php
// En AppServiceProvider.php:
// Comentar estas líneas (ya no se necesitan)
// TablaOriginal::observe(TablaOriginalObserver::class);
// TablaOriginalBodega::observe(TablaOriginalBodegaObserver::class);
```

**4.2 Opción A: Mantener como historial (Recomendado)**
```sql
-- Hacer tabla_original read-only
ALTER TABLE tabla_original COMMENT='DEPRECATED: Datos históricos. Usar pedidos_produccion';

-- Crear vista para compatibilidad (si es necesario)
CREATE VIEW v_tabla_original_backup AS 
SELECT * FROM tabla_original;
```

**4.3 Opción B: Eliminar tabla**
```sql
-- Asegurarse de que no hay referencias activas
DROP TABLE tabla_original;
DROP TABLE registros_por_orden;
DROP TABLE entregas_pedido_costura;
```

---

## 🔄 ESTRUCTURA POST-MIGRACIÓN

```
┌──────────────────────────────────────────────────────┐
│   SISTEMA NUEVO (ACTIVO)                            │
├──────────────────────────────────────────────────────┤
│                                                      │
│  PedidoProduccion (pedidos_produccion)              │
│  ├─ numero_pedido                                   │
│  ├─ cliente                                         │
│  ├─ asesora                                         │
│  ├─ estado                                          │
│  └─ fecha_de_creacion_de_orden                      │
│       │                                              │
│       └──→ PrendaPedido (1:N) (prendas_pedido)      │
│            ├─ nombre_prenda                        │
│            ├─ cantidad                             │
│            └─ descripcion                          │
│                 │                                   │
│                 └──→ ProcesoPrenda (1:N)           │
│                      ├─ proceso (Corte, Costura...)
│                      ├─ fecha_inicio               │
│                      ├─ fecha_fin                  │
│                      ├─ encargado                  │
│                      └─ estado_proceso             │
│                                                     │
└──────────────────────────────────────────────────────┘
```

---

## 🧪 TESTING POST-MIGRACIÓN

**Verificar en cada módulo:**

1. **Asesores**
   ```bash
   # Ir a /asesores/pedidos
   # Debe mostrar los pedidos históricos migrados
   # El área actual debe venir de procesos_prenda
   ```

2. **Dashboard**
   ```bash
   # Ir a /dashboard
   # Estadísticas deben mostrar datos correctos
   ```

3. **Vistas de Áreas**
   ```bash
   # /vistas/corte (debe mostrar órdenes)
   # /vistas/costura
   # /vistas/control-calidad
   ```

4. **Entrega**
   ```bash
   # /entregas (debe permitir buscar y entregar)
   ```

---

## ⚡ CHECKLIST DE MIGRACIÓN

### Pre-Migración
- [ ] Backup de BD realizado
- [ ] Dry-run ejecutado sin errores
- [ ] Revisión de salida del dry-run
- [ ] Comunicar a usuarios

### Migración
- [ ] Ejecutar `migrate:tabla-original-to-pedidos-produccion`
- [ ] Esperar a que termine (sin interrumpir)
- [ ] Ejecutar `validate:tabla-original-migration`
- [ ] Revisar reporte de validación

### Post-Migración Inmediata
- [ ] Verificar que hay datos en `pedidos_produccion`
- [ ] Verificar que hay datos en `prendas_pedido`
- [ ] Verificar que hay datos en `procesos_prenda`
- [ ] Testear cada módulo

### Actualización de Código
- [ ] Actualizar `AsesoresController`
- [ ] Actualizar `DashboardController`
- [ ] Actualizar `VistasController`
- [ ] Actualizar `RegistroOrdenController`
- [ ] Actualizar vistas `.blade.php`
- [ ] Tests pasando

### Deprecación Final
- [ ] Comentar Observers de `TablaOriginal`
- [ ] Crear vista de historial (si se desea)
- [ ] Documentar cambios para el equipo

---

## 📞 CONTACTO EN CASO DE PROBLEMAS

Si ocurre un error durante la migración:

1. **Revisar logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Rollback (si no completó):**
   ```bash
   # La transacción se revierte automáticamente
   # O restaurar desde backup
   mysql mundo_bd < backup_pre_migracion.sql
   ```

3. **Validar después de rollback:**
   ```bash
   php artisan validate:tabla-original-migration
   ```

---

## 📊 ESTADÍSTICAS ESPERADAS

```
Tabla Original:               ~45,150 órdenes
Prendas por orden (promedio):  ~3.5
Total de prendas:            ~160,000

Procesos por prenda (promedio): 3-4
Total de procesos:           ~500,000

Tamaño de datos:
- tabla_original: ~50 MB
- pedidos_produccion: ~30 MB  (más normalizada)
- prendas_pedido: ~20 MB
- procesos_prenda: ~50 MB
```

---

## 🎯 RESULTADO FINAL

✅ **Una estructura de BD completamente normalizada:**
- ✅ Sin redundancia
- ✅ Sin violaciones ACID
- ✅ Con historial completo de procesos
- ✅ Escalable para futuro
- ✅ Sistema único (no duplicado)

**Próximos pasos:** 
1. Continuar con migraciones de otros módulos (bodega)
2. Implementar nuevas características
3. Optimizar queries con índices

---

**Documento:** 2025-11-25  
**Versión:** 1.0  
**Estado:** Listo para ejecutar
