@push('scripts')
<!-- Scripts para Recibos/Procesos -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>

<!-- Script para el modal de seguimiento -->
<script src="{{ asset('js/ordersjs/tracking-modal-utils.js') }}?v={{ time() }}"></script>
<!-- Sistema de Tracking Modular -->
<!-- DAYS SELECTOR HANDLER - DEBE cargarse PRIMERO (ANTES de data-loader y ui-components) -->
<script src="{{ asset('js/ordersjs/tracking/days-selector-handler.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/ordersjs/tracking/date-utils.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/ordersjs/tracking/modal-manager.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/ordersjs/tracking/days-selector.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/ordersjs/tracking/data-loader.js') }}?v={{ time() }}"></script>
<!-- TRACKING MODAL HANDLER - DEBE cargarse ANTES de ui-components.js -->
<script type="module" src="{{ asset('js/ordersjs/tracking-modal-handler.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/ordersjs/tracking/ui-components.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/ordersjs/tracking/process-manager.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/ordersjs/tracking/area-cards.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/ordersjs/tracking/prendas-renderer.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/ordersjs/tracking/tracking-main.js') }}?v={{ time() }}"></script>

<!-- Scripts para la funcionalidad de Día de Entrega -->
<script src="{{ asset('js/ordersjs/modules/diaEntregaModule.js') }}?v={{ time() }}"></script>

<!-- Scripts para Formatters -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/utils/Formatters.js') }}?v={{ time() }}"></script>

<!-- Script para novedades de recibos (sistema completo) -->
<script src="{{ asset('js/recibos-novedades.js') }}?v={{ time() }}"></script>

<!-- Script con funciones globales (cargado antes del HTML) -->
<script>
// Definir funciones de filtro globalmente de inmediato para evitar ReferenceError
window.openFilterModal = function(filterType) {
    console.log('[Filtros] openFilterModal llamado con:', filterType);
    // La implementación real se cargará más abajo
    if (typeof openFilterModalImpl === 'function') {
        return openFilterModalImpl(filterType);
    } else {
        console.warn('[Filtros] La implementación de openFilterModal aún no está cargada');
    }
};

window.closeFilterModal = function() {
    console.log('[Filtros] closeFilterModal llamado');
    if (typeof closeFilterModalImpl === 'function') {
        return closeFilterModalImpl();
    }
};

window.resetFilters = function() {
    console.log('[Filtros] resetFilters llamado');
    if (typeof resetFiltersImpl === 'function') {
        return resetFiltersImpl();
    }
};

window.applyFilters = function() {
    console.log('[Filtros] applyFilters llamado');
    if (typeof applyFiltersImpl === 'function') {
        return applyFiltersImpl();
    }
};

window.selectAllCheckboxFilters = function(filterType) {
    console.log('[Filtros] selectAllCheckboxFilters llamado con:', filterType);
    if (typeof selectAllCheckboxFiltersImpl === 'function') {
        return selectAllCheckboxFiltersImpl(filterType);
    }
};

window.filterCheckboxOptions = function(filterType) {
    console.log('[Filtros] filterCheckboxOptions llamado con:', filterType);
    if (typeof filterCheckboxOptionsImpl === 'function') {
        return filterCheckboxOptionsImpl(filterType);
    }
};

