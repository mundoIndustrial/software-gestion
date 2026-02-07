# Arquitectura Final: Frontend Puro + Backend DDD

## 🎯 Resumen Ejecutivo

Se refactoriza **PrendaEditor** desde una clase acoplada y monolítica a una **arquitectura limpia** con:

- ✅ **Frontend**: Solo orquestación y presentación (cero lógica de negocio)
- ✅ **Backend**: DDD (Domain-Driven Design) con toda la lógica centralizada
- ✅ **API**: Simple y clara (GET/POST con respuestas consistentes)
- ✅ **Testeable**: Cada capa independiente
- ✅ **Escalable**: Agregar features sin afectar lo existente

---

## 📊 ANTES vs DESPUÉS

### ❌ ANTES (Arquitectura Acoplada)

```
Frontend (prenda-editor.js)
├── Lógica de negocio (aplicarOrigenAutomatico)
├── Validaciones (validarPrenda)
├── Transformaciones de datos (procesarProcesos)
├── Acceso directo a DOM (50+ getElementById)
├── Llamadas fetch directas
└── Dependencias globales (window.*)
```

**Problemas:**
- Imposible testear sin DOM
- Lógica duplicada si existen mobile/API
- Cambios en reglas de negocio requieren cambios en frontend
- Difícil mantener
- Inseguro (validación ignorable desde devtools)

---

### ✅ DESPUÉS (Arquitectura Limpia)

```
USER INTERFACE
    ↓
FRONTEND (PrendaEditorOrchestrator)
├── Recibir input del usuario
├── Llamar Backend (API)
├── Presentar respuesta
├── Emitir eventos
└── SIN lógica de negocio
    ↓
API REST (HTTP JSON)
    ↓
BACKEND DDD (PHP/Laravel)
├── Application Services (Orquestación)
│   └── ObtenerPrendaParaEdicionApplicationService
│   └── GuardarPrendaApplicationService
│
├── Domain Services (Lógica de negocio)
│   └── AplicarOrigenAutomaticoDomainService
│   └── ValidarPrendaDomainService
│   └── NormalizarDatosPrendaDomainService
│
├── Domain Models (Entities + Value Objects)
│   └── Prenda (Aggregate Root)
│   └── Origen, Genero, etc (Value Objects)
│
├── Repositories (Persistencia)
│   └── PrendaRepository
│
└── Events (Domain Events)
    └── PrendaCreada, PrendaGuardada, etc
```

**Beneficios:**
- Testeable sin dependencias externas
- Lógica reutilizable (API, Mobile, CLI, etc)
- Cambios centralizados
- Fácil mantener
- Seguro (validación en servidor)

---

## 📁 Estructura de Archivos

### Frontend - Ya Refactorizado

```
public/js/modulos/crear-pedido/procesos/services/
├── prenda-event-bus.js                    ✓ Comunicación desacoplada
├── prenda-api.js                          ✓ Abstracción HTTP (endpoints simples)
├── prenda-dom-adapter.js                  ✓ Acceso al DOM
├── prenda-editor-orchestrator.js          ✓ NUEVO - Solo orquestación puro
├── prenda-editor-refactorizado.js         ⚠️  ANTIGUO - Tenía lógica negocio (DESCONTINUAR)
├── prenda-editor-service.js               ⚠️  ANTIGUO - Lógica de negocio (MOVER BACKEND)
├── ARQUITECTURA_CORRECTA_FRONTEND_BACKEND.md
├── MIGRACION_REFACTORIZACION.md
└── BACKEND_DDD_SPECIFICATION.md
```

### Backend - A Implementar

```
app/
├── Domain/
│   └── Prenda/
│       ├── ValueObjects/
│       │   ├── PrendaId.php
│       │   ├── Origen.php
│       │   ├── Genero.php
│       │   └── ...
│       │
│       ├── Entities/
│       │   ├── Prenda.php (Aggregate Root)
│       │   ├── Tela.php
│       │   ├── Variacion.php
│       │   └── ...
│       │
│       ├── Services/
│       │   ├── AplicarOrigenAutomaticoDomainService.php
│       │   ├── ValidarPrendaDomainService.php
│       │   ├── NormalizarDatosPrendaDomainService.php
│       │   └── ...
│       │
│       ├── Repositories/
│       │   └── PrendaRepositoryInterface.php
│       │
│       └── Events/
│           ├── PrendaCreada.php
│           ├── PrendaGuardada.php
│           └── ...
│
├── Application/
│   ├── Services/
│   │   ├── ObtenerPrendaParaEdicionApplicationService.php
│   │   ├── GuardarPrendaApplicationService.php
│   │   └── ...
│   │
│   └── DTOs/
│       ├── ObtenerPrendaResponse.php
│       ├── GuardarPrendaResponse.php
│       └── ...
│
├── Infrastructure/
│   ├── Controllers/
│   │   └── PrendaController.php
│   │
│   ├── Persistence/
│   │   └── EloquentPrendaRepository.php
│   │
│   └── Routes/
│       └── api.php
│
└── ...
```

