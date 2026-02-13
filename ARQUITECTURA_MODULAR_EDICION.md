# 🏗️ ARQUITECTURA MODULAR - EDICIÓN COMPARTIDA DE PRENDAS

## 📋 VISIÓN GENERAL

Transformar la lógica de edición de prendas en un **servicio centralizado reutilizable** que funcione en diferentes módulos sin cambios de código.

```
┌─────────────────────────────────────────────────────────────────┐
│            APLICACIÓN MONOLÍTICA (Múltiples Módulos)           │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐           │
│  │ Crear Pedido │  │  Pedidos Edit│  │ Cotizaciones │  ...      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘           │
│         │                  │                  │                    │
│         └──────────────────┼──────────────────┘                    │
│                            │                                       │
│              ┌─────────────▼──────────────┐                       │
│              │  🔒 SERVICE CONTAINER      │                       │
│              │  (Inyección de Dependen.)  │                       │
│              └─────────────┬──────────────┘                       │
│                            │                                       │
│        ┌───────────────────┼───────────────────┐                 │
│        │                   │                   │                  │
│    ┌───▼───────┐   ┌──────▼──────┐   ┌───────▼────┐             │
│    │ Datos Service  │   Editor    │   │  Storage   │             │
│    │ (BD + Cache)   │  Service    │   │  Service   │             │
│    └───────────┘   │ (Business)  │   │(Imágenes)  │             │
│                    │             │   └────────────┘             │
│                    └─────────────┘                               │
│                            ▲                                      │
│                            │                                      │
│              ┌─────────────▼──────────────┐                      │
│              │  UI Components & Modals    │                      │
│              │  (Agnósticos del contexto) │                      │
│              └────────────────────────────┘                      │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🎯 PRINCIPIOS DE DISEÑO

### 1. **Single Responsibility Principle (SRP)**
```javascript
❌ ANTES: PrendaEditor hace TODO
  - Cargar datos
  - Transformar formatos
  - Validar
  - Renderizar UI
  - Guardar en BD
  - Manejar imágenes

✅ DESPUÉS: Cada servicio hace UNA cosa
  - PrendaDataService: Leer/escribir de BD
  - PrendaEditorService: Orquestar edición
  - PrendaValidationService: Validar datos
  - PrendaStorageService: Manejar archivos
  - PrendaUIService: Renderizar componentes
```

### 2. **Dependency Injection**
```javascript
// ❌ ANTES: Las dependencias están hardcodeadas
class PrendaEditor {
    constructor() {
        this.api = new ItemAPIService();      // Acoplado
        this.renderer = new ItemRenderer();   // Acoplado
    }
}

// ✅ DESPUÉS: Las dependencias se inyectan
class PrendaEditor {
    constructor(dependencies) {
        this.api = dependencies.api;          // Desacoplado
        this.renderer = dependencies.renderer; // Desacoplado
    }
}
```

### 3. **Context Independence**
```javascript
// El mismo código funciona en:
const service = new SharedPrendaEditorService(deps);

// Contexto 1: Crear-nuevo
service.abrirEditorParaCreacion(prendaLocal);

// Contexto 2: Edición pedido
service.abrirEditorParaEdicion(prendaId);

// Contexto 3: Duplicar prenda
service.abrirEditorParaDuplicar(prendaOrigen);
```

### 4. **Data Normalization**
```javascript
// TODOS los datos se normalizan a un formato único
interface NormalizedPrenda {
    id?: number;              // undefined si es creación
    nombre: string;
    descripcion: string;
    origen: 'bodega' | 'confeccion';
    tallas: {
        genero: string;
        talla: string;
        cantidad: number;
    }[];
    telas: {
        id?: number;
        tela_id: number;
        color_id?: number;
        referencia: string;
    }[];
    procesos: ProcesoNormalizado[];
    imagenes: ImagenNormalizada[];
    variantes: VarianteNormalizada[];
}
```

---

## 🏛️ ARQUITECTURA DE SERVICIOS

### Nivel 1: Data Access Layer (Datos)

```javascript
/**
 * Servicio compartido de DATOS de prendas
 * Responsable: Leer/escribir en BD, cachear, transformar formatos
 */
