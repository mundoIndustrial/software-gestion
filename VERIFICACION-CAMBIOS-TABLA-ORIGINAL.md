# ✅ VERIFICACIÓN - CAMBIOS TABLA_ORIGINAL

**Fecha:** Diciembre 3, 2025  
**Estado:** Cambios implementados, listo para verificar

---

## 🔍 VERIFICACIÓN EN TERMINAL

### 1. Verificar que no hay referencias a TablaOriginal

```bash
# Buscar en app/
grep -r "TablaOriginal" app/ --exclude-dir=node_modules

# Buscar en config/
grep -r "TablaOriginal" config/ --exclude-dir=node_modules

# Buscar en routes/
grep -r "TablaOriginal" routes/ --exclude-dir=node_modules
```

**Resultado esperado:** Sin resultados (excepto en comentarios históricos)

---

### 2. Verificar que no hay referencias a tabla_original

```bash
# Buscar referencias a la tabla
grep -r "tabla_original" app/ --exclude-dir=node_modules
```

**Resultado esperado:** Sin resultados

---

### 3. Limpiar Caché

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

**Resultado esperado:** Todos los comandos ejecutados sin errores

---

### 4. Ejecutar Tests

```bash
php artisan test
```

**Resultado esperado:** Todos los tests pasan (o al menos no hay errores nuevos)

---

## 🌐 VERIFICACIÓN EN NAVEGADOR

### 1. Abrir `/orders`

**URL:** `http://localhost:8000/orders` (o tu URL local)

**Verificar:**
- ✅ Tabla de órdenes carga correctamente
- ✅ Se muestran los datos
- ✅ Búsqueda funciona
- ✅ Filtros funcionan
- ✅ No hay errores en consola (F12)

**Si hay error:** Revisar logs en `storage/logs/laravel.log`

---

### 2. Abrir `/vistas`

**URL:** `http://localhost:8000/vistas`

**Verificar:**
- ✅ Vistas de costura/corte cargan correctamente
- ✅ Se muestran los datos
- ✅ Búsqueda funciona
- ✅ No hay errores en consola (F12)

---

### 3. Abrir `/entregas`

**URL:** `http://localhost:8000/entregas/pedido`

**Verificar:**
- ✅ Entregas cargan correctamente
- ✅ Se muestran los datos
- ✅ No hay errores en consola (F12)

---

### 4. Abrir DevTools (F12)

**Verificar:**
- ✅ No hay errores rojos en consola
- ✅ No hay errores de red (404, 500, etc.)
- ✅ No hay warnings sobre TablaOriginal

---

## 📊 VERIFICACIÓN DE LOGS

### Ver últimos logs

```bash
tail -f storage/logs/laravel.log
```

**Buscar errores relacionados:**
```bash
grep -i "error" storage/logs/laravel.log | tail -20
```

**Resultado esperado:** Sin errores relacionados a TablaOriginal

---

## 🔧 TROUBLESHOOTING

### Si hay error "Class 'TablaOriginal' not found"

**Causa:** Autoload no se regeneró correctamente

**Solución:**
```bash
composer dump-autoload
php artisan cache:clear
```

---

### Si hay error "Table 'tabla_original' doesn't exist"

**Causa:** Hay código que sigue intentando acceder a tabla_original

**Solución:**
1. Buscar la referencia: `grep -r "tabla_original" app/`
2. Actualizar el código para usar `pedidos_produccion`
3. Ejecutar `composer dump-autoload`

---

### Si hay error en `/orders`

**Pasos para debuggear:**
1. Abrir DevTools (F12)
2. Ir a Network
3. Recargar página
4. Ver si hay errores de red
5. Si hay error 500, revisar `storage/logs/laravel.log`

---

## ✅ CHECKLIST FINAL

- [ ] No hay referencias a `TablaOriginal` en código
- [ ] No hay referencias a `tabla_original` en código
- [ ] Autoload regenerado correctamente
- [ ] Caché limpiado
- [ ] `/orders` funciona sin errores
- [ ] `/vistas` funciona sin errores
- [ ] `/entregas` funciona sin errores
- [ ] DevTools no muestra errores
- [ ] Logs no muestran errores relacionados

---

## 📝 HACER COMMIT

Cuando todo esté verificado:

```bash
git add -A
git commit -m "refactor: eliminar referencias a tabla_original

- Eliminar import de TablaOriginal en RegistroOrdenController
- Actualizar método getOrderImages() para usar PedidoProduccion
- Actualizar método getProcesosTablaOriginal() para usar PedidoProduccion
- Eliminar imports de TablaOriginal en AppServiceProvider y VistasController
- Actualizar comentarios y logs
- Limpiar autoload
- Verificado: todas las funcionalidades funcionan correctamente"
```

---

## 🎉 COMPLETADO

Si todo funciona correctamente, ¡los cambios están completados exitosamente!

**Próximos pasos:**
1. Hacer push a repositorio
2. Deploy a staging (si aplica)
3. Deploy a producción (si aplica)

