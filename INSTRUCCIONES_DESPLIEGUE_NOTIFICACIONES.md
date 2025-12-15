# 🚀 INSTRUCCIONES DE DESPLIEGUE - Notificaciones de Fecha Estimada

## 📌 Archivos Modificados/Creados

### Backend (3 archivos)
1. **app/Observers/PedidoProduccionObserver.php** ✅ NUEVO
   - Observer que detecta cambios en fecha estimada
   - Crea notificaciones en tabla `notifications`

2. **app/Http/Controllers/AsesoresController.php** ✅ MODIFICADO
   - Añadido import de `DB`
   - Método `getNotificaciones()` - Obtiene notificaciones
   - Método `getNotifications()` - Alias
   - Actualizado `markAllAsRead()`
   - Método `markNotificationAsRead($id)` - Marca individual

3. **app/Providers/AppServiceProvider.php** ✅ MODIFICADO
   - Agregado import de `PedidoProduccion`
   - Agregado import de `PedidoProduccionObserver`
   - Registrado Observer en `boot()`

### Frontend (1 archivo)
1. **public/js/asesores/notifications.js** ✅ MODIFICADO
   - Actualizado `renderNotifications()` para mostrar fecha estimada
   - Actualizado `createNotificationElement()` con marca visual
   - Agregada función `markNotificationAsRead(id)`
   - Soporte para click en notificación

### Rutas (1 archivo)
1. **routes/web.php** ✅ MODIFICADO
   - Agregada ruta POST `/asesores/notifications/{notificationId}/mark-read`

### Documentación (3 archivos)
1. **NOTIFICACIONES_FECHA_ESTIMADA_IMPLEMENTACION.md** ✅ NUEVO
2. **NOTIFICACIONES_IMPLEMENTACION_RESUMEN.md** ✅ NUEVO
3. **CHECKLIST_NOTIFICACIONES_FECHA_ESTIMADA.md** ✅ NUEVO
4. **tests/test-notificaciones-fecha-estimada.php** ✅ NUEVO

---

## 📋 PASOS PARA DESPLIEGUE

### 1. Verificar que la tabla `notifications` existe
```bash
php artisan migrate:status | grep notifications
```

**Si no existe**, ejecutar:
```bash
php artisan migrate --database=mysql
```

### 2. Limpiar cache de Laravel
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### 3. Registrar los cambios en Git
```bash
git add app/Observers/PedidoProduccionObserver.php
git add app/Http/Controllers/AsesoresController.php
git add app/Providers/AppServiceProvider.php
git add public/js/asesores/notifications.js
git add routes/web.php
git add NOTIFICACIONES_*.md
git add CHECKLIST_NOTIFICACIONES_*.md
git commit -m "feat: Implementar notificaciones de fecha estimada de entrega"
```

### 4. Probar en desarrollo
```bash
# Ejecutar script de prueba
php tests/test-notificaciones-fecha-estimada.php
```

### 5. Verificar logs
```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

---

## ✅ VERIFICACIÓN POST-DESPLIEGUE

### 1. API Endpoints
```bash
# Obtener notificaciones
curl -H "Authorization: Bearer TOKEN" http://localhost:8000/asesores/notifications

# Marcar todas como leídas
curl -X POST -H "Authorization: Bearer TOKEN" http://localhost:8000/asesores/notifications/mark-all-read

# Marcar una como leída
curl -X POST -H "Authorization: Bearer TOKEN" http://localhost:8000/asesores/notifications/{id}/mark-read
```

### 2. Base de Datos
```sql
-- Verificar notificaciones creadas
SELECT * FROM notifications 
WHERE type = 'App\\Notifications\\FechaEstimadaAsignada' 
ORDER BY created_at DESC LIMIT 5;

-- Contar no leídas por asesor
SELECT notifiable_id, COUNT(*) as no_leidas 
FROM notifications 
WHERE type = 'App\\Notifications\\FechaEstimadaAsignada' 
  AND read_at IS NULL 
