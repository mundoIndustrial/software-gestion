# DIAGRAMA: FLUJO DE ESTADOS COTIZACIONES Y PEDIDOS

## 🔄 FLUJO DE COTIZACIONES

```
┌─────────────────────────────────────────────────────────────────────────┐
│                       FLUJO DE COTIZACIÓN COMPLETO                      │
└─────────────────────────────────────────────────────────────────────────┘

    ┌──────────────┐
    │   BORRADOR   │  ← Asesor crea cotización
    │  (Sin número)│     Estado inicial
    └──────┬───────┘
           │ Asesor: "Enviar"
           │ POST /cotizaciones/{id}/enviar
           ▼
    ┌──────────────────────┐
    │ ENVIADA_CONTADOR     │  ← Job: EnviarCotizacionAContadorJob
    │ (Sin número aún)     │     Notifica a Contador
    │ ⏳ Esperando Contador │
    └──────┬───────────────┘
           │ Contador: "Aprobar"
           │ POST /cotizaciones/{id}/aprobar-contador
           ▼
    ┌──────────────────────────────────────────────┐
    │ APROBADA_CONTADOR                            │
    │ 🔄 Job: AsignarNumeroCotizacionJob            │
    │    ├─ Asigna número_cotizacion (AUTOINCREMENT)│
    │    └─ Job: EnviarCotizacionAAprobadorJob      │
    │       └─ Notifica a Aprobador                │
    └──────┬───────────────────────────────────────┘
           │ Aprobador: "Aprobar"
           │ POST /cotizaciones/{id}/aprobar-aprobador
           ▼
    ┌───────────────────────┐
    │ APROBADA_COTIZACIONES │  ← ✅ LISTA PARA CREAR PEDIDO
    │ ✓ Tiene número        │     Visible en buscador Asesor
    │ ✓ Aprobada            │     Disponible para crear pedido
    └──────┬────────────────┘
           │ Asesor: "Crear Pedido"
           ▼
    ┌──────────────────────┐
    │ CONVERTIDA_PEDIDO    │  ← Se creó PedidoProduccion
    │ (Pedido PENDIENTE)   │     Cotización pasa a este estado
    └──────┬───────────────┘
           │ Supervisor: Aprueba
           │ POST /pedidos/{id}/aprobar-supervisor
           ▼
    ┌──────────────────────┐
    │   FINALIZADA         │  ← Pedido completado en producción
    │ ✓ Todo completado    │
    └──────────────────────┘


Estados Intermedios (Solo para auditoría):
• BORRADOR → ENVIADA_CONTADOR (paso de envío)
• ENVIADA_CONTADOR → APROBADA_CONTADOR (paso de aprobación contador)
• APROBADA_CONTADOR → APROBADA_COTIZACIONES (paso de aprobación final)
• APROBADA_COTIZACIONES → CONVERTIDA_PEDIDO (paso de conversión a pedido)
• CONVERTIDA_PEDIDO → FINALIZADA (paso de finalización)

```

---

## 📦 FLUJO DE PEDIDOS

```
┌──────────────────────────────────────────────────────────┐
│           FLUJO DE PEDIDO COMPLETO                       │
└──────────────────────────────────────────────────────────┘

    ┌──────────────────────────┐
    │ PENDIENTE_SUPERVISOR     │  ← Se creó desde cotización aprobada
    │ (numero_pedido = NULL)   │     "Por asignar" en front
    │ ⏳ Esperando Supervisor  │     Cotización: CONVERTIDA_PEDIDO
    └──────┬───────────────────┘
           │ Supervisor: "Aprobar"
           │ POST /pedidos/{id}/aprobar-supervisor
           ▼
    ┌──────────────────────────────────────────────┐
    │ APROBADO_SUPERVISOR                          │
    │ 🔄 Job: AsignarNumeroPedidoJob                │
    │    ├─ Asigna número_pedido (AUTOINCREMENT)   │
    │    └─ Cambia estado a EN_PRODUCCION          │
    └──────┬───────────────────────────────────────┘
           │
           ▼
    ┌──────────────────────┐
    │  EN_PRODUCCION       │  ← ✅ VA A PRODUCCIÓN
    │ ✓ Tiene número       │     Procesos comienzan
    │ ✓ Aprobado           │     Pasa por las áreas
    └──────┬───────────────┘
           │ [Procesos de Producción]
           │ Corte → Costura → Control → Empaque → Despacho
           │
           ▼
    ┌──────────────────────┐
    │   FINALIZADO         │  ← ✓ COMPLETADO
    │ ✓ Todo completado    │     Cotización: FINALIZADA
    └──────────────────────┘

Estados Intermedios:
• PENDIENTE_SUPERVISOR → APROBADO_SUPERVISOR (paso de aprobación)
• APROBADO_SUPERVISOR → EN_PRODUCCION (paso de envío a producción)
• EN_PRODUCCION → FINALIZADO (paso de finalización)

```

