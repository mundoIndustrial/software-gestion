/**
 * gestor-procesos-generico.js
 * 
 * Gestor genérico para modales de procesos (Reflectivo, Estampado, Bordado, DTF, Sublimado)
 * Permite reutilizar el mismo modal para diferentes tipos de procesos
 */

// Variables globales para controlar el tipo de proceso activo
let procesoActual = null;
const procesosConfig = {
    reflectivo: {
        titulo: 'Agregar Reflectivo',
        icon: 'light_mode',
        btnTexto: 'Agregar Reflectivo',
        mostrarUbicacion: false,
        mostrarEspecificaciones: false,
        mostrarProcesosAdicionales: true,
        placeholderNombre: 'Ej: Cinta Reflectiva, Banda Reflectiva...',
        placeholderDesc: 'Detalles especiales del reflectivo...'
    },
    estampado: {
        titulo: 'Agregar Estampado',
        icon: 'format_paint',
        btnTexto: 'Agregar Estampado',
        mostrarUbicacion: true,
        mostrarEspecificaciones: true,
        mostrarProcesosAdicionales: false,
        placeholderNombre: 'Ej: ESTAMPADO FRENTE, LOGO...',
        placeholderDesc: 'Detalles del diseño a estampar...'
    },
    bordado: {
        titulo: 'Agregar Bordado',
        icon: 'auto_awesome',
        btnTexto: 'Agregar Bordado',
        mostrarUbicacion: true,
        mostrarEspecificaciones: true,
        mostrarProcesosAdicionales: false,
        placeholderNombre: 'Ej: BORDADO ESPALDA...',
        placeholderDesc: 'Detalles del bordado (puntos, colores, etc)...'
    },
    dtf: {
        titulo: 'Agregar DTF',
        icon: 'straighten',
        btnTexto: 'Agregar DTF',
        mostrarUbicacion: true,
        mostrarEspecificaciones: true,
        mostrarProcesosAdicionales: false,
        placeholderNombre: 'Ej: DTF FRENTE...',
        placeholderDesc: 'Detalles de la impresión DTF...'
    },
    sublimado: {
        titulo: 'Agregar Sublimado',
        icon: 'water_drop',
        btnTexto: 'Agregar Sublimado',
        mostrarUbicacion: true,
        mostrarEspecificaciones: true,
        mostrarProcesosAdicionales: false,
        placeholderNombre: 'Ej: SUBLIMADO MANGA...',
        placeholderDesc: 'Detalles de la impresión sublimada...'
    }
};

/**
 * Abre el modal genérico con configuración específica del proceso
 */
window.abrirModalProcesoGenerico = function(tipoProceso) {
    procesoActual = tipoProceso;
    const config = procesosConfig[tipoProceso];
    
    if (!config) {
        console.error(' Tipo de proceso desconocido:', tipoProceso);
        return;
    }
    
    // Actualizar títulos y textos
    document.getElementById('modal-proceso-icon').textContent = config.icon;
    document.getElementById('modal-proceso-titulo').textContent = config.titulo;
    document.getElementById('modal-btn-texto').textContent = config.btnTexto;
    
    // Actualizar placeholders
    document.getElementById('proceso-prenda-nombre').placeholder = config.placeholderNombre;
    document.getElementById('proceso-prenda-descripcion').placeholder = config.placeholderDesc;
    
    // Mostrar/ocultar secciones
    document.getElementById('seccion-ubicacion').style.display = config.mostrarUbicacion ? 'block' : 'none';
    document.getElementById('seccion-especificaciones').style.display = config.mostrarEspecificaciones ? 'block' : 'none';
    document.getElementById('seccion-procesos-adicionales').style.display = config.mostrarProcesosAdicionales ? 'block' : 'none';
    
    // Limpiar formulario
    document.getElementById('form-proceso-generico').reset();
    document.getElementById('tarjetas-generos-proceso-container').innerHTML = '';
    document.getElementById('total-proceso').textContent = '0';
    document.getElementById('proceso-foto-contador').innerHTML = '';
    
    // Mostrar modal
    const modal = document.getElementById('modal-proceso-generico');
    modal.style.display = 'flex';
    
    console.log(` Modal de ${config.titulo} abierto`);
};

/**
 * Cierra el modal genérico
 */
