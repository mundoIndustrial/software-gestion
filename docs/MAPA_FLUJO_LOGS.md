# 🗺️ MAPA DE FLUJO DE LOGS - Creación de Pedidos

## 📍 GET /asesores/pedidos-editable/crear-nuevo

```
NAVEGADOR
   │
   ├─ HTTP GET → /asesores/pedidos-editable/crear-nuevo
   │
   └─→ CrearPedidoEditableController::crearNuevo()
         │
         ├─ ⏱️ START: [CREAR-PEDIDO-NUEVO] ⏱️ INICIANDO CARGA
         │
         ├─ 📏 Talla::all() 
         │  └─ ⏱️ LOG: [CREAR-PEDIDO-NUEVO] 📏 Tallas cargadas {tiempo_ms: X}
         │
         ├─ 📦 PedidoProduccion::where() ... ->get()
         │  └─ ⏱️ LOG: [CREAR-PEDIDO-NUEVO] 📦 Pedidos existentes {tiempo_ms: X}
         │
         ├─ 👥 Cliente::orderBy()->get() ← POSIBLE CUELLO DE BOTELLA
         │  └─ ⏱️ LOG: [CREAR-PEDIDO-NUEVO] 👥 Clientes cargados {tiempo_ms: X}
         │                                                           ↑
         │                                                  Si > 1000ms = PROBLEMA
         │
         ├─ view('crear-pedido-nuevo', [...])
         │  └─ ⏱️ LOG: TIEMPO DE RENDERIZADO
         │
         └─ ⏱️ END: [CREAR-PEDIDO-NUEVO] ✨ PÁGINA COMPLETADA
            └─ RESUMEN: "Tallas: Xms | Pedidos: Xms | Clientes: Xms | TOTAL: Xms"
                         └─ Si TOTAL > 2000ms = LENTO
                         └─ Si Clientes > 1000ms = ÍNDICES EN BD

   │
   └─→ RESPUESTA HTML → NAVEGADOR
       Página cargada ✅
```

---

## 📍 GET /asesores/pedidos-editable/crear-desde-cotizacion

```
NAVEGADOR
   │
   ├─ HTTP GET → /asesores/pedidos-editable/crear-desde-cotizacion
   │
   └─→ CrearPedidoEditableController::crearDesdeCotizacion()
         │
         ├─ ⏱️ START: [CREAR-DESDE-COTIZACION] ⏱️ INICIANDO CARGA
         │
         ├─ 📏 Talla::all() 
         │  └─ ⏱️ LOG: {tiempo_ms: X}
         │
         ├─ 📋 Cotizacion::with(['cliente', 'prendas', 'fotos', ...]) ← ⚠️ CRÍTICO
         │  │   WHERE asesor_id = X
         │  │   WHERE estado IN ['APROBADA', 'APROBADO_PEDIDO']
         │  │
         │  └─ ⏱️ LOG: [CREAR-DESDE-COTIZACION] 📋 Cotizaciones cargadas {
         │         tiempo_ms: X,  ← Si > 2000ms = OPTIMIZAR QUERY
         │         nota: "Este es el tiempo MÁS CRÍTICO"
         │     }
         │
         ├─ 📦 PedidoProduccion::where() ... ->get()
         │  └─ ⏱️ LOG: {tiempo_ms: X}
         │
         ├─ 👥 Cliente::orderBy()->get()
         │  └─ ⏱️ LOG: {tiempo_ms: X}
         │
         └─ ⏱️ END: [CREAR-DESDE-COTIZACION] ✨ PÁGINA COMPLETADA
            └─ RESUMEN con desglose

   │
   └─→ RESPUESTA HTML → NAVEGADOR
       Página cargada ✅
```

---

## 📍 POST /asesores/pedidos-editable/crear

