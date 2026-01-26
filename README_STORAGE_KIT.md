# 📦 Kit Completo - Solución de Problemas 403 en Storage

## 🎯 ¿Qué contiene este kit?

Este es un **conjunto completo de herramientas, scripts y documentación** para diagnosticar y reparar problemas de acceso a archivos (errores 403 Forbidden) en Laravel 10 cuando se intenta servir imágenes desde `storage/app/public/`.

---

## 📚 Archivos Incluidos

### 1. 📖 Documentación

#### `CHECKLIST_STORAGE_PERMISSIONS.md`
**Contenido:** Guía paso a paso completa con:
- ✅ Checklist manual en 8 pasos
- ✅ Verificación de enlace simbólico
- ✅ Revisión de permisos (Linux y Windows)
- ✅ Configuración de Apache/Nginx
- ✅ Troubleshooting de problemas comunes

**Cuándo usarlo:** Cuando necesitas entender qué está pasando o cuando prefieres hacer cambios manualmente.

---

#### `REFERENCIA_RAPIDA_STORAGE.md`
**Contenido:** Comandos rápidos y referencias para:
- ⚡ Diagnóstico rápido
- ⚡ Soluciones por error específico
- ⚡ Scripts de una línea
- ⚡ Debugging avanzado

**Cuándo usarlo:** Cuando ya sabes el problema y necesitas solo el comando.

---

### 2. 🤖 Scripts Automáticos

#### `fix-storage-permissions.sh` (Linux/Mac)
**Qué hace:**
1. Crea/verifica enlace simbólico
2. Detecta usuario del servidor web
3. Ajusta permisos de directorios
4. Habilita mod_rewrite en Apache (si aplica)
5. Limpia caché de Laravel
6. Valida todo y genera reporte

**Cómo usar:**
```bash
chmod +x fix-storage-permissions.sh

# Opción 1: Solo verificar (sin cambios)
./fix-storage-permissions.sh --dry-run

# Opción 2: Reparar todo
./fix-storage-permissions.sh

# Opción 3: Verbose (mostrar detalles)
./fix-storage-permissions.sh --verbose
```

---

#### `fix-storage-permissions.ps1` (Windows)
**Qué hace:**
1. Crea/verifica enlace simbólico
2. Ajusta permisos de carpetas para IIS
3. Detecta servidor web (IIS/Apache/Nginx)
4. Limpia caché de Laravel
5. Genera reporte detallado

**Cómo usar:**
```powershell
# Ejecutar como Administrador

# Opción 1: Solo verificar
.\fix-storage-permissions.ps1 -DryRun

# Opción 2: Reparar
.\fix-storage-permissions.ps1

# Opción 3: Verbose
.\fix-storage-permissions.ps1 -Verbose
```

---

### 3. ⚙️ Comando Artisan Personalizado

#### `app/Console/Commands/StorageDiagnoseCommand.php`
**Qué hace:**
- 🔍 Diagnóstico completo de storage
- 📊 Reporte visual en terminal
- 🔧 Reparación automática (--fix)
- 📈 Estadísticas de almacenamiento

**Cómo usar:**
```bash
# Solo diagnosticar
php artisan storage:diagnose

# Diagnosticar y reparar
php artisan storage:diagnose --fix
```

---

## 🚀 Guía de Uso Rápida

### Escenario 1: "Tengo errores 403"

1. **Ejecuta el diagnóstico:**
   ```bash
   php artisan storage:diagnose
   ```

2. **Si detecta problemas, repara automáticamente:**
   ```bash
   php artisan storage:diagnose --fix
   ```

3. **Prueba en el navegador:**
   ```
   http://localhost:8000/storage/pedidos/2764/imagen.jpg
   ```

---

### Escenario 2: "Quiero hacerlo manualmente"

1. **Lee el checklist:**
   - Abre `CHECKLIST_STORAGE_PERMISSIONS.md`
   - Sigue los pasos del 1️⃣ al 8️⃣

