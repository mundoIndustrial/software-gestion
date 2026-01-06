# 🎨 Refactorización Sistema de Logo Cotizaciones - Estructura DDD

## ✅ COMPLETADO - FASE 1 y 2 (BD + Models + DDD)

### 📊 BASE DE DATOS (3 tablas nuevas)

```
┌─────────────────────────────────────┐
│  tipo_logo_cotizaciones             │
├─────────────────────────────────────┤
│ id                                  │
│ nombre (BORDADO, ESTAMPADO, etc)   │
│ codigo (BOR, EST, SUB, DTF)        │
│ descripcion                         │
│ color (para UI)                     │
│ icono (FontAwesome)                 │
│ orden, activo, timestamps           │
└─────────────────────────────────────┘
         ↓ (foreign key)
┌─────────────────────────────────────┐
│  logo_cotizacion_tecnicas           │
├─────────────────────────────────────┤
│ id                                  │
│ logo_cotizacion_id ──→ Cotización  │
│ tipo_logo_cotizacion_id → Técnica  │
│ observaciones_tecnica               │
│ instrucciones_especiales            │
│ orden, activo, timestamps           │
└─────────────────────────────────────┘
         ↓ (1:N)
┌─────────────────────────────────────┐
│ logo_cotizacion_tecnica_prendas     │
├─────────────────────────────────────┤
│ id                                  │
│ logo_cotizacion_tecnica_id ↑        │
│ nombre_prenda (Camisa, Pantalón)   │
│ descripcion (ubicación: pecho, etc) │
│ ubicaciones (JSON array)            │
│ tallas (JSON array)                 │
│ cantidad                            │
│ especificaciones, color_hilo, etc   │
│ orden, activo, timestamps           │
└─────────────────────────────────────┘
```

### 📦 MODELS ELOQUENT (3 modelos)

**1. TipoLogoCotizacion** → `app/Models/TipoLogoCotizacion.php`
   - Representa los 4 tipos de técnicas
   - Relación: hasMany(LogoCotizacionTecnica)
   - Scopes: activos(), porCodigo()

**2. LogoCotizacionTecnica** → `app/Models/LogoCotizacionTecnica.php`
   - Vincula LogoCotizacion con TipoLogoCotizacion
   - Relaciones: belongsTo(LogoCotizacion), belongsTo(TipoLogoCotizacion), hasMany(Prendas)
   - Accessors: nombreTecnica, color

**3. LogoCotizacionTecnicaPrenda** → `app/Models/LogoCotizacionTecnicaPrenda.php`
   - Almacena prendas específicas de cada técnica
   - Casts automáticos de JSON para ubicaciones y tallas
   - Accessors: ubicacionesText, tallasText

### 🎯 DOMINIO DDD

**ValueObjects** → `app/Domain/LogoCotizacion/ValueObjects/`

1. **TipoTecnica.php**
   - Immutable value object
   - Métodos factory: bordado(), estampado(), sublimado(), dtf()
   - Validación de nombre y código

2. **UbicacionPrenda.php**
   - Representa ubicación en prenda (PECHO, ESPALDA, MANGA, etc)
   - Métodos factory para ubicaciones comunes
   - Inmutable

3. **Talla.php**
   - Representa talla de prenda (XS, S, M, L, XL, 2XL, etc)
   - Siempre en mayúsculas
   - Comparable con equals()

**Entities** → `app/Domain/LogoCotizacion/Entities/`

1. **PrendaTecnica.php**
   - Entity que representa una prenda dentro de una técnica
   - Propiedades: nombre, descripción, ubicaciones, tallas, cantidad, especificaciones
   - Métodos: actualizarCantidad(), actualizarUbicaciones(), activar(), desactivar()

2. **TecnicaLogoCotizacion.php** (Aggregate Root)
   - Agrupa tipo de técnica + prendas asociadas
   - Relaciones: pertenece a LogoCotizacion, tiene múltiples PrendaTecnica
   - Métodos: agregarPrenda(), eliminarPrenda(), actualizarObservaciones()
   - Business logic: tienePrendas(), contarPrendas()

### 💾 INFRASTRUCTURE

