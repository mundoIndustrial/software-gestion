# 📸 Integración Firebase Storage - Módulo Balanceo

## 🎯 Resumen

Las imágenes de las prendas en el módulo de Balanceo ahora se almacenan en **Firebase Storage** en lugar de guardarse localmente. Las URLs de las imágenes se guardan en la base de datos.

## ✅ Cambios Implementados

### **1. Base de Datos**
- ✅ Campo `imagen` en tabla `prendas` actualizado de `string` a `text`
- ✅ Ahora puede almacenar URLs largas de Firebase Storage

### **2. Controlador (BalanceoController)**

#### **Crear Prenda**
```php
// Antes: Guardaba en public/images/prendas
$imagen->move(public_path('images/prendas'), $nombreImagen);

// Ahora: Sube a Firebase Storage
$imageData = $this->firebaseStorage->uploadFile(
    $request->file('imagen'),
    'prendas',
    'prenda_' . time()
);
$validated['imagen'] = $imageData['url']; // URL de Firebase
```

#### **Actualizar Prenda**
- Elimina la imagen anterior de Firebase antes de subir la nueva
- Maneja errores de subida correctamente

#### **Eliminar Prenda**
- Elimina automáticamente la imagen de Firebase Storage
- Extrae el path de la URL de Firebase para eliminarla

### **3. Vistas**

Todas las vistas actualizadas para soportar URLs de Firebase:

```blade
<!-- Antes -->
<img src="{{ asset($prenda->imagen) }}">

<!-- Ahora -->
<img src="{{ str_contains($prenda->imagen, 'http') ? $prenda->imagen : asset($prenda->imagen) }}">
```

Esto permite compatibilidad con:
- ✅ URLs de Firebase (nuevas imágenes)
- ✅ Rutas locales (imágenes antiguas)

### **4. Validaciones**

Límites actualizados:
- **Formatos:** JPG, JPEG, PNG, GIF, WEBP
- **Tamaño máximo:** 5MB (antes 2MB)

## 🚀 Cómo Funciona

### **Flujo de Subida de Imagen**

1. Usuario selecciona imagen en formulario
2. Imagen se valida (formato y tamaño)
3. Imagen se sube a Firebase Storage en carpeta `prendas/`
4. Firebase devuelve URL pública
5. URL se guarda en base de datos
6. Imagen se muestra desde Firebase

### **Estructura en Firebase**

```
mundo-software-images (bucket)
└── prendas/
    ├── prenda_1730745600.jpg
    ├── prenda_1730745650.png
    └── prenda_1730745700.webp
```

### **Formato de URL**

```
https://firebasestorage.googleapis.com/v0/b/mundo-software-images.firebasestorage.app/o/prendas%2Fprenda_1730745600.jpg?alt=media
```

## 📝 Uso en el Código

### **Crear Nueva Prenda con Imagen**

```php
// En el formulario
<form method="POST" action="{{ route('balanceo.prenda.store') }}" enctype="multipart/form-data">
    @csrf
    <input type="text" name="nombre" required>
    <input type="file" name="imagen" accept="image/*">
    <button type="submit">Crear</button>
</form>
```

El controlador automáticamente:
1. Sube la imagen a Firebase
2. Guarda la URL en la base de datos
3. Retorna a la vista con mensaje de éxito

### **Actualizar Imagen de Prenda**

```php
// En el formulario de edición
<form method="POST" action="{{ route('balanceo.prenda.update', $prenda->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <!-- Mostrar imagen actual -->
    @if($prenda->imagen)
        <img src="{{ str_contains($prenda->imagen, 'http') ? $prenda->imagen : asset($prenda->imagen) }}">
    @endif
    
    <!-- Subir nueva imagen -->
    <input type="file" name="imagen" accept="image/*">
    <button type="submit">Actualizar</button>
</form>
```

El controlador automáticamente:
1. Elimina la imagen anterior de Firebase
2. Sube la nueva imagen
3. Actualiza la URL en la base de datos

### **Eliminar Prenda**

