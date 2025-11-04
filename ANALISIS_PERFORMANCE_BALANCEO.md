# 📊 Análisis de Performance - Módulo Balanceo

## 🔍 Resumen Ejecutivo

Basado en el análisis de Lighthouse del módulo de balanceo (`http://127.0.0.1:8000/balanceo`), se identificaron **problemas críticos de rendimiento** que afectan significativamente la experiencia del usuario.

### Métricas Clave Actuales

| Métrica | Valor Actual | Objetivo | Estado |
|---------|--------------|----------|--------|
| **First Contentful Paint (FCP)** | 5.47s | < 1.8s | ❌ Crítico |
| **Largest Contentful Paint (LCP)** | 8.08s | < 2.5s | ❌ Crítico |
| **Total Blocking Time (TBT)** | 4.5ms | < 200ms | ✅ Bueno |
| **Cumulative Layout Shift (CLS)** | - | < 0.1 | - |

---

## 🚨 Problemas Críticos Identificados

### 1. **Recursos que Bloquean el Renderizado** (Ahorro: 600ms)

**Impacto:** Los recursos CSS/JS están bloqueando la primera renderización de la página.

**Archivos problemáticos:**
- Múltiples archivos CSS cargados de forma síncrona
- JavaScript cargado sin `defer` o `async`
- Vite/AlpineJS cargándose de forma bloqueante

**Solución:**
```html
<!-- ❌ ANTES -->
<link rel="stylesheet" href="{{ asset('css/tableros.css') }}">
<link rel="stylesheet" href="{{ asset('css/orders styles/modern-table.css') }}">

<!-- ✅ DESPUÉS -->
<link rel="preload" href="{{ asset('css/tableros.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="{{ asset('css/orders styles/modern-table.css') }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet" href="{{ asset('css/tableros.css') }}">
    <link rel="stylesheet" href="{{ asset('css/orders styles/modern-table.css') }}">
</noscript>
```

---

### 2. **JavaScript No Utilizado** (Ahorro: 637KB / 3.14s)

**Impacto:** Se están cargando 637KB de JavaScript que no se utiliza en la página.

**Problemas:**
- AlpineJS completo cuando solo se usan funcionalidades básicas
- Vite dev server en producción
- Scripts globales que no son necesarios en esta vista

**Soluciones:**

#### A. Lazy Loading de AlpineJS
```javascript
// Solo cargar AlpineJS cuando sea necesario
if (document.querySelector('[x-data]')) {
    import('alpinejs').then(module => {
        window.Alpine = module.default;
        Alpine.start();
    });
}
```

#### B. Code Splitting con Vite
```javascript
// vite.config.js
export default {
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'alpine': ['alpinejs'],
                    'balanceo': ['./resources/js/balanceo.js']
                }
            }
        }
    }
}
```

---

### 3. **Consultas N+1 en el Backend**

**Problema Actual:**
```php
// BalanceoController.php línea 18
$query = Prenda::with('balanceoActivo')->where('activo', true);
```

**Problema:** Cada prenda carga su balanceo activo, y luego cada balanceo carga sus operaciones al acceder a `$prenda->balanceoActivo->operaciones->count()`.

**Solución - Eager Loading Optimizado:**
```php
public function index(Request $request)
{
    $query = Prenda::with([
        'balanceoActivo' => function($query) {
            $query->select([
                'id', 
                'prenda_id', 
                'sam_total', 
                'meta_real', 
                'total_operarios',
                'activo'
            ])->withCount('operaciones');
        }
    ])
    ->where('activo', true)
    ->select(['id', 'nombre', 'referencia', 'tipo', 'descripcion', 'imagen', 'created_at']);
    
    // Búsqueda
    if ($request->has('search') && $request->search != '') {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('nombre', 'like', '%' . $search . '%')
              ->orWhere('referencia', 'like', '%' . $search . '%')
              ->orWhere('tipo', 'like', '%' . $search . '%');
        });
    }
    
    // Paginación con cache
    $prendas = Cache::remember(
        'balanceo.index.' . md5($request->fullUrl()), 
        now()->addMinutes(5),
        fn() => $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString()
    );
    
    return view('balanceo.index', compact('prendas'));
}
```

---

### 4. **Optimización de Imágenes**

**Problema:** Las imágenes de prendas no están optimizadas.

**Soluciones:**

