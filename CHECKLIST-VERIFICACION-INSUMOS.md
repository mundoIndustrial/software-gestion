# ✅ CHECKLIST DE VERIFICACIÓN - MODAL DE INSUMOS

## 📋 ANTES DE EMPEZAR

- [ ] Hacer backup de la BD (recomendado)
- [ ] Verificar que estés en la rama correcta
- [ ] Verificar que no hay cambios sin guardar

---

## 🔧 INSTALACIÓN

### Paso 1: Ejecutar Migración
- [ ] Abrir terminal en la carpeta del proyecto
- [ ] Ejecutar: `php artisan migrate`
- [ ] Verificar que no hay errores
- [ ] Confirmar que se ejecutó correctamente

### Paso 2: Verificar Cambios en BD
- [ ] Abrir BD (phpMyAdmin, DBeaver, etc.)
- [ ] Verificar tabla `materiales_orden_insumos`
- [ ] Confirmar que existen las 5 nuevas columnas:
  - [ ] `fecha_orden`
  - [ ] `fecha_pago`
  - [ ] `fecha_despacho`
  - [ ] `observaciones`
  - [ ] `dias_demora`

### Paso 3: Verificar Archivos
- [ ] Migración creada: `database/migrations/2025_11_29_000002_add_columns_to_materiales_orden_insumos.php`
- [ ] Modelo actualizado: `app/Models/MaterialesOrdenInsumos.php`
- [ ] Controlador actualizado: `app/Http/Controllers/Insumos/InsumosController.php`
- [ ] Vista actualizada: `resources/views/insumos/materiales/index.blade.php`

---

## 🧪 PRUEBAS FUNCIONALES

### Test 1: Abrir Modal de Insumos
- [ ] Ir a `/insumos/materiales`
- [ ] Hacer clic en botón "Insumos" de cualquier orden
- [ ] Modal se abre correctamente
- [ ] Se muestran todas las columnas nuevas

### Test 2: Verificar Columnas
- [ ] Columna "Fecha Orden" visible
- [ ] Columna "Fecha Pedido" visible
- [ ] Columna "Fecha Pago" visible
- [ ] Columna "Fecha Llegada" visible
- [ ] Columna "Fecha Despacho" visible
- [ ] Columna "Días Demora" visible
- [ ] Columna "Observaciones" visible (botón ojo)

### Test 3: Agregar Fechas
- [ ] Hacer clic en campo "Fecha Orden"
- [ ] Seleccionar una fecha
- [ ] Campo se actualiza correctamente
- [ ] Repetir para todas las fechas

### Test 4: Cálculo de Días de Demora
- [ ] Ingresar "Fecha Pedido": 20/11/2025
- [ ] Ingresar "Fecha Llegada": 25/11/2025
- [ ] Verificar que "Días Demora" se calcula automáticamente
- [ ] Debe mostrar: 4 días (excluye sábado y domingo)
- [ ] Icono debe ser ✅ (verde) si es ≤ 0 días
- [ ] Icono debe ser ⚠️ (amarillo) si es 1-5 días
- [ ] Icono debe ser ❌ (rojo) si es > 5 días

### Test 5: Modal de Observaciones
- [ ] Hacer clic en botón 👁 (ojo)
- [ ] Modal de observaciones se abre
- [ ] Muestra el nombre del material
- [ ] Textarea está vacío (si es nuevo)
- [ ] Escribir texto de prueba: "Esto es una prueba"
- [ ] Hacer clic en "Guardar"
- [ ] Modal se cierra
- [ ] Volver a abrir modal de observaciones
- [ ] Verificar que el texto se guardó

### Test 6: Guardar Cambios
- [ ] Agregar datos a varios insumos
- [ ] Hacer clic en "Guardar Cambios"
- [ ] Mostrar mensaje de éxito
- [ ] Modal se cierra
- [ ] Volver a abrir modal
- [ ] Verificar que los datos se guardaron

### Test 7: Agregar Nuevo Insumo
- [ ] Hacer clic en "Agregar Insumo"
- [ ] Seleccionar un material
- [ ] Agregar fechas
- [ ] Agregar observaciones
- [ ] Hacer clic en "Guardar Cambios"
- [ ] Verificar que se guardó correctamente

### Test 8: Eliminar Insumo
- [ ] Hacer clic en botón 🗑 (papelera)
- [ ] Confirmar eliminación
- [ ] Insumo se elimina de la tabla
- [ ] Hacer clic en "Guardar Cambios"
- [ ] Verificar que se eliminó de la BD

