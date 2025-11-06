# 🌐 Guía para Ejecutar en Red Local

Esta guía te ayudará a configurar el proyecto para que otros computadores en la misma red puedan acceder.

## 📋 Requisitos Previos

- Todos los computadores deben estar en la **misma red local**
- El computador servidor debe tener **IP estática** o reservada en el router
- Firewall de Windows debe permitir conexiones en los puertos necesarios

## 🚀 Configuración Rápida (3 Pasos)

### Paso 1: Configurar Firewall

**Ejecuta como ADMINISTRADOR:**
```bash
config-firewall.bat
```

Este script:
- ✅ Abre el puerto 8000 (Laravel Server)
- ✅ Abre el puerto 8080 (Laravel Reverb/WebSocket)
- ✅ Abre el puerto 5173 (Vite Dev Server)

### Paso 2: Configurar Variables de Entorno

**Ejecuta normalmente:**
```bash
config-network.bat
```

Este script:
- 🔍 Detecta automáticamente tu IP local
- 📝 Genera la configuración necesaria
- ✅ Actualiza el archivo `.env`
- 🔄 Reconstruye los assets

### Paso 3: Iniciar Servicios

**Ejecuta normalmente:**
```bash
start-dev-network.bat
```

Este script inicia:
- ✅ NPM Dev Server (Vite)
- ✅ Laravel Reverb (WebSocket)
- ✅ Laravel Server (HTTP)

Todos configurados para aceptar conexiones de red.

## 🌐 Acceso desde Otros Computadores

Una vez configurado, los otros computadores pueden acceder usando:

```
http://[TU_IP]:8000
```

Por ejemplo:
```
http://192.168.1.100:8000
```

## 🔧 Configuración Manual (Avanzado)

Si prefieres configurar manualmente, edita el archivo `.env`:

### 1. Obtén tu IP local

En CMD ejecuta:
```bash
ipconfig
```

Busca tu **IPv4 Address** (ejemplo: 192.168.1.100)

### 2. Edita el archivo .env

Cambia estas líneas:
```env
# Antes (solo localhost)
APP_URL=http://127.0.0.1:8000
VITE_REVERB_HOST=127.0.0.1
REVERB_HOST=127.0.0.1
REVERB_SERVER_HOST=127.0.0.1

# Después (acceso en red)
APP_URL=http://192.168.1.100:8000
VITE_REVERB_HOST=192.168.1.100
REVERB_HOST=192.168.1.100
REVERB_SERVER_HOST=192.168.1.100
```

### 3. Reconstruye los assets

```bash
npm run build
php artisan config:clear
```

### 4. Inicia los servicios

```bash
php artisan serve --host=0.0.0.0 --port=8000
php artisan reverb:start --host=0.0.0.0 --port=8080
npm run dev -- --host
```

## 🔥 Configurar Firewall Manualmente

Si no puedes ejecutar el script como administrador:

### Windows Firewall

1. Abre **Panel de Control** → **Sistema y Seguridad** → **Firewall de Windows Defender**
2. Click en **Configuración avanzada**
3. Click en **Reglas de entrada** → **Nueva regla**
4. Selecciona **Puerto** → **Siguiente**
5. Selecciona **TCP** y escribe: `8000, 8080, 5173`
6. Selecciona **Permitir la conexión** → **Siguiente**
7. Marca todas las opciones (Dominio, Privado, Público) → **Siguiente**
8. Nombre: "Laravel + Reverb + Vite" → **Finalizar**

## 🧪 Verificar Configuración

### En el Servidor

1. Ejecuta `start-dev-network.bat`
2. Abre tu navegador en: `http://localhost:8000`
3. Verifica que funcione correctamente

### En Otro Computador

1. Abre un navegador
2. Accede a: `http://[IP_DEL_SERVIDOR]:8000`
3. Deberías ver la aplicación funcionando

### Verificar WebSocket (Tiempo Real)

En la consola del navegador (F12) deberías ver:
```
✅ WebSocket conectado exitosamente a Reverb
```