#### A. Lazy Loading de Imágenes
```html
<!-- ❌ ANTES -->
<img src="{{ asset($prenda->imagen) }}" alt="{{ $prenda->nombre }}">

<!-- ✅ DESPUÉS -->
<img src="{{ asset($prenda->imagen) }}" 
     alt="{{ $prenda->nombre }}"
     loading="lazy"
     decoding="async"
     width="300" 
     height="180">
```

#### B. Usar WebP con Fallback
```php
// En el modelo Prenda, agregar accessor
public function getImagenOptimizadaAttribute()
{
    if (!$this->imagen) return null;
    
    $path = public_path($this->imagen);
    $webpPath = str_replace(['.jpg', '.png', '.jpeg'], '.webp', $path);
    
    if (file_exists($webpPath)) {
        return str_replace(['.jpg', '.png', '.jpeg'], '.webp', $this->imagen);
    }
    
    return $this->imagen;
}
```

```html
<picture>
    <source srcset="{{ asset($prenda->imagen_optimizada) }}" type="image/webp">
    <img src="{{ asset($prenda->imagen) }}" 
         alt="{{ $prenda->nombre }}"
         loading="lazy"
         width="300" 
         height="180">
</picture>
```

---

### 5. **Optimización de la Vista Blade**

**Problema:** La vista genera mucho HTML inline con estilos repetitivos.

**Solución - Extraer estilos a clases CSS:**

```css
/* public/css/balanceo.css */
.prenda-card {
    background: var(--color-bg-sidebar);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--color-border-hr);
    transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    box-shadow: 0 1px 3px var(--color-shadow);
}

.prenda-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 157, 88, 0.4);
    box-shadow: 0 8px 16px var(--color-shadow);
}

.prenda-card__image {
    height: 180px;
    background: white;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.prenda-card__badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #ff9d58;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.prenda-card__content {
    padding: 20px;
}

.prenda-card__title {
    margin: 0 0 8px 0;
    font-size: 20px;
    color: var(--color-text-primary);
    font-weight: 700;
}

.prenda-card__metrics {
    border-top: 1px solid var(--color-border-hr);
    padding-top: 16px;
    margin-top: 16px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    font-size: 13px;
}

.metric-label {
    margin: 0;
    color: var(--color-text-placeholder);
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 600;
}

.metric-value {
    margin: 4px 0 0 0;
    font-weight: 700;
    color: #ff9d58;
    font-size: 18px;
}
```

**Vista simplificada:**
```blade
<div class="prenda-card" onclick="window.location='{{ route('balanceo.show', $prenda->id) }}'">
    <div class="prenda-card__image">
        @if($prenda->imagen)
        <img src="{{ asset($prenda->imagen_optimizada ?? $prenda->imagen) }}" 
             alt="{{ $prenda->nombre }}"
             loading="lazy"
             decoding="async"
             width="300" 
             height="180">
        @else
        <span class="material-symbols-rounded icon-placeholder">checkroom</span>
        @endif
        <div class="prenda-card__badge">{{ $prenda->tipo }}</div>
    </div>

    <div class="prenda-card__content">
        <h3 class="prenda-card__title">{{ $prenda->nombre }}</h3>
        
        @if($prenda->referencia)
        <p class="prenda-card__reference">
            <strong>Ref:</strong> {{ $prenda->referencia }}
        </p>
        @endif

        @if($prenda->balanceoActivo)
        <div class="prenda-card__metrics">
            <div>
                <p class="metric-label">Operaciones</p>
                <p class="metric-value">{{ $prenda->balanceoActivo->operaciones_count }}</p>
            </div>
            <div>
                <p class="metric-label">SAM Total</p>
                <p class="metric-value">{{ number_format($prenda->balanceoActivo->sam_total, 1) }}s</p>
            </div>
            <div>
                <p class="metric-label">Operarios</p>
                <p class="metric-value">{{ $prenda->balanceoActivo->total_operarios }}</p>
            </div>
            <div>
                <p class="metric-label">Meta Real</p>
                <p class="metric-value">{{ $prenda->balanceoActivo->meta_real ?? 'N/A' }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
```

---

### 6. **Implementar Cache de Página**

```php
// app/Http/Middleware/CacheResponse.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class CacheResponse
{
    public function handle($request, Closure $next)
    {
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        $key = 'route:' . md5($request->fullUrl());
        
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $response = $next($request);
        
        if ($response->status() === 200) {
            Cache::put($key, $response, now()->addMinutes(5));
        }

        return $response;
    }
}
```

