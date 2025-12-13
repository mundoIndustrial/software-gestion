# 🔐 GUÍA PASO A PASO: IMPLEMENTAR HTTPS

## OPCIÓN 1: cPanel (LA MÁS FÁCIL)

### Paso 1: Acceder a AutoSSL
1. Login en cPanel
2. Buscar "AutoSSL" en la barra de búsqueda
3. Click en "AutoSSL"

### Paso 2: Instalar Certificado
1. Click en "Manage AutoSSL"
2. Click en tu dominio
3. Click en "Install" o "Reinstall"
4. Esperar 5-15 minutos ✅

### Paso 3: Activar HTTPS Permanente
1. Volver al home de cPanel
2. Buscar "Redirects"
3. Click en "Redirects"
4. Elegir tu dominio en dropdown
5. Seleccionar "https://www.ejemplo.com"
6. Activar "Always use https://"
7. Click "Add" ✅

---

## OPCIÓN 2: Let's Encrypt + Certbot (VPS/Servidor Dedicado)

### Pre-requisito: Acceso SSH

```bash
# Conectar a tu servidor
ssh usuario@tuservidorip
```

### Paso 1: Instalar Certbot

**Para Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install certbot python3-certbot-apache
# o para Nginx:
sudo apt install certbot python3-certbot-nginx
```

**Para CentOS/RHEL:**
```bash
sudo yum install certbot python3-certbot-apache
# o para Nginx:
sudo yum install certbot python3-certbot-nginx
```

### Paso 2: Obtener Certificado

**Para Apache:**
```bash
sudo certbot --apache -d tudominio.com -d www.tudominio.com
```

**Para Nginx:**
```bash
sudo certbot --nginx -d tudominio.com -d www.tudominio.com
```

**Para usar Webroot (sin parar el servidor):**
```bash
sudo certbot certonly --webroot -w /ruta/al/public -d tudominio.com -d www.tudominio.com
```

### Paso 3: Seguir el Wizard

El certificado te hará preguntas:
1. Email de contacto: **tumail@ejemplo.com**
2. Aceptar términos: **Y**
3. Newsletter: **N** (opcional)

✅ **Certificado instalado en:** `/etc/letsencrypt/live/tudominio.com/`

### Paso 4: Configurar Auto-Renewal

```bash
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
sudo certbot renew --dry-run  # Test
```

✅ **Se renovará automáticamente antes de expirar**

### Paso 5: Forzar HTTPS en Apache

**Editar `/etc/apache2/sites-available/tudominio-le-ssl.conf`:**

```apache
# Ya debería estar, pero verifica:
<VirtualHost *:443>
    ServerName tudominio.com
    DocumentRoot /ruta/a/proyecto/public
    
    # FORCE HTTPS
    <IfModule mod_headers.c>
        Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    </IfModule>
    
    # SSL Configuration (Certbot lo agrega automáticamente)
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/tudominio.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/tudominio.com/privkey.pem
</VirtualHost>

# Redirect HTTP a HTTPS
<VirtualHost *:80>
    ServerName tudominio.com
    ServerAlias www.tudominio.com
    Redirect permanent / https://tudominio.com/
</VirtualHost>
```

**Habilitar módulos:**
```bash
sudo a2enmod ssl
sudo a2enmod headers
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Paso 6: Forzar HTTPS en Nginx

**Editar `/etc/nginx/sites-available/tudominio`:**