class SharedPrendaDataService {
    constructor(config) {
        this.apiEndpoint = config.apiEndpoint || '/api/prendas';
        this.cache = new Map();
        this.formatDetector = new FormatDetector();
    }

    // LECTURA
    async obtenerPrendPorId(id) {
        // Verificar cache
        if (this.cache.has(id)) {
            return this.cache.get(id);
        }

        // Fetch desde API
        const response = await fetch(`${this.apiEndpoint}/${id}`);
        const resultado = await response.json();
        
        // Transformar a formato estndar
        const prendaNormalizada = this.normalizarDesdeAPI(resultado.data);
        
        // Cachear
        this.cache.set(id, prendaNormalizada);
        
        return prendaNormalizada;
    }

    // ESCRITURA
    async guardarPrenda(prendata, options = {}) {
        const metodo = prendata.id ? 'PATCH' : 'POST';
        const endpoint = prendata.id 
            ? `${this.apiEndpoint}/${prendata.id}`
            : this.apiEndpoint;

        const response = await fetch(endpoint, {
            method: metodo,
            body: JSON.stringify(prendata)
        });

        const resultado = await response.json();

        // Limpiar cache
        if (prendata.id) {
            this.cache.delete(prendata.id);
        }

        return this.normalizarDesdeAPI(resultado.data);
    }

    // TRANSFORMACIÓN
    normalizarDesdeAPI(datos) {
        // Detectar formato automáticamente
        const formato = this.formatDetector.detectar(datos);
        
        // Aplicar transformer correspondiente
        let normalizado;
        if (formato === 'ANTIGUO') {
            normalizado = this.transformarDesdeAntiguo(datos);
        } else if (formato === 'NUEVO') {
            normalizado = this.transformarDesdeNuevo(datos);
        } else {
            normalizado = this.createDefault();
        }

        return normalizado;
    }

    // Otros métodos...
}
```

### Nivel 2: Business Logic Layer (Editor)

```javascript
/**
 * Servicio centralizado de EDICIÓN de prendas
 * Responsable: Orquestar el flujo de edición (creación, lectura, actualización, validación)
 * AGNÓSTICO del contexto → Funciona en cualquier lugar
 */
class SharedPrendaEditorService {
    constructor(dependencies) {
        this.dataService = dependencies.dataService;
        this.storageService = dependencies.storageService;
        this.validationService = dependencies.validationService;
        this.eventBus = dependencies.eventBus || new EventBus();
        this.cache = {
            prendaActual: null,
            cambiosPendientes: new Map(),
            estado: 'idle' // idle | editando | guardando
        };
    }

    /**
     * 🎯 MÉTODO PRINCIPAL: Abrir editor
     * Se usa en TODOS los contextos
     */
    async abrirEditor(config) {
        console.log('[SharedPrendaEditor] Abriendo editor:', config);
        
        config = {
            modo: config.modo || 'crear',  // crear | editar | duplicar
            prendaId: config.prendaId,
            prendaLocal: config.prendaLocal,
            contexto: config.contexto,  // Ej: 'crear-nuevo', 'pedidos', 'cotizaciones'
            onGuardar: config.onGuardar,
            onCancelar: config.onCancelar,
            ...config
        };

        try {
            this.cache.estado = 'editando';
            this.eventBus.emit('editor:abierto', config);

            let prenda;
            
            // 1️⃣ CARGAR DATOS según modo
            switch(config.modo) {
                case 'crear':
                    prenda = config.prendaLocal || this.crearPrendaVacia();
                    break;
                case 'editar':
                    prenda = await this.dataService.obtenerPrendPorId(config.prendaId);
                    break;
                case 'duplicar':
                    const original = await this.dataService.obtenerPrendPorId(config.prendaId);
                    prenda = { ...original, id: null }; // Crear nuevo sin ID
                    break;
            }

            // 2️⃣ GUARDAR EN CONTEXTO
            this.cache.prendaActual = prenda;
            window.prendaEnEdicion = { ...prenda }; // Compatibilidad backward

            // 3️⃣ EMITIR EVENTO para que UI se cargue
            this.eventBus.emit('editor:datos-cargados', {
                prenda,
                modo: config.modo
            });

            return prenda;

        } catch (error) {
            this.eventBus.emit('editor:error', error);
            throw error;
        }
    }

