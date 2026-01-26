/**
 * Modal Agregar EPP al Pedido - REFACTORIZADO CON SOLID
 * Solo HTML del modal + funciones que deleguen a servicios
 * Toda la lógica está en: /epp/services/
 * 
 * Cumplimiento SOLID:
 * - S: Responsabilidad única - Solo crear modal y delegar
 * - O: Abierto a extensión - Servicios son extensibles
 * - L: Sustitución de Liskov - Servicios intercambiables
 * - I: Segregación de interfaz - EppModalInterface proporciona interfaz clara
 * - D: Inversión de dependencias - Depende de abstracciones (servicios)
 */

let eppHttpService = null;

function inicializarEppService() {
    if (!eppHttpService && typeof EppHttpService !== 'undefined') {
        eppHttpService = new EppHttpService('/api');
    }
}

/**
 * Crear y mostrar el modal dinámicamente
 */
function crearModalAgregarEPP() {
    if (document.getElementById('modal-agregar-epp')) {
        return;
    }

    inicializarEppService();
    const modalHTML = window.EppModalTemplate.getHTML();
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

// ============================================================================
// FUNCIONES DELEGADAS A SERVICIOS - Interfaz Pública
// ============================================================================

function abrirModalAgregarEPP() {
    inicializarEppService();
    const modal = document.getElementById('modal-agregar-epp');
    if (!modal) crearModalAgregarEPP();
    window.EppModalInterface?.abrirModal();
}

function cerrarModalAgregarEPP() {
    window.EppModalInterface?.cerrarModal();
}

function mostrarFormularioCrearEPPNuevo() {
    window.EppModalInterface?.mostrarFormularioCrear();
}

function ocultarFormularioCrearEPP() {
    window.EppModalInterface?.ocultarFormularioCrear();
}

async function crearEPPNuevoYAgregar() {
    try {
        await window.EppModalInterface?.crearEPP();
    } catch (error) {

    }
}

function agregarEPPAlPedido() {
    window.EppModalInterface?.agregarEPP();
}

function editarItemEPP(id, nombre, codigo, categoria, cantidad, observaciones, imagenes) {
    window.EppModalInterface?.editarEPP(id, nombre, codigo, categoria, cantidad, observaciones, imagenes);
}

async function editarEPPDesdeDB(eppId) {
    return window.EppModalInterface?.editarEPPDesdeDB(eppId);
}

function eliminarItemEPP(id) {
    window.EppModalInterface?.eliminarEPP(id);
}

function filtrarEPPBuscador(valor) {
    window.EppModalInterface?.filtrarEPP(valor);
}

function actualizarEstilosBotonEPP() {
    window.EppModalInterface?.actualizarBoton();
}

async function manejarSeleccionImagenes(event) {
    return window.EppModalInterface?.manejarImagenes(event);
}

async function eliminarImagenCargada(imagenId) {
    return window.EppModalInterface?.eliminarImagen(imagenId);
}

function limpiarModalEPP() {
    if (window.eppService) {
        window.eppService.limpiarModal();
    }
}

function cargarCategoriasEnFormulario() {
    if (window.eppService) {
        window.eppService.cargarCategorias();
    }
}

function cargarEPPBuscador() {
    if (window.eppService) {
        window.eppService.cargarEPP();
    }
}

function mostrarErrorEPP(mensaje) {
    if (window.eppFormManager) {
        window.eppFormManager.mostrarErrorBuscador(mensaje);
    }
}

function crearItemEPP(id, nombre, codigo, categoria, cantidad, observaciones, imagenes = []) {
    if (window.eppItemManager) {
        window.eppItemManager.crearItem(id, nombre, codigo, categoria, cantidad, observaciones, imagenes);
    }
}

function configurarEventListenersItem(id) {
    if (window.eppItemManager) {
        const btnMenu = document.querySelector(`[data-item-id="${id}"].btn-menu-epp`);
        const submenu = document.querySelector(`[data-item-id="${id}"].submenu-epp`);
        const btnEditar = document.querySelector(`[data-item-id="${id}"].btn-editar-epp`);
        const btnEliminar = document.querySelector(`[data-item-id="${id}"].btn-eliminar-epp`);
        
        if (btnMenu && submenu) {
            btnMenu.addEventListener('click', (e) => {
                e.stopPropagation();
                submenu.style.display = submenu.style.display === 'none' ? 'block' : 'none';
            });
        }
        
        if (btnEditar) {
            btnEditar.addEventListener('click', () => {
                const datosItem = window.eppStateManager?.obtenerDatosItem(id);
                if (datosItem) {
                    if (datosItem.epp_id) {
                        editarEPPDesdeDB(datosItem.epp_id);
                    } else {
                        editarItemEPP(datosItem.id, datosItem.nombre, datosItem.codigo, datosItem.categoria, datosItem.cantidad, datosItem.observaciones, datosItem.imagenes);
                    }
                }
                if (submenu) submenu.style.display = 'none';
            });
        }
        
        if (btnEliminar) {
            btnEliminar.addEventListener('click', () => {
                eliminarItemEPP(id);
                if (submenu) submenu.style.display = 'none';
            });
        }
        
        document.addEventListener('click', (e) => {
            if (!e.target.closest(`[data-item-id="${id}"]`)) {
                if (submenu) submenu.style.display = 'none';
            }
        });
    }
}

// ============================================================================
// INICIALIZACIÓN
// ============================================================================

window.addEventListener('load', crearModalAgregarEPP);

// Nota: El HTML del modal se genera desde EppModalTemplate.getHTML()
// Los estilos inline están en el template para mantener todo centralizado
// La lógica está distribuida en los servicios de /epp/services/

// Los estilos se cargan desde epp-modal.css
// Las animaciones están definidas en el archivo CSS separado

function abrirModalAgregarEPP() {
    inicializarEppService();
    const modal = document.getElementById('modal-agregar-epp');
    if (!modal) crearModalAgregarEPP();
    if (window.eppService) window.eppService.abrirModalAgregar();
}

function cerrarModalAgregarEPP() {
    if (window.eppService) window.eppService.cerrarModal();
}

function mostrarFormularioCrearEPPNuevo() {
    document.getElementById('formularioEPPNuevo').style.display = 'block';
    document.getElementById('inputBuscadorEPP').value = '';
    document.getElementById('resultadosBuscadorEPP').style.display = 'none';
}

function ocultarFormularioCrearEPP() {
    document.getElementById('formularioEPPNuevo').style.display = 'none';
    ['nuevoEPPNombre'].forEach(id => {
        const elem = document.getElementById(id);
        if (elem) elem.value = '';
    });
}

async function crearEPPNuevoYAgregar() {
    const nombre = document.getElementById('nuevoEPPNombre').value.trim();

    console.log('[DEBUG] Iniciando creación de EPP:', { nombre });

    // Validar campos obligatorios
    if (!nombre) {
        console.warn('[DEBUG] Nombre vacío, validación fallida');
        alert('Por favor ingresa el nombre del EPP');
        document.getElementById('nuevoEPPNombre').focus();
        return;
    }

    // Mostrar modal de cargando inmediatamente
    console.log('[DEBUG] Mostrando modal de cargando');
    mostrarModalCargando('Creando EPP', 'Por favor espera...');

    try {
        // Crear EPP via API
        console.log('[DEBUG] Enviando POST a /api/epp con datos:', { nombre });
        const response = await fetch('/api/epp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                nombre: nombre
            })
        });

        console.log('[DEBUG] Respuesta recibida:', { status: response.status, ok: response.ok });

        if (!response.ok) {
            let errorData = null;
            const contentType = response.headers.get('content-type');
            
            console.log('[DEBUG] Tipo de respuesta:', contentType);
            
            try {
                if (contentType && contentType.includes('application/json')) {
                    errorData = await response.json();
                    console.error('[DEBUG] Error JSON:', errorData);
                } else {
                    const htmlText = await response.text();
                    console.error('[DEBUG] Respuesta HTML (primeros 500 chars):', htmlText.substring(0, 500));
                    errorData = { message: 'Error del servidor (ver consola para detalles)' };
                }
            } catch (parseError) {
                console.error('[DEBUG] Error al parsear respuesta:', parseError);
                errorData = { message: 'Error al parsear respuesta del servidor' };
            }
            
            throw new Error(errorData.message || 'Error al crear EPP');
        }

        const eppNuevo = await response.json();
        console.log('[DEBUG] EPP creado exitosamente:', eppNuevo);
        
        // Actualizar modal a éxito
        actualizarModalAExito('EPP creado exitosamente', 'Ahora puedes agregar talla, cantidad e imágenes.');
        
        // Establecer como producto seleccionado en el state manager
        if (window.eppStateManager) {
            window.eppStateManager.setProductoSeleccionado({
                id: eppNuevo.data?.id || eppNuevo.id,
                nombre: nombre,
                imagenes: []
            });
            console.log('[DEBUG] Producto establecido en stateManager:', window.eppStateManager.getProductoSeleccionado());
        } else {
            console.error('[DEBUG] ⚠️ window.eppStateManager NO EXISTE');
        }

        // Actualizar tarjeta
        document.getElementById('nombreProductoEPP').textContent = nombre;
        document.getElementById('productoCardEPP').style.display = 'flex';

        // Limpiar y ocultar formulario
        ocultarFormularioCrearEPP();

        // Habilitar campos de cantidad y observaciones
        document.getElementById('cantidadEPP').disabled = false;
        document.getElementById('observacionesEPP').disabled = false;
        document.getElementById('cantidadEPP').style.background = 'white';
        document.getElementById('cantidadEPP').style.color = '#1f2937';
        document.getElementById('cantidadEPP').style.cursor = 'text';
        document.getElementById('observacionesEPP').style.background = 'white';
        document.getElementById('observacionesEPP').style.color = '#1f2937';
        document.getElementById('observacionesEPP').style.cursor = 'text';

        // Mostrar área de carga de imágenes
        document.getElementById('areaCargarImagenes').style.display = 'block';
        document.getElementById('mensajeSelecccionarEPP').style.display = 'none';
        document.getElementById('listaImagenesSubidas').style.display = 'none';
        document.getElementById('contenedorImagenesSubidas').innerHTML = '';

        actualizarEstilosBotonEPP();
        
    } catch (error) {
        console.error('[DEBUG] Error capturado:', error);
        console.error('[DEBUG] Stack trace:', error.stack);
        
        // Actualizar modal a error
        actualizarModalAError('Error al crear EPP', error.message);
    }
}