// Funciones globales para el modal de celda formateada
function abrirModalCeldaConFormato(titulo, prendas) {
    console.log('[abrirModalCeldaConFormato]  INICIO - Datos recibidos:');
    console.log('[abrirModalCeldaConFormato] Título:', titulo);
    console.log('[abrirModalCeldaConFormato] Prendas tipo:', typeof prendas);
    console.log('[abrirModalCeldaConFormato] Prendas es array:', Array.isArray(prendas));
    console.log('[abrirModalCeldaConFormato] Prendas cantidad:', prendas ? prendas.length : 0);
    console.log('[abrirModalCeldaConFormato] Prendas RAW:', prendas);
    
    let htmlContenido = '';
    
    if (!prendas || prendas.length === 0) {
        htmlContenido = '<div style="text-align: center; color: #9ca3af;">No hay prendas disponibles</div>';
    } else {
        prendas.forEach((prenda, idx) => {
            console.log(`[abrirModalCeldaConFormato]  Procesando prenda ${idx}:`, prenda);
            
            // Convertir objeto Eloquent a objeto simple si es necesario
            let prendaData = prenda.toJSON ? prenda.toJSON() : prenda;
            console.log(`[abrirModalCeldaConFormato]  Después toJSON:`, prendaData);
            
            // NORMALIZAR datos: convertir objetos a strings
            prendaData = normalizarPrendaData(prendaData);
            console.log(`[abrirModalCeldaConFormato]  Después normalizar:`, prendaData);
            console.log(`[abrirModalCeldaConFormato] Campos principales: nombre="${prendaData.nombre_prenda}", tela="${prendaData.tela}", color="${prendaData.color}", manga="${prendaData.manga}"`);
            
            // Generar HTML formateado como en el recibo
            let prendaHtml = '';
            try {
                // Intentar usar Formatters si está disponible
                if (window.Formatters && typeof window.Formatters.construirDescripcionCostura === 'function') {
                    console.log(`[abrirModalCeldaConFormato]  Usando window.Formatters.construirDescripcionCostura`);
                    prendaHtml = window.Formatters.construirDescripcionCostura(prendaData);
                } else if (typeof Formatters !== 'undefined' && typeof Formatters.construirDescripcionCostura === 'function') {
                    console.log(`[abrirModalCeldaConFormato]  Usando Formatters.construirDescripcionCostura (module)`);
                    prendaHtml = Formatters.construirDescripcionCostura(prendaData);
                } else {
                    // Fallback si Formatters no disponible - generar HTML simple
                    console.log(`[abrirModalCeldaConFormato]  Formatters no disponible, usando fallback`);
                    prendaHtml = generarDescripcionSimple(prendaData);
                }
            } catch (e) {
                console.error('[abrirModalCeldaConFormato]  Error al formatear prenda:', e);
                console.error('[abrirModalCeldaConFormato] Stack:', e.stack);
                prendaHtml = generarDescripcionSimple(prendaData);
            }
            
            console.log(`[abrirModalCeldaConFormato]  HTML generado:`, prendaHtml);
            
            htmlContenido += `<div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px; border-left: 4px solid #3b82f6;">
                ${prendaHtml}
            </div>`;
        });
    }
    
    console.log('[abrirModalCeldaConFormato]  HTML FINAL A MOSTRAR:', htmlContenido);
    
    // Crear y mostrar el modal
    const modal = document.createElement('div');
    modal.id = 'modal-celda-formateada';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        border-radius: 12px;
        max-width: 800px;
        width: 100%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    `;
    
    content.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; border-radius: 12px 12px 0 0;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #111827;">${titulo}</h2>
            <button onclick="this.closest('#modal-celda-formateada').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px;">×</button>
        </div>
        <div style="padding: 24px;">
            ${htmlContenido}
        </div>
    `;
    
    modal.appendChild(content);
    document.body.appendChild(modal);
    
    // Cerrar al hacer clic fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Cerrar con ESC
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

function cerrarModalCeldaFormateada() {
    const modal = document.getElementById('modal-celda-formateada');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Funciones auxiliares
function generarDescripcionSimple(prenda) {
    console.log('[generarDescripcionSimple]  INPUT:', prenda);
    let html = '';
    
    // Título
    if (prenda.nombre_prenda) {
        html += `<strong style="font-size: 13.4px;">PRENDA: ${prenda.nombre_prenda.toUpperCase()}</strong><br>`;
        console.log('[generarDescripcionSimple]  Nombre agregado');
    }
    
    // Atributos básicos
    if (prenda.tela || prenda.color || prenda.manga) {
        let attrs = [];
        if (prenda.tela) {
            attrs.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
            console.log('[generarDescripcionSimple]  Tela:', prenda.tela);
        }
        if (prenda.color) {
            attrs.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
            console.log('[generarDescripcionSimple]  Color:', prenda.color);
        }
        if (prenda.manga) {
            attrs.push(`<strong>MANGA:</strong> ${prenda.manga.toUpperCase()}`);
            console.log('[generarDescripcionSimple]  Manga:', prenda.manga);
        }
        html += attrs.join(' | ') + '<br>';
    }
    
    // Descripción - Limpiar basura del inicio
    if (prenda.descripcion) {
        console.log('[generarDescripcionSimple]  Descripción RAW:', prenda.descripcion);
        let desc = String(prenda.descripcion);
        // Limpiar líneas de basura del inicio (DSFSDFS, etc)
        desc = desc.split('\n').filter(linea => {
            const trimmed = linea.trim();
            // Saltar líneas basura
            if (!trimmed) return false;
            if (trimmed.match(/^[A-Z]{5,}[A-Z\s]{0,10}$/i) && 
                !trimmed.match(/^(PRENDA|TALLA|TELA|COLOR|MANGA|BOLSILLO|BOTÓN|CREMALLERA|DESCRIPCIÓN|DAMA|HOMBRE)/i)) {
                console.log('[generarDescripcionSimple] 🚫 Línea basura filtrada:', trimmed);
                return false;
            }
            return true;
        }).join('\n');
        
        if (desc.trim()) {
            html += desc + '<br>';
            console.log('[generarDescripcionSimple]  Descripción agregada (después de limpiar)');
        }
    }
    
    console.log('[generarDescripcionSimple]  OUTPUT HTML:', html);
}

// Función para normalizar datos de prenda
function normalizarPrendaData(prendaData) {
    const normalized = { ...prendaData };
    
    // Normalizar campos que puedan ser objetos
    Object.keys(normalized).forEach(key => {
        if (normalized[key] && typeof normalized[key] === 'object' && !Array.isArray(normalized[key])) {
            // Si es un objeto con propiedades comunes, convertir a string
            if (normalized[key].nombre) {
                normalized[key] = normalized[key].nombre;
            } else if (normalized[key].id) {
                normalized[key] = `ID: ${normalized[key].id}`;
            } else {
                normalized[key] = JSON.stringify(normalized[key]);
            }
        }
    });
    
    return normalized;
}

// Función fallback para generar HTML
function generarFallbackHTML(prendaData) {
    return `
        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
            <h3 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #111827;">
                ${prendaData.nombre_prenda || 'Prenda sin nombre'}
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 14px;">
                ${prendaData.talla ? `<div><strong>Talla:</strong> ${prendaData.talla}</div>` : ''}
                ${prendaData.tela ? `<div><strong>Tela:</strong> ${prendaData.tela}</div>` : ''}
                ${prendaData.color ? `<div><strong>Color:</strong> ${prendaData.color}</div>` : ''}
                ${prendaData.manga ? `<div><strong>Manga:</strong> ${prendaData.manga}</div>` : ''}
                ${prendaData.descripcion ? `<div><strong>Descripción:</strong> ${prendaData.descripcion}</div>` : ''}
            </div>
        </div>
    `;
}

// Función para obtener datos de la prenda asociada al recibo (igual que en registros)
function obtenerDatosPrendaRecibo(titulo, pedidoId, prendaId) {
    console.log('[obtenerDatosPrendaRecibo] FUNCION LLAMADA - Parametros:', titulo, pedidoId, prendaId);
    console.log(`[obtenerDatosPrendaRecibo]  Obteniendo datos para pedido ID: ${pedidoId}, prenda ID: ${prendaId}`);
    
    // Obtener datos de la prenda del pedido (igual que en registros)
    fetch(`/pedidos-public/${pedidoId}/recibos-datos`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(datosRecibo => {
            console.log(`[obtenerDatosPrendaRecibo]  Datos del recibo recibidos:`, datosRecibo);
            
            if (!datosRecibo.success || !datosRecibo.data || !datosRecibo.data.prendas) {
                console.error('No se encontraron datos del recibo:', pedidoId);
                alert('No se encontraron datos del recibo');
                return;
            }
            
            const todasLasPrendas = datosRecibo.data.prendas;
            console.log(`[obtenerDatosPrendaRecibo]  Prendas encontradas: ${todasLasPrendas.length}`);
            
            // Filtrar para mostrar solo la prenda asociada al recibo
            const prendaFiltrada = todasLasPrendas.filter(prenda => {
                console.log(`[obtenerDatosPrendaRecibo]  Comparando prenda.id=${prenda.id} con prendaId=${prendaId}`);
                return prenda.id == prendaId; // Comparación flexible para manejar string/int
            });
            
            console.log(`[obtenerDatosPrendaRecibo]  Prendas filtradas: ${prendaFiltrada.length}`);
            
            if (prendaFiltrada.length === 0) {
                console.warn('No se encontró la prenda asociada al recibo:', prendaId);
                alert('No se encontró la prenda asociada a este recibo');
                return;
            }
            
            // Usar solo la prenda filtrada
            abrirModalCeldaConFormato(titulo, prendaFiltrada);
        })
        .catch(error => {
            console.error('[obtenerDatosPrendaRecibo] Error al obtener datos del recibo:', error);
            alert('Error al cargar los datos de la prenda: ' + error.message);
        });
}

// Función para abrir modal de novedades específicas de recibo (NUEVO SISTEMA)
function openNovedadesModalRecibo(button) {
    // Obtener datos desde los data attributes del botón
    const pedidoId = button.getAttribute('data-pedido-id');
    const numeroRecibo = button.getAttribute('data-numero-recibo');
    const novedadesActuales = button.getAttribute('data-novedades') || '';
    
    console.log(`[openNovedadesModalRecibo]  Abriendo modal para pedido: ${pedidoId}, recibo: ${numeroRecibo}`);
    console.log(`[openNovedadesModalRecibo] Novedades actuales:`, novedadesActuales);
    
    // Esperar a que el script de novedades esté disponible
    if (typeof abrirModalNovedadesRecibo === 'function') {
        abrirModalNovedadesRecibo(pedidoId, numeroRecibo);
        return;
    }
    
    // Si no está disponible, esperar un poco y reintentar
    setTimeout(() => {
        if (typeof abrirModalNovedadesRecibo === 'function') {
            abrirModalNovedadesRecibo(pedidoId, numeroRecibo);
        } else {
            console.warn('[openNovedadesModalRecibo] Sistema nuevo no disponible, usando fallback');
            // Fallback simple: mostrar alerta con las novedades actuales
            alert(`Novedades del recibo ${numeroRecibo}:\n\n${novedadesActuales || 'Sin novedades'}`);
        }
    }, 100);
}

// Funciones para el menú de acciones
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Menu] Inicializando menú de acciones');
});

// Los event listeners del menú de acciones ahora están manejados por el sistema
// de dropdowns dinámicos (estilo insumos) en recibos-costura.blade.php

// ============================================================
// SISTEMA DE FILTROS MÚLTIPLES POR COLUMNA
// ============================================================
let currentFilterType = '';
let activeFilters = {
    estado: [],
    dia_entrega: [],
    total_dias: [],
    numero_recibo: [],
    cliente: [],
    descripcion: [],
    cantidad: [],
    novedades: [],
    fecha_creacion: [],
    fecha_estimada: [],
    encargado: []
};