    /**
     * Guardar cambios (create/update)
     */
    async guardarCambios() {
        try {
            if (!this.cache.prendaActual) {
                throw new Error('No hay prenda en edición');
            }

            // 1️⃣ RECOLECTAR datos del modal
            const datos = this.recolectarDatosDelModal();

            // 2️⃣ VALIDAR
            const erroresValidacion = this.validationService.validar(datos);
            if (erroresValidacion.length > 0) {
                this.eventBus.emit('editor:error-validacion', erroresValidacion);
                throw new Error('Datos inválidos');
            }

            // 3️⃣ PROCESAR imágenes (si hay cambios)
            const datosConImagenes = await this.procesarImagenesCambios(datos);

            // 4️⃣ GUARDAR en BD
            this.cache.estado = 'guardando';
            const prendaGuardada = await this.dataService.guardarPrenda(datosConImagenes);

            // 5️⃣ ACTUALIZAR cache
            this.cache.prendaActual = prendaGuardada;
            this.cache.cambiosPendientes.clear();

            // 6️⃣ NOTIFICAR
            this.eventBus.emit('editor:guardado', prendaGuardada);

            return prendaGuardada;

        } catch (error) {
            this.cache.estado = 'editando';
            this.eventBus.emit('editor:error', error);
            throw error;
        }
    }

    /**
     * Cancelar edición
     */
    cancelarEdicion() {
        this.cache.prendaActual = null;
        this.cache.cambiosPendientes.clear();
        this.cache.estado = 'idle';
        this.eventBus.emit('editor:cancelado');
    }

    // Otros métodos...
}
```

### Nivel 3: Storage Service (Imágenes)

```javascript
/**
 * Servicio centralizado para IMÁGENES
 * Responsable: Upload, delete, preview, transformación
 */
class SharedPrendaStorageService {
    constructor(config) {
        this.storageEndpoint = config.storageEndpoint || '/api/storage';
        this.maxFileSize = config.maxFileSize || 5 * 1024 * 1024;
    }

    /**
     * Procesar cambios de imágenes
     * Retorna: {agregar: [], eliminar: [], mantener: []}
     */
    async procesarCambiosImagenes(imagenesActuales, imagenesNuevas) {
        const cambios = {
            agregar: [],    // Nuevos archivos a subir
            eliminar: [],   // IDs de imágenes existentes a borrar
            mantener: []    // IDs que se mantienen
        };

        // Identificar qué mantener vs eliminar
        const idsNuevos = imagenesNuevas
            .filter(img => img.id)
            .map(img => img.id);

        cambios.eliminar = imagenesActuales
            .filter(img => img.id && !idsNuevos.includes(img.id))
            .map(img => img.id);

        cambios.mantener = imagenesActuales
            .filter(img => idsNuevos.includes(img.id));

        // Archivos nuevos a subir
        cambios.agregar = imagenesNuevas
            .filter(img => !img.id && img.archivo); // Sin ID = nuevo

        return cambios;
    }