/**
 * Mostrar modal de éxito
 */
function mostrarModalExito(titulo, mensaje) {
    const backdrop = document.createElement('div');
    backdrop.id = 'modal-exito-backdrop';
    backdrop.className = 'epp-modal-backdrop';

    const modal = document.createElement('div');
    modal.className = 'epp-modal-content';

    modal.innerHTML = `
        <div class="epp-modal-content-wrapper">
            <div class="epp-modal-icon-circle success">
                <span class="material-symbols-rounded">check</span>
            </div>
        </div>
        <h3 class="epp-modal-title">${titulo}</h3>
        <p class="epp-modal-message">${mensaje}</p>
        <button onclick="cerrarModalExito()" class="epp-modal-button info">
            Entendido
        </button>
    `;

    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
}

/**
 * Mostrar modal de error
 */
function mostrarModalError(titulo, mensaje) {
    const backdrop = document.createElement('div');
    backdrop.id = 'modal-error-backdrop';
    backdrop.className = 'epp-modal-backdrop';

    const modal = document.createElement('div');
    modal.className = 'epp-modal-content';

    modal.innerHTML = `
        <div class="epp-modal-content-wrapper">
            <div class="epp-modal-icon-circle error">
                <span class="material-symbols-rounded">close</span>
            </div>
        </div>
        <h3 class="epp-modal-title">${titulo}</h3>
        <p class="epp-modal-message">${mensaje}</p>
        <button onclick="cerrarModalError()" class="epp-modal-button error">
            Cerrar
        </button>
    `;

    backdrop.appendChild(modal);
    document.body.appendChild(backdrop);
}

