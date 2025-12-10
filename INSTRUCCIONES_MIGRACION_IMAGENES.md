# 📋 INSTRUCCIONES - MIGRACIÓN DE IMÁGENES

**Fecha:** 10 de Diciembre de 2025
**Estado:** 🔄 LISTO PARA EJECUTAR

---

## ⚠️ IMPORTANTE

**Antes de ejecutar estos scripts:**
1. ✅ Hacer backup de la base de datos
2. ✅ Probar en ambiente de staging
3. ✅ Verificar que no hay operaciones en curso

---

## 🚀 PASOS DE EJECUCIÓN

### PASO 1: Crear nuevas tablas

```bash
# Opción 1: Ejecutar script SQL directamente
mysql -u usuario -p nombre_base_datos < database/scripts/01_crear_tablas_imagenes.sql

# Opción 2: Desde Laravel
php artisan tinker
> DB::unprepared(file_get_contents('database/scripts/01_crear_tablas_imagenes.sql'));
```

**Resultado esperado:**
```
✅ Tabla prenda_tela_fotos_cot creada
✅ Tabla logo_fotos_cot creada
```

---

### PASO 2: Migrar datos de telas

```bash
# Ejecutar script SQL
mysql -u usuario -p nombre_base_datos < database/scripts/02_migrar_datos_imagenes.sql
```

**Resultado esperado:**
```
✅ Fotos de telas migradas a prenda_tela_fotos_cot
⚠️ Imágenes de logos aún en JSON (próximo paso)
```

---

### PASO 3: Migrar imágenes de logos (PHP)

```bash
# Ejecutar comando Artisan
php artisan db:migrar-imagenes-logo
```

**Resultado esperado:**
```
✅ Imágenes migradas: 50
❌ Errores: 0
```

---

### PASO 4: Modificar tablas existentes

```bash
# Ejecutar script SQL
mysql -u usuario -p nombre_base_datos < database/scripts/03_modificar_tablas_existentes.sql
```

**Cambios realizados:**
```
✅ Eliminada columna 'tipo' de prenda_fotos_cot
✅ Modificada relación en prenda_telas_cot
✅ Agregados índices para rendimiento
```

---

### PASO 5: Eliminar datos antiguos (OPCIONAL)

```bash
# Después de verificar que todo funciona, eliminar datos antiguos

# Opción 1: Desde MySQL
DELETE FROM prenda_fotos_cot WHERE tipo = 'tela';
ALTER TABLE logo_cotizaciones DROP COLUMN imagenes;

# Opción 2: Desde Laravel
php artisan tinker
> DB::table('prenda_fotos_cot')->where('tipo', 'tela')->delete();
> DB::statement('ALTER TABLE logo_cotizaciones DROP COLUMN imagenes');
```

---

## 📊 VERIFICACIÓN

### Después de cada paso, verificar:

```sql
-- Verificar nuevas tablas
SELECT TABLE_NAME, TABLE_ROWS 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('prenda_tela_fotos_cot', 'logo_fotos_cot');

-- Verificar datos migrados
SELECT 
    'prenda_fotos_cot' as tabla,
    COUNT(*) as cantidad
FROM prenda_fotos_cot
UNION ALL
SELECT 
    'prenda_tela_fotos_cot' as tabla,
    COUNT(*) as cantidad
FROM prenda_tela_fotos_cot
UNION ALL
SELECT 
    'logo_fotos_cot' as tabla,
    COUNT(*) as cantidad
FROM logo_fotos_cot;

-- Verificar relaciones
SHOW CREATE TABLE prenda_telas_cot\G
```

---

## 🔄 ROLLBACK (Si algo sale mal)

```sql
-- Restaurar desde backup
mysql -u usuario -p nombre_base_datos < backup_anterior.sql

-- O eliminar tablas nuevas
DROP TABLE IF EXISTS prenda_tela_fotos_cot;
DROP TABLE IF EXISTS logo_fotos_cot;

-- Y restaurar datos en tablas antiguas
-- (Requiere backup de datos)
```

---

## 📋 CHECKLIST DE EJECUCIÓN

- [ ] Backup de base de datos realizado
- [ ] Probar en staging
- [ ] Paso 1: Crear nuevas tablas ✅
- [ ] Paso 2: Migrar datos de telas ✅
- [ ] Paso 3: Migrar imágenes de logos ✅
- [ ] Paso 4: Modificar tablas existentes ✅
- [ ] Paso 5: Eliminar datos antiguos (opcional) ✅
- [ ] Verificar integridad de datos ✅
- [ ] Actualizar modelos Eloquent ✅
- [ ] Actualizar handlers ✅
- [ ] Tests en staging ✅
- [ ] Deploy a producción ✅

---

## 🎯 PRÓXIMOS PASOS

Después de completar la migración:

1. **Actualizar Modelos Eloquent**
   - Crear `PrendaTelaFoto`
   - Crear `LogoFoto`
   - Actualizar relaciones

2. **Actualizar Handlers**
   - `SubirImagenCotizacionHandler`
   - Lógica de guardado de imágenes

3. **Actualizar Frontend**
   - Actualizar `subir-imagenes.js`
   - Validar máximo de 5 logos

4. **Testing**
   - Tests unitarios
   - Tests E2E
   - Validación en staging

---

## 📞 SOPORTE

Si encuentras errores:

1. Revisar logs: `storage/logs/laravel.log`
2. Verificar integridad de datos
3. Consultar documentación de migración
4. Contactar al equipo de desarrollo

---

**Instrucciones creadas:** 10 de Diciembre de 2025
**Estado:** 🟢 LISTO PARA EJECUCIÓN
