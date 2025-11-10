# Obtener Nuevo Refresh Token de Google Drive

El refresh token actual ha expirado. Necesitas generar uno nuevo.

## 📋 Pasos:

### 1. Ve a Google OAuth 2.0 Playground
Abre en tu navegador: https://developers.google.com/oauthplayground/

### 2. Configurar el Playground

1. Haz clic en el **ícono de engranaje** ⚙️ (arriba a la derecha)
2. Marca la casilla: **"Use your own OAuth credentials"**
3. Ingresa:
   - **OAuth Client ID**: `377832184815-ulbdp631n4irovrer0it0gk8rfsvetfj.apps.googleusercontent.com`
   - **OAuth Client secret**: `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`
4. Cierra la configuración

### 3. Seleccionar Scopes

1. En el panel izquierdo, busca: **"Drive API v3"**
2. Expande la sección
3. Marca: **`https://www.googleapis.com/auth/drive.file`**

### 4. Autorizar

1. Haz clic en el botón azul: **"Authorize APIs"**
2. Selecciona tu cuenta de Google
3. Acepta los permisos (haz clic en "Permitir" o "Allow")
4. Te redirigirá de vuelta al Playground

### 5. Obtener el Refresh Token

1. Haz clic en el botón: **"Exchange authorization code for tokens"**
2. Verás una respuesta JSON con:
   ```json
   {
     "access_token": "ya29.a0...",
     "refresh_token": "1//0...",
     "expires_in": 3599,
     "token_type": "Bearer"
   }
   ```
3. **Copia el `refresh_token`** (el que empieza con `1//0...`)

### 6. Actualizar el .env

1. Abre el archivo `.env` en la raíz del proyecto
2. Busca la línea `GOOGLE_DRIVE_REFRESH_TOKEN=`
3. Reemplázala con el nuevo refresh token:
   ```
   GOOGLE_DRIVE_REFRESH_TOKEN=1//0tu_nuevo_refresh_token_aqui
   ```
4. Guarda el archivo

### 7. Limpiar Caché

Ejecuta en PowerShell:
```bash
php artisan config:clear
```

### 8. Probar

Ahora ve a tu aplicación y haz clic en **"Subir a Google Drive"**

¡Debería funcionar! 🎉

---

## ⚠️ Importante:

- El **refresh token NUNCA expira** a menos que:
  - Lo revoques manualmente
  - No lo uses por 6 meses
  - Cambies la contraseña de Google
  - Revoques el acceso desde tu cuenta de Google

- Una vez que tengas el nuevo refresh token, el sistema renovará automáticamente el access token cada hora sin que hagas nada.

---

## 🔍 Verificar que Funciona:

Después de actualizar el refresh token, puedes verificar que funciona ejecutando:

```bash
php renovar-token-google.php
```

Debería decir: "✅ TOKEN RENOVADO Y GUARDADO"
