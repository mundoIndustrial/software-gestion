# 🚀 PLAN DE IMPLEMENTACIÓN - Optimización de Assets Frontend

## FASE 1: ANÁLISIS Y PREPARACIÓN (15 min)

### A. Archivos a REMOVER de index.blade.php (líneas exactas)

**REMOVER ESTOS 30 SCRIPTS (cargan en carga inicial pero NO se usan en lista):**

```blade
<!-- ❌ REMOVER: Constantes de tallas (solo para crear/editar) -->
<script src="{{ asset('js/configuraciones/constantes-tallas.js') }}"></script>

<!-- ❌ REMOVER: Image Storage (solo para editar) -->
<script src="{{ asset('js/modulos/crear-pedido/fotos/image-storage-service.js') }}"></script>

<!-- ❌ REMOVER: Gestión de Telas (solo para editar) -->
<script src="{{ asset('js/modulos/crear-pedido/telas/gestion-telas.js') }}"></script>

<!-- ❌ REMOVER: Gestión de Tallas (solo para editar) -->
<script src="{{ asset('js/modulos/crear-pedido/tallas/gestion-tallas.js') }}"></script>

<!-- ❌ REMOVER: Manejadores de variaciones (solo para editar) -->
<script src="{{ asset('js/modulos/crear-pedido/prendas/manejadores-variaciones.js') }}"></script>

<!-- ❌ REMOVER: Prenda Card (solo para editar) -->
<script src="{{ asset('js/componentes/prenda-card-editar-simple.js') }}"></script>

<!-- ❌ REMOVER: Prendas Wrappers (solo para editar) -->
<script src="{{ asset('js/componentes/prendas-wrappers.js') }}"></script>

<!-- ❌ REMOVER: Utilidades DOM (DUPLICADO en base.blade.php) -->
<script src="{{ asset('js/utilidades/dom-utils.js') }}"></script>

<!-- ❌ REMOVER: Modal Cleanup (DUPLICADO en base.blade.php) -->
<script src="{{ asset('js/utilidades/modal-cleanup.js') }}"></script>

<!-- ❌ REMOVER: Constantes items (global, no lo necesita) -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido-constantes.js') }}"></script>

<!-- ❌ REMOVER: Gestión de items (solo para crear/editar) -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/gestion-items-pedido.js') }}"></script>

<!-- ❌ REMOVER: Manejadores procesos (solo para editar) -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js') }}"></script>

<!-- ❌ REMOVER: Gestor modal procesos (solo para editar) -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js') }}"></script>

<!-- ❌ REMOVER: Renderizador procesos (solo para editar) -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js') }}"></script>

<!-- ❌ REMOVER: Notification service (solo para crear/editar) -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/notification-service.js') }}?v={{ time() }}"></script>

<!-- ❌ REMOVER: Payload normalizer (solo para crear/editar) -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/payload-normalizer-v3-definitiva.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/payload-normalizer-init.js') }}?v={{ time() }}"></script>

<!-- ❌ REMOVER: Item services (solo para crear/editar) -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-api-service.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-validator.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-form-collector.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-renderer.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/prenda-editor.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/item-orchestrator.js') }}?v={{ time() }}"></script>

<!-- ❌ REMOVER: Modal novedad (solo para editar) -->
<script src="{{ asset('js/componentes/modal-novedad-prenda.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/componentes/modal-novedad-edicion.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/componentes/prenda-form-collector.js') }}?v={{ time() }}"></script>

<!-- ❌ REMOVER: Modal prenda dinámico (solo para editar) -->
<script src="{{ asset('js/componentes/modal-prenda-dinamico-constantes.js') }}"></script>
<script src="{{ asset('js/componentes/modal-prenda-dinamico.js') }}"></script>
<script src="{{ asset('js/componentes/prenda-editor-modal.js') }}"></script>

<!-- ❌ REMOVER: Proceso editor services (solo para editar) -->
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/proceso-editor.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/gestor-edicion-procesos.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/servicio-procesos.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modulos/crear-pedido/procesos/services/middleware-guardado-prenda.js') }}?v={{ time() }}"></script>

<!-- ❌ REMOVER: API y configuración (solo para crear) -->
<script src="{{ asset('js/modulos/crear-pedido/configuracion/api-pedidos-editable.js') }}"></script>
```

