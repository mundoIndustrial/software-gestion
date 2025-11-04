# 🚀 Configuración Rápida de Firebase Storage

## ⚡ Pasos Rápidos

### 1️⃣ Descargar Credenciales

1. Ve a: https://console.firebase.google.com/project/mundo-software-images/settings/serviceaccounts/adminsdk
2. Clic en **"Generate new private key"**
3. Descarga el archivo JSON

### 2️⃣ Guardar Credenciales

Crea la carpeta y guarda el archivo:
```bash
mkdir storage\app\firebase
# Copia el archivo descargado a: storage\app\firebase\credentials.json
```

### 3️⃣ Configurar .env

Agrega estas líneas a tu archivo `.env`:
```env
FIREBASE_PROJECT_ID=mundo-software-images
FIREBASE_CREDENTIALS=storage/app/firebase/credentials.json
FIREBASE_STORAGE_BUCKET=mundo-software-images.firebasestorage.app
FIREBASE_DEFAULT_FOLDER=images
FIREBASE_MAX_FILE_SIZE=5242880
```

### 4️⃣ Configurar Reglas de Storage

1. Ve a: https://console.firebase.google.com/project/mundo-software-images/storage/rules
2. Pega estas reglas:

```javascript
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /{allPaths=**} {
      allow read: if true;
      allow write: if true;
    }
  }
}
```

3. Clic en **"Publish"**

### 5️⃣ Probar la Integración

Inicia el servidor:
```bash
php artisan serve
```

Visita: http://localhost:8000/images/test

## ✅ ¡Listo!

Ahora puedes:
- ✨ Subir imágenes arrastrándolas
- 📋 Copiar URLs públicas
- 🗑️ Eliminar imágenes
- 📊 Ver estadísticas

## 📚 Documentación Completa

Para más detalles, consulta: `FIREBASE_STORAGE_GUIA.md`

## 🔗 Enlaces Útiles

- **Consola Firebase:** https://console.firebase.google.com/project/mundo-software-images
- **Storage:** https://console.firebase.google.com/project/mundo-software-images/storage
- **Reglas:** https://console.firebase.google.com/project/mundo-software-images/storage/rules

## 🐛 Problemas Comunes

### ❌ Error: "Firebase credentials file not found"
**Solución:** Verifica que `storage/app/firebase/credentials.json` existe

### ❌ Error: "Permission denied"
**Solución:** Revisa las reglas de Storage en Firebase Console

### ❌ Las imágenes no se muestran
**Solución:** Asegúrate de que las reglas permiten lectura pública (`allow read: if true`)

## 📞 Información del Proyecto

- **Nombre:** mundo-software-images
- **ID:** mundo-software-images
- **Número:** 481222406251
- **Bucket:** mundo-software-images.firebasestorage.app
