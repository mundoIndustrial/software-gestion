# 🔍 Panel de Diagnóstico - Sistema de Pedidos

## ¿Qué es?

Un sistema de logging y monitoreo para detectar y diagnosticar **errores en la creación/edición de pedidos**. Captura:

- ❌ Errores de imagen (archivos rechazados, demasiado grandes, etc.)
- ❌ Errores de validación (cliente vacío, tamaño de imágenes total, etc.)
- ❌ Errores de red/API (fallos de conexión, timeouts, 5xx errors)
- ✅ Operaciones exitosas (para contexto)

---

## 📋 Cómo Usarlo

### 1. **Abrir el Panel** (desde la consola del navegador)

```javascript
abrirPanelDiagnostico()
```

O crea un botón en la UI:

```html
<button onclick="abrirPanelDiagnostico()">🔍 Diagnosticar</button>
```

### 2. **Pestaña "Resumen"** (por defecto)

Muestra un dashboard de alto nivel:
- **4 tarjetas** con contadores principales
- **Últimas 24h y últimos 30 minutos** (resumen rápido)
- **Últimos errores** (top 10)
- **Últimos eventos** (feed en tiempo real)

### 3. **Pestaña "Errores"**

Tabla con todos los errores, filtrados por:
- Tipo de error
- Descripción del problema
- Archivo/campo afectado
- Hora exacta

Puedes copiar aquí cuando necesites reportar un bug.

### 4. **Pestaña "Todos"**

Todos los eventos (errores + éxitos) en formato tabla.

---

## 🎯 Botones de Acción

### 📋 Copiar Resumen
Copia a portapapeles un resumen formateado:
```
=== RESUMEN DE ERRORES ===

Total de logs: 45
Últimas 24h: 12
Últimos 30min: 2

POR TIPO:
  - ERROR_IMAGEN: 8
  - ERROR_RED: 3
  - ERROR_VALIDACION: 1
  - EXITO: 33

POR ORIGEN:
  - image-upload: 8
  - api: 3
  - validation: 1
  - general: 33
```

### 📥 Descargar JSON
Descarga un archivo JSON con **todos los detalles**:
```json
[
  {
    "tipo": "ERROR_IMAGEN",
    "timestamp": "2026-04-23T15:30:45.123Z",
    "archivo": "foto_grande.jpg",
    "tamanio": 5242880,
    "error": "FILE_TOO_LARGE: ...",
    "contexto": {
      "tipoError": "FILE_TOO_LARGE"
    },
    "origen": "image-upload"
  },
  ...
]
```

Útil para:
- Enviar a soporte técnico
- Analizar patrones
- Reproducir bugs

### 🗑 Limpiar
Borra todos los logs del localStorage.

---

## 💾 Dónde se Guardan

Los logs se guardan en **localStorage del navegador** bajo la clave:
```javascript
localStorage.getItem('pedido_error_logs')
```

Capacidad: Últimos **50 eventos** (se descartan automáticamente los más antiguos)

Persistencia: Se mantienen entre sesiones (hasta que el usuario limpie caché)

---

## 🔧 Uso Programático

Si necesitas acceder directamente desde código:

### Registrar un error de imagen
```javascript
ErrorLoggerService.registrarErrorImagen(file, error, { detalles });
```

### Registrar un error de validación
```javascript
ErrorLoggerService.registrarErrorValidacion('cliente', '', 'Cliente vacío');
```

### Registrar un error de red
```javascript
ErrorLoggerService.registrarErrorRed('/api/endpoint', 500, 'Server Error', 2);
```

### Obtener todos los logs
```javascript
const logs = ErrorLoggerService.obtenerLogs();
```

### Obtener logs por tipo
```javascript
const errores = ErrorLoggerService.obtenerLogsPorTipo('ERROR_IMAGEN');
```

### Obtener resumen
```javascript
const resumen = ErrorLoggerService.obtenerResumen();
console.log(resumen);
// {
//   total: 45,
//   porTipo: { ERROR_IMAGEN: 8, ERROR_RED: 3, ... },
//   porOrigen: { 'image-upload': 8, 'api': 3, ... },
//   ultimasHoras24: 12,
//   ultimos30Min: 2
// }
```

