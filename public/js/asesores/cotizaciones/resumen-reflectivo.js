/**
 * resumen-reflectivo.js - Módulo para actualizar el resumen del reflectivo en Paso 5
 * 
 * Responsabilidades:
 * - Actualizar el resumen del reflectivo en el Paso 5 (Revisar)
 * - Mostrar descripción, ubicación y observaciones
 */

/**
 * Actualizar el resumen del reflectivo en el Paso 5
 */
function actualizarResumenReflectivo() {


    // Obtener datos del reflectivo
    const descripcion = document.getElementById('descripcion_reflectivo')?.value || '-';
    const ubicacion = document.getElementById('ubicacion_reflectivo')?.value || '-';

    // Actualizar descripción
    const descElement = document.getElementById('resumen_reflectivo_desc');
    if (descElement) {
        descElement.textContent = descripcion;

    }

    // Actualizar ubicación
    const ubicElement = document.getElementById('resumen_reflectivo_ubicacion');
    if (ubicElement) {
        ubicElement.textContent = ubicacion;

    }

    // Actualizar observaciones generales
    const obsElement = document.getElementById('resumen_reflectivo_observaciones');
    if (obsElement) {
        obsElement.innerHTML = '';

        // Obtener observaciones del reflectivo desde la variable global
        if (typeof observacionesReflectivo !== 'undefined' && Array.isArray(observacionesReflectivo)) {
            if (observacionesReflectivo.length === 0) {
                obsElement.innerHTML = '<p style="color: #999; font-size: 0.9rem;">Sin observaciones</p>';
            } else {
                observacionesReflectivo.forEach((obs, index) => {
                    const div = document.createElement('div');
                    div.style.cssText = `
                        padding: 8px;
                        background: #f9f9f9;
                        border-left: 3px solid #3498db;
                        margin-bottom: 8px;
                        border-radius: 4px;
                    `;

                    const tipo = obs.tipo === 'checkbox' ? '☑️' : '';
                    const valor = obs.valor ? ` = ${obs.valor}` : '';
                    div.innerHTML = `<strong>${tipo} ${obs.texto}</strong>${valor}`;

                    obsElement.appendChild(div);
                });

            }
        } else {
            obsElement.innerHTML = '<p style="color: #999; font-size: 0.9rem;">Sin observaciones</p>';
        }
    }


}

/**
 * Actualizar resumen completo (cliente, prendas, logo, reflectivo)
 * Esta función se llama cuando se navega al Paso 5
 */
function actualizarResumenCompleto() {


    // Actualizar cliente
    const clienteInput = document.getElementById('cliente');
    if (clienteInput) {
        const resumenCliente = document.getElementById('resumen_cliente');
        if (resumenCliente) {
            resumenCliente.textContent = clienteInput.value || '-';
        }
    }

    // Actualizar fecha
    const fechaInput = document.getElementById('fechaActual');
    if (fechaInput) {
        const resumenFecha = document.getElementById('resumen_fecha');
        if (resumenFecha) {
            resumenFecha.textContent = fechaInput.value || '-';
        }
    }

    // Actualizar tipo
    const tipoVentaInput = document.getElementById('tipo_venta');
    if (tipoVentaInput) {
        const resumenTipo = document.getElementById('resumen_tipo');
        if (resumenTipo) {
            const mapeoTipo = {
                'M': ' Prendas',
                'D': ' Logos',
                'X': '✨ Prendas con Bordado'
            };
            resumenTipo.textContent = mapeoTipo[tipoVentaInput.value] || '-';
        }
    }

    // Actualizar prendas
    const resumenPrendas = document.getElementById('resumen_prendas');
    if (resumenPrendas) {
        resumenPrendas.innerHTML = '';
        const prendas = document.querySelectorAll('.producto-card');
        if (prendas.length === 0) {
            resumenPrendas.innerHTML = '<p style="color: #999;">Sin prendas agregadas</p>';
        } else {
            prendas.forEach((prenda, index) => {
                const nombre = prenda.querySelector('input[name*="nombre_producto"]')?.value || 'Sin nombre';
                const cantidad = prenda.querySelector('input[name*="cantidad"]')?.value || '1';
                const tallas = prenda.querySelectorAll('.talla-btn.activo');
                const tallasText = Array.from(tallas).map(t => t.dataset.talla).join(', ') || 'Sin tallas';

                const div = document.createElement('div');
                div.style.cssText = `
                    padding: 10px;
                    background: white;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    margin-bottom: 10px;
                `;
                div.innerHTML = `
                    <strong>${nombre}</strong><br>
                    <small>Cantidad: ${cantidad} | Tallas: ${tallasText}</small>
                `;
                resumenPrendas.appendChild(div);
            });
        }
    }

    // Actualizar logo
    const resumenLogoDesc = document.getElementById('resumen_logo_desc');
    if (resumenLogoDesc) {
        const descripcionLogo = document.getElementById('descripcion_logo')?.value || '-';
        resumenLogoDesc.textContent = descripcionLogo;
    }

    // Actualizar técnicas
    const resumenTecnicas = document.getElementById('resumen_tecnicas');
    if (resumenTecnicas) {
        resumenTecnicas.innerHTML = '';
        const tecnicas = document.querySelectorAll('#tecnicas_seleccionadas > div');
        if (tecnicas.length === 0) {
            resumenTecnicas.innerHTML = '<span style="color: #999;">Sin técnicas</span>';
        } else {
            tecnicas.forEach(tag => {
                const input = tag.querySelector('input[name="tecnicas[]"]');
                if (input) {
                    const span = document.createElement('span');
                    span.style.cssText = `
                        background: #3498db;
                        color: white;
                        padding: 4px 8px;
                        border-radius: 4px;
                        font-size: 0.85rem;
                    `;
                    span.textContent = input.value;
                    resumenTecnicas.appendChild(span);
                }
            });
        }
    }

    // Actualizar reflectivo
    actualizarResumenReflectivo();


}

/**
 * Hook para actualizar resumen cuando se navega al Paso 5
 * Se llama desde irAlPaso(5)
 */
window.actualizarResumenAlNavegar = function() {

    setTimeout(() => {
        actualizarResumenCompleto();
    }, 100);
};
