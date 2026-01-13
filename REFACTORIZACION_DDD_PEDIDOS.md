# Refactorización DDD - Pedidos Editables

## Estado: En Progreso

### ✅ Completado

1. **DTOs creados**
   - `ItemPedidoDTO.php` - Transferencia de datos entre capas

2. **Services de Dominio creados**
   - `GestionItemsPedidoService.php` - Orquestación de ítems
   - `TransformadorCotizacionService.php` - Transformación de datos

3. **Controller creado**
   - `CrearPedidoEditableController.php` - Orquestación de lógica

4. **Rutas API creadas**
   - `routes/api-pedidos-editable.php` - Endpoints REST

5. **API Client JavaScript creado**
   - `api-pedidos-editable.js` - Comunicación con backend

### 📋 Próximos Pasos

#### 1. Registrar rutas en `routes/api.php`
```php
require base_path('routes/api-pedidos-editable.php');
```

#### 2. Refactorizar Blade - Eliminar toda lógica inline

**Cambios en `crear-desde-cotizacion-editable.blade.php`:**

- ❌ Eliminar: Bloque `@php` con transformación de cotizaciones (líneas 253-276)
- ❌ Eliminar: Variables globales de imágenes (líneas 294-298)
- ❌ Eliminar: Funciones de manejo de imágenes inline
- ❌ Eliminar: Lógica de ítems (`itemsPedido`, `agregarItem`, etc.)
- ❌ Eliminar: Funciones de validación
- ❌ Eliminar: Código de debug console.log

**Reemplazar con:**

```blade
@push('scripts')
    <script src="{{ asset('js/modulos/crear-pedido/api-pedidos-editable.js') }}"></script>
    <script>
        // Datos del servidor (solo presentación)
        window.cotizacionesData = @json($cotizacionesData);
        window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';
    </script>
    <script src="{{ asset('js/modulos/crear-pedido/gestion-items-pedido-refactorizado.js') }}"></script>
@endpush
```

#### 3. Crear `gestion-items-pedido-refactorizado.js`

Este archivo solo maneja:
- Eventos de UI (clicks, cambios)
- Llamadas a `window.pedidosAPI`
- Actualización de vistas

```javascript
class GestionItemsUI {
    constructor() {
        this.api = window.pedidosAPI;
        this.inicializar();
    }

    inicializar() {
        document.getElementById('btn-agregar-item-cotizacion')?.addEventListener('click', 
            () => this.agregarItemCotizacion());
        document.getElementById('formCrearPedidoEditable')?.addEventListener('submit',
            (e) => this.crearPedido(e));
    }

    async agregarItemCotizacion() {
        try {
            const itemData = this.recolectarDatosItem();
            const resultado = await this.api.agregarItem(itemData);
            this.actualizarVistaItems(resultado.items);
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async crearPedido(e) {
        e.preventDefault();
        
        try {
            const validacion = await this.api.validarPedido();
            if (!validacion.valid) {
                alert('Errores: ' + validacion.errores.join('\n'));
                return;
            }

            const pedidoData = this.recolectarDatosPedido();
            const resultado = await this.api.crearPedido(pedidoData);
            
            alert('Pedido creado: ' + resultado.pedido_id);
            window.location.href = '/asesores/pedidos-produccion';
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    recolectarDatosItem() {
        // Recolectar datos del formulario
        return {
            tipo: 'cotizacion',
            prenda: { /* datos */ },
            origen: 'bodega',
            tallas: [],
        };
    }

    recolectarDatosPedido() {
        return {
            cliente: document.getElementById('cliente_editable').value,
            asesora: document.getElementById('asesora_editable').value,
            forma_de_pago: document.getElementById('forma_de_pago_editable').value,
        };
    }

    actualizarVistaItems(items) {
        // Actualizar UI con nuevos ítems
        console.log('Ítems actualizados:', items);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new GestionItemsUI();
});
```

#### 4. Separar manejo de imágenes

Crear `image-storage-service.js`:
```javascript
class ImageStorageService {
    constructor(maxImages = 3) {
        this.maxImages = maxImages;
        this.images = [];
    }

    agregarImagen(file) {
        if (this.images.length >= this.maxImages) {
            throw new Error(`Máximo ${this.maxImages} imágenes permitidas`);
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            this.images.push({
                data: e.target.result,
                file: file,
            });
        };
        reader.readAsDataURL(file);
    }

    obtenerImagenes() {
        return this.images;
    }

    limpiar() {
        this.images = [];
    }
}
```

### 📊 Estructura Final

```
Backend (PHP - DDD):
├── DTOs/
│   └── ItemPedidoDTO.php
├── Services/
│   ├── GestionItemsPedidoService.php
│   └── TransformadorCotizacionService.php
└── Controllers/
    └── CrearPedidoEditableController.php

Frontend (JavaScript - Solo UI):
├── api-pedidos-editable.js (Comunicación HTTP)
├── gestion-items-pedido-refactorizado.js (Eventos UI)
└── image-storage-service.js (Manejo de imágenes)

Blade (Solo Presentación):
└── crear-desde-cotizacion-editable.blade.php
```

### 🎯 Beneficios

✅ **Separación clara de responsabilidades**
✅ **Lógica de negocio en backend (segura, reutilizable)**
✅ **Frontend solo maneja presentación y eventos**
✅ **Fácil de testear (cada capa independiente)**
✅ **Escalable (agregar nuevas funcionalidades sin tocar Blade)**
✅ **Mantenible (cambios en lógica = cambios en backend)**

### 🔄 Migración Paso a Paso

1. Registrar rutas API
2. Crear `gestion-items-pedido-refactorizado.js`
3. Crear `image-storage-service.js`
4. Refactorizar Blade (eliminar lógica)
5. Testear endpoints API
6. Eliminar código inline del Blade
