# ✅ Checklist Completo - Problemas de Acceso a Imágenes (Storage 403 Forbidden)

## 📋 Tabla de Contenidos
1. [Diagnóstico Rápido](#diagnóstico-rápido)
2. [Checklist Manual Paso a Paso](#checklist-manual-paso-a-paso)
3. [Scripts Automáticos](#scripts-automáticos)
4. [Validación Final](#validación-final)

---

## 🔍 Diagnóstico Rápido

**Síntomas comunes:**
- ❌ `GET /storage/pedidos/2764/imagen1.jpg` → 403 Forbidden
- ❌ Las URLs se generan correctamente pero no sirven las imágenes
- ❌ `public/storage` no existe o apunta al lugar incorrecto
- ❌ Permisos incorrectos en `storage/app/public`

---

## ✅ Checklist Manual Paso a Paso

### 1️⃣ Verificar que el Enlace Simbólico Existe

#### En Linux/Mac:
```bash
# Ver si existe
ls -la public/storage

# Resultado esperado:
# lrwxrwxrwx 1 user group ... public/storage -> ../storage/app/public

# Si NO existe o está roto, crearlo:
php artisan storage:link

# Verificar que funciona:
test -L public/storage && echo "✅ Enlace simbólico OK" || echo "❌ Problema"
```

#### En Windows (PowerShell):
```powershell
# Verificar si existe
Get-Item -Path "public\storage" -ErrorAction SilentlyContinue | Select-Object FullName, LinkType

# Si NO existe, crear con:
php artisan storage:link

# O manualmente (requiere permisos de administrador):
New-Item -ItemType SymbolicLink -Path "public\storage" -Target "..\storage\app\public" -Force
```

---

### 2️⃣ Verificar Permisos del Directorio `storage`

#### En Linux/Mac:
```bash
# Ver permisos actuales
ls -la storage/

# Resultado esperado (rwxrwxr-x para usuario/grupo):
# drwxrwxr-x  user group  storage/

# Verificar permisos específicos de pedidos
find storage/app/public/pedidos -type d -exec ls -ld {} \;

# Ver propietario/grupo
ls -la storage/app/public/

# Corregir si es necesario:
chmod -R 755 storage/app/public
chmod -R 755 storage/logs
chmod -R 755 storage/cache
```

#### En Windows (PowerShell):
```powershell
# Ver permisos
Get-Acl "storage\app\public" | Format-List

# Ver tamaño y contenido
Get-ChildItem -Recurse "storage\app\public" | Measure-Object

# Las carpetas deberían ser accesibles por IIS AppPool o Apache Service
```

---

### 3️⃣ Verificar que `config/filesystems.php` está Correcto

```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL') . '/storage',  // ← IMPORTANTE
        'visibility' => 'public',
    ],
],
```

**Verificar en la app:**
```bash
php artisan tinker
>>> config('filesystems.disks.public')
>>> Storage::disk('public')->url('pedidos/2764/imagen.jpg')
```

---

### 4️⃣ Verificar Configuración del Servidor Web

#### ✅ Si usas Apache:
```bash
# Verificar que mod_rewrite está habilitado
apache2ctl -M | grep rewrite

# Resultado esperado: rewrite_module (shared)

# Habilitar si no está:
sudo a2enmod rewrite

# Restart Apache:
sudo systemctl restart apache2
```

**Verificar `.htaccess` en `public/`:**
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
```

#### ✅ Si usas Nginx:
```nginx
# En /etc/nginx/sites-available/tu-sitio.conf o similar:

location /storage {
    # Esto sirve archivos directamente desde storage/app/public
    alias /ruta/absoluta/storage/app/public;
    
    # Permitir que se cacheen imágenes
    expires 7d;
    add_header Cache-Control "public, immutable";
}

# O mejor, usa:
location ~ ^/storage/(.*)$ {
    # Asegurar que no pase a index.php
    try_files $uri =404;
}
```

**Restart Nginx:**
```bash
sudo systemctl restart nginx
```

#### ✅ Si usas PHP Built-in Server (desarrollo):
```bash
php artisan serve
# Debería servir /storage correctamente
```

---

### 5️⃣ Verificar Pertenencia de Usuario/Grupo (Linux)

```bash
# Ver quién es propietario de storage
ls -la storage/ | head -5

# Ver quién es el usuario de Apache/PHP
ps aux | grep apache
ps aux | grep php-fpm

# Ejemplo: Si Apache corre como `www-data`
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
sudo chmod -R 755 storage/
sudo chmod -R 755 bootstrap/cache/
```

---

### 6️⃣ Verificar que las Imágenes se Guardan Correctamente

```bash
# Ver si existen archivos
find storage/app/public/pedidos -type f | head -10

# Verificar permisos de archivos específicos
ls -la storage/app/public/pedidos/2764/

# Resultado esperado:
# -rw-r--r-- 1 www-data www-data 123456 Jan 25 10:30 imagen1.jpg
```

---

### 7️⃣ Verificar URLs Generadas

```php
// En tu controlador o tinker:

// Opción 1: Storage facade
Storage::disk('public')->url('pedidos/2764/imagen.jpg');
// Resultado esperado: /storage/pedidos/2764/imagen.jpg

// Opción 2: asset() helper
asset('storage/pedidos/2764/imagen.jpg');
// Resultado esperado: http://localhost:8000/storage/pedidos/2764/imagen.jpg
```

---

### 8️⃣ Prueba en el Navegador

1. Visita: `http://tu-sitio.com/storage/pedidos/2764/imagen.jpg`
2. Si ves **200 OK** → ✅ **Problema resuelto**
3. Si ves **403 Forbidden** → Revisa permisos de archivo/carpeta
4. Si ves **404 Not Found** → El enlace simbólico no funciona

---

## 🤖 Scripts Automáticos

### Script para Linux/Mac

**Archivo:** `fix-storage-permissions.sh`

```bash
#!/bin/bash

set -e  # Salir si hay error

echo "🔧 === ARREGLANDO PERMISOS DE STORAGE ==="
echo ""

# 1. Crear enlace simbólico
echo "1️⃣  Creando/verificando enlace simbólico..."
php artisan storage:link
echo "✅ Enlace simbólico listo"
echo ""

# 2. Obtener usuario del servidor web
echo "2️⃣  Detectando usuario del servidor web..."
WEB_USER=""
if command -v apache2ctl &> /dev/null; then
    WEB_USER=$(apache2ctl -S 2>/dev/null | grep "User:" | awk '{print $2}' || echo "www-data")
    echo "📌 Apache detectado, usuario: $WEB_USER"
elif pgrep -x "nginx" > /dev/null; then
    WEB_USER=$(ps aux | grep nginx | grep -v grep | awk '{print $1}' | head -1)
    echo "📌 Nginx detectado, usuario: $WEB_USER"
else
    WEB_USER="www-data"
    echo "📌 Usuario por defecto: $WEB_USER"
fi
echo ""

# 3. Cambiar propiedad
echo "3️⃣  Cambiando permisos de directorios..."
sudo chown -R $WEB_USER:$WEB_USER storage/
sudo chown -R $WEB_USER:$WEB_USER bootstrap/cache/
echo "✅ Propiedad actualizada: $WEB_USER"
echo ""

# 4. Establecer permisos correctos
echo "4️⃣  Estableciendo permisos (755 directorios, 644 archivos)..."
sudo find storage/ -type d -exec chmod 755 {} \;
sudo find storage/ -type f -exec chmod 644 {} \;
sudo find bootstrap/cache/ -type d -exec chmod 755 {} \;
sudo find bootstrap/cache/ -type f -exec chmod 644 {} \;
echo "✅ Permisos actualizados"
echo ""

# 5. Habilitar mod_rewrite en Apache
if command -v apache2ctl &> /dev/null; then
    echo "5️⃣  Habilitando mod_rewrite en Apache..."
    sudo a2enmod rewrite 2>/dev/null || echo "ℹ️  mod_rewrite ya estaba habilitado"
    sudo systemctl restart apache2
    echo "✅ Apache reiniciado"
fi
echo ""

# 6. Limpiar caché de Laravel
echo "6️⃣  Limpiando caché de Laravel..."
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:clear
echo "✅ Caché limpiado"
echo ""

# 7. Verificar que todo funciona
echo "7️⃣  VERIFICACIÓN FINAL:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ -L public/storage ]; then
    echo "✅ Enlace simbólico: OK"
else
    echo "❌ Enlace simbólico: NO EXISTE"
fi

# Verificar permisos de storage/app/public
STORAGE_PERMS=$(stat -c "%A" storage/app/public)
echo "📁 Permisos storage/app/public: $STORAGE_PERMS"

# Verificar si hay imágenes
IMAGES_COUNT=$(find storage/app/public/pedidos -type f 2>/dev/null | wc -l)
echo "🖼️  Imágenes en storage: $IMAGES_COUNT archivos"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ REPARACIÓN COMPLETADA"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📌 PRÓXIMOS PASOS:"
echo "1. Visita: http://tu-sitio.com/storage/pedidos/{id}/imagen.jpg"
echo "2. Si ves 403: Revisa permisos con: ls -la storage/app/public"
echo "3. Si ves 404: Verifica que el enlace existe: ls -la public/storage"
echo ""
```

**Usar:**
```bash
chmod +x fix-storage-permissions.sh
./fix-storage-permissions.sh
```

---

### Script para Windows (PowerShell)

**Archivo:** `fix-storage-permissions.ps1`

```powershell
# Run as Administrator!

Write-Host "🔧 === ARREGLANDO PERMISOS DE STORAGE (WINDOWS) ===" -ForegroundColor Cyan
Write-Host ""

# 1. Crear enlace simbólico
Write-Host "1️⃣  Creando/verificando enlace simbólico..." -ForegroundColor Yellow
php artisan storage:link
Write-Host "✅ Enlace simbólico listo" -ForegroundColor Green
Write-Host ""

# 2. Verificar enlace
Write-Host "2️⃣  Verificando enlace simbólico..." -ForegroundColor Yellow
$symlinkExists = Test-Path "public\storage" -PathType Container
if ($symlinkExists) {
    $item = Get-Item "public\storage"
    if ($item.LinkType -eq "SymbolicLink") {
        Write-Host "✅ Enlace simbólico válido" -ForegroundColor Green
    } else {
        Write-Host "⚠️  public\storage existe pero no es un enlace simbólico" -ForegroundColor Yellow
    }
} else {
    Write-Host "❌ Enlace simbólico no encontrado" -ForegroundColor Red
}
Write-Host ""

# 3. Darle permisos a carpetas
Write-Host "3️⃣  Ajustando permisos de carpetas..." -ForegroundColor Yellow

$folders = @(
    "storage\app\public",
    "storage\logs",
    "storage\framework\cache",
    "bootstrap\cache"
)

foreach ($folder in $folders) {
    if (Test-Path $folder) {
        # Heredar permisos del padre
        icacls $folder /inheritance:e /grant:r "*S-1-5-20:(OI)(CI)F" 2>$null
        Write-Host "✅ $folder - Permisos actualizados" -ForegroundColor Green
    }
}
Write-Host ""

# 4. Identificar usuario de IIS o Apache
Write-Host "4️⃣  Detectando servidor web..." -ForegroundColor Yellow

$iisAppPool = Get-IISAppPool -ErrorAction SilentlyContinue | Select-Object -First 1
if ($iisAppPool) {
    Write-Host "📌 IIS detectado" -ForegroundColor Cyan
    $appPoolIdentity = $iisAppPool.processModel.identityType
    Write-Host "   Pool: $($iisAppPool.name) | Identity: $appPoolIdentity" -ForegroundColor Gray
} else {
    Write-Host "📌 Apache o servidor manual detectado" -ForegroundColor Cyan
}
Write-Host ""

# 5. Limpiar caché
Write-Host "5️⃣  Limpiando caché de Laravel..." -ForegroundColor Yellow
php artisan cache:clear 2>$null
php artisan route:clear 2>$null
php artisan view:clear 2>$null
php artisan config:clear 2>$null
Write-Host "✅ Caché limpiado" -ForegroundColor Green
Write-Host ""

# 6. Verificación final
Write-Host "6️⃣  VERIFICACIÓN FINAL:" -ForegroundColor Cyan
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray

if (Test-Path "public\storage") {
    Write-Host "✅ Enlace simbólico: OK" -ForegroundColor Green
} else {
    Write-Host "❌ Enlace simbólico: NO EXISTE" -ForegroundColor Red
}

# Ver carpetas de almacenamiento
if (Test-Path "storage\app\public") {
    $itemCount = @(Get-ChildItem -Path "storage\app\public" -Recurse -File -ErrorAction SilentlyContinue).Count
    Write-Host "📁 Archivos en storage: $itemCount" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray
Write-Host "✅ REPARACIÓN COMPLETADA" -ForegroundColor Green
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Gray
Write-Host ""
Write-Host "📌 PRÓXIMOS PASOS:" -ForegroundColor Yellow
Write-Host "1. Abre: http://tu-sitio.com/storage/pedidos/{id}/imagen.jpg"
Write-Host "2. Si ves 403: Revisa permisos en propiedades de archivos"
Write-Host "3. Si ves 404: Verifica que public\storage existe"
Write-Host ""
```

**Usar:**
```powershell
# Ejecutar como Administrador
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
.\fix-storage-permissions.ps1
```

---

## ✅ Validación Final

### 1️⃣ Checklist de Verificación

```bash
# Linux/Mac
echo "🔍 Checklist de Verificación"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# ✅ 1. Enlace simbólico
[ -L public/storage ] && echo "✅ Enlace simbólico existe" || echo "❌ Enlace simbólico falta"

# ✅ 2. Directorio
[ -d storage/app/public ] && echo "✅ storage/app/public existe" || echo "❌ Falta storage/app/public"

# ✅ 3. Permisos (debe ser 755 o mejor)
PERMS=$(stat -c "%A" storage/app/public | cut -c2-4)
if [[ "$PERMS" == "rwx" ]]; then
    echo "✅ Permisos de storage/app/public: CORRECTOS"
else
    echo "⚠️  Permisos de storage/app/public: $PERMS (revisá)"
fi

# ✅ 4. Propietario
OWNER=$(stat -c "%U:%G" storage/app/public)
echo "📁 Propietario: $OWNER"

# ✅ 5. Imágenes
COUNT=$(find storage/app/public -type f | wc -l)
echo "🖼️  Archivos almacenados: $COUNT"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
```

### 2️⃣ Prueba en PHP Tinker

```php
php artisan tinker

// Verificar configuración
>>> config('filesystems.disks.public')
>>> Storage::disk('public')->url('test.jpg')

// Crear un archivo de prueba
>>> Storage::disk('public')->put('test-file.txt', 'Test content')
>>> Storage::disk('public')->url('test-file.txt')

// Verificar que existe
>>> file_exists(storage_path('app/public/test-file.txt'))
```

### 3️⃣ Prueba en el Navegador

```
Visita estas URLs:
1. http://localhost:8000/storage
   → Debería mostrar listado de carpetas

2. http://localhost:8000/storage/test-file.txt
   → Debería descargar o mostrar "Test content"

3. http://localhost:8000/storage/pedidos/2764/imagen.jpg
   → Debería mostrar la imagen (si existe)
```

---

## 🚨 Problemas Comunes y Soluciones

| Problema | Causa | Solución |
|----------|-------|----------|
| **403 Forbidden** | Permisos incorrectos | `chmod 755 storage/app/public` |
| **404 Not Found** | Enlace simbólico roto | `php artisan storage:link` |
| **Las URLs no funcionan** | `config/filesystems.php` incorrecto | Ver paso 3️⃣ del checklist |
| **Apache no sirve archivos** | `mod_rewrite` deshabilitado | `a2enmod rewrite && systemctl restart apache2` |
| **Nginx sirve 404** | Configuración de location | Ver configuración Nginx en paso 4️⃣ |
| **Permisos de propietario** | Usuario incorrecto | `sudo chown -R www-data:www-data storage/` |

---

## 📞 Comandos de Referencia Rápida

### Linux/Mac
```bash
# Ver estado actual
php artisan storage:link --dry-run  # Solo verificar

# Crear enlace
php artisan storage:link

# Permisos
chmod -R 755 storage/app/public
sudo chown -R www-data:www-data storage/

# Limpiar caché
php artisan cache:clear

# Verificar
ls -la public/storage
```

### Windows (PowerShell)
```powershell
# Crear enlace
php artisan storage:link

# Ver permisos
icacls "storage\app\public"

# Limpiar caché
php artisan cache:clear
```

---

**Última actualización:** 25/01/2026
