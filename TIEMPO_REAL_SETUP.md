# 🚀 Guía de Configuración: Actualizaciones en Tiempo Real

## Problema identificado
Las tablas de dashboard-tables-corte.blade.php no se actualizan en tiempo real porque faltan dependencias y servicios por ejecutar.

## ✅ Pasos para activar tiempo real

### 1️⃣ Instalar pusher-js (REQUERIDO)
```bash
npm install
```

### 2️⃣ Verificar variables de entorno en .env
Asegúrate de que tu archivo `.env` tenga estas configuraciones:

```env
# Broadcasting
BROADCAST_CONNECTION=reverb

# Reverb (WebSocket Server)
REVERB_APP_ID=tu_app_id
REVERB_APP_KEY=tu_app_key
REVERB_APP_SECRET=tu_app_secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Variables para el frontend (Vite)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Colas (para que los eventos se emitan)
QUEUE_CONNECTION=database
```

**IMPORTANTE:** Si no tienes estas variables, cópialas y genera valores aleatorios para APP_ID, APP_KEY y APP_SECRET.

### 3️⃣ Ejecutar migraciones de colas (si no lo has hecho)
```bash
php artisan queue:table
php artisan migrate
```

### 4️⃣ Iniciar los 3 servicios necesarios

Abre **3 terminales separadas** y ejecuta en cada una:

**Terminal 1: Servidor Reverb (WebSockets)**
```bash
php artisan reverb:start
```
Debe mostrar: "Reverb server started on ws://127.0.0.1:8080"

**Terminal 2: Procesador de colas**
```bash
php artisan queue:work
```
Debe mostrar: "Processing jobs..."

**Terminal 3: Vite (compilar JavaScript)**
```bash
npm run dev
```
Debe mostrar URLs locales (http://127.0.0.1:5173 o similar)

### 5️⃣ Verificar en el navegador

1. Abre el dashboard con las tablas de corte
2. Abre la **Consola del Navegador** (F12 → Console)
3. Deberías ver estos mensajes:

```
=== DASHBOARD CORTE - Inicializando Echo ===
window.Echo disponible: true
Suscribiéndose al canal "corte"...
✅ Suscrito exitosamente al canal "corte"
Listeners configurados. Esperando eventos...
```

4. Si en lugar de eso ves:
   - ❌ `Echo NO está disponible` → Vite no está corriendo o pusher-js no está instalado
   - ❌ Error de conexión WebSocket → Reverb no está corriendo
   - Sin mensajes → El script no se cargó, recarga la página

### 6️⃣ Probar tiempo real

1. Abre **dos ventanas/pestañas** del dashboard
2. En una ventana, crea un **nuevo registro de corte**
3. En la **otra ventana** deberías ver:
   - En consola: `🎉 Evento CorteRecordCreated recibido!`
   - En la tabla: los datos se actualizan automáticamente sin recargar

---

## 🔧 Solución de problemas

### Error: "Echo is not available"
- Verifica que `npm run dev` esté corriendo
- Verifica que pusher-js esté instalado: `npm list pusher-js`
- Revisa que `resources/js/bootstrap.js` importe Echo

### Error de conexión WebSocket
- Verifica que `php artisan reverb:start` esté corriendo
- Verifica las variables VITE_REVERB_* en .env
- Verifica que los puertos no estén ocupados

### Eventos no llegan
- Verifica que `php artisan queue:work` esté corriendo
- Verifica que QUEUE_CONNECTION=database en .env
- Revisa los logs: `tail -f storage/logs/laravel.log`

### El evento se crea pero no se actualiza la tabla
- Abre la consola y busca errores en JavaScript
- Verifica que `actualizarTablaHoras` y `actualizarTablaOperarios` no tengan errores

---

## 📌 Notas importantes

1. **Los 3 servicios deben estar corriendo simultáneamente** para que funcione
2. Si reinicias el servidor, debes volver a iniciar los 3 servicios
3. En producción, usa supervisord o similar para mantener los servicios corriendo
4. El evento usa `->toOthers()` para no actualizar la ventana que creó el registro (opcional)

---

## 🎯 Resumen rápido

```bash
# Paso 1: Instalar dependencias
npm install

# Paso 2: En 3 terminales diferentes:
php artisan reverb:start    # Terminal 1
php artisan queue:work       # Terminal 2
npm run dev                  # Terminal 3
```

¡Listo! Ahora las tablas se actualizarán en tiempo real 🎉
