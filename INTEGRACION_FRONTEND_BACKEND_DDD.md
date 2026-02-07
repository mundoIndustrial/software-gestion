# Flujo de Integración Frontend-Backend DDD (Completado)

## Arquitectura Implementada

```
┌─────────────────────────────────────────────────────────────────────┐
│                          USUARIO (NAVEGADOR)                        │
└────┬────────────────────────────────────────────────────────────────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────────────┐
│             FRONTEND PURO (public/js)                               │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ PrendaEditorOrchestrator - ORQUESTACIÓN PURA                │  │
│  │  ✓ NO contiene lógica de negocio                            │  │
│  │  ✓ NO valida reglas                                         │  │
│  │  ✓ SOLO coordina UI ↔ API                                   │  │
│  └──────────────────────────────────────────────────────────────┘  │
│              ↓                              ↓                       │
│  ┌────────────────────────────┐  ┌─────────────────────────────┐  │
│  │   PrendaEventBus           │  │   PrendaDOMAdapter          │  │
│  │   (Pub/Sub eventos)        │  │   (Acceso a DOM)            │  │
│  └────────────────────────────┘  └─────────────────────────────┘  │
│              ↓                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ PrendaAPI - HTTP Abstraction Layer                           │  │
│  │  • GET /api/prendas/{id}                                    │  │
│  │  • POST /api/prendas                                        │  │
│  │  • PUT /api/prendas/{id}                                    │  │
│  │  • DELETE /api/prendas/{id}                                 │  │
│  └──────────────────────────────────────────────────────────────┘  │
└─────────────────────────────┬──────────────────────────────────────┘
                              │ HTTP JSON
                              ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      BACKEND DDD (PHP/Laravel)                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ App\Infrastructure\Http\Controllers\API\PrendaController     │  │
│  │  • show(id) → GET /api/prendas/{id}                         │  │
│  │  • store(Request) → POST /api/prendas                       │  │
│  │  • update(id, Request) → PUT /api/prendas/{id}              │  │
│  │  • destroy(id) → DELETE /api/prendas/{id}                   │  │
│  │  • index() → GET /api/prendas                               │  │
│  │  • search(Request) → GET /api/prendas/search                │  │
│  └──────────────────────────────────────────────────────────────┘  │
│              ↓                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ Application Services                                          │  │
│  │  • ObtenerPrendaParaEdicionApplicationService               │  │
│  │    - Lee de BD                                              │  │
│  │    - Normaliza para frontend                               │  │
│  │                                                             │  │
│  │  • GuardarPrendaApplicationService                          │  │
│  │    - Valida datos básicos                                  │  │
│  │    - Orquesta Domain Services                              │  │
│  │    - Persiste resultado                                    │  │
│  └──────────────────────────────────────────────────────────────┘  │
│              ↓                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ Domain Services (LÓGICA DE NEGOCIO PURA)                     │  │
│  │  ┌────────────────────────────────────────────────────────┐ │  │
│  │  │ AplicarOrigenAutomaticoDomainService                   │ │  │
│  │  │ ► Aplica regla: REFLECTIVO/LOGO → BODEGA              │ │  │
│  │  │ ► Única implementación en el SISTEMA                  │ │  │
│  │  └────────────────────────────────────────────────────────┘ │  │
│  │  ┌────────────────────────────────────────────────────────┐ │  │
│  │  │ ValidarPrendaDomainService                             │ │  │
│  │  │ • Telas ≥ 1                                            │ │  │
│  │  │ • BODEGA con variaciones ≥ 1                           │ │  │
│  │  │ • Procesos válidos                                     │ │  │
│  │  │ • Genero válido (1,2,3)                                │ │  │
│  │  └────────────────────────────────────────────────────────┘ │  │
│  │  ┌────────────────────────────────────────────────────────┐ │  │
│  │  │ NormalizarDatosPrendaDomainService                     │ │  │
│  │  │ • Convierte a formato frontend                         │ │  │
│  │  │ • Convierte a formato BD                               │ │  │
│  │  │ • Formatea errores y respuestas                        │ │  │
│  │  └────────────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────────┘  │
│              ↓                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ Domain Layer - VALUE OBJECTS (Type-Safe Primitives)          │  │
│  │  • PrendaId          - ID único de prenda                    │  │
│  │  • TipoCotizacion    - REFLECTIVO | LOGO | BORDADO | PRENDA  │  │
│  │  • Origen            - BODEGA | COSTURA                      │  │
│  │  • Genero            - DAMA(1) | CABALLERO(2) | UNISEX(3)    │  │
│  │  • PrendaNombre      - Validado (3-255 chars)                │  │
│  │  • Telas / Tela      - Colección validada (min 1)            │  │
│  │  • Procesos / Proceso - Colección de procesos válidos        │  │
│  │  • Variaciones / Variacion - Colección sin duplicados        │  │
│  └──────────────────────────────────────────────────────────────┘  │
│              ↓                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ Domain Entity - Prenda (Aggregate Root)                      │  │
│  │  Mantiene INVARIANTES:                                       │  │
│  │  • Siempre tiene valid TipoCotizacion                        │  │
│  │  • Si BODEGA → SIEMPRE tiene variaciones ≥ 1                │  │
│  │  • Si COSTURA → puede no tener variaciones                  │  │
│  │  • Origen se aplica automáticamente según TipoCotizacion    │  │
│  └──────────────────────────────────────────────────────────────┘  │
│              ↓                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ Repository Pattern                                           │  │
│  │  ┌─────────────────────────────────────────────────────────┐ │  │
│  │  │ PrendaRepositoryInterface (App\Domain)                 │ │  │
│  │  │  • Defines contract: guardar(), porId(), todas(), etc   │ │  │
│  │  └─────────────────────────────────────────────────────────┘ │  │
│  │  ┌─────────────────────────────────────────────────────────┐ │  │
│  │  │ EloquentPrendaRepository (App\Infrastructure)          │ │  │
│  │  │  • Implements interface with Eloquent ORM              │ │  │
│  │  │  • Maps Eloquent Model ↔ Domain Entity                 │ │  │
│  │  └─────────────────────────────────────────────────────────┘ │  │
│  └──────────────────────────────────────────────────────────────┘  │
│              ↓                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ Database Layer (Eloquent Model)                              │  │
│  │  • Prenda (table: prendas)                                  │  │
│  │  • Relations: telas(), procesos(), variaciones()            │  │
│  └──────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

## Flujos de Datos Implementados

### 1. CREAR PRENDA (POST /api/prendas)

```javascript
// Frontend - gestion-items-pedido.js
const formData = {
    nombre: 'Polo Reflectivo',
    tipo_cotizacion: 'REFLECTIVO',  // ← User selects
    genero: 1,
    telas: [{ id: 1, nombre: 'Algodón', codigo: 'ALG' }],
    procesos: ['ESTAMPADO'],
    variaciones: [{ talla: 'M', color: 'NEGRO' }]
};