/**
 * Mostrar modal de cargando
 */
/**
 * Función de diagnóstico de z-index
 */
function diagnosticarZIndex() {
    console.log('[DIAGNÓSTICO] ===== Z-INDEX ANALYSIS =====');
    
    // Obtener todas las posibles capas
    const backdrop = document.getElementById('modal-cargando-backdrop');
    const modal = document.getElementById('modal-cargando');
    const mainForm = document.getElementById('formCrearPedidoEditable');
    const body = document.body;
    const html = document.documentElement;
    
    // VERIFICAR STACKING CONTEXT
    console.log('[DIAGNÓSTICO] Verificando stacking context del formulario:');
    if (mainForm) {
        const formStyles = window.getComputedStyle(mainForm);
        console.log('[DIAGNÓSTICO] Form styles:', {
            zIndex: formStyles.zIndex,
            position: formStyles.position,
            transform: formStyles.transform,
            filter: formStyles.filter,
            opacity: formStyles.opacity,
            isolation: formStyles.isolation,
        });
    }
    
    // Verificar si hay elementos con stacking context entre el form y el body
    let parent = mainForm;
    let depth = 0;
    console.log('[DIAGNÓSTICO] Árbol de stacking contexts:');
    while (parent && depth < 10) {
        const styles = window.getComputedStyle(parent);
        if (styles.zIndex !== 'auto' || styles.position !== 'static' || styles.transform !== 'none') {
            console.log(`  [Nivel ${depth}] ${parent.tagName}#${parent.id}.${parent.className}:`, {
                zIndex: styles.zIndex,
                position: styles.position,
                transform: styles.transform,
            });
        }
        parent = parent.parentElement;
        depth++;
    }
    
    // Crear tabla de diagnóstico
    const diagnostico = {
        'Backdrop': backdrop ? {
            exists: true,
            zIndex: window.getComputedStyle(backdrop).zIndex,
            position: window.getComputedStyle(backdrop).position,
            display: window.getComputedStyle(backdrop).display,
            visibility: window.getComputedStyle(backdrop).visibility,
            opacity: window.getComputedStyle(backdrop).opacity,
            className: backdrop.className,
        } : { exists: false },
        'Modal': modal ? {
            exists: true,
            zIndex: window.getComputedStyle(modal).zIndex,
            position: window.getComputedStyle(modal).position,
            display: window.getComputedStyle(modal).display,
            visibility: window.getComputedStyle(modal).visibility,
            opacity: window.getComputedStyle(modal).opacity,
            className: modal.className,
        } : { exists: false },
        'Form': mainForm ? {
            exists: true,
            zIndex: window.getComputedStyle(mainForm).zIndex,
            position: window.getComputedStyle(mainForm).position,
        } : { exists: false },
        'Body': {
            zIndex: window.getComputedStyle(body).zIndex,
            position: window.getComputedStyle(body).position,
        },
    };
    
    console.table(diagnostico);
    console.log('[DIAGNÓSTICO] Estructura DOM del backdrop:');
    console.log(backdrop);
    console.log('[DIAGNÓSTICO] Estructura DOM del modal:');
    console.log(modal);
}

