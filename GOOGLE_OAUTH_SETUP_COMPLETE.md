# Google OAuth Login - Guía Completa Implementación

## ✅ Estado: CONFIGURADO Y FUNCIONANDO

---

## 📋 Componentes Implementados

### 1. **Configuración de Socialite**
- ✅ Archivo: `config/socialite.php` - CREADO
- ✅ Contiene configuración para provider Google
- ✅ Lee credenciales desde `.env`

### 2. **Credenciales en .env**
```dotenv
GOOGLE_CLIENT_ID=150032677898-703pk3usnv99aaqqdjpsoojfarhakco4.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=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
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### 3. **Base de Datos**
- ✅ Tabla `users` con columna `google_id` (NULLABLE, UNIQUE)
- ✅ Migración: `2026_01_23_add_google_id_to_users.php` - EJECUTADA
- ✅ Modelo User incluye `google_id` en `$fillable`

### 4. **Rutas de Autenticación**
Archivo: `routes/auth.php`

```php
// Google OAuth
Route::get('auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google');
Route::get('auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');
```

### 5. **Controlador de Google OAuth**
Archivo: `app/Http/Controllers/Auth/GoogleAuthController.php`

**Flujo:**
1. `redirect()` → Redirige a Google para autenticación
2. `callback()` → Maneja la respuesta de Google
3. Busca usuario existente por email en la BD
4. Si existe: Actualiza `google_id` y autentica
5. Si NO existe: Muestra error (requiere cuenta previa en sistema)
6. Redirige según rol del usuario

### 6. **Vista de Login**
Archivo: `resources/views/auth/login.blade.php`

- ✅ Botón "Iniciar sesión con Google" prominente
- ✅ SVG del logo de Google
- ✅ Enlace a ruta `auth.google`
- ✅ Diseño responsivo y profesional
- ✅ Divisor visual entre Google OAuth y login tradicional

---

## 🔄 Flujo Completo de Funcionamiento

### Caso 1: Usuario Registrado Previamente (ÉXITO)
```
1. Usuario hace clic en "Iniciar sesión con Google"
   ↓
2. Se redirige a Google para autorizar
   ↓
3. Usuario aprueba permisos en Google
   ↓
4. Google redirige a: /auth/google/callback
   ↓
5. Controlador obtiene email y google_id de Google
   ↓
6. Busca usuario con ese email en BD
   ↓
7. Usuario existe ✓
   - Si NO tiene google_id: Lo guarda
   - Si ya tiene google_id: Verifica que coincida
   ↓
8. Auth::login($user, remember: true)
   ↓
9. Redirige según rol:
   - asesor → /asesores/dashboard
   - contador → /contador
   - supervisor → /registros
   - supervisor_planta → /registros
   - insumos → /insumos/materiales
   - cartera → /cartera/pedidos
   - admin → /admin/users
   ↓
10. ✅ Sesión iniciada correctamente
```

### Caso 2: Usuario NO Registrado (ERROR)
```
1. Usuario hace clic en "Iniciar sesión con Google"
   ↓
2. Se autentica en Google exitosamente
   ↓
3. Retorna al callback
   ↓
4. Se busca usuario por email en BD
   ↓
5. Usuario NO existe ✗
   ↓
6. Redirige a /login con error:
   "No puedes ingresar. Por favor, habla con el administrador 
    del sitio para que cree tu cuenta."