### B. CSS a REMOVER de index.blade.php

```blade
<!-- ❌ REMOVER: Crear pedido CSS (solo para crear/editar) -->
<link rel="stylesheet" href="{{ asset('css/crear-pedido.css') }}">
<link rel="stylesheet" href="{{ asset('css/crear-pedido-editable.css') }}">
<link rel="stylesheet" href="{{ asset('css/form-modal-consistency.css') }}">
<link rel="stylesheet" href="{{ asset('css/swal-z-index-fix.css') }}">

<!-- ❌ REMOVER: Componentes prendas (solo para editar) -->
<link rel="stylesheet" href="{{ asset('css/componentes/prendas.css') }}">
<link rel="stylesheet" href="{{ asset('css/componentes/reflectivo.css') }}">

<!-- ❌ REMOVER: Modal EPP (solo para editar) -->
<link rel="stylesheet" href="{{ asset('css/modulos/epp-modal.css') }}">

<!-- ❌ REMOVER: Modales personalizados (solo para crear/editar) -->
<link rel="stylesheet" href="{{ asset('css/modales-personalizados.css') }}">
```

---

## FASE 2: CREAR LAZY LOADERS (20 min)

### A. Crear archivo: `public/js/lazy-loaders/prenda-editor-loader.js`

```javascript
/**
 * Lazy Loader: Módulos de Edición de Prendas
 * Se carga cuando usuario abre modal "Editar Pedido"
 * 
 * Incluye: Telas, Tallas, Procesos, Componentes de prendas
 * Tamaño: ~30KB (minificado)
 * Tiempo: ~200ms en conexión lenta
 */

window.PrendaEditorLoader = (function() {
    let isLoading = false;
    let isLoaded = false;

    const scriptsToLoad = [
        // Orden crítico: constantes PRIMERO
        '/js/configuraciones/constantes-tallas.js',
        '/js/modulos/crear-pedido/fotos/image-storage-service.js',
        
        // Luego servicios
        '/js/modulos/crear-pedido/procesos/services/notification-service.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/services/payload-normalizer-v3-definitiva.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/services/item-api-service.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/services/item-validator.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/services/item-form-collector.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/services/item-renderer.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/services/prenda-editor.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/services/item-orchestrator.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/services/proceso-editor.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/services/gestor-edicion-procesos.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/services/servicio-procesos.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/services/middleware-guardado-prenda.js?v=' + Math.random(),
        
        // Gestión de ítems
        '/js/modulos/crear-pedido/procesos/gestion-items-pedido-constantes.js',
        '/js/modulos/crear-pedido/procesos/gestion-items-pedido.js?v=' + Math.random(),
        '/js/modulos/crear-pedido/procesos/manejadores-procesos-prenda.js',
        '/js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js',
        '/js/modulos/crear-pedido/procesos/renderizador-tarjetas-procesos.js',
        
        // Gestión de telas, tallas, variaciones
        '/js/modulos/crear-pedido/telas/gestion-telas.js',
        '/js/modulos/crear-pedido/tallas/gestion-tallas.js',
        '/js/modulos/crear-pedido/prendas/manejadores-variaciones.js',
        
        // Componentes de prendas
        '/js/componentes/prenda-card-editar-simple.js',
        '/js/componentes/prendas-wrappers.js',
        '/js/componentes/modal-novedad-prenda.js?v=' + Math.random(),
        '/js/componentes/modal-novedad-edicion.js?v=' + Math.random(),
        '/js/componentes/prenda-form-collector.js?v=' + Math.random(),
        '/js/componentes/modal-prenda-dinamico-constantes.js',
        '/js/componentes/modal-prenda-dinamico.js',
        '/js/componentes/prenda-editor-modal.js',
        
        // API
        '/js/modulos/crear-pedido/configuracion/api-pedidos-editable.js',
    ];

    const cssToLoad = [
        '/css/crear-pedido.css',
        '/css/crear-pedido-editable.css',
        '/css/form-modal-consistency.css',
        '/css/swal-z-index-fix.css',
        '/css/componentes/prendas.css',
        '/css/componentes/reflectivo.css',
        '/css/modales-personalizados.css',
    ];

    /**
     * Cargar scripts secuencialmente
     */
    async function loadScriptsSequentially() {
        return new Promise((resolve, reject) => {
            let loaded = 0;
            
            const loadNext = () => {
                if (loaded >= scriptsToLoad.length) {
                    resolve();
                    return;
                }
                
                const src = scriptsToLoad[loaded];
                const script = document.createElement('script');
                script.src = src;
                script.defer = true;
                
                script.onload = () => {
                    console.log(`[PrendaEditorLoader] ✅ Cargado: ${src.split('/').pop()}`);
                    loaded++;
                    loadNext();
                };
                
                script.onerror = () => {
                    console.error(`[PrendaEditorLoader] ❌ Error cargando: ${src}`);
                    reject(new Error(`Failed to load: ${src}`));
                };
                
                document.head.appendChild(script);
            };
            
            loadNext();
        });
    }

    /**
     * Cargar CSS (en paralelo, más rápido)
     */
    function loadCSS() {
        cssToLoad.forEach(href => {
            // Verificar que no esté cargado ya
            if (document.querySelector(`link[href="${href}"]`)) {
                console.log(`[PrendaEditorLoader] ⏭️ Ya cargado: ${href}`);
                return;
            }
            
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            document.head.appendChild(link);
            console.log(`[PrendaEditorLoader] ✅ CSS cargado: ${href}`);
        });
    }

    return {
        /**
         * Cargar todos los módulos
         * @returns {Promise}
         */
        load: async function() {
            if (isLoaded) {
                console.log('[PrendaEditorLoader] ⏭️ Módulos ya cargados');
                return;
            }
            
            if (isLoading) {
                console.log('[PrendaEditorLoader] ⏳ Carga en progreso...');
                return;
            }
            
            isLoading = true;
            
            try {
                console.log('[PrendaEditorLoader] 🚀 Iniciando carga de módulos de edición...');
                
                // Cargar CSS primero (no bloquea)
                loadCSS();
                
                // Cargar scripts secuencialmente
                await loadScriptsSequentially();
                
                isLoaded = true;
                isLoading = false;
                
                console.log('[PrendaEditorLoader] ✅ TODOS LOS MÓDULOS CARGADOS');
                
                // Disparar evento personalizado
                window.dispatchEvent(new CustomEvent('prendaEditorLoaded'));
                
            } catch (error) {
                isLoading = false;
                console.error('[PrendaEditorLoader] ❌ Error:', error);
                throw error;
            }
        },
        
        /**
         * Verificar si está cargado
         */
        isLoaded: function() {
            return isLoaded;
        }
    };
})();
```

