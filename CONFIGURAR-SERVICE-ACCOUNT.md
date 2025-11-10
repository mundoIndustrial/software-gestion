# Configuración de Service Account para Google Drive

## ✅ Ya Tienes Todo Configurado

Tu archivo `mundoindustrial-backups-d98b14a4bd34.json` ya está en la carpeta correcta y el código ya está actualizado para usarlo.

## 🔑 Paso IMPORTANTE: Dar Permisos a la Service Account

La Service Account necesita tener acceso a la carpeta de Google Drive donde se guardarán los backups.

### Email de la Service Account:
```
backup-service@mundoindustrial-backups.iam.gserviceaccount.com
```

### Pasos para Dar Permisos:

1. **Abre Google Drive** en tu navegador: https://drive.google.com

2. **Busca la carpeta de backups**:
   - ID de la carpeta: `106fZ_fbQ45BA-EGy632i5KAx3qxEHsZ6`
   - Puedes buscarla o ir directamente: https://drive.google.com/drive/folders/106fZ_fbQ45BA-EGy632i5KAx3qxEHsZ6

3. **Haz clic derecho en la carpeta** → **Compartir** (o "Share")

4. **Agregar la Service Account**:
   - En el campo "Agregar personas y grupos", pega:
     ```
     backup-service@mundoindustrial-backups.iam.gserviceaccount.com
     ```
   - Selecciona el rol: **Editor** (o "Editor" / "Can edit")
   - **Desactiva** la opción "Notificar a las personas" (no es necesario)
   - Haz clic en **Compartir** o **Enviar**

5. **¡Listo!** La Service Account ahora tiene acceso a la carpeta

## 🧪 Probar la Configuración

Una vez que hayas dado permisos, prueba el backup:

1. Ve a tu aplicación web
2. Ve a la sección de Configuración
3. Haz clic en **"Subir a Google Drive"**
4. Debería funcionar sin errores

## 🎉 Ventajas de Service Account

- ✅ **No expira nunca** - No necesitas renovar tokens manualmente
- ✅ **Más seguro** - Las credenciales están en un archivo, no en variables de entorno
- ✅ **Más simple** - No necesitas OAuth, refresh tokens, client secrets, etc.
- ✅ **Automático** - El sistema genera un nuevo token cada vez que lo necesita

## 🔍 Verificar Permisos

Para verificar que la Service Account tiene acceso:

1. Ve a Google Drive
2. Busca la carpeta de backups
3. Haz clic derecho → **Compartir**
4. Deberías ver en la lista:
   ```
   backup-service@mundoindustrial-backups.iam.gserviceaccount.com (Editor)
   ```

## ⚠️ Solución de Problemas

### Error: "The caller does not have permission"
- La Service Account no tiene acceso a la carpeta
- Sigue los pasos de arriba para compartir la carpeta

### Error: "File not found: mundoindustrial-backups-d98b14a4bd34.json"
- El archivo debe estar en: `resources/mundoindustrial-backups-d98b14a4bd34.json`
- Verifica que el archivo existe y tiene el nombre correcto

### Error: "Invalid JWT"
- El archivo JSON podría estar corrupto
- Verifica que el contenido del archivo sea válido

## 📝 Notas

- El archivo JSON contiene la clave privada de la Service Account
- **NO compartas este archivo** con nadie
- **NO lo subas a repositorios públicos**
- Ya está en `.gitignore` para protegerlo
