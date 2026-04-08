/**
 * =====================================================
 * MODAL EDITAR PEDIDO - FUNCIONALIDAD
 * =====================================================
 *
 * Requiere: supervisor-pedidos/core/bootstrap.js → window.supervisorPedidos
 */

if (!window.supervisorPedidos?.isReady) {
    throw new Error('[edit-pedido] window.supervisorPedidos no está disponible. Carga core/bootstrap.js ANTES.');
}

const _editService = window.supervisorPedidos.orderEditService;
const _editNotify = window.shared.notify;

// Variable para rastrear si se eliminaron imágenes
let imagenesEliminadas = false;

function abrirModalEditar(ordenId, numeroOrden) {
    imagenesEliminadas = false; // Resetear al abrir modal
    document.getElementById('editarNumeroOrden').textContent = '#' + numeroOrden;
    document.getElementById('editarOrdenId').value = ordenId;
    document.getElementById('modalEditarPedido').style.display = 'flex';
    cargarDatosPedido(ordenId);
}

function cerrarModalEditar() {
    document.getElementById('modalEditarPedido').style.display = 'none';
    document.getElementById('formEditarPedido').reset();
    document.getElementById('prendasContainer').innerHTML = '';
    
    // Si se eliminaron imágenes, recargar la página para actualizar todos los modales
    if (imagenesEliminadas) {
        location.reload();
    }
}

let coloresDisponibles = [];
let telasDisponibles = [];

async function cargarDatosPedido(ordenId) {
    try {
        const { orden, colores, telas } = await _editService.loadOrderData(ordenId);
        coloresDisponibles = colores;
        telasDisponibles = telas;

        document.getElementById('editarCliente').value = orden.cliente || '';
        document.getElementById('editarFormaPago').value = orden.forma_de_pago || '';
        document.getElementById('editarDiaEntrega').value = orden.dia_de_entrega || '';
        document.getElementById('editarNovedades').value = orden.novedades || '';
        cargarPrendas(orden.prendas);
    } catch (error) {
        console.error('[cargarDatosPedido] Error:', error);
        _editNotify.error(error.message || 'Error al cargar datos del pedido');
    }
}

function cargarPrendas(prendas) {
    const container = document.getElementById('prendasContainer');
    container.innerHTML = `
        <h3 style="margin: 0 0 1.5rem 0; color: #2c3e50; font-size: 1.1rem; border-bottom: 2px solid #e0e6ed; padding-bottom: 0.75rem;">
            <span class="material-symbols-rounded" style="vertical-align: middle; margin-right: 0.5rem;">checkroom</span>
            Prendas del Pedido
        </h3>
    `;
    
    prendas.forEach((prenda, index) => {
        // Mapear estructura del nuevo endpoint a la estructura esperada
        const prendaMapeada = {
            ...prenda,
            fotos: prenda.imagenes || [],
            fotos_logo: prenda.fotos_logo || [],
            fotos_tela: prenda.fotos_tela || prenda.imagenes_tela || [],
            obs_manga: prenda.obs_manga || '',
            obs_bolsillos: prenda.obs_bolsillos || '',
            obs_broche: prenda.obs_broche || '',
            obs_reflectivo: prenda.obs_reflectivo || '',
            tiene_bolsillos: prenda.tiene_bolsillos || false,
            tiene_reflectivo: prenda.tiene_reflectivo || false
        };
        
        const prendaHtml = crearPrendaHTML(prendaMapeada, index);
        container.insertAdjacentHTML('beforeend', prendaHtml);
    });
}

