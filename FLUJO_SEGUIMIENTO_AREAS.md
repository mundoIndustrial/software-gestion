#  Flujo Completo de Datos: Modal de Seguimiento por Áreas

##  RESUMEN EJECUTIVO

El modal de seguimiento muestra **datos de áreas** (Insumos, Corte, Costura, etc.) con **duraciones calculadas** mediante un flujo orquestado que:

1.  Obtiene procesos de la tabla `proceso_prenda`
2.  Recupera fechas de completado de `prenda_recibo_completado`
3.  Calcula duraciones con CalculadorDiasService (excluye festivos/fines de semana)
4.  Inyecta área virtual "Insumos"
5.  Retorna JSON estructurado al frontend
6.  Frontend renderiza tarjetas de área con duraciones

---

##  PARTE 1: ESTRUCTURA DE DATOS (TABLAS)

### Tabla 1: `consecutivos_recibos_pedidos`
**Ubicación**: [app/Models/ConsecutivosRecibosPedidos.php](app/Models/ConsecutivosRecibosPedidos.php)

Almacena el estado actual de cada recibo por prenda:
```sql
SELECT id, pedido_produccion_id, prenda_id, tipo_recibo, 
       consecutivo_actual, area, estado, created_at
FROM consecutivos_recibos_pedidos;
```

**Campos relevantes**:
- `area` → Área donde está actualmente el proceso (Costura, Corte, etc.)
- `tipo_recibo` → COSTURA, BORDADO, ESTAMPADO, DTF
- `activo` → Boolean indicando si está disponible
- `created_at` → Cuándo se creó (marca entrada al área)

---

### Tabla 2: `proceso_prenda`
**Ubicación**: [app/Models/ProcesoPrenda.php](app/Models/ProcesoPrenda.php)

Historial de TODOS los procesos por los que pasó una prenda:
```sql
SELECT id, numero_pedido, prenda_pedido_id, proceso as area,
       estado_proceso, encargado, created_at, 
       fecha_de_asignacion_encargado, fecha_completado
FROM proceso_prenda
ORDER BY created_at ASC;
```

**Campos relevantes**:
- `proceso` → Nombre del área (Corte, Costura, Bordado, etc.)
- `created_at` → **Fecha cuando LLEGÓ a esa área**
- `fecha_de_asignacion_encargado` → Cuándo se asignó operario (puede ser NULL)
- `fecha_completado` → Fecha cuando salió del área

---

### Tabla 3: `prenda_recibo_completado`
**Ubicación**: [app/Models/PrendaReciboCompletado.php](app/Models/PrendaReciboCompletado.php)

Registra fecha exacta cuando se completó POR ÁREA:
```sql
SELECT id, id_recibo, area, nombre_operario, fecha_completado
FROM prenda_recibo_completado
WHERE id_recibo = ? AND area = ?;
```

**Campos relevantes**:
- `id_recibo` → ID del recibo costura (FK de consecutivos_recibos_pedidos)
- `area` → Área que se completó (Corte, Costura, etc.)
- `fecha_completado` → **Fecha exacta de completado** ← CRÍTICA
- Índice único: (id_recibo, area)

---

##  PARTE 2: FLUJO BACKEND

### 1️⃣ Endpoint de Entrada
```
GET /registros/{numero_pedido}/seguimiento-prenda
├─ Controlador: RegistroOrdenQueryController
├─ Método: getSeguimientoPorPrenda($pedido)
└─ UseCase: GetSeguimientoPorPrendaUseCase
```

**Archivo**: [app/Infrastructure/Http/Controllers/RegistroOrdenQueryController.php](app/Infrastructure/Http/Controllers/RegistroOrdenQueryController.php)

```php
// Línea ~200
public function getSeguimientoPorPrenda($pedido)
{
    $result = $this->useCasesFacade->getConsecutivoCosturaUseCase->execute($pedido);
    return response()->json([
        'success' => true,
        'prendas' => [ ... ]  // Contiene seguimientos_por_area
    ]);
}
```

---

### 2️⃣ Use Case Principal: GetSeguimientoPorPrendaUseCase
**Archivo**: [app/Application/Pedidos/UseCases/RegistroOrden/GetSeguimientoPorPrendaUseCase.php](app/Application/Pedidos/UseCases/RegistroOrden/GetSeguimientoPorPrendaUseCase.php)

Este es el **responsable de toda la orquestación**. Flujo:

