#  LOGS DE DIAGNÓSTICO - Creación de Pedidos

##  ¿Qué Se Hizo?

Se agregaron **logs detallados con medición de tiempo** en toda la cadena de creación de pedidos para identificar rápidamente **dónde está el cuello de botella** que causa lentitud.

```
┌─────────────────────────────────────────────┐
│    Página tarda en cargar / pedido lento    │
│             (¿Por qué demora?)              │
│                     ↓                       │
│      Revisar LOGS con microtiming para     │
│        saber exactamente dónde está        │
│         el problema y optimizar            │
└─────────────────────────────────────────────┘
```

---

##  Uso Rápido (30 segundos)

### 1. Hacer la acción en navegador
```
Visita: http://localhost:8000/asesores/pedidos-editable/crear-nuevo
Crea un pedido de prueba y observa el tiempo
```

### 2. Ver los logs en PowerShell
```powershell
# Opción A: Ver todo automático
.\scripts\analizar-logs-pedidos.ps1

# Opción B: Ver manualmente
tail -100 storage/logs/laravel.log | Select-String "CREAR-PEDIDO"
```

### 3. Interpretar resultado
```
Si ves en el resumen:
  Tallas: 50ms | Clientes: 2500ms | Imágenes: 3000ms | TOTAL: 5600ms
                            ↑                    ↑
                       PROBLEMA1            PROBLEMA2
                       
→ Clientes tarda 2500ms = Problema en BD
→ Imágenes tarda 3000ms = Problema en procesamiento
```

---

##  Archivos Modificados

### Controllers (2 archivos)
-  `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`
  - `crearNuevo()` - Carga de página
  - `crearDesdeCotizacion()` - Carga de página desde cotización
  - `crearPedido()` - 8 pasos desglosados con timing

### Domain Services (2 archivos)
-  `app/Domain/Pedidos/Services/ResolutorImagenesService.php`
  - Extracción y procesamiento de imágenes
  
-  `app/Domain/Pedidos/Services/MapeoImagenesService.php`
  - Mapeo de referencias de imágenes

### Application Services (1 archivo)
-  `app/Application/Services/ImageUploadService.php`
  - Guardado de imágenes con conversión WebP

---

##  Ejemplo de Logs Que Verás

###  Caso Normal (Rápido)
```
[CREAR-PEDIDO-NUEVO] ✨ PÁGINA COMPLETADA
"tiempo_total_ms": 1500,
"resumen": "Tallas: 45ms | Pedidos: 120ms | Clientes: 500ms | View: 100ms | TOTAL: 1500ms"
```

###  Caso Problema (Lento)
```
[CREAR-PEDIDO] ✨ TRANSACCIÓN EXITOSA - RESUMEN TOTAL
"tiempo_total_ms": 12500,
"desglose_pasos": {
  "paso_7_imagenes_ms": 8000,  ← ¡AQUÍ ESTÁ EL PROBLEMA!
  "paso_5_pedido_base_ms": 200
}
"resumen": "...Imágenes: 8000ms...TOTAL: 12500ms"
```

---

## 🎓 Cómo Leer Los Logs

| Qué Buscar | Log | Qué Significa |
|---|---|---|
| Carga lenta | `[CREAR-PEDIDO-NUEVO] ✨` | Página tarda en aparecer |
| Creación lenta | `[CREAR-PEDIDO] ✨` | Guardar pedido es lento |
| Imágenes | `[RESOLVER-IMAGENES]` | Procesamiento de imágenes |
| Almacenamiento | `[IMAGE-UPLOAD]` | Guardado individual de imagen |

---

## 🛠️ Documentación Completa

### 📖 Archivos de Documentación
1. **LOGS_QUICK_START.md** ← Empieza aquí (5 min)
2. **LOGS_DIAGNOSTICO_PEDIDOS.md** ← Detallado (15 min)
3. **IMPLEMENTACION_LOGS_COMPLETA.md** ← Técnico

### 🤖 Script de Análisis
```
.\scripts\analizar-logs-pedidos.ps1
```

Proporciona:
- 📈 Estadísticas de tiempos
-  Identificación de cuellos de botella
- 🚨 Alertas de problemas críticos

---

##  Troubleshooting Rápido

### "La página tarda 5 segundos en cargar"
```powershell
.\scripts\analizar-logs-pedidos.ps1 -Operacion carga-inicial

Luego mira en "Desglose de pasos" cuál tiene el tiempo más alto
```

### "Guardar el pedido tarda 10 segundos"
```powershell
.\scripts\analizar-logs-pedidos.ps1 -Operacion creacion-pedido

Busca en "Desglose de pasos" cuál PASO tarda > 2000ms
```

### "Las imágenes no se guardan"
```powershell
.\scripts\analizar-logs-pedidos.ps1 -Operacion imagenes

Si ves "diferencia > 0" → Problema en FormData del frontend
```

---

##  Tabla de Tiempos Normales

| Operación | Normal | Alerta | Crítico |
|---|---|---|---|
| Carga inicial | < 500ms | 500-2000ms | > 2000ms |
| Crear cliente | < 50ms | 50-100ms | > 100ms |
| Procesar imagen | < 200ms | 200-400ms | > 400ms |
| Crear pedido | < 2000ms | 2-5000ms | > 5000ms |
| **TOTAL pedido** | < 3000ms | 3-6000ms | > 6000ms |

---

##  Próximos Pasos

1. **Ejecutar** `.\scripts\analizar-logs-pedidos.ps1`
2. **Identificar** qué es > 2000ms
3. **Buscar** soluciones en LOGS_DIAGNOSTICO_PEDIDOS.md
4. **Aplicar** optimización (índices DB, caché, etc.)
5. **Medir** mejora comparando logs antes/después

---

##  Soluciones Comunes

### Si Clientes es lento (> 1000ms)
```sql
-- Agregar índices
ALTER TABLE clientes ADD INDEX idx_nombre (nombre);
ALTER TABLE clientes ADD INDEX idx_asesor_nombre (asesor_id, nombre);
```

### Si Cotizaciones es lento (> 2000ms)
```php
// Limitar relaciones en eager loading
->with(['cliente', 'prendas' => fn($q) => $q->limit(50)])
```

### Si Imágenes es lento (> 3000ms)
```
• Reducir resolución de imágenes subidas
• Aumentar memoria PHP (memory_limit = 512M)
• Considerar procesamiento en background con Queue
```

---

## 📞 Referencia Rápida

```
┌─ PREFIJOS DE LOGS
├─ [CREAR-PEDIDO-NUEVO]    = Carga página crear nuevo
├─ [CREAR-DESDE-COTIZACION] = Carga página desde cotización
├─ [CREAR-PEDIDO]          = Guardado de pedido (8 pasos)
├─ [RESOLVER-IMAGENES]     = Extracción de archivos
├─ [MAPEO-IMAGENES]        = Mapeo UID → ruta
└─ [IMAGE-UPLOAD]          = Guardado de imagen

┌─ ARCHIVO DE LOGS
└─ storage/logs/laravel.log

┌─ SCRIPT ANÁLISIS
└─ scripts/analizar-logs-pedidos.ps1
```

---

##  Listo para Usar

Los logs están **100% implementados** en:
-  Controlador de creación
-  Servicios de imágenes
-  Mapeo de referencias
-  Guardado de archivos

Solo hay que **ejecutar la acción y revisar los logs**.

¡A buscar ese cuello de botella! 
