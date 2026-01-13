# Refactorización DDD - Pedidos Editables
## Resumen Ejecutivo

### 🎯 Objetivo
Mover toda la lógica de negocio del frontend (Blade + JavaScript inline) al backend, siguiendo principios de Domain-Driven Design.

---

## ✅ Archivos Creados

### Backend (PHP)

#### 1. **DTOs** - Transferencia de datos entre capas
- `app/Application/DTOs/ItemPedidoDTO.php`
  - Encapsula datos de un ítem del pedido
  - Métodos: `fromArray()`, `toArray()`

#### 2. **Services de Dominio** - Lógica de negocio
- `app/Domain/PedidoProduccion/Services/GestionItemsPedidoService.php`
  - Gestiona colección de ítems
  - Métodos: `agregarItem()`, `eliminarItem()`, `validar()`, `obtenerItems()`
  - **Responsabilidad única**: Orquestación de ítems

- `app/Domain/PedidoProduccion/Services/TransformadorCotizacionService.php`
  - Transforma datos de cotizaciones para frontend
  - Métodos: `transformarCotizacionesParaFrontend()`, `transformarCotizacionDetalle()`
  - **Responsabilidad única**: Transformación de datos

#### 3. **Controller** - Orquestación de casos de uso
- `app/Http/Controllers/Asesores/CrearPedidoEditableController.php`
  - Endpoints: `agregarItem()`, `eliminarItem()`, `obtenerItems()`, `validarPedido()`, `crearPedido()`
  - Inyección de dependencias de Services
  - Validación de requests

#### 4. **Rutas API** - Endpoints REST
- `routes/api-pedidos-editable.php`
  - Prefijo: `/api/pedidos-editable`
  - Middleware: `auth`, `role:asesor`
  - Endpoints:
    - `POST /items/agregar`
    - `POST /items/eliminar`
    - `GET /items`
    - `POST /validar`
    - `POST /crear`

### Frontend (JavaScript)

#### 1. **API Client** - Comunicación HTTP
- `public/js/modulos/crear-pedido/api-pedidos-editable.js`
  - Clase: `PedidosEditableAPI`
  - Métodos: `agregarItem()`, `eliminarItem()`, `obtenerItems()`, `validarPedido()`, `crearPedido()`
  - Manejo de errores y CSRF tokens
  - **Responsabilidad única**: Comunicación con backend

#### 2. **UI Manager** - Presentación y eventos
- `public/js/modulos/crear-pedido/gestion-items-pedido-refactorizado.js`
  - Clase: `GestionItemsUI`
  - Métodos: `agregarItem()`, `eliminarItem()`, `actualizarVistaItems()`, `manejarSubmitFormulario()`
  - Event listeners para botones y formularios
  - Notificaciones al usuario
  - **Responsabilidad única**: Gestión de UI

#### 3. **Image Storage** - Manejo de imágenes
- `public/js/modulos/crear-pedido/image-storage-service.js`
  - Clase: `ImageStorageService`
  - Métodos: `agregarImagen()`, `eliminarImagen()`, `toFormData()`, `toJSON()`
  - Validación de archivos
  - Conversión a diferentes formatos
  - **Responsabilidad única**: Almacenamiento temporal de imágenes

---

## 📊 Arquitectura DDD

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                       │
│  crear-desde-cotizacion-editable.blade.php (Solo HTML)     │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                   APPLICATION LAYER                         │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ gestion-items-pedido-refactorizado.js (UI Events)   │  │
│  │ api-pedidos-editable.js (HTTP Communication)        │  │
│  │ image-storage-service.js (Image Management)         │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                    API LAYER (REST)                         │
│  CrearPedidoEditableController                             │
│  routes/api-pedidos-editable.php                           │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                   DOMAIN LAYER (Business Logic)             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ GestionItemsPedidoService                           │  │
│  │ TransformadorCotizacionService                      │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                   DATA LAYER (Persistence)                  │
│  Database / Repositories                                    │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔄 Flujo de Datos

