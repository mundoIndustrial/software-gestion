# MÓDULO DE PERFIL PARA ASESORAS

## 📋 Descripción

Sistema completo de gestión de perfil para asesoras con diseño ERP profesional que permite:
- Subir y gestionar foto de perfil
- Editar información personal (nombre, email, teléfono, ciudad, departamento)
- Agregar biografía personalizada
- Cambiar contraseña de forma segura
- Ver información de la cuenta

## 🎨 Características

### ✨ Diseño ERP Profesional
- **Paleta de colores corporativa**: Azul (#0066CC) como color principal
- **Tarjetas con sombras y efectos hover**: Experiencia visual moderna
- **Grid responsive**: Se adapta a cualquier dispositivo
- **Modo claro/oscuro**: Compatible con el tema del sistema
- **Animaciones suaves**: Transiciones fluidas en todos los elementos

### 📸 Gestión de Avatar
- **Subida de imagen**: Drag & drop o selección de archivo
- **Preview instantáneo**: Vista previa antes de guardar
- **Validaciones**: Formato (JPG, PNG, GIF) y tamaño máximo (2MB)
- **Eliminación de avatar**: Volver al placeholder con iniciales
- **Actualización automática**: El avatar se actualiza en toda la aplicación

### 📝 Información Personal
- **Nombre completo**: Campo obligatorio con validación
- **Email**: Validación de formato y unicidad
- **Teléfono**: Campo opcional con formato
- **Ciudad y Departamento**: Ubicación geográfica
- **Biografía**: Hasta 500 caracteres con contador en tiempo real

### 🔒 Seguridad
- **Cambio de contraseña**: Mínimo 8 caracteres
- **Confirmación de contraseña**: Validación de coincidencia
- **Toggle de visibilidad**: Ver/ocultar contraseña
- **Requisitos claros**: Indicaciones de seguridad

### ℹ️ Información de Cuenta
- **Fecha de registro**: Cuándo se unió la asesora
- **Última actualización**: Fecha y hora del último cambio
- **Rol del usuario**: Badge visual del rol

## 📁 Archivos Creados

### 1. Migración
```
database/migrations/2024_11_10_214500_add_profile_fields_to_users_table.php
```
- Agrega campos: `avatar`, `telefono`, `bio`, `ciudad`, `departamento`

### 2. Controlador
```
app/Http/Controllers/AsesoresController.php
```
**Métodos agregados:**
- `profile()`: Muestra la vista de perfil
- `updateProfile()`: Actualiza información del perfil
- `deleteAvatar()`: Elimina la foto de perfil

### 3. Vista
```
resources/views/asesores/profile.blade.php
```
- Formulario de información personal
- Sección de avatar con preview
- Formulario de cambio de contraseña
- Información de cuenta

### 4. CSS
```
public/css/asesores/profile.css
```
- Estilos profesionales con diseño ERP
- Grid responsive
- Animaciones y transiciones
- Modo claro/oscuro

### 5. JavaScript
```
public/js/asesores/profile.js
```
- Manejo de subida de avatar
- Validaciones de formularios
- Preview de imágenes
- Contador de caracteres
- Toggle de contraseña

### 6. Rutas
```
routes/web.php
```
**Rutas agregadas:**
- `GET /asesores/profile` - Ver perfil
- `POST /asesores/profile/update` - Actualizar perfil
- `POST /asesores/profile/delete-avatar` - Eliminar avatar

## 🚀 Instalación

### 1. Ejecutar la migración
```bash
php artisan migrate
```

### 2. Crear enlace simbólico de storage (si no existe)
```bash
php artisan storage:link
```

### 3. Verificar permisos
Asegúrate de que la carpeta `storage/app/public/avatars` tenga permisos de escritura.

## 📖 Uso

### Acceder al Perfil
1. Hacer clic en el avatar o nombre en la esquina superior derecha
2. Seleccionar "Mi Perfil" del menú desplegable
3. También disponible en "Configuración"

### Cambiar Foto de Perfil
1. Hacer clic en "Subir Foto"
2. Seleccionar una imagen (JPG, PNG, GIF)
3. La imagen se sube y actualiza automáticamente
4. Para eliminar: hacer clic en "Eliminar"

### Editar Información Personal
1. Modificar los campos deseados
2. Hacer clic en "Guardar Cambios"
3. Se muestra mensaje de confirmación
4. Los cambios se reflejan inmediatamente

### Cambiar Contraseña
1. Ingresar nueva contraseña (mínimo 8 caracteres)
2. Confirmar la contraseña
3. Hacer clic en "Actualizar Contraseña"
4. La contraseña se actualiza de forma segura

## 🎯 Validaciones

### Avatar
- ✅ Formatos permitidos: JPG, JPEG, PNG, GIF
- ✅ Tamaño máximo: 2MB
- ✅ Preview antes de subir

### Información Personal
- ✅ Nombre: Mínimo 3 caracteres, obligatorio
- ✅ Email: Formato válido, único en la base de datos
- ✅ Teléfono: Formato numérico (opcional)
- ✅ Biografía: Máximo 500 caracteres

### Contraseña
- ✅ Mínimo 8 caracteres
- ✅ Confirmación debe coincidir
- ✅ Encriptación con bcrypt

## 🔧 Configuración

### Variables de Entorno
No se requieren variables adicionales. El sistema usa la configuración estándar de Laravel.

### Permisos de Storage
```bash
# Linux/Mac
chmod -R 775 storage/app/public/avatars

# Windows (ejecutar como administrador)
icacls storage\app\public\avatars /grant Users:F /T
```

## 🎨 Personalización

### Cambiar Colores
Editar variables CSS en `public/css/asesores/profile.css`:
```css
:root {
    --profile-primary: #0066CC;        /* Color principal */
    --profile-primary-dark: #0052A3;   /* Color principal oscuro */
    --profile-success: #28a745;        /* Color de éxito */
    --profile-danger: #dc3545;         /* Color de peligro */
}
```

### Modificar Validaciones
Editar en `app/Http/Controllers/AsesoresController.php`:
```php
$validated = $request->validate([
    'name' => 'required|string|max:255',
    'avatar' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
    // ... más validaciones
]);
```

## 📱 Responsive

El diseño es completamente responsive:
- **Desktop**: Grid de 2 columnas
- **Tablet**: Grid de 1 columna
- **Mobile**: Diseño vertical optimizado

## 🌙 Modo Oscuro

El módulo es compatible con el modo oscuro del sistema:
- Colores ajustados automáticamente
- Contraste optimizado
- Transiciones suaves entre temas

## 🔐 Seguridad

- ✅ Validación CSRF en todos los formularios
- ✅ Contraseñas encriptadas con bcrypt
- ✅ Validación de tipos de archivo
- ✅ Sanitización de inputs
- ✅ Protección contra XSS
- ✅ Middleware de autenticación

## 🐛 Solución de Problemas

### Avatar no se muestra
1. Verificar que existe el enlace simbólico: `php artisan storage:link`
2. Verificar permisos de la carpeta `storage/app/public/avatars`
3. Verificar que la ruta en la base de datos es correcta

### Error al subir imagen
1. Verificar tamaño máximo en `php.ini`: `upload_max_filesize` y `post_max_size`
2. Verificar permisos de escritura en storage
3. Verificar formato de imagen permitido

### Cambios no se guardan
1. Verificar token CSRF en el formulario
2. Revisar logs de Laravel: `storage/logs/laravel.log`
3. Verificar validaciones en el controlador

## 📊 Base de Datos

### Campos agregados a `users`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `avatar` | string(nullable) | Ruta del avatar en storage |
| `telefono` | string(nullable) | Número de teléfono |
| `bio` | text(nullable) | Biografía del usuario |
| `ciudad` | string(nullable) | Ciudad de residencia |
| `departamento` | string(nullable) | Departamento/Estado |

## 🎉 Características Adicionales

### Contador de Caracteres
- Actualización en tiempo real
- Cambio de color al acercarse al límite
- Indicador visual claro

### Toggle de Contraseña
- Ver/ocultar contraseña
- Icono dinámico
- Mejora la experiencia de usuario

### Mensajes de Feedback
- Mensajes de éxito en verde
- Mensajes de error en rojo
- Auto-ocultado después de 5 segundos
- Scroll automático al mensaje

## 📝 Notas

- El avatar se guarda en `storage/app/public/avatars`
- Las imágenes se optimizan automáticamente
- El sistema mantiene solo un avatar por usuario
- Al eliminar el avatar, se muestra un placeholder con las iniciales

## 🔄 Actualizaciones Futuras

Posibles mejoras a implementar:
- [ ] Recorte de imagen antes de subir
- [ ] Múltiples tamaños de avatar (thumbnail, medium, large)
- [ ] Historial de cambios de perfil
- [ ] Verificación de email
- [ ] Autenticación de dos factores
- [ ] Integración con redes sociales

## 📞 Soporte

Para problemas o preguntas sobre este módulo, revisar:
1. Este documento de documentación
2. Logs de Laravel en `storage/logs/laravel.log`
3. Consola del navegador para errores JavaScript

---

**Versión**: 1.0.0  
**Fecha**: 10 de Noviembre de 2024  
**Autor**: Sistema MundoIndustrial
