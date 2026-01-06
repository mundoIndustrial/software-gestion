# 🎨 Refactorización Sistema de Logo Cotizaciones - FASE COMPLETA

## ✅ STATUS: LISTO PARA USAR

Has completado **3 fases importantes** de la refactorización DDD:

### 📋 ESTRUCTURA CREADA

#### **FASE 1-2: Base de Datos + Models + Domain**
✅ Completado

```
Database Tables:
├── tipo_logo_cotizaciones (4 registros: BORDADO, ESTAMPADO, SUBLIMADO, DTF)
├── logo_cotizacion_tecnicas (relación Logo → Técnica)
└── logo_cotizacion_tecnica_prendas (prendas por técnica)

Models:
├── TipoLogoCotizacion
├── LogoCotizacionTecnica
└── LogoCotizacionTecnicaPrenda

Domain (app/Domain/LogoCotizacion/):
├── Entities/
│   ├── TecnicaLogoCotizacion.php (Aggregate Root)
│   └── PrendaTecnica.php
├── ValueObjects/
│   ├── TipoTecnica.php
│   ├── UbicacionPrenda.php
│   └── Talla.php
└── Repositories/ (Infrastructure)
    └── LogoCotizacionTecnicaRepository.php
```

#### **FASE 3: Application + Infrastructure**
✅ Completado

```
Application Services:
└── app/Application/LogoCotizacion/Services/
    └── AgregarTecnicaLogoCotizacionService.php
        - Orquesta lógica de negocio
        - Validación completa
        - Usa repository para persistencia

Form Requests:
└── app/Http/Requests/LogoCotizacion/
    ├── AgregarTecnicaRequest.php
    └── AgregarPrendaTecnicaRequest.php

DTOs:
└── app/DTOs/LogoCotizacion/
    ├── AgregarTecnicaDTO.php
    └── AgregarPrendaTecnicaDTO.php

Controllers:
└── app/Infrastructure/Http/Controllers/
    └── LogoCotizacionTecnicaController.php
        - tiposDisponibles() - GET /api/logo-cotizacion-tecnicas/tipos-disponibles
        - agregarTecnica() - POST /api/logo-cotizacion-tecnicas/agregar
        - obtenerTecnicas() - GET /api/logo-cotizacion-tecnicas/cotizacion/{id}
        - eliminarTecnica() - DELETE /api/logo-cotizacion-tecnicas/{id}
        - actualizarObservaciones() - PATCH /api/logo-cotizacion-tecnicas/{id}/observaciones

Routes Registradas:
└── routes/api.php
    POST   /api/logo-cotizacion-tecnicas/agregar
    GET    /api/logo-cotizacion-tecnicas/tipos-disponibles
    GET    /api/logo-cotizacion-tecnicas/cotizacion/{logoCotizacionId}
    DELETE /api/logo-cotizacion-tecnicas/{tecnicaId}
    PATCH  /api/logo-cotizacion-tecnicas/{tecnicaId}/observaciones
```

---

## 📚 CÓMO USAR

### 1️⃣ Obtener tipos de técnicas disponibles

```bash
GET /api/logo-cotizacion-tecnicas/tipos-disponibles

Response:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nombre": "BORDADO",
      "codigo": "BOR",
      "color": "#e74c3c",
      "icono": "fa-needle"
    },
    ...
  ]
}
```

### 2️⃣ Agregar una técnica a una cotización

```bash
POST /api/logo-cotizacion-tecnicas/agregar

{
  "logo_cotizacion_id": 1,
  "tipo_logo_cotizacion_id": 1,
  "observaciones_tecnica": "Bordado de alta calidad",
  "instrucciones_especiales": "Usar hilo poliéster",
  "prendas": [
    {
      "nombre_prenda": "Camisa",
      "descripcion": "Bordado en pecho izquierdo",
      "ubicaciones": ["PECHO"],
      "tallas": ["S", "M", "L", "XL"],
      "cantidad": 50
    },
    {
      "nombre_prenda": "Pantalón",
      "descripcion": "Logo en bolsillo trasero",
      "ubicaciones": ["BOLSILLO TRASERO"],
      "tallas": ["28", "30", "32", "34"],
      "cantidad": 30
    }
  ]
}

Response:
{
  "success": true,
  "message": "Técnica agregada exitosamente",
  "data": {
    "id": 1,
    "tipo": "BORDADO",
    "prendas_count": 2
  }
}
```

### 3️⃣ Obtener técnicas de una cotización

```bash
GET /api/logo-cotizacion-tecnicas/cotizacion/1

Response:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "tipo": {
        "id": 1,
        "nombre": "BORDADO",
        "color": "#e74c3c"
      },
      "observaciones_tecnica": "...",
      "prendas": [
        {
          "id": 1,
          "nombre": "Camisa",
          "descripcion": "...",
          "ubicaciones": ["PECHO"],
          "tallas": ["S", "M", "L"],
          "cantidad": 50
        }
      ]
    }
  ]
}
```

### 4️⃣ Eliminar una técnica

```bash
DELETE /api/logo-cotizacion-tecnicas/1

Response:
{
  "success": true,
  "message": "Técnica eliminada exitosamente"
}
```

### 5️⃣ Actualizar observaciones

