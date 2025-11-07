# Sistema de Sesión Persistente - "Recordarme"

## 📋 Descripción

Se ha implementado un sistema de sesión persistente que permite a los usuarios mantener su sesión activa por **30 días** sin necesidad de volver a iniciar sesión cada vez que acceden al sistema.

## ✅ Características Implementadas

### 1. **Duración Extendida del "Remember Me"**
- La sesión se mantiene activa por **30 días** (43,200 minutos)
- El checkbox "Recordarme por 30 días" está **marcado por defecto** en el login
- Los usuarios pueden desmarcar el checkbox si prefieren sesiones más cortas

### 2. **Configuración Flexible**
- La duración es configurable desde el archivo `.env`
- Variable: `AUTH_REMEMBER_DURATION=43200` (en minutos)
- Puedes cambiar este valor según tus necesidades:
  - 7 días: `10080`
  - 15 días: `21600`
  - 30 días: `43200` (por defecto)
  - 60 días: `86400`
  - 90 días: `129600`

### 3. **Seguridad Mantenida**
- El token de sesión se almacena de forma segura en la base de datos
- Laravel maneja automáticamente la renovación y validación del token
- El sistema sigue siendo seguro contra ataques CSRF y XSS

## 🔧 Archivos Modificados

### 1. `config/auth.php`
```php
'remember_duration' => env('AUTH_REMEMBER_DURATION', 43200), // 30 días
```

### 2. `app/Models/User.php`
- Agregado método `getRememberTokenDuration()` para obtener la duración configurada

### 3. `app/Providers/AuthServiceProvider.php`
- Configuración automática de la duración del token al iniciar la aplicación

### 4. `resources/views/auth/login.blade.php`
- Checkbox "Recordarme por 30 días" marcado por defecto
- Texto actualizado para indicar la duración

### 5. `AGREGAR_AL_ENV.txt`
- Documentación de las nuevas variables de entorno

## 📝 Cómo Usar

### Para Usuarios
1. Al iniciar sesión, el checkbox "Recordarme por 30 días" estará marcado por defecto
2. Si deseas una sesión más corta, desmarca el checkbox antes de iniciar sesión
3. Una vez iniciada la sesión, no necesitarás volver a iniciar sesión por 30 días (o hasta que cierres sesión manualmente)

### Para Administradores

#### Cambiar la Duración
1. Abre el archivo `.env` en la raíz del proyecto
2. Agrega o modifica la línea:
   ```
   AUTH_REMEMBER_DURATION=43200
   ```
3. Cambia el valor según tus necesidades (en minutos)
4. Guarda el archivo
5. Limpia la caché de configuración:
   ```bash
   php artisan config:cache
   ```

#### Verificar que Funciona
1. Inicia sesión con el checkbox marcado
2. Cierra el navegador completamente
3. Abre el navegador nuevamente y accede al sistema
4. Deberías estar automáticamente autenticado

## 🔐 Consideraciones de Seguridad

### ✅ Ventajas
- **Comodidad**: Los usuarios no necesitan iniciar sesión constantemente
- **Productividad**: Ideal para estaciones de trabajo dedicadas
- **Flexibilidad**: Configurable según las necesidades de seguridad

### ⚠️ Recomendaciones
1. **Computadores Compartidos**: Instruye a los usuarios a desmarcar el checkbox si usan computadores compartidos
2. **Cerrar Sesión**: Recuerda a los usuarios cerrar sesión al terminar su turno si comparten el computador
3. **Auditoría**: El sistema mantiene registro de todos los accesos (trait `Auditable`)
4. **Red Local**: Ideal para tu entorno de red local donde los computadores son controlados

### 🚫 Cuándo NO Usar Sesión Persistente
- Computadores públicos o compartidos sin control
- Entornos con alta rotación de personal
- Cuando se requiere autenticación en cada acceso por políticas de seguridad

## 🔄 Cómo Funciona Técnicamente

1. **Login**: Cuando el usuario marca "Recordarme", Laravel crea un token único
2. **Almacenamiento**: El token se guarda en:
   - Base de datos (tabla `users`, columna `remember_token`)
   - Cookie del navegador (encriptada)
3. **Validación**: En cada petición, Laravel verifica:
   - Si existe la cookie
   - Si el token coincide con el de la base de datos
   - Si no ha expirado (30 días)
4. **Renovación**: El token se renueva automáticamente en cada acceso
5. **Expiración**: Después de 30 días de inactividad, el token expira

## 📊 Monitoreo

Para verificar las sesiones activas, puedes consultar:

```sql
-- Ver usuarios con sesión "remember me" activa
SELECT id, name, email, remember_token, updated_at
FROM users
WHERE remember_token IS NOT NULL;

-- Ver sesiones activas en la tabla sessions
SELECT * FROM sessions
WHERE last_activity > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY));
```

## 🆘 Solución de Problemas

### El usuario sigue pidiendo login después de cerrar el navegador
1. Verifica que el checkbox esté marcado al iniciar sesión
2. Verifica que la variable `AUTH_REMEMBER_DURATION` esté en el `.env`
3. Limpia la caché: `php artisan config:cache`
4. Verifica que las cookies no estén bloqueadas en el navegador

### Quiero que la sesión dure más o menos tiempo
1. Edita el archivo `.env`
2. Cambia `AUTH_REMEMBER_DURATION` al valor deseado (en minutos)
3. Ejecuta: `php artisan config:cache`

### Quiero forzar el cierre de todas las sesiones
```bash
# Opción 1: Limpiar todas las sesiones
php artisan session:flush

# Opción 2: Regenerar la clave de la aplicación (cierra TODAS las sesiones)
php artisan key:generate
```

## 📞 Soporte

Si tienes dudas o problemas con el sistema de sesión persistente, revisa:
1. Este documento
2. Los logs en `storage/logs/laravel.log`
3. La configuración en `config/auth.php` y `config/session.php`
