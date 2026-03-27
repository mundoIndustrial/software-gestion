#  IMPLEMENTACIÓN COMPLETADA - LOGS DE DIAGNÓSTICO

##  Resumen

Se han agregado **logs detallados con microtiming** en toda la cadena de creación de pedidos para identificar rápidamente cuellos de botella.

**Objetivo:** Determinar por qué la creación de pedidos se demora tanto en cargar/guardar.

---

##  Qué Se Instrumentó

### 1. **Controlador Principal**
**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`

#### Método: `crearNuevo()` (GET /crear-nuevo)
-  Tiempo total de carga de página
-  Tiempo de carga de tallas
-  Tiempo de carga de pedidos existentes
-  Tiempo de carga de clientes
-  Tiempo de renderizado de vista
-  Resumen en una línea para comparación rápida

#### Método: `crearDesdeCotizacion()` (GET /crear-desde-cotizacion)
-  Tiempo total de carga de página
-  Tiempo de carga de tallas
-  Tiempo de carga de cotizaciones (CON RELACIONES) **← CRÍTICO**
-  Tiempo de carga de pedidos existentes
-  Tiempo de carga de clientes
-  Tiempo de renderizado de vista

#### Método: `crearPedido()` (POST /crear)
Desglose de 8 pasos:
1. **PASO 1:** Decodificación JSON del frontend
2. **PASO 2:** Obtención/creación de cliente
3. **PASO 3:** Normalización con DTO
4. **PASO 5:** Creación de pedido base
5. **PASO 6:** Creación de carpetas
6. **PASO 7:** Mapeo y procesamiento de imágenes **← CRÍTICO**
7. **PASO 7B:** Procesamiento de EPPs
8. **PASO 8:** Cálculo de cantidades y commit

**Cada paso tiene su propio log INFO con microtiming**

---

### 2. **Servicios de Resolución de Imágenes**
**Archivo:** `app/Domain/Pedidos/Services/ResolutorImagenesService.php`

#### Método: `extraerYProcesarImagenes()`
-  Tiempo de extracción de archivos anidados
-  Tiempo total de procesamiento
-  Cuenta de imágenes procesadas vs esperadas
-  Alerta si hay imágenes perdidas (FormData no llegó)

#### Método: `procesarImagenesDeGrupo()`
-  Tiempo por grupo de imágenes
-  Tiempo de guardado individual de cada imagen
-  Desglose por prenda/tela/proceso

---

### 3. **Mapeo de Imágenes**
**Archivo:** `app/Domain/Pedidos/Services/MapeoImagenesService.php`

#### Método: `mapearYCrearFotos()`
-  Tiempo de resolución de imágenes
-  Tiempo de creación de registros en BD
-  Tiempo total de mapeo

---

### 4. **Guardado de Imágenes**
**Archivo:** `app/Application/Services/ImageUploadService.php`

#### Método: `guardarImagenDirecta()`
-  Tiempo de validación
-  Tiempo de carga de imagen en memoria
-  Tiempo de conversión a WebP
-  Tiempo total
-  Información de archivo (tamano, nombre original)

---

##  Ejemplos de Logs

### Carga de Página
```
[2026-01-29 21:35:10] local.INFO: [CREAR-PEDIDO-NUEVO]  INICIANDO CARGA DE PÁGINA {"usuario_id":92}
[2026-01-29 21:35:10] local.INFO: [CREAR-PEDIDO-NUEVO] 📏 Tallas cargadas {"cantidad":50,"tiempo_ms":45.23}
[2026-01-29 21:35:10] local.INFO: [CREAR-PEDIDO-NUEVO]  Pedidos existentes cargados {"cantidad":5,"tiempo_ms":120.56}
[2026-01-29 21:35:10] local.INFO: [CREAR-PEDIDO-NUEVO] 👥 Clientes cargados {"cantidad":500,"tiempo_ms":850.42}
[2026-01-29 21:35:10] local.INFO: [CREAR-PEDIDO-NUEVO] ✨ PÁGINA COMPLETADA {
  "tiempo_total_ms": 1234.56,
  "resumen": "Tallas: 45.23ms | Pedidos: 120.56ms | Clientes: 850.42ms | View: 120.40ms | TOTAL: 1234.56ms"
}
```

### Creación de Pedido
```
[2026-01-29 21:36:00] local.INFO: [CREAR-PEDIDO]  INICIANDO CREACIÓN TRANSACCIONAL
[2026-01-29 21:36:00] local.INFO: [CREAR-PEDIDO]  PASO 1: JSON decodificado {"tiempo_ms":5.12}
[2026-01-29 21:36:00] local.INFO: [CREAR-PEDIDO]  PASO 2: Cliente obtenido/creado {"tiempo_ms":50.45}
[2026-01-29 21:36:00] local.INFO: [CREAR-PEDIDO]  PASO 3: Pedido normalizado (DTO) {"tiempo_ms":30.78}
[2026-01-29 21:36:01] local.INFO: [CREAR-PEDIDO]  PASO 5: Pedido base creado {"tiempo_ms":200.12}
[2026-01-29 21:36:01] local.INFO: [CREAR-PEDIDO]  PASO 6: Carpetas creadas {"tiempo_ms":100.45}
[2026-01-29 21:36:05] local.INFO: [CREAR-PEDIDO]  PASO 7: Imágenes mapeadas {"tiempo_ms":5000.23}
[2026-01-29 21:36:06] local.INFO: [CREAR-PEDIDO]  PASO 8: Cálculo de cantidades {"tiempo_ms":100.56}
[2026-01-29 21:36:06] local.INFO: [CREAR-PEDIDO] ✨ TRANSACCIÓN EXITOSA - RESUMEN TOTAL {
  "tiempo_total_ms": 7500.12,
  "desglose_pasos": {
    "paso_1_json_ms": 5.12,
    "paso_2_cliente_ms": 50.45,
    "paso_3_dto_ms": 30.78,
    "paso_5_pedido_base_ms": 200.12,
    "paso_6_carpetas_ms": 100.45,
    "paso_7_imagenes_ms": 5000.23,
    "paso_7b_epps_ms": 1000.12,
    "paso_8_calculo_ms": 100.56
  },
  "resumen": "JSON: 5.12ms | Cliente: 50.45ms | DTO: 30.78ms | PedidoBase: 200.12ms | Carpetas: 100.45ms | Imágenes: 5000.23ms | EPPs: 1000.12ms | Cálculo: 100.56ms | TOTAL: 7500.12ms"
}
```

---

##  Cómo Usar

### 1. **Opción Rápida: PowerShell Script**
```powershell
# Usar el script analítico
.\scripts\analizar-logs-pedidos.ps1

