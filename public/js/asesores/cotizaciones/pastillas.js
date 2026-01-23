/**
 * SISTEMA DE PASTILLAS DE COTIZACIONES
 * Responsabilidad: Gestionar la interacción y lógica de las pastillas de tipos de cotización
 */



// Variables globales
let tipoCotzacionSeleccionada = null;

/**
 * Inicializar el sistema de pastillas
 */
function inicializarPastillas() {
    // Obtener todos los botones de pastilla
    const tabBtns = document.querySelectorAll('.cotizacion-tab-btn');
    
    if (tabBtns.length === 0) {
        // Silenciosamente retornar si no hay pastillas (es normal en algunas vistas)
        return;
    }
    

    
    // Agregar event listeners
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            seleccionarPastilla(this);
        });
        
        // Agregar animación en hover
        btn.addEventListener('mouseenter', function() {
            this.classList.add('hover-active');
        });
        
        btn.addEventListener('mouseleave', function() {
            this.classList.remove('hover-active');
        });
    });
    

}

/**
 * Seleccionar una pastilla
 */
function seleccionarPastilla(element) {

    
    // Remover clase active de todas
    document.querySelectorAll('.cotizacion-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Agregar clase active al elemento actual
    element.classList.add('active');
    
    // Obtener el tipo
    const tipo = element.dataset.tipo;
    tipoCotzacionSeleccionada = tipo;
    
    // Ocultar todos los contenidos
    document.querySelectorAll('.cotizacion-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Mostrar el contenido correspondiente
    const content = document.getElementById(`content-${tipo}`);
    if (content) {
        content.classList.add('active');

    }
    
    // Guardar en localStorage
    localStorage.setItem('cotizacion_tipo_seleccionado', tipo);
    
    // Ejecutar callback si existe
    if (typeof onPastillaSeleccionada === 'function') {
        onPastillaSeleccionada(tipo);
    }
}

/**
 * Obtener la pastilla seleccionada
 */
function obtenerPastillaSeleccionada() {
    return tipoCotzacionSeleccionada || localStorage.getItem('cotizacion_tipo_seleccionado');
}

/**
 * Establecer pastilla activa por tipo
 */
function establecerPastillaActiva(tipo) {

    
    const btn = document.querySelector(`.cotizacion-tab-btn[data-tipo="${tipo}"]`);
    if (btn) {
        seleccionarPastilla(btn);
    } else {

    }
}

/**
 * Animar el cambio de pastilla
 */
function animarPastilla(elemento) {
    elemento.style.animation = 'none';
    setTimeout(() => {
        elemento.style.animation = 'bounce 0.6s ease';
    }, 10);
}

/**
 * Obtener información de una pastilla
 */
function obtenerInfoPastilla(tipo) {
    const info = {
        'prenda': {
            nombre: 'Prenda',
            icon: '',
            descripcion: 'Solo prendas sin logo',
            color: '#3b82f6'
        },
        'logo': {
            nombre: 'Logo',
            icon: '',
            descripcion: 'Solo logos y diseños',
            color: '#8b5cf6'
        },
        'prenda-bordado': {
            nombre: 'Prenda/Bordado',
            icon: '✨',
            descripcion: 'Prendas con logo o bordado',
            color: '#ec4899'
        }
    };
    
    return info[tipo] || null;
}

/**
 * Validar que existe una pastilla seleccionada
 */
function validarPastillaSeleccionada() {
    const pastilla = obtenerPastillaSeleccionada();
    
    if (!pastilla) {

        Swal.fire({
            title: 'Selecciona un tipo',
            text: 'Debes seleccionar el tipo de cotización antes de continuar',
            icon: 'warning',
            confirmButtonColor: '#3b82f6'
        });
        return false;
    }
    

    return true;
}

/**
 * Habilitar/Deshabilitar una pastilla
 */
function establecerPastillaHabilitada(tipo, habilitada = true) {
    const btn = document.querySelector(`.cotizacion-tab-btn[data-tipo="${tipo}"]`);
    
    if (btn) {
        btn.disabled = !habilitada;
        btn.style.opacity = habilitada ? '1' : '0.5';
        btn.style.cursor = habilitada ? 'pointer' : 'not-allowed';

    }
}

/**
 * Mostrar notificación en una pastilla
 */
function mostrarNotificacionPastilla(tipo) {
    const indicator = document.querySelector(`.cotizacion-tab-btn[data-tipo="${tipo}"] .cotizacion-tab-indicator`);
    
    if (indicator) {
        indicator.style.background = '#f59e0b';
        indicator.style.animation = 'pulse-glow 1.5s ease-in-out infinite';

    }
}

/**
 * Limpiar notificación en una pastilla
 */
function limpiarNotificacionPastilla(tipo) {
    const indicator = document.querySelector(`.cotizacion-tab-btn[data-tipo="${tipo}"] .cotizacion-tab-indicator`);
    
    if (indicator) {
        indicator.style.animation = 'none';

    }
}

/**
 * Inicializar cuando el DOM está listo
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarPastillas);
} else {
    inicializarPastillas();
}