---

### 7. **Optimizar Scripts de AlpineJS**

**Problema:** El script `balanceoApp()` es muy grande (434 líneas) y se carga en todas las páginas.

**Solución - Dividir en módulos:**

```javascript
// resources/js/balanceo/state.js
export function createBalanceoState(balanceoId, initialData) {
    return {
        balanceoId,
        operaciones: initialData.operaciones || [],
        editingCell: null,
        parametros: initialData.parametros || {},
        metricas: initialData.metricas || {},
        showAddModal: false,
        editingOperacion: null,
        pendingOperaciones: [],
        formData: createEmptyFormData()
    };
}

// resources/js/balanceo/operations.js
export function createOperationsMethods() {
    return {
        async saveCell(operacion, field, newValue) {
            // ... código existente
        },
        async deleteOperacion(id) {
            // ... código existente
        }
        // ... otros métodos
    };
}

// resources/js/balanceo/index.js
import { createBalanceoState } from './state';
import { createOperationsMethods } from './operations';

window.balanceoApp = function(balanceoId, initialData) {
    return {
        ...createBalanceoState(balanceoId, initialData),
        ...createOperationsMethods()
    };
};
```

---

### 8. **Agregar Índices a la Base de Datos**

```php
// database/migrations/xxxx_add_indexes_to_balanceo_tables.php
public function up()
{
    Schema::table('prendas', function (Blueprint $table) {
        $table->index('activo');
        $table->index(['activo', 'created_at']);
        $table->index('nombre');
        $table->index('referencia');
        $table->index('tipo');
    });

    Schema::table('balanceos', function (Blueprint $table) {
        $table->index(['prenda_id', 'activo']);
        $table->index('activo');
    });

    Schema::table('operaciones_balanceo', function (Blueprint $table) {
        $table->index('balanceo_id');
        $table->index(['balanceo_id', 'orden']);
    });
}
```

---

## 📈 Mejoras Esperadas

Implementando todas estas optimizaciones, se esperan las siguientes mejoras:

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| **FCP** | 5.47s | ~1.2s | 78% ⬇️ |
| **LCP** | 8.08s | ~2.0s | 75% ⬇️ |
| **TBT** | 4.5ms | ~3ms | 33% ⬇️ |
| **Tamaño JS** | 637KB | ~150KB | 76% ⬇️ |
| **Queries DB** | ~15-20 | ~3-5 | 70% ⬇️ |

---

## 🎯 Plan de Implementación Priorizado

### Fase 1: Quick Wins (1-2 días)
1. ✅ Agregar lazy loading a imágenes
2. ✅ Implementar eager loading optimizado
3. ✅ Agregar índices a la base de datos
4. ✅ Extraer estilos inline a CSS

### Fase 2: Optimizaciones Medias (3-5 días)
5. ✅ Implementar cache de consultas
6. ✅ Optimizar carga de CSS (preload)
7. ✅ Dividir JavaScript en módulos
8. ✅ Implementar lazy loading de AlpineJS

### Fase 3: Optimizaciones Avanzadas (1 semana)
9. ✅ Convertir imágenes a WebP
10. ✅ Implementar cache de respuestas HTTP
11. ✅ Optimizar bundle de Vite
12. ✅ Implementar Service Worker para cache

---

## 🔧 Comandos Útiles

```bash
# Optimizar imágenes a WebP
php artisan balanceo:optimize-images

# Limpiar cache
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Compilar assets optimizados
npm run build

# Analizar bundle size
npm run build -- --analyze
```

---

## 📊 Monitoreo Continuo

Implementar herramientas de monitoreo:

1. **Laravel Telescope** - Para queries N+1
2. **Laravel Debugbar** - Para análisis de rendimiento local
3. **Google PageSpeed Insights** - Para métricas de producción
4. **New Relic / Sentry** - Para monitoreo en tiempo real

---

## 🎓 Recursos Adicionales

- [Web Vitals](https://web.dev/vitals/)
- [Laravel Performance](https://laravel.com/docs/performance)
- [Vite Optimization](https://vitejs.dev/guide/build.html)
- [AlpineJS Best Practices](https://alpinejs.dev/advanced/csp)

---

**Última actualización:** 4 de noviembre de 2025
**Autor:** Análisis de Performance - Módulo Balanceo