// Configuración de tipos de filtros
const filterTypes = {
    estado: {
        title: 'Filtrar por Estado',
        type: 'checkbox',
        options: [
            { value: 'Pendiente', label: 'Pendiente' },
            { value: 'No iniciado', label: 'No iniciado' },
            { value: 'En Ejecución', label: 'En Ejecución' },
            { value: 'Entregado', label: 'Entregado' },
            { value: 'Anulada', label: 'Anulada' },
            { value: 'PENDIENTE_INSUMOS', label: 'Pendiente Insumos' }
        ]
    },
    dia_entrega: {
        title: 'Filtrar por Día de Entrega',
        type: 'checkbox',
        options: [
            { value: 'Lunes', label: 'Lunes' },
            { value: 'Martes', label: 'Martes' },
            { value: 'Miércoles', label: 'Miércoles' },
            { value: 'Jueves', label: 'Jueves' },
            { value: 'Viernes', label: 'Viernes' },
            { value: 'Sábado', label: 'Sábado' }
        ]
    },
    total_dias: {
        title: 'Filtrar por Total de Días',
        type: 'range',
        min: 0,
        max: 30,
        step: 1
    },
    numero_recibo: {
        title: 'Filtrar por Número de Recibo',
        type: 'text',
        placeholder: 'Ej: 001, 002, 003...'
    },
    cliente: {
        title: 'Filtrar por Cliente',
        type: 'text',
        placeholder: 'Nombre del cliente...'
    },
    descripcion: {
        title: 'Filtrar por Descripción',
        type: 'text',
        placeholder: 'Buscar en descripción...'
    },
    cantidad: {
        title: 'Filtrar por Cantidad',
        type: 'range',
        min: 0,
        max: 1000,
        step: 10
    },
    novedades: {
        title: 'Filtrar por Novedades',
        type: 'checkbox',
        options: [
            { value: 'con_novedades', label: 'Con novedades' },
            { value: 'sin_novedades', label: 'Sin novedades' }
        ]
    },
    fecha_creacion: {
        title: 'Filtrar por Fecha de Creación',
        type: 'daterange'
    },
    fecha_estimada: {
        title: 'Filtrar por Fecha Estimada de Entrega',
        type: 'daterange'
    },
    encargado: {
        title: 'Filtrar por Encargado de Orden',
        type: 'text',
        placeholder: 'Nombre del encargado...'
    }
};

// Función para inicializar el modal de forma segura
function initializeFilterModal(filterType) {
    const modal = document.getElementById('filterModal');
    const title = document.getElementById('filterModalTitle');
    const content = document.getElementById('filterOptions');
    
    if (!modal || !title || !content) {
        console.error('[Filtros] No se encontraron elementos del modal');
        return false;
    }
    
    try {
        // Configurar título según el tipo
        const config = getFilterConfig(filterType);
        title.textContent = config.title;
        
        // Generar contenido según el tipo
        content.innerHTML = generateFilterContent(filterType);
        
        return true;
    } catch (error) {
        console.error('[Filtros] Error inicializando modal:', error);
        return false;
    }
}

// Función para enfocar input de forma segura
function focusFirstInputSafely() {
    try {
        const content = document.getElementById('filterOptions');
        if (!content) {
            console.warn('[Filtros] No se encontró contenido del modal');
            return;
        }
        
        // Buscar el primer input de búsqueda
        const searchInput = document.querySelector('.filter-search');
        if (searchInput) {
            setTimeout(() => {
                searchInput.focus();
                console.log('[Filtros] Input de búsqueda enfocado automáticamente');
            }, 100);
            return;
        }
        
        // Buscar el primer input dentro del contenido
        const firstInput = content.querySelector('input[type="text"], input[type="search"]');
        if (firstInput) {
            setTimeout(() => {
                firstInput.focus();
                console.log('[Filtros] Input enfocado automáticamente');
            }, 100);
            
            // Intentar enfocar primer checkbox
            const checkbox = content.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.focus();
                return;
            }
            
            // Como último recurso, enfocar cualquier input
            const anyInput = content.querySelector('input');
            if (anyInput) {
                anyInput.focus();
            }
        }
    } catch (error) {
        console.warn('[Filtros] Error al enfocar input:', error);
    }
}

// Función para filtrar opciones dinámicamente
function filterOptions(searchTerm) {
    const options = document.querySelectorAll('.filter-option');
    let visibleCount = 0;
    
    options.forEach(option => {
        const label = option.querySelector('label');
        if (label) {
            const text = label.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                option.style.display = 'block';
                visibleCount++;
            } else {
                option.style.display = 'none';
            }
        }
    });
    
    console.log(`[Filtros] Búsqueda: "${searchTerm}" - ${visibleCount} opciones visibles`);
}

// Función para abrir el modal de filtros
function openFilterModalImpl(filterType) {
    console.log('[Filtros] Abriendo modal para tipo:', filterType);
    
    // Validación robusta del parámetro
    if (!filterType || typeof filterType !== 'string') {
        console.error('[Filtros] Tipo de filtro inválido:', filterType);
        console.error('[Filtros] Stack trace:', new Error().stack);
        return;
    }
    
    // Verificar que el tipo de filtro exista en la configuración
    if (!filterTypes[filterType]) {
        console.error('[Filtros] Tipo de filtro no configurado:', filterType);
        console.error('[Filtros] Tipos disponibles:', Object.keys(filterTypes));
        return;
    }
    
    currentFilterType = filterType;
    
    // Inicializar modal de forma segura
    if (!initializeFilterModal(filterType)) {
        return;
    }
    
    // Mostrar modal
    const modal = document.getElementById('filterModal');
    if (modal) {
        console.log('[Filtros] Modal encontrado, cambiando display a flex');
        modal.style.display = 'flex';
        
        // Forzar visibilidad con estilos inline
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.zIndex = '99999';
        
        // Verificar que el modal sea visible
        setTimeout(() => {
            const computedStyle = window.getComputedStyle(modal);
            console.log('[Filtros] Estado del modal después de mostrar:', {
                display: computedStyle.display,
                visibility: computedStyle.visibility,
                opacity: computedStyle.opacity,
                zIndex: computedStyle.zIndex,
                offsetWidth: modal.offsetWidth,
                offsetHeight: modal.offsetHeight
            });
            
            if (modal.offsetWidth === 0 || modal.offsetHeight === 0) {
                console.warn('[Filtros] El modal no tiene dimensiones visibles');
            }
        }, 100);
    } else {
        console.error('[Filtros] No se encontró el elemento del modal');
        return;
    }
    
    // Usar un enfoque más robusto para la inicialización
    requestAnimationFrame(() => {
        // Restaurar valores actuales
        try {
            restoreFilterValues(filterType);
        } catch (error) {
            console.warn('[Filtros] Error restaurando valores:', error);
        }
        
        // Enfocar primer input de forma segura
        setTimeout(() => {
            focusFirstInputSafely();
        }, 150);
    });
}