function crearPrendaHTML(prenda, index) {
    const tallasHtml = prenda.tallas && Array.isArray(prenda.tallas)
        ? prenda.tallas
            .filter(tallaRecord => tallaRecord.cantidad > 0)
            .sort((a, b) => {
                const orden = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'];
                const indexA = orden.indexOf(a.talla);
                const indexB = orden.indexOf(b.talla);
                if (indexA !== -1 && indexB !== -1) return indexA - indexB;
                if (indexA !== -1) return -1;
                if (indexB !== -1) return 1;
                return a.talla.localeCompare(b.talla, undefined, {numeric: true});
            })
            .map(tallaRecord => `
        <div class="talla-item">
            <label>${tallaRecord.genero} ${tallaRecord.talla}:</label>
            <input type="number" name="prendas[${index}][tallas][${tallaRecord.genero}][${tallaRecord.talla}]" value="${tallaRecord.cantidad}" min="1" required>
        </div>
    `).join('') : '';

    const fotosHtml = prenda.fotos ? prenda.fotos.map(foto => {
        let rutaFoto = foto.url || '';
        
        // Construir ruta final: agregar /storage/ solo si no comienza con storage/ o /storage/
        let rutaFinal = rutaFoto;
        if (!rutaFoto.startsWith('storage/') && !rutaFoto.startsWith('/storage/') && !rutaFoto.startsWith('/')) {
            rutaFinal = '/storage/' + rutaFoto;
        } else if (rutaFoto.startsWith('storage/') && !rutaFoto.startsWith('/storage/')) {
            // Si comienza con storage/ (sin /), agregar / al inicio
            rutaFinal = '/' + rutaFoto;
        }
        
        return `
        <div class="foto-item">
            <img src="${rutaFinal}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e0e6ed;">
            <button type="button" onclick="eliminarImagen('prenda', ${foto.id}, this)" 
                    style="position: absolute; top: -8px; right: -8px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1;">×</button>
        </div>
    `;
    }).join('') : '';

    const fotosLogoHtml = prenda.fotos_logo ? prenda.fotos_logo.map(foto => {
        let rutaFoto = foto.url || '';
        
        // Construir ruta final: agregar /storage/ solo si no comienza con storage/ o /storage/
        let rutaFinal = rutaFoto;
        if (!rutaFoto.startsWith('storage/') && !rutaFoto.startsWith('/storage/') && !rutaFoto.startsWith('/')) {
            rutaFinal = '/storage/' + rutaFoto;
        } else if (rutaFoto.startsWith('storage/') && !rutaFoto.startsWith('/storage/')) {
            // Si comienza con storage/ (sin /), agregar / al inicio
            rutaFinal = '/' + rutaFoto;
        }
        
        return `
        <div class="foto-item">
            <img src="${rutaFinal}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e0e6ed;">
            <button type="button" onclick="eliminarImagen('logo', ${foto.id}, this)" 
                    style="position: absolute; top: -8px; right: -8px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1;">×</button>
        </div>
    `;
    }).join('') : '';

    const fotosTelaHtml = prenda.fotos_tela ? prenda.fotos_tela.map(foto => {
        let rutaFoto = foto.url || '';
        
        // Construir ruta final: agregar /storage/ solo si no comienza con storage/ o /storage/
        let rutaFinal = rutaFoto;
        if (!rutaFoto.startsWith('storage/') && !rutaFoto.startsWith('/storage/') && !rutaFoto.startsWith('/')) {
            rutaFinal = '/storage/' + rutaFoto;
        } else if (rutaFoto.startsWith('storage/') && !rutaFoto.startsWith('/storage/')) {
            // Si comienza con storage/ (sin /), agregar / al inicio
            rutaFinal = '/' + rutaFoto;
        }
        
        return `
        <div class="foto-item">
            <img src="${rutaFinal}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #e0e6ed;">
            <button type="button" onclick="eliminarImagen('tela', ${foto.id}, this)" 
                    style="position: absolute; top: -8px; right: -8px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1;">×</button>
        </div>
    `;
    }).join('') : '';

    return `
        <div class="prenda-card">
            <h4>
                <span class="material-symbols-rounded">checkroom</span>
                Prenda ${index + 1}: ${prenda.nombre_prenda || 'Sin nombre'}
            </h4>
            <div class="prenda-card-body">
                <input type="hidden" name="prendas[${index}][id]" value="${prenda.id}">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group">
                        <label>Nombre de Prenda *</label>
                        <input type="text" name="prendas[${index}][nombre_prenda]" value="${prenda.nombre_prenda || ''}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Color</label>
                        <select name="prendas[${index}][color_id]" class="form-control">
                            <option value="">Seleccionar color</option>
                            ${coloresDisponibles.map(color => `
                                <option value="${color.id}" ${prenda.color_id == color.id ? 'selected' : ''}>
                                    ${color.nombre}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tela</label>
                        <select name="prendas[${index}][tela_id]" class="form-control">
                            <option value="">Seleccionar tela</option>
                            ${telasDisponibles.map(tela => `
                                <option value="${tela.id}" ${prenda.tela_id == tela.id ? 'selected' : ''}>
                                    ${tela.nombre}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
                
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Descripción</label>
                    <textarea name="prendas[${index}][descripcion]" class="form-control" rows="3">${prenda.descripcion || ''}</textarea>
                </div>
                
                <div class="variaciones-section" style="margin-top: 1rem; padding: 1rem; background: #f5f5f5; border-radius: 0.5rem; border-left: 4px solid #1e40af;">
                    <strong style="display: block; margin-bottom: 1rem; color: #333333; font-size: 1rem;"> Variaciones de la Prenda:</strong>
                    <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #d0d0d0; border-radius: 4px; overflow: hidden; font-size: 0.9rem;">
                        <thead>
                            <tr style="background: #f0f0f0; border-bottom: 2px solid #d0d0d0;">
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid #d0d0d0; width: 120px;">Tipo</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid #d0d0d0; width: 150px;">Valor</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600;">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0; font-weight: 500;">Manga</td>
                                <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0;">
                                    <input type="text" value="${prenda.tipo_manga_nombre || 'N/A'}" readonly style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; background: #f9f9f9; font-size: 0.875rem;">
                                </td>
                                <td style="padding: 0.75rem;">
                                    <textarea name="prendas[${index}][obs_manga]" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.875rem; resize: vertical; min-height: 60px;" placeholder="Ej: manga prueba">${prenda.obs_manga || ''}</textarea>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0; font-weight: 500;">${prenda.tipo_broche_nombre || 'Cierre'}</td>
                                <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0;">
                                    <input type="text" value="${prenda.tipo_broche_nombre || 'Botón'}" readonly style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; background: #f9f9f9; font-size: 0.875rem;">
                                </td>
                                <td style="padding: 0.75rem;">
                                    <textarea name="prendas[${index}][obs_broche]" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.875rem; resize: vertical; min-height: 60px;" placeholder="Ej: prueba boton">${prenda.obs_broche || ''}</textarea>
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0; font-weight: 500;">Bolsillos</td>
                                <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0; text-align: center;">
                                    <input type="checkbox" name="prendas[${index}][tiene_bolsillos]" value="1" ${prenda.tiene_bolsillos ? 'checked' : ''} style="width: 20px; height: 20px; cursor: pointer; accent-color: #1e40af;">
                                </td>
                                <td style="padding: 0.75rem;">
                                    <textarea name="prendas[${index}][obs_bolsillos]" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.875rem; resize: vertical; min-height: 60px;" placeholder="Ej: LLEVA BOLSILLOS CON TAPA BOTON Y OJAL">${prenda.obs_bolsillos || ''}</textarea>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0; font-weight: 500;">Reflectivo</td>
                                <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0; text-align: center;">
                                    <input type="checkbox" name="prendas[${index}][tiene_reflectivo]" value="1" ${prenda.tiene_reflectivo ? 'checked' : ''} style="width: 20px; height: 20px; cursor: pointer; accent-color: #1e40af;">
                                </td>
                                <td style="padding: 0.75rem;">
                                    <textarea name="prendas[${index}][obs_reflectivo]" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.875rem; resize: vertical; min-height: 60px;" placeholder="Ej: CON REFLECTIVO GRIS 2\\" DE 25 CICLOS EN H">${prenda.obs_reflectivo || ''}</textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                ${tallasHtml ? `<div class="form-group tallas-section">
                    <label class="tallas-label">
                        <span class="material-symbols-rounded">straighten</span>
                        Tallas (Talla : Cantidad)
                    </label>
                    <div class="tallas-grid">${tallasHtml}</div>
                </div>` : ''}
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Fotos de Prenda</label>
                    <div id="fotos-prenda-${index}" class="fotos-container">${fotosHtml}</div>
                    <input type="file" name="prendas[${index}][nuevas_fotos][]" class="form-control" multiple accept="image/*" onchange="previsualizarImagenes(this, 'fotos-prenda-${index}')">
                    <small>Puedes seleccionar múltiples imágenes</small>
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Fotos de Logo</label>
                    <div id="fotos-logo-${index}" class="fotos-container">${fotosLogoHtml}</div>
                    <input type="file" name="prendas[${index}][nuevas_fotos_logo][]" class="form-control" multiple accept="image/*" onchange="previsualizarImagenes(this, 'fotos-logo-${index}')">
                    <small>Puedes seleccionar múltiples imágenes</small>
                </div>
                
                <div class="form-group">
                    <label>Fotos de Tela</label>
                    <div id="fotos-tela-${index}" class="fotos-container">${fotosTelaHtml}</div>
                    <input type="file" name="prendas[${index}][nuevas_fotos_tela][]" class="form-control" multiple accept="image/*" onchange="previsualizarImagenes(this, 'fotos-tela-${index}')">
                    <small>Puedes seleccionar múltiples imágenes</small>
                </div>
            </div>
        </div>
    `;
}

function previsualizarImagenes(input, containerId) {
    const container = document.getElementById(containerId);
    const files = input.files;
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.createElement('div');
            preview.className = 'foto-preview';
            preview.style.cssText = 'position: relative; display: inline-block; margin: 0.5rem;';
            preview.innerHTML = `
                <img src="${e.target.result}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 2px solid #3498db;">
                <div style="position: absolute; top: -8px; right: -8px; background: #3498db; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px;">NEW</div>
            `;
            container.appendChild(preview);
        };
        
        reader.readAsDataURL(file);
    }
}

