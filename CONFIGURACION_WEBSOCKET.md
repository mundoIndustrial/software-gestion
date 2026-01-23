# Configuración de WebSocket/Reverb para Desarrollo y Producción

## Resumen

El sistema ahora detecta automáticamente si está en **desarrollo** o **producción** y se conecta al servidor WebSocket correcto.

## Configuración

### 🔧 Desarrollo (Local)
**Archivo:** `.env`
- **Host:** `localhost` (127.0.0.1)
- **Puerto:** `8080`
- **Esquema:** `http`
- **URL:** `ws://localhost:8080`

```env
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_HOST=localhost
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=http
```

### 🚀 Producción
**Archivo:** `.env.production`
- **Host:** `sistemamundoindustrial.online`
- **Puerto:** `443`
- **Esquema:** `https`
- **URL:** `wss://sistemamundoindustrial.online:443`

```env
REVERB_HOST=sistemamundoindustrial.online
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_HOST=sistemamundoindustrial.online
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

## Detección Automática de Entorno

El archivo `resources/js/bootstrap.js` detecta automáticamente el entorno basándose en:

1. **`import.meta.env.MODE`** - Vite proporciona esto (development/production)
2. **`import.meta.env.VITE_ENV`** - Nuestra variable personalizada en .env
3. **`window.location.hostname`** - Si no es localhost/127.0.0.1, asume producción

```javascript
const isProduction = import.meta.env.MODE === 'production' || 
                     import.meta.env.VITE_ENV === 'production' ||
                     window.location.hostname !== 'localhost' && 
                     window.location.hostname !== '127.0.0.1';
```

## Ejecución

### Desarrollo
Ejecuta ambos servidores simultáneamente:

```bash
npm run start
```

O manualmente en 3 terminales:

```bash
# Terminal 1: Servidor Vite
npm run dev

# Terminal 2: Servidor Laravel
php artisan serve

# Terminal 3: Servidor Reverb WebSocket
php artisan reverb:start
```

### Producción
Solo necesitas:

```bash
# Build de Vite
npm run build

# El servidor Reverb debe estar corriendo en background
php artisan reverb:start --host=0.0.0.0 --port=8080

# Y tu servidor Laravel normal
```

## Flujo de Conexión

### 🔍 Desarrollo
```
1. Browser carga la página (localhost:8000)
2. Vite carga variables del .env (VITE_REVERB_HOST=localhost)
3. bootstrap.js intenta conectar a ws://localhost:8080
4. El servidor Reverb local recibe la conexión
5. WebSocket funcionando: ✅
```

### 🔍 Producción
```
1. Browser carga la página (sistemamundoindustrial.online)
2. Vite usa variables del .env.production (VITE_REVERB_HOST=sistemamundoindustrial.online)
3. bootstrap.js intenta conectar a wss://sistemamundoindustrial.online:443
4. El servidor Reverb de producción recibe la conexión
5. WebSocket funcionando: ✅
```

## Verificación en Console

Abre la consola del navegador (F12) y verás logs como:

```
🔧 Environment Detection:
MODE: development
VITE_ENV: local
Hostname: localhost
isProduction: false

📡 Configuración de Echo/Reverb:
VITE_REVERB_APP_KEY: mundo-industrial-key
VITE_REVERB_HOST: localhost
VITE_REVERB_PORT: 8080
VITE_REVERB_SCHEME: http

✅ Configuración final de Echo:
broadcaster: reverb
wsHost: localhost
wsPort: 8080
forceTLS: false

✅ WebSocket conectado exitosamente a Reverb
```

## Troubleshooting

### Error: "WebSocket connection failed"
- En **desarrollo**: Verifica que `php artisan reverb:start` esté corriendo
- En **producción**: Verifica que el servidor Reverb esté escuchando en el puerto 8080 correctamente

### Conectando a servidor incorrecto
- Revisa la consola (F12)
- Verifica que las variables `VITE_REVERB_*` están correctas en tu `.env`
- En producción, asegúrate de que estés usando `npm run build` (no dev)

### SSL/HTTPS en producción
- El servidor Reverb escucha en puerto 8080 (sin SSL)
- Nginx/Apache debe hacer proxy y SSL termination
- bootstrap.js detecta automáticamente `forceTLS: true` en producción