---

## 🎨 VERIFICACIÓN VISUAL

### Colores de Fechas
- [ ] Fecha Orden: Gris
- [ ] Fecha Pedido: Azul
- [ ] Fecha Pago: Púrpura
- [ ] Fecha Llegada: Verde
- [ ] Fecha Despacho: Naranja

### Indicadores de Demora
- [ ] Verde (✅): Cuando días ≤ 0
- [ ] Amarillo (⚠️): Cuando días 1-5
- [ ] Rojo (❌): Cuando días > 5

### Responsividad
- [ ] Modal se ve bien en desktop
- [ ] Modal se ve bien en tablet
- [ ] Modal se ve bien en móvil
- [ ] Tabla no se desborda en móvil

---

## 🔍 VERIFICACIÓN EN BD

### Consulta SQL para verificar datos

```sql
-- Ver estructura de la tabla
DESCRIBE materiales_orden_insumos;

-- Ver datos guardados
SELECT * FROM materiales_orden_insumos LIMIT 5;

-- Ver columnas nuevas específicamente
SELECT 
    id,
    nombre_material,
    fecha_orden,
    fecha_pedido,
    fecha_pago,
    fecha_llegada,
    fecha_despacho,
    observaciones,
    dias_demora
FROM materiales_orden_insumos
LIMIT 5;
```

### Verificación de datos
- [ ] Columnas existen en BD
- [ ] Datos se guardan correctamente
- [ ] Observaciones se guardan como TEXT
- [ ] Fechas se guardan como DATE
- [ ] dias_demora se guarda como INT

---

## 🐛 RESOLUCIÓN DE PROBLEMAS

### Si el modal no muestra nuevas columnas
- [ ] Limpiar caché: `php artisan cache:clear`
- [ ] Limpiar vistas: `php artisan view:clear`
- [ ] Recargar página (Ctrl+F5)

### Si las fechas no se guardan
- [ ] Verificar que la migración se ejecutó: `php artisan migrate:status`
- [ ] Verificar permisos de BD
- [ ] Revisar logs: `storage/logs/laravel.log`

### Si el cálculo de días es incorrecto
- [ ] Verificar que las fechas estén en formato correcto (YYYY-MM-DD)
- [ ] Verificar que fecha_llegada > fecha_pedido
- [ ] Revisar la lógica en el modelo

### Si las observaciones no se guardan
- [ ] Verificar que el modal se cierra correctamente
- [ ] Verificar que el atributo `data-observaciones` se establece
- [ ] Revisar la consola del navegador (F12)

---

## 📊 PRUEBAS DE RENDIMIENTO

- [ ] Modal abre en menos de 2 segundos
- [ ] Cálculo de días es instantáneo
- [ ] Guardar cambios toma menos de 3 segundos
- [ ] No hay errores en la consola (F12)

---

## 📝 DOCUMENTACIÓN

- [ ] Leer: `MEJORAS-MODAL-INSUMOS.md`
- [ ] Leer: `RESUMEN-CAMBIOS-INSUMOS.md`
- [ ] Leer: `INSTRUCCIONES-EJECUTAR-MIGRACION.md`

---

## ✅ CHECKLIST FINAL

- [ ] Migración ejecutada correctamente
- [ ] Todas las columnas nuevas existen en BD
- [ ] Modal muestra todas las columnas
- [ ] Fechas se guardan correctamente
- [ ] Observaciones se guardan correctamente
- [ ] Cálculo de días funciona correctamente
- [ ] Modal de observaciones funciona correctamente
- [ ] Indicadores visuales son correctos
- [ ] No hay errores en la consola
- [ ] Datos persisten después de recargar página
- [ ] Sistema es responsive
- [ ] Documentación está completa

---

## 🎯 ESTADO FINAL

Si todos los checkboxes están marcados:

✅ **SISTEMA LISTO PARA PRODUCCIÓN**

Si hay alguno sin marcar:

⚠️ **REVISAR PROBLEMA ANTES DE USAR EN PRODUCCIÓN**

---

## 📞 SOPORTE

Si encuentras problemas:
1. Revisa este checklist
2. Revisa los logs: `storage/logs/laravel.log`
3. Revisa la consola del navegador (F12)
4. Revisa la documentación

---

## 📅 Fecha: 29 de Noviembre de 2025
## 🎯 Estado: CHECKLIST COMPLETO ✅
