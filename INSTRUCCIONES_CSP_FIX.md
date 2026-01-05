# 🔒 Solución al Error de Content Security Policy (CSP)

## ❌ Problema
El navegador muestra: **"Content Security Policy blocks the use of 'eval' in JavaScript"**

## ✅ Solución Aplicada

### 1. Middleware de Laravel Actualizado

✓ El middleware `SetSecurityHeaders.php` ahora:
- **Elimina** cualquier CSP header previo
- **Establece** un CSP permisivo con `'unsafe-eval'` y `'unsafe-inline'`

### 2. Pasos en tu VPS (PRODUCCIÓN)

Conéctate a tu VPS por SSH y ejecuta:

```bash
# 1. Editar configuración de nginx
sudo nano /etc/nginx/sites-available/sistemamundoindustrial.online
```

**VERIFICA** que en tu archivo nginx **NO** haya ninguna línea como:
```nginx
add_header Content-Security-Policy "...";
```

Si la encuentras, **ELIMÍNALA** o **COMÉNTALA** con `#`:
```nginx
# add_header Content-Security-Policy "...";  # Comentada - Laravel maneja CSP
```

**Deja solo estos headers de seguridad en nginx:**
```nginx
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
```

```bash
# 2. Verificar sintaxis de nginx
sudo nginx -t

# 3. Si no hay errores, recargar nginx
sudo systemctl reload nginx

# 4. Limpiar caché de Laravel
cd /var/www/mundoindustrial
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear

# 5. Verificar que el middleware esté registrado
php artisan route:list --middleware
```

### 3. Limpiar Caché del Navegador

**En tu navegador:**

1. Presiona `Ctrl + Shift + Delete` (Chrome/Edge) o `Ctrl + Shift + R`
2. Selecciona "Imágenes y archivos en caché"
3. Selecciona "Cookies y otros datos de sitios"
4. Haz clic en "Borrar datos"

**O usa modo incógnito:**
- `Ctrl + Shift + N` (Chrome/Edge)
- `Ctrl + Shift + P` (Firefox)

### 4. Verificar los Headers Enviados

Abre las **DevTools** del navegador:

1. Presiona `F12`
2. Ve a la pestaña **Network** (Red)
3. Recarga la página (`F5`)
4. Haz clic en la primera petición (documento HTML)
5. Ve a **Headers** → **Response Headers**
6. Busca `Content-Security-Policy`

**Debe verse así:**
```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net ...
```

Si ves `'unsafe-eval'` en el header, ¡está funcionando! 🎉

---

## 🐛 Si el Problema Persiste

### Diagnóstico Avanzado

```bash
# En tu VPS, verifica los headers reales enviados
curl -I https://sistemamundoindustrial.online | grep -i content-security-policy
```

### Causa Raíz Común

El problema suele ser que:
1. **Nginx** está agregando su propio CSP header
2. El navegador recibe **DOS headers CSP** (uno de nginx, otro de Laravel)
3. Cuando hay múltiples CSP, el navegador usa **el más restrictivo**

### Verificar Si Hay Headers Duplicados

```bash
# Ver todos los headers enviados
curl -v https://sistemamundoindustrial.online 2>&1 | grep -i content-security
```

Si ves la línea **DOS VECES**, tienes headers duplicados.

---

## 📝 Notas Importantes

- ⚠️ `'unsafe-eval'` y `'unsafe-inline'` reducen la seguridad, pero son necesarios para:
  - **Laravel Echo** (WebSockets)
  - **Pusher.js**
  - **SweetAlert2**
  - **Alpine.js** (si usa inline expressions)

- ✅ En el futuro, considera usar **nonces** o **hashes** en lugar de `'unsafe-inline'`

---

## 🔄 Aplicar Cambios en Producción

```bash
# Subir el middleware actualizado a tu VPS
git add app/Http/Middleware/SetSecurityHeaders.php
git commit -m "fix: Eliminar CSP headers duplicados"
git push

# En el VPS
cd /var/www/mundoindustrial
git pull
php artisan optimize:clear
```
