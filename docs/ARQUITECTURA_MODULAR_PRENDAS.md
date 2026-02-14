## 📦 Arquitectura Modular - Prendas Editor (REFACTORIZADO)

### 🎯 Cambio Principal

**ANTES:**
- 1 archivo gigante: `prenda-editor.js` (~850 líneas)
- Todas las responsabilidades mezcladas
- Difícil de mantener y testear

**AHORA:**
- 10 módulos especializados (~100-150 líneas cada uno)
- Cada módulo tiene **una sola responsabilidad**
- Fácil de mantener, testear y extender

---

## 📁 Estructura Nueva

```
prendas/
├── prenda-editor-refactorizado.js          (120 líneas - ORQUESTADOR)
│
├── loaders/                                (Cargan datos EN el modal)
│   ├── prenda-editor-basicos.js           (Nombre, origen, descripción)
│   ├── prenda-editor-imagenes.js          (Imágenes)
│   ├── prenda-editor-telas.js             (Tabla de telas)
│   ├── prenda-editor-variaciones.js       (Manga, bolsillos, broche)
│   ├── prenda-editor-tallas.js            (Tarjetas de género + inputs)
│   ├── prenda-editor-colores.js           (Asignaciones por talla)
│   └── prenda-editor-procesos.js          (Reflectivo, bordado, etc.)
│
├── services/
│   └── prenda-editor-service.js           (Obtener datos del servidor)
│
└── modalHandlers/
    └── prenda-modal-manager.js            (Abrir, cerrar, limpiar modal)

lazy-loaders/
└── prenda-editor-loader-modular.js     (Carga todos los módulos)
```

---

## 🔄 Cómo Funciona

### 1️⃣ Flujo de Carga

```javascript
// Usuario hace clic en "Editar Prenda"
gestion-items-pedido.js → cargarItemEnModal()
  ↓
PrendaEditor.cargarPrendaEnModal(prenda, index)
  ↓
PrendaEditorService.obtenerConFallback(prenda)
  ├─ Si pedido existente → obtener del servidor
  └─ Si pedido nuevo → usar datos locales
  ↓
PrendaEditor._cargarTodosLosModulos(prenda)
  ├─ PrendaEditorBasicos.cargar()
  ├─ PrendaEditorImagenes.cargar()
  ├─ PrendaEditorTelas.cargar()
  ├─ PrendaEditorVariaciones.cargar()
  ├─ PrendaEditorTallas.cargar()
  ├─ PrendaEditorColores.cargar()
  └─ PrendaEditorProcesos.cargar()
  ↓
Modal completamente relleno en ~500ms
```

### 2️⃣ Responsabilidades Claras

| Módulo | Responsabilidad | Métodos |
|--------|-----------------|---------|
| **PrendaEditor** | Orquesta todo | `abrirModal()`, `cargarPrendaEnModal()`, `cerrarModal()` |
| **PrendaEditorBasicos** | Campos básicos | `cargar()`, `obtener()`, `limpiar()` |
| **PrendaEditorImagenes** | Imágenes | `cargar()`, `limpiar()` |
| **PrendaEditorTelas** | Tabla de telas | `cargar()`, `limpiar()` |
| **PrendaEditorVariaciones** | Manga, bolsillos, broche | `cargar()`, `limpiar()` |
| **PrendaEditorTallas** | Tarjetas + inputs | `cargar()`, `marcarGeneros()`, `limpiar()` |
| **PrendaEditorColores** | Colores por talla | `cargar()`, `limpiar()` |
| **PrendaEditorProcesos** | Procesos | `cargar()`, `limpiar()` |
| **PrendaEditorService** | Datos del servidor | `obtenerDelServidor()`, `obtenerConFallback()` |
| **PrendaModalManager** | UI del modal | `abrir()`, `cerrar()`, `limpiar()`, `mostrarNotificacion()` |

---

## ✨ Ventajas

### 1. **Mantenibilidad**
```javascript
// Cambiar cómo se cargan telas ? Solo editar:
// prendas/loaders/prenda-editor-telas.js
// (El resto del código no se ve afectado)
```

