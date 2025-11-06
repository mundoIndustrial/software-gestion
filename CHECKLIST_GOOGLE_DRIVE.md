# ✅ Checklist de Configuración Google Drive

## Paso 1: Archivo JSON ✓
- [x] Directorio creado: `storage/app/google/`
- [ ] Archivo creado: `storage/app/google/service-account-credentials.json`
- [ ] Contenido del JSON copiado correctamente

**Acción:** Crea el archivo y pega el JSON que te proporcioné arriba

---

## Paso 2: Compartir Carpeta de Google Drive
- [ ] Ir a: https://drive.google.com/drive/folders/106fZ_fbQ45BA-EGy632i5KAx3qxEHsZ6
- [ ] Hacer clic derecho → "Compartir"
- [ ] Agregar email: `backup-service@mundoindustrial-backups.iam.gserviceaccount.com`
- [ ] Permisos: **Editor**
- [ ] Clic en "Enviar"

---

## Paso 3: Configurar .env
- [ ] Abrir archivo `.env` (en la raíz del proyecto)
- [ ] Ir al final del archivo
- [ ] Agregar estas líneas:
  ```
  GOOGLE_DRIVE_SERVICE_ACCOUNT_FILE=storage/app/google/service-account-credentials.json
  GOOGLE_DRIVE_FOLDER_ID=106fZ_fbQ45BA-EGy632i5KAx3qxEHsZ6
  ```
- [ ] Guardar el archivo

---

## Paso 4: Probar
- [ ] Recargar la página de configuración (Ctrl + F5)
- [ ] Hacer clic en el botón verde "Google Drive"
- [ ] Esperar el spinner
- [ ] Ver mensaje de éxito
- [ ] Verificar en Google Drive que apareció el archivo

---

## 🔍 Verificación Rápida

### ¿El archivo JSON existe?
```bash
dir storage\app\google\service-account-credentials.json
```

### ¿Las variables están en .env?
```bash
findstr "GOOGLE_DRIVE" .env
```

Deberías ver:
```
GOOGLE_DRIVE_SERVICE_ACCOUNT_FILE=storage/app/google/service-account-credentials.json
GOOGLE_DRIVE_FOLDER_ID=106fZ_fbQ45BA-EGy632i5KAx3qxEHsZ6
```

---

## 🆘 Solución de Problemas

### Error: "Google Drive no está configurado"
- Verifica que las variables estén en el `.env`
- Recarga la configuración: `php artisan config:clear`

### Error: "Archivo de credenciales no encontrado"
- Verifica que el archivo JSON esté en: `storage/app/google/service-account-credentials.json`
- Verifica que el contenido sea válido JSON

### Error: "Permission denied" o "Folder not found"
- Verifica que compartiste la carpeta con el email correcto
- Verifica que el ID de la carpeta sea: `106fZ_fbQ45BA-EGy632i5KAx3qxEHsZ6`

---

## 📞 Siguiente Paso

Una vez completados todos los pasos, haz clic en el botón verde **"Google Drive"** en la página de configuración.

¡Deberías ver tu backup aparecer en Google Drive! 🎉