function mostrarModalCargando(titulo, mensaje) {
    console.log('[DEBUG] === CREANDO MODAL CARGANDO ===');
    
    const backdrop = document.createElement('div');
    backdrop.id = 'modal-cargando-backdrop';
    backdrop.className = 'epp-modal-backdrop';

    const modal = document.createElement('div');
    modal.id = 'modal-cargando';
    modal.className = 'epp-modal-content';

    modal.innerHTML = `
        <div class="epp-modal-content-wrapper">
            <div class="epp-modal-icon-circle loading">
                <div class="epp-modal-spinner"></div>
            </div>
        </div>
        <h3 class="epp-modal-title" id="modal-cargando-titulo">${titulo}</h3>
        <p class="epp-modal-message small" id="modal-cargando-mensaje">${mensaje}</p>
    `;

    backdrop.appendChild(modal);
    
    console.log('[DEBUG] Backdrop creado:', {
        id: backdrop.id,
        className: backdrop.className,
        zIndex: window.getComputedStyle(backdrop).zIndex,
        position: window.getComputedStyle(backdrop).position,
    });
    
    console.log('[DEBUG] Modal creado:', {
        id: modal.id,
        className: modal.className,
        zIndex: window.getComputedStyle(modal).zIndex,
        position: window.getComputedStyle(modal).position,
    });
    
    document.documentElement.appendChild(backdrop);
    
    // Forzar estilos en línea para asegurar que aparezca encima
    backdrop.style.cssText = `
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background: rgba(0, 0, 0, 0.5) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 999999999 !important;
        visibility: visible !important;
        opacity: 1 !important;
        pointer-events: all !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
    `;
    
    modal.style.cssText = `
        z-index: 9999999999 !important;
        position: relative !important;
        visibility: visible !important;
        opacity: 1 !important;
        display: block !important;
    `;
    
    console.log('[DEBUG] Backdrop agregado al HTML (no al body)');
    console.log('[DEBUG] Verificando z-index después de agregar:', {
        backdropZIndex: window.getComputedStyle(backdrop).zIndex,
        modalZIndex: window.getComputedStyle(modal).zIndex,
        bodyZIndex: window.getComputedStyle(document.body).zIndex,
        htmlZIndex: window.getComputedStyle(document.documentElement).zIndex,
    });
    
    // Ejecutar diagnóstico completo
    setTimeout(() => {
        diagnosticarZIndex();
    }, 100);
}