```
NAVEGADOR (FormData)
   │
   ├─ JSON: { cliente: "X", prendas: [...], epps: [...] }
   ├─ FILES: imagen1.jpg, imagen2.jpg, ...
   │
   └─→ CrearPedidoEditableController::crearPedido()
         │
         ├─ ⏱️ START: [CREAR-PEDIDO] ⏱️ INICIANDO CREACIÓN TRANSACCIONAL
         │
         ├─ ═══ PASO 1: JSON ═══
         │  ├─ json_decode($request->input('pedido'))
         │  └─ ⏱️ LOG: [CREAR-PEDIDO] ✅ PASO 1: JSON decodificado {tiempo_ms: X}
         │
         ├─ ═══ PASO 2: CLIENTE ═══
         │  ├─ obtenerOCrearCliente()
         │  └─ ⏱️ LOG: [CREAR-PEDIDO] ✅ PASO 2: Cliente obtenido {tiempo_ms: X}
         │
         ├─ ═══ PASO 3: DTO ═══
         │  ├─ PedidoNormalizadorDTO::fromFrontendJSON()
         │  └─ ⏱️ LOG: [CREAR-PEDIDO] ✅ PASO 3: Pedido normalizado {tiempo_ms: X}
         │
         ├─ ═══ PASO 4: TRANSACCIÓN ═══
         │  └─ DB::beginTransaction()
         │
         ├─ ═══ PASO 5: PEDIDO BASE ═══
         │  ├─ $this->pedidoWebService->crearPedidoCompleto()
         │  └─ ⏱️ LOG: [CREAR-PEDIDO] ✅ PASO 5: Pedido base creado {tiempo_ms: X}
         │                                                             ↑
         │                                                    Si > 500ms = TRIGGERS
         │
         ├─ ═══ PASO 6: CARPETAS ═══
         │  ├─ crearCarpetasPedido()
         │  └─ ⏱️ LOG: [CREAR-PEDIDO] ✅ PASO 6: Carpetas creadas {tiempo_ms: X}
         │
         ├─ ═══ PASO 7: IMÁGENES ═══ ← ⚠️ CUELLO DE BOTELLA TÍPICO
         │  │
         │  └─→ MapeoImagenesService::mapearYCrearFotos()
         │       │
         │       ├─ ⏱️ LOG: [MAPEO-IMAGENES] 📸 INICIANDO MAPEO
         │       │
         │       └─→ ResolutorImagenesService::extraerYProcesarImagenes()
         │            │
         │            ├─ ⏱️ LOG: [RESOLVER-IMAGENES] 📸 INICIANDO EXTRACCIÓN
         │            │
         │            ├─ foreach imagen in FormData
         │            │  │
         │            │  └─→ ImageUploadService::guardarImagenDirecta()
         │            │       │
         │            │       ├─ ⏱️ LOG: [IMAGE-UPLOAD] 📤 Iniciando guardado
         │            │       ├─ • Validación → X ms
         │            │       ├─ • Carga imagen → X ms
         │            │       ├─ • Conversión WebP → X ms ← Si > 200ms = LENTO
         │            │       │
         │            │       └─ ⏱️ LOG: [IMAGE-UPLOAD] ✅ Imagen guardada {
         │            │              tiempo_total_ms: X,
         │            │              desglose: {...}
         │            │          }
         │            │
         │            └─ ⏱️ LOG: [RESOLVER-IMAGENES] ✅ Extracción completada {
         │                   imagenes_procesadas: X,
         │                   imagenes_esperadas: X,
         │                   diferencia: Y  ← Si > 0 = IMÁGENES PERDIDAS
         │               }
         │
         │       └─ ⏱️ LOG: [MAPEO-IMAGENES] ✨ MAPEO COMPLETADO {tiempo_ms: X}
         │
         │  └─ ⏱️ LOG: [CREAR-PEDIDO] ✅ PASO 7: Imágenes mapeadas {tiempo_ms: X}
         │                                                              ↑
         │                                                     Si > 3000ms = PROBLEMA
         │
         ├─ ═══ PASO 7B: EPPs ═══
         │  ├─ procesarYAsignarEpps()
         │  └─ ⏱️ LOG: [CREAR-PEDIDO] ✅ PASO 7B: EPPs procesados {tiempo_ms: X}
         │
         ├─ ═══ PASO 8: CÁLCULO Y COMMIT ═══
         │  ├─ calcularCantidadTotalPrendas()
         │  ├─ calcularCantidadTotalEpps()
         │  ├─ $pedido->update(['cantidad_total' => X])
         │  ├─ DB::commit()
         │  └─ ⏱️ LOG: [CREAR-PEDIDO] ✅ PASO 8: Cálculo {tiempo_ms: X}
         │
         └─ ⏱️ END: [CREAR-PEDIDO] ✨ TRANSACCIÓN EXITOSA - RESUMEN TOTAL {
                tiempo_total_ms: X,
                desglose_pasos: {
                  paso_1_json_ms: X,
                  paso_2_cliente_ms: X,
                  paso_3_dto_ms: X,
                  paso_5_pedido_base_ms: X,
                  paso_6_carpetas_ms: X,
                  paso_7_imagenes_ms: X,  ← CRÍTICO Si > 3000ms
                  paso_7b_epps_ms: X,
                  paso_8_calculo_ms: X
                },
                resumen: "JSON: Xms | Cliente: Xms | ... | TOTAL: Xms"
            }
                      │
                      └─ Si TOTAL > 6000ms = MUY LENTO
                      └─ Encontrar paso > 2000ms

   │
   └─→ RESPUESTA JSON → NAVEGADOR {success: true, pedido_id: X}
       Pedido guardado ✅
```