function eliminarImagen(tipo, id, button) {
    if (!confirm('¿Estás seguro de eliminar esta imagen?')) {
        return;
    }

    // Si estamos en el modal de edición de prendas, marcar para eliminación en lugar de eliminar inmediatamente
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    const modalVisible = modal && modal.style.display !== 'none';
    
    if (modalVisible) {
        _editService.markImageForDeletion(id);

        // Ocultar visualmente la imagen del preview
        button.closest('.foto-item').style.opacity = '0.3';
        button.closest('.foto-item').style.border = '2px dashed #e74c3c';
        button.textContent = '✓';
        button.style.background = '#27ae60';
        
        _editNotify.info('Imagen marcada para eliminación. Se eliminará al guardar los cambios.');
        return;
    }

    // Eliminación inmediata para otros casos
    _editService.deleteImageNow(tipo, id)
        .then(() => {
            button.closest('.foto-item').remove();
            _editNotify.success('Imagen eliminada correctamente');
            imagenesEliminadas = true;
            
            const ordenId = document.getElementById('editarOrdenId').value;
            if (ordenId) {
                cargarDatosPedido(ordenId);
            }
        })
        .catch(error => {
            _editNotify.error(error.message || 'Error al eliminar la imagen');
        });
}

async function calcularFechaEstimada() {
    const diaEntrega = document.getElementById('editarDiaEntrega').value;
    const ordenId = document.getElementById('editarOrdenId').value;

    try {
        const result = await _editService.calculateEstimatedDate(ordenId, parseInt(diaEntrega));

        const container = document.getElementById('fechaEstimadaContainer');
        const mostrada = document.getElementById('fechaEstimadaMostrada');
        const fieldOculto = document.getElementById('fechaEstimadaOculta');

        container.style.display = 'block';
        mostrada.textContent = result.fecha_estimada;
        fieldOculto.value = result.fecha_estimada_iso;
    } catch (error) {
        _editNotify.error(error.message || 'Error al calcular la fecha estimada');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Listener para cambios en días de entrega - mostrar/ocultar la fecha si existe
    const diaEntregaInput = document.getElementById('editarDiaEntrega');
    if (diaEntregaInput) {
        diaEntregaInput.addEventListener('change', function() {
            // Al cambiar el valor, ocultar la fecha estimada calculada hasta que presione el botón
            const container = document.getElementById('fechaEstimadaContainer');
            if (container) {
                container.style.display = 'none';
            }
        });
    }

    const formEditarPedido = document.getElementById('formEditarPedido');
    if (formEditarPedido) {
        formEditarPedido.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const ordenId = document.getElementById('editarOrdenId').value;
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-rounded">hourglass_empty</span> Guardando...';

            try {
                await _editService.saveOrder(ordenId, formData);
                _editNotify.success('Pedido actualizado correctamente');
                setTimeout(() => {
                    cerrarModalEditar();
                    location.reload();
                }, 1000);
            } catch (error) {
                _editNotify.error(error.message || 'Error al actualizar el pedido');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    const modalEditarPedido = document.getElementById('modalEditarPedido');
    if (modalEditarPedido) {
        modalEditarPedido.addEventListener('click', function(e) {
            if (e.target === this) cerrarModalEditar();
        });
    }
});

/**
 * Función wrapper para usar editarPedido (compatible con asesores)
 * Usa la misma función de asesores si está disponible, si no, usa abrirModalEditar
 */
window.editarPedido = async function(pedidoId) {
    // Intentar usar la función de asesores si existe
    if (typeof window._editarPedidoAsesores === 'function') {
        return window._editarPedidoAsesores(pedidoId);
    }
    
    // Fallback: usar la función del supervisor
    return abrirModalEditar(pedidoId);
};