```php
public function execute(string $pedido): array
{
    // 1. Obtener pedido
    $pedidoModel = $this->pedidoRepository->obtenerPorIdONumero($pedido);
    
    // 2. Por cada prenda del pedido
    foreach ($prendasDB as $prenda) {
        // 2a. Obtener consecutivos del recibo
        $consecutivos = $this->consecutivosRepository->obtenerTodosPorPrenda(
            $prenda->id, $pedidoId
        );
        
        // 2b. OBTENER Y CALCULAR PROCESOS ← AQUÍ ES DONDE PASA LA MAGIA
        $procesosSeguimiento = $this->obtenerYCalcularProcesos(
            $pedidoModel->numero_pedido,
            $prenda->id,
            $numeroReciboCostura,
            $reciboCosturaId
        );
        
        // 2c. Agrupar por área
        $seguimientosPorArea = $this->agruparProcesosPorArea(
            $procesosSeguimiento
        );
        
        // 2d. Inyectar área virtual "Insumos"
        $seguimientosPorArea = $this->inyectarAreaInsumos(
            $seguimientosPorArea,
            $consecutivos
        );
        
        // 2e. Calcular datos de activación
        $datosActivacion = $this->calcularDatosActivacionRecibo(
            $consecutivos,
            $pedidoModel
        );
    }
    
    return [
        'success' => true,
        'prendas' => [ ... ]
    ];
}
```

---

### 3️⃣ Función Crítica: obtenerYCalcularProcesos()
**Líneas**: ~213-265

```php
private function obtenerYCalcularProcesos(...)
{
    // 1. Query: Obtener todos los procesos de esta prenda
    $procesos = ProcesoPrenda::where('numero_pedido', $numeroPedido)
        ->where('prenda_pedido_id', $prendaId)
        ->orderBy('created_at', 'asc')
        ->get();
    
    // 2. Query: Obtener fechas de completado por área
    $completadosPorArea = $this->consecutivosRepository
        ->obtenerFechasCompletadoPorArea($reciboCosturaId);
    //   Retorna: ['corte' => '2026-03-08', 'costura' => '2026-03-15', ...]
    
    // 3. Por cada proceso, CALCULAR DURACIONES
    foreach ($procesos as $index => $proceso) {
        $siguienteProceso = $procesos->get($index + 1);
        
        $fechaCompletado = $completadosPorArea[
            strtolower(trim((string) $proceso->proceso))
        ] ?? null;
        
        // ✨ AQUÍ SE CALCULAN LAS DURACIONES
        $clone->duraciones = $this->calcularDuracionesArea(
            $proceso->created_at,                    // Fecha llegada
            $proceso->fecha_de_asignacion_encargado,  // Fecha asignación
            $fechaCompletado,                        // Fecha completado
            $siguienteProceso ? $siguienteProceso->created_at : null
        );
    }
    
    return $procesosCalculados;
}
```

---

### 4️⃣ Función Crítica: calcularDuracionesArea()
**Líneas**: ~311-345

```php
private function calcularDuracionesArea(
    $fechaInicio,           // Cuándo LLEGÓ a la área
    $fechaAsignacion,       // Cuándo se ASIGNÓ operario (puede ser NULL)
    $fechaCompletado,       // Cuándo se COMPLETÓ (desde prenda_recibo_completado)
    $fechaFin               // Cuándo pasó a siguiente área
): array {
    // Duración de ASIGNACIÓN (desde llegada hasta asignación)
    $duracionAsignacion = null;
    if ($fechaInicio && $fechaAsignacion) {
        $duracionAsignacion = CalculadorDiasService::calcularDiasHabiles(
            $fechaInicio,
            $fechaAsignacion
        );
    }
    
    // Duración EN ÁREA (desde asignación[o llegada] hasta completado[o fin])
    $duracionEnArea = null;
    if ($fechaInicio) {
        $inicioCalculo = $fechaAsignacion ?: $fechaInicio;  // Asignación o llegada
        $finCalculo = $fechaCompletado ?: $fechaFin ?: now();  // Completado o fin
        
        $duracionEnArea = CalculadorDiasService::calcularDiasHabiles(
            $inicioCalculo,
            $finCalculo
        );
    }
    
    // Total de DÍAS en el área
    $totalDias = null;
    if ($fechaInicio) {
        $finCalculo = $fechaCompletado ?: $fechaFin ?: now();
        
        $totalDias = CalculadorDiasService::calcularDiasHabiles(
            $fechaInicio,
            $finCalculo
        );
    }
    
    return [
        'duracion_asignacion' => $duracionAsignacion,
        'duracion_en_area_dias' => $duracionEnArea,
        'total_dias_numero' => $totalDias,
        'estado_display' => !empty($fechaCompletado) ? 'Completado' : 'Pendiente',
        'esta_activo_display' => empty($fechaCompletado),
    ];
}
```

