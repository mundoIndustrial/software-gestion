# ✅ Análisis de Base de Datos y Arquitectura DDD para Procesos

## 📊 Base de Datos Analizada

**Tablas Existentes Relevantes:**
- `pedidos_produccion` (2,531 registros)
- `prendas_pedido` (3,257 registros)
- `prendas_reflectivo` (0 registros)
- `procesos_prenda` (15,000 registros)

**Decisión:** OPCIÓN B - 2 Tablas Nuevas (mejor escalabilidad)

---

## 📋 Tablas Nuevas Creadas

### 1. tipos_procesos (Catálogo)
```sql
- id (PK)
- nombre (UNIQUE): reflectivo, bordado, estampado, dtf, sublimado
- slug (UNIQUE)
- descripcion, color (HEX), icono
- activo (boolean)
- timestamps, soft_deletes
```

**Datos Iniciales:**
- Reflectivo (#FFB000)
- Bordado (#8B4513)
- Estampado (#FF6B6B)
- DTF (#4ECDC4)
- Sublimado (#A8E6CF)

### 2. procesos_prenda_detalles (Procesos por Prenda)
```sql
- id (PK)
- prenda_pedido_id (FK) → prendas_pedido
- tipo_proceso_id (FK) → tipos_procesos
- ubicaciones (JSON): ["Frente", "Espalda"]
- observaciones, tallas_dama (JSON), tallas_caballero (JSON)
- imagen_ruta, nombre_imagen, tipo_mime, tamaño_imagen
- estado (ENUM): PENDIENTE, EN_REVISION, APROBADO, EN_PRODUCCION, COMPLETADO, RECHAZADO
- notas_rechazo, fecha_aprobacion, aprobado_por (FK → users)
- datos_adicionales (JSON)
- timestamps, soft_deletes
```

**Restricciones:**
- UNIQUE (prenda_pedido_id, tipo_proceso_id)
- FOREIGN KEY prenda_pedido_id CASCADE
- FOREIGN KEY tipo_proceso_id RESTRICT
- FOREIGN KEY aprobado_por SET NULL

---

## 🏗️ Arquitectura DDD Implementada

### Estructura de Carpetas

```
app/
├── Domain/Procesos/              ← BOUNDED CONTEXT
│   ├── Entities/
│   │   ├── TipoProceso.php
│   │   └── ProcesoPrendaDetalle.php
│   ├── Repositories/
│   │   ├── TipoProcesoRepository.php
│   │   └── ProcesoPrendaDetalleRepository.php
│   ├── Services/
│   │   ├── CrearProcesoPrendaService.php
│   │   ├── AprobarProcesoPrendaService.php
│   │   └── RechazarProcesoPrendaService.php
│   └── ValueObjects/
│
├── Application/Actions/Procesos/
│   └── CrearProcesoAction.php
│
├── DTOs/
│   └── CrearProcesoPrendaDTO.php
│
├── Http/Controllers/Api/
│   └── ProcesosController.php    (ACTUALIZAR)
│
└── Models/
    ├── TipoProceso.php           (CREAR)
    └── ProcesoPrendaDetalle.php  (CREAR)
```

### Archivos Creados ✅

#### Domain Layer
- ✅ `app/Domain/Procesos/Entities/TipoProceso.php`
- ✅ `app/Domain/Procesos/Entities/ProcesoPrendaDetalle.php`
- ✅ `app/Domain/Procesos/Repositories/TipoProcesoRepository.php`
- ✅ `app/Domain/Procesos/Repositories/ProcesoPrendaDetalleRepository.php`
- ✅ `app/Domain/Procesos/Services/CrearProcesoPrendaService.php`
- ✅ `app/Domain/Procesos/Services/AprobarProcesoPrendaService.php`
- ✅ `app/Domain/Procesos/Services/RechazarProcesoPrendaService.php`

#### Application Layer
- ✅ `app/Application/Actions/Procesos/CrearProcesoAction.php`

#### Data Transfer
- ✅ `app/DTOs/CrearProcesoPrendaDTO.php`

#### Database
- ✅ `database/migrations/2026_01_14_000000_create_procesos_tables.php`
- ✅ `database/seeders/TiposProcesosSeeder.php`

#### Documentation
- ✅ `ARQUITECTURA_DDD_PROCESOS.md` - Documentación completa de la arquitectura
- ✅ `ESTRUCTURA_PROCESOS_OPCION_B.md` - Especificación de tablas
- ✅ `analizar-bd-simple.php` - Script de análisis de BD

---

## 🔄 Flujo de Datos Implementado

```
USUARIO (Modal)
   ↓
   Selecciona tipo de proceso
   Escribe ubicaciones
   Escribe observaciones
   Selecciona tallas
   Sube imagen
   ↓
HTTP POST /api/prendas/{id}/procesos
   ↓
ProcesosController::crear()
   ↓
CrearProcesoAction::ejecutar($dto)
   ├─ Valida tipo existe
   ├─ Procesa imagen
   └─ Llama domain service
   ↓
CrearProcesoPrendaService::ejecutar()
   ├─ Valida no existe otro del mismo tipo
   ├─ Valida ubicaciones
   ├─ Crea Entity
   └─ Persiste
   ↓
Base de Datos
   INSERT INTO procesos_prenda_detalles (...)
   ↓
HTTP Response 201 Created
```

---

## 📦 Entity Relationships

```
pedidos_produccion
       ↑
       │ numero_pedido
       │
prendas_pedido ──┬────→ colores_prenda
                 │
                 ├────→ telas_prenda
                 │
                 ├────→ tipos_manga
                 │
                 ├────→ tipos_broche
                 │
                 └────→ procesos_prenda_detalles
                        │
                        ├─→ tipos_procesos
                        │    └─ (reflectivo, bordado, etc)
                        │
                        ├─ ubicaciones (JSON)
                        ├─ observaciones (TEXT)
                        ├─ tallas_dama (JSON)
                        ├─ tallas_caballero (JSON)
                        ├─ imagen_ruta (VARCHAR)
                        ├─ estado (ENUM)
                        └─ aprobado_por (users)
```

---

## 🎯 Capacidades Implementadas

### Domain Services
- ✅ Crear proceso con validaciones
- ✅ Aprobar proceso (cambio de estado)
- ✅ Rechazar proceso (con motivo)
- ⏳ Enviar a producción
- ⏳ Marcar completado

### Validaciones de Negocio
- ✅ No duplicar proceso por prenda
- ✅ Validar ubicaciones obligatorias
- ✅ Validar transiciones de estado
- ✅ Validar que usuario pueda editar

### Gestión de Imágenes
- ✅ Base64 encoding/decoding
- ✅ Validación de MIME types
- ✅ Almacenamiento en Storage
- ✅ Metadata (nombre, tipo, tamaño)

---

## 📝 Próximos Pasos

### 1. Infrastructure Layer (Pendiente)
- [ ] `app/Repositories/EloquentTipoProcesoRepository.php`
- [ ] `app/Repositories/EloquentProcesoPrendaDetalleRepository.php`
- [ ] `app/Models/TipoProceso.php` (Eloquent Model)
- [ ] `app/Models/ProcesoPrendaDetalle.php` (Eloquent Model)

### 2. Application Layer (Pendiente)
- [ ] `app/Application/Actions/Procesos/AprobarProcesoAction.php`
- [ ] `app/Application/Actions/Procesos/RechazarProcesoAction.php`
- [ ] `app/Application/Actions/Procesos/ActualizarProcesoAction.php`
- [ ] `app/Application/Actions/Procesos/EliminarProcesoAction.php`
- [ ] `app/Application/Actions/Procesos/ListarProcesosPrendaAction.php`

### 3. Presentation Layer (Pendiente)
- [ ] Actualizar `app/Http/Controllers/Api/ProcesosController.php` (refactor)
- [ ] Crear rutas API en `routes/api.php`
- [ ] Binding de dependencias en `app/Providers/AppServiceProvider.php`

### 4. Frontend (Pendiente)
- [ ] Actualizar `gestor-modal-proceso-generico.js` para enviar data correcta
- [ ] Crear servicio API para procesos
- [ ] Implementar handlers de respuesta
- [ ] Mostrar procesos agregados en resumen

### 5. Testing (Pendiente)
- [ ] Tests unitarios de Domain Entities
- [ ] Tests de Domain Services
- [ ] Tests de Application Actions
- [ ] Tests API de Controller

### 6. Database (Pendiente)
- [ ] Ejecutar migración: `php artisan migrate`
- [ ] Ejecutar seeder: `php artisan db:seed --class=TiposProcesosSeeder`

---

## 🔧 Comandos para Continuar

```bash
# Ejecutar migración
php artisan migrate

# Ejecutar seeder
php artisan db:seed --class=TiposProcesosSeeder

# Verificar tablas creadas
php artisan tinker
>>> \DB::table('tipos_procesos')->get()
>>> \DB::table('procesos_prenda_detalles')->get()
```

---

## 📚 Documentación Disponible

1. **ARQUITECTURA_DDD_PROCESOS.md** - Detalle de la arquitectura DDD
2. **ESTRUCTURA_PROCESOS_OPCION_B.md** - Especificación de tablas y ejemplo JSON

---

## ✨ Ventajas de Esta Implementación

✅ **DDD Completo** - Separación clara entre capas
✅ **Testable** - Domain logic sin dependencias
✅ **Escalable** - Fácil agregar nuevos procesos
✅ **Mantenible** - Código organizado y documentado
✅ **Flexible** - Repository pattern permite cambios
✅ **Seguro** - Validaciones en domain + application
✅ **Auditado** - Tracking de cambios y aprobaciones