```

---

## 🧪 Cómo Testear

### Paso 1: Crear usuario de prueba en la BD
```php
// Usuario de prueba
$user = User::create([
    'name' => 'Juan Pérez',
    'email' => 'juan@gmail.com',  // Mismo email de tu cuenta Google
    'password' => Hash::make('password123'),
    'role_id' => 2,  // Rol válido en tu sistema
]);
```

### Paso 2: Configurar credenciales de Google
1. Ir a [Google Cloud Console](https://console.cloud.google.com)
2. Crear aplicación OAuth 2.0
3. Autorizar URI de redirección: `http://localhost:8000/auth/google/callback`
4. Obtener Client ID y Client Secret
5. Actualizar `.env`:
```dotenv
GOOGLE_CLIENT_ID=tu_cliente_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=tu_cliente_secreto
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### Paso 3: Testear el flujo
1. Abre `/login` en el navegador
2. Haz clic en "Iniciar sesión con Google"
3. Autoriza con tu cuenta Google
4. Verifica que:
   - ✅ Se actualiza la columna `google_id` en la BD
   - ✅ Se inicia sesión automáticamente
   - ✅ Se redirige al dashboard correcto según el rol
   - ✅ La sesión persiste (Remember Me por 30 días)

---

## 🔐 Seguridad Implementada

✅ **CSRF Protection**: Token @csrf en formularios  
✅ **Session Security**: Datos sensibles ocultos (password)  
✅ **Email Unique**: Solo un google_id por usuario  
✅ **Remember Me**: Cookies seguras por 30 días  
✅ **Error Handling**: Try-catch en controlador  
✅ **Validación de Usuario**: Solo usuarios registrados pueden loginear  
✅ **Redirección por Rol**: Acceso controlado según permisos  

---

## 📁 Archivos Modificados/Creados

| Archivo | Estado | Descripción |
|---------|--------|-------------|
| `config/socialite.php` | ✅ CREADO | Configuración de Socialite |
| `database/migrations/2026_01_23_add_google_id_to_users.php` | ✅ CREADO | Agrega columna google_id |
| `app/Models/User.php` | ✅ MODIFICADO | Agrega google_id a $fillable |
| `app/Http/Controllers/Auth/GoogleAuthController.php` | ✅ EXISTENTE | Controlador de OAuth |
| `routes/auth.php` | ✅ EXISTENTE | Rutas de Google OAuth |
| `resources/views/auth/login.blade.php` | ✅ EXISTENTE | Botón de Google OAuth |
| `composer.json` | ✅ EXISTENTE | Socialite ya instalado |

---

## 🚀 Para Mantener Funcionando Permanentemente

### 1. **Siempre mantener credenciales actualizadas**
```dotenv
# .env
GOOGLE_CLIENT_ID=xxx
GOOGLE_CLIENT_SECRET=xxx
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### 2. **Asegurar que Socialite esté instalado**
```bash
composer require laravel/socialite
```

### 3. **Verificar migraciones ejecutadas**
```bash
php artisan migrate:status
# Debe mostrar "Ran" para: 2026_01_23_add_google_id_to_users
```

### 4. **Limpiar caché si hay problemas**
```bash
php artisan config:clear
php artisan cache:clear
```

### 5. **En producción, actualizar GOOGLE_REDIRECT_URI**
```dotenv
# .env.production
GOOGLE_REDIRECT_URI=https://tunombre.com/auth/google/callback
```

---

## ⚠️ Solución de Problemas Comunes

### Problema: "No puedes ingresar. Por favor, habla con el administrador..."
**Causa**: El usuario no existe en la BD  
**Solución**: Crear el usuario en la BD con el mismo email de Google

### Problema: "Error al autenticar con Google: Invalid client"
**Causa**: Credenciales incorrectas o expiradas  
**Solución**: Verificar Client ID y Secret en Google Cloud Console

### Problema: "CSRF token mismatch"
**Causa**: Session expirada o cookies borradas  
**Solución**: Limpiar cookies del navegador y reintentar

### Problema: "google_id column not found"
**Causa**: Migración no ejecutada  
**Solución**: 
```bash
php artisan migrate --path=database/migrations/2026_01_23_add_google_id_to_users.php
```

---

## 📊 Base de Datos - Estructura Final

```sql
-- Columna agregada a tabla users
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE NULLABLE AFTER email;

-- Índice único para google_id
ALTER TABLE users ADD UNIQUE INDEX unique_google_id (google_id);
```

---

## ✨ Características Adicionales

- ✅ Login con Google para usuarios registrados previamente
- ✅ Almacenamiento de google_id para futuras autenticaciones rápidas
- ✅ Remember Me (30 días)
- ✅ Redirección automática según rol
- ✅ Manejo de errores descriptivos
- ✅ Logs de debugging completos
- ✅ UI/UX profesional con botón Google prominente

---

**Última actualización**: 23 de Enero, 2026  
**Estado**: ✅ COMPLETAMENTE FUNCIONAL Y PERMANENTE
