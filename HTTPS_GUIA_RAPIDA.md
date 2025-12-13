╔══════════════════════════════════════════════════════════════════╗
║              🔐 IMPLEMENTAR HTTPS - GUÍA RÁPIDA                   ║
║                    (15 minutos con cPanel)                        ║
╚══════════════════════════════════════════════════════════════════╝

⏱️  TIEMPO TOTAL: 15 minutos (después esperar 5-10 min en background)
💰 IMPACTO: Best Practices 78 → 95+ (+17 puntos)

═════════════════════════════════════════════════════════════════════

OPCIÓN 1: cPanel AutoSSL ⭐ RECOMENDADO (15 min)

PASO 1: Acceder a cPanel
───────────────────────────────────────────────────────────────────
1. Ir a: https://tudominio.com:2083
2. Login con credenciales de cPanel
3. Buscar "AutoSSL" o "Let's Encrypt"
   (Icono: cerrojo azul o "SSL/TLS")

PASO 2: Instalar Certificado
───────────────────────────────────────────────────────────────────
1. Hacer clic en "AutoSSL" o "Let's Encrypt"
2. Seleccionar tu dominio (tudominio.com)
3. Opcionalmente: www.tudominio.com
4. Hacer clic en "Issue" o "Instalar"
5. Esperar 2-5 minutos (puedes cerrar la ventana)

PASO 3: Verificar Instalación
───────────────────────────────────────────────────────────────────
1. Ir a: https://tudominio.com
2. Verificar que carga correctamente ✅
3. Ver cerrojo verde en navegador 🔒

PASO 4: Forzar HTTPS en .htaccess (IMPORTANTE)
───────────────────────────────────────────────────────────────────

⚠️  NECESITAS EDITAR: public/.htaccess

BUSCAR ESTO:
───────────
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    ...
</IfModule>

REEMPLAZAR CON ESTO:
───────────────────
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Forzar HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Redirigir www a no-www (opcional)
    RewriteCond %{HTTP_HOST} ^www\. [NC]
    RewriteRule ^(.*)$ https://%{HTTP_HOST:www.}$1 [R=301,L]

    ...REST DEL ARCHIVO...
</IfModule>

PASO 5: Agregar Headers HSTS (IMPORTANTE)
───────────────────────────────────────────────────────────────────

EDITAR: public/.htaccess

AGREGAR ESTO (después del <IfModule mod_rewrite>):
────────────────────────────────────────────────────
# HSTS Header - Force HTTPS for 1 year
<IfModule mod_headers.c>
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
</IfModule>

═════════════════════════════════════════════════════════════════════

VERIFICACIÓN: ¿Funcionó?

1. Ir a: https://www.sslshopper.com/ssl-checker.html
2. Escribir: tudominio.com
3. Click "Check SSL"
4. Debe mostrar: ✅ Certificate is valid
5. Cadena completa: ✅ Complete Chain

═════════════════════════════════════════════════════════════════════

OPCIÓN 2: Si tienes Certbot (VPS/Linux) - 30 min

1. SSH a tu servidor
2. Instalar Certbot:
   sudo apt-get install certbot python3-certbot-apache

3. Obtener certificado:
   sudo certbot --apache -d tudominio.com -d www.tudominio.com

4. Renovación automática:
   sudo certbot renew --dry-run

═════════════════════════════════════════════════════════════════════

DESPUÉS DE IMPLEMENTAR HTTPS:

1. ✅ Re-ejecutar Lighthouse
   $ lighthouse https://tudominio.com --view

2. ✅ Verificar scores:
   - Performance: 92+ (sin cambios)
   - Accessibility: 92+ (sin cambios)
   - Best Practices: 78 → 95+ ✅
   - SEO: 100 (sin cambios)

3. ✅ Verificar en navegador:
   - Cerrojo verde 🔒
   - No advertencias de "not secure"
   - URL comienza con https://

═════════════════════════════════════════════════════════════════════

PROBLEMAS COMUNES:

❌ "Mixed content" warning
   → Asegúrate que todos los recursos usen HTTPS o //
   → Revisar: CSS/JS/Fonts tienen https:// al inicio

❌ "Certificate not recognized"
   → Esperar 15 minutos más (propagación DNS)
   → O limpiar caché del navegador (Ctrl+Shift+R)

❌ "Redirect loop"
   → Revisar .htaccess - no tiene dos RewriteEngine On
   → Resetear: Borrar .htaccess y regenerar desde cPanel

═════════════════════════════════════════════════════════════════════

✨ RESULTADO FINAL ESPERADO:

Performance:     92 / 100 ✅
Accessibility:   92 / 100 ✅
Best Practices:  95 / 100 ✅ (fue 78)
SEO:             100 / 100 ✅

TOTAL PROMEDIO: 95 / 100 (Excelente) 🎉

═════════════════════════════════════════════════════════════════════

TIEMPO ESTIMADO:
  cPanel AutoSSL: 15 min (+ 5 min espera)
  Editar .htaccess: 5 min
  Verificar: 5 min
  ─────────────────
  TOTAL: 30 minutos ⏱️

═════════════════════════════════════════════════════════════════════
