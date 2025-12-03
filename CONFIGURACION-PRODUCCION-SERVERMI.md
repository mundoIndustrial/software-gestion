# Configuración para Producción en servermi:8000

## Problemas Resueltos ✅

1. **Vite HMR configurado correctamente** para `servermi`
2. **Scripts duplicados removidos** (Alpine.js y SweetAlert2)
3. **CORS habilitado** en Vite

## Cambios Realizados

### 1. vite.config.js ✅
- Cambié `hmr.host` de `'localhost'` a `'servermi'`
- Ahora Vite se conectará correctamente desde `http://servermi:8000`

### 2. resources/views/layouts/base.blade.php ✅
- Removí scripts duplicados de SweetAlert2 y Alpine.js
- Estos ya se cargan correctamente desde `resources/js/app.js` vía Vite

### 3. resources/views/asesores/layout.blade.php ✅
- Removí scripts duplicados de SweetAlert2 y Alpine.js
- Estos ya se cargan correctamente desde Vite

## Pasos para Producción

### 1. Actualizar .env
```
APP_URL=http://servermi:8000
VITE_HMR_HOST=servermi
```

### 2. Compilar Assets
```bash
npm run build
```

### 3. Limpiar Caché
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### 4. Reiniciar Servidor
```bash
# Si usas Vite en desarrollo:
npm run dev

# Si compilaste para producción:
php artisan serve --host=0.0.0.0 --port=8000
```

## Verificación

Accede a `http://servermi:8000` y verifica:

✅ No hay errores de CORS
✅ No hay errores de "Identifier already declared"
✅ Los scripts se cargan correctamente
✅ Vite HMR conecta sin errores
✅ Alpine.js funciona (x-data, x-show, etc.)
✅ SweetAlert2 funciona (Swal.fire, etc.)

## Si Aún Hay Problemas

### Opción 1: Deshabilitar Vite en Producción
Si los problemas persisten, puedes compilar los assets una sola vez:

```bash
npm run build
```

Luego en `resources/views/layouts/base.blade.php`, reemplaza:
```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

Por:
```blade
<link rel="stylesheet" href="{{ asset('build/assets/app-[hash].css') }}">
<script src="{{ asset('build/assets/app-[hash].js') }}"></script>
```

### Opción 2: Usar Dominio Diferente
Si necesitas usar `localhost` en desarrollo y `servermi` en producción:

```bash
# Desarrollo
VITE_HMR_HOST=localhost npm run dev

# Producción
VITE_HMR_HOST=servermi npm run build
```

## Resumen de Cambios

| Archivo | Cambio |
|---------|--------|
| `vite.config.js` | hmr.host: 'localhost' → 'servermi' |
| `base.blade.php` | Removidos scripts duplicados |
| `asesores/layout.blade.php` | Removidos scripts duplicados |

## Próximos Pasos

1. Ejecuta los pasos de "Pasos para Producción"
2. Accede a `http://servermi:8000`
3. Abre DevTools (F12) y verifica que no hay errores
4. Si todo funciona, ¡listo! 🎉