```bash
PATCH /api/logo-cotizacion-tecnicas/1/observaciones

{
  "observaciones_tecnica": "Nueva observación"
}
```

---

## 🎯 PRÓXIMOS PASOS

### FASE 4: Rediseño de Vista (formulario con modal)

Necesitas actualizar `resources/views/cotizaciones/bordado/create.blade.php`:

**Nuevo flujo:**
1. Cliente selecciona técnica (BORDADO, ESTAMPADO, SUBLIMADO, DTF)
2. Se abre modal para agregar prendas
3. Por cada prenda:
   - Nombre
   - Descripción (ubicación)
   - Ubicaciones (checkboxes)
   - Tallas
   - Cantidad
4. Guardar técnica → llamar a API
5. ¿Otra técnica? Sí → volver a paso 1

**JavaScript necesario:**
- Llamadas AJAX a los nuevos endpoints
- Modal reutilizable
- Validación cliente-lado
- Renderizado dinámico de técnicas agregadas

### FASE 5: Tests Unitarios

Crear tests para:
- `AgregarTecnicaLogoCotizacionService`
- `TecnicaLogoCotizacion` entity
- `PrendaTecnica` entity
- Controllers (endpoints)

---

## 🔗 RELACIONES ENTRE CAPAS

```
VIEW (formulario modal)
    ↓ (AJAX)
CONTROLLER (LogoCotizacionTecnicaController)
    ↓ (Request validation)
FORM REQUEST (AgregarTecnicaRequest)
    ↓ (DTO conversion)
DTO (AgregarTecnicaDTO)
    ↓ (use case)
APPLICATION SERVICE (AgregarTecnicaLogoCotizacionService)
    ↓ (domain logic)
DOMAIN ENTITIES (TecnicaLogoCotizacion, PrendaTecnica)
    ↓ (persistence)
REPOSITORY (LogoCotizacionTecnicaRepository)
    ↓ (mapping)
ELOQUENT MODELS (LogoCotizacionTecnica, LogoCotizacionTecnicaPrenda)
    ↓
DATABASE
```

---

## 📦 ARCHIVOS CREADOS/MODIFICADOS

### Migraciones
- ✅ `database/migrations/2026_01_06_110000_create_logo_cotizacion_structure.php`
- ✅ `database/seeders/TipoLogoCotizacionSeeder.php`

### Models
- ✅ `app/Models/TipoLogoCotizacion.php` (nuevo)
- ✅ `app/Models/LogoCotizacionTecnica.php` (nuevo)
- ✅ `app/Models/LogoCotizacionTecnicaPrenda.php` (nuevo)
- ✅ `app/Models/LogoCotizacion.php` (actualizado - nuevas relaciones)

### Domain
- ✅ `app/Domain/LogoCotizacion/Entities/TecnicaLogoCotizacion.php`
- ✅ `app/Domain/LogoCotizacion/Entities/PrendaTecnica.php`
- ✅ `app/Domain/LogoCotizacion/ValueObjects/TipoTecnica.php`
- ✅ `app/Domain/LogoCotizacion/ValueObjects/UbicacionPrenda.php`
- ✅ `app/Domain/LogoCotizacion/ValueObjects/Talla.php`

### Application
- ✅ `app/Application/LogoCotizacion/Services/AgregarTecnicaLogoCotizacionService.php`

### Infrastructure
- ✅ `app/Infrastructure/Repositories/LogoCotizacion/LogoCotizacionTecnicaRepository.php`
- ✅ `app/Infrastructure/Http/Controllers/LogoCotizacionTecnicaController.php`

### HTTP Layer
- ✅ `app/Http/Requests/LogoCotizacion/AgregarTecnicaRequest.php`
- ✅ `app/Http/Requests/LogoCotizacion/AgregarPrendaTecnicaRequest.php`
- ✅ `app/DTOs/LogoCotizacion/AgregarTecnicaDTO.php`
- ✅ `app/DTOs/LogoCotizacion/AgregarPrendaTecnicaDTO.php`
- ✅ `app/Traits/LogoCotizacionTrait.php`

### Routes
- ✅ `routes/api.php` (actualizado con nuevas rutas)

---

## 🧪 TESTING RÁPIDO

```bash
# Verificar sintaxis
php -l app/Infrastructure/Http/Controllers/LogoCotizacionTecnicaController.php

# Ver rutas registradas
php artisan route:list | grep logo-cotizacion

# Test en Tinker
php artisan tinker

# Dentro de tinker:
$tipos = App\Models\TipoLogoCotizacion::activos()->get();
$tipos->each(fn($t) => echo $t->nombre . "\n");
```

---

## 💡 VENTAJAS DE ESTA ARQUITECTURA

✅ **Separación clara**: Domain ↔ Application ↔ Infrastructure  
✅ **Testeable**: Entities sin dependencias a Laravel  
✅ **Escalable**: Fácil agregar más técnicas o funcionalidades  
✅ **Mantenible**: Lógica de negocio centralizada en Services  
✅ **Type-safe**: Uso de ValueObjects para propiedades críticas  
✅ **SOLID**: SRP, DIP, OCP respetados  

---

## ❓ DUDAS O CAMBIOS

Si necesitas:
- Modificar flujo de prendas
- Agregar más campos a técnicas
- Cambiar validaciones
- Integrar con otras funcionalidades

Avísame y ajustamos 🚀
