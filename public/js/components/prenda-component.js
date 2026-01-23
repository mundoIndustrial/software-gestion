/**
 * Componente de Gestión de Prendas
 * Maneja renderizado y lógica de prendas
 * 
 * @class PrendaComponent
 */

class PrendaComponent {
    constructor() {
        this.templates = window.templates || {};
    }

    // ============================================================
    // RENDERIZADO DE PRENDAS
    // ============================================================

    /**
     * Renderizar todas las prendas
     * @param {Array} prendas - Array de prendas
     * @param {Object} options - Opciones de renderizado
     */
    renderizarPrendas(prendas, options = {}) {
        const container = document.getElementById('prendas-container-editable');
        if (!container) {

            return;
        }

        if (!prendas || prendas.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>No hay prendas para mostrar</p>
                </div>
            `;
            return;
        }

        let html = '';
        prendas.forEach((prenda, index) => {
            if (!window.PedidoState?.isPrendaEliminada(index)) {
                html += this.renderizarPrenda(prenda, index, options);
            }
        });

        container.innerHTML = html;

    }

    /**
     * Renderizar una prenda individual
     * @param {Object} prenda - Datos de la prenda
     * @param {number} index - Índice de la prenda
     * @param {Object} options - Opciones
     * @returns {string} HTML de la prenda
     */
    renderizarPrenda(prenda, index, options = {}) {
        const fotosHtml = this.renderizarFotosPrenda(prenda, index);
        const variacionesHtml = this.renderizarVariaciones(prenda, index);
        const telasHtml = this.renderizarTelas(prenda, index);
        const tallasHtml = this.renderizarTallas(prenda, index);

        return `
            <div class="prenda-card-editable" data-prenda-index="${index}">
                <!-- Header -->
                <div class="prenda-header">
                    <div class="prenda-title">
                         Prenda ${index + 1}: ${prenda.nombre_producto || 'Sin nombre'}
                    </div>
                    <div class="prenda-actions">
                        <button type="button" 
                            class="btn-eliminar-prenda" 
                            onclick="window.PrendaComponent.eliminarPrenda(${index})">
                            🗑️ Eliminar Prenda
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="prenda-content">
                    <!-- Información -->
                    <div class="prenda-info-section">
                        ${this.renderizarInfoBasica(prenda, index)}
                        ${variacionesHtml}
                        ${telasHtml}
                        ${tallasHtml}
                    </div>

                    <!-- Fotos -->
                    <div class="prenda-fotos-section">
                        ${fotosHtml}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Renderizar información básica de prenda
     * @param {Object} prenda - Datos de la prenda
     * @param {number} index - Índice
     * @returns {string} HTML
     */
    renderizarInfoBasica(prenda, index) {
        return `
            <div class="form-group-inline">
                <div class="form-group-editable">
                    <label>Nombre de Prenda</label>
                    <input type="text" 
                        class="prenda-nombre" 
                        data-prenda-index="${index}"
                        value="${prenda.nombre_producto || ''}"
                        placeholder="Ej: Camisa, Pantalón..."
                    >
                </div>
                <div class="form-group-editable">
                    <label>Descripción</label>
                    <textarea 
                        class="prenda-descripcion" 
                        data-prenda-index="${index}"
                        placeholder="Descripción opcional..."
                        rows="2"
                    >${prenda.descripcion || ''}</textarea>
                </div>
            </div>
        `;
    }

    /**
     * Renderizar variaciones de prenda
     * @param {Object} prenda - Datos de la prenda
     * @param {number} index - Índice
     * @returns {string} HTML
     */
    renderizarVariaciones(prenda, index) {
        if (!prenda.variantes || Object.keys(prenda.variantes).length === 0) {
            return '';
        }

        let html = '<div class="variaciones-section"><strong>Variaciones:</strong><table>';
        html += '<thead><tr><th>Campo</th><th>Valor</th><th>Observaciones</th></tr></thead><tbody>';

        for (const [campo, valor] of Object.entries(prenda.variantes)) {
            if (campo.includes('_obs')) continue; // Skip observaciones, se muestran aparte

            const campoObs = `${campo}_obs`;
            const observaciones = prenda.variantes[campoObs] || '';

            html += `
                <tr>
                    <td><strong>${this.formatearNombreCampo(campo)}</strong></td>
                    <td>
                        <input type="text" 
                            class="variacion-valor"
                            data-field="${campo}"
                            data-prenda-index="${index}"
                            value="${valor || ''}"
                        >
                    </td>
                    <td>
                        <textarea 
                            class="variacion-obs"
                            data-field="${campoObs}"
                            data-prenda-index="${index}"
                            placeholder="Observaciones..."
                            rows="1"
                        >${observaciones}</textarea>
                    </td>
                </tr>
            `;
        }

        html += '</tbody></table></div>';
        return html;
    }

    /**
     * Renderizar telas de prenda
     * @param {Object} prenda - Datos de la prenda
     * @param {number} index - Índice
     * @returns {string} HTML
     */
    renderizarTelas(prenda, index) {
        const telas = prenda.telas || prenda.variantes?.telas_multiples || [];
        
        if (telas.length === 0) {
            return '';
        }

        let html = '<div class="telas-seccion" data-section="telas"><strong>Telas:</strong>';
        
        telas.forEach((tela, telaIndex) => {
            html += this.renderizarTela(tela, index, telaIndex);
        });

        html += `
            <button type="button" 
                class="btn-agregar-talla-nuevo" 
                onclick="window.PrendaComponent.agregarTela(${index})"
                style="margin-top: 1rem;">
                ➕ Agregar Tela
            </button>
        </div>`;

        return html;
    }

    /**
     * Renderizar una tela individual
     * @param {Object} tela - Datos de la tela
     * @param {number} prendaIndex - Índice de prenda
     * @param {number} telaIndex - Índice de tela
     * @returns {string} HTML
     */
    renderizarTela(tela, prendaIndex, telaIndex) {
        const fotos = window.PedidoState?.getFotosTela(prendaIndex, telaIndex) || [];
        const fotosExistentes = tela.fotos || [];

        return `
            <div class="tela-item" data-tela-index="${telaIndex}">
                <div class="form-group-inline">
                    <div class="form-group-editable">
                        <label>Nombre Tela</label>
                        <input type="text" 
                            class="tela-nombre"
                            data-prenda-index="${prendaIndex}"
                            data-tela-index="${telaIndex}"
                            value="${tela.nombre_tela || ''}"
                            placeholder="Ej: Algodón, Poliéster..."
                        >
                    </div>
                    <div class="form-group-editable">
                        <label>Color</label>
                        <input type="text" 
                            class="tela-color"
                            data-prenda-index="${prendaIndex}"
                            data-tela-index="${telaIndex}"
                            value="${tela.color || ''}"
                            placeholder="Ej: Azul, Rojo..."
                        >
                    </div>
                    <div class="form-group-editable">
                        <label>Referencia</label>
                        <input type="text" 
                            class="tela-referencia"
                            data-prenda-index="${prendaIndex}"
                            data-tela-index="${telaIndex}"
                            value="${tela.referencia || ''}"
                            placeholder="Referencia..."
                        >
                    </div>
                </div>

                <!-- Fotos de tela -->
                <div class="tela-fotos">
                    <button type="button" 
                        class="btn-agregar-talla-nuevo"
                        onclick="window.abrirModalAgregarFotosTela(${prendaIndex}, ${telaIndex})"
                        style="margin-bottom: 0.5rem;">
                        📸 Agregar Fotos de Tela
                    </button>
                    
                    <div class="fotos-adicionales">
                        ${fotosExistentes.map(foto => `
                            <img src="${foto.url || foto}" 
                                class="foto-mini" 
                                onclick="window.abrirModalImagen('${foto.url || foto}')"
                                alt="Foto tela">
                        `).join('')}
                        ${fotos.map(foto => `
                            <img src="${foto.url}" 
                                class="foto-mini" 
                                onclick="window.abrirModalImagen('${foto.url}')"
                                alt="Foto tela nueva">
                        `).join('')}
                    </div>
                </div>

                <button type="button" 
                    class="btn-eliminar-variacion"
                    onclick="window.PrendaComponent.eliminarTela(${prendaIndex}, ${telaIndex})"
                    style="margin-top: 0.5rem;">
                    🗑️ Eliminar Tela
                </button>
            </div>
        `;
    }

    /**
     * Renderizar tallas de prenda
     * @param {Object} prenda - Datos de la prenda
     * @param {number} index - Índice
     * @returns {string} HTML
     */
    renderizarTallas(prenda, index) {
        const cantidades = prenda.cantidades || {};
        const tallas = Object.keys(cantidades);

        let html = '<div class="tallas-editable"><label>Tallas y Cantidades:</label>';

        if (tallas.length === 0) {
            html += '<p style="color: #666; font-style: italic;">No hay tallas agregadas</p>';
        } else {
            tallas.forEach(talla => {
                html += `
                    <div class="talla-item">
                        <strong style="min-width: 60px;">${talla}:</strong>
                        <input type="number" 
                            class="talla-cantidad-editable"
                            data-prenda="${index}"
                            data-talla="${talla}"
                            min="0"
                            value="${cantidades[talla] || 0}"
                            placeholder="Cantidad"
                        >
                        <button type="button" 
                            class="btn-quitar-talla"
                            onclick="window.TallaComponent.eliminarTalla(${index}, '${talla}')">
                            ✕ Quitar
                        </button>
                    </div>
                `;
            });
        }

        html += `
            <button type="button" 
                class="btn-agregar-talla-nuevo"
                onclick="window.TallaComponent.mostrarModalAgregarTalla(${index})"
                style="margin-top: 0.5rem;">
                ➕ Agregar Talla
            </button>
        </div>`;

        return html;
    }

    /**
     * Renderizar fotos de prenda
     * @param {Object} prenda - Datos de la prenda
     * @param {number} index - Índice
     * @returns {string} HTML
     */
    renderizarFotosPrenda(prenda, index) {
        const fotosExistentes = prenda.fotos || [];
        const fotosNuevas = window.PedidoState?.getFotosPrenda(index) || [];

        let html = '<div class="prenda-fotos">';
        html += `
            <button type="button" 
                class="btn-agregar-talla-nuevo"
                onclick="window.abrirModalAgregarFotosPrenda(${index})"
                style="margin-bottom: 1rem; width: 100%;">
                📸 Agregar Fotos
            </button>
        `;

        // Foto principal
        if (fotosExistentes.length > 0 || fotosNuevas.length > 0) {
            const fotosPrincipal = fotosExistentes[0] || fotosNuevas[0];
            html += `
                <img src="${fotosPrincipal.url || fotosPrincipal}" 
                    class="prenda-foto-principal"
                    onclick="window.abrirGaleriaPrenda(${index})"
                    alt="Foto prenda">
            `;
        }

        // Fotos adicionales
        const todasFotos = [...fotosExistentes.slice(1), ...fotosNuevas.slice(fotosExistentes.length > 0 ? 0 : 1)];
        if (todasFotos.length > 0) {
            html += '<div class="fotos-adicionales">';
            todasFotos.forEach(foto => {
                html += `
                    <img src="${foto.url || foto}" 
                        class="foto-mini"
                        onclick="window.abrirGaleriaPrenda(${index})"
                        alt="Foto adicional">
                `;
            });
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    // ============================================================
    // ACCIONES
    // ============================================================

    /**
     * Eliminar prenda
     * @param {number} index - Índice de la prenda
     */
    async eliminarPrenda(index) {
        const result = await Swal.fire({
            title: '¿Eliminar prenda?',
            text: '¿Estás seguro de eliminar esta prenda?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            window.PedidoState.removePrenda(index);
            
            const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${index}"]`);
            if (prendaCard) {
                prendaCard.remove();
            }



            Swal.fire({
                icon: 'success',
                title: 'Prenda eliminada',
                timer: 1500,
                showConfirmButton: false
            });
        }
    }

    /**
     * Agregar tela a prenda
     * @param {number} prendaIndex - Índice de la prenda
     */
    agregarTela(prendaIndex) {
        const prenda = window.PedidoState.getPrenda(prendaIndex);
        if (!prenda) return;

        if (!prenda.telas) prenda.telas = [];
        if (!prenda.variantes) prenda.variantes = {};
        if (!prenda.variantes.telas_multiples) prenda.variantes.telas_multiples = [];

        const nuevaTela = {
            nombre_tela: '',
            color: '',
            referencia: '',
            fotos: []
        };

        prenda.telas.push(nuevaTela);
        prenda.variantes.telas_multiples.push(nuevaTela);

        window.PedidoState.updatePrenda(prendaIndex, prenda);

        // Re-renderizar solo la sección de telas
        this.actualizarSeccionTelas(prendaIndex);


    }

    /**
     * Eliminar tela de prenda
     * @param {number} prendaIndex - Índice de la prenda
     * @param {number} telaIndex - Índice de la tela
     */
    async eliminarTela(prendaIndex, telaIndex) {
        const result = await Swal.fire({
            title: '¿Eliminar tela?',
            text: '¿Estás seguro de eliminar esta tela?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            const prenda = window.PedidoState.getPrenda(prendaIndex);
            if (prenda) {
                if (prenda.telas) prenda.telas.splice(telaIndex, 1);
                if (prenda.variantes?.telas_multiples) {
                    prenda.variantes.telas_multiples.splice(telaIndex, 1);
                }

                window.PedidoState.updatePrenda(prendaIndex, prenda);
                this.actualizarSeccionTelas(prendaIndex);



                Swal.fire({
                    icon: 'success',
                    title: 'Tela eliminada',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    }

    /**
     * Actualizar solo la sección de telas
     * @param {number} prendaIndex - Índice de la prenda
     */
    actualizarSeccionTelas(prendaIndex) {
        const prenda = window.PedidoState.getPrenda(prendaIndex);
        if (!prenda) return;

        const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
        if (!prendaCard) return;

        const telasSection = prendaCard.querySelector('[data-section="telas"]');
        if (telasSection) {
            telasSection.outerHTML = this.renderizarTelas(prenda, prendaIndex);
        }
    }

    // ============================================================
    // UTILIDADES
    // ============================================================

    /**
     * Formatear nombre de campo para mostrar
     * @param {string} campo - Nombre del campo
     * @returns {string} Nombre formateado
     */
    formatearNombreCampo(campo) {
        return campo
            .replace(/_/g, ' ')
            .replace(/\b\w/g, l => l.toUpperCase());
    }

    /**
     * Recopilar datos de todas las prendas del DOM
     * @returns {Array} Array de prendas con datos actualizados
     */
    recopilarDatosPrendas() {
        const prendas = [];
        const prendasCards = document.querySelectorAll('.prenda-card-editable');

        prendasCards.forEach((card, index) => {
            const prendaIndex = parseInt(card.dataset.prendaIndex);
            const prenda = window.PedidoState.getPrenda(prendaIndex);
            
            if (!prenda) return;

            // Actualizar nombre y descripción
            const nombreInput = card.querySelector('.prenda-nombre');
            const descInput = card.querySelector('.prenda-descripcion');
            
            if (nombreInput) prenda.nombre_producto = nombreInput.value;
            if (descInput) prenda.descripcion = descInput.value;

            // Actualizar cantidades
            prenda.cantidades = window.TallaComponent.getCantidadesPorTalla(prendaIndex);

            // Actualizar variaciones
            card.querySelectorAll('.variacion-valor').forEach(input => {
                const campo = input.dataset.field;
                if (campo && prenda.variantes) {
                    prenda.variantes[campo] = input.value;
                }
            });

            card.querySelectorAll('.variacion-obs').forEach(textarea => {
                const campo = textarea.dataset.field;
                if (campo && prenda.variantes) {
                    prenda.variantes[campo] = textarea.value;
                }
            });

            // Actualizar telas
            card.querySelectorAll('[data-tela-index]').forEach(telaRow => {
                const telaIdx = parseInt(telaRow.dataset.telaIndex);
                const nombreInput = telaRow.querySelector('.tela-nombre');
                const colorInput = telaRow.querySelector('.tela-color');
                const refInput = telaRow.querySelector('.tela-referencia');

                if (prenda.telas?.[telaIdx]) {
                    prenda.telas[telaIdx].nombre_tela = nombreInput?.value || '';
                    prenda.telas[telaIdx].color = colorInput?.value || '';
                    prenda.telas[telaIdx].referencia = refInput?.value || '';
                }
            });

            prendas.push(prenda);
        });

        return prendas;
    }
}

// Crear instancia global
window.PrendaComponent = new PrendaComponent();