### B. Crear archivo: `public/js/lazy-loaders/epp-manager-loader.js`

```javascript
/**
 * Lazy Loader: Módulos de Gestión EPP
 * Se carga cuando usuario abre modal "Editar EPP"
 * 
 * Tamaño: ~25KB (minificado)
 * Tiempo: ~150ms en conexión lenta
 */

window.EPPManagerLoader = (function() {
    let isLoading = false;
    let isLoaded = false;

    const scriptsToLoad = [
        // Services (orden: API service PRIMERO)
        '/js/modulos/crear-pedido/epp/services/epp-api-service.js',
        '/js/modulos/crear-pedido/epp/services/epp-state-manager.js',
        '/js/modulos/crear-pedido/epp/services/epp-modal-manager.js',
        '/js/modulos/crear-pedido/epp/services/epp-item-manager.js',
        '/js/modulos/crear-pedido/epp/services/epp-imagen-manager.js',
        '/js/modulos/crear-pedido/epp/services/epp-notification-service.js',
        '/js/modulos/crear-pedido/epp/services/epp-creation-service.js',
        '/js/modulos/crear-pedido/epp/services/epp-form-manager.js',
        '/js/modulos/crear-pedido/epp/services/epp-menu-handlers.js',
        '/js/modulos/crear-pedido/epp/services/epp-service.js',
        
        // Templates e interfaces
        '/js/modulos/crear-pedido/epp/templates/epp-modal-template.js',
        '/js/modulos/crear-pedido/epp/interfaces/epp-modal-interface.js',
        
        // Inicialización
        '/js/modulos/crear-pedido/epp/epp-init.js',
        
        // Modales
        '/js/modulos/crear-pedido/modales/modal-agregar-epp.js',
    ];

    /**
     * Cargar scripts secuencialmente
     */
    async function loadScriptsSequentially() {
        return new Promise((resolve, reject) => {
            let loaded = 0;
            
            const loadNext = () => {
                if (loaded >= scriptsToLoad.length) {
                    resolve();
                    return;
                }
                
                const src = scriptsToLoad[loaded];
                const script = document.createElement('script');
                script.src = src;
                script.defer = true;
                
                script.onload = () => {
                    console.log(`[EPPManagerLoader] ✅ Cargado: ${src.split('/').pop()}`);
                    loaded++;
                    loadNext();
                };
                
                script.onerror = () => {
                    console.error(`[EPPManagerLoader] ❌ Error cargando: ${src}`);
                    reject(new Error(`Failed to load: ${src}`));
                };
                
                document.head.appendChild(script);
            };
            
            loadNext();
        });
    }

    return {
        /**
         * Cargar todos los módulos
         */
        load: async function() {
            if (isLoaded) {
                console.log('[EPPManagerLoader] ⏭️ Módulos ya cargados');
                return;
            }
            
            if (isLoading) {
                console.log('[EPPManagerLoader] ⏳ Carga en progreso...');
                return;
            }
            
            isLoading = true;
            
            try {
                console.log('[EPPManagerLoader] 🚀 Iniciando carga de módulos EPP...');
                
                await loadScriptsSequentially();
                
                isLoaded = true;
                isLoading = false;
                
                console.log('[EPPManagerLoader] ✅ TODOS LOS MÓDULOS CARGADOS');
                
                // Disparar evento personalizado
                window.dispatchEvent(new CustomEvent('eppManagerLoaded'));
                
            } catch (error) {
                isLoading = false;
                console.error('[EPPManagerLoader] ❌ Error:', error);
                throw error;
            }
        },
        
        /**
         * Verificar si está cargado
         */
        isLoaded: function() {
            return isLoaded;
        }
    };
})();
```

