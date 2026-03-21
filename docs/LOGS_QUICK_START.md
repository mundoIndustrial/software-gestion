#  ANÁLISIS RÁPIDO DE LOGS - Creación de Pedidos

##  Resumen Ejecutivo

Se han agregado **logs detallados con microtiming** en todos los pasos críticos de creación de pedidos para identificar cuellos de botella. Los logs miden el tiempo de cada operación en **milisegundos (ms)**.

##  Archivos Modificados

### Controladores
1. **[CrearPedidoEditableController.php](../app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php)**
   - `crearNuevo()` - Carga inicial de página (crear pedido sin cotización)
   - `crearDesdeCotizacion()` - Carga inicial de página (crear desde cotización)
   - `crearPedido()` - Guardado de pedido con desglose de 8 pasos

### Servicios de Dominio
2. **[ResolutorImagenesService.php](../app/Domain/Pedidos/Services/ResolutorImagenesService.php)**
   - `extraerYProcesarImagenes()` - Resolución y procesamiento de imágenes
   - `procesarImagenesDeGrupo()` - Procesamiento de cada grupo de imágenes

3. **[MapeoImagenesService.php](../app/Domain/Pedidos/Services/MapeoImagenesService.php)**
   - `mapearYCrearFotos()` - Mapeo de UIDs a rutas finales

### Servicios de Aplicación
4. **[ImageUploadService.php](../app/Application/Services/ImageUploadService.php)**
   - `guardarImagenDirecta()` - Guardado de imágenes (validación, carga, conversión WebP)

##  Cómo Usar Los Logs

### 1. Ejecutar acción en el navegador
```
Visita: http://localhost:8000/asesores/pedidos-editable/crear-nuevo
O: http://localhost:8000/asesores/pedidos-editable/crear-desde-cotizacion
Luego: Crea un pedido de prueba
```

### 2. Ver logs en tiempo real
```powershell
# Abrir en PowerShell
Get-Content "storage/logs/laravel.log" -Wait | Select-String "CREAR-PEDIDO"

# O buscar archivo
Get-ChildItem "storage/logs/laravel.log" | tail -100
```

### 3. Filtrar por tipo de operación
```powershell
# Ver carga de página
Select-String "CREAR-PEDIDO-NUEVO.*|CREAR-PEDIDO-NUEVO.*✨" storage/logs/laravel.log

# Ver creación de pedido
Select-String "CREAR-PEDIDO.*✨" storage/logs/laravel.log

# Ver procesamiento de imágenes
Select-String "IMAGE-UPLOAD|RESOLVER-IMAGENES|MAPEO-IMAGENES" storage/logs/laravel.log
```

##  Ejemplo de Log Completo

```
[2026-01-29 21:35:10] local.INFO: [CREAR-PEDIDO-NUEVO]  INICIANDO CARGA DE PÁGINA {"usuario_id":92,"timestamp":"2026-01-29 21:35:10"}
[2026-01-29 21:35:10] local.INFO: [CREAR-PEDIDO-NUEVO] 📏 Tallas cargadas {"cantidad":50,"tiempo_ms":45.23}
[2026-01-29 21:35:10] local.INFO: [CREAR-PEDIDO-NUEVO]  Pedidos existentes cargados {"cantidad":5,"tiempo_ms":120.56}
[2026-01-29 21:35:10] local.INFO: [CREAR-PEDIDO-NUEVO] 👥 Clientes cargados {"cantidad":500,"tiempo_ms":850.42}
[2026-01-29 21:35:10] local.INFO: [CREAR-PEDIDO-NUEVO] ✨ PÁGINA COMPLETADA {
  "tiempo_total_ms": 1234.56,
  "resumen": "Tallas: 45ms | Pedidos: 120ms | Clientes: 850ms | View: 120ms | TOTAL: 1234.56ms"
}
```

→ En este ejemplo, **Clientes es el cuello de botella (850ms)**

##  Qué Buscar

### Carga de Página Lenta?
```
Busca el log: [CREAR-PEDIDO-NUEVO] ✨ PÁGINA COMPLETADA
Luego en "resumen": ¿Cuál es el número más alto?
- Si Cotizaciones > 2000ms → Problema DB
- Si Clientes > 1000ms → Tabla sin índices
```

### Guardado de Pedido Lento?
```
Busca el log: [CREAR-PEDIDO] ✨ TRANSACCIÓN EXITOSA
En "desglose_pasos", ¿cuál es el mayor?
- Si paso_7_imagenes_ms > 3000ms → Imágenes
- Si paso_5_pedido_base_ms > 500ms → BD
```

### Imágenes No Se Guardan?
```
Busca: [RESOLVER-IMAGENES]  Extracción completada
Si: imagenes_esperadas > imagenes_procesadas
Entonces: Problema en FormData (frontend)
```

## 📈 Comparar Antes y Después

```powershell
# Guardar logs antes de optimizar
Copy-Item "storage/logs/laravel.log" "storage/logs/laravel.ANTES.log"

# Hacer optimizaciones...

# Comparar tiempos
$antes = Select-String "TOTAL:" "storage/logs/laravel.ANTES.log"
$ahora = Select-String "TOTAL:" "storage/logs/laravel.log"

# Mostrar última entrada de cada uno
$antes[-1]
$ahora[-1]
```

##  Próximos Pasos Recomendados

1. **Ejecutar el proceso** 3-5 veces y promediar tiempos
2. **Identificar el paso más lento** (> 2000ms es problema)
3. **Consultar [LOGS_DIAGNOSTICO_PEDIDOS.md](LOGS_DIAGNOSTICO_PEDIDOS.md)** para soluciones específicas
4. **Aplicar optimización** (índices DB, caché, etc.)
5. **Comparar antes/después**

##  Notas de Producción

-  Estos logs son para **desarrollo/debugging**
-  Después de terminar, cambiar `Log::info()` a `Log::debug()` en logs no críticos
-  El overhead de los logs es ~2-5% del tiempo total

## 📞 Referencia Rápida

| Prefijo | Significa |
|---|---|
| `[CREAR-PEDIDO-NUEVO]` | Carga de formulario para crear pedido sin cotización |
| `[CREAR-DESDE-COTIZACION]` | Carga de formulario para crear desde cotización |
| `[CREAR-PEDIDO]` | Guardado/creación real del pedido |
| `[RESOLVER-IMAGENES]` | Extracción de archivos del FormData |
| `[MAPEO-IMAGENES]` | Mapeo de UIDs a rutas finales |
| `[IMAGE-UPLOAD]` | Guardado individual de cada imagen |

---

**Documentación completa:** [LOGS_DIAGNOSTICO_PEDIDOS.md](LOGS_DIAGNOSTICO_PEDIDOS.md)
