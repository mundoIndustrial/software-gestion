# Guía de Deployment - Mundo Industrial en VPS

## Información General
- **Dominio**: sistemamundoindustrial.online
- **Framework**: Laravel 12
- **Base de Datos**: MySQL
- **Servidor Web**: Nginx
- **Process Manager**: Supervisor
- **SO Recomendado**: Ubuntu 22.04 LTS

---

## 1. PREPARACIÓN DEL VPS

### 1.1 Conectarse al VPS
```bash
ssh root@tu_ip_vps
```

### 1.2 Actualizar sistema
```bash
apt update && apt upgrade -y
apt install -y curl wget git unzip zip
```

### 1.3 Instalar dependencias
```bash
# PHP 8.2 y extensiones
apt install -y php8.2-cli php8.2-fpm php8.2-mysql php8.2-redis php8.2-gd \
               php8.2-curl php8.2-xml php8.2-soap php8.2-intl \
               php8.2-bcmath php8.2-mbstring php8.2-zip

# Nginx
apt install -y nginx

# MySQL Client
apt install -y mysql-client

# Node.js (para compilar assets)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Supervisor (para mantener procesos vivos)
apt install -y supervisor

# Certbot (para SSL)
apt install -y certbot python3-certbot-nginx
```

---

## 2. CONFIGURACIÓN DEL PROYECTO

### 2.1 Crear usuario y directorios
```bash
# Crear usuario para la aplicación
useradd -m -s /bin/bash www-data

# Crear directorio del proyecto
mkdir -p /var/www/sistemamundoindustrial
cd /var/www/sistemamundoindustrial

# Establecer permisos
chown -R www-data:www-data /var/www/sistemamundoindustrial
chmod -R 755 /var/www/sistemamundoindustrial
```

### 2.2 Clonar o subir el proyecto
```bash
# Opción 1: Clonar desde Git
cd /var/www/sistemamundoindustrial
git clone tu_repositorio .

# Opción 2: Subir via SCP desde local
# En tu máquina local:
scp -r ./* root@tu_ip_vps:/var/www/sistemamundoindustrial/
```

### 2.3 Instalar dependencias del proyecto
```bash
cd /var/www/sistemamundoindustrial

# Instalar dependencias PHP
composer install --no-dev --optimize-autoloader

# Instalar dependencias Node
npm install
npm run build

# Establecer permisos
chown -R www-data:www-data /var/www/sistemamundoindustrial
chmod -R 755 /var/www/sistemamundoindustrial
chmod -R 775 storage bootstrap/cache public
```

---

## 3. CONFIGURACIÓN DE BASE DE DATOS

### 3.1 Crear base de datos y usuario
```bash
mysql -h tu_host_bd -u root -p

# Dentro de MySQL:
CREATE DATABASE mundo_industrial;
CREATE USER 'mundo_user'@'localhost' IDENTIFIED BY 'ChangeMe123!@#';
GRANT ALL PRIVILEGES ON mundo_industrial.* TO 'mundo_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3.2 Configurar archivo .env
```bash
cp .env.vps .env

# Editar con los datos correctos
nano .env

# Generar APP_KEY si es necesario
php artisan key:generate
```

### 3.3 Ejecutar migraciones
```bash
php artisan migrate --force
php artisan db:seed --force  # Si tienes seeders
```

---

## 4. CONFIGURACIÓN DE NGINX

### 4.1 Copiar configuración
```bash
cp nginx-mundo-industrial.conf /etc/nginx/sites-available/sistemamundoindustrial.online
ln -s /etc/nginx/sites-available/sistemamundoindustrial.online /etc/nginx/sites-enabled/

# Desactivar sitio default
rm /etc/nginx/sites-enabled/default
```

### 4.2 Validar configuración
```bash
nginx -t
```

### 4.3 Reiniciar Nginx
```bash
systemctl restart nginx
```

---

## 5. CONFIGURACIÓN DE SSL (Let's Encrypt)

### 5.1 Generar certificado
```bash
certbot certonly --standalone -d sistemamundoindustrial.online -d www.sistemamundoindustrial.online

# O con Nginx (si tienes la conf lista):
certbot --nginx -d sistemamundoindustrial.online -d www.sistemamundoindustrial.online
```

### 5.2 Renovación automática
```bash
systemctl enable certbot.timer
systemctl start certbot.timer
```

---

## 6. CONFIGURACIÓN DE SUPERVISOR

### 6.1 Copiar configuración
```bash
cp supervisor-mundo-industrial.conf /etc/supervisor/conf.d/mundo-industrial.conf

# Alternativa: Crear manualmente
nano /etc/supervisor/conf.d/mundo-industrial.conf
# Copiar el contenido de supervisor-mundo-industrial.conf
```

### 6.2 Activar supervisorctl
```bash
systemctl enable supervisor
systemctl start supervisor