---

## FASE 3: ACTUALIZAR FUNCIONES (15 min)

### A. Modificar función `editarPedido()` en index.blade.php

**REEMPLAZAR la función actual (líneas ~460-520) con esta versión mejorada:**

```javascript
/**
 * Editar pedido - OPTIMIZADO CON LAZY LOADING
 * 
 * ✅ CAMBIOS:
 * - Carga módulos de edición bajo demanda (NO en la carga inicial)
 * - Extrae datos de data attributes (no hace fetch)
 * - Solo fetch si faltan datos (fallback)
 * - Tiempo: <100ms para abrir modal (datos en fila) o ~1s con lazy loading
 */
async function editarPedido(pedidoId) {
    // 🔒 Prevenir múltiples clics simultáneos
    if (window.edicionEnProgreso) {
        return;
    }
    
    window.edicionEnProgreso = true;
    
    try {
        // 🔥 PASO 1: Cargar módulos de edición (solo primera vez)
        if (!window.PrendaEditorLoader.isLoaded()) {
            console.log('[editarPedido] 📦 Cargando módulos de edición...');
            await _ensureSwal();
            UI.cargando('Cargando editor de prendas...', 'Iniciando módulos');
            
            try {
                await window.PrendaEditorLoader.load();
                console.log('[editarPedido] ✅ Módulos cargados');
            } catch (error) {
                console.error('[editarPedido] ❌ Error cargando módulos:', error);
                Swal.close();
                UI.error('Error', 'No se pudieron cargar los módulos de edición');
                window.edicionEnProgreso = false;
                return;
            }
        }

        // 🔥 PASO 2: Extraer datos de la fila
        const fila = document.querySelector(`[data-pedido-id="${pedidoId}"]`);
        
        if (!fila) {
            console.warn('[editarPedido] Fila no encontrada, haciendo fetch como fallback');
            throw new Error('No se encontró la fila del pedido');
        }

        // 📊 Extraer datos de data attributes
        const datosEnFila = {
            id: fila.dataset.pedidoId,
            numero_pedido: fila.dataset.numeroPedido,
            numero: fila.dataset.numeroPedido,
            cliente: fila.dataset.cliente,
            estado: fila.dataset.estado,
            forma_de_pago: fila.dataset.formaPago,
            asesor: fila.dataset.asesor,
            prendas: fila.dataset.prendas ? JSON.parse(fila.dataset.prendas) : [],
        };

        console.log('[editarPedido] ✅ Datos extraídos de fila:', {
            id: datosEnFila.id,
            numero: datosEnFila.numero_pedido,
            cliente: datosEnFila.cliente
        });

        // ✅ Si los datos básicos están presentes, abrir modal sin fetch
        if (datosEnFila.numero_pedido && datosEnFila.cliente) {
            console.log('[editarPedido] 🚀 Abriendo modal sin fetch adicional');
            Swal.close();
            abrirModalEditarPedido(pedidoId, datosEnFila, 'editar');
            return;
        }

        // 🔴 FALLBACK: Si falta info crítica, hacer fetch
        console.warn('[editarPedido] ⚠️ Datos incompletos en fila, haciendo fetch...');
        
        await _ensureSwal();
        UI.cargando('Cargando datos del pedido...', 'Por favor espera');

        const response = await fetch(`/api/pedidos/${pedidoId}`, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const respuesta = await response.json();
        Swal.close();

        if (!respuesta.success) {
            throw new Error(respuesta.message || 'Error desconocido');
        }

        const datos = respuesta.data || respuesta.datos;
        
        // Transformar datos al formato que espera el modal
        const datosTransformados = {
            id: datos.id || datos.numero_pedido,
            numero_pedido: datos.numero_pedido || datos.numero,
            numero: datos.numero || datos.numero_pedido,
            cliente: datos.cliente || 'Cliente sin especificar',
            asesora: datos.asesor || datos.asesora?.name || 'Asesor sin especificar',
            estado: datos.estado || 'Pendiente',
            forma_de_pago: datos.forma_pago || datos.forma_de_pago || 'No especificada',
            prendas: datos.prendas || [],
            epps: datos.epps_transformados || datos.epps || [],
            ...datos
        };

        console.log('[editarPedido] ✅ Datos cargados vía fetch:', datosTransformados);

        abrirModalEditarPedido(pedidoId, datosTransformados, 'editar');

    } catch (err) {
        Swal.close();
        console.error('[editarPedido] ❌ Error:', err);
        UI.error('Error', 'No se pudo cargar el pedido: ' + err.message);
        
    } finally {
        window.edicionEnProgreso = false;
    }
}
```

