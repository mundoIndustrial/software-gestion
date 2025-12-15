# RESUMEN - DEPLOYMENT EN VPS

## 📋 Archivos Generados

1. **`iniciar-vps.sh`** - Script de inicialización en el VPS (después de setup)
2. **`.env.vps`** - Archivo de configuración para producción
3. **`nginx-mundo-industrial.conf`** - Configuración de Nginx
4. **`supervisor-mundo-industrial.conf`** - Configuración de Supervisor (procesos)
5. **`actualizar-vps.sh`** - Script para actualizar el código
6. **`sync-vps.bat`** - Script para sincronizar código desde Windows
7. **`GUIA_DEPLOYMENT_VPS.md`** - Guía completa paso a paso

---

## ⚡ RESUMEN RÁPIDO

### FASE 1: SETUP INICIAL (Una sola vez)

1. **En el VPS (SSH)**:
```bash
# 1. Actualizar sistema
apt update && apt upgrade -y

# 2. Instalar dependencias (ver GUIA_DEPLOYMENT_VPS.md sección 1.3)
apt install -y php8.2-cli php8.2-fpm php8.2-mysql ... (ver guía)

# 3. Crear directorios
mkdir -p /var/www/sistemamundoindustrial
chown -R www-data:www-data /var/www/sistemamundoindustrial

# 4. Subir proyecto (vía Git o SCP)
# Opción Git:
cd /var/www/sistemamundoindustrial
git clone tu_repo .

# 5. Instalar dependencias
composer install --no-dev
npm install
npm run build

# 6. Configurar .env
cp .env.vps .env
nano .env  # Editar credenciales de BD

# 7. Ejecutar migraciones
php artisan migrate --force
php artisan key:generate

# 8. Copiar configuraciones
cp nginx-mundo-industrial.conf /etc/nginx/sites-available/sistemamundoindustrial.online
ln -s /etc/nginx/sites-available/sistemamundoindustrial.online /etc/nginx/sites-enabled/

cp supervisor-mundo-industrial.conf /etc/supervisor/conf.d/mundo-industrial.conf

# 9. SSL (Let's Encrypt)
certbot --nginx -d sistemamundoindustrial.online

# 10. Iniciar servicios
systemctl restart nginx
supervisorctl reread && supervisorctl update && supervisorctl start all
```

---

### FASE 2: ACTUALIZACIONES FUTURAS

#### Opción A: Desde local (Windows)
```bash
# En PowerShell desde tu proyecto:
.\sync-vps.bat 192.168.1.100 root
```

#### Opción B: Directamente en VPS
```bash
cd /var/www/sistemamundoindustrial
git pull origin main
bash actualizar-vps.sh
```

---

## 🔧 CONFIGURACIÓN CLAVE

### Variables de entorno importantes en `.env.vps`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sistemamundoindustrial.online

DB_HOST=localhost
DB_DATABASE=mundo_industrial
DB_USERNAME=mundo_user
DB_PASSWORD=ChangeMe123!@#

BROADCAST_CONNECTION=reverb
REVERB_HOST=sistemamundoindustrial.online
REVERB_PORT=443
REVERB_SCHEME=https

SESSION_DOMAIN=.sistemamundoindustrial.online
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16
```

---

## 📊 ESTRUCTURA DE SERVICIOS

```
Nginx (Puerto 80/443)
    ↓
    ├─→ Laravel App (PHP-FPM en 127.0.0.1:9000)
    │    └─→ Base de Datos (MySQL)
    │
    ├─→ WebSocket (Reverb en 127.0.0.1:8080)
    │    └─→ Supervisor: programa reverb
    │
    └─→ Queue Worker
         └─→ Supervisor: programa queue
```

Todos gestionados por **Supervisor** (ver estado con: `supervisorctl status`)

---

## 🐛 TROUBLESHOOTING

### El sitio no carga
```bash
# Verificar Nginx
nginx -t
systemctl status nginx
tail -f /var/log/nginx/sistemamundoindustrial-error.log
```

### Permisos denegados
```bash
chown -R www-data:www-data /var/www/sistemamundoindustrial
chmod -R 775 storage bootstrap/cache
```

### Queue no procesa
```bash
supervisorctl restart queue
tail -f /var/log/supervisor/queue.log
```

### WebSocket (Reverb) no conecta
```bash
supervisorctl restart reverb
tail -f /var/log/supervisor/reverb.log
netstat -tulpn | grep 8080
```

---

## 📝 CHECKLIST PRE-DEPLOYMENT

- [ ] Dominio apuntando a VPS (cambiar DNS)
- [ ] SSH acceso al VPS
- [ ] MySQL accesible (host, usuario, contraseña)
- [ ] Certificado SSL Let's Encrypt válido
- [ ] Archivo `.env.vps` configurado
- [ ] Todas las migraciones creadas
- [ ] Assets compilados (`npm run build`)
- [ ] Pruebas funcionales en local

---

## 🚀 COMANDOS FRECUENTES VPS

```bash
# Ver estado de todo
supervisorctl status

# Reiniciar un servicio
supervisorctl restart laravel
supervisorctl restart queue
supervisorctl restart reverb

# Ver logs en tiempo real
tail -f /var/log/supervisor/laravel.log
tail -f /var/log/nginx/sistemamundoindustrial-error.log

# Actualizar código
cd /var/www/sistemamundoindustrial && git pull && bash actualizar-vps.sh

# Limpiar caches
php artisan cache:clear
php artisan config:clear

# Hacer backup BD
mysqldump -u mundo_user -p mundo_industrial > backup_$(date +%Y%m%d).sql
```

---

## 💾 RESPALDO DE DATOS

```bash
# Backup completo
tar -czf backup_$(date +%Y%m%d).tar.gz /var/www/sistemamundoindustrial/storage/
mysqldump -u mundo_user -p mundo_industrial | gzip > bd_backup_$(date +%Y%m%d).sql.gz

# Descargar localmente
scp root@vps:/var/backup_*.gz ./backups/
```

---

## 📞 PRÓXIMOS PASOS

1. **Obtener IP/acceso SSH del VPS**
2. **Configurar DNS del dominio** (apuntar a IP del VPS)
3. **Seguir guía completa** en `GUIA_DEPLOYMENT_VPS.md`
4. **Ejecutar setup inicial** (FASE 1)
5. **Probar en producción**: https://sistemamundoindustrial.online
6. **Usar scripts de actualización** para cambios futuros

---

## 🎯 IMPORTANTE

- **NUNCA** commit `.env` a Git
- Cambiar contraseña de BD después del deployment
- Activar backups automáticos
- Monitorear logs regularmente
- Actualizar PHP y Nginx periódicamente
- Usar HTTPS siempre