Si ves este mensaje, el tiempo real está funcionando.

## ❌ Solución de Problemas

### No puedo acceder desde otro computador

**Verifica:**
1. ✅ Ambos computadores están en la misma red
2. ✅ El Firewall permite las conexiones (puertos 8000, 8080, 5173)
3. ✅ La IP en `.env` es correcta
4. ✅ Los servicios están corriendo (`start-dev-network.bat`)

**Prueba de conectividad:**
```bash
# Desde otro computador, ejecuta en CMD:
ping [IP_DEL_SERVIDOR]
telnet [IP_DEL_SERVIDOR] 8000
```

### El tiempo real no funciona

**Verifica:**
1. ✅ Reverb está corriendo
2. ✅ El puerto 8080 está abierto en el Firewall
3. ✅ `VITE_REVERB_HOST` en `.env` tiene la IP correcta (no 127.0.0.1)
4. ✅ Ejecutaste `npm run build` después de cambiar `.env`

**En la consola del navegador:**
```javascript
// Verifica la configuración
console.log(import.meta.env.VITE_REVERB_HOST); // Debe mostrar tu IP, no 127.0.0.1
```

### Error: "Connection refused"

**Causa:** El Firewall está bloqueando las conexiones

**Solución:**
1. Ejecuta `config-firewall.bat` como administrador
2. O configura el Firewall manualmente

### Error: "WebSocket connection failed"

**Causa:** Reverb no está escuchando en 0.0.0.0

**Solución:**
1. Detén Reverb
2. Ejecuta: `php artisan reverb:start --host=0.0.0.0 --port=8080`

## 📝 Notas Importantes

### IP Dinámica vs IP Estática

Si tu IP cambia frecuentemente:
1. Configura una **IP estática** en tu router
2. O ejecuta `config-network.bat` cada vez que cambie tu IP

### Seguridad

⚠️ **IMPORTANTE:** Esta configuración es para **desarrollo en red local**.

**NO uses esto en producción** sin:
- Configurar HTTPS
- Implementar autenticación robusta
- Configurar un firewall adecuado
- Usar variables de entorno seguras

### Rendimiento

Para mejor rendimiento en red:
- Usa una conexión por cable (Ethernet) en lugar de WiFi
- Asegúrate de que el router no esté sobrecargado
- Considera usar un switch dedicado para la red de desarrollo

## 🔄 Volver a Configuración Local

Si quieres volver a usar solo en localhost:

1. Edita `.env` y cambia:
   ```env
   APP_URL=http://127.0.0.1:8000
   VITE_REVERB_HOST=127.0.0.1
   REVERB_HOST=127.0.0.1
   REVERB_SERVER_HOST=127.0.0.1
   ```

2. Reconstruye:
   ```bash
   npm run build
   php artisan config:clear
   ```

3. Usa el script normal:
   ```bash
   start-dev.bat
   ```

## 📞 Soporte

Si tienes problemas:
1. Revisa los logs en `storage/logs/laravel.log`
2. Verifica la consola del navegador (F12)
3. Ejecuta `php fix-reverb-config.php` para verificar configuración

## 📚 Archivos Relacionados

- `start-dev-network.bat` - Inicia servicios para red
- `config-network.bat` - Configura .env automáticamente
- `config-firewall.bat` - Configura Firewall de Windows
- `start-dev.bat` - Inicia servicios solo para localhost
- `GUIA-RED-LOCAL.md` - Esta guía

## ✅ Checklist de Configuración

- [ ] Ejecuté `config-firewall.bat` como administrador
- [ ] Ejecuté `config-network.bat` y apliqué los cambios
- [ ] Ejecuté `start-dev-network.bat`
- [ ] Verifiqué que funciona en localhost
- [ ] Probé acceder desde otro computador
- [ ] Verifiqué que el tiempo real funciona (WebSocket conectado)
- [ ] Documenté la IP del servidor para el equipo

¡Listo! Ahora tu aplicación está disponible en toda la red local. 🎉
