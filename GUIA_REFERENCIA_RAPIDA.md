# 🎯 GUÍA RÁPIDA DE REFERENCIA - Sistema Implementado

## 🚀 En 30 Segundos

```javascript
// 1. Scripts en HTML
<script src="...cotizacion-prenda-handler.js"></script>
<script src="...cotizacion-prenda-config.js"></script>
<script src="...inicializador-origen-automatico.js"></script>

// 2. Crear PrendaEditor
const prendaEditor = new PrendaEditor({ 
    notificationService: window.notificationService 
});

// 3. Cargar prendas desde cotización
const prendas = prendaEditor.cargarPrendasDesdeCotizacion(
    arrayPrendas,
    cotizacion
);
// ✓ Automáticamente: prenda.origen = "bodega" si cotización es Reflectivo/Logo
```

---

## 📱 API Rápida

### PrendaEditor - Nuevos Métodos

| Método | Parámetros | Retorna | Descripción |
|--------|-----------|---------|-------------|
| `cargarPrendasDesdeCotizacion()` | prendas[], cotizacion | prenda[] | Cargar múltiples prendas con origen automático |
| `aplicarOrigenAutomaticoDesdeCotizacion()` | prenda | prenda | Aplicar origen a una prenda |
| `abrirModal()` | esEdicion, index, cotizacion | void | Abrir modal con cotización (NEW) |

### CotizacionPrendaHandler - Métodos

| Método | Descripción |
|--------|-------------|
| `prepararPrendaParaEdicion()` | Main: Aplicar origen automático |
| `requiereBodega()` | Verificar si tipo requiere bodega |
| `registrarTipoBodega()` | Agregar nuevo tipo |
| `obtenerTiposBodega()` | Listar tipos registrados |

### CotizacionPrendaConfig - Métodos

| Método | Descripción |
|--------|-------------|
| `inicializarConRetroalimentacion()` | Init automático con fallback |
| `inicializarDesdeAPI()` | Cargar desde `/api/tipos-cotizacion` |
| `iniciarSincronizacionAutomatica()` | Sync periódica |

---

## 🎨 Comportamiento

### Cuando cargas prendas desde cotización "Reflectivo"
```
Prenda input:  { nombre: 'Camiseta', talla: 'M' }
         ↓
         ↓ CotizacionPrendaHandler.prepararPrendaParaEdicion()
         ↓
Prenda output: { nombre: 'Camiseta', talla: 'M', origen: 'bodega' } ✅
```

### Cuando cargas prendas desde cotización "Estándar"
```
Prenda input:  { nombre: 'Pantalón', talla: 'L' }
         ↓
         ↓ CotizacionPrendaHandler.prepararPrendaParaEdicion()
         ↓
Prenda output: { nombre: 'Pantalón', talla: 'L', origen: 'confeccion' } ✅
```

### Cuando agregas prenda manualmente (sin cotización)
```
Prenda input:  { nombre: 'Chaleco', talla: 'XL' }
         ↓
         ↓ Sin cotización → sin cambios
         ↓
Prenda output: { nombre: 'Chaleco', talla: 'XL' } (sin origen asignado)
               Usuario selecciona manualmente
```

---

## 🔴 Tipos que Requieren BODEGA

| Tipo | Origen | Aplica |
|------|--------|--------|
| Reflectivo | bodega | ✅ Automático |
| Logo | bodega | ✅ Automático |
| Estándar | confeccion | ❌ Normal |
| Bordado | confeccion | ❌ Normal |
| (Otros) | confeccion | ❌ Normal |

---

## 🛠️ Setup Mínimo

### HTML
```html
<!-- Antes de </body> -->
<script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-handler.js"></script>
<script src="/js/modulos/crear-pedido/procesos/services/cotizacion-prenda-config.js"></script>
<script src="/js/modulos/crear-pedido/procesos/services/inicializador-origen-automatico.js"></script>
```

### JavaScript
```javascript
// Esto es todo lo que necesitas:
const prendas = prendaEditor.cargarPrendasDesdeCotizacion(arrayPrendas, cotizacion);
```

### Backend
```php
// GET /api/tipos-cotizacion
Route::get('/api/tipos-cotizacion', [TiposCotizacionController::class, 'index']);

// Retorna:
// { "data": [
//     { "id": 1, "nombre": "Reflectivo", "requiere_bodega": true },
//     { "id": 2, "nombre": "Logo", "requiere_bodega": true }
// ]}
```