GROUP BY notifiable_id;
```

### 3. Frontend
1. Acceder a `/asesores/pedidos`
2. Verificar que el dropdown de notificaciones carga sin errores
3. Actualizar `dia_de_entrega` de un pedido desde otro usuario
4. Verificar que el asesor propietario recibe la notificación
5. Hacer click en la notificación
6. Verificar que se marca como leída

### 4. Console del Navegador
```javascript
// Verificar que fetchAPI está disponible
console.log(window.fetchAPI);

// Obtener notificaciones manualmente
fetch('/asesores/notifications')
  .then(r => r.json())
  .then(d => console.log(d))
```

---

## 🐛 TROUBLESHOOTING

### ❌ Las notificaciones no aparecen
1. Verificar que el Observer está registrado:
   ```bash
   php artisan tinker
   >>> \Illuminate\Support\Facades\Event::getListeners('eloquent.updated: App\Models\PedidoProduccion')
   ```

2. Verificar logs:
   ```bash
   grep "fecha estimada" storage/logs/laravel.log
   ```

### ❌ Error 404 en rutas
1. Ejecutar:
   ```bash
   php artisan route:clear
   php artisan route:list | grep asesores/notifications
   ```

### ❌ Tabla `notifications` no existe
1. Ejecutar migraciones:
   ```bash
   php artisan migrate
   ```

### ❌ JavaScript no carga
1. Verificar en console:
   ```javascript
   console.log(document.querySelector('script[src*="notifications.js"]'));
   ```

2. Limpiar cache del navegador (Ctrl+Shift+Delete)

---

## 📊 MONITOREO

### Logs recomendados
```bash
# Ver creación de notificaciones en tiempo real
grep "Notificación de fecha estimada creada" storage/logs/laravel.log -i
```

### Métricas
```sql
-- Total de notificaciones creadas
SELECT COUNT(*) FROM notifications 
WHERE type = 'App\\Notifications\\FechaEstimadaAsignada';

-- Promedio de tiempo para marcar como leída
SELECT 
  AVG(TIMESTAMPDIFF(MINUTE, created_at, read_at)) as promedio_minutos,
  COUNT(*) as total_leidas
FROM notifications 
WHERE type = 'App\\Notifications\\FechaEstimadaAsignada' 
  AND read_at IS NOT NULL;
```

---

## ⚠️ CONSIDERACIONES IMPORTANTES

1. **Tabla `notifications`**: Usa tabla estándar de Laravel, no es nueva
2. **UUID**: Las notificaciones usan UUID como ID (no incrementales)
3. **JSON**: Los datos se guardan en formato JSON en la columna `data`
4. **Sincronía**: El Observer se ejecuta de forma síncrona (no queued)
5. **Rendimiento**: Para alto volumen, considerar hacer queued

---

## 🔄 PRÓXIMAS MEJORAS (Opcionales)

- [ ] Implementar WebSockets para notificaciones en tiempo real
- [ ] Agregar email cuando se asigna fecha estimada
- [ ] Dashboard con histórico de notificaciones
- [ ] Preferencias de notificación por asesor
- [ ] Notificaciones para otros cambios de estado
- [ ] Notificaciones push (PWA)

---

## 📞 SOPORTE

Si tienes dudas, revisa:
1. `NOTIFICACIONES_FECHA_ESTIMADA_IMPLEMENTACION.md` - Documentación técnica
2. `CHECKLIST_NOTIFICACIONES_FECHA_ESTIMADA.md` - Checklist de implementación
3. `NOTIFICACIONES_IMPLEMENTACION_RESUMEN.md` - Resumen ejecutivo

---

**Última Actualización:** 14 de Diciembre, 2025
**Versión:** 1.0
**Estado:** ✅ LISTO PARA PRODUCCIÓN
