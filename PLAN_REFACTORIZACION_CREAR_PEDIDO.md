# Plan de Refactorización: crear-pedido-editable.js

## 📊 Estado Actual
- **Archivo:** `public/js/crear-pedido-editable.js`
- **Líneas:** 4533
- **Problema:** Archivo monolítico con múltiples responsabilidades

## 🎯 Objetivo
Dividir en módulos especializados siguiendo Single Responsibility Principle

## 📁 Estructura Propuesta

```
public/js/
├── services/
│   ├── image-service.js              ✅ COMPLETADO
│   ├── api-service.js                ⬜ CREAR - Llamadas al backend
│   ├── validation-service.js         ⬜ CREAR - Validaciones cliente
│   └── state-service.js              ⬜ CREAR - Gestión de estado global
│
├── components/
│   ├── prenda-component.js           ⬜ CREAR - Renderizado de prendas
│   ├── talla-component.js            ⬜ CREAR - Gestión de tallas
│   ├── tela-component.js             ⬜ CREAR - Gestión de telas
│   ├── logo-component.js             ⬜ CREAR - Gestión de logos
│   └── reflectivo-component.js       ⬜ CREAR - Gestión de reflectivos
│
├── utils/
│   ├── dom-helpers.js                ⬜ CREAR - Helpers DOM
│   ├── formatters.js                 ⬜ CREAR - Formateo de datos
│   └── constants.js                  ⬜ CREAR - Constantes globales
│
└── crear-pedido-editable.js          🔄 REFACTORIZAR - Orquestador principal
```

## 🔍 Análisis de Secciones a Extraer

### 1. **API Service** (Prioridad: ALTA)
**Líneas a extraer:** ~200 líneas
**Responsabilidad:** Comunicación con backend

**Funciones:**
- `cargarPrendasDesdeCotizacion()` - línea 274
- Fetch a `/obtener-datos-cotizacion/`
- Fetch a `/crear-desde-cotizacion/`
- Manejo de respuestas y errores

**Beneficio:** Centralizar todas las llamadas API, fácil de testear

---

### 2. **Validation Service** (Prioridad: ALTA)
**Líneas a extraer:** ~150 líneas
**Responsabilidad:** Validaciones del lado del cliente

**Funciones:**
- Validar cantidades por talla
- Validar datos de prenda
- Validar imágenes (ya parcialmente en image-service)
- Validar formulario completo antes de enviar

**Beneficio:** Reutilizable, testeable, separar lógica de validación

---

### 3. **State Service** (Prioridad: ALTA)
**Líneas a extraer:** ~100 líneas
**Responsabilidad:** Gestión de estado global

**Variables globales actuales:**
```javascript
let tallasDisponiblesCotizacion = [];
let currentLogoCotizacion = null;
let currentEspecificaciones = null;
let currentEsReflectivo = false;
let currentDatosReflectivo = null;
let currentEsLogo = false;
let currentTipoCotizacion = 'P';
window.prendasCargadas = [];
window.prendasFotosNuevas = [];
window.telasFotosNuevas = [];
```

**Propuesta:**
```javascript
class PedidoStateManager {
    constructor() {
        this.cotizacion = null;
        this.prendas = [];
        this.tipo = 'P';
        this.fotosNuevas = {};
        // ...
    }
    
    setPrendas(prendas) { }
    getPrendas() { }
    addPrenda(prenda) { }
    removePrenda(index) { }
    // ...
}
```

**Beneficio:** Estado predecible, fácil de debuggear, evitar bugs de estado

---

### 4. **Talla Component** (Prioridad: MEDIA)
**Líneas a extraer:** ~400 líneas
**Responsabilidad:** Todo lo relacionado con tallas

**Funciones:**
- `mostrarModalAgregarTalla()` - línea 3953
- `agregarTallaAlFormulario()` - línea 4007
- `agregarTallaParaGenero()` - línea 4261
- `seleccionarTallasManual()` - línea 4387
- `seleccionarTallasRango()` - línea 4456
- `eliminarTallaDelGenero()` - línea 4584
- `renderizarTallasDelGenero()` - línea 4139

**Beneficio:** Aislar lógica compleja de tallas, más fácil de mantener

---

### 5. **Prenda Component** (Prioridad: MEDIA)
**Líneas a extraer:** ~800 líneas
**Responsabilidad:** Renderizado y gestión de prendas

**Funciones:**
- `renderizarPrendasEditables()` - línea 459
- `eliminarPrendaDelPedido()` - línea 53
- Renderizado de variaciones
- Renderizado de telas
- Renderizado de fotos

**Beneficio:** Componente reutilizable, más fácil de testear

---

### 6. **DOM Helpers** (Prioridad: BAJA)
**Líneas a extraer:** ~100 líneas
**Responsabilidad:** Utilidades DOM

**Funciones:**
- Selección de elementos
- Creación de elementos
- Manipulación de clases
- Event listeners helpers

---

## 🚀 Plan de Ejecución (Fases)

### **Fase 1: Servicios Core** (Día 1)
1. ✅ `image-service.js` - COMPLETADO
2. ⬜ `api-service.js` - Extraer llamadas API
3. ⬜ `state-service.js` - Gestión de estado
4. ⬜ `validation-service.js` - Validaciones

**Resultado:** Reducir ~450 líneas del archivo principal

---

### **Fase 2: Componentes Principales** (Día 2)
5. ⬜ `talla-component.js` - Gestión de tallas
6. ⬜ `prenda-component.js` - Renderizado de prendas
7. ⬜ `tela-component.js` - Gestión de telas

**Resultado:** Reducir ~1200 líneas adicionales