---

## 🔄 Flujos Principales

### 1. Cargar Prenda para Edición

```
Frontend: Usuario abre prenda
    ↓
Frontend (Orchestrator):
  cargarPrendaEnModal(prendaId)
    ↓
    1. Mostrar loading
    2. await api.obtenerPrendaParaEdicion(prendaId)
    3. Si error → mostrarNotificacion
    4. Si ok → llenarFormulario(prenda)
    5. Emitir evento PRENDA_CARGADA
    ↓
Backend (DDD):
  GET /api/prendas/{id}
    ↓
    1. PrendaController.show()
    2. ObtenerPrendaParaEdicionApplicationService.ejecutar()
    3. PrendaRepository.porId()
    4. Cargar telas, procesos, variaciones, tallas
    5. NormalizarDatosPrendaDomainService.normalizar()
    6. Retornar DTO
    ↓
Response: {
  "exito": true,
  "datos": {
    "nombre_prenda": "...",
    "origen": "bodega",
    "telasAgregadas": [...],  // YA PROCESADAS
    "procesosSeleccionados": {...},  // YA NORMALIZADOS
    "variacionesActuales": {...},
    "tallasRelacionales": {...}
  }
}
    ↓
Frontend: domAdapter.llenarFormulario(datos)
    ↓
Usuario ve formulario listo ✓
```

### 2. Guardar Prenda

```
Frontend: Usuario hace click en "Guardar"
    ↓
Frontend (Orchestrator):
  guardarPrenda(datosFormulario)
    ↓
    1. Validación básica (nombre no vacío, etc) - SOLO UI
    2. Mostrar loading
    3. await api.guardarPrenda(datos)
    4. Si error → mostrar errores
    5. Si ok → resetearFormulario()
    ↓
Backend (DDD):
  POST /api/prendas
  Input: { nombre_prenda, origen, telas, procesos, ... }
    ↓
    1. PrendaController.store()
    2. GuardarPrendaApplicationService.ejecutar()
    3. ValidarPrendaDomainService.validar()
       → Si errores retornar con ellos
    4. AplicarOrigenAutomaticoDomainService.ejecutar()
    5. PrendaRepository.guardar()
    6. Publicar domain events
    ↓
Response: {
  "exito": true,
  "mensaje": "Prenda guardada correctamente",
  "prendaId": 123
}
    ↓
    O si hay errores:
Response: {
  "exito": false,
  "errores": [
    "El nombre es obligatorio",
    "Debe agregar al menos una tela"
  ]
}
    ↓
Frontend: mostrar errores o éxito
    ↓
Usuario ve confirmación ✓
```

---

## 🛠️ Endpoints Principal que el Frontend Llama

### GET /api/prendas/{id}
**Frontend:**
```javascript
const prenda = await api.obtenerPrendaParaEdicion(id);
```

**Backend retorna:**
```json
{
  "nombre_prenda": "Camisa Corporativa",
  "descripcion": "...",
  "origen": "bodega",
  "de_bodega": 1,
  "telasAgregadas": [
    {
      "nombre_tela": "Algodón",
      "color": "Azul",
      "referencia": "ALG-001",
      "fotos": ["url1", "url2"]
    }
  ],
  "variacionesActuales": {
    "genero_id": 1,
    "tipo_manga": "corta",
    "obs_manga": "..."
  },
  "procesosSeleccionados": {
    "bordado": {
      "datos": {
        "nombre": "Bordado",
        "ubicaciones": [...]
      }
    }
  },
  "tallasRelacionales": {
    "DAMA": { "S": 10, "M": 20 },
    "CABALLERO": {}
  }
}
```