# Opciones
.\scripts\analizar-logs-pedidos.ps1 -Operacion carga-inicial
.\scripts\analizar-logs-pedidos.ps1 -Operacion creacion-pedido
.\scripts\analizar-logs-pedidos.ps1 -Operacion imagenes
.\scripts\analizar-logs-pedidos.ps1 -Ultimas 50
```

### 2. **Opción Manual: Ver logs**
```powershell
# Ver últimas líneas
Get-Content "storage/logs/laravel.log" -Tail 100

# Filtrar por operación
Select-String "CREAR-PEDIDO" "storage/logs/laravel.log"

# Filtrar por imágenes
Select-String "RESOLVER-IMAGENES|IMAGE-UPLOAD" "storage/logs/laravel.log"

# En tiempo real
Get-Content "storage/logs/laravel.log" -Wait | Select-String "CREAR-PEDIDO"
```

### 3. **Ver Documentación Completa**
```
docs/LOGS_DIAGNOSTICO_PEDIDOS.md     ← Análisis detallado
docs/LOGS_QUICK_START.md             ← Guía rápida
```

---

##  Cucellos de Botella Típicos

### Si carga de página tarda > 3 segundos
Buscar en logs: `[CREAR-DESDE-COTIZACION] ✨ PÁGINA COMPLETADA`
- Si `tiempo_cotizaciones_ms > 2000` → Optimizar query (índices)
- Si `tiempo_clientes_ms > 1000` → Tabla sin índices

### Si creación de pedido tarda > 6 segundos
Buscar en logs: `[CREAR-PEDIDO] ✨ TRANSACCIÓN EXITOSA`
- Si `paso_7_imagenes_ms > 3000` → Imágenes muy grandes/procesamiento lento
- Si `paso_5_pedido_base_ms > 500` → Problema en triggers/validación de BD

### Si imágenes no se guardan
Buscar en logs: `[RESOLVER-IMAGENES]  Extracción completada`
- Si `diferencia > 0` → Imágenes perdidas en FormData
- Ver logs de `[IMAGE-UPLOAD]` para ver si hay errores

---

## 📝 Archivos Documentación

1. **LOGS_QUICK_START.md** - Guía rápida (5 min de lectura)
2. **LOGS_DIAGNOSTICO_PEDIDOS.md** - Guía completa con interpretación
3. **analizar-logs-pedidos.ps1** - Script para análisis automático

---

##  Próximos Pasos

1. **Reproducir el problema** y guardar logs
2. **Ejecutar:** `.\scripts\analizar-logs-pedidos.ps1`
3. **Identificar** el componente más lento
4. **Consultar documentación** para soluciones específicas
5. **Aplicar optimización** (índices, caché, etc.)
6. **Medir mejora** comparando logs antes/después

---

##  Checklist de Implementación

- [x] Logs en `crearNuevo()`
- [x] Logs en `crearDesdeCotizacion()`
- [x] Logs en `crearPedido()` (8 pasos desglosados)
- [x] Logs en `extraerYProcesarImagenes()`
- [x] Logs en `procesarImagenesDeGrupo()`
- [x] Logs en `mapearYCrearFotos()`
- [x] Logs en `guardarImagenDirecta()`
- [x] Documentación LOGS_QUICK_START.md
- [x] Documentación LOGS_DIAGNOSTICO_PEDIDOS.md
- [x] Script PowerShell analizar-logs-pedidos.ps1
- [x] Ejemplos de logs incluidos

---

##  Formato de Logs

Todos los logs siguen este patrón:
- **Prefijo:** `[MODULO]` para fácil filtrado
- **Emoji:** Para identificación visual rápida
- **Timestamp:** Incluido por Laravel
- **Detalles JSON:** Estructura parseable
- **Resumen:** Una línea para comparación rápida

**Ejemplo:**
```
[CREAR-PEDIDO]  PASO 5: Pedido base creado {
  "pedido_id": 123,
  "numero_pedido": "PED-2026-001",
  "tiempo_ms": 250.45
}
```

---

##  Configuración de Producción

 **Para producción:**
1. Cambiar `Log::info()` a `Log::debug()` en logs menos críticos
2. Aumentar frecuencia de rotación de logs
3. Considerar enviar logs a servicio externo (LogChannel)

---

**Implementado:** 29 de Enero, 2026  
**Versión:** 1.0  
**Estado:**  Listo para usar
