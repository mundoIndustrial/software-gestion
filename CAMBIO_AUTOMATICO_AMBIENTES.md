# 🔄 CAMBIO AUTOMÁTICO ENTRE DESARROLLO Y PRODUCCIÓN

## ✅ Archivos Creados

- `.env.development` - Configuración para desarrollo local
- `.env.production` - Configuración para producción online
- `switch-env.ps1` - Script para cambiar entre ambientes

---

## 🚀 Cómo Usar

### En DESARROLLO (localhost)

```powershell
.\switch-env.ps1 development
```

**Resultado:**
- ✅ `APP_ENV=local`
- ✅ `APP_URL=http://localhost:8000`
- ✅ `GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback`
- ✅ `APP_DEBUG=true`
- ✅ `LOG_LEVEL=debug`

---

### En PRODUCCIÓN (online)

```powershell
.\switch-env.ps1 production
```

**Resultado:**
- ✅ `APP_ENV=production`
- ✅ `APP_URL=https://sistemamundoindustrial.online`
- ✅ `GOOGLE_REDIRECT_URI=https://sistemamundoindustrial.online/auth/google/callback`
- ✅ `APP_DEBUG=false`
- ✅ `LOG_LEVEL=notice`

---

## 🔍 Diferencias entre Ambientes

| Configuración | Desarrollo | Producción |
|---|---|---|
| **APP_ENV** | local | production |
| **APP_DEBUG** | true | false |
| **APP_URL** | http://localhost:8000 | https://sistemamundoindustrial.online |
| **LOG_LEVEL** | debug | notice |
| **SESSION_ENCRYPT** | false | true |
| **GOOGLE_REDIRECT_URI** | http://localhost:8000/auth/google/callback | https://sistemamundoindustrial.online/auth/google/callback |
| **VITE_HMR** | Activo (localhost) | Desactivo |

---

## 📋 Diferencias en Google OAuth

### Desarrollo
```
Cliente ID:    150032677898-703pk3usnv99aaqqdjpsoojfarhakco4.apps.googleusercontent.com
Secret:        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
Redirect:      http://localhost:8000/auth/google/callback
```

### Producción
```
Cliente ID:    150032677898-703pk3usnv99aaqqdjpsoojfarhakco4.apps.googleusercontent.com
Secret:        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
Redirect:      https://sistemamundoindustrial.online/auth/google/callback
```

**✅ Same credentials, different redirect URLs** - Google Cloud ya lo soporta

---

## 🎯 Flujo Recomendado

### Antes de DEPLOYS a Producción

```powershell
# 1. Asegúrate que estés en desarrollo
.\switch-env.ps1 development

# 2. Testea todo localmente
php artisan serve

# 3. Cuando esté listo, cambia a producción
.\switch-env.ps1 production

# 4. Verifica el .env
cat .env | findstr GOOGLE_REDIRECT_URI

# 5. Deploy a servidor
# (git push, ssh, etc.)
```

### En Servidor Producción

```bash
# No necesitas el script si copias directamente .env.production
cp .env.production .env

# O si quieres el script:
./switch-env.ps1 production

# Limpiar caché
php artisan config:clear
php artisan cache:clear
```

---

## ⚙️ Flujo de Google OAuth (Automático)

```
Desarrollo:
  Usuario clica "Google Login"
  → APP_ENV=local
  → Google redirige a: http://localhost:8000/auth/google/callback
  → ✅ Funciona

Producción:
  Usuario clica "Google Login"
  → APP_ENV=production
  → Google redirige a: https://sistemamundoindustrial.online/auth/google/callback
  → ✅ Funciona
```

---

## 📝 Verificar Configuración Actual

Para verificar qué configuración está activa:

```powershell
# Ver el .env actual
cat .env | Select-String "APP_ENV|APP_URL|GOOGLE_REDIRECT"

# Resultado esperado en DESARROLLO:
# APP_ENV=local
# APP_URL=http://localhost:8000
# GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

---

## ⚠️ Importante: Actualizar Antes de Deploy

Antes de desplegar a producción, asegúrate de actualizar en `.env.production`:

```dotenv
# ✅ ACTUALIZAR ESTOS VALORES CON TUS CREDENCIALES REALES

# Base de datos
DB_HOST=tu-host-produccion
DB_USERNAME=tu-usuario
DB_PASSWORD=tu-contraseña

# Email SMTP
MAIL_HOST=smtp.tuproveedor.com
MAIL_USERNAME=tu-email
MAIL_PASSWORD=tu-contraseña

# Google Drive (si lo usas)
GOOGLE_DRIVE_REFRESH_TOKEN=tu-token
GOOGLE_DRIVE_FOLDER_ID=tu-folder-id
```

---

## ✨ Ventajas de Este Sistema

✅ **Cambio rápido**: 1 comando para cambiar todo
✅ **Seguro**: No mezclas configuraciones
✅ **Automatizado**: El script limpia caché
✅ **Versión controlada**: Ambos .env en git (sin .env principal)
✅ **Google OAuth funciona en ambos lados**: Automáticamente
✅ **Fácil de mantener**: Cambios en un solo archivo

---

**Uso**: `.\switch-env.ps1 development` o `.\switch-env.ps1 production`

¡Listo! 🎉