---

### 5️⃣ Cálculo de Días Hábiles: CalculadorDiasService
**Archivo**: [app/Services/CalculadorDiasService.php](app/Services/CalculadorDiasService.php)

```php
public static function calcularDiasHabiles($fechaInicio, $fechaFin)
{
    // Excluir: Sábados (6), Domingos (0), Festivos fijos
    $festivos = self::obtenerFestivos($inicio->year);
    // → Año Nuevo (1/1), Día Trabajo (5/1), Día Independencia (7/1),
    //   Grito Independencia (7/20), Batalla Boyacá (8/7),
    //   Inmaculada (12/8), Navidad (12/25)
    
    // Iterar fecha a fecha
    while ($actual <= $fin) {
        if ($actual->dayOfWeek !== 0 && $actual->dayOfWeek !== 6 
            && !in_array($actual->toDateString(), $festivos)
        ) {
            $diasHabiles++;  // Contar solo días hábiles
        }
        $actual->addDay();
    }
    
    // Restar 1 porque no se cuenta el día de inicio
    return max(0, $diasHabiles - 1);
}
```

**Resultado**: `int` (número de días hábiles entre dos fechas)

---

### 6️⃣ Inyección de Área Virtual "Insumos"
**Líneas**: ~354-415

Si la prenda tiene un recibo COSTURA pero NO hay proceso en `proceso_prenda` para "Insumos", se crea virtualmente:

```php
private function inyectarAreaInsumos(...)
{
    // Si ya existe "Insumos" → no inyectar
    if (in_array('Insumos', array_keys($seguimientosPorArea))) {
        return $seguimientosPorArea;
    }
    
    // Encontrar fecha de llegada a Costura (se considera salida de Insumos)
    $fechaEnvioProduccion = $seguimientosPorArea['Corte']['fecha_inicio'] 
        ?? $seguimientosPorArea['Costura']['fecha_inicio'] ?? null;
    
    // Insumos siempre comienza cuando se crea el recibo COSTURA
    $insumosArea = [
        'id' => null,
        'area' => 'Insumos',
        'estado' => $yaEnviado ? 'Enviado a producción' : 'Llegó a insumos',
        'encargado' => '-',
        'fecha_inicio' => $reciboCostura->created_at,
        'fecha_fin' => $fechaEnvioProduccion,
        'duraciones' => $this->calcularDuracionesArea(
            $reciboCostura->created_at,
            null,  // Insumos no tiene asignación de operario
            $fechaEnvioProduccion,  // Se completa cuando sale a producción
            $fechaEnvioProduccion
        ),
    ];
    
    return ['Insumos' => $insumosArea] + $seguimientosPorArea;
}
```

---

### 7️⃣ Respuesta JSON Retornada

```json
{
  "success": true,
  "prendas": [
    {
      "id": 123,
      "nombre_prenda": "Polo M",
      "seguimientos_por_area": {
        "Insumos": {
          "id": null,
          "area": "Insumos",
          "estado": "Legó a insumos",
          "encargado": "-",
          "fecha_inicio": "2026-03-01T10:00:00Z",
          "fecha_fin": "2026-03-05T15:00:00Z",
          "duraciones": {
            "duracion_asignacion": null,
            "duracion_en_area_dias": 2,
            "total_dias_numero": 4,
            "estado_display": "Completado",
            "esta_activo_display": false
          }
        },
        "Corte": {
          "id": 1,
          "area": "Corte",
          "estado": "Completado",
          "encargado": 5,
          "encargado_nombre": "Juan Pérez",
          "fecha_inicio": "2026-03-05T15:30:00Z",
          "fecha_de_asignacion_encargado": "2026-03-05T16:00:00Z",
          "fecha_fin": "2026-03-08T12:00:00Z",
          "fecha_completado": "2026-03-08T12:00:00Z",
          "duraciones": {
            "duracion_asignacion": 1,      // 1 día entre inicio y asignación
            "duracion_en_area_dias": 2,    // 2 días entre asignación y completado
            "total_dias_numero": 3,        // 3 días totales desde llegada
            "estado_display": "Completado",
            "esta_activo_display": false
          }
        },
        "Costura": {
          "id": 2,
          "area": "Costura",
          "estado": "En Progreso",
          "encargado": 8,
          "encargado_nombre": "María López",
          "fecha_inicio": "2026-03-08T14:00:00Z",
          "fecha_de_asignacion_encargado": "2026-03-08T16:00:00Z",
          "fecha_fin": null,
          "fecha_completado": null,
          "duraciones": {
            "duracion_asignacion": 0,
            "duracion_en_area_dias": 3,    // Desde asignación hasta hoy
            "total_dias_numero": 3,        // Desde llegada hasta hoy
            "estado_display": "Pendiente",
            "esta_activo_display": true    // Todavía está en proceso
          }
        }
      },
      "datos_activacion": {
        "dias_transcurridos": 5,
        "fecha_activacion": "2026-03-01T10:00:00Z"
      }
    }
  ]
}
```