---

## 🧪 Testing Rápido

```javascript
// En consola (F12)
debugOrigenAutomatico()              // Ver todo
testearOrigenAutomatico()            // Tests
CotizacionPrendaConfig.mostrarEstado()  // Tipos
window.verificarIntegracion()        // Checklist
```

---

## ⚡ 5 Pasos a Producción

1. **Incluir scripts** (1 min)
   ```html
   <!-- 3 scripts en HTML -->
   ```

2. **Implementar API** (10 min)
   ```php
   // GET /api/tipos-cotizacion
   ```

3. **Usar método** (1 min)
   ```javascript
   prendaEditor.cargarPrendasDesdeCotizacion(prendas, cotizacion)
   ```

4. **Testing** (5 min)
   ```javascript
   testearOrigenAutomatico()
   ```

5. **Deploy** ✅

---

## 🐛 Troubleshooting

| Error | Solución |
|-------|----------|
| Script not found | Verificar paths en HTML |
| Undefined CotizacionPrendaHandler | Incluir script antes de usarlo |
| Origen no cambia | Ejecutar `CotizacionPrendaConfig.mostrarEstado()` |
| API 404 | Implementar endpoint en backend |

---

## 📚 Documentos

| Documento | Usa para... |
|-----------|-----------|
| `QUICK_START_ORIGEN_PRENDAS.md` | Empezar rápido (5 min) |
| `RESUMEN_ORIGEN_AUTOMATICO.md` | Entender qué se hizo (10 min) |
| `GUIA_ORIGEN_AUTOMATICO_PRENDAS.md` | Referencia completa |
| `INSTRUCCIONES_INTEGRACION_HTML.js` | Cómo incluir scripts |
| `IMPLEMENTACION_COMPLETADA.md` | Cambios en prenda-editor.js |
| `API_TIPOS_COTIZACION.md` | Backend endpoint |

---

## ✅ Checklist Implementación

- [ ] Scripts incluidos
- [ ] Endpoint `/api/tipos-cotizacion` implementado
- [ ] `testearOrigenAutomatico()` pasa
- [ ] Prendas "Reflectivo" = bodega
- [ ] Prendas "Estándar" = confeccion
- [ ] BD guarda origen correcto
- [ ] Deploy a producción

---

## 💡 Ejemplo Completo

```javascript
// PASO 1: Al cargar página
document.addEventListener('DOMContentLoaded', async () => {
    // Sistema se inicializa automáticamente
    console.log('✅ Sistema listo');
});

// PASO 2: Cuando usuario selecciona cotización
document.getElementById('select-cotizacion').addEventListener('change', async (e) => {
    const response = await fetch(`/api/cotizaciones/${e.target.value}`);
    const { cotizacion, prendas } = await response.json();
    
    // Cargar prendas con origen automático
    const prendasProcesadas = prendaEditor.cargarPrendasDesdeCotizacion(
        prendas,
        cotizacion
    );
    
    // Agregar al pedido
    window.prendas = [...(window.prendas || []), ...prendasProcesadas];
    
    // Ver estadísticas
    console.log(window.obtenerEstadisticasPrendas());
});

// PASO 3: Guardar pedido
document.getElementById('btn-guardar-pedido').addEventListener('click', () => {
    // Prendas ya tienen origen correcto
    fetch('/api/pedidos', {
        method: 'POST',
        body: JSON.stringify({ prendas: window.prendas })
    });
});
```

---

## 🎯 Pasos Siguientes

```
HOY:        Incluir scripts en HTML + implementar API
MAÑANA:     Probar con datos reales
PRÓXIMA SEMANA: Deploy a producción
```

---

## 📞 Resumen

| Aspecto | Status |
|--------|--------|
| Código implementado | ✅ Listo |
| Documentación | ✅ Completa |
| Testing | ✅ Incluido |
| Backend | ⏳ Tu turno |
| HTML | ⏳ Tu turno |
| Deploy | ⏳ Tu turno |

---

**Tiempo total implementación: 20-30 minutos**  
**Complejidad: 🟢 Baja**  
**Riesgo: 🟢 Mínimo (100% retrocompatible)**

