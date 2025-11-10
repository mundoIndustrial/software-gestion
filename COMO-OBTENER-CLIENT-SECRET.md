# Cómo Obtener el CLIENT_SECRET de Google Drive

## Paso 1: Acceder a Google Cloud Console
1. Ve a: https://console.cloud.google.com
2. Inicia sesión con tu cuenta de Google

## Paso 2: Ir a Credenciales
1. En el menú lateral, haz clic en **"APIs & Services"** (APIs y Servicios)
2. Haz clic en **"Credentials"** (Credenciales)

## Paso 3: Encontrar tu OAuth 2.0 Client ID
1. En la lista de credenciales, busca el que tiene:
   - **Type**: OAuth 2.0 Client ID
   - **Client ID**: `407408718192.apps.googleusercontent.com`
   
2. Haz clic en el nombre del Client ID (generalmente se llama "Web client" o similar)

## Paso 4: Ver el Client Secret
1. En la ventana que se abre, verás:
   - **Client ID**: 407408718192.apps.googleusercontent.com
   - **Client secret**: GOCSPX-xxxxxxxxxxxxxxxxxxxxxxxx
   
2. **Copia el Client Secret** (el que empieza con GOCSPX-)

## Paso 5: Configurar en la Aplicación

### Opción A: Usando el comando artisan (RECOMENDADO)
```bash
php artisan google-drive:configure GOCSPX-tu_client_secret_aqui
```

### Opción B: Manualmente
1. Abre el archivo `.env` en la raíz del proyecto
2. Busca o agrega estas líneas:
```env
GOOGLE_DRIVE_CLIENT_ID=407408718192.apps.googleusercontent.com
GOOGLE_DRIVE_CLIENT_SECRET=GOCSPX-tu_client_secret_aqui
```
3. Guarda el archivo
4. Ejecuta: `php artisan config:clear`

## Verificar la Configuración
```bash
php verificar-google-drive.php
```

Deberías ver:
```
GOOGLE_DRIVE_CLIENT_ID: ✅ CONFIGURADO
GOOGLE_DRIVE_CLIENT_SECRET: ✅ CONFIGURADO
GOOGLE_DRIVE_REFRESH_TOKEN: ✅ CONFIGURADO
```

## ¿No tienes el Client Secret?
Si no puedes ver el Client Secret o lo perdiste:

1. En la página de credenciales, haz clic en el ícono de **"Reset secret"** (Restablecer secreto)
2. Confirma la acción
3. Se generará un nuevo Client Secret
4. **IMPORTANTE**: Copia el nuevo Client Secret inmediatamente, no podrás verlo de nuevo

## Solución de Problemas

### Error: "The OAuth client was not found"
- Verifica que estás en el proyecto correcto de Google Cloud
- El Client ID debe ser exactamente: `407408718192.apps.googleusercontent.com`

### Error: "Invalid client secret"
- Asegúrate de copiar el Client Secret completo (empieza con GOCSPX-)
- No debe tener espacios al inicio o al final
- Debe estar entre comillas si tiene caracteres especiales

### El comando no funciona
Si el comando artisan no funciona, edita manualmente el `.env`:
1. Abre `.env` con un editor de texto
2. Agrega al final del archivo:
```env
GOOGLE_DRIVE_CLIENT_ID=407408718192.apps.googleusercontent.com
GOOGLE_DRIVE_CLIENT_SECRET=GOCSPX-tu_client_secret_real
```
3. Guarda y cierra
4. Ejecuta: `php artisan config:clear`

## Contacto
Si tienes problemas, revisa los logs en `storage/logs/laravel.log`
