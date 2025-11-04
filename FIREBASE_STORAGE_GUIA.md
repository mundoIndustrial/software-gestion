# Guía de Integración Firebase Storage

## 📋 Información del Proyecto Firebase

- **Nombre del proyecto:** mundo-software-images
- **ID del proyecto:** mundo-software-images
- **Número del proyecto:** 481222406251
- **Storage Bucket:** mundo-software-images.firebasestorage.app

## 🚀 Configuración Inicial

### 1. Obtener Credenciales de Firebase

Para usar Firebase Storage necesitas descargar el archivo de credenciales:

1. Ve a la [Consola de Firebase](https://console.firebase.google.com/project/mundo-software-images/settings/serviceaccounts/adminsdk)
2. En "Service accounts" > "Firebase Admin SDK"
3. Haz clic en "Generate new private key"
4. Descarga el archivo JSON

### 2. Configurar el Archivo de Credenciales

Guarda el archivo JSON descargado en:
```
storage/app/firebase/credentials.json
```

Crea la carpeta si no existe:
```bash
mkdir -p storage/app/firebase
```

### 3. Configurar Variables de Entorno

Agrega estas variables a tu archivo `.env`:

```env
FIREBASE_PROJECT_ID=mundo-software-images
FIREBASE_CREDENTIALS=storage/app/firebase/credentials.json
FIREBASE_STORAGE_BUCKET=mundo-software-images.firebasestorage.app
FIREBASE_DEFAULT_FOLDER=images
FIREBASE_MAX_FILE_SIZE=5242880
```

### 4. Configurar Reglas de Storage en Firebase

Ve a la consola de Firebase > Storage > Rules y configura:

```javascript
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /{allPaths=**} {
      allow read: if true; // Permitir lectura pública
      allow write: if request.auth != null; // Solo usuarios autenticados pueden escribir
    }
  }
}
```

Para desarrollo, puedes usar (NO RECOMENDADO EN PRODUCCIÓN):
```javascript
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /{allPaths=**} {
      allow read, write: if true;
    }
  }
}
```

## 📡 API Endpoints

### 1. Subir una Imagen

**POST** `/images/upload`

**Parámetros:**
- `image` (file, required): Archivo de imagen
- `folder` (string, optional): Carpeta donde guardar
- `custom_name` (string, optional): Nombre personalizado

**Ejemplo con cURL:**
```bash
curl -X POST http://localhost:8000/images/upload \
  -H "Content-Type: multipart/form-data" \
  -F "image=@/ruta/a/imagen.jpg" \
  -F "folder=productos" \
  -F "custom_name=producto_1.jpg"
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "message": "Imagen subida exitosamente",
  "data": {
    "url": "https://firebasestorage.googleapis.com/v0/b/mundo-software-images.firebasestorage.app/o/productos%2Fproducto_1.jpg?alt=media",
    "path": "productos/producto_1.jpg",
    "name": "producto_1.jpg",
    "size": 245678,
    "mime_type": "image/jpeg"
  }
}
```

### 2. Subir Múltiples Imágenes

**POST** `/images/upload-multiple`

**Parámetros:**
- `images[]` (array of files, required): Array de imágenes
- `folder` (string, optional): Carpeta donde guardar

**Ejemplo con JavaScript:**
```javascript
const formData = new FormData();
formData.append('images[]', file1);
formData.append('images[]', file2);
formData.append('folder', 'productos');

fetch('/images/upload-multiple', {
    method: 'POST',
    body: formData,
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
.then(response => response.json())
.then(data => console.log(data));
```

### 3. Subir Imagen desde Base64

**POST** `/images/upload-base64`

**Parámetros:**
- `image` (string, required): Imagen en formato base64
- `folder` (string, optional): Carpeta donde guardar
- `custom_name` (string, optional): Nombre personalizado

**Ejemplo:**
```javascript
const base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgA...';

fetch('/images/upload-base64', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        image: base64Image,
        folder: 'productos',
        custom_name: 'producto_2.png'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### 4. Eliminar una Imagen

**DELETE** `/images/delete`

**Parámetros:**
- `path` (string, required): Ruta del archivo en Firebase

**Ejemplo:**
```javascript
fetch('/images/delete', {
    method: 'DELETE',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        path: 'productos/producto_1.jpg'
    })
})
.then(response => response.json())
.then(data => console.log(data));
```

### 5. Listar Imágenes de una Carpeta

**GET** `/images/list?folder=productos`

**Parámetros:**
- `folder` (string, optional): Carpeta a listar

**Respuesta:**
```json
{
  "success": true,
  "message": "Archivos obtenidos exitosamente",
  "data": [
    {
      "name": "productos/producto_1.jpg",
      "url": "https://firebasestorage.googleapis.com/...",
      "size": 245678,
      "updated": "2024-11-04T15:30:00Z"
    }
  ],
  "count": 1
}
```

### 6. Verificar si una Imagen Existe

**GET** `/images/exists?path=productos/producto_1.jpg`

**Parámetros:**
- `path` (string, required): Ruta del archivo

**Respuesta:**
```json
{
  "success": true,
  "exists": true,
  "url": "https://firebasestorage.googleapis.com/..."
}
```

### 7. Información del Bucket

**GET** `/images/bucket-info`

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "name": "mundo-software-images.firebasestorage.app",
    "location": "us-central1",
    "storageClass": "STANDARD",
    "timeCreated": "2024-11-04T12:00:00Z"
  }
}
```

