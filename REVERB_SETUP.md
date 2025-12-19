# 🚀 CONFIGURACIÓN DE LARAVEL REVERB - WEBSOCKET EN TIEMPO REAL

## ❌ Problema Actual
- ❌ WebSocket connection failed: `wss://sistemamundoindustrial.online:8080`
- ❌ Reverb no está configurado correctamente
- ❌ Las notificaciones y actualizaciones en tiempo real no funcionan

---

## ✅ SOLUCIÓN: INSTALAR Y CONFIGURAR REVERB

### Paso 1️⃣ : Hacer scripts ejecutables
```bash
cd /var/www/mundoindustrial
chmod +x instalar-reverb.sh
chmod +x diagnostico-reverb.sh
chmod +x iniciar-reverb.sh
```

### Paso 2️⃣ : Ejecutar instalación (como root)
```bash
sudo ./instalar-reverb.sh
```

Este script hará automáticamente:
- ✅ Instalar Laravel Reverb (si no está)
- ✅ Copiar configuración a Supervisor
- ✅ Iniciar Reverb en el puerto 8080
- ✅ Configurar para iniciar automáticamente al reiniciar
- ✅ Limpiar cache de Laravel

### Paso 3️⃣ : Verificar que funciona
```bash
# Ver estado
sudo supervisorctl status reverb

# Ver logs
tail -f /var/log/mundo-industrial/reverb.log

# Verificar puerto
netstat -tln | grep 8080
```

---

## 🔧 ARCHIVOS CREADOS

### 1. `.env` (Actualizado)
- Variables de Reverb configuradas correctamente
- Paths de certificados SSL
- Configuración de cliente y servidor

### 2. `reverb.conf`
- Configuración de Supervisor para ejecutar Reverb
- Reinicio automático si falla
- Logs centralizados

### 3. `instalar-reverb.sh`
- Script de instalación automatizada
- Configura Supervisor
- Inicia Reverb

### 4. `diagnostico-reverb.sh`
- Verifica que todo esté funcionando
- Detecta problemas comunes
- Proporciona soluciones

### 5. `iniciar-reverb.sh`
- Inicia Reverb manualmente (si es necesario)
- Con soporte para SSL

---

## 📊 CONFIGURACIÓN EN DETALLE

### Frontend (.env)
```dotenv
VITE_REVERB_HOST=sistemamundoindustrial.online
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME=https
VITE_REVERB_APP_KEY=mundo-industrial-key
```

### Backend (.env)
```dotenv
BROADCAST_DRIVER=reverb
BROADCAST_CONNECTION=reverb

REVERB_HOST=sistemamundoindustrial.online
REVERB_PORT=8080
REVERB_SCHEME=https

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```

### Supervisor (`reverb.conf`)
- Comando: `php artisan reverb:start --host=0.0.0.0 --port=8080`
- Usuario: `www-data`
- Reinicio automático: SÍ
- Logs: `/var/log/mundo-industrial/reverb.log`

---

## 🔒 SSL/TLS PARA WEBSOCKET SEGURO (WSS)

### Certificados automáticos (Let's Encrypt)
Si usas certificados de Let's Encrypt, el script detecta automáticamente:
- 📄 `/etc/letsencrypt/live/sistemamundoindustrial.online/fullchain.pem`
- 🔑 `/etc/letsencrypt/live/sistemamundoindustrial.online/privkey.pem`

### Verificar certificados
```bash
ls -la /etc/letsencrypt/live/sistemamundoindustrial.online/

# Ver fecha de expiración
openssl x509 -enddate -noout -in /etc/letsencrypt/live/sistemamundoindustrial.online/fullchain.pem
```

---

## 🧪 PRUEBAS DE TIEMPO REAL

### 1. Verificar en Navegador
Abre la consola del navegador (F12) y busca:
- ✅ `✅ WebSocket conectado exitosamente a Reverb`
- ❌ `❌ Error de conexión WebSocket`

### 2. Test de conexión
```bash
# Verificar que Reverb está escuchando
netstat -tln | grep 8080

# Debería ver algo como:
# tcp  0  0 0.0.0.0:8080  0.0.0.0:*  LISTEN
```

### 3. Test de evento de prueba
Usa JavaScript en la consola:
```javascript
// Suscribirse a canal
window.Echo.channel('ordenes')
    .listen('OrdenActualizada', (data) => {
        console.log('✅ Evento recibido:', data);
    });

// Deberías ver "✅ Evento recibido" en tiempo real
```

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Error: "WebSocket connection failed"

**Causa 1: Reverb no está corriendo**
```bash
# Verificar estado
sudo supervisorctl status reverb

# Debería mostrar: RUNNING
```

**Causa 2: Puerto 8080 bloqueado por firewall**
```bash
# Abrir puerto en firewall (Ubuntu/Debian)
sudo ufw allow 8080/tcp
sudo ufw reload
```

**Causa 3: Nginx no está configurado para WebSocket**
```bash
# Verificar archivo Nginx
grep -i "websocket\|upgrade" /etc/nginx/sites-enabled/sistemamundoindustrial.online

# Debería tener:
# proxy_http_version 1.1;
# proxy_set_header Upgrade $http_upgrade;
# proxy_set_header Connection "upgrade";
```

**Causa 4: Certificado SSL inválido**
```bash
# Generar nuevo certificado
sudo certbot renew --force-renewal -d sistemamundoindustrial.online

# O generar uno nuevo
sudo certbot certonly --standalone -d sistemamundoindustrial.online
```

### Error: "Port already in use"
```bash
# Ver qué proceso está usando puerto 8080
sudo lsof -i :8080

# Matar el proceso
sudo kill -9 <PID>
```

### Reverb se detiene constantemente
```bash
# Ver logs detallados
tail -50 /var/log/mundo-industrial/reverb.log

# Aumentar timeout en reverb.conf
# stopwaitsecs=3600
```

---

## 📝 COMANDOS RÁPIDOS

```bash
# Estado
sudo supervisorctl status reverb

# Logs
tail -f /var/log/mundo-industrial/reverb.log

# Reiniciar
sudo supervisorctl restart reverb

# Detener
sudo supervisorctl stop reverb

# Iniciar
sudo supervisorctl start reverb

# Recargar configuración de Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Diagnóstico completo
sudo /var/www/mundoindustrial/diagnostico-reverb.sh

# Limpiar cache Laravel
cd /var/www/mundoindustrial
php artisan config:cache
php artisan cache:clear
```

---

## ✅ VERIFICACIÓN FINAL

Después de instalar, deberías ver:

```
✅ Laravel Echo configurado
✅ WebSocket conectado exitosamente a Reverb
✅ Listener de órdenes configurado
✅ Colores condicionales aplicados
✅ Sistema de filtros inicializado
```

En lugar de:
```
❌ WebSocket connection failed
❌ Failed to load resource: the server responded with a status of 500
```

---

## 🆘 Si sigue fallando

Ejecuta el diagnóstico:
```bash
sudo /var/www/mundoindustrial/diagnostico-reverb.sh
```

Y revisa:
1. **Logs**: `/var/log/mundo-industrial/reverb.log`
2. **Estado Supervisor**: `sudo supervisorctl status`
3. **Puertos abiertos**: `netstat -tln`
4. **Errores Nginx**: `sudo tail /var/log/nginx/error.log`
5. **Errores Laravel**: `tail /var/www/mundoindustrial/storage/logs/laravel.log`

---

## 📞 Contacto / Soporte

Para más información sobre Laravel Reverb:
- Documentación oficial: https://reverb.laravel.com
- GitHub: https://github.com/laravel/reverb

