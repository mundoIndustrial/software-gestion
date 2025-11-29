# 🚀 INSTRUCCIONES PARA EJECUTAR LA MIGRACIÓN

## ⚠️ IMPORTANTE

La migración debe ejecutarse ANTES de usar las nuevas funcionalidades del modal de insumos.

---

## 📋 PASOS A SEGUIR

### Paso 1: Abrir Terminal/Consola

En tu proyecto, abre una terminal en la raíz del proyecto:

```bash
cd c:\Users\Usuario\Documents\proyecto\v10\mundoindustrial
```

### Paso 2: Ejecutar la Migración

```bash
php artisan migrate
```

**Salida esperada:**
```
Migrating: 2025_11_29_000002_add_columns_to_materiales_orden_insumos
Migrated:  2025_11_29_000002_add_columns_to_materiales_orden_insumos (0.XX seconds)
```

### Paso 3: Verificar que se ejecutó correctamente

Puedes verificar que la migración se ejecutó correctamente de dos formas:

#### Opción A: Usar Tinker (recomendado)

```bash
php artisan tinker
```

Luego ejecuta:

```php
>>> Schema::getColumns('materiales_orden_insumos')
```

Deberías ver las nuevas columnas:
- `fecha_orden`
- `fecha_pago`
- `fecha_despacho`
- `observaciones`
- `dias_demora`

#### Opción B: Verificar en la BD directamente

Usa tu cliente de BD (phpMyAdmin, DBeaver, etc.) y ejecuta:

```sql
DESCRIBE materiales_orden_insumos;
```

O:

```sql
SHOW COLUMNS FROM materiales_orden_insumos;
```

---

## ✅ VERIFICACIÓN

Si ves las 5 nuevas columnas, la migración se ejecutó correctamente:

| Campo | Tipo | Nulo | Predeterminado |
|-------|------|------|---|
| fecha_orden | DATE | YES | NULL |
| fecha_pago | DATE | YES | NULL |
| fecha_despacho | DATE | YES | NULL |
| observaciones | TEXT | YES | NULL |
| dias_demora | INT | YES | NULL |

---

## 🔄 SI NECESITAS REVERTIR LA MIGRACIÓN

Si algo sale mal y necesitas revertir:

```bash
php artisan migrate:rollback
```

Esto eliminará las columnas agregadas.

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Error: "Class not found"

**Solución:**
```bash
composer dump-autoload
php artisan migrate
```

### Error: "SQLSTATE[HY000]"

**Solución:**
Verifica que tu BD esté corriendo y que tengas conexión.

### Error: "Migration already exists"

**Solución:**
La migración ya se ejecutó. Puedes ignorar este error.

---

## 📝 PRÓXIMOS PASOS

Una vez ejecutada la migración:

1. ✅ Abre tu navegador
2. ✅ Ve a `/insumos/materiales`
3. ✅ Haz clic en el botón "Insumos" de cualquier orden
4. ✅ Deberías ver el modal con las nuevas columnas

---

## 📞 SOPORTE

Si tienes problemas:

1. Verifica que estés en la carpeta correcta del proyecto
2. Verifica que PHP esté instalado: `php -v`
3. Verifica que Composer esté instalado: `composer -v`
4. Verifica que la BD esté corriendo
5. Revisa los logs: `storage/logs/laravel.log`

---

## 📅 Fecha: 29 de Noviembre de 2025
## 🎯 Estado: LISTO PARA EJECUTAR ✅