```javascript
// JavaScript en la vista
async function deletePrenda(id) {
    if (!confirm('¿Eliminar esta prenda?')) return;
    
    const response = await fetch(`/balanceo/prenda/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    });
    
    if (response.ok) {
        window.location.reload();
    }
}
```

El controlador automáticamente:
1. Elimina la imagen de Firebase
2. Elimina balanceos asociados
3. Elimina la prenda de la base de datos

## 🔧 Configuración Necesaria

### **1. Variables de Entorno (.env)**

```env
FIREBASE_PROJECT_ID=mundo-software-images
FIREBASE_CREDENTIALS=storage/app/firebase/credentials.json
FIREBASE_STORAGE_BUCKET=mundo-software-images.firebasestorage.app
FIREBASE_DEFAULT_FOLDER=images
FIREBASE_MAX_FILE_SIZE=5242880
```

### **2. Archivo de Credenciales**

Ubicación: `storage/app/firebase/credentials.json`

Descarga desde: https://console.firebase.google.com/project/mundo-software-images/settings/serviceaccounts/adminsdk

### **3. Reglas de Firebase Storage**

```javascript
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /prendas/{allPaths=**} {
      allow read: if true;  // Lectura pública
      allow write: if true; // Escritura pública (ajustar en producción)
    }
  }
}
```

## 📊 Ventajas de Firebase Storage

### **Antes (Local)**
- ❌ Imágenes en `public/images/prendas/`
- ❌ Ocupan espacio en servidor
- ❌ Difícil de escalar
- ❌ Sin CDN
- ❌ Backups manuales

### **Ahora (Firebase)**
- ✅ Imágenes en la nube
- ✅ No ocupan espacio en servidor
- ✅ Escalabilidad automática
- ✅ CDN global incluido
- ✅ Backups automáticos
- ✅ URLs permanentes
- ✅ Optimización automática

## 🔒 Seguridad

### **Validaciones Implementadas**

1. **Tipo de archivo:** Solo imágenes (jpg, jpeg, png, gif, webp)
2. **Tamaño:** Máximo 5MB
3. **Autenticación:** Solo usuarios autenticados pueden subir
4. **Sanitización:** Nombres de archivo seguros

### **Manejo de Errores**

```php
try {
    $imageData = $this->firebaseStorage->uploadFile(...);
} catch (\Exception $e) {
    return redirect()->back()
        ->withInput()
        ->withErrors(['imagen' => 'Error al subir la imagen: ' . $e->getMessage()]);
}
```

## 🐛 Solución de Problemas

### **Error: "Firebase credentials file not found"**

**Causa:** Archivo de credenciales no existe

**Solución:**
```bash
# Crear directorio
mkdir storage\app\firebase

# Descargar credenciales desde Firebase Console
# Guardar como: storage\app\firebase\credentials.json
```

### **Error: "Permission denied"**

**Causa:** Reglas de Firebase muy restrictivas

**Solución:** Actualizar reglas en Firebase Console

### **Imagen no se muestra**

**Causa:** URL incorrecta o reglas de lectura

**Solución:**
1. Verificar que la URL es válida
2. Verificar reglas de Firebase permiten lectura pública
3. Revisar consola del navegador para errores CORS

### **Error al subir imagen grande**

**Causa:** Excede límite de 5MB

**Solución:**
1. Comprimir imagen antes de subir
2. Ajustar `FIREBASE_MAX_FILE_SIZE` en `.env`

## 📈 Monitoreo

### **Ver Imágenes en Firebase Console**

https://console.firebase.google.com/project/mundo-software-images/storage

### **Estadísticas de Uso**

- Total de archivos
- Espacio utilizado
- Transferencia de datos
- Solicitudes por día

### **Logs**

```php
// Ver logs de Firebase en Laravel
tail -f storage/logs/laravel.log | grep Firebase
```

## 🔄 Migración de Imágenes Existentes

Si tienes imágenes locales antiguas, puedes migrarlas:

```php
// Comando artisan personalizado (crear si es necesario)
php artisan migrate:images-to-firebase

// O manualmente:
$prendas = Prenda::whereNotNull('imagen')
    ->where('imagen', 'not like', 'http%')
    ->get();

foreach ($prendas as $prenda) {
    $localPath = public_path($prenda->imagen);
    
    if (file_exists($localPath)) {
        $file = new \Illuminate\Http\UploadedFile(
            $localPath,
            basename($localPath)
        );
        
        $imageData = $firebaseStorage->uploadFile($file, 'prendas');
        $prenda->update(['imagen' => $imageData['url']]);
        
        // Opcional: eliminar archivo local
        unlink($localPath);
    }
}
```

## 📚 Referencias

- **Documentación Firebase Storage:** https://firebase.google.com/docs/storage
- **SDK PHP Firebase:** https://firebase-php.readthedocs.io/
- **Consola Firebase:** https://console.firebase.google.com/project/mundo-software-images

## ✨ Próximas Mejoras

- [ ] Compresión automática de imágenes
- [ ] Generación de thumbnails
- [ ] Soporte para múltiples imágenes por prenda
- [ ] Galería de imágenes en vista de prenda
- [ ] Drag & drop para subir imágenes
- [ ] Editor de imágenes integrado
