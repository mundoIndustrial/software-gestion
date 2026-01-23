# SOLUCIÓN: Obtener Credenciales Correctas de Google OAuth

## ❌ Problema Actual
```
Error: invalid_client
POST https://www.googleapis.com/oauth2/v4/token resulted in 401 Unauthorized
```

**Causa**: Las credenciales en `.env` NO son válidas para OAuth o están expiradas.

---

## ✅ SOLUCIÓN: Generar Nuevas Credenciales OAuth

### Paso 1: Ir a Google Cloud Console
1. Abre: https://console.cloud.google.com
2. Inicia sesión con tu cuenta de Google
3. En la parte superior, haz clic en **"Seleccionar un proyecto"**

### Paso 2: Crear Nuevo Proyecto (si no tienes uno)
1. Haz clic en **"NUEVO PROYECTO"**
2. Dale un nombre: `Mundo Industrial` (o el que prefieras)
3. Haz clic en **"CREAR"**
4. Espera a que se cree (toma 1-2 minutos)
5. Cuando esté listo, selecciona el proyecto

### Paso 3: Habilitar la API de Google+
1. En el menú izquierdo, ve a **"APIs y servicios"** → **"Biblioteca"**
2. Busca: `Google+ API`
3. Haz clic en el resultado
4. Haz clic en **"HABILITAR"**
5. Espera a que se habilite

### Paso 4: Crear Credenciales OAuth
1. Ve a **"APIs y servicios"** → **"Credenciales"**
2. Haz clic en **"+ CREAR CREDENCIALES"** (botón superior)
3. Selecciona **"ID de cliente de OAuth"**
4. Si te pide configurar pantalla de consentimiento, haz clic en **"CONFIGURAR PANTALLA DE CONSENTIMIENTO"**

### Paso 5: Configurar Pantalla de Consentimiento
1. Selecciona **"Externo"** (para desarrollo)
2. Haz clic en **"CREAR"**
3. Completa el formulario:
   - **App name**: `Mundo Industrial`
   - **User support email**: Tu email
   - **Developer contact info**: Tu email
4. Haz clic en **"GUARDAR Y CONTINUAR"**
5. En la siguiente página, haz clic en **"GUARDAR Y CONTINUAR"** (sin cambiar nada)
6. Vuelve a **"GUARDAR Y CONTINUAR"**
7. Haz clic en **"VOLVER AL PANEL"**

### Paso 6: Crear ID de Cliente OAuth
1. Ve a **"APIs y servicios"** → **"Credenciales"** nuevamente
2. Haz clic en **"+ CREAR CREDENCIALES"**
3. Selecciona **"ID de cliente de OAuth"**
4. Selecciona el tipo: **"Aplicación web"**
5. Bajo **"Orígenes autorizados de JavaScript"**, agrega:
   - `http://localhost:8000`
   - `http://127.0.0.1:8000`
6. Bajo **"URI de redirección autorizados"**, agrega:
   - `http://localhost:8000/auth/google/callback`
7. Haz clic en **"CREAR"**

### Paso 7: Copiar las Credenciales
Se abrirá un modal con tu `Client ID` y `Client Secret`. 

**⚠️ COPIA AMBOS VALORES INMEDIATAMENTE**

Ejemplo de cómo se verá:
```
Client ID: xxxxxxxxx-yyyyyyyyyyyyyyyyyyyyyyyyyyyyy.apps.googleusercontent.com
Client Secret: GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx
```

---

## 🔧 Actualizar tu .env

Reemplaza estas líneas en tu archivo `.env`:

```dotenv
# Antes (INCORRECTO o expirado)
GOOGLE_CLIENT_ID=150032677898-703pk3usnv99aaqqdjpsoojfarhakco4.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=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
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

# Después (NUEVO - reemplaza con tus valores)
GOOGLE_CLIENT_ID=NUEVO_CLIENT_ID_AQUI.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=NUEVO_CLIENT_SECRET_AQUI
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

---

## 🔄 Después de Actualizar

### Paso 1: Limpiar caché de Laravel
```bash
cd c:\Users\Usuario\Documents\mundoindustrial
php artisan config:clear
php artisan cache:clear
```

### Paso 2: Testear nuevamente
1. Abre http://localhost:8000/login
2. Haz clic en "Iniciar sesión con Google"
3. Deberías ver la pantalla de Google sin errores

---

## ✅ Verificación Final

Cuando funcione, verás:
1. ✅ Redirige a la pantalla de Google
2. ✅ Te pide autorización
3. ✅ Redirige de vuelta a tu app
4. ✅ Se inicia sesión automáticamente
5. ✅ Se guarda el `google_id` en la BD

---

## ⚠️ Errores Comunes al Crear Credenciales

| Error | Solución |
|-------|----------|
| "Pantalla de consentimiento no configurada" | Completa Paso 5 (Configurar Pantalla) |
| "invalid_redirect_uri" | Asegúrate que la URL de redirección coincida exactamente en ambos lugares |
| "Invalid Client" después de crear | Espera 1-2 minutos para que se propaguen los cambios |
| "Error 404 en /auth/google/callback" | Verifica que la ruta esté correcta en `routes/auth.php` |

---

## 📌 Resumen Rápido

```
Google Cloud Console
    ↓
Crear Proyecto "Mundo Industrial"
    ↓
Habilitar Google+ API
    ↓
Crear Credenciales OAuth 2.0 (Aplicación Web)
    ↓
Configurar URI de redirección: http://localhost:8000/auth/google/callback
    ↓
Copiar Client ID y Client Secret
    ↓
Pegar en .env
    ↓
Ejecutar: php artisan config:clear
    ↓
Testear en /login
```

---

**¿Necesitas ayuda en algún paso específico? Dime qué código ves en Google Cloud Console y te ayudaré a configurarlo.**