---

## 🎯 Cómo Usar Este Mapa

### "Mi página tarda en cargar"
→ Revisar el FLUJO de `GET /crear-nuevo`
→ Buscar el primer **[...] COMPLETADA** en logs
→ Mirar el "resumen" y encontrar el ms más alto

### "Mi pedido tarda en guardarse"
→ Revisar el FLUJO de `POST /crear`
→ Buscar **[CREAR-PEDIDO] ✨ TRANSACCIÓN EXITOSA**
→ En "desglose_pasos" encontrar el > 2000ms

### "Mis imágenes no se guardan"
→ En el FLUJO `POST /crear`, seguir PASO 7
→ Buscar **[RESOLVER-IMAGENES] ✅ Extracción completada**
→ Si "diferencia > 0" → Problema en FormData

---

## 🔄 Ciclo Completo de Diagnóstico

```
┌─────────────────────────────────────┐
│     1. USUARIO REPORTA: "Va lento"  │
└──────────────┬──────────────────────┘
               │
               ↓
┌─────────────────────────────────────┐
│   2. EJECUTA: .\analizar-logs-     │
│      logs-pedidos.ps1              │
└──────────────┬──────────────────────┘
               │
               ↓
┌─────────────────────────────────────┐
│  3. IDENTIFICA: PASO/MÓDULO LENTO   │
│     (Ej: paso_7_imagenes_ms: 5000) │
└──────────────┬──────────────────────┘
               │
               ↓
┌─────────────────────────────────────┐
│  4. CONSULTA: Sección correspon-   │
│     diente en este mapa             │
│     (Ej: PASO 7: IMÁGENES)          │
└──────────────┬──────────────────────┘
               │
               ↓
┌─────────────────────────────────────┐
│  5. BUSCA SOLUCIÓN en:              │
│     LOGS_DIAGNOSTICO_PEDIDOS.md     │
└──────────────┬──────────────────────┘
               │
               ↓
┌─────────────────────────────────────┐
│  6. APLICA: Optimización recomenda │
│     (Ej: aumentar memory_limit)     │
└──────────────┬──────────────────────┘
               │
               ↓
┌─────────────────────────────────────┐
│  7. VERIFICA: Nuevo tiempo en logs  │
│     antes vs después                │
└──────────────┬──────────────────────┘
               │
               ↓
         ✅ RESUELTO
```

---

## 📊 Leyenda de Símbolos

| Símbolo | Significa |
|---|---|
| `⏱️` | Medición de tiempo |
| `✅` | Operación completada |
| `⚠️` | Atención, posible cuello de botella |
| `🔴` | Crítico, esperar aquí |
| `→` | Flujo de ejecución |
| `└─` | Final de rama |
| `│` | Continuación |

---

## 🎓 Ejemplo de Interpretación

```
LOG ACTUAL:
[CREAR-PEDIDO] ✨ TRANSACCIÓN EXITOSA
"paso_7_imagenes_ms": 8000    ← ¡PROBLEMA!
"paso_5_pedido_base_ms": 200

INTERPRETACIÓN:
1. Paso 7 tarda 8000ms (8 segundos) = MUY LENTO
2. Esto es en ImageUploadService (conversión WebP)
3. Probable causa: Imágenes muy grandes o CPU lenta
4. Solución: Reducir resolución o usar Queue

ANTES: 8000ms
DESPUÉS: 1500ms (✅ 5.3x más rápido)
```

---

**Este mapa es tu guía visual para entender dónde están los logs en el flujo real de ejecución.**