/**
 * Actualizar modal de cargando a éxito
 */
function actualizarModalAExito(titulo, mensaje) {
    console.log('[DEBUG] === ACTUALIZANDO MODAL A ÉXITO ===');
    
    const backdrop = document.getElementById('modal-cargando-backdrop');
    const modal = document.getElementById('modal-cargando');
    
    console.log('[DEBUG] Elementos encontrados:', {
        backdropFound: !!backdrop,
        modalFound: !!modal,
        backdropZIndex: backdrop ? window.getComputedStyle(backdrop).zIndex : 'N/A',
        modalZIndex: modal ? window.getComputedStyle(modal).zIndex : 'N/A',
    });
    
    if (modal && backdrop) {
        // Actualizar contenido
        const contenedor = modal.querySelector('div:first-child');
        contenedor.innerHTML = `
            <div class="epp-modal-icon-circle success epp-modal-icon-wrapper animated">
                <span class="material-symbols-rounded">check</span>
            </div>
        `;
        
        document.getElementById('modal-cargando-titulo').textContent = titulo;
        document.getElementById('modal-cargando-mensaje').textContent = mensaje;
        
        // Agregar botón
        const btn = document.createElement('button');
        btn.textContent = 'Entendido';
        btn.onclick = cerrarModalCargando;
        btn.className = 'epp-modal-button success';
        
        modal.appendChild(btn);
        
        console.log('[DEBUG] Modal actualizado a éxito');
    } else {
        console.warn('[DEBUG] ⚠️ No se encontraron los elementos del modal');
    }
}

/**
 * Actualizar modal de cargando a error
 */
function actualizarModalAError(titulo, mensaje) {
    const backdrop = document.getElementById('modal-cargando-backdrop');
    const modal = document.getElementById('modal-cargando');
    
    if (modal && backdrop) {
        // Actualizar contenido
        const contenedor = modal.querySelector('div:first-child');
        contenedor.innerHTML = `
            <div class="epp-modal-icon-circle error epp-modal-icon-wrapper animated">
                <span class="material-symbols-rounded">close</span>
            </div>
        `;
        
        document.getElementById('modal-cargando-titulo').textContent = titulo;
        document.getElementById('modal-cargando-mensaje').textContent = mensaje;
        
        // Agregar botón
        const btn = document.createElement('button');
        btn.textContent = 'Cerrar';
        btn.onclick = cerrarModalCargando;
        btn.className = 'epp-modal-button error';
        
        modal.appendChild(btn);
    }
}

