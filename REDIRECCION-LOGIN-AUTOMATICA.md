# 🔐 Redirección Automática al Login - Implementación Completada

## ✅ Objetivo Alcanzado

Cuando un usuario intenta acceder a una URL protegida **sin estar autenticado**, es redirigido automáticamente al login en lugar de mostrar un error.

---

## 📋 Cambios Realizados

### 1. **Nuevo Middleware: RedirectToLoginIfUnauthenticated**
**Ubicación:** `app/Http/Middleware/RedirectToLoginIfUnauthenticated.php`

**Función:** Verifica si el usuario está autenticado. Si NO lo está:
- Para peticiones normales → Redirige a `/login`
- Para peticiones AJAX → Devuelve JSON con error 401

**Características:**
- ✅ Redirige a login automáticamente
- ✅ Guarda la URL original (intended) para redirigir después del login
- ✅ Muestra mensaje amigable
- ✅ Soporta peticiones AJAX/API

### 2. **Registro del Middleware**
**Archivo:** `bootstrap/app.php` (línea 20)

```php
'redirect-to-login' => \App\Http\Middleware\RedirectToLoginIfUnauthenticated::class,
```

### 3. **Mejora del Exception Handler**
**Archivo:** `app/Exceptions/Handler.php` (líneas 50-58)

Mejorada la lógica para redirigir a login cuando:
- La sesión ha expirado (AuthenticationException)
- El token CSRF es inválido (TokenMismatchException)
- El usuario no está autenticado (AccessDeniedHttpException)

---

## 🚀 Cómo Usar

### Opción 1: Usar el Middleware en Rutas Específicas

```php
// En routes/web.php
Route::middleware(['auth', 'redirect-to-login'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pedidos', [PedidosController::class, 'index'])->name('pedidos.index');
});
```

### Opción 2: Usar el Middleware `auth` de Laravel (Recomendado)

Laravel ya tiene un middleware `auth` que redirige automáticamente a login. Solo asegúrate de que tus rutas lo usen:

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
```

### Opción 3: Aplicar Globalmente (No Recomendado)

Si quieres que TODAS las rutas redirigidas a login, puedes agregar el middleware globalmente en `bootstrap/app.php`:

```php
$middleware->web([
    \App\Http\Middleware\RedirectToLoginIfUnauthenticated::class,
]);
```

---

## 📊 Flujo de Funcionamiento

### Escenario 1: Usuario NO Autenticado Accede a Ruta Protegida

```
1. Usuario intenta: GET /dashboard
2. Middleware verifica: auth()->check() → FALSE
3. Middleware redirige: redirect()->route('login')
4. Usuario ve: Página de login con mensaje "No tienes acceso..."
```

### Escenario 2: Usuario Autenticado Accede a Ruta Protegida

```
1. Usuario intenta: GET /dashboard
2. Middleware verifica: auth()->check() → TRUE
3. Middleware permite: $next($request)
4. Usuario ve: Dashboard normalmente
```

### Escenario 3: Sesión Expirada

```
1. Usuario intenta: GET /dashboard (sesión expirada)
2. Laravel lanza: AuthenticationException
3. Handler redirige: redirect()->route('login')
4. Usuario ve: Página de login con mensaje "Tu sesión ha expirado..."
```

### Escenario 4: Petición AJAX sin Autenticación

```
1. JavaScript intenta: fetch('/api/datos')
2. Middleware verifica: auth()->check() → FALSE
3. Middleware responde: JSON { error: true, message: "...", redirect: "/login" }
4. JavaScript maneja: Redirige a login o muestra error
```

---

## 🔍 Verificación

### Prueba 1: Acceso sin Autenticación
1. Abre una pestaña privada/incógnito
2. Intenta acceder a: `http://localhost:8000/dashboard`
3. **Resultado esperado:** Redirige a `/login`

### Prueba 2: Acceso con Autenticación
1. Inicia sesión normalmente
2. Accede a: `http://localhost:8000/dashboard`
3. **Resultado esperado:** Muestra el dashboard

### Prueba 3: Sesión Expirada
1. Inicia sesión
2. Espera a que la sesión expire (o elimina la cookie)
3. Intenta acceder a una ruta protegida
4. **Resultado esperado:** Redirige a `/login` con mensaje

---

## 📝 Rutas Afectadas

Todas las rutas que usen el middleware `auth` serán redirigidas automáticamente a login si el usuario no está autenticado:

```php
// Ejemplos de rutas protegidas en routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', ...);
    Route::get('/registros', ...);
    Route::get('/bodega', ...);
    Route::get('/profile', ...);
    // ... todas las demás rutas protegidas
});
```

---

## 🎯 Beneficios

✅ **Experiencia de Usuario Mejorada**
- No hay errores 403 o 404 confusos
- Redirige directamente a login
- Mensaje claro y amigable

✅ **Seguridad**
- Protege rutas sensibles
- Valida autenticación en cada petición
- Soporta sesiones expiradas

✅ **Flexibilidad**
- Funciona con rutas normales y AJAX
- Guarda URL original para redirigir después del login
- Personalizable por ruta

✅ **Mantenibilidad**
- Código centralizado en middleware
- Fácil de modificar o extender
- Sigue patrones de Laravel

---

## ⚙️ Configuración Avanzada

### Personalizar Mensaje de Error

En `app/Http/Middleware/RedirectToLoginIfUnauthenticated.php`:

```php
return redirect()->route('login')
    ->with('error', 'Tu mensaje personalizado aquí')
    ->with('intended', $request->url());
```

### Excluir Rutas Específicas

```php
public function handle(Request $request, Closure $next): Response
{
    // Excluir rutas públicas
    $publicRoutes = ['/api/public', '/docs'];
    
    if (in_array($request->path(), $publicRoutes)) {
        return $next($request);
    }
    
    // Resto del código...
}
```

### Redirigir a Página Diferente

```php
// En lugar de login, redirigir a welcome
return redirect()->route('welcome')
    ->with('error', 'Debes iniciar sesión primero');
```

---

## 🐛 Troubleshooting

### Problema: No redirige a login

**Causa:** El middleware no está aplicado a la ruta

**Solución:** Asegúrate de que la ruta tenga `middleware('auth')`:

```php
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')  // ← Agregar esto
    ->name('dashboard');
```

### Problema: Redirige pero pierde datos del formulario

**Causa:** El middleware redirige antes de procesar el formulario

**Solución:** Esto es normal. El usuario debe iniciar sesión primero, luego volver a enviar el formulario.

### Problema: AJAX no funciona

**Causa:** El middleware devuelve HTML en lugar de JSON

**Solución:** El middleware detecta automáticamente peticiones AJAX. Si no funciona, asegúrate de enviar el header `Accept: application/json`:

```javascript
fetch('/api/datos', {
    headers: {
        'Accept': 'application/json'
    }
});
```

---

## 📅 Fecha de Implementación

**Fecha:** 9 de Diciembre de 2025
**Estado:** ✅ COMPLETADO

---

## 📞 Soporte

Si tienes problemas o preguntas sobre la redirección a login:

1. Verifica que el middleware esté registrado en `bootstrap/app.php`
2. Verifica que las rutas tengan `middleware('auth')`
3. Revisa los logs en `storage/logs/laravel.log`
4. Prueba en una pestaña privada/incógnito