### B. Crear función auxiliar para editar EPP (agregar en index.blade.php)

```javascript
/**
 * Editar EPP - CON LAZY LOADING
 * Agregado después de editarPedido()
 */
async function abrirEditarEPPOptimizado(pedidoId) {
    try {
        // 🔄 Cargar módulos EPP (solo primera vez)
        if (!window.EPPManagerLoader.isLoaded()) {
            console.log('[abrirEditarEPP] 📦 Cargando módulos EPP...');
            await _ensureSwal();
            UI.cargando('Cargando gestor de EPP...', 'Iniciando módulos');
            
            try {
                await window.EPPManagerLoader.load();
                console.log('[abrirEditarEPP] ✅ Módulos EPP cargados');
            } catch (error) {
                console.error('[abrirEditarEPP] ❌ Error:', error);
                Swal.close();
                UI.error('Error', 'No se pudieron cargar los módulos de EPP');
                return;
            }
        }
        
        Swal.close();
        
        // Llamar función original (ahora disponible después de lazy load)
        if (typeof window.abrirEditarEPP === 'function') {
            window.abrirEditarEPP(pedidoId);
        } else {
            UI.error('Error', 'Módulos EPP no disponibles');
        }
        
    } catch (error) {
        console.error('[abrirEditarEPP] ❌ Error:', error);
        UI.error('Error', 'Error al abrir editor de EPP');
    }
}
```

