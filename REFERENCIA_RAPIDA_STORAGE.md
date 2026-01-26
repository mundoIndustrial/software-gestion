#  Referencia Rápida - Comandos para Arreglar Storage 403

## 📋 TL;DR (Lo más importante)

```bash
# Linux/Mac - Ejecutar en este orden:
php artisan storage:link                          # 1. Crear enlace
php artisan storage:diagnose                      # 2. Diagnosticar
php artisan storage:diagnose --fix                # 3. Reparar (si hay problemas)
chmod -R 755 storage/app/public                   # 4. Permisos (si necesario)

# Windows - PowerShell (como Administrador):
php artisan storage:link
php artisan storage:diagnose
.\fix-storage-permissions.ps1                     # Script automático
```

---

## 🔍 Diagnóstico Rápido

### Comando Artisan ( NUEVO)
```bash
# Solo diagnosticar (sin cambios)
php artisan storage:diagnose

# Diagnosticar Y reparar
php artisan storage:diagnose --fix
```

### Verificar Enlace Simbólico

**Linux/Mac:**
```bash
# Ver si existe
ls -la public/storage

# Resultado esperado:
# lrwxrwxrwx 1 user group ... public/storage -> ../storage/app/public
```

**Windows (PowerShell):**
```powershell
Get-Item "public\storage" -Force | Select-Object FullName, LinkType
```

### Verificar Permisos

**Linux/Mac:**
```bash
# Ver permisos detallados
ls -la storage/app/public

# Resultado esperado:
# drwxrwxr-x  user group  storage/app/public

# Ver contenido recursivo
find storage/app/public -ls | head -20
```

**Windows (PowerShell):**
```powershell
# Ver permisos
Get-Acl "storage\app\public"

# Ver tamaño
du -r "storage\app\public"
```

### Verificar URLs Generadas

**Laravel Tinker:**
```bash
php artisan tinker

# Crear archivo de prueba
>>> Storage::disk('public')->put('test.txt', 'Test')

# Ver URL
>>> Storage::disk('public')->url('test.txt')
# Resultado: /storage/test.txt

# Verificar que existe
>>> file_exists(storage_path('app/public/test.txt'))
# Resultado: true

# Limpiar
>>> Storage::disk('public')->delete('test.txt')
```

---

## Soluciones Rápidas por Problema

### 🔴 Error 403 Forbidden

**Causa:** Permisos incorrectos

**Solución:**

```bash
# Linux/Mac
chmod -R 755 storage/app/public
chmod -R 644 storage/app/public/*   # Archivos: 644

# Windows (PowerShell como Admin)
icacls "storage\app\public" /inheritance:e /grant:r "*S-1-5-20:(OI)(CI)F"
```

### 🔴 Error 404 Not Found

**Causa:** Enlace simbólico no existe o está roto

**Solución:**

```bash
# Verificar
ls -la public/storage              # Linux/Mac
Get-Item "public\storage"          # Windows

# Reparar
php artisan storage:link           # Crear/renovar enlace
```

### 🔴 Las URLs no funcionan

**Causa:** `config/filesystems.php` incorrecto

**Solución:**

```php
// config/filesystems.php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL') . '/storage',  // ← IMPORTANTE
        'visibility' => 'public',
    ],
],
```

Luego ejecutar:
```bash
php artisan config:clear
php artisan route:clear
```

### 🔴 Apache no sirve archivos

**Causa:** `mod_rewrite` deshabilitado

**Solución (Linux/Apache):**

```bash
# Habilitar mod_rewrite
sudo a2enmod rewrite

# Reiniciar Apache
sudo systemctl restart apache2

# Verificar que está habilitado
apache2ctl -M | grep rewrite
```

### 🔴 Nginx retorna 404

**Causa:** Configuración incorrecta de `location`

**Solución (Nginx):**

```nginx
# En /etc/nginx/sites-available/tu-sitio.conf

location /storage {
    # Servir archivos directamente
    alias /ruta/absoluta/storage/app/public;
    expires 7d;
}

# O alternativa:
location ~ ^/storage/(.*)$ {
    try_files $uri =404;
}
```

Luego:
```bash
sudo systemctl restart nginx
```

---

## 🤖 Scripts Automáticos

### Linux/Mac - Script completo

```bash
#!/bin/bash
# 1. Crear enlace
php artisan storage:link

# 2. Detectar usuario web
WEB_USER=$(ps aux | grep apache | awk '{print $1}' | head -1)
[ -z "$WEB_USER" ] && WEB_USER="www-data"

# 3. Cambiar propiedad
sudo chown -R $WEB_USER:$WEB_USER storage/
sudo chown -R $WEB_USER:$WEB_USER bootstrap/cache/

# 4. Cambiar permisos
sudo chmod -R 755 storage/
sudo chmod -R 755 bootstrap/cache/

# 5. Habilitar mod_rewrite (si Apache)
sudo a2enmod rewrite && sudo systemctl restart apache2

# 6. Limpiar caché
php artisan cache:clear
php artisan route:clear
```