---

### **Fase 3: Componentes Secundarios** (Día 3)
8. ⬜ `logo-component.js` - Gestión de logos
9. ⬜ `reflectivo-component.js` - Gestión de reflectivos
10. ⬜ `dom-helpers.js` - Utilidades DOM

**Resultado:** Reducir ~600 líneas adicionales

---

### **Fase 4: Refactorización Final** (Día 4)
11. ⬜ Actualizar `crear-pedido-editable.js` como orquestador
12. ⬜ Agregar imports/scripts en vista
13. ⬜ Testing completo
14. ⬜ Documentación

**Resultado:** Archivo principal ~1500 líneas (reducción del 67%)

---

## 📝 Ejemplo: API Service

```javascript
// public/js/services/api-service.js

class ApiService {
    constructor() {
        this.baseUrl = '/asesores/pedidos-produccion';
        this.csrfToken = this.getCsrfToken();
    }

    getCsrfToken() {
        return document.querySelector('input[name="_token"]')?.value;
    }

    async obtenerDatosCotizacion(cotizacionId) {
        const response = await fetch(`${this.baseUrl}/obtener-datos-cotizacion/${cotizacionId}`);
        if (!response.ok) throw new Error('Error al obtener datos');
        return await response.json();
    }

    async crearPedidoDesdeCotizacion(cotizacionId, data) {
        const response = await fetch(`${this.baseUrl}/crear-desde-cotizacion/${cotizacionId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) throw new Error('Error al crear pedido');
        return await response.json();
    }

    async crearPedidoSinCotizacion(data) {
        const response = await fetch(`${this.baseUrl}/crear-sin-cotizacion`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) throw new Error('Error al crear pedido');
        return await response.json();
    }
}

window.ApiService = new ApiService();
```

---

## 📝 Ejemplo: State Service

```javascript
// public/js/services/state-service.js

class PedidoStateManager {
    constructor() {
        this.reset();
    }

    reset() {
        this.cotizacion = {
            id: null,
            numero: null,
            cliente: null,
            asesora: null,
            formaPago: null
        };
        
        this.prendas = [];
        this.prendasEliminadas = new Set();
        
        this.tipo = 'P'; // P, L, PL, RF
        this.esReflectivo = false;
        this.esLogo = false;
        
        this.tallasDisponibles = [];
        this.fotosNuevas = {
            prendas: {},
            telas: {},
            logos: [],
            reflectivos: []
        };
        
        this.logo = null;
        this.especificaciones = null;
        this.datosReflectivo = null;
    }

    // Cotización
    setCotizacion(data) {
        this.cotizacion = { ...this.cotizacion, ...data };
    }

    getCotizacion() {
        return this.cotizacion;
    }

    // Prendas
    setPrendas(prendas) {
        this.prendas = prendas;
    }

    getPrendas() {
        return this.prendas.filter((_, idx) => !this.prendasEliminadas.has(idx));
    }

    addPrenda(prenda) {
        this.prendas.push(prenda);
        return this.prendas.length - 1;
    }

    removePrenda(index) {
        this.prendasEliminadas.add(index);
    }

    getPrenda(index) {
        return this.prendas[index];
    }

    updatePrenda(index, data) {
        this.prendas[index] = { ...this.prendas[index], ...data };
    }

    // Fotos
    addFotoPrenda(prendaIndex, foto) {
        if (!this.fotosNuevas.prendas[prendaIndex]) {
            this.fotosNuevas.prendas[prendaIndex] = [];
        }
        this.fotosNuevas.prendas[prendaIndex].push(foto);
    }

    getFotosPrenda(prendaIndex) {
        return this.fotosNuevas.prendas[prendaIndex] || [];
    }

    // Tipo
    setTipo(tipo) {
        this.tipo = tipo;
        this.esReflectivo = tipo === 'RF';
        this.esLogo = tipo === 'L' || tipo === 'PL';
    }

    getTipo() {
        return this.tipo;
    }

    // Estado para debugging
    getState() {
        return {
            cotizacion: this.cotizacion,
            prendas: this.getPrendas(),
            tipo: this.tipo,
            fotosNuevas: this.fotosNuevas
        };
    }
}

window.PedidoState = new PedidoStateManager();
```

---

## ✅ Checklist de Refactorización

### Antes de Extraer un Módulo:
- [ ] Identificar todas las funciones relacionadas
- [ ] Identificar dependencias (qué necesita)
- [ ] Identificar dependientes (quién lo usa)
- [ ] Crear tests si es posible

### Al Crear el Módulo:
- [ ] Crear archivo en carpeta correcta
- [ ] Documentar con JSDoc
- [ ] Exportar a window para compatibilidad
- [ ] Agregar manejo de errores

### Después de Extraer:
- [ ] Actualizar archivo principal
- [ ] Agregar script a vista .blade.php
- [ ] Probar funcionalidad
- [ ] Actualizar documentación

---

## 🎯 Métricas de Éxito

| Métrica | Antes | Meta | 
|---------|-------|------|
| Líneas totales | 4533 | ~1500 |
| Funciones por archivo | ~80 | ~15 |
| Responsabilidades | Múltiples | 1 por módulo |
| Testeable | ❌ | ✅ |
| Mantenible | ❌ | ✅ |

---

## 📚 Próximos Pasos

1. **Empezar con Fase 1** - Servicios Core
2. **Crear api-service.js** primero (más impacto)
3. **Probar cada módulo** antes de continuar
4. **Documentar cambios** en cada paso

---

**Última actualización:** 12 de enero de 2026  
**Estado:** 🟡 En progreso - Fase 1 iniciada