### POST /api/prendas
**Frontend:**
```javascript
const resultado = await api.guardarPrenda(datos);
```

**Input:**
```json
{
  "nombre_prenda": "Prenda Nueva",
  "descripcion": "...",
  "origen": "bodega",
  "telasAgregadas": [...],
  "procesosSeleccionados": {...},
  "variacionesActuales": {...},
  "tallasRelacionales": {...}
}
```

**Backend retorna (exito):**
```json
{
  "exito": true,
  "mensaje": "Prenda guardada",
  "prendaId": 456
}
```

**Backend retorna (error):**
```json
{
  "exito": false,
  "errores": [
    "El origen debe ser bodega para telas Reflectivo",
    "Debe agregar procesos para prendas de bodega"
  ]
}
```

---

## ✅ Checklist Migración

### Fase 1: Frontend (YA HECHO ✓)
- [x] Crear PrendaEventBus
- [x] Crear PrendaDOMAdapter
- [x] Crear PrendaAPI (endpoints correctos)
- [x] Crear PrendaEditorOrchestrator (sin lógica negocio)

### Fase 2: Backend DDD (A HACER)
- [ ] Crear Value Objects (Origen, Genero, etc)
- [ ] Crear Prenda Aggregate Root
- [ ] Crear Domain Services:
  - [ ] AplicarOrigenAutomaticoDomainService
  - [ ] ValidarPrendaDomainService
  - [ ] NormalizarDatosPrendaDomainService
- [ ] Crear Application Services:
  - [ ] ObtenerPrendaParaEdicionApplicationService
  - [ ] GuardarPrendaApplicationService
- [ ] Crear PrendaRepository (Eloquent)
- [ ] Crear endpoints en Controller
- [ ] Actualizar rutas API
- [ ] Migrar datos si aplica
- [ ] Tests unitarios para Domain Services
- [ ] Tests de integración para Application Services

### Fase 3: Migración en Vivo
- [ ] Desplegar Backend DDD
- [ ] Desplegar Frontend (PrendaEditorOrchestrator)
- [ ] Probar flujos principales
- [ ] Remover PrendaEditorService (lógica vieja)
- [ ] Documentar para equipo

---

## 🎓 Key Learnings

### Separación de Responsabilidades
```
Frontend:
  - Orquestar flujos
  - Presentar datos
  - Capturar input usuario
  - Emitir eventos

Backend:
  - Validar datos
  - Aplicar reglas negocio
  - Transformar datos
  - Persistir en BD
```

### DDD Beneficios
- **Value Objects**: Tipos primitivos con lógica (ej: Origen)
- **Aggregates**: Prenda como raíz agregada
- **Domain Services**: Lógica de negocio pura (testeable)
- **Application Services**: Orquestación y coordinación
- **Domain Events**: Registro de lo importante que pasó
- **Repositories**: Abstracción de persistencia

### Testing
```python
# Frontend
test("cargarPrendaEnModal", async () => {
  const api = new MockPrendaAPI();
  const orchestrator = new PrendaEditorOrchestrator({ api });
  await orchestrator.cargarPrendaEnModal(1);
  expect(domAdapter.obtenerNombrePrenda()).toBe("...");
});

# Backend
test("AplicarOrigenAutomatico", () => {
  const service = new AplicarOrigenAutomaticoDomainService();
  const tipo = new TipoCotizacion('Reflectivo');
  const origen = service.ejecutar($prenda, $tipo);
  expect($origen->esBodega()).toBe(true);
});
```

---

## 📚 Documentación Relacionada

1. **ARQUITECTURA_CORRECTA_FRONTEND_BACKEND.md** - Análisis de separación
2. **BACKEND_DDD_SPECIFICATION.md** - Especificación completa DDD
3. **MIGRACION_REFACTORIZACION.md** - Guía original de cambios

---

## 🚀 Próximos Pasos

1. **Revisar** especificación Backend con equipo
2. **Estimar** tiempo de implementación DDD
3. **Crear** rama para desarrollo Backend
4. **Implementar** Value Objects y Entities de Prenda
5. **Implementar** Domain Services con validaciones
6. **Crear** Application Services
7. **Tests** de Domain Services
8. **Integración** con frontend
9. **Deployment** escalonado

---

**Creado**: Febrero 7, 2026  
**Arquitectura**: Frontend Puro + Backend DDD  
**Estado**: Especificación Completa, Fase 2 Pendiente
