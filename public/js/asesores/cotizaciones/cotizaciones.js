/**
 * SISTEMA DE COTIZACIONES - ORQUESTACIÓN E INICIALIZACIÓN
 * Responsabilidad: Inicializar el sistema, gestionar el ciclo de vida
 */

// Variables globales
window.imagenesEnMemoria = { prenda: [], tela: [], general: [] };
window.especificacionesSeleccionadas = [];

console.log('🔵 Sistema de cotizaciones inicializado');

// ============ INICIALIZACIÓN ============

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM cargado - Inicializando cotizaciones');
    
    // Ocultar navbar
    const topNav = document.querySelector('.top-nav');
    if (topNav) topNav.style.display = 'none';
    
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) pageHeader.style.display = 'none';
    
    // Inicializar funciones
    cargarDatosDelBorrador();
    mostrarFechaActual();
    configurarDragAndDrop();
});

window.addEventListener('beforeunload', function() {
    const topNav = document.querySelector('.top-nav');
    if (topNav) topNav.style.display = '';
    
    const pageHeader = document.querySelector('.page-header');
    if (pageHeader) pageHeader.style.display = '';
});

// ============ NAVEGACIÓN ============

function irAlPaso(paso) {
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    const formStep = document.querySelector(`.form-step[data-step="${paso}"]`);
    if (formStep) formStep.classList.add('active');
    
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    const stepElement = document.querySelector(`.step[data-step="${paso}"]`);
    if (stepElement) stepElement.classList.add('active');
    
    if (paso === 4) setTimeout(() => actualizarResumenFriendly(), 100);
}

// ============ UTILIDADES ============

function mostrarFechaActual() {
    const el = document.getElementById('fechaActual');
    if (el) {
        const hoy = new Date();
        el.textContent = hoy.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
    }
}

function actualizarResumenFriendly() {
    const cliente = document.getElementById('cliente');
    if (document.getElementById('resumenCliente')) {
        document.getElementById('resumenCliente').textContent = cliente ? cliente.value || '-' : '-';
    }
    if (document.getElementById('resumenProductos')) {
        document.getElementById('resumenProductos').textContent = document.querySelectorAll('.producto-card').length;
    }
    if (document.getElementById('resumenFecha')) {
        const hoy = new Date();
        document.getElementById('resumenFecha').textContent = hoy.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
    }
}

function cargarDatosDelBorrador() {
    // Implementar si es necesario cargar datos de un borrador existente
}

function recopilarDatos() {
    const cliente = document.getElementById('cliente');
    if (!cliente) {
        console.error('❌ Campo cliente no encontrado');
        return null;
    }
    
    const clienteValue = cliente.value;
    const productos = [];
    
    document.querySelectorAll('.producto-card').forEach((item, index) => {
        const nombre = item.querySelector('input[name*="nombre_producto"]')?.value || '';
        const descripcion = item.querySelector('textarea[name*="descripcion"]')?.value || '';
        const cantidad = item.querySelector('input[name*="cantidad"]')?.value || 1;
        
        // Obtener tallas seleccionadas (desde botones activos)
        const tallasSeleccionadas = [];
        
        // Buscar tallas en el campo hidden que se actualiza con agregarTallasSeleccionadas()
        const tallasHidden = item.querySelector('input[name*="tallas"][type="hidden"]');
        if (tallasHidden && tallasHidden.value) {
            // Las tallas están separadas por comas en el campo hidden
            tallasSeleccionadas.push(...tallasHidden.value.split(', ').filter(t => t.trim()));
        }
        
        // Alternativa: buscar botones activos directamente
        if (tallasSeleccionadas.length === 0) {
            item.querySelectorAll('.talla-btn.activo').forEach(btn => {
                tallasSeleccionadas.push(btn.dataset.talla);
            });
        }
        
        // Obtener fotos de esta prenda (desde fotosSeleccionadas)
        const productoId = item.dataset.productoId;
        const fotos = fotosSeleccionadas[productoId] ? fotosSeleccionadas[productoId].map(f => f.name) : [];
        
        // Obtener imagen de tela de esta prenda (desde telaConIndice)
        let imagenTela = null;
        if (window.imagenesEnMemoria && window.imagenesEnMemoria.telaConIndice) {
            const telaEncontrada = window.imagenesEnMemoria.telaConIndice.find(t => t.prendaIndex === index);
            if (telaEncontrada) {
                imagenTela = telaEncontrada.file.name;
            }
        }
        
        console.log('📋 Recopilando prenda:', {
            nombre: nombre,
            tallas: tallasSeleccionadas,
            fotos: fotos,
            imagenTela: imagenTela,
            productoId: productoId
        });
        
        if (nombre.trim()) {
            productos.push({
                nombre_producto: nombre,
                descripcion: descripcion,
                cantidad: parseInt(cantidad) || 1,
                tallas: tallasSeleccionadas,
                fotos: fotos,
                imagen_tela: imagenTela
            });
        }
    });
    
    console.log('📦 Productos recopilados:', productos);
    
    // ========== PASO 3: BORDADO/ESTAMPADO ==========
    
    // Recopilar técnicas
    const tecnicas = [];
    document.querySelectorAll('#tecnicas_seleccionadas div').forEach(tag => {
        const input = tag.querySelector('input[name="tecnicas[]"]');
        if (input) tecnicas.push(input.value);
    });
    console.log('🎨 Técnicas recopiladas:', tecnicas);
    
    // Recopilar observaciones técnicas
    const observaciones_tecnicas = document.getElementById('observaciones_tecnicas')?.value || '';
    console.log('📝 Observaciones técnicas:', observaciones_tecnicas);
    
    // Recopilar ubicaciones
    const ubicaciones = [];
    document.querySelectorAll('input[name="ubicaciones[]"]').forEach(input => {
        const valor = input.value.trim();
        if (valor) ubicaciones.push(valor);
    });
    console.log('📍 Ubicaciones recopiladas:', ubicaciones);
    
    // Recopilar observaciones generales
    const observaciones_generales = [];
    document.querySelectorAll('#observaciones_lista > div').forEach(obs => {
        const valor = obs.querySelector('input[name="observaciones_generales[]"]')?.value || '';
        if (valor.trim()) observaciones_generales.push(valor);
    });
    console.log('💬 Observaciones generales recopiladas:', observaciones_generales);
    
    return { 
        cliente: clienteValue, 
        productos, 
        tecnicas, 
        observaciones_tecnicas,
        ubicaciones,
        observaciones_generales 
    };
}