## 💻 Uso en el Código

### Ejemplo en un Controlador

```php
use App\Services\FirebaseStorageService;

class ProductoController extends Controller
{
    protected $firebaseStorage;

    public function __construct(FirebaseStorageService $firebaseStorage)
    {
        $this->firebaseStorage = $firebaseStorage;
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'imagen' => 'required|image|max:5120'
        ]);

        // Subir imagen a Firebase
        $imageData = $this->firebaseStorage->uploadFile(
            $request->file('imagen'),
            'productos',
            'producto_' . time()
        );

        // Guardar en base de datos
        $producto = Producto::create([
            'nombre' => $request->nombre,
            'imagen_url' => $imageData['url'],
            'imagen_path' => $imageData['path']
        ]);

        return response()->json($producto);
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        
        // Eliminar imagen de Firebase
        $this->firebaseStorage->deleteFile($producto->imagen_path);
        
        // Eliminar de base de datos
        $producto->delete();

        return response()->json(['message' => 'Producto eliminado']);
    }
}
```

### Ejemplo en una Vista Blade

```html
<!-- Formulario de subida -->
<form id="uploadForm" enctype="multipart/form-data">
    @csrf
    <input type="file" name="image" id="imageInput" accept="image/*">
    <input type="text" name="folder" value="productos" placeholder="Carpeta">
    <button type="submit">Subir Imagen</button>
</form>

<div id="preview"></div>

<script>
document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('/images/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Mostrar imagen subida
            document.getElementById('preview').innerHTML = `
                <img src="${data.data.url}" alt="Imagen subida" style="max-width: 300px;">
                <p>URL: ${data.data.url}</p>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
    }
});
</script>
```

### Ejemplo con Drag & Drop

```html
<div id="dropZone" style="border: 2px dashed #ccc; padding: 20px; text-align: center;">
    Arrastra imágenes aquí o haz clic para seleccionar
    <input type="file" id="fileInput" multiple accept="image/*" style="display: none;">
</div>

<div id="imageGallery"></div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const gallery = document.getElementById('imageGallery');

dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#007bff';
});

dropZone.addEventListener('dragleave', () => {
    dropZone.style.borderColor = '#ccc';
});

dropZone.addEventListener('drop', async (e) => {
    e.preventDefault();
    dropZone.style.borderColor = '#ccc';
    
    const files = Array.from(e.dataTransfer.files);
    await uploadMultipleImages(files);
});

fileInput.addEventListener('change', async (e) => {
    const files = Array.from(e.target.files);
    await uploadMultipleImages(files);
});

