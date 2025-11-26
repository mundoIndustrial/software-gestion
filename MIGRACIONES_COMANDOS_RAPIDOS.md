# ⚡ COMANDOS RÁPIDOS - MIGRACIONES

## 🎯 COMANDOS MÁS USADOS

### 🔍 Testear sin cambios
```bash
php artisan migrate:procesos-prenda --dry-run
```

### ✨ Ejecutar migración completa
```bash
php artisan migrate:procesos-prenda
```

### ✔️ Validar migración
```bash
php artisan migrate:validate
```

### 🔧 Corregir errores
```bash
php artisan migrate:fix-errors
```

### ↩️ Revertir migración
```bash
php artisan migrate:procesos-prenda --reset
```

---

## 📊 MATRIZ DE COMANDOS

| Comando | Propósito | Duración | Seguro? | Cuándo usar |
|---------|-----------|----------|---------|-------------|
| `--dry-run` | Simular | 2-3 min | ✅ Sí | Antes de migrar |
| Sin opciones | Ejecutar real | 5-10 min | ⚠️ Datos cambian | Después de validar --dry-run |
| `validate` | Verificar | 1 min | ✅ Sí | Después de migrar |
| `fix-errors` | Corregir | 2 min | ⚠️ Modifica | Si hay errores |
| `--reset` | Deshacer | 2 min | ⚠️ Elimina datos | Para empezar de nuevo |

---

## 🚀 FLUJO RECOMENDADO

```
┌─────────────────────────────────────┐
│ 1. php artisan migrate:procesos-prenda --dry-run
│    (Simular - NO cambia nada)
└────────────┬────────────────────────┘
             │ ✅ Sin errores?
             ▼
┌─────────────────────────────────────┐
│ 2. php artisan migrate:procesos-prenda
│    (Ejecutar migración REAL)
└────────────┬────────────────────────┘
             │
             ▼
┌─────────────────────────────────────┐
│ 3. php artisan migrate:validate
│    (Verificar integridad)
└────────────┬────────────────────────┘
             │ ⚠️ ¿Hay errores?
             ├─ ✅ NO → ¡LISTO!
             └─ ⚠️ SÍ → Continuar
                       │
                       ▼
        ┌──────────────────────────┐
        │ 4. php artisan migrate:fix-errors
        │    (Corregir errores)
        └──────────────┬───────────┘
                       │
                       ▼
        ┌──────────────────────────┐
        │ 5. php artisan migrate:validate
        │    (Validar nuevamente)
        └──────────────┬───────────┘
                       │ ✅ OK!
```

---

## 📁 ARCHIVOS RELACIONADOS

### Comandos (app/Console/Commands/)
- `MigrateProcessesToProcesosPrend.php` - Orquestador principal (1000+ líneas)
- `ValidateMigration.php` - Verificador de integridad
- `FixMigrationErrors.php` - Corrector de errores
- `RollbackProcessesMigration.php` - Revertidor

### Migraciones (database/migrations/)
- `2025_11_26_expand_nombre_prenda_field.php` - Expande campo nombre_prenda a TEXT

### Documentación
- `MIGRACIONES_DOCUMENTACION.md` - Guía técnica completa (400+ líneas)
- `MIGRACIONES_REFERENCIA_RAPIDA.md` - Referencia rápida
- `MIGRACIONES_GUIA_PASO_A_PASO.md` - Este archivo (guía ejecutable)

---

## ⚙️ OPCIONES DISPONIBLES

### Comando Principal: `migrate:procesos-prenda`

```bash
# Opción: --dry-run
# Simula sin hacer cambios
php artisan migrate:procesos-prenda --dry-run

# Opción: --reset
# Elimina todos los datos migrados (pedir confirmación)
php artisan migrate:procesos-prenda --reset

# Opción: -v (verbose)
# Muestra más detalles durante ejecución
php artisan migrate:procesos-prenda -v
```

### Comando: `migrate:validate`

```bash
# Sin opciones: mostrar estadísticas completas
php artisan migrate:validate

# Con -v: más detalles
php artisan migrate:validate -v
```

### Comando: `migrate:fix-errors`