// Call PrendaEditorOrchestrator.guardarPrenda()
await this.prendaEditor.guardarPrenda(formData);
```

**Backend Execution Flow:**
```
1. PrendaController::store(Request)
   └─> GuardarPrendaApplicationService::ejecutar(datos)
       ├─ Validar datos básicos (nombre length, etc)
       ├─ Crear Prenda Entity from DTO
       ├─ AplicarOrigenAutomaticoDomainService::aplicar()
       │  └─> Si tipo_cotizacion = REFLECTIVO
       │      └─> Origin automáticamente = BODEGA ✓ CORE RULE
       ├─ ValidarPrendaDomainService::validar()
       │  └─> Verificar BODEGA tiene ≥1 variación ✓
       ├─ Repository::guardar(prendaEntity)
       │  └─> EloquentPrendaRepository mapea Entity→Model
       │      └─> Guardar en prendas table
       └─> NormalizarDatosPrendaDomainService::normalizarParaFrontend()
           └─> Retornar respuesta formateada JSON
```

**Response (200 Created):**
```json
{
  "exito": true,
  "datos": {
    "id": 123,
    "nombre": "Polo Reflectivo",
    "tipo_cotizacion": "REFLECTIVO",
    "origen": "BODEGA",  ← AUTO-APPLIED
    "genero": 1,
    "telas": [...],
    "procesos": [...],
    "variaciones": [...]
  }
}
```

### 2. CARGAR PRENDA PARA EDITAR (GET /api/prendas/{id})

```javascript
// Frontend - PrendaEditorOrchestrator::cargarPrendaEnModal()
const datosPrenda = await this.api.obtenerPrendaParaEdicion(prendaId);
// datosPrenda ya tiene origen = 'BODEGA' (como fue guardado)
this.llenarFormulario(datosPrenda);
```

**Backend:**
```
PrendaController::show(id)
└─> ObtenerPrendaParaEdicionApplicationService::ejecutar(id)
    ├─ Repository::porId(id)
    │  └─> EloquentPrendaRepository carga con relaciones
    ├─ NormalizarDatosPrendaDomainService::normalizarParaFrontend()
    └─> Retornar JSON normalizado
```

## Testing Validado ✓

**Test: Crear REFLECTIVO aplicando BODEGA automáticamente**
```
PASS  endpoint store existe (21.45s)
✓ Se creó prenda
✓ Backend aplicó automáticamente origen = BODEGA
✓ Validaciones se ejecutaron (BODEGA requiere ≥1 variación)
```

## Clave del Éxito: SINGLE SOURCE OF TRUTH

El rule `Origen::segunTipoCotizacion()` está implementado UNA SOLA VEZ en:
```
App\Domain\Prenda\ValueObjects\Origen.php
```

✓ No se repite en frontend
✓ No se repite en múltiples servicios
✓ Cuando cambia→ cambia en UN lugar
✓ Todos los endpoints obtienen el mismo resultado

## Siguiente Fase

Ahora que el backend DDD está implementado y validado:

1. **Crear UI Modal** - Formulario para ingresar prendas
2. **Conectar validaciones** - Mostrar errores del backend en UI
3. **Implementar búsqueda** - GET /api/prendas?q=nombre
4. **Actualizar listado** - Refrescar después de crear/editar
5. **Eliminar prendas** - DELETE /api/prendas/{id}

Todo con el Backend como FUENTE DE VERDAD para la lógica.