// Función para obtener opciones de filtro dinámicas desde la tabla
function getDynamicFilterOptions(filterType) {
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) {
        console.warn('[Filtros] No se encontró la tabla para generar opciones dinámicas');
        return [];
    }
    
    const rows = tbody.querySelectorAll('tr[data-orden-id]');
    const uniqueValues = new Set();
    
    rows.forEach(row => {
        let cellIndex = -1;
        
        // Determinar el índice de la columna según el tipo de filtro
        switch (filterType) {
            case 'estado':
                cellIndex = 1; // Columna de estado
                break;
            case 'dia_entrega':
                cellIndex = 2; // Columna de día de entrega
                break;
            case 'total_dias':
                cellIndex = 3; // Columna de total de días
                break;
            case 'numero_recibo':
                cellIndex = 4; // Columna de N° Recibo
                break;
            case 'cliente':
                cellIndex = 5; // Columna de Cliente
                break;
            case 'descripcion':
                cellIndex = 6; // Columna de Descripción
                break;
            case 'cantidad':
                cellIndex = 7; // Columna de Cantidad
                break;
            case 'novedades':
                cellIndex = 8; // Columna de Novedades
                break;
            case 'fecha_creacion':
                cellIndex = 9; // Columna de Fecha de creación
                break;
            case 'fecha_estimada':
                cellIndex = 10; // Columna de Fecha estimada entrega
                break;
            case 'encargado':
                cellIndex = 11; // Columna de Encargado orden
                break;
        }
        
        if (cellIndex >= 0) {
            const cell = row.cells[cellIndex];
            if (cell) {
                let value = '';
                
                // Extraer valor según el tipo de columna
                if (filterType === 'estado') {
                    const badge = cell.querySelector('.badge');
                    value = badge ? badge.textContent.trim() : '';
                } else if (filterType === 'total_dias') {
                    const badge = cell.querySelector('.badge');
                    value = badge ? badge.textContent.trim().replace(' días', '').trim() : '';
                } else if (filterType === 'numero_recibo') {
                    value = cell.textContent.trim();
                } else if (filterType === 'cliente') {
                    value = cell.textContent.trim();
                } else if (filterType === 'descripcion') {
                    const span = cell.querySelector('.descripcion-prenda-texto');
                    value = span ? span.textContent.trim() : '';
                } else if (filterType === 'cantidad') {
                    const span = cell.querySelector('span');
                    value = span ? span.textContent.trim() : '';
                } else if (filterType === 'novedades') {
                    const span = cell.querySelector('.novedades-text');
                    value = span ? span.textContent.trim() : '';
                } else if (filterType === 'fecha_creacion' || filterType === 'fecha_estimada') {
                    value = cell.textContent.trim();
                } else if (filterType === 'encargado') {
                    value = cell.textContent.trim();
                } else {
                    value = cell.textContent.trim();
                }
                
                // Limpiar y agregar valor único
                if (value && value !== '' && value !== '-' && value !== 'N/A' && value !== 'Sin novedades') {
                    uniqueValues.add(value);
                }
            }
        }
    });
    
    // Convertir Set a Array y ordenar
    const options = Array.from(uniqueValues).sort();
    console.log(`[Filtros] Opciones dinámicas para ${filterType}:`, options);
    
    return options;
}

