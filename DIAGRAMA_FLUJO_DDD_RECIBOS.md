```
┌─────────────────────────────────────────────────────────────────┐
│                         FRONTEND (Blade/JS)                     │
│  resources/views/registros/recibos-costura.blade.php             │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                ┌──────────┼──────────┐
                │          │          │
                ▼          ▼          ▼
          GET /          POST /      GET /api/
        registros-     seguimiento-   recibos-
        costura        proceso        costura

┌─────────────────────────────────────────────────────────────────┐
│             INFRASTRUCTURE LAYER (Controllers)                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  RecibosCozturaController              RecibosCozturaApiController
│  (Web - Renderiza Blade)               (API - Retorna JSON)     │
│                                                                   │
│  ├─ index(Request)                     ├─ index(Request)         │
│  │  Renderiza vista con datos          │  Retorna JSON listado   │
│  │                                     │                         │
│  ├─ obtenerDatos()                    ├─ show(id)               │
│  │  Legacy: datos del pedido            │  Recibo individual     │
│  │                                     │                         │
│  └─ obtenerConsecutivoCostura()       ├─ obtenerOpciones()      │
│     Legacy: datos de seguimiento      │  Opciones dinámicas     │
│                                        │                         │
│                                        ├─ buscar()               │
│                                        │  Búsqueda en tiempo real│
│                                        │                         │
│                                        ├─ agregarProceso()       │
│                                        │  POST proceso            │
│                                        │                         │
│                                        └─ obtenerEncargados()    │
│                                           Datos auxiliares       │
└──────────────────────┬──────────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────────┐
│           APPLICATION LAYER (Services)                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  RecibosCozturaApplicationService      ProcesosRecibosService   │
│  (Orquestación de dominios)            (Gestión de procesos)    │
│                                                                   │
│  ├─ obtenerRecibos(filtros)            ├─ validar(datos)        │
│  │  ├─ Aplica filtros                  │                        │
│  │  ├─ Enriquece datos                 ├─ guardarProceso()      │
│  │  ├─ Pagina resultados               │  ├─ Create o Update    │
│  │  └─ Retorna estructura completa     │  └─ Auditoría          │
│  │                                     │                        │
│  ├─ obtenerRecibo(id)                  ├─ obtenerProcesos()     │
│  │  └─ Enriquece recibo individual     │                        │
│  │                                     ├─ obtenerEncargados()   │
│  ├─ obtenerOpcionesFilttro()           │                        │
│  │  └─ Retorna opciones dinámicas      ├─ obtenerAreas()        │
│  │                                     │                        │
│  ├─ buscar(termino)                    └─ marcarCompletado()    │
│  │  └─ Búsqueda full-text             │                        │
│  │                                    │                        │
│  └─ validar(id)                       │                        │
│     └─ Valida reglas de negocio       │                        │
│                          ▲            │                        │
│                          │            │                        │
│      ┌───────────────────┴─────┬──────┴────────┐               │
│      │                         │               │               │
└──────┼─────────────────────────┼───────────────┼───────────────┘
       │                         │               │
       ▼                         ▼               ▼
┌──────────────────────┬──────────────────┬──────────────────────┐
│   DOMAIN LAYER       │  DOMAIN LAYER    │  SERVICES COMUNES    │
├──────────────────────┼──────────────────┼──────────────────────┤
│                      │                  │                      │
│ RecibosCozturaService │ FiltrosRecibos  │ CalculadorDiasService│
│ (Lógica de negocio)  │ Service          │ (Cálculos)           │
│                      │ (Validación      │                      │
│ ├─ calcularDias()    │ y queries)       │ ├─ obtenerFestivos() │
│ │                    │                  │ │                    │
│ ├─ validar()         │ ├─ validar()     │ ├─ calcularDias      │
│ │                    │ │                │ │  Habiles()         │
│ ├─ enriquecer()      │ ├─ aplicar()     │ │                    │
│ │                    │ │  (Builder)     │ └─ esDiaFestivo()    │
│ ├─ obtenerCantidad() │ │                │                      │
│ │                    │ └─ obtenerOpciones()                    │
│ └─ esCritico()       │                  │                      │
│                      │                  │                      │
└──────────────┬───────┴──────────────────┴──────────────────────┘
               │
               │ Queries & Mutations
               ▼
┌─────────────────────────────────────────────────────────────────┐
│                    MODELS & DATABASE                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ReciboCostura                  Proceso                          │
│  (Tabla: recibos_costura)       (Tabla: procesos)               │
│                                                                   │
│  ├─ id (PK)                     ├─ id (PK)                     │
│  ├─ numero_recibo               ├─ recibo_id (FK)              │
│  ├─ orden_id (FK)               ├─ area                        │
│  ├─ estado                       ├─ encargado                   │
│  ├─ area (nullable)              ├─ estado                      │
│  ├─ cantidad                     ├─ usuario_id                 │
│  ├─ novedades                    ├─ ip_address                 │
│  ├─ created_at                   ├─ completed_at               │
│  └─ updated_at                   ├─ created_at                 │
│                                  └─ updated_at                 │
│  Relationships:                                                 │
│  ├─ belongsTo(PedidoProduccion)  Relationships:                │
│  ├─ hasMany(Proceso)             └─ belongsTo(ReciboCostura)   │
│  └─ hasMany(Novedades)                                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

═══════════════════════════════════════════════════════════════════

                        FLUJO DE DATOS EJEMPLOS

═══════════════════════════════════════════════════════════════════

CASO 1: Obtener lista de recibos filtrados
────────────────────────────────────────────

User Action:
    ├─ Selecciona filtros
    └─ Hace clic en "Aplicar"
    
Frontend (JavaScript):
    └─ fetch('/api/recibos-costura?estados=RECIBIDO&areas=COSTURA')
       └─ PayLoad: QueryString
    
RecibosCozturaApiController:
    └─ index(Request $request)
       → Obtiene parámetros
       → Delega a RecibosCozturaApplicationService
    
RecibosCozturaApplicationService:
    └─ obtenerRecibos($filtros)
       ├─ Valida filtros (FiltrosRecibosService::validar)
       ├─ Construye query (FiltrosRecibosService::aplicar)
       │  └─ ReciboCostura::with('orden.prendas')
       │     └─ whereIn('estado', ['RECIBIDO'])
       │     └─ whereIn('area', ['COSTURA'])
       ├─ Enriquece cada recibo
       │  └─ RecibosCozturaService::enriquecer()
       │     ├─ calcularDiasHabiles()
       │     ├─ validar()
       │     └─ formatear datos
       ├─ Pagina (per_page=50)
       └─ Retorna estructura

Response:
{
    "success": true,
    "data": [
        {
            "id": 1,
            "numero_recibo": "REC-001",
            "estado": "RECIBIDO",
            "total_dias": 5,
            "descripcion": "Camiseta Azul",
            ...
        },
        ...
    ],
    "pagination": {...},
    "totalCantidadGlobal": 5420
}

Frontend:
    └─ actualizar tabla con datos


CASO 2: Agregar proceso a recibo
─────────────────────────────────

User Action:
    ├─ Hace clic en badge de área
    ├─ Selecciona encargado
    └─ Clica "Guardar"

Frontend (JavaScript):
    └─ fetch('/api/recibos-costura/1/procesos', {
           method: 'POST',
           body: {area: 'COSTURA', encargado: 'Juan'}
       })

RecibosCozturaApiController:
    └─ agregarProceso(int $reciboId, Request $request)
       ├─ Valida input
       └─ Delega a ProcesosRecibosService

ProcesosRecibosService:
    └─ guardarProceso($reciboId, $datos)
       ├─ Valida reglas de negocio
       │  ├─ Área existe
       │  └─ Encargado requerido para COSTURA
       ├─ Busca proceso existente
       ├─ Si existe: UPDATE
       │  └─ Proceso::update([$datos])
       └─ Si no existe: CREATE
          └─ Proceso::create({
                 recibo_id: $reciboId,
                 area: 'COSTURA',
                 encargado: 'Juan',
                 usuario_id: auth()->id(),  // Auditoría
                 ip_address: request()->ip() // Auditoría
             })

Response:
{
    "success": true,
    "action": "creado",
    "data": {...},
    "message": "Proceso COSTURA agregado correctamente"
}

Frontend:
    └─ showSuccess()
    └─ Reload tabla


═══════════════════════════════════════════════════════════════════
```