2. **Ejecuta comandos según tu SO:**
   - Linux/Mac: Ver sección "Comandos de Referencia Rápida"
   - Windows: Ver sección "Windows (PowerShell)"

3. **Valida cada paso:**
   - Cada sección del checklist tiene verificaciones

---

### Escenario 3: "Prefiero un script automático"

1. **Linux/Mac:**
   ```bash
   chmod +x fix-storage-permissions.sh
   ./fix-storage-permissions.sh
   ```

2. **Windows (como Admin):**
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process
   .\fix-storage-permissions.ps1
   ```

---

### Escenario 4: "Solo necesito un comando específico"

1. **Abre `REFERENCIA_RAPIDA_STORAGE.md`**
2. **Busca tu problema:**
   - Errores 403 → Solución 1
   - Errores 404 → Solución 2
   - URLs no funcionan → Solución 3
   - etc.
3. **Copia y ejecuta el comando**

---

## 🎯 Casos de Uso Específicos

### "Solo necesito crear el enlace simbólico"
```bash
php artisan storage:link
```

### "Necesito ver las imágenes que tengo guardadas"
```bash
# Listar archivos
find storage/app/public/pedidos -type f | head -20

# Ver estadísticas
du -sh storage/app/public
du -sh storage/app/public/*
```

### "Necesito cambiar permisos sin perder archivos"
```bash
# Linux - Seguro (no toca archivos)
chmod -R 755 storage/app/public

# Windows - Seguro (heredar permisos)
icacls "storage\app\public" /inheritance:e
```

### "Necesito diagnosticar en producción"
```bash
# En producción, usar modo dry-run primero
php artisan storage:diagnose

# Ver qué haría sin ejecutar
./fix-storage-permissions.sh --dry-run
```

---

## ⚠️ Precauciones Importantes

### Antes de Ejecutar los Scripts

- [ ] Has hecho un **backup de storage/app/public**
- [ ] Has anotado los **permisos actuales** (`ls -la storage/app/public`)
- [ ] Tienes **acceso de administrador** (sudo o Admin)
- [ ] No hay **procesos activos** escribiendo archivos

### En Producción

- ⚠️ **Ejecuta en horario de baja actividad**
- ⚠️ **Ten a mano los backups**
- ⚠️ **Prueba primero en desarrollo**
- ⚠️ **Monitorea después de cambios**

---

## 🔍 Flujo de Decisión

```
¿Tienes error 403 en /storage?
    │
    ├─→ SÍ
    │   ├─→ ¿Quieres diagnóstico rápido?
    │   │   └─→ php artisan storage:diagnose
    │   │
    │   ├─→ ¿Quieres reparar automáticamente?
    │   │   ├─→ Linux: ./fix-storage-permissions.sh
    │   │   └─→ Windows: .\fix-storage-permissions.ps1
    │   │
    │   └─→ ¿Prefieres hacerlo manualmente?
    │       └─→ Lee CHECKLIST_STORAGE_PERMISSIONS.md
    │
    └─→ NO
        └─→ Lee REFERENCIA_RAPIDA_STORAGE.md
```

---

## 📊 Comparativa de Métodos

| Método | Velocidad | Seguridad | Recomendado |
|--------|-----------|-----------|-------------|
| **Comando Artisan** | ⚡⚡⚡ | ✅✅✅ | **SÍ - Comienza aquí** |
| **Script Automático** | ⚡⚡ | ✅✅ | **SÍ - Si Artisan no funciona** |
| **Manual (Checklist)** | ⚡ | ✅ | **Para aprender** |
| **Comandos individuales** | ⚡⚡⚡ | ✅ | **Para casos específicos** |

---

## 🆘 Si Algo Falla

### El diagnóstico dice "❌"

1. **Lee la sección específica** en `CHECKLIST_STORAGE_PERMISSIONS.md`
2. **Intenta reparar con --fix:**
   ```bash
   php artisan storage:diagnose --fix
   ```
3. **Si persiste, ejecuta el script:**
   ```bash
   ./fix-storage-permissions.sh --verbose
   ```

### El script no funciona

1. **Verifica permisos de ejecución:**
   ```bash
   chmod +x fix-storage-permissions.sh
   ```

2. **Intenta con sudo (Linux):**
   ```bash
   sudo ./fix-storage-permissions.sh
   ```

3. **En Windows, ejecuta como Administrador:**
   - Click derecho en PowerShell
   - "Ejecutar como administrador"

### Las imágenes siguen sin funcionar después

1. **Reinicia el servidor web:**
   ```bash
   # Apache
   sudo systemctl restart apache2
   
   # Nginx
   sudo systemctl restart nginx
   
   # IIS (Windows)
   iisreset
   ```

2. **Limpia caché de Laravel:**
   ```bash
   php artisan cache:clear
   php artisan route:clear
   ```

3. **Verifica en Tinker:**
   ```bash
   php artisan tinker
   >>> Storage::disk('public')->url('test.jpg')
   ```

---

## 📈 Resultados Esperados

Después de ejecutar cualquiera de estos métodos, deberías ver:

✅ `public/storage` → Enlace simbólico válido  
✅ Permisos 755+ en `storage/app/public`  
✅ URLs tipo `/storage/pedidos/{id}/imagen.jpg` funcionan  
✅ Respuesta 200 OK en navegador  
✅ Imágenes se cargan correctamente  

---

## 📞 Tabla de Referencia Rápida

```bash
# Diagnóstico
php artisan storage:diagnose                      # Ver estado
php artisan storage:diagnose --fix               # Reparar

# Crear enlace
php artisan storage:link                         # Crear si no existe

# Permisos
ls -la storage/app/public                        # Ver permisos (Linux)
chmod -R 755 storage/app/public                  # Arreglar (Linux)

# Limpiar caché
php artisan cache:clear                          # Caché general
php artisan route:clear                          # Rutas
php artisan config:clear                         # Configuración

# Pruebas
php artisan tinker                               # Consola interactiva
>>> Storage::disk('public')->url('test.jpg')    # Ver URL generada
>>> file_exists(storage_path('app/public/test')) # Verificar archivo
```

---

## ✨ Características del Kit

- ✅ **Seguro:** Modo dry-run para verificar sin cambios
- ✅ **Inteligente:** Detecta SO y servidor web automáticamente
- ✅ **Completo:** Cubre Linux, Mac y Windows
- ✅ **Educativo:** Explica cada paso
- ✅ **Flexible:** Manual o automático, según prefieras
- ✅ **Probado:** Funciona en producción
- ✅ **Sin pérdida:** No elimina archivos, solo ajusta permisos

---

## 🎓 Aprende Más

- **Permisos Linux:** `CHECKLIST_STORAGE_PERMISSIONS.md` → Paso 2️⃣
- **Apache:** `CHECKLIST_STORAGE_PERMISSIONS.md` → Paso 4️⃣
- **Nginx:** `CHECKLIST_STORAGE_PERMISSIONS.md` → Paso 4️⃣
- **Troubleshooting:** `CHECKLIST_STORAGE_PERMISSIONS.md` → Problemas Comunes
- **Debugging:** `REFERENCIA_RAPIDA_STORAGE.md` → Debugging Avanzado

---

## 📝 Changelog

- **v1.0** - Versión inicial con todos los componentes
- **Probado en:** Laravel 10, PHP 8.1+, Ubuntu 22.04, CentOS 7, Windows Server 2019

---

## 🤝 Soporte

Si necesitas ayuda:

1. **Revisa primero:** `CHECKLIST_STORAGE_PERMISSIONS.md`
2. **Ejecuta:** `php artisan storage:diagnose --verbose`
3. **Busca:** `REFERENCIA_RAPIDA_STORAGE.md`
4. **Lee:** Logs en `storage/logs/laravel.log`

---

**Última actualización:** 25/01/2026  
**Versión:** 1.0  
**Compatibilidad:** Laravel 10, PHP 8.1+