    /**
     * Subir archivos nuevos
     */
    async subirImagenes(archivos) {
        const urls = [];

        for (const archivo of archivos) {
            // Validar
            if (!this.validarArchivo(archivo)) {
                throw new Error(`Archivo inválido: ${archivo.name}`);
            }

            // Convertir a FormData (si es necesario)
            const formData = new FormData();
            formData.append('imagen', archivo);

            // Subir
            const response = await fetch(this.storageEndpoint, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Error subiendo ${archivo.name}`);
            }

            const resultado = await response.json();
            urls.push(resultado.url);
        }

        return urls;
    }

    /**
     * Eliminar imágenes
     */
    async eliminarImagenes(ids) {
        for (const id of ids) {
            await fetch(`${this.storageEndpoint}/${id}`, {
                method: 'DELETE'
            });
        }
    }

    // Otros métodos...
}
```

### Nivel 4: UI Service (Presentación)

```javascript
/**
 * Servicio de RENDERIZACIÓN agnóstico del contexto
 * Permite reutilizar el mismo modal/componentes en cualquier lugar
 */
class SharedPrendaUIService {
    constructor(config) {
        this.modalSelector = config.modalSelector || '#modal-agregar-prenda-nueva';
        this.eventBus = config.eventBus;
    }

    /**
     * Renderizar formulario con datos precargados
     */
    renderizarFormulario(prenda) {
        // Inyectar datos en inputs
        this.llenarInputs({
            nombre: prenda.nombre,
            descripcion: prenda.descripcion,
            origen: prenda.origen
        });

        // Renderizar tallas
        this.renderizarTallas(prenda.tallas);

        // Renderizar telas
        this.renderizarTelas(prenda.telas);

        // Renderizar procesos
        this.renderizarProcesos(prenda.procesos);

        // Renderizar imágenes
        this.renderizarImagenes(prenda.imagenes);

        // Mostrar modal
        this.mostrarModal();
    }

    /**
     * Recolectar datos del formulario
     */
    recolectarDatos() {
        return {
            nombre: document.getElementById('nueva-prenda-nombre').value,
            descripcion: document.getElementById('nueva-prenda-descripcion').value,
            origen: document.getElementById('nueva-prenda-origen-select').value,
            tallas: this.recolectarTallas(),
            telas: this.recolectarTelas(),
            procesos: this.recolectarProcesos(),
            imagenes: this.recolectarImagenes()
        };
    }

    // Otros métodos...
}
```

---

## 🔌 INTEGRATION PATTERN: Service Container

```javascript
/**
 * Contenedor de Services - Punto único de inicialización
 * Se crea una vez al cargar la aplicación
 */
class PrendaServiceContainer {
    constructor() {
        this.services = {};
        this.initialized = false;
    }

    /**
     * Inicializar todos los servicios
     */
    async initialize() {
        if (this.initialized) return;

        console.log('[ServiceContainer] Inicializando servicios de prendas...');

        // 1️⃣ Data Service
        this.services.data = new SharedPrendaDataService({
            apiEndpoint: '/api/prendas'
        });

        // 2️⃣ Storage Service
        this.services.storage = new SharedPrendaStorageService({
            storageEndpoint: '/api/storage'
        });

        // 3️⃣ Validation Service
        this.services.validation = new SharedPrendaValidationService({
            rules: this.getValidationRules()
        });

        // 4️⃣ Event Bus
        this.services.eventBus = new EventBus();

        // 5️⃣ Editor Service (orquestador principal)
        this.services.editor = new SharedPrendaEditorService({
            dataService: this.services.data,
            storageService: this.services.storage,
            validationService: this.services.validation,
            eventBus: this.services.eventBus
        });

        // 6️⃣ UI Service
        this.services.ui = new SharedPrendaUIService({
            modalSelector: '#modal-agregar-prenda-nueva',
            eventBus: this.services.eventBus
        });

        // 7️⃣ Conectar eventos para sincrozar UI con servicios
        this.conectarEventos();

        this.initialized = true;
        console.log('[ServiceContainer] ✓ Servicios inicializados');
    }

    /**
     * Obtener un servicio específico
     */
    getService(nombre) {
        if (!this.initialized) {
            throw new Error('ServiceContainer no inicializado. Llama initialize() primero.');
        }
        if (!this.services[nombre]) {
            throw new Error(`Servicio no encontrado: ${nombre}`);
        }
        return this.services[nombre];
    }

    /**
     * Conectar eventos para sincronizar UI con business logic
     */
    conectarEventos() {
        const { eventBus, editor, ui } = this.services;

        // Cuando el editor carga datos, renderizar en UI
        eventBus.on('editor:datos-cargados', (datos) => {
            ui.renderizarFormulario(datos.prenda);
        });

        // Cuando hay error de validación, mostrar en UI
        eventBus.on('editor:error-validacion', (errores) => {
            ui.mostrarErroresValidacion(errores);
        });

        // Cuando se guarda exitosamente, cerrar modal
        eventBus.on('editor:guardado', () => {
            ui.cerrarModal();
        });
    }

    getValidationRules() {
        return {
            nombre: { required: true, minLength: 3 },
            descripcion: { required: false },
            origen: { required: true, enum: ['bodega', 'confeccion'] },
            tallas: { required: true, minItems: 1 },
            telas: { required: false }
        };
    }
}

// Instancia global única
window.prendasServiceContainer = new PrendaServiceContainer();
```

---

## 💻 USO EN DIFERENTES MÓDULOS

### Ejemplo 1: Crear-Nuevo

```javascript
// crear-nuevo.js
async function abrirEditorAgregarPrenda() {
    const container = window.prendasServiceContainer;
    const editor = container.getService('editor');

    // Preparar datos locales
    const prendaLocal = {
        nombre: '',
        descripcion: '',
        origen: 'confeccion',
        tallas: [],
        telas: [],
        procesos: [],
        imagenes: []
    };

    // Abrir editor (MISMO código en todos lados)
    const prenda = await editor.abrirEditor({
        modo: 'crear',
        prendaLocal,
        contexto: 'crear-nuevo',
        onGuardar: async (prendaGuardada) => {
            // Agregar a lista local
            window.datosCreacionPedido.prendas.push(prendaGuardada);
            actualizarTabla();
        }
    });
}
```

### Ejemplo 2: Edición Pedidos

```javascript
// pedidos-editable.js
async function abrirEditorEditarPrenda(prendaId) {
    const container = window.prendasServiceContainer;
    const editor = container.getService('editor');

    // Abrir editor (MISMO código que en crear-nuevo!)
    const prenda = await editor.abrirEditor({
        modo: 'editar',
        prendaId,  // Solo necesita el ID
        contexto: 'pedidos-editable',
        onGuardar: async (prendaGuardada) => {
            // Actualizar en tablaLocal
            const index = window.datosEdicionPedido.prendas.findIndex(
                p => p.id === prendaGuardada.id
            );
            if (index >= 0) {
                window.datosEdicionPedido.prendas[index] = prendaGuardada;
            }
            actualizarTabla();
        }
    });
}
```

### Ejemplo 3: Cotizaciones

```javascript
// cotizaciones.js
async function duplicarPrendaDeCotizacion(prendaOriginalId) {
    const container = window.prendasServiceContainer;
    const editor = container.getService('editor');

    // Abrir editor en modo duplicar
    const prendaDuplicada = await editor.abrirEditor({
        modo: 'duplicar',
        prendaId: prendaOriginalId,
        contexto: 'cotizaciones',
        onGuardar: async (prendaGuardada) => {
            // Agregar a cotización actual
            window.cotizacionActual.prendas.push(prendaGuardada);
            actualizarListaCotizacion();
        }
    });
}
```

---

## 🔗 VENTAJAS DEL PATRÓN

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Código duplicado** | ❌ 30% repetido en créar/editar | ✅ 0%, un solo flujo |
| **Cambios de lógica** | ❌ Cambiar en 3-5 lugares | ✅ Un solo lugar |
| **Nuevo módulo** | ❌ Reimplementar todo | ✅ 5 líneas de código |
| **Testing** | ❌ Difícil (acoplado a UI/API) | ✅ Fácil (servicios aislados) |
| **Mantenimiento** | ❌ Alto (lógica dispersa) | ✅ Bajo (servicio centralizado) |
| **Escalabilidad** | ❌ Limitada | ✅ Ilimitada |

---

## 📊 DIAGRAMA DE FLUJO UNIFICADO

```
┌─────────────────────────────────────────────────────────────┐
│ CUALQUIER MÓDULO (crear-nuevo, edición, cotizaciones, etc) │
└────────────────┬────────────────────────────────────────────┘
                 │
                 │ module.abrirEditor({modo, prendaId, ...})
                 ▼
    ┌────────────────────────────────────┐
    │  SharedPrendaEditorService         │
    │  (Orquestador Central)             │
    └──────┬─────────────────┬───────────┘
           │                 │
           │ Modo: CREAR     │ Modo: EDITAR/DUPLICAR
           ▼                 ▼
    ┌──────────────┐  ┌──────────────────┐
    │ Crear vacía  │  │ DataService      │
    │              │  │ .obtenerPorId()  │
    └──────┬───────┘  └──────┬───────────┘
           │                 │
           └────────┬────────┘
                    │
                    ▼
        ┌───────────────────────┐
        │ Normalizar datos      │
        │ (único formato)       │
        └───────────┬───────────┘
                    │
                    ▼
        ┌───────────────────────┐
        │ eventBus.emit(        │
        │   'datos-cargados'    │
        │ )                     │
        └───────────┬───────────┘
                    │
                    ▼
        ┌───────────────────────┐
        │ UIService             │
        │ .renderForm()         │
        └───────────┬───────────┘
                    │
        ┌───────────▼───────────┐
        │ Usuario edita         │
        │ Interactúa con modal  │
        └───────────┬───────────┘
                    │
                    ▼
        ┌───────────────────────┐
        │ Usuario guarda        │
        │ .guardarCambios()     │
        └───────────┬───────────┘
                    │
                    ▼
        ┌───────────────────────┐
        │ Recolectar datos      │
        │ Validar               │
        │ Procesar imágenes     │
        └───────────┬───────────┘
                    │
                    ▼
        ┌───────────────────────┐
        │ DataService           │
        │ .guardarPrenda()      │
        │ (POST o PATCH)        │
        └───────────┬───────────┘
                    │
                    ▼
        ┌───────────────────────┐
        │ eventBus.emit(        │
        │   'guardado'          │
        │ )                     │
        └───────────┬───────────┘
                    │
                    ▼
        ┌───────────────────────┐
        │ Cerrar modal          │
        │ Actualizar módulo     │
        │ Callback: onGuardar() │
        └───────────────────────┘
```

---

## 📦 ARCHIVOS A CREAR

```
/public/js/servicios/shared/
├── shared-prenda-editor-service.js      ← Orquestador principal
├── shared-prenda-data-service.js        ← Acceso a datos
├── shared-prenda-storage-service.js     ← Imágenes
├── shared-prenda-validation-service.js  ← Validación
├── shared-prenda-ui-service.js          ← Componentes UI
├── prenda-service-container.js          ← Contenedor de inyección
├── event-bus.js                         ← Sistema de eventos
└── format-detector.js                   ← Detección de formatos
```

---

## ✅ CHECKLIST DE MIGRACIÓN

- [ ] Crear carpeta `/public/js/servicios/shared/`
- [ ] Implementar EventBus
- [ ] Implementar FormatDetector
- [ ] Implementar SharedPrendaDataService
- [ ] Implementar SharedPrendaStorageService
- [ ] Implementar SharedPrendaValidationService
- [ ] Implementar SharedPrendaUIService
- [ ] Implementar SharedPrendaEditorService
- [ ] Implementar ServiceContainer
- [ ] Actualizar crear-nuevo.js
- [ ] Actualizar pedidos-editable.js
- [ ] Deprecar prenda-editor-legacy.js (backward compat)
- [ ] Testing en múltiples módulos
- [ ] Documentación de uso
- [ ] Training al equipo

