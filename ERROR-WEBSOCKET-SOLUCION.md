# ❌ Error de WebSocket - Solución

## Error Mostrado

```
❌ Error de conexión WebSocket: {type: 'WebSocketError', error: {…}}
⚠️ WebSocket desconectado
```

## Diagnóstico

El error ocurre porque **las credenciales del cliente (navegador) NO coinciden con las del servidor Reverb**.

### Configuración Actual

**En el navegador (Vite):**
```
VITE_REVERB_APP_KEY: ztf74hxzjipb5iqicenl  ❌ INCORRECTO
```

**En el servidor (.env):**
```
REVERB_APP_KEY: mundo-industrial-key  ✅ CORRECTO
VITE_REVERB_APP_KEY: mundo-industrial-key  ✅ CORRECTO
```

## Causa del Problema

Vite tiene **las variables de entorno en caché**. Aunque el archivo `.env` tiene los valores correctos, Vite está usando una versión antigua cacheada.

## Solución Rápida ⚡

Ejecuta este script que limpia la caché y reconstruye los assets:

```bash
fix-vite-quick.bat
```

Este script:
1. Detiene npm dev server
2. Limpia caché de Laravel
3. Reconstruye assets con Vite
4. Reinicia Reverb
5. Inicia npm dev server

**Luego:**
- Espera 5-10 segundos a que Vite compile
- Recarga la página en el navegador (**Ctrl + F5** para forzar recarga)
- Verifica en la consola que `VITE_REVERB_APP_KEY` sea `mundo-industrial-key`

## Solución Completa 🔧

Si la solución rápida no funciona, ejecuta:

```bash
fix-vite-cache.bat
```

Este script hace una limpieza completa:
1. Detiene procesos de node
2. Limpia caché de npm
3. Elimina node_modules
4. Reinstala dependencias
5. Reconstruye assets

⚠️ **Advertencia:** Este proceso toma más tiempo (5-10 minutos).

## Verificación Manual

### 1. Verificar archivo .env

Ejecuta:
```bash
php fix-reverb-config.php
```

Debe mostrar:
```
✅ La configuración parece correcta
```

### 2. Verificar en el navegador

1. Abre la consola del navegador (F12)
2. Busca el mensaje: `🔧 Configuración de Echo/Reverb:`
3. Verifica que muestre:
   ```
   VITE_REVERB_APP_KEY: mundo-industrial-key  ✅
   VITE_REVERB_HOST: 127.0.0.1
   VITE_REVERB_PORT: 8080
   VITE_REVERB_SCHEME: http
   ```

### 3. Verificar conexión WebSocket

Después de recargar, deberías ver:
```
✅ WebSocket conectado exitosamente a Reverb
```

## Pasos Detallados (Manual)

Si prefieres hacerlo manualmente:

### 1. Detener servicios
```bash
# Detener npm dev server (Ctrl+C en la terminal)
# Detener Reverb (Ctrl+C en la terminal)
```

### 2. Limpiar caché
```bash
php artisan config:clear
php artisan cache:clear
npm cache clean --force
```

### 3. Reconstruir assets
```bash
npm run build
```

### 4. Reiniciar servicios
```bash
# Terminal 1: Reverb
php artisan reverb:start

# Terminal 2: npm dev
npm run dev

# Terminal 3: Laravel server
php artisan serve
```

### 5. Recargar navegador
- Presiona **Ctrl + Shift + R** (o **Ctrl + F5**)
- Esto fuerza una recarga completa sin caché

## Por Qué Ocurre Este Error

### Flujo de Autenticación

1. **Cliente (navegador)** se conecta a Reverb usando `VITE_REVERB_APP_KEY`
2. **Servidor Reverb** valida la clave contra `REVERB_APP_KEY`
3. Si las claves **NO coinciden** → Error de autenticación
4. WebSocket se desconecta

### Caché de Vite

Vite cachea las variables de entorno para mejorar el rendimiento. Cuando cambias el `.env`, Vite puede seguir usando los valores antiguos hasta que:
- Reconstruyas los assets (`npm run build`)
- Reinicies el dev server (`npm run dev`)
- Limpies la caché del navegador

## Prevención Futura

### 1. Siempre sincroniza las variables

En `.env`, asegúrate de que:
```env
REVERB_APP_KEY=mundo-industrial-key
VITE_REVERB_APP_KEY=mundo-industrial-key  # ← Mismo valor
```

### 2. Después de cambiar .env

Siempre ejecuta:
```bash
npm run build  # o reinicia npm run dev
php artisan config:clear
```

### 3. Usa el script de inicio

El script `start-dev.bat` ya está configurado correctamente. Úsalo siempre:
```bash
start-dev.bat
```

## Troubleshooting Adicional

### Error persiste después de la solución

1. **Limpia caché del navegador completamente**
   - Chrome: Ctrl + Shift + Delete → Borrar todo
   - Firefox: Ctrl + Shift + Delete → Borrar todo

2. **Verifica que no haya múltiples instancias de Reverb**
   ```bash
   netstat -ano | findstr ":8080"
   ```
   Solo debe haber una instancia corriendo.

3. **Verifica el archivo .env directamente**
   ```bash
   type .env | findstr "REVERB"
   ```

4. **Reinicia todo desde cero**
   ```bash
   fix-vite-cache.bat
   ```

### Error: "Cannot find module"

Si ves errores de módulos faltantes:
```bash
npm install
```

### Error: "Port 8080 already in use"

Otro proceso está usando el puerto 8080:
```bash
# Ver qué proceso usa el puerto
netstat -ano | findstr ":8080"

# Matar el proceso (reemplaza PID con el número mostrado)
taskkill /F /PID <PID>
```

## Resumen

✅ **Problema:** Vite usa credenciales cacheadas incorrectas
✅ **Solución:** Reconstruir assets y limpiar caché
✅ **Prevención:** Siempre sincronizar REVERB_APP_KEY y VITE_REVERB_APP_KEY

**Comando más importante:**
```bash
fix-vite-quick.bat
```

Después de ejecutarlo, **recarga el navegador con Ctrl + F5**.