---

## 🔀 INTEGRACIÓN COTIZACIONES ↔ PEDIDOS

```
┌─────────────────────────────────────────────────────────────┐
│        RELACIÓN ENTRE COTIZACIONES Y PEDIDOS                 │
└─────────────────────────────────────────────────────────────┘

COTIZACIÓN:                          PEDIDO:
┌─────────────────────────┐         ┌──────────────────────┐
│ APROBADA_COTIZACIONES   │         │ PENDIENTE_SUPERVISOR │
│ ✓ Tiene numero_cot      │ ──────> │ (Se crea desde cot)  │
│ ✓ Disponible para       │         └──────────────────────┘
│   crear pedido          │         (copiar datos)
│ numero_cotizacion: 1001 │         numero_cotizacion: 1001
│ cliente: XYZ            │         numero_pedido: NULL
│ asesor_id: 5            │         asesor_id: 5
└─────────────────────────┘         └──────────────────────┘
        │                                    │
        │ Asesor: "Crear Pedido"            │
        │                                    │
        └──────────────────────────────────>│
                                             │
    Cotización:                         Pedido:
    estado: CONVERTIDA_PEDIDO           estado: PENDIENTE_SUPERVISOR
                                             │
                                        Supervisor: "Aprobar"
                                             │
                                             ▼
                                        ┌──────────────────────┐
                                        │  EN_PRODUCCION       │
                                        │ ✓ numero_pedido: 501 │
                                        │ ✓ En proceso...      │
                                        └──────────────────────┘
                                             │
                                        [Todos los procesos ✓]
                                             │
                                             ▼
                          Ambos pasan a estado FINALIZADO
                          Cotización: FINALIZADA
                          Pedido: FINALIZADO
```

---

## 📋 HISTORIAL DE CAMBIOS (Auditoría)

```
┌────────────────────────────────────────────────────────────────┐
│     CADA CAMBIO DE ESTADO SE REGISTRA EN HISTORIAL             │
└────────────────────────────────────────────────────────────────┘

Registro en: historial_cambios_cotizaciones / historial_cambios_pedidos

{
  ID: 1
  cotizacion_id: 100
  estado_anterior: "BORRADOR"
  estado_nuevo: "ENVIADA_CONTADOR"
  usuario_id: 5
  usuario_nombre: "Juan Pérez"
  rol_usuario: "asesor"
  razon_cambio: "Cotización enviada a contador para revisión"
  ip_address: "192.168.1.100"
  user_agent: "Mozilla/5.0..."
  datos_adicionales: {
    "numero_cotizacion": 1001,
    "cliente": "XYZ S.A."
  }
  created_at: 2025-12-04 10:30:45
}

[Todos los cambios quedan registrados en orden cronológico]
```

---

## 🎬 SECUENCIA DE COLAS (Jobs)

```
┌────────────────────────────────────────────────────────────────┐
│          PROCESAMIENTO EN COLAS (Queue Workers)                │
└────────────────────────────────────────────────────────────────┘

Evento: Asesor envía cotización

1️⃣  EnviarCotizacionAContadorJob
    └─ Notifica a Contador
    └─ Guarda en logs
    └─ Status: COMPLETADO

2️⃣  Contador aprueba
    └─ AsignarNumeroCotizacionJob (dispatch inmediato)
       ├─ Calcula: MAX(numero_cotizacion) + 1
       ├─ Asigna número
       ├─ Registra en historial
       └─ Dispara: EnviarCotizacionAAprobadorJob

3️⃣  EnviarCotizacionAAprobadorJob
    ├─ Cambia estado a APROBADA_COTIZACIONES
    ├─ Notifica a Aprobador
    └─ Status: COMPLETADO

Para Pedidos: Igual patrón con AsignarNumeroPedidoJob

Características:
✓ Retries: 3 intentos
✓ Backoff: [10s, 30s, 60s]
✓ Timeout: 60 segundos
✓ Logging completo
✓ Sin bloqueo del usuario (async)
```

---

## 🛡️ VALIDACIONES

