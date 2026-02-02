/**
 *  UI COMPONENTS
 * 
 * Componentes reutilizables para renderizar el formulario de pedidos.
 * Funciones puras que retornan HTML strings (framework-agnostic).
 * 
 * Uso:
 * const html = UIComponents.renderPrendaCard(prenda, actions);
 * document.getElementById('container').innerHTML = html;
 * 
 * @author Senior Frontend Developer
 * @version 1.0.0
 */

const UIComponents = {
    // ==================== TEMPLATES PRINCIPALES ====================

    /**
     * Renderizar tarjeta de prenda
     */
    renderPrendaCard(prenda, actions = {}) {
        const variantesCount = prenda.variantes?.length || 0;
        const procesosCount = prenda.procesos?.length || 0;
        const fotosCount = (prenda.fotos_prenda?.length || 0) + (prenda.fotos_tela?.length || 0);
        const itemsCount = prenda.variantes?.reduce((sum, v) => sum + v.cantidad, 0) || 0;

        return `
            <div class="card mb-3 border-left-primary" data-prenda-id="${prenda._id}">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">${this.escape(prenda.nombre_prenda)}</h5>
                        <small>${this.escape(prenda.descripcion || '(sin descripción)')}</small>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-light" 
                                data-action="edit-prenda" data-prenda-id="${prenda._id}"
                                title="Editar prenda">
                             Editar
                        </button>
                        <button type="button" class="btn btn-danger" 
                                data-action="delete-prenda" data-prenda-id="${prenda._id}"
                                title="Eliminar prenda">
                            🗑️ Eliminar
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- RESUMEN PRENDA -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="badge-group">
                                <span class="badge badge-info"> ${this.capitalize(prenda.genero || 'unisex')}</span>
                                <span class="badge badge-secondary"> ${variantesCount} variante${variantesCount !== 1 ? 's' : ''}</span>
                                <span class="badge badge-success"> ${itemsCount} items totales</span>
                                <span class="badge badge-warning"> ${procesosCount} proceso${procesosCount !== 1 ? 's' : ''}</span>
                                <span class="badge badge-dark">📷 ${fotosCount} foto${fotosCount !== 1 ? 's' : ''}</span>
                                ${prenda.de_bodega ? '<span class="badge badge-info">🏭 De bodega</span>' : ''}
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑAS -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#variantes-${prenda._id}" role="tab">
                                Variantes (${variantesCount})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#fotos-${prenda._id}" role="tab">
                                Fotos (${fotosCount})
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#procesos-${prenda._id}" role="tab">
                                Procesos (${procesosCount})
                            </a>
                        </li>
                    </ul>

                    <!-- CONTENIDO PESTAÑAS -->
                    <div class="tab-content p-3">
                        <!-- TAB: VARIANTES -->
                        <div class="tab-pane fade show active" id="variantes-${prenda._id}" role="tabpanel">
                            <table class="table table-sm table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Talla</th>
                                        <th>Cantidad</th>
                                        <th>Color</th>
                                        <th>Tela</th>
                                        <th>Opciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${prenda.variantes && prenda.variantes.length > 0
                                        ? prenda.variantes.map(v => this.renderVarianteRow(v, prenda._id)).join('')
                                        : '<tr><td colspan="5" class="text-muted text-center">Sin variantes</td></tr>'
                                    }
                                </tbody>
                            </table>
                            <button class="btn btn-sm btn-success" 
                                    data-action="add-variante" data-prenda-id="${prenda._id}">
                                ➕ Agregar variante
                            </button>
                        </div>

                        <!-- TAB: FOTOS -->
                        <div class="tab-pane fade" id="fotos-${prenda._id}" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>📸 Fotos de prenda</h6>
                                    <div class="foto-gallery mb-3">
                                        ${prenda.fotos_prenda && prenda.fotos_prenda.length > 0
                                            ? prenda.fotos_prenda.map(f => this.renderFotoThumb(f, prenda._id, 'prenda')).join('')
                                            : '<p class="text-muted">Sin fotos</p>'
                                        }
                                    </div>
                                    <input type="file" class="form-control-file" 
                                           accept="image/*" multiple
                                           data-action="upload-foto-prenda" data-prenda-id="${prenda._id}">
                                </div>
                                <div class="col-md-6">
                                    <h6> Fotos de tela</h6>
                                    <div class="foto-gallery mb-3">
                                        ${prenda.fotos_tela && prenda.fotos_tela.length > 0
                                            ? prenda.fotos_tela.map(f => this.renderFotoThumb(f, prenda._id, 'tela')).join('')
                                            : '<p class="text-muted">Sin fotos</p>'
                                        }
                                    </div>
                                    <input type="file" class="form-control-file" 
                                           accept="image/*" multiple
                                           data-action="upload-foto-tela" data-prenda-id="${prenda._id}">
                                </div>
                            </div>
                        </div>

                        <!-- TAB: PROCESOS -->
                        <div class="tab-pane fade" id="procesos-${prenda._id}" role="tabpanel">
                            <div class="procesos-list">
                                ${prenda.procesos && prenda.procesos.length > 0
                                    ? prenda.procesos.map(p => this.renderProcesoCard(p, prenda._id)).join('')
                                    : '<p class="text-muted">Sin procesos definidos</p>'
                                }
                            </div>
                            <button class="btn btn-sm btn-info" 
                                    data-action="add-proceso" data-prenda-id="${prenda._id}">
                                ➕ Agregar proceso
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Renderizar fila de variante en tabla
     */
    renderVarianteRow(variante, prendaId) {
        return `
            <tr data-variante-id="${variante._id}">
                <td class="font-weight-bold">${this.escape(variante.talla)}</td>
                <td><span class="badge badge-primary">${variante.cantidad}</span></td>
                <td>${variante.color_id ? ` ID:${variante.color_id}` : '-'}</td>
                <td>${variante.tela_id ? ` ID:${variante.tela_id}` : '-'}</td>
                <td>
                    <button class="btn btn-xs btn-secondary" 
                            data-action="edit-variante" 
                            data-prenda-id="${prendaId}" 
                            data-variante-id="${variante._id}">
                        
                    </button>
                    <button class="btn btn-xs btn-danger" 
                            data-action="delete-variante" 
                            data-prenda-id="${prendaId}" 
                            data-variante-id="${variante._id}">
                        🗑️
                    </button>
                </td>
            </tr>
        `;
    },

    /**
     * Renderizar miniatura de foto
     */
    renderFotoThumb(foto, prendaId, tipo) {
        return `
            <div class="foto-thumb d-inline-block m-2 position-relative">
                <img src="${foto.file ? URL.createObjectURL(foto.file) : '#'}" 
                     alt="${this.escape(foto.nombre)}"
                     class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                <button class="btn btn-xs btn-danger position-absolute" 
                        style="top: -5px; right: -5px;"
                        data-action="delete-foto" 
                        data-prenda-id="${prendaId}" 
                        data-foto-id="${foto._id}"
                        data-foto-tipo="${tipo}">
                    ✕
                </button>
                <small class="d-block text-muted" style="width: 80px; font-size: 0.7em; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    ${this.escape(foto.nombre)}
                </small>
            </div>
        `;
    },

    /**
     * Renderizar tarjeta de proceso
     */
    renderProcesoCard(proceso, prendaId) {
        return `
            <div class="card mb-2 bg-light" data-proceso-id="${proceso._id}">
                <div class="card-body p-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Proceso ID: ${proceso.tipo_proceso_id || 'N/A'}</h6>
                            <p class="mb-1 small">${this.escape(proceso.observaciones || '(sin observaciones)')}</p>
                            ${proceso.ubicaciones.length > 0 
                                ? `<p class="mb-0"><small class="badge badge-secondary">${Array.isArray(proceso.ubicaciones) ? proceso.ubicaciones.join(', ') : proceso.ubicaciones}</small></p>`
                                : '<p class="mb-0 text-muted small">(sin ubicaciones)</p>'
                            }
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-secondary" 
                                    data-action="edit-proceso" 
                                    data-prenda-id="${prendaId}" 
                                    data-proceso-id="${proceso._id}">
                                
                            </button>
                            <button class="btn btn-danger" 
                                    data-action="delete-proceso" 
                                    data-prenda-id="${prendaId}" 
                                    data-proceso-id="${proceso._id}">
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Renderizar modal de formulario
     */
    renderModal(title, content, actions = []) {
        const actionButtons = actions.map(a => `
            <button type="button" class="btn btn-${a.variant || 'secondary'}" 
                    data-action="${a.action}">
                ${a.label}
            </button>
        `).join('');

        return `
            <div class="modal fade" id="formModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            ${content}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                Cancelar
                            </button>
                            ${actionButtons}
                        </div>
                    </div>
                </div>
            </div>
        `;
    },

    /**
     * Renderizar toast de notificación
     */
    renderToast(type = 'info', message = '', duration = 3000) {
        const bgClass = {
            'success': 'bg-success',
            'error': 'bg-danger',
            'warning': 'bg-warning',
            'info': 'bg-info'
        }[type] || 'bg-info';

        const icon = {
            'success': '',
            'error': '',
            'warning': '',
            'info': ''
        }[type] || '';

        const html = `
            <div class="toast ${bgClass} text-white position-fixed" 
                 style="bottom: 20px; right: 20px; min-width: 300px; z-index: 9999;"
                 role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        ${icon} ${this.escape(message)}
                    </div>
                    <button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast">
                        <span>&times;</span>
                    </button>
                </div>
            </div>
        `;

        const container = document.getElementById('toast-container') || (() => {
            const div = document.createElement('div');
            div.id = 'toast-container';
            document.body.appendChild(div);
            return div;
        })();

        const toastEl = document.createElement('div');
        toastEl.innerHTML = html;
        container.appendChild(toastEl.firstElementChild);

        if (duration > 0) {
            setTimeout(() => {
                const toast = container.querySelector('.toast');
                if (toast) toast.remove();
            }, duration);
        }
    },

    /**
     * Renderizar resumen del pedido
     */
    renderResumen(summary) {
        return `
            <div class="alert alert-info">
                <h5> Resumen del pedido</h5>
                <ul class="mb-0">
                    <li>Pedido ID: <strong>${summary.pedido_id || 'N/A'}</strong></li>
                    <li>Prendas: <strong>${summary.prendas}</strong></li>
                    <li>Variantes totales: <strong>${summary.variantes}</strong></li>
                    <li>Items a producir: <strong>${summary.items}</strong></li>
                    <li>Procesos: <strong>${summary.procesos}</strong></li>
                </ul>
                <small class="text-muted">Estado: ${summary.completo ? ' Listo para enviar' : ' Incompleto'}</small>
            </div>
        `;
    },

    /**
     * Renderizar errores de validación
     */
    renderValidationErrors(errors) {
        const errorList = errors.map(e => `
            <li>
                <strong>${this.escape(e.field)}:</strong> ${this.escape(e.message)}
            </li>
        `).join('');

        return `
            <div class="alert alert-danger">
                <h6> Errores de validación</h6>
                <ul class="mb-0">${errorList}</ul>
            </div>
        `;
    },

    // ==================== UTILIDADES ====================

    /**
     * Escapar HTML
     */
    escape(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    /**
     * Capitalizar primera letra
     */
    capitalize(text) {
        if (!text) return '';
        return text.charAt(0).toUpperCase() + text.slice(1);
    },

    /**
     * Formatear tamaño de archivo
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
};

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UIComponents;
}