// Función para generar opciones de checkbox dinámicas
function generateDynamicCheckboxFilter(filterType) {
    const options = getDynamicFilterOptions(filterType);
    
    if (options.length === 0) {
        return '<div style="padding: 20px; text-align: center; color: #6b7280;">No hay datos disponibles para filtrar</div>';
    }
    
    let html = `
        <input type="text" class="filter-search" placeholder="Buscar..." onkeyup="filterCheckboxOptions('${filterType}')">
        <div class="filter-options" id="filterOptions-${filterType}">
            <div style="padding: 12px; border-bottom: 1px solid rgb(229, 231, 235); margin-bottom: 8px;">
                <button type="button" class="btn-select-all" onclick="selectAllCheckboxFilters('${filterType}')" style="width: 100%; padding: 8px 12px; background: linear-gradient(135deg, rgb(59, 130, 246), rgb(37, 99, 235)); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px; transition: 0.2s; box-shadow: rgba(59, 130, 246, 0.2) 0px 2px 4px;">
                    Seleccionar todas
                </button>
            </div>
    `;
    
    options.forEach(option => {
        const safeValue = option.replace(/[^a-zA-Z0-9\s]/g, '_');
        html += `
            <div class="filter-option">
                <input type="checkbox" id="filter-${filterType}-${safeValue}" value="${option}">
                <label for="filter-${filterType}-${safeValue}">${option}</label>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}

// Función para generar filtro de rango dinámico
function generateDynamicRangeFilter(filterType) {
    const options = getDynamicFilterOptions(filterType);
    
    if (options.length === 0) {
        return '<div style="padding: 20px; text-align: center; color: #6b7280;">No hay datos numéricos disponibles</div>';
    }
    
    // Convertir a números y encontrar min/max
    const numbers = options
        .map(opt => parseFloat(opt.replace(/[^\d.-]/g, '')))
        .filter(num => !isNaN(num));
    
    if (numbers.length === 0) {
        return '<div style="padding: 20px; text-align: center; color: #6b7280;">No hay datos numéricos disponibles</div>';
    }
    
    const min = Math.min(...numbers);
    const max = Math.max(...numbers);
    
    return `
        <div style="padding: 16px 0;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <span style="font-size: 14px; font-weight: 500; min-width: 60px;">Mín: <span id="range-min-${filterType}">${min}</span></span>
                <input type="range" 
                       id="filter-${filterType}-range-min" 
                       min="${min}" 
                       max="${max}" 
                       step="${(max - min) / 10 || 1}" 
                       value="${min}"
                       style="flex: 1;"
                       oninput="updateRangeDisplay('${filterType}', 'min')">
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 14px; font-weight: 500; min-width: 60px;">Máx: <span id="range-max-${filterType}">${max}</span></span>
                <input type="range" 
                       id="filter-${filterType}-range-max" 
                       min="${min}" 
                       max="${max}" 
                       step="${(max - min) / 10 || 1}" 
                       value="${max}"
                       style="flex: 1;"
                       oninput="updateRangeDisplay('${filterType}', 'max')">
            </div>
        </div>
    `;
}

// Función para generar filtro de texto dinámico
function generateDynamicTextFilter(filterType) {
    const options = getDynamicFilterOptions(filterType);
    
    if (options.length === 0) {
        return '<div style="padding: 20px; text-align: center; color: #6b7280;">No hay datos de texto disponibles</div>';
    }
    
    // Convertir opciones al formato esperado por generateCheckboxFilter
    const checkboxOptions = options.map(option => ({
        value: option,
        label: option
    }));
    
    return generateCheckboxFilter(filterType, checkboxOptions);
}

// Función para generar filtro de rango de fechas dinámico
function generateDynamicDateRangeFilter(filterType) {
    const options = getDynamicFilterOptions(filterType);
    
    if (options.length === 0) {
        return '<div style="padding: 20px; text-align: center; color: #6b7280;">No hay fechas disponibles</div>';
    }
    
    // Intentar parsear fechas
    const dates = options
        .map(opt => {
            // Intentar diferentes formatos de fecha
            let date = null;
            
            // Formato DD/MM/YYYY
            if (opt.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
                const [day, month, year] = opt.split('/');
                date = new Date(`${year}-${month}-${day}`);
            }
            // Formato YYYY-MM-DD
            else if (opt.match(/^\d{4}-\d{2}-\d{2}$/)) {
                date = new Date(opt);
            }
            // Formato DD/MM/YY
            else if (opt.match(/^\d{2}\/\d{2}\/\d{2}$/)) {
                const [day, month, year] = opt.split('/');
                const fullYear = 2000 + parseInt(year);
                date = new Date(`${fullYear}-${month}-${day}`);
            }
            
            return date;
        })
        .filter(date => !isNaN(date.getTime()));
    
    if (dates.length === 0) {
        return '<div style="padding: 20px; text-align: center; color: #6b7280;">No hay fechas válidas disponibles</div>';
    }
    
    const minDate = new Date(Math.min(...dates));
    const maxDate = new Date(Math.max(...dates));
    
    // Formatear fechas para inputs
    const minDateStr = minDate.toISOString().split('T')[0];
    const maxDateStr = maxDate.toISOString().split('T')[0];
    
    return `
        <div style="padding: 16px 0;">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Fecha de inicio:</label>
                <input type="date" 
                       id="filter-${filterType}-start" 
                       class="filter-date-input"
                       min="${minDateStr}" 
                       max="${maxDateStr}"
                       value="${minDateStr}"
                       style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>
            <div>
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Fecha de fin:</label>
                <input type="date" 
                       id="filter-${filterType}-end" 
                       class="filter-date-input"
                       min="${minDateStr}" 
                       max="${maxDateStr}"
                       value="${maxDateStr}"
                       style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; box-sizing: border-box;">
            </div>
        </div>
        <div style="margin-top: 12px; font-size: 12px; color: #6b7280;">
            Rango de fechas: ${minDateStr} a ${maxDateStr}
        </div>
    `;
}

// Función para generar contenido del filtro según el tipo
function generateFilterContent(filterType) {
    console.log(`[Filtros] Generando contenido dinámico para ${filterType}`);
    
    // Generar contenido dinámico basado en los datos reales de la tabla
    switch (filterType) {
        case 'estado':
        case 'dia_entrega':
        case 'novedades':
            return generateDynamicCheckboxFilter(filterType);
        case 'total_dias':
        case 'cantidad':
            return generateDynamicRangeFilter(filterType);
        case 'numero_recibo':
        case 'cliente':
        case 'descripcion':
        case 'encargado':
            return generateDynamicTextFilter(filterType);
        case 'fecha_creacion':
        case 'fecha_estimada':
            return generateDynamicDateRangeFilter(filterType);
        default:
            return '<div style="padding: 20px; text-align: center; color: #6b7280;">Tipo de filtro no implementado</div>';
    }
}

// Generar filtro de checkboxes
function generateCheckboxFilter(filterType, options) {
    let html = `
        <input type="text" class="filter-search" placeholder="Buscar..." onkeyup="filterCheckboxOptions('${filterType}')">
        <div class="filter-options" id="filterOptions-${filterType}">
            <div style="padding: 12px; border-bottom: 1px solid rgb(229, 231, 235); margin-bottom: 8px;">
                <button type="button" class="btn-select-all" onclick="selectAllCheckboxFilters('${filterType}')" style="width: 100%; padding: 8px 12px; background: linear-gradient(135deg, rgb(59, 130, 246), rgb(37, 99, 235)); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 14px; transition: 0.2s; box-shadow: rgba(59, 130, 246, 0.2) 0px 2px 4px;">
                    Seleccionar todas
                </button>
            </div>
    `;
    
    options.forEach(option => {
        html += `
            <div class="filter-option">
                <input type="checkbox" id="filter-${filterType}-${option.value.replace(/\s+/g, '_')}" value="${option.value}">
                <label for="filter-${filterType}-${option.value.replace(/\s+/g, '_')}">${option.label}</label>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}

// Generar filtro de texto
function generateTextFilter(filterType, placeholder) {
    return `
        <div style="padding: 16px 0;">
            <input type="text" 
                   id="filter-${filterType}-text" 
                   class="filter-text-input" 
                   placeholder="${placeholder}" 
                   style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;"
                   onkeyup="handleTextFilter('${filterType}')">
            <div style="margin-top: 12px; font-size: 12px; color: #6b7280;">
                Escribe para buscar coincidencias parciales
            </div>
        </div>
    `;
}

// Generar filtro de rango
function generateRangeFilter(filterType, min, max, step) {
    return `
        <div style="padding: 16px 0;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                <span style="font-size: 14px; font-weight: 500; min-width: 60px;">Mín: <span id="range-min-${filterType}">${min}</span></span>
                <input type="range" 
                       id="filter-${filterType}-range-min" 
                       min="${min}" 
                       max="${max}" 
                       step="${step}" 
                       value="${min}"
                       style="flex: 1;"
                       oninput="updateRangeDisplay('${filterType}', 'min')">
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span style="font-size: 14px; font-weight: 500; min-width: 60px;">Máx: <span id="range-max-${filterType}">${max}</span></span>
                <input type="range" 
                       id="filter-${filterType}-range-max" 
                       min="${min}" 
                       max="${max}" 
                       step="${step}" 
                       value="${max}"
                       style="flex: 1;"
                       oninput="updateRangeDisplay('${filterType}', 'max')">
            </div>
        </div>
    `;
}

// Generar filtro de rango de fechas
function generateDateRangeFilter(filterType) {
    return `
        <div style="padding: 16px 0;">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Fecha de inicio:</label>
                <input type="date" 
                       id="filter-${filterType}-start" 
                       class="filter-date-input"
                       style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
            </div>
            <div>
                <label style="display: block; font-size: 14px; font-weight: 500; margin-bottom: 8px;">Fecha de fin:</label>
                <input type="date" 
                       id="filter-${filterType}-end" 
                       class="filter-date-input"
                       style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;">
            </div>
        </div>
    `;
}

// Función para cerrar el modal de filtros
function closeFilterModal() {
    console.log('[Filtros] Cerrando modal de filtros');
    const modal = document.getElementById('filterModal');
    if (modal) {
        modal.style.display = 'none';
    }
    currentFilterType = '';
}

// Función para filtrar opciones de checkboxes
function filterCheckboxOptions(filterType) {
    const searchTerm = document.querySelector('.filter-search').value.toLowerCase();
    const options = document.querySelectorAll(`#filterOptions-${filterType} .filter-option`);
    
    options.forEach(option => {
        const label = option.querySelector('label').textContent.toLowerCase();
        if (label.includes(searchTerm)) {
            option.style.display = 'flex';
        } else {
            option.style.display = 'none';
        }
    });
}

// Función para seleccionar todos los checkboxes de un tipo
function selectAllCheckboxFilters(filterType) {
    const checkboxes = document.querySelectorAll(`#filterOptions-${filterType} input[type="checkbox"]`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });
    
    console.log(`[Filtros] ${allChecked ? 'Deseleccionando' : 'Seleccionando'} todos los filtros de ${filterType}`);
}

// Función para actualizar display de rango
function updateRangeDisplay(filterType, type) {
    const value = document.getElementById(`filter-${filterType}-range-${type}`).value;
    document.getElementById(`range-${type}-${filterType}`).textContent = value;
}

// Función para manejar filtro de texto
function handleTextFilter(filterType) {
    const value = document.getElementById(`filter-${filterType}-text`).value;
    console.log(`[Filtros] Texto filtrado para ${filterType}:`, value);
}

// Función para restaurar valores del filtro
function restoreFilterValues(filterType) {
    const config = filterTypes[filterType];
    const values = activeFilters[filterType] || [];
    
    switch (config.type) {
        case 'checkbox':
            values.forEach(value => {
                const checkbox = document.getElementById(`filter-${filterType}-${value.replace(/\s+/g, '_')}`);
                if (checkbox) checkbox.checked = true;
            });
            break;
        case 'text':
            const textInput = document.getElementById(`filter-${filterType}-text`);
            if (textInput && values.length > 0) textInput.value = values[0];
            break;
        case 'range':
            if (values.length >= 2) {
                const minInput = document.getElementById(`filter-${filterType}-range-min`);
                const maxInput = document.getElementById(`filter-${filterType}-range-max`);
                if (minInput) {
                    minInput.value = values[0];
                    updateRangeDisplay(filterType, 'min');
                }
                if (maxInput) {
                    maxInput.value = values[1];
                    updateRangeDisplay(filterType, 'max');
                }
            }
            break;
        case 'daterange':
            if (values.length >= 2) {
                const startInput = document.getElementById(`filter-${filterType}-start`);
                const endInput = document.getElementById(`filter-${filterType}-end`);
                if (startInput) startInput.value = values[0];
                if (endInput) endInput.value = values[1];
            }
            break;
    }
}

// Función para obtener valores del filtro actual
function getCurrentFilterValues() {
    const config = filterTypes[currentFilterType];
    let values = [];
    
    switch (config.type) {
        case 'checkbox':
            const checkboxes = document.querySelectorAll(`#filterOptions-${currentFilterType} input[type="checkbox"]:checked`);
            values = Array.from(checkboxes).map(cb => cb.value);
            break;
        case 'text':
            const textInput = document.getElementById(`filter-${currentFilterType}-text`);
            if (textInput && textInput.value.trim()) {
                values = [textInput.value.trim()];
            }
            break;
        case 'range':
            const minInput = document.getElementById(`filter-${currentFilterType}-range-min`);
            const maxInput = document.getElementById(`filter-${currentFilterType}-range-max`);
            if (minInput && maxInput) {
                values = [parseInt(minInput.value), parseInt(maxInput.value)];
            }
            break;
        case 'daterange':
            const startInput = document.getElementById(`filter-${currentFilterType}-start`);
            const endInput = document.getElementById(`filter-${currentFilterType}-end`);
            if (startInput && endInput && startInput.value && endInput.value) {
                values = [startInput.value, endInput.value];
            }
            break;
    }
    
    return values;
}

// Función para aplicar filtros
function applyFilters() {
    const values = getCurrentFilterValues();
    activeFilters[currentFilterType] = values;
    
    console.log(`[Filtros] Aplicando filtros para ${currentFilterType}:`, values);
    
    // Cerrar modal
    closeFilterModal();
    
    // Aplicar filtrado a la tabla
    filterTable();
    
    // Actualizar estado visual de todos los botones de filtro
    updateAllFilterButtons();
}

// Función para limpiar filtros
function resetFilters() {
    console.log(`[Filtros] Limpiando filtros para ${currentFilterType}`);
    activeFilters[currentFilterType] = [];
    
    // Limpiar controles según el tipo
    const config = filterTypes[currentFilterType];
    switch (config.type) {
        case 'checkbox':
            const checkboxes = document.querySelectorAll(`#filterOptions-${currentFilterType} input[type="checkbox"]`);
            checkboxes.forEach(cb => cb.checked = false);
            break;
        case 'text':
            const textInput = document.getElementById(`filter-${currentFilterType}-text`);
            if (textInput) textInput.value = '';
            break;
        case 'range':
            const minInput = document.getElementById(`filter-${currentFilterType}-range-min`);
            const maxInput = document.getElementById(`filter-${currentFilterType}-range-max`);
            if (minInput) {
                minInput.value = config.min;
                updateRangeDisplay(currentFilterType, 'min');
            }
            if (maxInput) {
                maxInput.value = config.max;
                updateRangeDisplay(currentFilterType, 'max');
            }
            break;
        case 'daterange':
            const startInput = document.getElementById(`filter-${currentFilterType}-start`);
            const endInput = document.getElementById(`filter-${currentFilterType}-end`);
            if (startInput) startInput.value = '';
            if (endInput) endInput.value = '';
            break;
    }
    
    // Aplicar filtrado
    filterTable();
    
    // Actualizar botones
    updateAllFilterButtons();
    
    // Cerrar modal
    closeFilterModal();
}

// Función para actualizar el estado visual de todos los botones de filtro
function updateAllFilterButtons() {
    Object.keys(activeFilters).forEach(filterType => {
        const button = document.querySelector(`[onclick="openFilterModal('${filterType}')"]`);
        if (button) {
            if (activeFilters[filterType].length > 0) {
                button.style.color = '#fbbf24';
                button.title = `${filterTypes[filterType].title} (${activeFilters[filterType].length} activos)`;
            } else {
                button.style.color = 'white';
                button.title = filterTypes[filterType].title;
            }
        }
    });
}

// Función para filtrar la tabla
function filterTable() {
    console.log('[Filtros] Aplicando filtrado múltiple:', activeFilters);
    
    // Mostrar indicador de carga
    showLoadingIndicator();
    
    // Construir URL con todos los filtros activos
    const url = new URL(window.location.origin + '/recibos-costura');
    
    // Agregar cada tipo de filtro activo
    Object.keys(activeFilters).forEach(filterType => {
        const values = activeFilters[filterType];
        if (values.length > 0) {
            url.searchParams.append(filterType, JSON.stringify(values));
        }
    });
    
    console.log('[Filtros] URL construida:', url.toString());
    
    // Hacer solicitud AJAX para obtener datos filtrados
    fetch(url.toString(), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('[Filtros] Datos recibidos del servidor:', data);
        
        if (data.success) {
            // Actualizar tabla con nuevos datos
            updateTableWithData(data.recibos);
            
            // Mostrar mensaje de no resultados si es necesario
            showNoResultsMessage(data.recibos.length === 0);
            
            console.log(`[Filtros] Tabla actualizada: ${data.recibos.length} recibos mostrados`);
        } else {
            console.error('[Filtros] Error del servidor:', data.message);
            showErrorMessage('Error al aplicar filtros: ' + data.message);
        }
    })
    .catch(error => {
        console.error('[Filtros] Error en la solicitud:', error);
        showErrorMessage('Error de conexión al aplicar filtros');
        
        // Fallback: filtrar localmente si hay error
        filterTableLocally();
    })
    .finally(() => {
        hideLoadingIndicator();
    });
}