---

##  PARTE 3: FLUJO FRONTEND

### Controlador: TrackingTimelineController
**Archivo**: [public/js/ordersjs/presentation/TrackingTimelineController.js](public/js/ordersjs/presentation/TrackingTimelineController.js)

#### Método 1: showPrendaTracking(prenda)
**Líneas**: ~28-65

```javascript
async showPrendaTracking(prenda) {
    // 1. Hidratar datos
    prenda = this.#hydratePrenda(prenda);
    
    // 2. Guardar en estado
    this.orderState.setCurrentPrenda(prenda);
    
    // 3. Mostrar modal
    this.updateRenderer.toggleModal('orderTrackingModal', true);
    
    // 4. Renderizar timeline
    this.renderPrendaTrackingTimeline(prenda);
}
```

#### Método 2: renderSeguimientosPorArea(prenda, container)
**Líneas**: ~104-170

```javascript
renderSeguimientosPorArea(prenda, container) {
    const seguimientosPorArea = prenda.seguimientos_por_area || {};
    
    // Crear sección de activación
    const activationSection = document.createElement('div');
    activationSection.className = 'tracking-section tracking-section-activation';
    
    // Mostrar fechas de creación vs activación
    const datosActivacion = prenda.datos_activacion_recibo || {};
    fechasWrapper.appendChild(createInfoCard(
        'Fecha creación orden',
        datosActivacion.fecha_creacion_orden_formateada || '-',
        this.svgIcons.calendar()
    ));
    
    // POR CADA ÁREA
    Object.entries(seguimientosPorArea).forEach(([area, data]) => {
        const areaCard = this.createAreaCard(area, data, readonly);
        areasSection.appendChild(areaCard);
    });
}
```

#### Método 3: createAreaCard(area, data, readonly) ← MÁS IMPORTANTE
**Líneas**: ~172-400+

```javascript
createAreaCard(area, data, readonly = false) {
    const card = document.createElement('div');
    
    // Extraer datos del objeto retornado por backend
    const metadata = data.metadata || {};
    const duraciones = data.duraciones || {};
    const fechasFormateadas = data.fechas_formateadas || {};
    
    // Formatear fechas
    const fechaLlegada = fecharFormateadas.fecha_llegada 
        || this.formatDate(data.fecha_inicio) || '---';
    const fechaAsignacion = fechasFormateadas.fecha_asignacion 
        || this.formatDate(data.fecha_de_asignacion_encargado) || '---';
    const fechaFin = fechasFormateadas.fecha_fin 
        || this.formatDate(data.fecha_fin || data.fecha_completado) || '---';
    
    // Formatear duraciones
    const formatDuracionDias = (dias) => {
        if (!dias) return '---';
        return `${dias} día${dias !== 1 ? 's' : ''}`;
    };
    
    const duracionAsignacion = formatDuracionDias(duraciones.duracion_asignacion);
    const duracionEnArea = formatDuracionDias(duraciones.duracion_en_area_dias);
    const totalDias = formatDuracionDias(duraciones.total_dias_numero);
    
    // Construir HTML
    card.innerHTML = `
        <div class="tracking-area-header">
            <span class="area-icon">${this.svgIcons.get(area)}</span>
            <span class="area-nombre">${area}</span>
            <span class="area-estado ${duraciones.estado_display.toLowerCase()}">
                ${duraciones.estado_display}
            </span>
        </div>
        
        <div class="tracking-area-content">
            <!-- Fechas -->
            <div class="tracking-row">
                <div class="tracking-item">
                    <label>Llegada</label>
                    <span>${fechaLlegada}</span>
                </div>
                <div class="tracking-item">
                    <label>Asignación</label>
                    <span>${fechaAsignacion}</span>
                </div>
                <div class="tracking-item">
                    <label>Completado</label>
                    <span>${fechaFin}</span>
                </div>
            </div>
            
            <!-- DURACIONES DEL BACKEND -->
            <div class="tracking-row">
                <div class="tracking-item">
                    <label>Duración asignación </label>
                    <span class="duration">${duracionAsignacion}</span>
                </div>
                <div class="tracking-item">
                    <label>Duración en área </label>
                    <span class="duration">${duracionEnArea}</span>
                </div>
                <div class="tracking-item">
                    <label>Total días </label>
                    <span class="duration">${totalDias}</span>
                </div>
            </div>
            
            <!-- Encargado -->
            <div class="tracking-encargado">
                <label>Encargado</label>
                <span>${data.encargado_nombre || 'No asignado'}</span>
            </div>
            
            <!-- Botones si no está readonly -->
            ${!readonly ? `
                <button class="btn btn-editar" onclick="...">Editar</button>
                <button class="btn btn-completar" onclick="...">Completar</button>
            ` : ''}
        </div>
    `;
    
    return card;
}
```