**Repository** → `app/Infrastructure/Repositories/LogoCotizacion/LogoCotizacionTecnicaRepository.php`
- Persiste TecnicaLogoCotizacion en BD
- Métodos: save(), findById(), findByLogoCotizacionId(), delete()
- Mapea modelos Eloquent a entities del dominio

### 🚀 APPLICATION SERVICES

**AgregarTecnicaLogoCotizacionService** → `app/Application/LogoCotizacion/Services/AgregarTecnicaLogoCotizacionService.php`
- Orquesta la lógica de agregar técnica a cotización
- Validación completa de datos
- Persistencia mediante repository
- Manejo de errores con InvalidArgumentException

### 📋 SEEDER

**TipoLogoCotizacionSeeder** → `database/seeders/TipoLogoCotizacionSeeder.php`
```php
// Tipos creados:
- BORDADO (BOR) - Color: #e74c3c (rojo)
- ESTAMPADO (EST) - Color: #3498db (azul)
- SUBLIMADO (SUB) - Color: #f39c12 (naranja)
- DTF (DTF) - Color: #9b59b6 (púrpura)
```

---

## 📝 PRÓXIMOS PASOS (FASE 3)

### Controllers
- Actualizar `CotizacionBordadoController@store` para usar service
- Crear endpoint para agregar técnica
- Crear endpoint para agregar prenda a técnica
- Crear endpoint para eliminar técnica/prenda

### Views
- Rediseñar `create.blade.php` con flujo modal
  1. Seleccionar técnica
  2. Modal: agregar prendas (nombre, descripción, ubicaciones, tallas, cantidad)
  3. Guardar sección
  4. ¿Otra técnica? Sí → Volver a paso 1, No → Finalizar

### Requests (Form Validation)
- AgregarTecnicaRequest
- AgregarPrendaTecnicaRequest

### DTOs (Data Transfer Objects)
- AgregarTecnicaDTO
- AgregarPrendaDTO

---

## 🧪 TESTING

Crear tests para:
- AgregarTecnicaLogoCotizacionService
- LogoCotizacionTecnicaRepository
- TecnicaLogoCotizacion entity
- PrendaTecnica entity

---

## 📚 ESTRUCTURA DE CARPETAS ACTUAL

```
app/
├── Domain/
│   └── LogoCotizacion/
│       ├── Entities/
│       │   ├── TecnicaLogoCotizacion.php ✅
│       │   └── PrendaTecnica.php ✅
│       └── ValueObjects/
│           ├── TipoTecnica.php ✅
│           ├── UbicacionPrenda.php ✅
│           └── Talla.php ✅
├── Application/
│   └── LogoCotizacion/
│       └── Services/
│           └── AgregarTecnicaLogoCotizacionService.php ✅
├── Infrastructure/
│   └── Repositories/
│       └── LogoCotizacion/
│           └── LogoCotizacionTecnicaRepository.php ✅
└── Models/
    ├── TipoLogoCotizacion.php ✅
    ├── LogoCotizacionTecnica.php ✅
    ├── LogoCotizacionTecnicaPrenda.php ✅
    └── LogoCotizacion.php (actualizado) ✅

database/
├── migrations/
│   ├── 2026_01_06_050000_clean_logo_cotizacion_tables.php ✅
│   ├── 2026_01_06_100000_create_tipo_logo_cotizaciones_table.php ✅
│   ├── 2026_01_06_100100_create_logo_cotizacion_tecnicas_table.php ✅
│   └── 2026_01_06_100200_create_logo_cotizacion_tecnica_prendas_table.php ✅
└── seeders/
    └── TipoLogoCotizacionSeeder.php ✅
```

---

## 🎯 VENTAJAS DE ESTA ARQUITECTURA

✅ **Separación de responsabilidades** - Domain logic separado de persistencia
✅ **Testeable** - Entities y Services son independientes de BD
✅ **Escalable** - Fácil agregar más tipos de técnicas o funcionalidades
✅ **DDD completo** - ValueObjects, Entities, Repositories, Services
✅ **Validación en capas** - En Application Service y en Entity
✅ **Type-safe** - Uso de ValueObjects para propiedades críticas
