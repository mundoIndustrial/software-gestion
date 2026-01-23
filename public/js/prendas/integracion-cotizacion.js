// ============================================
// INTEGRACIÓN DE PRENDAS CON COTIZACIÓN
// ============================================

let prendas = [];

/**
 * Inicializar al cargar la página
 */
document.addEventListener('DOMContentLoaded', function() {
    cargarPrendasDisponibles();
});

/**
 * Cargar prendas desde la API
 */
async function cargarPrendasDisponibles() {
    try {
        const response = await fetch('/api/prendas', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();
        prendas = data.data || [];

        const selector = document.getElementById('selector_prendas');
        if (!selector) return;

        selector.innerHTML = '<option value="">-- Seleccionar prenda --</option>';

        prendas.forEach(prenda => {
            const option = document.createElement('option');
            option.value = prenda.id;
            option.textContent = `${prenda.nombre_producto} (${prenda.tipo_prenda?.nombre || 'Sin tipo'})`;
            option.dataset.prenda = JSON.stringify(prenda);
            selector.appendChild(option);
        });


    } catch (error) {

    }
}

/**
 * Buscar prendas en tiempo real
 */
function buscarPrendas(termino) {
    if (!termino) {
        cargarPrendasDisponibles();
        return;
    }

    fetch(`/api/prendas/search?q=${termino}`, {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        prendas = data.data || [];

        const selector = document.getElementById('selector_prendas');
        selector.innerHTML = '<option value="">-- Seleccionar prenda --</option>';

        prendas.forEach(prenda => {
            const option = document.createElement('option');
            option.value = prenda.id;
            option.textContent = `${prenda.nombre_producto} (${prenda.tipo_prenda?.nombre || 'Sin tipo'})`;
            option.dataset.prenda = JSON.stringify(prenda);
            selector.appendChild(option);
        });


    })
    .catch(error => console.error(' Error buscando prendas:', error));
}

/**
 * Agregar prenda seleccionada
 */
function agregarPrendaSeleccionada() {
    const selector = document.getElementById('selector_prendas');
    const prendaId = selector.value;

    if (!prendaId) {
        alert('Por favor selecciona una prenda');
        return;
    }

    const prenda = prendas.find(p => p.id == prendaId);
    if (!prenda) return;

    // Llamar función existente para agregar producto
    agregarProductoFriendly();

    // Esperar a que se cree el elemento
    setTimeout(() => {
        const ultimoProducto = document.querySelectorAll('.producto-card')[
            document.querySelectorAll('.producto-card').length - 1
        ];

        if (ultimoProducto) {
            // Nombre
            const inputNombre = ultimoProducto.querySelector('input[name*="nombre_producto"]');
            if (inputNombre) inputNombre.value = prenda.nombre_producto;

            // Descripción
            const textareaDesc = ultimoProducto.querySelector('textarea[name*="descripcion"]');
            if (textareaDesc) textareaDesc.value = prenda.descripcion || '';

            // Tallas
            if (prenda.tallas && Array.isArray(prenda.tallas)) {
                prenda.tallas.forEach(talla => {
                    const tallaBtn = ultimoProducto.querySelector(`.talla-btn[data-talla="${talla.talla}"]`);
                    if (tallaBtn) tallaBtn.click();
                });
            }


        }
    }, 500);

    // Limpiar selector
    selector.value = '';
}

/**
 * Recopilar datos de productos
 */
function recopilarProductos() {
    const productos = [];
    document.querySelectorAll('.producto-card').forEach(card => {
        const nombre = card.querySelector('input[name*="nombre_producto"]')?.value;
        const descripcion = card.querySelector('textarea[name*="descripcion"]')?.value;
        const tallas = Array.from(card.querySelectorAll('.talla-btn.active')).map(btn => btn.dataset.talla);

        if (nombre) {
            productos.push({
                nombre_producto: nombre,
                descripcion,
                tallas
            });
        }
    });
    return productos;
}

/**
 * Recopilar técnicas
 */
function recopilarTecnicas() {
    const tecnicas = [];
    document.querySelectorAll('#tecnicas_seleccionadas .tecnica-tag').forEach(tag => {
        tecnicas.push(tag.textContent.replace('✕', '').trim());
    });
    return tecnicas;
}

/**
 * Recopilar ubicaciones
 */
function recopilarUbicaciones() {
    const ubicaciones = [];
    document.querySelectorAll('#secciones_agregadas .seccion-card').forEach(card => {
        const seccion = card.querySelector('input[name*="seccion"]')?.value;
        if (seccion) ubicaciones.push(seccion);
    });
    return ubicaciones;
}

/**
 * Recopilar observaciones
 */
function recopilarObservaciones() {
    const observaciones = [];
    document.querySelectorAll('#observaciones_lista .observacion-item').forEach(item => {
        const texto = item.querySelector('input[name*="observacion"]')?.value;
        if (texto) observaciones.push({ texto });
    });
    return observaciones;
}

/**
 * Actualizar resumen (Paso 5: REVISAR COTIZACIÓN)
 */
function actualizarResumen() {

    
    const cliente = document.getElementById('cliente')?.value || '-';
    const fecha = document.getElementById('fechaActual')?.value || '-';
    const tipo = document.getElementById('tipo_cotizacion')?.value || document.getElementById('tipo_venta')?.value || '-';

    const clienteEl = document.getElementById('resumen_cliente');
    const fechaEl = document.getElementById('resumen_fecha');
    const tipoEl = document.getElementById('resumen_tipo');
    
    if (clienteEl) clienteEl.textContent = cliente;
    if (fechaEl) fechaEl.textContent = fecha;
    if (tipoEl) tipoEl.textContent = tipo;



    // Resumen de prendas
    const resumenPrendas = document.getElementById('resumen_prendas');
    if (resumenPrendas) {
        resumenPrendas.innerHTML = '';
        const productos = recopilarProductos();
        
        if (productos.length === 0) {
            resumenPrendas.innerHTML = '<p style="color: #999;">No hay prendas agregadas</p>';
        } else {
            productos.forEach((prod, idx) => {
                const div = document.createElement('div');
                div.style.cssText = 'background: white; padding: 10px; border-radius: 4px; border-left: 4px solid #3498db; margin-bottom: 8px;';
                div.innerHTML = `
                    <strong>${idx + 1}. ${prod.nombre_producto}</strong><br>
                    <small>Tallas: ${prod.tallas.join(', ') || 'Sin tallas'}</small>
                `;
                resumenPrendas.appendChild(div);
            });

        }
    }

    // Resumen de técnicas
    const resumenTecnicas = document.getElementById('resumen_tecnicas');
    if (resumenTecnicas) {
        resumenTecnicas.innerHTML = '';
        const tecnicas = recopilarTecnicas();
        
        if (tecnicas.length === 0) {
            resumenTecnicas.innerHTML = '<p style="color: #999; font-size: 0.9rem;">No hay técnicas agregadas</p>';
        } else {
            tecnicas.forEach(tec => {
                const span = document.createElement('span');
                span.style.cssText = 'background: #3498db; color: white; padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; display: inline-block;';
                span.textContent = tec;
                resumenTecnicas.appendChild(span);
            });

        }
    }

    // Resumen de logo/descripción
    const logoDescEl = document.getElementById('resumen_logo_desc');
    if (logoDescEl) {
        const logoDesc = document.getElementById('descripcion_logo')?.value || '-';
        logoDescEl.textContent = logoDesc;

    }
}

/**
 * Guardar cotización como borrador
 */
async function guardarCotizacion() {
    try {
        const cliente = document.getElementById('cliente')?.value;
        const fecha = document.getElementById('fechaActual')?.value;
        const tipo = document.getElementById('tipo_cotizacion')?.value;

        if (!cliente || !fecha) {
            alert('Por favor completa los datos del cliente');
            return;
        }

        const productos = recopilarProductos();
        const tecnicas = recopilarTecnicas();
        const ubicaciones = recopilarUbicaciones();
        const observaciones = recopilarObservaciones();

        const datos = {
            cliente,
            fecha_cotizacion: fecha,
            tipo_cotizacion: tipo,
            productos,
            logo_descripcion: document.getElementById('descripcion_logo')?.value || '',
            logo_imagenes: [],
            tecnicas,
            ubicaciones,
            observaciones_generales: observaciones,
            observaciones_tecnicas: document.getElementById('observaciones_tecnicas')?.value || '',
            estado: 'borrador'
        };

        const response = await fetch('/api/cotizaciones', {
            method: 'POST',
            body: JSON.stringify(datos),
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(' Cotización guardada como borrador');

        } else {
            alert(' Error: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {

        alert('Error: ' + error.message);
    }
}

/**
 * Enviar cotización
 */
async function enviarCotizacion() {
    try {
        const cliente = document.getElementById('cliente')?.value;
        const fecha = document.getElementById('fechaActual')?.value;
        const tipo = document.getElementById('tipo_cotizacion')?.value;

        if (!cliente || !fecha) {
            alert('Por favor completa los datos del cliente');
            return;
        }

        const productos = recopilarProductos();
        if (productos.length === 0) {
            alert('Por favor agrega al menos una prenda');
            return;
        }

        const tecnicas = recopilarTecnicas();
        const ubicaciones = recopilarUbicaciones();
        const observaciones = recopilarObservaciones();

        const datos = {
            cliente,
            fecha_cotizacion: fecha,
            tipo_cotizacion: tipo,
            productos,
            logo_descripcion: document.getElementById('descripcion_logo')?.value || '',
            logo_imagenes: [],
            tecnicas,
            ubicaciones,
            observaciones_generales: observaciones,
            observaciones_tecnicas: document.getElementById('observaciones_tecnicas')?.value || '',
            estado: 'enviada'
        };

        const response = await fetch('/api/cotizaciones', {
            method: 'POST',
            body: JSON.stringify(datos),
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(' Cotización enviada exitosamente');

            // Redirigir a lista de cotizaciones
            // window.location.href = '/cotizaciones';
        } else {
            alert(' Error: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {

        alert('Error: ' + error.message);
    }
}

/**
 * Modificar función irAlPaso para actualizar resumen
 */
const irAlPasoOriginal = window.irAlPaso;
window.irAlPaso = function(paso) {
    irAlPasoOriginal(paso);
    
    // Actualizar resumen si vamos al paso 5 (REVISAR COTIZACIÓN)
    if (paso === 5) {
        // Primero intentar con la función completa si está disponible
        if (typeof actualizarResumenCompleto === 'function') {

            actualizarResumenCompleto();
        } else {
            // Si no está disponible, usar la función local

            actualizarResumen();
        }
        
        // Además, actualizar reflectivo si está disponible
        if (typeof actualizarResumenReflectivoPaso4 === 'function') {

            actualizarResumenReflectivoPaso4();
        }
    }
};