---

##  MAPA DE FLUJO DE DATOS

```
TABLAS BD              BACKEND PROCESSING           JSON Response        FRONTEND Rendering
═══════════════════════════════════════════════════════════════════════════════════════════════

consecutivos_recibos_
pedidos (área actual)  ┐
                      │
proceso_prenda        ├─ GetSeguimientoPorPrendaUseCase
(todas las áreas)     │  ├─ obtenerYCalcularProcesos()
                      │  ├─ calcularDuracionesArea()
prenda_recibo_        │  └─ inyectarAreaInsumos()
completado (fechas)   ┘   CalculadorDiasService
                          (exclusión festivos)
                                       │
                                       ▼
                           {
                             "seguimientos_por_area": {
                               "Corte": {
                                 "duraciones": {
                                   "duracion_asignacion": 1,
                                   "duracion_en_area_dias": 2,
                                   "total_dias_numero": 3
                                 }
                               }
                             }
                           }
                                       │
                                       ▼
                            TrackingTimelineController
                            - renderSeguimientosPorArea()
                            - createAreaCard()
                            - formatDuracionDias()
                                       │
                                       ▼
                            ┌──────────────────────┐
                            │ Modal HTML Renderizado│
                            │ Con tarjetas por área │
                            │ Mostrando duraciones  │
                            └──────────────────────┘
```

---

##  RESUMEN RÁPIDO

### Para obtener datos de área completada:
1. **ID del recibo**: Desde `consecutivos_recibos_pedidos.id`
2. **Query**: `prenda_recibo_completado.fecha_completado WHERE id_recibo=? AND area=?`
3. **Si existe**: Usar esa fecha como `fecha_completado`
4. **Si no existe**: El área está en progreso, usar `now()`

### Para calcular duraciones:
1. **Duración asignación**: Días entre `created_at` y `fecha_de_asignacion_encargado`
2. **Duración en área**: Días entre `fecha_de_asignacion_encargado` (o `created_at`) y `fecha_completado`
3. **Total días**: Días entre `created_at` y `fecha_completado`
4. **Servicio**: `CalculadorDiasService::calcularDiasHabiles()` (excluye sábados, domingos, festivos)

### En el frontend:
- Recibir JSON con `seguimientos_por_area[area_name].duraciones`
- Formatear con `formatDuracionDias()`
- Renderizar en tarjetas del modal

---

##  ARCHIVOS CLAVE POR RESPONSABILIDAD

| Responsabilidad | Archivo |
|---|---|
| **Queries BD** | [ConsecutivosRecibosRepository.php](app/Infrastructure/Repositories/ConsecutivosRecibosRepository.php) |
| **Orquestación** | [GetSeguimientoPorPrendaUseCase.php](app/Application/Pedidos/UseCases/RegistroOrden/GetSeguimientoPorPrendaUseCase.php) |
| **Cálculo días** | [CalculadorDiasService.php](app/Services/CalculadorDiasService.php) |
| **Modelos** | [ConsecutivosRecibosPedidos.php](app/Models/ConsecutivosRecibosPedidos.php), [PrendaReciboCompletado.php](app/Models/PrendaReciboCompletado.php) |
| **Controlador** | [RegistroOrdenQueryController.php](app/Infrastructure/Http/Controllers/RegistroOrdenQueryController.php) |
| **Frontend JS** | [TrackingTimelineController.js](public/js/ordersjs/presentation/TrackingTimelineController.js) |

---

## ✨ NOTA IMPORTANTE

**El flujo es completamente funcional:** Los datos de duración se calculan en el backend (línea 326-345 del UseCase) y se retornan listos para usar en el frontend. El frontend solo formatea y renderiza.

