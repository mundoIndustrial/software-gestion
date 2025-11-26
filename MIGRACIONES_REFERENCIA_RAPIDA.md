# ⚡ REFERENCIA RÁPIDA DE MIGRACIONES

## 🎯 Comandos Principales

```bash
# 1. EJECUTAR MIGRACIÓN COMPLETA
php artisan migrate:procesos-prenda

# 2. SIMULAR MIGRACIÓN (sin cambios)
php artisan migrate:procesos-prenda --dry-run

# 3. VALIDAR MIGRACIÓN
php artisan migrate:validate

# 4. CORREGIR ERRORES
php artisan migrate:fix-errors

# 5. REVERTIR MIGRACIÓN
php artisan migrate:procesos-prenda --reset
```

---

## 📁 ARCHIVOS DE MIGRACIÓN

### **Comandos Artisan** (app/Console/Commands/)
| Archivo | Función |
|---------|---------|
| `MigrateProcessesToProcesosPrend.php` | Ejecuta migración completa (5 pasos) |
| `ValidateMigration.php` | Valida integridad de datos migrados |
| `FixMigrationErrors.php` | Corrige errores encontrados |
| `RollbackProcessesMigration.php` | Revierte migraciones |
| `AnalyzeDataMigration.php` | Analiza datos antes de migrar |

### **Migraciones BD** (database/migrations/)
| Archivo | Función |
|---------|---------|
| `2025_11_26_expand_nombre_prenda_field.php` | Expande campo nombre_prenda a TEXT |

---

## 🔄 FLUJO DE MIGRACION

```
tabla_original + registros_por_orden
        ↓
┌───────┴──────────┬──────────────┬──────────┐
│                  │              │          │
↓                  ↓              ↓          ↓
USUARIOS      CLIENTES        PEDIDOS    PRENDAS
(asesoras)                        +
                              PROCESOS
        ↓                          ↓
users              clientes    pedidos_       prendas_
                              produccion     pedido
                                        +
                                   procesos_
                                   prenda
```

---

## 📊 RESULTADOS

| Entidad | Creados | Estado |
|---------|---------|--------|
| Usuarios | 51 | ✅ |
| Clientes | 965 | ✅ |
| Pedidos | 2,260 | ✅ |
| Prendas | 2,906 | ✅ |
| Procesos | 17,000 | ✅ |

---

## ⚠️ NOTAS IMPORTANTES

- 527 pedidos sin asesor (datos nulos en origen)
- 7 pedidos sin cliente (datos nulos en origen)
- Campo `nombre_prenda` ahora es TEXT (permite descripciones largas)
- Todos los comandos son reversibles
- Usar `--dry-run` antes de ejecutar en producción

---

**Ver documentación completa en**: `MIGRACIONES_DOCUMENTACION.md`