### Windows - Script completo

```powershell
# Ejecutar como Administrador

# 1. Crear enlace
php artisan storage:link

# 2. Cambiar permisos
icacls "storage\app\public" /inheritance:e /grant:r "*S-1-5-20:(OI)(CI)F"
icacls "bootstrap\cache" /inheritance:e /grant:r "*S-1-5-20:(OI)(CI)F"

# 3. Limpiar caché
php artisan cache:clear
php artisan route:clear
```

---

## 📞 Referencia de Permisos Linux

| Permiso | Significado | Uso |
|---------|-------------|-----|
| `755` | rwxr-xr-x | Carpetas (propietario: escribir, otros: leer/ejecutar) |
| `644` | rw-r--r-- | Archivos (propietario: escribir, otros: solo leer) |
| `775` | rwxrwxr-x | Carpetas compartidas (grupo puede escribir) |
| `666` | rw-rw-rw- | Archivos abiertos (todos pueden escribir) |
| `700` | rwx------ | Privadas (solo propietario) |

---

## 🔐 Usuarios Comunes por Servidor

| Servidor | Usuario | Grupo |
|----------|---------|-------|
| Apache 2 (Debian/Ubuntu) | `www-data` | `www-data` |
| Apache 2 (RedHat/CentOS) | `apache` | `apache` |
| Nginx | `www-data` | `www-data` |
| PHP-FPM | `www-data` | `www-data` |
| IIS (Windows) | `IUSR` | `IIS_IUSRS` |
| Docker | Variable | Variable |

---

## 🧪 Pruebas en el Navegador

```
1. http://localhost:8000/storage
   → Debería mostrar directorio de carpetas

2. http://localhost:8000/storage/pedidos/2764/imagen.jpg
   → Si 200 OK: Imagen se sirve
   → Si 403 Forbidden: Permisos incorrectos
   → Si 404 Not Found: Archivo no existe o enlace roto

3. Abre DevTools (F12) → Network
   → Verifica que el request es GET /storage/...
   → Verifica response headers (Content-Type, Cache-Control)
```

---

## 📊 Checklist de Validación

```
[ ] Enlace simbólico existe: ls -la public/storage
[ ] Apunta correctamente: readlink public/storage
[ ] storage/app/public existe: test -d storage/app/public
[ ] Permisos 755+: ls -l storage/app/public
[ ] Propietario web: ls -l storage/app/public | grep www-data
[ ] config/filesystems.php tiene APP_URL correcto
[ ] Se puede escribir: touch storage/app/public/test && rm storage/app/public/test
[ ] URLs funcionan en Tinker: Storage::disk('public')->url('test')
[ ] Servidor web sirve /storage: curl http://localhost/storage
[ ] Imágenes se cargan: http://localhost/storage/pedidos/2764/imagen.jpg
```

---

## 🆘 Debugging Avanzado

### Ver qué usuario ejecuta PHP

**Linux/Mac:**
```bash
php -r "echo posix_getpwuid(posix_geteuid())['name'];"
```

**Windows:**
```powershell
php -r "echo get_current_user();"
```

### Verificar módulos Apache

```bash
apache2ctl -M                      # Listar todos
apache2ctl -M | grep rewrite       # Solo mod_rewrite
```

### Registrar acceso a archivos

```bash
# Linux - Ver accesos a /storage
tail -f /var/log/apache2/access.log | grep storage

# Ver errores
tail -f /var/log/apache2/error.log
```

### Monitorear cambios de permisos

```bash
# Linux - Auditar cambios
auditctl -w storage/app/public -p wa -k storage_changes
ausearch -k storage_changes
```

---

## 📝 Archivos Generados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `CHECKLIST_STORAGE_PERMISSIONS.md` | 📄 Guía completa | Checklist detallado de verificación |
| `fix-storage-permissions.sh` |  Script Linux | Automatiza todas las correcciones |
| `fix-storage-permissions.ps1` |  Script Windows | Automatiza todas las correcciones |
| `StorageDiagnoseCommand.php` | ⚙️ Comando Artisan | `php artisan storage:diagnose [--fix]` |
| `REFERENCIA_RAPIDA.md` | 📚 Este archivo | Comandos más comunes |

---

##  Flujo Recomendado

1. **Diagnosticar:**
   ```bash
   php artisan storage:diagnose
   ```

2. **Si hay problemas, reparar:**
   ```bash
   php artisan storage:diagnose --fix
   # O ejecutar script: ./fix-storage-permissions.sh
   ```

3. **Verificar en navegador:**
   ```
   http://localhost:8000/storage/pedidos/2764/imagen.jpg
   ```

4. **Si persiste el problema:**
   ```bash
   php artisan tinker
   >>> Storage::disk('public')->url('test')
   >>> file_exists(storage_path('app/public/test'))
   ```

---

**Última actualización:** 25/01/2026