/**
 * Cerrar modal de cargando
 */
function cerrarModalCargando() {
    const backdrop = document.getElementById('modal-cargando-backdrop');
    if (backdrop) {
        backdrop.style.animation = 'slideDown 0.3s ease';
        setTimeout(() => {
            backdrop.remove();
            
            // Habilitar área de cargar imágenes ahora que hay un EPP seleccionado
            const areaCargar = document.getElementById('areaCargarImagenes');
            const msgSeleccionar = document.getElementById('mensajeSelecccionarEPP');
            if (areaCargar && window.eppStateManager?.getProductoSeleccionado()) {
                areaCargar.style.opacity = '1';
                areaCargar.style.cursor = 'pointer';
                areaCargar.querySelector('p:first-of-type').textContent = 'Haz clic o arrastra imágenes';
                if (msgSeleccionar) msgSeleccionar.style.display = 'none';
                console.log('[DEBUG] Área de cargar imágenes habilitada');
            }
        }, 300);
    }
}

/**
 * Cerrar modal de éxito
 */
function cerrarModalExito() {
    const backdrop = document.getElementById('modal-exito-backdrop');
    if (backdrop) {
        backdrop.style.animation = 'slideDown 0.3s ease';
        setTimeout(() => {
            backdrop.remove();
        }, 300);
    }
}

/**
 * Cerrar modal de error
 */
function cerrarModalError() {
    const backdrop = document.getElementById('modal-error-backdrop');
    if (backdrop) {
        backdrop.style.animation = 'slideDown 0.3s ease';
        setTimeout(() => {
            backdrop.remove();
        }, 300);
    }
}

function limpiarModalEPP() {
    if (window.eppService) {
        window.eppService.limpiarModal();
    }
}

/**
 * Cargar categorías desde la API y llenar el select
 */
function cargarCategoriasEnFormulario() {
    if (window.eppService) {
        window.eppService.cargarCategorias();
    }
}

/**
 * Cargar EPP disponibles en el buscador
 */
function cargarEPPBuscador() {
    if (window.eppService) {
        window.eppService.cargarEPP();
    }
}

/**
 * Filtrar EPP por término de búsqueda
 */
function filtrarEPPBuscador(valor) {
    if (window.eppService) {
        window.eppService.filtrarEPP(valor);
    }
}

/**
 * Actualizar estilos del botón de agregar
 */
function actualizarEstilosBotonEPP() {
    if (window.eppService) {
        window.eppService.actualizarBoton();
    }
}

/**
 * Agregar EPP al pedido
 */
function agregarEPPAlPedido() {
    if (window.eppService) {
        window.eppService.agregarEPPAlPedido();
    }
}

/**
 * Eliminar item EPP
 */
function eliminarItemEPP(id) {
    if (window.eppService) {
        window.eppService.eliminarEPP(id);
    }
}

async function editarEPPDesdeDB(eppId) {
    if (window.eppService) {
        return window.eppService.editarEPPDesdeDB(eppId);
    }
}

function mostrarErrorEPP(mensaje) {
    const container = document.getElementById('resultadosBuscadorEPP');
    container.innerHTML = `<div class="epp-search-error">${mensaje}</div>`;
    container.style.display = 'block';
}


async function manejarSeleccionImagenes(event) {
    if (window.eppImagenManager) {
        return window.eppImagenManager.manejarSeleccionImagenes(event);
    }
}

async function eliminarImagenCargada(imagenId) {
    if (window.eppImagenManager) {
        return window.eppImagenManager.eliminarImagen(imagenId);
    }
}

window.addEventListener('load', crearModalAgregarEPP);