```bash
# Sin opciones: intentar corregir todo
php artisan migrate:fix-errors

# Posibles correcciones:
# - Expandir campos demasiado largos
# - Limpiar fechas inválidas
# - Recalcular procesos incompletos
```

---

## 🎯 CASOS DE USO COMUNES

### 1️⃣ Primer uso (Migración inicial)
```bash
cd C:\Users\Usuario\Documents\proyecto\v10\mundoindustrial
php artisan migrate:procesos-prenda --dry-run
# Revisar output...
php artisan migrate:procesos-prenda
php artisan migrate:validate
```

### 2️⃣ Hubo error, necesito corregir
```bash
php artisan migrate:fix-errors
php artisan migrate:validate
```

### 3️⃣ Revertir y empezar de nuevo
```bash
php artisan migrate:procesos-prenda --reset
# Restaurar backup de BD si es necesario
php artisan migrate:procesos-prenda
```

### 4️⃣ Solo verificar estado actual
```bash
php artisan migrate:validate
```

### 5️⃣ Ver detalles de lo que haría
```bash
php artisan migrate:procesos-prenda --dry-run -v
```

---

## 📊 ESTADÍSTICAS ESPERADAS

Después de ejecutar `php artisan migrate:validate`:

```
📊 ESTADÍSTICAS DE MIGRACIÓN:
   Usuarios (Asesoras): 51
   Clientes: 965
   Pedidos: 2260
   Prendas: 2906
   Procesos: 17000
   
✅ COMPLETENESS: 76.46% (1728/2260 pedidos con todos los campos)
```

---

## ⚠️ MENSAJES DE ERROR COMUNES

| Mensaje | Causa | Solución |
|---------|-------|----------|
| `Data truncated for column` | Campo demasiado pequeño | `php artisan migrate:fix-errors` |
| `Duplicate entry` | Registro ya existe | Revisar datos, puede ser normal |
| `Foreign key constraint failed` | ID padre no existe | `php artisan migrate:fix-errors` |
| `Syntax error in datetime` | Fecha con formato inválido | `php artisan migrate:fix-errors` |
| `Access denied` | Permisos de BD | Revisar .env credentials |
| `Unknown database` | BD no existe | Crear BD primero |

---

## 🔐 PRECAUCIONES

⚠️ **ANTES DE MIGRAR**:
- [ ] Hacer BACKUP de la base de datos
- [ ] Probar con `--dry-run` primero
- [ ] Verificar conexión a BD
- [ ] Revisar espacio en disco

⚠️ **DURANTE LA MIGRACIÓN**:
- [ ] No cerrar la terminal
- [ ] No apagar la computadora
- [ ] No modificar BD manualmente
- [ ] No ejecutar otros comandos

⚠️ **DESPUÉS DE MIGRACIÓN**:
- [ ] Ejecutar `migrate:validate`
- [ ] Verificar datos en BD
- [ ] Probar funcionalidad en UI
- [ ] Hacer backup de datos migrados

---

## 🆘 SOPORTE RÁPIDO

**Problema**: No veo output
```bash
# Añade -v para verbose
php artisan migrate:procesos-prenda -v
```

**Problema**: Tarda mucho
```bash
# Normal: 5-10 minutos (migra 17000+ registros)
# Si tarda >30 min, revisar conexión BD
```

**Problema**: Se interrumpió
```bash
# Ver dónde paró con:
php artisan migrate:validate

# Luego ejecutar nuevamente:
php artisan migrate:procesos-prenda
```

**Problema**: Quiero revertir TODO
```bash
# Opción 1: Revertir con comando
php artisan migrate:procesos-prenda --reset

# Opción 2: Restaurar backup de BD
# (Usar phpMyAdmin o línea de comandos MySQL)
```

---

## 📞 CONTACTO / DOCUMENTACIÓN

- **Documentación técnica**: `MIGRACIONES_DOCUMENTACION.md`
- **Referencia rápida**: `MIGRACIONES_REFERENCIA_RAPIDA.md`
- **Guía paso a paso**: `MIGRACIONES_GUIA_PASO_A_PASO.md` ← Estás aquí
- **Logs**: `storage/logs/laravel.log`

---

**Última actualización**: 26 de Noviembre de 2025  
**Versión**: 1.0  
**Estado**: ✅ Listo para producción
