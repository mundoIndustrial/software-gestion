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
        console.error('Error creando EPP:', error);
    }
}

function agregarEPPAlPedido() {
    window.EppModalInterface?.agregarEPP();
}

function editarItemEPP(id, nombre, codigo, categoria, talla, cantidad, observaciones, imagenes) {
    window.EppModalInterface?.editarEPP(id, nombre, codigo, categoria, null, cantidad, observaciones, imagenes);
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

function crearItemEPP(id, nombre, codigo, categoria, talla, cantidad, observaciones, imagenes = []) {
    if (window.eppItemManager) {
        window.eppItemManager.crearItem(id, nombre, codigo, categoria, talla, cantidad, observaciones, imagenes);
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
                        editarItemEPP(datosItem.id, datosItem.nombre, datosItem.codigo, datosItem.categoria, datosItem.talla, datosItem.cantidad, datosItem.observaciones, datosItem.imagenes);
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
    ['nuevoEPPNombre', 'nuevoEPPCategoria', 'nuevoEPPCodigo', 'nuevoEPPDescripcion'].forEach(id => {
        const elem = document.getElementById(id);
        if (elem) elem.value = '';
    });
}

async function crearEPPNuevoYAgregar() {
    const nombre = document.getElementById('nuevoEPPNombre').value.trim();
    const categoria = document.getElementById('nuevoEPPCategoria').value.trim();
    const codigo = document.getElementById('nuevoEPPCodigo').value.trim();
    const descripcion = document.getElementById('nuevoEPPDescripcion').value.trim();

    // Validar campos obligatorios
    if (!nombre) {
        alert('Por favor ingresa el nombre del EPP');
        document.getElementById('nuevoEPPNombre').focus();
        return;
    }
    if (!categoria) {
        alert('Por favor selecciona la categoría');
        document.getElementById('nuevoEPPCategoria').focus();
        return;
    }
    if (!codigo) {
        alert('Por favor ingresa el código del EPP');
        document.getElementById('nuevoEPPCodigo').focus();
        return;
    }

    // Mostrar modal de cargando inmediatamente
    mostrarModalCargando('Creando EPP', 'Por favor espera...');

    try {
        // Crear EPP via API
        const response = await fetch('/api/epp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({
                nombre: nombre,
                categoria: categoria,
                codigo: codigo,
                descripcion: descripcion
            })
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error al crear EPP');
        }

        const eppNuevo = await response.json();
        
        // Actualizar modal a éxito
        actualizarModalAExito('EPP creado exitosamente', 'Ahora puedes agregar talla, cantidad e imágenes.');
        
        // Establecer como producto seleccionado
        productoSeleccionadoEPP = {
            id: eppNuevo.id || eppNuevo.data?.id,
            nombre: nombre,
            codigo: codigo,
            categoria: categoria
        };

        // Actualizar tarjeta
        document.getElementById('nombreProductoEPP').textContent = nombre;
        document.getElementById('categoriaProductoEPP').textContent = categoria;
        document.getElementById('codigoProductoEPP').textContent = codigo;
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
        console.error('Error creando EPP:', error);
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
function mostrarModalCargando(titulo, mensaje) {
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
    document.body.appendChild(backdrop);
}

/**
 * Actualizar modal de cargando a éxito
 */
function actualizarModalAExito(titulo, mensaje) {
    const backdrop = document.getElementById('modal-cargando-backdrop');
    const modal = document.getElementById('modal-cargando');
    
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

/**
 * Editar item EPP
 */
function editarItemEPP(id, nombre, codigo, categoria, talla, cantidad, observaciones, imagenes) {
    if (window.eppService) {
        window.eppService.editarEPPFormulario(id, nombre, codigo, categoria, talla, cantidad, observaciones, imagenes);
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
