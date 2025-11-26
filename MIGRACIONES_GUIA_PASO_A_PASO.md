# 🚀 GUÍA PASO A PASO: MIGRACIONES

## ✅ CHECKLIST PRE-MIGRACIÓN

Antes de ejecutar la migración, verifica:

- [ ] Base de datos accesible
- [ ] Backup de BD realizado (CRÍTICO)
- [ ] Acceso a terminal/consola
- [ ] PHP 8.0+ instalado
- [ ] Laravel 10+ instalado

```bash
# Verificar versiones
php --version
php artisan --version
```

---

## 📋 PASO 1: ANALIZAR DATOS (OPCIONAL)

Analiza los datos antes de migrar para ver qué se hará:

```bash
cd C:\Users\Usuario\Documents\proyecto\v10\mundoindustrial

php artisan analyze:migration
```

**Output esperado**: Reporte detallado de qué se migrará

---

## 🧪 PASO 2: PROBAR EN MODO DRY-RUN

Simula la migración SIN hacer cambios reales:

```bash
php artisan migrate:procesos-prenda --dry-run
```

**Output**: Muestra lista de:
- ✅ Usuarios a crear
- ✅ Clientes a crear
- ✅ Pedidos a migrar
- ✅ Prendas a migrar
- ✅ Procesos a crear

**Tiempo esperado**: 2-3 minutos

---

## ✨ PASO 3: EJECUTAR MIGRACIÓN REAL

Una vez validado en dry-run, ejecuta la migración real:

```bash
php artisan migrate:procesos-prenda
```

**Output esperado**:
```
📋 PASO 1: Creando usuarios (asesoras)...
   ✅ Usuarios creados: X | Existentes: Y

📋 PASO 2: Creando clientes...
   ✅ Clientes creados: X | Existentes: Y

📋 PASO 3: Migrando pedidos...
   ✅ Pedidos migrados: X | Saltados: Y

📋 PASO 4: Migrando prendas...
   ✅ Prendas migradas: X | Actualizadas: Y

📋 PASO 5: Migrando procesos...
   ✅ Procesos migrados: X | Errores: Y

✅ MIGRACIÓN COMPLETA EXITOSA
```

**Tiempo esperado**: 5-10 minutos

---

## ✔️ PASO 4: VALIDAR MIGRACIÓN

Verifica que todo se migró correctamente:

```bash
php artisan migrate:validate
```

**Output esperado**:
```
📊 ESTADÍSTICAS DE MIGRACIÓN:
   Usuarios (Asesoras): 51
   Clientes: 965
   Pedidos: 2260
   Prendas: 2906
   Procesos: 17000

🔗 VERIFICACIÓN DE RELACIONES:
   Pedidos sin asesor asignado: 527 ⚠️
   Pedidos sin cliente asignado: 7 ⚠️
   Prendas sin pedido asignado: 0 ✅
   Procesos sin prenda asignada: 0 ✅

✅ INTEGRIDAD DE DATOS:
   Pedidos con datos completos: 1728 / 2260 (76.46%)

✅ MIGRACIÓN VALIDADA EXITOSAMENTE
```

---

## 🔧 PASO 5: CORREGIR ERRORES (SI HAY)

Si hay errores, intenta corregirlos:

```bash
php artisan migrate:fix-errors
```

**Arregla**:
- ✅ Campos expandidos
- ✅ Fechas inválidas eliminadas
- ✅ Procesos incompletos

---

## 📚 VERIFICACIÓN MANUAL (OPCIONAL)

Puedes verificar manualmente si es necesario:

```bash
# Contar usuarios creados
mysql -u user -p database -e "SELECT COUNT(*) FROM users;"

# Contar clientes
mysql -u user -p database -e "SELECT COUNT(*) FROM clientes;"

# Contar pedidos
mysql -u user -p database -e "SELECT COUNT(*) FROM pedidos_produccion;"

# Ver ejemplo de prenda con tallas
mysql -u user -p database -e "SELECT id, nombre_prenda, cantidad_talla FROM prendas_pedido LIMIT 5;"

# Ver procesos migrados
mysql -u user -p database -e "SELECT proceso, COUNT(*) FROM procesos_prenda GROUP BY proceso;"
```

---

## ↩️ EN CASO DE ERROR: REVERTIR

Si algo va mal, puedes revertir:

```bash
# Opción 1: Preguntar si deseas revertir
php artisan migrate:procesos-prenda --reset

# Opción 2: Solo eliminar procesos
php artisan migrate:rollback-procesos

# Opción 3: Restaurar desde backup
# (Restaurar archivo de backup de BD)
```

⚠️ **ADVERTENCIA**: Esto eliminará TODOS los datos migrados. Pide confirmación.

---

## 🎯 CASOS DE USO

### 📍 Caso 1: Primer uso
```bash
1. php artisan migrate:procesos-prenda --dry-run
2. php artisan migrate:procesos-prenda
3. php artisan migrate:validate
✅ ¡LISTO!
```

### 📍 Caso 2: Hay errores y necesito corregir
```bash
1. php artisan migrate:fix-errors
2. php artisan migrate:validate
✅ Errores corregidos
```

### 📍 Caso 3: Necesito revertir y empezar de nuevo
```bash
1. php artisan migrate:procesos-prenda --reset
2. Restaurar backup de BD (si es necesario)
3. Empezar nuevamente desde "Caso 1"
```

### 📍 Caso 4: Ya migré y solo quiero validar
```bash
php artisan migrate:validate
```

---

## 🔍 SIGNOS DE ÉXITO

✅ Deberías ver:
- [x] Usuarios creados correctamente
- [x] Clientes creados correctamente
- [x] Pedidos con sus asesores asignados
- [x] Prendas con tallas en JSON
- [x] Procesos creados correctamente
- [x] Validación sin errores críticos

---

## ⚠️ SIGNOS DE ERROR

❌ Si ves:
- [ ] "Data truncated for column" → Expandir campo
- [ ] "Duplicate entry" → Ya existe ese registro
- [ ] "Foreign key constraint" → Usuario/Cliente no existe
- [ ] "Invalid datetime format" → Fecha con formato inválido

**Solución**: Ejecutar `php artisan migrate:fix-errors`

---

## 📞 SOPORTE

Si encuentras problemas:

1. Revisa `MIGRACIONES_DOCUMENTACION.md` (sección Troubleshooting)
2. Ejecuta `php artisan migrate:validate` para diagnóstico
3. Revisa los logs en `storage/logs/`
4. Verifica base de datos con MySQL Workbench o phpMyAdmin

---

## 📝 REGISTRO DE EJECUCIÓN

Copia esto y completa después de ejecutar:

```
Fecha: _______________
Hora inicio: _______________
Hora fin: _______________

Usuarios creados: _______________
Clientes creados: _______________
Pedidos migrados: _______________
Prendas migradas: _______________
Procesos migrados: _______________

Errores encontrados: _______________
Errores corregidos: _______________

Validación resultado: ✅ / ❌

Notas: _______________
_______________
_______________
```

---

**Versión**: 1.0  
**Última actualización**: 26 de Noviembre de 2025  
**Estado**: ✅ Listo para usar