// Función para actualizar la tabla con nuevos datos
function updateTableWithData(recibos) {
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) return;
    
    // Limpiar tabla actual
    tbody.innerHTML = '';
    
    if (recibos.length === 0) {
        // La función showNoResultsMessage se encargará de mostrar el mensaje
        return;
    }
    
    // Generar HTML para cada recibo
    recibos.forEach(recibo => {
        const row = createReciboRow(recibo);
        tbody.appendChild(row);
    });
    
    console.log(`[Filtros] ${recibos.length} filas agregadas a la tabla`);
}

// Función para crear una fila de recibo
function createReciboRow(recibo) {
    const tr = document.createElement('tr');
    
    // Determinar clases de días
    const dias = recibo.dias_calculados || 0;
    let diasClase = '';
    if (dias >= 14) diasClase = 'dias-mayor-15';
    else if (dias >= 10) diasClase = 'dias-10-15';
    else if (dias >= 5) diasClase = 'dias-5-9';
    else if (dias > 0) diasClase = 'dias-0-4';

    tr.className = diasClase;
    tr.setAttribute('data-orden-id', recibo.id);
    tr.setAttribute('data-pedido-id', recibo.pedido_produccion_id || '');
    tr.setAttribute('data-numero-recibo', recibo.consecutivo_actual || '');

    // Badge de estado
    let estadoBadge = 'bg-secondary';
    let estadoTexto = recibo.estado;
    if (recibo.estado === 'En Ejecución') estadoBadge = 'bg-primary';
    else if (recibo.estado === 'No iniciado') estadoBadge = 'bg-warning';
    else if (recibo.estado === 'PENDIENTE_INSUMOS') { estadoBadge = 'bg-info'; estadoTexto = 'Pendiente Insumos'; }

    // Badge de área
    let areaBadge = 'bg-secondary';
    const area = recibo.pedido_info?.area || recibo.area || 'Insumos';
    if (area === 'Corte') areaBadge = 'bg-success';
    else if (area === 'Insumos') areaBadge = 'bg-info';
    else if (area === 'Costura') areaBadge = 'bg-primary';

    // Badge de días
    let diasBadge = 'bg-secondary';
    if (dias >= 14) diasBadge = 'bg-danger';
    else if (dias >= 5) diasBadge = 'bg-warning';
    else if (dias > 0) diasBadge = 'bg-success';

    tr.innerHTML = `
        <td class="acciones-column" style="text-align: center; position: relative;">
            <button class="btn-ver-dropdown" title="Ver Opciones"
                data-menu-id="menu-recibo-${recibo.id}"
                data-pedido-id="${recibo.pedido_produccion_id}"
                data-prenda-id="${recibo.prenda_id || ''}">
                <i class="fas fa-eye"></i>
            </button>
        </td>
        <td><span class="badge ${estadoBadge}">${estadoTexto}</span></td>
        <td><span class="badge ${areaBadge}">${area}</span></td>
        <td style="text-align: center;">
            <span class="badge ${diasBadge}" style="font-weight: 600;">${dias} días</span>
        </td>
        <td style="text-align: center;">
            <span style="font-weight: 600;">${recibo.consecutivo_actual}</span>
        </td>
        <td style="text-align: center;">
            <span>${recibo.pedido_info?.cliente || 'N/A'}</span>
        </td>
        <td>
            <div class="table-cell" style="flex: 10;">
                <div class="cell-content" style="justify-content: flex-start; cursor: pointer;" onclick="console.log('[ONCLICK TABLE CELL] 📌 Click en descripción'); console.log('[ONCLICK TABLE CELL] 📌 Datos:', {pedidoId: ${recibo.pedido_produccion_id}, prendaId: ${recibo.prenda_id}}); event.stopPropagation(); obtenerDatosPrendaRecibo('Descripción', ${recibo.pedido_produccion_id}, ${recibo.prenda_id})">
                    <span style="color: #6b7280; font-size: 0.875rem; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="Click para ver completo">
                        <span class="descripcion-prenda-texto">${recibo.nombre_prenda || 'Sin prendas'}</span> <span style="color: #3b82f6; font-weight: 600;">...</span>
                    </span>
                </div>
            </div>
        </td>
        <td><span class="text-muted">-</span></td>
        <td>
            <div class="table-cell" style="flex: 0 0 120px;">
                <div class="cell-content" style="justify-content: flex-start;">
                    <button class="btn-edit-novedades"
                        data-pedido-id="${recibo.pedido_produccion_id}"
                        data-numero-recibo="${recibo.consecutivo_actual}"
                        data-novedades=""
                        onclick="event.stopPropagation(); openNovedadesModalRecibo(this)"
                        title="Ver novedades del recibo" type="button">
                        <span class="novedades-text empty">Sin novedades</span>
                        <span class="material-symbols-rounded">edit</span>
                    </button>
                </div>
            </div>
        </td>
        <td><span>${recibo.pedido_info?.fecha_creacion_orden ? new Date(recibo.pedido_info.fecha_creacion_orden).toLocaleDateString('es-ES') : '-'}</span></td>
        <td><span class="fecha-estimada-span text-muted">-</span></td>
        <td><span class="text-muted">-</span></td>
    `;

    return tr;
}