# Recargar configuración
supervisorctl reread
supervisorctl update
supervisorctl start all
```

### 6.3 Verificar estado
```bash
supervisorctl status
```

---

## 7. CONFIGURACIÓN DE PHP-FPM

### 7.1 Configurar pool de PHP
```bash
# Editar configuración
nano /etc/php/8.2/fpm/pool.d/www.conf

# Cambiar si es necesario:
# listen = /run/php/php8.2-fpm.sock
# user = www-data
# group = www-data
```

### 7.2 Reiniciar PHP-FPM
```bash
systemctl restart php8.2-fpm
systemctl enable php8.2-fpm
```

---

## 8. CONFIGURACIÓN DE FIREWALL

```bash
# Habilitar firewall
ufw enable

# Permitir SSH, HTTP, HTTPS
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp

# WebSocket (si es necesario acceder directamente)
ufw allow 8080/tcp

# Ver estado
ufw status
```

---

## 9. CONFIGURACIÓN DE CRON JOBS (Laravel Scheduler)

### 9.1 Agregar cron job
```bash
crontab -e

# Agregar esta línea:
* * * * * cd /var/www/sistemamundoindustrial && php artisan schedule:run >> /dev/null 2>&1
```

---

## 10. MONITOREO Y LOGS

### 10.1 Ver logs
```bash
# Laravel
tail -f /var/log/supervisor/laravel.log

# Queue
tail -f /var/log/supervisor/queue.log

# Reverb (WebSocket)
tail -f /var/log/supervisor/reverb.log

# Nginx
tail -f /var/log/nginx/sistemamundoindustrial-error.log
```

### 10.2 Restart servicios
```bash
# Restart todo
supervisorctl restart all

# Restart específico
supervisorctl restart laravel
supervisorctl restart queue
supervisorctl restart reverb
```

---

## 11. SCRIPT DE INICIALIZACIÓN

### 11.1 Ejecutar script
```bash
# Dar permisos de ejecución
chmod +x /var/www/sistemamundoindustrial/iniciar-vps.sh

# Ejecutar como root
sudo /var/www/sistemamundoindustrial/iniciar-vps.sh
```

---

## 12. VERIFICACIÓN FINAL

```bash
# Verificar que sitio está online
curl -I https://sistemamundoindustrial.online

# Verificar base de datos
php artisan tinker
>>> DB::connection()->getPdo()

# Verificar storage
ls -la /var/www/sistemamundoindustrial/storage/

# Verificar cache
php artisan cache:clear
```

---

## 13. TROUBLESHOOTING

### 13.1 Permisos denegados
```bash
chown -R www-data:www-data /var/www/sistemamundoindustrial
chmod -R 755 /var/www/sistemamundoindustrial
chmod -R 775 /var/www/sistemamundoindustrial/storage
chmod -R 775 /var/www/sistemamundoindustrial/bootstrap/cache
```

### 13.2 Queue no funciona
```bash
# Ver estado
supervisorctl status queue

# Reiniciar
supervisorctl restart queue

# Ver logs
tail -f /var/log/supervisor/queue-error.log
```

### 13.3 WebSocket (Reverb) no conecta
```bash
# Verificar puerto 8080
netstat -tulpn | grep 8080

# Ver logs
tail -f /var/log/supervisor/reverb.log

# Reiniciar
supervisorctl restart reverb
```

### 13.4 Sitio no encuentra archivos
```bash
# Verificar permisos de public
chmod -R 755 /var/www/sistemamundoindustrial/public

# Limpiar cache de Nginx
systemctl reload nginx
```

---

## 14. COMANDOS ÚTILES

```bash
# Actualizar código desde Git
cd /var/www/sistemamundoindustrial
git pull origin main
composer install --no-dev
npm run build
chown -R www-data:www-data .
supervisorctl restart all

# Ver estado general
supervisorctl status
systemctl status nginx
systemctl status php8.2-fpm

# Limpiar caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Hacer backups
mysqldump -h localhost -u mundo_user -p mundo_industrial > backup_$(date +%Y%m%d_%H%M%S).sql

# Ver tamaño de directorios
du -sh /var/www/sistemamundoindustrial/*
```

---

## 15. CHECKLIST FINAL

- [ ] VPS con Ubuntu 22.04 LTS
- [ ] PHP 8.2 instalado
- [ ] MySQL accesible
- [ ] Nginx instalado y configurado
- [ ] SSL con Let's Encrypt
- [ ] Supervisor instalado
- [ ] Proyecto clonado/subido
- [ ] Dependencias instaladas
- [ ] Base de datos migrada
- [ ] Permisos configurados
- [ ] Servicios iniciados
- [ ] Dominio apuntando a VPS
- [ ] HTTPS funcionando
- [ ] WebSocket (Reverb) funcionando
- [ ] Queue worker activo
- [ ] Logs accesibles

---

## Contacto y Soporte

Para resolver problemas, revisa:
1. Logs de Supervisor: `/var/log/supervisor/`
2. Logs de Nginx: `/var/log/nginx/`
3. Logs de Laravel: `storage/logs/`
4. Estado de servicios: `supervisorctl status`
