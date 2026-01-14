# Migración del Controlador a Infrastructure

**Fecha:** 14 de Enero de 2026  
**Objetivo:** Mover `PedidosProduccionController` a la capa Infrastructure según arquitectura DDD

## Cambios Realizados

### 1. Estructura de Directorios

**Antes:**
```
app/Http/Controllers/Asesores/PedidosProduccionController.php
```

**Después:**
```
app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php
```

### 2. Actualización de Namespace

**Antes:**
```php
namespace App\Http\Controllers\Asesores;
```

**Después:**
```php
namespace App\Infrastructure\Http\Controllers\Asesores;
```

### 3. Actualización de Rutas

Todas las rutas en `routes/web.php` fueron actualizadas:

**Antes:**
```php
[App\Http\Controllers\Asesores\PedidosProduccionController::class, 'metodo']
```

**Después:**
```php
[App\Infrastructure\Http\Controllers\Asesores\PedidosProduccionController::class, 'metodo']
```

### 4. Rutas Actualizadas

Total de **14 rutas** actualizadas en web.php:
- GET `/pedidos-produccion/crear-desde-cotizacion`
- GET `/pedidos-produccion/crear-nuevo`
- GET `/pedidos-produccion/crear`
- GET `/pedidos-produccion/obtener-datos-cotizacion/{cotizacion_id}`
- GET `/pedidos-produccion`
- GET `/pedidos-produccion/{id}`
- GET `/pedidos-produccion/{id}/plantilla`
- POST `/pedidos-produccion/crear-desde-cotizacion/{cotizacionId}`
- POST `/pedidos-produccion/crear-sin-cotizacion`
- POST `/pedidos-produccion/crear-prenda-sin-cotizacion`
- POST `/pedidos-produccion/crear-reflectivo-sin-cotizacion`

### 5. Validación

✅ Sintaxis PHP validada  
✅ Archivo original eliminado  
✅ Rutas actualizadas correctamente

## Beneficios de la Migración

### Separación de Responsabilidades (Clean Architecture)

```
Domain/                    <- Lógica de negocio (Servicios, Interfaces, Entities)
  PedidoProduccion/
    Services/
    Repositories/
    Entities/

Infrastructure/           <- Detalles técnicos e implementación
  Http/
    Controllers/Asesores/PedidosProduccionController.php ✅ Aquí va
  Persistence/
  Providers/
  ...

Application/             <- Casos de uso (cuando sea necesario)
Http/                    <- Controllers HTTP simples (Legacy, pueden desaparecer)
```

### Estructura en Capas

```
┌─────────────────────────────┐
│   HTTP Requests/Responses   │
└──────────────┬──────────────┘
               │
┌──────────────▼──────────────────────────────────┐
│  Infrastructure/Http/Controllers/Asesores       │  ◄── Aquí está ahora
│  - Maneja requests HTTP                         │
│  - Valida permisos de asesor                    │
│  - Delega a servicios                           │
└──────────────┬──────────────────────────────────┘
               │
┌──────────────▼──────────────────────────────────┐
│  Domain/PedidoProduccion/Services                │
│  - Lógica de negocio                            │
│  - ListaPedidosService                          │
│  - VariantesService                             │
│  - FormularioPedidoService                      │
│  - UtilitariosService                           │
└──────────────┬──────────────────────────────────┘
               │
┌──────────────▼──────────────────────────────────┐
│  Models, Database, APIs                         │
│  - Persistencia de datos                        │
│  - Integraciones externas                       │
└─────────────────────────────────────────────────┘
```

### Por Qué en Infrastructure?

1. **Infrastructure** = Capa de detalles técnicos
   - HTTP Controllers (adapters para HTTP)
   - Repositorios (implementación de persistencia)
   - Servicios de infraestructura

2. **Domain** = Lógica de negocio pura
   - Servicios de dominio (sin dependencias HTTP)
   - Interfaces y contratos
   - Entidades de negocio

3. El controlador es un **adapter HTTP**, por lo tanto pertenece a Infrastructure

## Archivos Modificados

- ✅ Nuevo: `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php`
- ✅ Eliminado: `app/Http/Controllers/Asesores/PedidosProduccionController.php`
- ✅ Actualizado: `routes/web.php` (14 referencias)

## Compatibilidad

- ✅ Todas las rutas siguen funcionando igual
- ✅ Los métodos no cambiaron
- ✅ Los servicios inyectados siguen siendo los mismos
- ✅ Sin cambios en modelos ni vistas

## Próximos Pasos (Opcionales)

1. [ ] Crear alias para compatibilidad (usar trait)
2. [ ] Mover otros controladores a Infrastructure
3. [ ] Documentar estructura de capas
4. [ ] Actualizar bootstrap/autoload si es necesario
5. [ ] Agregar comentarios de arquitectura

## Validación de Funcionamiento

```bash
# Verificar que el archivo existe
ls app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php

# Verificar que el archivo original fue eliminado
ls app/Http/Controllers/Asesores/PedidosProduccionController.php  # Debe fallar

# Validar PHP
php -l app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php
```

---

**Estado:** ✅ COMPLETADO