// Función de fallback para filtrado local
function filterTableLocally() {
    console.log('[Filtros] Aplicando filtrado local (fallback)');
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) return;
    
    const rows = tbody.querySelectorAll('tr[data-orden-id]');
    let visibleCount = 0;
    
    rows.forEach(row => {
        const shouldShow = shouldShowRow(row);
        row.style.display = shouldShow ? '' : 'none';
        if (shouldShow) visibleCount++;
    });
    
    console.log(`[Filtros] Filtrado local aplicado: ${visibleCount} filas visibles de ${rows.length}`);
    showNoResultsMessage(visibleCount === 0 && rows.length > 0);
}

// Función para mostrar indicador de carga
function showLoadingIndicator() {
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) return;
    
    // Eliminar indicador existente
    const existing = tbody.querySelector('.loading-indicator');
    if (existing) existing.remove();
    
    const loadingRow = document.createElement('tr');
    loadingRow.className = 'loading-indicator';
    loadingRow.innerHTML = `
        <td colspan="13" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Cargando...</span>
            </div>
            <div class="mt-2">Aplicando filtros...</div>
        </td>
    `;
    tbody.appendChild(loadingRow);
}

// Función para ocultar indicador de carga
function hideLoadingIndicator() {
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) return;
    
    const loading = tbody.querySelector('.loading-indicator');
    if (loading) loading.remove();
}

// Función para mostrar mensaje de error
function showErrorMessage(message) {
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) return;
    
    // Eliminar mensaje existente
    const existing = tbody.querySelector('.error-message');
    if (existing) existing.remove();
    
    const errorRow = document.createElement('tr');
    errorRow.className = 'error-message';
    errorRow.innerHTML = `
        <td colspan="13" class="text-center py-4">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                ${message}
            </div>
        </td>
    `;
    tbody.appendChild(errorRow);
}

// Función para determinar si una fila debe mostrarse
function shouldShowRow(row) {
    // Si no hay filtros activos, mostrar todo
    if (activeFilters.length === 0) {
        return true;
    }
    
    // Obtener el estado de la fila
    const estadoElement = row.querySelector('td:nth-child(2) .badge');
    if (!estadoElement) return true;
    
    const estadoTexto = estadoElement.textContent.trim();
    
    // Verificar si el estado está en los filtros activos
    return activeFilters.includes(estadoTexto);
}