### Exportar como JSON
```javascript
const json = ErrorLoggerService.exportarJSON();
console.log(json);
```

### Exportar resumen legible
```javascript
const resumen = ErrorLoggerService.exportarResumen();
console.log(resumen);
```

---

## 📊 Flujo de Captura

```
Usuario carga imagen
    ↓
ImageStorageService.agregarImagen()
    ↓
¿Error? (demasiado grande, formato inválido, etc.)
    ↓
error handler en image-management.js
    ↓
ErrorLoggerService.registrarErrorImagen() ✓ CAPTURADO
    ↓
mostrarModalError("La imagen es muy grande...")
    ↓
Usuario ve el error Y el panel diagnóstico lo registra
```

---

## 🎯 Casos de Uso

### Caso 1: Usuario reporta "No puedo guardar imágenes"

1. Abre el panel: `abrirPanelDiagnostico()`
2. Ve pestaña "Errores"
3. Copia el JSON con `📥 Descargar JSON`
4. Comparte contigo para debugging

### Caso 2: Quieres medir qué % de imágenes fallan

1. Ejecuta: `ErrorLoggerService.obtenerResumen()`
2. Ves el breakdown por tipo

### Caso 3: Investigar patrón de fallos

1. Descarga JSON del día anterior
2. Analiza qué errores son más frecuentes
3. Prioriza fixes basado en datos reales

---

## 🔐 Seguridad

- ✅ Los logs se guardan **localmente en el navegador** (no se envían al servidor)
- ✅ Cada usuario ve solo sus propios logs
- ✅ Se borran al limpiar caché/localStorage
- ✅ No contienen datos sensibles (excepto nombre de cliente, que ya es visible)

---

## 📝 Campos Capturados

### Error de Imagen
```javascript
{
  tipo: 'ERROR_IMAGEN',
  timestamp: '2026-04-23T15:30:45.123Z',
  archivo: 'foto.jpg',      // Nombre del archivo
  tamanio: 2097152,         // Bytes
  error: 'FILE_TOO_LARGE',  // Mensaje de error
  contexto: { ... },        // Detalles adicionales
  origen: 'image-upload'
}
```

### Error de Validación
```javascript
{
  tipo: 'ERROR_VALIDACION',
  timestamp: '2026-04-23T15:30:45.123Z',
  campo: 'cliente',         // Campo que falló
  valor: '',                // Valor (truncado si muy largo)
  razon: 'Cliente vacío',   // Por qué falló
  origen: 'validation'
}
```

### Error de Red
```javascript
{
  tipo: 'ERROR_RED',
  timestamp: '2026-04-23T15:30:45.123Z',
  endpoint: '/api/asesores/pedidos/borrador',
  statusCode: 500,          // HTTP status
  mensaje: 'Internal Server Error',
  intento: 2,               // Número de intento
  esReintento: true,        // ¿Fue un reintento?
  origen: 'api'
}
```

---

## ✅ Ventajas

- 📍 Diagnostica **sin depender de consola del navegador**
- 📊 Visualización clara y organizada
- 💾 Persiste entre sesiones
- 📥 Exportable para análisis
- 🔍 Filtrable por tipo de error
- ⚡ Zero performance impact (usa localStorage, no afecta UI)

---

## 🚀 Próximas Mejoras Sugeridas

1. **Enviar logs al servidor** (opcional):
   ```javascript
   // Endpoint: POST /api/diagnostico/logs
   fetch('/api/diagnostico/logs', {
       method: 'POST',
       body: ErrorLoggerService.exportarJSON()
   });
   ```

2. **Alertas automáticas**:
   ```javascript
   // Si más de 5 errores en 30 min
   if (ErrorLoggerService.obtenerLogsRecientes(0.5).length > 5) {
       notificarAdmin('Spike de errores detectado');
   }
   ```

3. **Dashboard en admin panel**:
   - Gráficos de tendencias
   - Errores por usuario
   - Tasas de éxito/fallo

---

**¡Listo! El panel está activo y capturando errores automáticamente. Abre la consola y prueba:** `abrirPanelDiagnostico()`