### Agregar Ítem
```
1. Usuario hace click en "Agregar Ítem"
   ↓
2. GestionItemsUI.agregarItem() recolecta datos
   ↓
3. PedidosEditableAPI.agregarItem() envía POST /api/pedidos-editable/items/agregar
   ↓
4. CrearPedidoEditableController.agregarItem() valida y procesa
   ↓
5. GestionItemsPedidoService.agregarItem() agrega a colección
   ↓
6. Respuesta JSON con items actualizados
   ↓
7. GestionItemsUI.actualizarVistaItems() renderiza nuevos ítems
```

### Crear Pedido
```
1. Usuario envía formulario
   ↓
2. GestionItemsUI.manejarSubmitFormulario() valida
   ↓
3. PedidosEditableAPI.validarPedido() POST /api/pedidos-editable/validar
   ↓
4. CrearPedidoEditableController.validarPedido() ejecuta validaciones
   ↓
5. GestionItemsPedidoService.validar() retorna errores o éxito
   ↓
6. Si válido: PedidosEditableAPI.crearPedido() POST /api/pedidos-editable/crear
   ↓
7. CrearPedidoEditableController.crearPedido() crea pedido en BD
   ↓
8. GestionItemsPedidoService.limpiar() limpia estado
   ↓
9. Redirección a /asesores/pedidos-produccion
```

---

## 📋 Próximos Pasos

### 1. Registrar rutas en `routes/api.php`
```php
// Al final del archivo
require base_path('routes/api-pedidos-editable.php');
```

### 2. Actualizar Blade `crear-desde-cotizacion-editable.blade.php`
- ❌ Eliminar bloque `@php` con transformación de cotizaciones (líneas 253-276)
- ❌ Eliminar variables globales de imágenes (líneas 294-298)
- ❌ Eliminar todas las funciones inline de manejo de imágenes
- ❌ Eliminar lógica de ítems (`itemsPedido`, `agregarItem`, etc.)
- ❌ Eliminar código de debug `console.log`

- ✅ Agregar en `@push('scripts')`:
```blade
<script src="{{ asset('js/modulos/crear-pedido/api-pedidos-editable.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/image-storage-service.js') }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/gestion-items-pedido-refactorizado.js') }}"></script>
<script>
    window.cotizacionesData = @json($cotizacionesData);
    window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';
</script>
```

### 3. Testear endpoints API
```bash
# Agregar ítem
POST /api/pedidos-editable/items/agregar
{
    "tipo": "cotizacion",
    "prenda": {"id": 1, "nombre": "Camisa"},
    "origen": "bodega",
    "tallas": ["M", "L"]
}

# Obtener ítems
GET /api/pedidos-editable/items

# Validar
POST /api/pedidos-editable/validar

# Crear pedido
POST /api/pedidos-editable/crear
{
    "cliente": "Cliente XYZ",
    "asesora": "Asesora ABC",
    "forma_de_pago": "Efectivo"
}
```

### 4. Refactorizar modales
- Extraer lógica de modales a Services
- Crear endpoints API para operaciones de modales

---

## 🎯 Beneficios Logrados

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Lógica de negocio** | En Blade + JS inline | En Services de dominio |
| **Testabilidad** | Difícil (mezclado) | Fácil (separado por capas) |
| **Reutilización** | No (solo en Blade) | Sí (cualquier cliente HTTP) |
| **Mantenibilidad** | Compleja (múltiples archivos) | Simple (cada capa responsable) |
| **Escalabilidad** | Limitada (crece el Blade) | Ilimitada (agregar Services) |
| **Seguridad** | Lógica expuesta en cliente | Lógica protegida en servidor |
| **Líneas de código Blade** | ~1,850 | ~500 (estimado) |

---

## 📚 Documentación Adicional

Ver: `REFACTORIZACION_DDD_PEDIDOS.md` para detalles técnicos paso a paso.

---

## ✨ Resultado Final

**Blade limpio y enfocado en presentación**
- Solo HTML y datos
- Sin lógica de negocio
- Fácil de mantener

**Backend robusto y escalable**
- Lógica centralizada
- Fácil de testear
- Reutilizable en múltiples clientes

**Frontend modular y responsable**
- Cada módulo tiene una responsabilidad
- Comunicación clara con backend
- Fácil de debuggear