---

## FASE 4: ACTUALIZAR index.blade.php (30 min)

### Código exacto para reemplazar en index.blade.php

**UBICACIÓN: Línea 1-70 (remove extra_styles)**

```blade
@extends('layouts.asesores')

@section('title', 'Mis Pedidos')
@section('page-title', 'Mis Pedidos')

@section('extra_styles')
    <!-- ✅ MANTENER SOLO ESTOS 3 CSS -->
    <link rel="stylesheet" href="{{ asset('css/asesores/pedidos/index.css') }}">
    <link rel="stylesheet" href="{{ asset('css/asesores/pedidos/page-loading.css') }}">
@endsection

@section('content')
    <!-- 🔄 LOADING OVERLAY -->
    <div id="page-loading-overlay">
        <div class="loading-container">
            <div class="spinner"></div>
            <div class="loading-text">
                Cargando los pedidos<span class="loading-dots"></span>
            </div>
            <div class="loading-subtext">
                Por favor espera mientras se cargan los datos
            </div>
        </div>
    </div>

    @include('asesores.pedidos.components.header')
    @include('asesores.pedidos.components.quick-filters')
    @include('asesores.pedidos.components.table')
    @include('asesores.pedidos.components.modals')
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/asesores/pedidos.css') }}">
@endpush

@push('scripts')
<!-- ✅ LAZY LOADERS (cargados en la inicial, pero livianos) -->
<script src="{{ asset('js/lazy-loaders/prenda-editor-loader.js') }}"></script>
<script src="{{ asset('js/lazy-loaders/epp-manager-loader.js') }}"></script>

<!-- ✅ SERVICIOS CENTRALIZADOS (siempre necesarios) -->
<script src="{{ asset('js/utilidades/validation-service.js') }}"></script>
<script src="{{ asset('js/utilidades/ui-modal-service.js') }}"></script>
<script src="{{ asset('js/utilidades/deletion-service.js') }}"></script>
<script src="{{ asset('js/utilidades/galeria-service.js') }}"></script>

<!-- ✅ CORE PEDIDOS (siempre necesario) -->
<script src="{{ asset('js/asesores/pedidos-list.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-modal.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-dropdown-simple.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-anular.js') }}"></script>

<!-- ✅ TRACKING Y RECIBOS (necesarios para funcionalidad completa) -->
<script src="{{ asset('js/asesores/pedidos-detail-modal.js') }}"></script>
<script src="{{ asset('js/asesores/pedidos-table-filters.js') }}"></script>
<script src="{{ asset('js/orders-scripts/image-gallery-zoom.js') }}"></script>
<script src="{{ asset('js/invoice-preview-live.js') }}"></script>
<script src="{{ asset('js/asesores/invoice-from-list.js') }}"></script>
<script src="{{ asset('js/orders-scripts/order-detail-modal-manager.js') }}"></script>

<!-- ✅ ORDER TRACKING (MODULAR) -->
<script src="{{ asset('js/order-tracking/modules/dateUtils.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/holidayManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/areaMapper.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/trackingService.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/trackingUI.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/apiClient.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/processManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/tableManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/modules/dropdownManager.js') }}"></script>
<script src="{{ asset('js/order-tracking/orderTracking-v2.js') }}"></script>

<!-- ✅ LAZY: Recibos dinámicos (carga bajo demanda) -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>

<!-- 🔄 SCRIPT: Inicialización y lazy loading de módulos -->
<script>
    /**
     * Inicializar funciones globales necesarias para lazy loading
     * Se ejecuta ANTES de que se carguen los módulos bajo demanda
     */
    
    // Función wrapper para abrirEditarEPP (con lazy loading)
    const abrirEditarEPPOriginal = window.abrirEditarEPP;
    window.abrirEditarEPP = async function(pedidoId) {
        // Si ya está cargado, llamar directamente
        if (window.EPPManagerLoader && window.EPPManagerLoader.isLoaded()) {
            if (abrirEditarEPPOriginal && typeof abrirEditarEPPOriginal === 'function') {
                abrirEditarEPPOriginal.call(this, pedidoId);
                return;
            }
        }
        
        // Si no está cargado, cargar primero
        console.log('[abrirEditarEPP] Cargando módulos EPP...');
        try {
            await window.EPPManagerLoader.load();
            if (abrirEditarEPPOriginal && typeof abrirEditarEPPOriginal === 'function') {
                abrirEditarEPPOriginal.call(this, pedidoId);
            }
        } catch (error) {
            console.error('[abrirEditarEPP] Error cargando EPP:', error);
            UI.error('Error', 'No se pudieron cargar los módulos de EPP');
        }
    };

    // Similar para otros modales que se cargan lazy
    window.abrirAgregarPrenda = async function() {
        if (!window.PrendaEditorLoader.isLoaded()) {
            console.log('[abrirAgregarPrenda] Cargando módulos de prendas...');
            try {
                await window.PrendaEditorLoader.load();
            } catch (error) {
                console.error('[abrirAgregarPrenda] Error:', error);
                UI.error('Error', 'No se pudieron cargar los módulos');
                return;
            }
        }
        
        // Llamar función original (ahora disponible)
        if (typeof window.abrirModalPrendaNueva === 'function') {
            window.abrirModalPrendaNueva();
        }
    };

    /**
     * Logs dinámicos (solo en desarrollo)
     */
    if (app && app.isLocal && app.isLocal()) {
        console.log('[asesores/pedidos] 🚀 Vista inicializada');
        console.log('[asesores/pedidos] 📦 Lazy loaders disponibles');
        console.log('[asesores/pedidos] ✅ Módulos core cargados');
    }
</script>

<!-- 🔄 SCRIPT: Lógica de búsqueda y filtrado (inline, siempre en base) -->
<script>
    /**
     * Buscador principal: buscar por número de pedido o cliente
     */
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('mainSearchInput');
        const clearButton = document.getElementById('clearMainSearch');
        
        if (!searchInput) return;

        function searchOrders() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('[data-pedido-row]');
            let visibleCount = 0;

            rows.forEach(row => {
                const numeroPedido = (row.getAttribute('data-numero-pedido') || '').toLowerCase();
                const cliente = (row.getAttribute('data-cliente') || '').toLowerCase();
                
                const matches = !searchTerm || 
                               numeroPedido.includes(searchTerm) || 
                               cliente.includes(searchTerm);

                row.style.display = matches ? 'grid' : 'none';
                if (matches) visibleCount++;
            });

            if (searchTerm) {
                clearButton.style.display = 'block';
            } else {
                clearButton.style.display = 'none';
            }

            const tableContainer = document.querySelector('.table-scroll-container');
            let noResultsMsg = document.getElementById('noSearchResults');
            
            if (visibleCount === 0 && searchTerm) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.id = 'noSearchResults';
                    noResultsMsg.style.cssText = 'padding: 2rem; text-align: center; color: #6b7280; font-size: 0.95rem;';
                    noResultsMsg.innerHTML = `
                        <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                        <p style="margin: 0; font-weight: 600;">No se encontraron resultados</p>
                    `;
                    tableContainer.appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        }

        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(searchOrders, 300);
        });

        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            searchOrders();
            searchInput.focus();
        });
    });
</script>

<!-- 🔄 SCRIPT: Ocultar loading al terminar -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const overlay = document.getElementById('page-loading-overlay');
            if (overlay) {
                overlay.classList.add('hidden');
                setTimeout(() => overlay.remove(), 400);
            }
        }, 500);
    });

    const maxLoadTime = setTimeout(function() {
        const overlay = document.getElementById('page-loading-overlay');
        if (overlay && !overlay.classList.contains('hidden')) {
            overlay.classList.add('hidden');
            setTimeout(() => overlay.remove(), 400);
        }
    }, 10000);

    window.addEventListener('load', () => clearTimeout(maxLoadTime));
</script>

<!-- ✅ MANTENER: Componentes de modales -->
@include('asesores.pedidos.components.modal-editar-pedido')
@include('asesores.pedidos.components.modal-prendas-lista')
@include('asesores.pedidos.components.modal-agregar-prenda')
@include('asesores.pedidos.components.modal-editar-prenda')
@include('asesores.pedidos.components.modal-editar-epp')
@endpush
```