### 2. **Testabilidad**
```javascript
//  Ahora puedes testear cada módulo por separado:
test('PrendaEditorTelas', () => {
  const prenda = { telasAgregadas: [...] };
  PrendaEditorTelas.cargar(prenda);
  expect(document.querySelector('#tbody-telas')).toHaveChildren();
});
```

### 3. **Reutilización**
```javascript
// Puedes usar PrendaEditorTables en otro contexto:
class CotizacionEditor {
  cargarTelas(cotizacion) {
    PrendaEditorTelas.cargar(cotizacion); //  Funciona igual
  }
}
```

### 4. **Escalabilidad**
```javascript
// Agregar nueva funcionalidad? Crea un nuevo módulo:
// loaders/prenda-editor-etiquetas.js
// loaders/prenda-editor-empaque.js
// No toca código existente
```

---

## 🚀 Cómo Usar

### Opción 1: Con el nuevo loader (RECOMENDADO)

```html
<!-- En tu HTML -->
<script src="/js/lazy-loaders/prenda-editor-loader-modular.js"></script>

<script>
document.addEventListener('prendaEditorReady', () => {
  // Ya está cargado, usar normalmente:
  const editor = window.PrendaEditorLoader.getPrendaEditor();
  editor.cargarPrendaEnModal(prenda, 0);
});
</script>
```

### Opción 2: Cargar manualmente

```javascript
// Cargar módulos explícitamente
await PrendaEditorLoader.load();

// Luego usar:
const editor = new PrendaEditor({ modalId: 'mi-modal' });
await editor.cargarPrendaEnModal(prenda, 0);
```

---

##  Integración Gradual

SI TODAVÍA USAS EL VIEJO CÓDIGO:

1. Mantén el antiguo `prenda-editor.js` por ahora
2. Carga AMBOS:
   ```html
   <script src="/js/modulos/crear-pedido/prendas/prenda-editor.js"></script>
   <script src="/js/lazy-loaders/prenda-editor-refactored-loader.js"></script>
   ```

3. Usa el nuevo cuando quieras, el viejo sigue funcionando

4. Cuando todo esté probado, elimina el viejo

---

## 📊 Comparación Antes/Después

### ANTES: Monolítico
```
prenda-editor.js: 850 líneas
├─ cargarCamposBasicos()
├─ cargarImagenes()
├─ cargarTelas()
├─ cargarVariacionesEspecificas()
├─ cargarVariaciones()
├─ cargarTallasYCantidades()
├─ cargarAsignacionColoresPorTalla()
├─ cargarProcesos()
└─ ... (10 más métodos)
```

### AHORA: Modular
```
prenda-editor-refactorizado.js: 120 líneas ← SOLO ORQUESTA
├─ cargarPrendaEnModal()
├─ _cargarTodosLosModulos()
└─ _aplicarOrigenDesdeCotizacion()

+ 7 módulos especializados (~100 líneas cada uno)
+ 1 servicio
+ 1 manager de modal
```

---

## 🎯 Próximos Pasos (Opcional)

1. **Tests Unitarios**
   ```javascript
   describe('PrendaEditorTelas', () => {
     it('debe cargar telas en tabla', () => { ... });
     it('debe preservar fila de inputs', () => { ... });
   });
   ```

2. **Componentes Web (WebComponents)**
   ```javascript
   class PrendaTelasComponent extends HTMLElement { ... }
   ```

3. **TypeScript**
   ```typescript
   interface IPrendaLoader {
     carga(prenda: IPrenda): void;
     limpiar(): void;
   }
   ```

---

##  Notas Importantes

-  Todos los módulos ya incluyen **logging detallado**
-  **Sin breaking changes** - código viejo sigue funcionando
-  **Carga asíncrona** - no bloquea la UI
-  **Manejo de errores** - fallbacks automáticos
-  **Validación** - parámetros checkeados antes de usar

---

**Versión:** 1.0 - Refactorizado  
**Fecha:** Febrero 2026  
**Archivo:** `/docs/ARQUITECTURA_MODULAR_PRENDAS.md`