window.cerrarModalProcesoGenerico = function() {
    const modal = document.getElementById('modal-proceso-generico');
    modal.style.display = 'none';
    procesoActual = null;
    console.log(' Modal de proceso cerrado');
};

/**
 * Maneja las imágenes del proceso
 */
window.manejarImagenesProceso = function(input) {
    if (!input.files || input.files.length === 0) return;
    
    const file = input.files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        const preview = document.getElementById('proceso-foto-preview');
        preview.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">`;
        
        const contador = document.getElementById('proceso-foto-contador');
        contador.innerHTML = `<span class="foto-count">1 foto</span>`;
        
        const btnAgregar = document.getElementById('proceso-foto-btn');
        btnAgregar.style.display = 'block';
        
        console.log(` Imagen agregada para ${procesoActual}`);
    };
    
    reader.readAsDataURL(file);
};

/**
 * Abre el modal para seleccionar tallas del proceso
 */
window.abrirModalSeleccionarTallasProceso = function(genero) {
    console.log(` Seleccionando tallas para ${procesoActual} - ${genero}`);
    
    // Guardar referencia para que el gestor de tallas sepa que es para un proceso
    window._tallas_modal_tipo = 'proceso';
    window._tallas_modal_proceso = procesoActual;
    
    // Reutilizar la función existente si está disponible
    // Si no, podemos crear una versión adaptada
    if (typeof abrirModalSeleccionarTallasReflectivo === 'function') {
        abrirModalSeleccionarTallasReflectivo(genero);
    } else if (typeof abrirModalSeleccionarTallas === 'function') {
        // Alternativa si existe una función más genérica
        abrirModalSeleccionarTallas(genero, 'proceso', procesoActual);
    } else {
        console.error(' No se encontró función para abrir modal de tallas');
    }
};

/**
 * Agrega el proceso al pedido
 */
window.agregarProceso = function() {
    if (!procesoActual) {
        console.error(' Ningún proceso seleccionado');
        return;
    }
    
    const nombre = document.getElementById('proceso-prenda-nombre').value.trim();
    const origen = document.getElementById('proceso-origen-select').value;
    const descripcion = document.getElementById('proceso-prenda-descripcion').value.trim();
    const total = document.getElementById('total-proceso').textContent;
    
    if (!nombre) {
        alert('Por favor ingresa el nombre de la prenda');
        return;
    }
    
    if (total === '0') {
        alert('Por favor selecciona al menos una talla y cantidad');
        return;
    }
    
    // Obtener ubicaciones si aplica
    let ubicaciones = [];
    if (procesosConfig[procesoActual].mostrarUbicacion) {
        ubicaciones = Array.from(document.querySelectorAll('input[name="proceso-ubicaciones"]:checked'))
            .map(cb => cb.value);
    }
    
    // Obtener especificaciones si aplica
    let especificaciones = '';
    if (procesosConfig[procesoActual].mostrarEspecificaciones) {
        especificaciones = document.getElementById('proceso-especificaciones').value.trim();
    }
    
    // Obtener procesos adicionales si aplica
    let procesosAdicionales = [];
    if (procesosConfig[procesoActual].mostrarProcesosAdicionales) {
        procesosAdicionales = Array.from(document.querySelectorAll('input[name="proceso-procesos-adicionales"]:checked'))
            .map(cb => cb.value);
    }
    
    const datos = {
        tipo: procesoActual,
        nombre: nombre,
        origen: origen,
        descripcion: descripcion,
        total: parseInt(total),
        ubicaciones: ubicaciones,
        especificaciones: especificaciones,
        procesosAdicionales: procesosAdicionales,
        tallas: obtenerTallasSeleccionadas(procesoActual)
    };
    
    console.log(` Agregando ${procesoActual}:`, datos);
    
    // Agregar a la lista de ítems del pedido
    window.agregarItemPedido(datos);
    
    // Cerrar modal
    cerrarModalProcesoGenerico();
};

/**
 * Obtiene las tallas seleccionadas para el proceso actual
 */
function obtenerTallasSeleccionadas(tipoProceso) {
    // Esta función debería integrarse con el sistema de tallas existente
    // Por ahora devolvemos un placeholder
    return {
        dama: [],
        caballero: []
    };
}

console.log(' Módulo gestor-procesos-generico.js cargado correctamente');
