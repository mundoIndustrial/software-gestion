# Web Push Cartera

Implementación para notificar en navegador/móvil cuando un pedido entra a `pendiente_cartera`.

## Componentes

- Tabla: `push_subscriptions`
- Service Worker: `public/sw-push.js`
- Endpoints:
  - `POST /push-subscriptions`
  - `DELETE /push-subscriptions`
- Servicio backend: `App\Services\PushNotificationService`
- Trigger: `PedidoProduccionObserver` en creación y cambios de estado a `pendiente_cartera`.

## Variables de entorno requeridas

```env
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
VAPID_SUBJECT=mailto:tu-correo@dominio.com
```

## Generar claves VAPID

Intentar:

```bash
php artisan push:vapid-generate
```

Si falla por OpenSSL/curvas EC en el servidor, generar las claves en otro entorno compatible y copiarlas al `.env`.

## Activación

1. Ejecutar migración:
```bash
php artisan migrate
```
2. Configurar variables VAPID en `.env`.
3. Limpiar caché de config:
```bash
php artisan config:clear
```
4. Abrir `/cartera/pedidos` y aceptar permiso de notificaciones.

## Nota móvil

En iPhone/Safari, las push web requieren usar el sitio como app web (agregado a pantalla de inicio) para comportamiento completo de notificaciones.