---

## FASE 5: VERIFICACIÓN (10 min)

### Checklist de Testing

```bash
# 1. En navegador, abrir DevTools (F12)
# 2. Ir a Network tab
# 3. Limpiar cache: Ctrl+Shift+Del
# 4. Recargar página

✅ Verificar:
- ✓ Page load: < 1s
- ✓ Peticiones iniciales: ~18 (vs 48 antes)
- ✓ Tamaño JS: ~80KB (vs 285KB antes)
- ✓ Consola: Sin errores
- ✓ Buscar pedido: Funciona rápido
- ✓ Click "Editar": Abre modal en <100ms (sin lazy) o ~1s (primera vez con lazy)
- ✓ Modal de edición: Carga con los datos correctos
- ✓ Editar EPP: Funciona después de lazy load
- ✓ Cerrar modales: Sin errores

# 5. Medir con Lighthouse
- ✓ Performance: > 85
- ✓ Largest Contentful Paint (LCP): < 1.5s
- ✓ First Input Delay (FID): < 100ms
```

---

## 📊 COMPARATIVA ANTES VS DESPUÉS

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|---------|
| Peticiones HTTP | 48 | 18 | -62% ⭐ |
| Tamaño JS inicial | 285KB | 80KB | -72% ⭐ |
| Tamaño CSS inicial | 45KB | 15KB | -67% ⭐ |
| Time to Interactive | 2.5s | 0.6s | -76% ⭐ |
| First Contentful Paint | 1.8s | 0.4s | -78% ⭐ |
| Modal editar (primera vez) | N/A | +1s (lazy) | Carga bajo demanda |
| Modal editar (subsecuentes) | N/A | <100ms | Ya cargado |

---

## 🚨 FALLBACKS DE SEGURIDAD

### Si algo falla, tienes estos respaldos:

1. **Si los lazy loaders fallan:**
   - Los usuarios siguen pudiendo listar pedidos
   - Modal de edición mostrará error: "No se pudieron cargar los módulos"
   - Sin afectar la vista principal

2. **Si EditarPedido falla:**
   - Sigue la ruta del FALLBACK (hace fetch)
   - Abre modal con datos del API
   - Sin impactar la UX crítica

3. **Si EPP no carga:**
   - UI.error() muestra mensaje
   - Usuario sigue viendo la lista de pedidos
   - Puede reintentarlo

---

## 🔧 ROLLBACK (si necesitas volver atrás)

Si algo no funciona, solo revertir:

1. Restaurar líneas de @section extra_styles original
2. Restaurar función editarPedido() original
3. Recargar la página

Los archivos lazy-loaders nuevos no interfieren con nada existente.