// Función para mostrar mensaje de no resultados
function showNoResultsMessage(show) {
    const tbody = document.getElementById('tablaRecibosBody');
    if (!tbody) return;
    
    // Eliminar mensaje existente si lo hay
    const existingMessage = tbody.querySelector('.no-results-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    if (show) {
        const messageRow = document.createElement('tr');
        messageRow.className = 'no-results-message';
        messageRow.innerHTML = `
            <td colspan="13" class="text-center py-4">
                <div class="alert alert-warning">
                    <i class="fas fa-filter"></i>
                    No se encontraron recibos con los estados seleccionados.
                    <button type="button" onclick="resetFilters()" class="btn btn-sm btn-link ml-2">
                        Limpiar filtros
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(messageRow);
    }
}

// Función para actualizar el estado visual del botón de filtro
function updateFilterButton() {
    const filterBtn = document.querySelector('.filter-btn');
    if (!filterBtn) return;
    
    if (activeFilters.length > 0) {
        // Cambiar color del botón para indicar que hay filtros activos
        filterBtn.style.color = '#fbbf24';
        filterBtn.title = `Filtrar por estado (${activeFilters.length} activos)`;
    } else {
        // Restaurar color original
        filterBtn.style.color = 'white';
        filterBtn.title = 'Filtrar por estado';
    }
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(e) {
    const modal = document.getElementById('filterModal');
    if (modal && e.target === modal) {
        closeFilterModal();
    }
});

// Cerrar modal con la tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeFilterModal();
    }
});

// Manejador de eventos delegado para botones de filtro (fallback)
document.addEventListener('click', function(e) {
    const filterBtn = e.target.closest('.filter-btn');
    
    if (filterBtn) {
        e.preventDefault();
        e.stopPropagation();
        
        // Obtener el tipo de filtro desde el onclick o data attribute
        let filterType = null;
        
        // Intentar obtener desde el atributo onclick
        const onclickAttr = filterBtn.getAttribute('onclick');
        if (onclickAttr) {
            const match = onclickAttr.match(/openFilterModal\(['"]([^'"]+)['"]\)/);
            if (match) {
                filterType = match[1];
            }
        }
        
        // Intentar obtener desde data attribute si existe
        if (!filterType) {
            filterType = filterBtn.getAttribute('data-filter-type');
        }
        
        console.log('[Filtros] Botón clickeado, tipo detectado:', filterType);
        
        if (filterType) {
            openFilterModal(filterType);
        } else {
            console.error('[Filtros] No se pudo determinar el tipo de filtro');
            console.log('[Filtros] Atributos del botón:', {
                onclick: onclickAttr,
                dataType: filterBtn.getAttribute('data-filter-type'),
                className: filterBtn.className,
                id: filterBtn.id
            });
        }
    }
});

// ============================================================
// TIEMPO REAL: Escuchar canal recibos-costura via WebSocket/Reverb
// ============================================================
(function() {
    console.log('[RecibosCostura-RT] Inicializando listener en tiempo real...');

    function suscribirCanal() {
        const echoInstance = window.EchoInstance;
        if (!echoInstance) {
            console.warn('[RecibosCostura-RT] EchoInstance no disponible aún');
            return;
        }

        console.log('[RecibosCostura-RT] Suscribiendo al canal recibos-costura...');

        echoInstance.channel('recibos-costura')
            .listen('.recibo.aprobado', function(data) {
                console.log('[RecibosCostura-RT] Recibo aprobado recibido:', data);
                agregarReciboEnTiempoReal(data);
            });

        console.log('[RecibosCostura-RT] Suscripción activa al canal recibos-costura');
    }

    /**
     * Agregar una nueva fila a la tabla cuando llega un recibo aprobado
     */
    function agregarReciboEnTiempoReal(data) {
        const reciboId = data.recibo_id;

        // Verificar que no exista ya en la tabla
        if (document.querySelector(`tr[data-orden-id="${reciboId}"]`)) {
            console.log('[RecibosCostura-RT] Recibo ya existe en tabla, ignorando:', reciboId);
            return;
        }

        // Obtener datos completos del recibo vía API
        fetch(`/recibos-costura/recibo/${reciboId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (!result.success || !result.recibo) {
                console.warn('[RecibosCostura-RT] No se pudo obtener datos del recibo:', reciboId);
                return;
            }

            const recibo = result.recibo;
            console.log('[RecibosCostura-RT] Datos completos del recibo:', recibo);

            insertarFilaRecibo(recibo);
            mostrarNotificacionNuevoRecibo(recibo);
        })
        .catch(error => {
            console.error('[RecibosCostura-RT] Error al obtener datos del recibo:', error);
        });
    }

    /**
     * Insertar una nueva fila en la tabla de recibos
     */
    function insertarFilaRecibo(recibo) {
        const tbody = document.getElementById('tablaRecibosBody');
        if (!tbody) {
            console.warn('[RecibosCostura-RT] No se encontró tablaRecibosBody');
            return;
        }

        // Remover fila de "No se encontraron recibos" si existe
        const filaVacia = tbody.querySelector('td[colspan]');
        if (filaVacia) {
            filaVacia.closest('tr').remove();
        }

        // Determinar clases de días
        const dias = recibo.dias_calculados || 0;
        let diasClase = '';
        if (dias >= 14) diasClase = 'dias-mayor-15';
        else if (dias >= 10) diasClase = 'dias-10-15';
        else if (dias >= 5) diasClase = 'dias-5-9';
        else if (dias > 0) diasClase = 'dias-0-4';

        // Badge de estado
        let estadoBadge = 'bg-secondary';
        let estadoTexto = recibo.estado;
        if (recibo.estado === 'En Ejecución') estadoBadge = 'bg-primary';
        else if (recibo.estado === 'No iniciado') estadoBadge = 'bg-warning';
        else if (recibo.estado === 'PENDIENTE_INSUMOS') { estadoBadge = 'bg-info'; estadoTexto = 'Pendiente Insumos'; }

        // Badge de area
        let areaBadge = 'bg-secondary';
        if (recibo.area === 'Corte') areaBadge = 'bg-success';
        else if (recibo.area === 'Insumos') areaBadge = 'bg-info';

        // Badge de días
        let diasBadge = 'bg-secondary';
        if (dias >= 14) diasBadge = 'bg-danger';
        else if (dias >= 5) diasBadge = 'bg-warning';
        else if (dias > 0) diasBadge = 'bg-success';

        const tr = document.createElement('tr');
        tr.className = diasClase;
        tr.setAttribute('data-orden-id', recibo.id);
        tr.setAttribute('data-pedido-id', recibo.pedido_produccion_id || '');
        tr.setAttribute('data-numero-recibo', recibo.consecutivo_actual || '');

        tr.innerHTML = `
            <td class="acciones-column" style="text-align: center; position: relative;">
                <button class="btn-ver-dropdown" title="Ver Opciones"
                    data-menu-id="menu-recibo-${recibo.id}"
                    data-pedido-id="${recibo.pedido_produccion_id}"
                    data-prenda-id="${recibo.prenda_id || ''}">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
            <td><span class="badge ${estadoBadge}">${estadoTexto}</span></td>
            <td><span class="badge ${areaBadge}">${recibo.area || 'Insumos'}</span></td>
            <td style="text-align: center;">
                <span class="badge ${diasBadge}" style="font-weight: 600;">${dias} días</span>
            </td>
            <td style="text-align: center;">
                <span style="font-weight: 600;">${recibo.consecutivo_actual}</span>
            </td>
            <td style="text-align: center;">
                <span>${recibo.cliente || 'N/A'}</span>
            </td>
            <td>
                <div class="table-cell" style="flex: 10;">
                    <div class="cell-content" style="justify-content: flex-start; cursor: pointer;">
                        <span class="descripcion-prenda-texto" style="color: #6b7280; font-size: 0.875rem; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            ${recibo.nombre_prenda || 'Sin prendas'} <span style="color: #3b82f6; font-weight: 600;">...</span>
                        </span>
                    </div>
                </div>
            </td>
            <td><span class="text-muted">-</span></td>
            <td>
                <div class="table-cell" style="flex: 0 0 120px;">
                    <div class="cell-content" style="justify-content: flex-start;">
                        <button class="btn-edit-novedades"
                            data-pedido-id="${recibo.pedido_produccion_id}"
                            data-numero-recibo="${recibo.consecutivo_actual}"
                            data-novedades=""
                            onclick="event.stopPropagation(); openNovedadesModalRecibo(this)"
                            title="Ver novedades del recibo" type="button">
                            <span class="novedades-text empty">Sin novedades</span>
                            <span class="material-symbols-rounded">edit</span>
                        </button>
                    </div>
                </div>
            </td>
            <td><span>${recibo.fecha_creacion || '-'}</span></td>
            <td><span class="fecha-estimada-span text-muted">-</span></td>
            <td><span class="text-muted">-</span></td>
        `;

        // Insertar al inicio de la tabla (los más recientes primero)
        tbody.insertBefore(tr, tbody.firstChild);

        // Animación de entrada
        tr.style.transition = 'background-color 1.5s ease';
        tr.style.backgroundColor = '#d4edda';
        setTimeout(() => { tr.style.backgroundColor = ''; }, 2000);

        console.log('[RecibosCostura-RT] Fila insertada para recibo:', recibo.consecutivo_actual);
    }

    /**
     * Mostrar notificación visual de nuevo recibo
     */
    function mostrarNotificacionNuevoRecibo(recibo) {
        // Crear notificación toast
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed; top: 20px; right: 20px; z-index: 99999;
            background: #28a745; color: white; padding: 12px 20px;
            border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            font-size: 14px; font-weight: 500; max-width: 400px;
            animation: slideInRight 0.3s ease-out;
        `;
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-check-circle" style="font-size: 18px;"></i>
                <div>
                    <div style="font-weight: 600;">Nuevo recibo aprobado</div>
                    <div style="font-size: 12px; opacity: 0.9;">Recibo #${recibo.consecutivo_actual} - ${recibo.cliente || ''}</div>
                </div>
            </div>
        `;
        document.body.appendChild(toast);

        // Remover después de 4 segundos
        setTimeout(() => {
            toast.style.transition = 'opacity 0.5s ease';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    // Suscribir usando waitForEcho o directamente si ya está listo
    if (typeof window.waitForEcho === 'function') {
        window.waitForEcho(suscribirCanal);
    } else {
        // Fallback: esperar a que EchoInstance esté disponible
        const checkInterval = setInterval(() => {
            if (window.EchoInstance) {
                clearInterval(checkInterval);
                suscribirCanal();
            }
        }, 500);
        // Dejar de intentar después de 30 segundos
        setTimeout(() => clearInterval(checkInterval), 30000);
    }
})();

// Exportar funciones al objeto window para que estén disponibles globalmente
window.openFilterModalImpl = openFilterModalImpl;
window.closeFilterModalImpl = closeFilterModal;
window.resetFiltersImpl = resetFilters;
window.applyFiltersImpl = applyFilters;
window.selectAllCheckboxFiltersImpl = selectAllCheckboxFilters;
window.filterCheckboxOptionsImpl = filterCheckboxOptions;
</script>
@endpush