async function uploadMultipleImages(files) {
    const formData = new FormData();
    files.forEach(file => formData.append('images[]', file));
    formData.append('folder', 'galeria');
    
    try {
        const response = await fetch('/images/upload-multiple', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            data.data.forEach(img => {
                gallery.innerHTML += `
                    <div style="display: inline-block; margin: 10px;">
                        <img src="${img.url}" style="width: 150px; height: 150px; object-fit: cover;">
                        <p>${img.name}</p>
                    </div>
                `;
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
</script>
```

## 🎨 Ejemplo Completo: Galería de Productos

```html
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .gallery-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Galería de Productos</h1>
    
    <input type="file" id="uploadInput" accept="image/*" multiple>
    <button onclick="loadGallery()">Recargar Galería</button>
    
    <div class="gallery" id="gallery"></div>
    
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        // Cargar galería al inicio
        loadGallery();
        
        // Subir imágenes
        document.getElementById('uploadInput').addEventListener('change', async (e) => {
            const formData = new FormData();
            Array.from(e.target.files).forEach(file => {
                formData.append('images[]', file);
            });
            formData.append('folder', 'productos');
            
            const response = await fetch('/images/upload-multiple', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            
            const data = await response.json();
            if (data.success) {
                loadGallery();
                e.target.value = '';
            }
        });
        
        // Cargar galería
        async function loadGallery() {
            const response = await fetch('/images/list?folder=productos');
            const data = await response.json();
            
            const gallery = document.getElementById('gallery');
            gallery.innerHTML = '';
            
            data.data.forEach(img => {
                gallery.innerHTML += `
                    <div class="gallery-item">
                        <img src="${img.url}" alt="${img.name}">
                        <button class="delete-btn" onclick="deleteImage('${img.name}')">
                            Eliminar
                        </button>
                    </div>
                `;
            });
        }
        
        // Eliminar imagen
        async function deleteImage(path) {
            if (!confirm('¿Eliminar esta imagen?')) return;
            
            const response = await fetch('/images/delete', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ path })
            });
            
            const data = await response.json();
            if (data.success) {
                loadGallery();
            }
        }
    </script>
</body>
</html>
```

## 🔒 Seguridad

### Validaciones Implementadas

1. **Extensiones permitidas:** jpg, jpeg, png, gif, webp, svg
2. **Tamaño máximo:** 5MB por defecto (configurable en `.env`)
3. **Autenticación:** Todas las rutas requieren autenticación
4. **CSRF Protection:** Incluido en todas las peticiones

### Recomendaciones

1. Siempre valida las imágenes en el servidor
2. Usa nombres únicos para evitar sobrescrituras
3. Implementa límites de cuota por usuario
4. Monitorea el uso del storage en Firebase Console

## 📊 Monitoreo

Puedes monitorear el uso de Firebase Storage en:
- [Firebase Console - Storage](https://console.firebase.google.com/project/mundo-software-images/storage)
- Ver estadísticas de uso
- Configurar alertas de cuota
- Revisar logs de acceso

## 🐛 Troubleshooting

### Error: "Firebase credentials file not found"
**Solución:** Verifica que el archivo `storage/app/firebase/credentials.json` existe y tiene los permisos correctos.

### Error: "ext-gd is missing"
**Solución:** Habilita la extensión GD en tu `php.ini`:
```ini
extension=gd
```

### Error: "ext-sodium is missing"
**Solución:** Habilita la extensión sodium en tu `php.ini`:
```ini
extension=sodium
```

### Las imágenes no se muestran
**Solución:** Verifica las reglas de Storage en Firebase Console y asegúrate de permitir lectura pública.

## 📝 Notas Adicionales

- Las URLs generadas son públicas y permanentes
- Los archivos se almacenan con metadata (fecha de subida, nombre original, etc.)
- El servicio maneja automáticamente la generación de nombres únicos
- Puedes organizar archivos en carpetas ilimitadas
- Firebase Storage escala automáticamente

## 🔗 Enlaces Útiles

- [Consola Firebase](https://console.firebase.google.com/project/mundo-software-images)
- [Documentación Firebase Storage](https://firebase.google.com/docs/storage)
- [Kreait Firebase PHP SDK](https://firebase-php.readthedocs.io/)