```nginx
# HTTP redirect to HTTPS
server {
    listen 80;
    server_name tudominio.com www.tudominio.com;
    return 301 https://$server_name$request_uri;
}

# HTTPS
server {
    listen 443 ssl http2;
    server_name tudominio.com www.tudominio.com;
    root /ruta/a/proyecto/public;

    ssl_certificate /etc/letsencrypt/live/tudominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tudominio.com/privkey.pem;

    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;

    # Laravel requirements
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

**Reiniciar Nginx:**
```bash
sudo systemctl restart nginx
```

---

## OPCIÓN 3: AWS / CloudFront

### Paso 1: Solicitar Certificado en ACM
1. AWS Console → ACM (Certificate Manager)
2. Click "Request Certificate"
3. Ingresar dominio: `tudominio.com`
4. Click "Add another name to this certificate"
5. Ingresar: `*.tudominio.com`
6. Click "Request"

### Paso 2: Validar Dominio
1. Ir a Route 53
2. Crear record CNAME que ACM te proporciona
3. Esperar validación (5-10 min)

### Paso 3: Crear CloudFront Distribution
1. CloudFront Console
2. Click "Create Distribution"
3. Origin domain: tu IP/dominio
4. Protocol: HTTPS
5. Certificate: Seleccionar el que creaste
6. Click "Create Distribution"

✅ **URL CloudFront con HTTPS automático**

---

## OPCIÓN 4: DigitalOcean App Platform

### Paso 1: Agregar Dominio
1. App Platform → Settings
2. Domains
3. Agregar tu dominio
4. DigitalOcean genera certificado automáticamente ✅

---

## VERIFICAR QUE HTTPS FUNCIONA ✅

### Test 1: En el navegador
```
https://tudominio.com
```
Debe mostrar candado 🔒 verde

### Test 2: SSL Labs (Verificar fortaleza)
```
https://www.ssllabs.com/ssltest/analyze.html?d=tudominio.com
```
**Objetivo: Puntuación A o A+**

### Test 3: Verificar HSTS
```bash
curl -I https://tudominio.com | grep Strict
```
Debe mostrar:
```
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

### Test 4: Verificar Redirect HTTP→HTTPS
```bash
curl -I http://tudominio.com
```
Debe mostrar:
```
HTTP/1.1 301 Moved Permanently
Location: https://tudominio.com/
```

---

## ACTUALIZAR LARAVEL

### Paso 1: En `.env`
```env
APP_URL=https://tudominio.com
FORCE_HTTPS=true
```

### Paso 2: En `config/app.php`
```php
'url' => env('APP_URL', 'https://tudominio.com'),
'asset_url' => env('ASSET_URL', 'https://tudominio.com'),
```

### Paso 3: En `AppServiceProvider.php`
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
```

### Paso 4: En `middleware.php`
```php
// Ya agregamos SetSecurityHeaders, pero verifica que esté:
->withMiddleware(function (Middleware $middleware): void {
    // Tu middleware CSP
})
```

---

## LIMPIAR CACHÉ

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan optimize:clear
```

---

## PROBLEMAS COMUNES

### ❌ "Mixed Content" (HTTP dentro de HTTPS)
**Solución:** Forzar HTTPS en AppServiceProvider (paso anterior)

### ❌ Certificado vencido
**Solución:** 
```bash
# Let's Encrypt se renueva automáticamente
# Pero verifica manualmente:
sudo certbot renew
```

### ❌ Certificado no válido para www.
**Solución:** Usar comodín
```bash
sudo certbot certonly --webroot -w /public -d tudominio.com -d *.tudominio.com
```

### ❌ HSTS error
**Solución:** No fuerces HSTS si aún debugueas. Usa primero `max-age=0`

---

## CHECKLIST FINAL ✅

- [ ] Certificado SSL instalado
- [ ] HTTP redirige a HTTPS (301)
- [ ] HSTS header configurado
- [ ] .env con APP_URL=https://...
- [ ] AppServiceProvider fuerza HTTPS
- [ ] CSP headers correctos (sin bloqueos)
- [ ] Test en SSL Labs: A o A+
- [ ] Caché limpio
- [ ] Lighthouse performance checkeado

---

## CUÁL OPCIÓN ELEGIR?

| Opción | Dificultad | Costo | Recomendado Para |
|--------|-----------|-------|-----------------|
| **cPanel** | Muy fácil | Incluido | Hosting compartido |
| **Certbot** | Media | Gratis | VPS/Dedicado |
| **AWS** | Difícil | $$ | Escala enterprise |
| **DigitalOcean** | Fácil | $ | Startups/pymes |

**Para ti: Probablemente cPanel o Certbot**

---

¿Cuál es tu caso? Cuéntame y te ayudo más específicamente.