```
┌────────────────────────────────────────────────────────────────┐
│            VALIDACIONES EN CADA TRANSICIÓN                     │
└────────────────────────────────────────────────────────────────┘

1. VALIDACIÓN DE TRANSICIÓN DE ESTADO
   ✓ Solo se permite cambiar a estados válidos
   ✓ Definidos en Enum: transicionesPermitidas()
   
   BORRADOR solo puede ir a:
   └─ ENVIADA_CONTADOR
   
   ENVIADA_CONTADOR solo puede ir a:
   └─ APROBADA_CONTADOR
   
   (Y así sucesivamente)

2. VALIDACIÓN DE AUTORIZACIÓN
   ✓ Asesor: solo puede enviar su propia cotización
   ✓ Contador: solo puede aprobar como contador
   ✓ Aprobador: solo puede aprobar como aprobador
   ✓ Supervisor: solo puede aprobar como supervisor
   
3. VALIDACIÓN DE DATOS
   ✓ Número único (cotizacion, pedido)
   ✓ Foreign keys válidas
   ✓ Datos requeridos presentes

4. VALIDACIÓN DE ESTADO ANTERIOR
   ✓ No se permite cambio si el estado actual no es el esperado
   ✓ Race condition protection
```

---

## 📊 CASOS DE USO

### Caso Feliz ✅
```
Asesor → Enviar → Contador → Aprobar → Aprobador → Aprobar 
→ Asesor → Crear Pedido → Supervisor → Aprobar → Producción
```

### Casos de Error ❌
```
1. Asesor intenta enviar cotización que no es suya
   └─ Error 403: Forbidden

2. Contador intenta aprobar una cotización ya aprobada
   └─ Error 400: Transición inválida

3. Intento de cambiar estado sin autorización
   └─ Error 403: Forbidden

4. Base de datos rechaza número duplicado
   └─ Retry automático hasta 3 veces
   └─ Si persiste: Job falla y se registra en failed_jobs
```

---

## 🎨 EJEMPLO DE RESPUESTA JSON

### Enviar Cotización
```json
{
  "success": true,
  "message": "Cotización enviada a contador exitosamente",
  "cotizacion": {
    "id": 100,
    "estado": "ENVIADA_CONTADOR",
    "numero_cotizacion": null
  }
}
```

### Aprobar como Contador (con Job)
```json
{
  "success": true,
  "message": "Cotización aprobada por contador. Se está asignando número...",
  "cotizacion": {
    "id": 100,
    "estado": "APROBADA_CONTADOR",
    "numero_cotizacion": null
  }
}
// (El número se asignará cuando el Job se ejecute)
```

### Obtener Seguimiento
```json
{
  "success": true,
  "data": {
    "id": 100,
    "numero_cotizacion": 1001,
    "cliente": "XYZ S.A.",
    "estado": "APROBADA_COTIZACIONES",
    "estado_label": "Aprobada por Aprobador",
    "estado_color": "green",
    "estado_icono": "check-double",
    "fecha_envio": "2025-12-04 10:30:45",
    "aprobada_por_contador_en": "2025-12-04 10:35:20",
    "aprobada_por_aprobador_en": "2025-12-04 10:40:10",
    "historial": [
      {
        "estado_anterior": "BORRADOR",
        "estado_nuevo": "ENVIADA_CONTADOR",
        "usuario_nombre": "Juan Pérez",
        "fecha": "2025-12-04 10:30:45"
      },
      {
        "estado_anterior": "ENVIADA_CONTADOR",
        "estado_nuevo": "APROBADA_CONTADOR",
        "usuario_nombre": "Sistema",
        "fecha": "2025-12-04 10:35:20"
      }
    ]
  }
}
```

---

## 🚀 PRÓXIMAS FASES

1. **Fase 1: Vistas y Componentes** (Blade)
   - Botones de acción
   - Modales de historial
   - Paneles de seguimiento
   - Indicadores visuales

2. **Fase 2: Notificaciones**
   - Email a Contador
   - Email a Aprobador
   - Email a Supervisor
   - Notificaciones en-app (database channel)

3. **Fase 3: Búsqueda de Cotizaciones**
   - Filtro: Solo APROBADA_COTIZACIONES
   - Búsqueda por cliente
   - Búsqueda por número_cotizacion

4. **Fase 4: Pruebas**
   - Unit tests
   - Feature tests
   - Integration tests
   - Seeders

5. **Fase 5: Documentación**
   - API docs (Swagger)
   - Manual de usuario por rol
   - Guía de troubleshooting
