# 📋 VISTAS DE COTIZACIONES DISPONIBLES

**Fecha:** 10 de Diciembre de 2025

---

## 🎯 VISTAS EXISTENTES

### 1. Vista General de Cotizaciones
**Ruta:** `resources/views/cotizaciones/index.blade.php`
**Acceso:** `/asesores/cotizaciones`
**Descripción:** Tabla con todas las cotizaciones
**Características:**
- Tabla con columnas: Número, Fecha, Cliente, Asesora, Estado, Acciones
- Modal para ver detalles
- Botones para ver y descargar PDF
- Información de productos y prendas
- Subida de imágenes

**Estructura:**
```blade
@extends('layouts.app')
<table class="cotizaciones-table">
    @forelse($cotizaciones as $cotizacion)
        <tr>
            <td>{{ $cotizacion->id }}</td>
            <td>{{ $cotizacion->created_at }}</td>
            <td>{{ $cotizacion->cliente }}</td>
            <td>{{ $cotizacion->asesora }}</td>
            <td>{{ $cotizacion->estado }}</td>
            <td>
                <button onclick="openCotizacionModal({{ $cotizacion->id }})">Ver</button>
            </td>
        </tr>
    @endforelse
</table>
```

---

### 2. Vista de Cotizaciones de Bordado (Asesoras)
**Ruta:** `resources/views/cotizaciones/bordado/lista.blade.php`
**Acceso:** `/asesores/cotizaciones-bordado` (antigua)
**Descripción:** Lista de cotizaciones de bordado para asesoras
**Características:**
- Diseño de tarjetas (cards)
- Información: Número, Cliente, Estado, Fecha
- Botones: Editar, Enviar, Eliminar, Ver Pedido
- Acciones específicas para borradores
- Integración con pedidos de producción

**Estructura:**
```blade
@extends('layouts.asesores')
<div class="grid gap-4">
    @foreach ($cotizaciones as $cotizacion)
    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <p>Número de Cotización</p>
                <p>{{ $cotizacion->numero_cotizacion }}</p>
            </div>
            <div>
                <p>Cliente</p>
                <p>{{ $cotizacion->cliente }}</p>
            </div>
            <div>
                <p>Estado</p>
                <span>{{ $cotizacion->estado }}</span>
            </div>
            <div>
                <p>Creada</p>
                <p>{{ $cotizacion->created_at->format('d/m/Y') }}</p>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t flex gap-3">
            <a href="{{ route('asesores.cotizaciones-bordado.edit', $cotizacion->id) }}">Editar</a>
            @if ($cotizacion->estado === 'borrador')
            <button onclick="confirmarEnvio({{ $cotizacion->id }})">Enviar</button>
            <button onclick="confirmarEliminar({{ $cotizacion->id }})">Eliminar</button>
            @endif
            <a href="{{ route('pedidos-produccion.show', $cotizacion->pedidosProduccion()->first()->id ?? '#') }}">Ver Pedido</a>
        </div>
    </div>
    @endforeach
</div>
```

---

## 🔄 RUTAS RELACIONADAS

### Rutas Activas (DDD)
```php
// Vista HTML
Route::get('/cotizaciones', [CotizacionesViewController::class, 'index'])->name('cotizaciones.index');

// API endpoints
Route::post('/cotizaciones', [CotizacionController::class, 'store'])->name('cotizaciones.store');
Route::get('/cotizaciones/{id}', [CotizacionController::class, 'show'])->name('cotizaciones.show');
Route::post('/cotizaciones/{id}/imagenes', [CotizacionController::class, 'subirImagen'])->name('cotizaciones.subir-imagen');
Route::delete('/cotizaciones/{id}', [CotizacionController::class, 'destroy'])->name('cotizaciones.destroy');
```

### Rutas Antiguas (Compatibilidad)
```php
Route::post('/cotizaciones/guardar', [CotizacionController::class, 'store'])->name('cotizaciones.guardar');
Route::get('/cotizaciones/{id}/editar-borrador', [CotizacionController::class, 'show'])->name('cotizaciones.edit-borrador');
Route::get('/cotizaciones/filtros/valores', function() { return response()->json([]); })->name('cotizaciones.filtros.valores');
```

---

## 📊 COMPARATIVA

| Aspecto | Vista General | Vista Bordado |
|---------|---------------|---------------|
| **Archivo** | `cotizaciones/index.blade.php` | `cotizaciones/bordado/lista.blade.php` |
| **Layout** | `layouts.app` | `layouts.asesores` |
| **Tipo de vista** | Tabla | Tarjetas (cards) |
| **Acciones** | Ver, PDF | Editar, Enviar, Eliminar, Ver Pedido |
| **Estado** | Fijo (Entregar) | Dinámico |
| **Público** | Todos | Asesoras |
| **Integración** | Modal | Pedidos de producción |

---

## 🎯 RECOMENDACIÓN

**Usar:** `resources/views/cotizaciones/index.blade.php`
- Es la vista general más completa
- Tiene mejor estructura
- Soporta modal con detalles
- Compatible con DDD

**Alternativa:** `resources/views/cotizaciones/bordado/lista.blade.php`
- Si necesitas vista específica para asesoras
- Mejor para gestión de borradores
- Integración directa con pedidos

---

**Documentación creada:** 10 de Diciembre de 2025
**Estado:** ✅ COMPLETADO
