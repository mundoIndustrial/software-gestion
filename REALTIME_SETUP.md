# Configuración de Actualizaciones en Tiempo Real para Cotizaciones

## 📋 Resumen

Se ha implementado funcionalidad de tiempo real para los módulos de cotizaciones usando Laravel Reverb (WebSocket server oficial de Laravel). Los usuarios ahora verán:

- ✅ Cambios de estado de cotizaciones en tiempo real
- ✅ Nuevas cotizaciones aparecen automáticamente
- ✅ Notificaciones cuando se aprueban cotizaciones
- ✅ Actualizaciones sin necesidad de recargar el navegador

## 🚀 Configuración Requerida

### 1. Instalar Laravel Reverb (si no está instalado)

```bash
composer require laravel/reverb
php artisan reverb:install
```

### 2. Configurar Variables de Entorno

Agrega estas variables a tu archivo `.env`:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

Para generar las credenciales, ejecuta:

```bash
php artisan reverb:install
```

### 3. Iniciar el Servidor Reverb

En una terminal separada, ejecuta:

```bash
php artisan reverb:start
```

O para modo desarrollo con debug:

```bash
php artisan reverb:start --debug
```

### 4. Configurar para Producción

Para producción, usa un gestor de procesos como Supervisor:

```ini
[program:reverb]
command=php /ruta/a/tu/proyecto/artisan reverb:start
directory=/ruta/a/tu/proyecto
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

## 📁 Archivos Creados/Modificados

### Eventos de Broadcast Creados:
- `app/Events/CotizacionEstadoCambiado.php` - Cuando cambia el estado
- `app/Events/CotizacionCreada.php` - Cuando se crea una cotización
- `app/Events/CotizacionAprobada.php` - Cuando se aprueba una cotización

### Handlers Actualizados:
- `app/Application/Cotizacion/Handlers/Commands/CambiarEstadoCotizacionHandler.php`
- `app/Application/Cotizacion/Handlers/Commands/CrearCotizacionHandler.php`
- `app/Application/Cotizacion/Handlers/Commands/AceptarCotizacionHandler.php`
- `app/Http/Controllers/ContadorController.php`

### Canales de Broadcast:
- `routes/channels.php` - Configuración de canales públicos y privados

### Frontend:
- `public/js/realtime-cotizaciones.js` - Lógica de escucha de eventos
- `public/css/realtime-cotizaciones.css` - Estilos para notificaciones y animaciones

### Vistas Actualizadas:
- `resources/views/asesores/cotizaciones/index.blade.php`
- `resources/views/contador/index.blade.php`

## 🔧 Cómo Funciona

### Backend (Broadcasting)

Cuando ocurre un evento importante (crear, cambiar estado, aprobar):

```php
broadcast(new CotizacionEstadoCambiado(
    $cotizacionId,
    $nuevoEstado,
    $estadoAnterior,
    $asesorId,
    $cotizacionData
))->toOthers();
```

### Frontend (Listening)

Laravel Echo escucha los eventos en tiempo real:

```javascript
window.Echo.channel('cotizaciones')
    .listen('.cotizacion.estado.cambiado', (event) => {
        // Actualizar la UI automáticamente
        handleEstadoCambiado(event);
    });
```

## 📡 Canales Disponibles

### 1. Canal General
- **Nombre**: `cotizaciones`
- **Acceso**: Todos los usuarios autenticados
- **Eventos**: Todas las actualizaciones de cotizaciones

### 2. Canal por Asesor
- **Nombre**: `cotizaciones.asesor.{asesorId}`
- **Acceso**: Solo el asesor específico
- **Eventos**: Actualizaciones de sus propias cotizaciones

### 3. Canal de Contador
- **Nombre**: `cotizaciones.contador`
- **Acceso**: Solo usuarios con rol contador
- **Eventos**: Cotizaciones enviadas para revisión

## 🎨 Características Visuales

### Animaciones:
- **Nueva cotización**: Aparece con fade-in desde arriba (verde)
- **Estado actualizado**: Pulso azul en la fila
- **Cotización removida**: Slide-out hacia la derecha

### Notificaciones:
- **Toast in-app**: Esquina superior derecha
- **Notificaciones del navegador**: Si el usuario da permiso
- **Indicador de conexión**: Muestra estado de WebSocket

## 🧪 Probar la Funcionalidad

### Test Manual:

1. **Abrir dos navegadores/pestañas**:
   - Pestaña 1: Módulo de Asesores (crear/editar cotización)
   - Pestaña 2: Módulo de Contador (revisar cotizaciones)

2. **Crear una cotización** en Pestaña 1:
   - Debería aparecer automáticamente en Pestaña 2 si está en estado ENVIADA_CONTADOR

3. **Cambiar estado** en Pestaña 2:
   - El estado debería actualizarse en Pestaña 1 sin recargar

4. **Aprobar cotización** en Pestaña 2:
   - Notificación debería aparecer en Pestaña 1

### Verificar en Consola:

Abre las DevTools del navegador (F12) y verifica:

```
✅ Laravel Echo configurado
🔴 Iniciando escucha de eventos en tiempo real para cotizaciones
✅ Conectado al servidor WebSocket
✅ Real-time cotizaciones listener initialized
```

## 🐛 Troubleshooting

### Problema: No se conecta al WebSocket

**Solución**:
1. Verifica que Reverb esté corriendo: `php artisan reverb:start`
2. Revisa las variables de entorno en `.env`
3. Verifica el puerto 8080 esté disponible

### Problema: Eventos no se reciben

**Solución**:
1. Verifica que los eventos implementen `ShouldBroadcast`
2. Revisa los logs de Reverb: `php artisan reverb:start --debug`
3. Verifica la configuración de canales en `routes/channels.php`

### Problema: Error de CORS

**Solución**:
Agrega a `config/cors.php`:

```php
'paths' => ['api/*', 'broadcasting/auth', 'sanctum/csrf-cookie'],
```

## 📊 Monitoreo

### Ver conexiones activas:

```bash
php artisan reverb:start --debug
```

### Logs de eventos:

Los eventos se registran en `storage/logs/laravel.log` con prefijos:
- `CambiarEstadoCotizacionHandler:`
- `CrearCotizacionHandler:`
- `AceptarCotizacionHandler:`

## 🔒 Seguridad

- Los canales privados requieren autenticación
- Solo los asesores pueden ver sus propias cotizaciones
- Los contadores solo ven cotizaciones en estados específicos
- Todos los eventos usan `->toOthers()` para evitar duplicados

## 🚀 Próximos Pasos (Opcional)

1. **Agregar más eventos**: Eliminar, duplicar, etc.
2. **Notificaciones push**: Integrar con servicios como Firebase
3. **Historial de cambios**: Mostrar quién hizo qué cambio
4. **Indicadores de usuarios activos**: Mostrar quién está viendo una cotización

## 📞 Soporte

Si encuentras problemas:
1. Revisa los logs: `storage/logs/laravel.log`
2. Verifica la consola del navegador (F12)
3. Ejecuta Reverb en modo debug: `php artisan reverb:start --debug`
