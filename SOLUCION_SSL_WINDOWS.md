# 🔧 Solución Error SSL en Windows

## ❌ Error Actual

```
cURL error 60: SSL certificate problem: unable to get local issuer certificate
```

## ✅ Solución

### **Opción 1: Descargar Certificados CA (Recomendado)**

1. **Descargar el archivo de certificados:**
   - Ve a: https://curl.se/ca/cacert.pem
   - Descarga el archivo `cacert.pem`

2. **Guardar el archivo:**
   - Guárdalo en: `C:\php\cacert.pem`
   - O en cualquier ubicación que prefieras

3. **Configurar PHP:**
   - Abre tu archivo `php.ini` (ubicación: `C:\php\8.2\php-8.2.29-nts-Win32-vs16-x64\php.ini`)
   - Busca la línea: `;curl.cainfo =`
   - Descoméntala y configúrala así:
   ```ini
   curl.cainfo = "C:\php\cacert.pem"
   ```

4. **Reiniciar el servidor:**
   ```bash
   # Detener el servidor si está corriendo
   # Luego volver a iniciar
   php artisan serve
   ```

### **Opción 2: Deshabilitar Verificación SSL (Solo para Desarrollo)**

⚠️ **ADVERTENCIA:** Esta opción NO es segura para producción.

Edita `config/firebase.php` y agrega:

```php
'verify_ssl' => env('FIREBASE_VERIFY_SSL', true),
```

Luego en `.env`:
```env
FIREBASE_VERIFY_SSL=false
```

## 🧪 Verificar la Solución

Ejecuta el script de prueba:
```bash
php test-firebase.php
```

Deberías ver:
```
✅ Archivo de credenciales encontrado
✅ Conexión con Firebase establecida
📦 Información del Bucket:
   Nombre: mundo-software-images.firebasestorage.app
   ...
✅ ¡Firebase Storage está funcionando correctamente!
```

## 🎯 Siguiente Paso

Una vez solucionado el error SSL, puedes:

1. Iniciar el servidor:
   ```bash
   php artisan serve
   ```

2. Ir a: http://localhost:8000/balanceo/prenda/create

3. Crear una prenda con imagen

4. La imagen se subirá automáticamente a Firebase

## 📝 Notas

- El error SSL solo afecta las conexiones HTTPS desde PHP
- Es un problema común en instalaciones de PHP en Windows
- La solución con certificados CA es permanente y segura
- Deshabilitar SSL solo debe usarse en desarrollo local
