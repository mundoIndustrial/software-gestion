// Crear Pedido - Script EDITABLE con soporte para edición y eliminación de prendas
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('cotizacion_search_editable');
    const hiddenInput = document.getElementById('cotizacion_id_editable');
    const dropdown = document.getElementById('cotizacion_dropdown_editable');
    const selectedDiv = document.getElementById('cotizacion_selected_editable');
    const selectedText = document.getElementById('cotizacion_selected_text_editable');
    
    const prendasContainer = document.getElementById('prendas-container-editable');
    const clienteInput = document.getElementById('cliente_editable');
    const asesoraInput = document.getElementById('asesora_editable');
    const formaPagoInput = document.getElementById('forma_de_pago_editable');
    const numeroCotizacionInput = document.getElementById('numero_cotizacion_editable');
    const numeroPedidoInput = document.getElementById('numero_pedido_editable');
    const formCrearPedido = document.getElementById('formCrearPedidoEditable');

    // Variables globales
    let prendasCargadas = [];
    let prendasEliminadas = new Set(); // Rastrear índices de prendas eliminadas

    const misCotizaciones = window.cotizacionesData || [];

    // ============================================================
    // BÚSQUEDA Y SELECCIÓN DE COTIZACIÓN
    // ============================================================
    
    function mostrarOpciones(filtro = '') {
        const filtroLower = filtro.toLowerCase();
        const opciones = misCotizaciones.filter(cot => {
            return cot.numero.toLowerCase().includes(filtroLower) ||
                   (cot.numero_cotizacion && cot.numero_cotizacion.toLowerCase().includes(filtroLower)) ||
                   cot.cliente.toLowerCase().includes(filtroLower);
        });

        if (misCotizaciones.length === 0) {
            dropdown.innerHTML = '<div style="padding: 1rem; color: #ef4444; text-align: center;"><strong>No hay cotizaciones aprobadas</strong></div>';
        } else if (opciones.length === 0) {
            dropdown.innerHTML = `<div style="padding: 1rem; color: #9ca3af; text-align: center;">No se encontraron cotizaciones</div>`;
        } else {
            dropdown.innerHTML = opciones.map(cot => {
                return `
                    <div onclick="seleccionarCotizacion(${cot.id}, '${cot.numero}', '${cot.cliente}', '${cot.asesora}', '${cot.formaPago}')" 
                         style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; cursor: pointer; transition: background 0.2s;"
                         onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='white'">
                        <div style="font-weight: 600; color: #1f2937;">${cot.numero}</div>
                        <div style="font-size: 0.875rem; color: #6b7280;">${cot.cliente} - ${cot.asesora}</div>
                    </div>
                `;
            }).join('');
        }
        dropdown.style.display = 'block';
    }

    searchInput.addEventListener('focus', () => mostrarOpciones(searchInput.value));
    searchInput.addEventListener('input', (e) => mostrarOpciones(e.target.value));
    document.addEventListener('click', (e) => {
        if (e.target !== searchInput && e.target !== dropdown) {
            dropdown.style.display = 'none';
        }
    });

    window.seleccionarCotizacion = function(id, numero, cliente, asesora, formaPago) {
        hiddenInput.value = id;
        searchInput.value = numero;
        numeroCotizacionInput.value = numero;
        clienteInput.value = cliente;
        asesoraInput.value = asesora;
        formaPagoInput.value = formaPago || '';
        dropdown.style.display = 'none';
        selectedText.textContent = `${numero} - ${cliente}`;
        selectedDiv.style.display = 'block';

        // Cargar prendas
        cargarPrendasDesdeCotizacion(id);
    };

    // ============================================================
    // CARGAR PRENDAS DESDE COTIZACIÓN (VÍA AJAX)
    // ============================================================
    
    function cargarPrendasDesdeCotizacion(cotizacionId) {
        fetch(`/asesores/pedidos-produccion/obtener-datos-cotizacion/${cotizacionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    prendasContainer.innerHTML = `<p style="color: #ef4444;">Error: ${data.error}</p>`;
                } else {
                    console.log('Datos de cotización obtenidos:', data);
                    prendasCargadas = data.prendas || [];
                    prendasEliminadas.clear(); // Limpiar eliminadas
                    
                    // Mostrar información de logos si existe
                    if (data.logo && data.logo.fotos && data.logo.fotos.length > 0) {
                        console.log('Logos encontrados:', data.logo.fotos.length);
                    }
                    
                    // Mostrar especificaciones generales
                    if (data.especificaciones) {
                        console.log('Especificaciones de cotización:', data.especificaciones);
                    }
                    
                    renderizarPrendasEditables(prendasCargadas, data.logo, data.especificaciones);
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                prendasContainer.innerHTML = `<p style="color: #ef4444;">Error al cargar las prendas: ${error.message}</p>`;
            });
    }

    // ============================================================
    // RENDERIZAR PRENDAS EDITABLES
    // ============================================================
    
    function renderizarPrendasEditables(prendas, logoCotizacion = null, especificacionesCotizacion = null) {
        if (!prendas || prendas.length === 0) {
            prendasContainer.innerHTML = '<p class="text-gray-500 text-center py-8">Esta cotización no tiene prendas</p>';
            return;
        }

        let html = '';

        prendas.forEach((prenda, index) => {
            // Saltar si la prenda fue eliminada
            if (prendasEliminadas.has(index)) {
                return;
            }

            const tallas = prenda.tallas || [];
            const fotos = prenda.fotos || [];
            const telaFotos = prenda.telaFotos || [];
            const fotoPrincipal = fotos.length > 0 ? fotos[0] : null;
            const fotosAdicionales = fotos.slice(1);
            const variantes = prenda.variantes || {};

            let nombreProenda = prenda.nombre_producto || '';
            const variacionesPrincipales = [];
            if (variantes.color) variacionesPrincipales.push(variantes.color);
            if (variacionesPrincipales.length > 0) {
                nombreProenda += ' (' + variacionesPrincipales.join(' - ') + ')';
            }

            // Generar HTML de tallas editables
            let tallasHtml = '';
            if (tallas.length > 0) {
                tallasHtml = `
                    <div class="tallas-editable">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1f2937;">
                            Tallas - Introduce cantidades:
                        </label>
                `;
                tallas.forEach(talla => {
                    tallasHtml += `
                        <div class="talla-item" data-talla="${talla}" data-prenda="${index}">
                            <label style="font-weight: 500; min-width: 80px; color: #374151;">${talla}</label>
                            <input type="number" 
                                   name="cantidades[${index}][${talla}]" 
                                   class="talla-cantidad"
                                   min="0" 
                                   value="0" 
                                   placeholder="0"
                                   data-talla="${talla}"
                                   data-prenda="${index}">
                            <button type="button" class="btn-quitar-talla" onclick="quitarTallaDelFormulario(${index}, '${talla}')">
                                ✕ Quitar
                            </button>
                        </div>
                    `;
                });
                tallasHtml += '</div>';
            }

            // Género (removido del formulario visual pero se mantiene en datos)

            // Generar HTML de variaciones de variantes (TABLA EDITABLE CON ELIMINACIÓN)
            let variacionesHtml = '';
            const variacionesArray = [];
            
            // Recopilar todas las variaciones en un array
            if (variantes.tipo_manga) {
                variacionesArray.push({
                    tipo: 'Manga',
                    valor: variantes.tipo_manga,
                    obs: variantes.obs_manga,
                    campo: 'tipo_manga'
                });
            }
            if (variantes.tipo_broche) {
                variacionesArray.push({
                    tipo: 'Cierre',
                    valor: variantes.tipo_broche,
                    obs: variantes.obs_broche,
                    campo: 'tipo_broche'
                });
            }
            if (variantes.tiene_bolsillos !== undefined) {
                variacionesArray.push({
                    tipo: 'Bolsillos',
                    valor: variantes.tiene_bolsillos ? 'Sí' : 'No',
                    obs: variantes.obs_bolsillos,
                    campo: 'tiene_bolsillos',
                    esCheckbox: true
                });
            }
            if (variantes.tiene_reflectivo !== undefined) {
                variacionesArray.push({
                    tipo: 'Reflectivo',
                    valor: variantes.tiene_reflectivo ? 'Sí' : 'No',
                    obs: variantes.obs_reflectivo,
                    campo: 'tiene_reflectivo',
                    esCheckbox: true
                });
            }
            
            if (variacionesArray.length > 0) {
                variacionesHtml = '<div class="variaciones-section" style="margin-top: 1rem; padding: 1rem; background: #f5f5f5; border-radius: 0.5rem; border-left: 4px solid #0066cc;">';
                variacionesHtml += '<strong style="display: block; margin-bottom: 1rem; color: #333333;">📋 Variaciones de la Prenda:</strong>';
                variacionesHtml += '<table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #d0d0d0; border-radius: 4px; overflow: hidden; font-size: 0.9rem;">';
                variacionesHtml += '<thead><tr style="background: #f0f0f0; border-bottom: 2px solid #d0d0d0;">';
                variacionesHtml += '<th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid #d0d0d0;">Tipo</th>';
                variacionesHtml += '<th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid #d0d0d0;">Valor</th>';
                variacionesHtml += '<th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid #d0d0d0;">Observaciones</th>';
                variacionesHtml += '<th style="padding: 0.75rem; text-align: center; font-weight: 600; width: 80px;">Eliminar</th>';
                variacionesHtml += '</tr></thead>';
                variacionesHtml += '<tbody>';
                
                variacionesArray.forEach((variacion, varIdx) => {
                    variacionesHtml += `<tr style="border-bottom: 1px solid #eee;" data-variacion="${varIdx}" data-prenda="${index}">
                        <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0; font-weight: 500;">${variacion.tipo}</td>
                        <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0;">
                            <input type="text" 
                                   value="${variacion.valor}" 
                                   data-field="${variacion.campo}" 
                                   data-prenda="${index}"
                                   data-variacion="${varIdx}"
                                   style="width: 100%; padding: 0.4rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.85rem;">
                        </td>
                        <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0;">
                            <textarea 
                                   data-field="${variacion.campo}_obs" 
                                   data-prenda="${index}"
                                   data-variacion="${varIdx}"
                                   style="width: 100%; padding: 0.4rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.85rem; min-height: 40px; resize: vertical; font-family: inherit;" placeholder="Agregar observaciones...">${variacion.obs || ''}</textarea>
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <button type="button" 
                                    class="btn-eliminar-variacion" 
                                    onclick="eliminarVariacionDePrenda(${index}, ${varIdx})"
                                    style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;">
                                ✕ Eliminar
                            </button>
                        </td>
                    </tr>`;
                });
                
                variacionesHtml += '</tbody></table>';
                variacionesHtml += '</div>';
            }

            // Generar HTML de telas/colores múltiples (EDITABLE)
            let telasHtml = '';
            if (variantes.telas_multiples && variantes.telas_multiples.length > 0) {
                telasHtml = '<div style="margin-top: 1rem; padding: 1rem; background: #f5f5f5; border-radius: 0.5rem; border-left: 4px solid #0066cc;">';
                telasHtml += '<strong style="display: block; margin-bottom: 0.75rem; color: #333333;">Telas/Colores:</strong>';
                telasHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 0.75rem;">';
                variantes.telas_multiples.forEach((tela, telaIdx) => {
                    telasHtml += `<div style="padding: 0.75rem; background: white; border-radius: 4px; border: 1px solid #d0d0d0;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.5rem;">
                            <div>
                                <label style="font-size: 0.8rem; color: #666; font-weight: 500; display: block; margin-bottom: 0.25rem;">Tela:</label>
                                <input type="text" value="${tela.tela}" data-field="tela_nombre" data-idx="${telaIdx}" data-prenda="${index}" style="width: 100%; padding: 0.4rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.85rem;">
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; color: #666; font-weight: 500; display: block; margin-bottom: 0.25rem;">Color:</label>
                                <input type="text" value="${tela.color}" data-field="tela_color" data-idx="${telaIdx}" data-prenda="${index}" style="width: 100%; padding: 0.4rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.85rem;">
                            </div>
                            <div>
                                <label style="font-size: 0.8rem; color: #666; font-weight: 500; display: block; margin-bottom: 0.25rem;">Referencia:</label>
                                <input type="text" value="${tela.referencia || ''}" data-field="tela_ref" data-idx="${telaIdx}" data-prenda="${index}" style="width: 100%; padding: 0.4rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.85rem;">
                            </div>
                        </div>
                    </div>`;
                });
                telasHtml += '</div>';
                telasHtml += '</div>';
            }

            // Generar HTML de foto principal y adicionales de prendas (MINIATURAS RESPONSIVAS)
            let fotosHtml = '';
            if (fotoPrincipal) {
                fotosHtml += `
                    <div style="width: 100%; display: flex; flex-direction: column; gap: 0.5rem;">
                        <div>
                            <img src="${fotoPrincipal}" alt="${prenda.nombre_producto}" 
                                 class="prenda-foto-principal" 
                                 style="width: 100%; height: 200px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 1px solid #d0d0d0; transition: all 0.2s;"
                                 onclick="abrirModalImagen('${fotoPrincipal}', '${prenda.nombre_producto}')">
                        </div>
                `;
                if (fotosAdicionales.length > 0) {
                    fotosHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(70px, 1fr)); gap: 0.4rem;">';
                    fotosAdicionales.forEach(foto => {
                        fotosHtml += `
                            <img src="${foto}" alt="Foto adicional prenda" 
                                 style="width: 100%; height: 100px; object-fit: cover; cursor: pointer; border: 1px solid #d0d0d0; border-radius: 4px; transition: all 0.2s;"
                                 onclick="abrirModalImagen('${foto}', '${prenda.nombre_producto}')">
                        `;
                    });
                    fotosHtml += '</div>';
                }
                fotosHtml += '</div>';
            }

            // Generar HTML de fotos de telas (MINIATURAS RESPONSIVAS)
            let fotosTelasHtml = '';
            if (telaFotos.length > 0) {
                fotosTelasHtml = '<div style="margin-top: 1rem; padding: 1rem; background: #f5f5f5; border-radius: 0.5rem; border-left: 4px solid #0066cc;">';
                fotosTelasHtml += '<strong style="display: block; margin-bottom: 0.75rem; color: #333;">Fotos de Telas:</strong>';
                fotosTelasHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 0.5rem;">';
                telaFotos.forEach(telaFoto => {
                    const fotoUrl = telaFoto.url || telaFoto.ruta_webp || telaFoto.ruta_original;
                    if (fotoUrl) {
                        fotosTelasHtml += `
                            <img src="${fotoUrl}" alt="Foto de tela" 
                                 style="width: 100%; height: 120px; object-fit: cover; cursor: pointer; border: 1px solid #d0d0d0; border-radius: 4px; transition: all 0.2s;"
                                 onclick="abrirModalImagen('${fotoUrl}', 'Foto de tela')">
                        `;
                    }
                });
                fotosTelasHtml += '</div></div>';
            }

            // Crear tarjeta completa
            html += `
                <div class="prenda-card-editable" data-prenda-index="${index}">
                    <div class="prenda-header">
                        <div class="prenda-title">
                            🧥 Prenda ${index + 1}: ${nombreProenda}
                        </div>
                        <div class="prenda-actions">
                            <button type="button" class="btn-eliminar-prenda" onclick="eliminarPrendaDelPedido(${index})">
                                🗑️ Eliminar Prenda
                            </button>
                        </div>
                    </div>

                    <div class="prenda-content">
                        <div class="prenda-info-section">
                            <div class="form-group-editable">
                                <label>Nombre del Producto:</label>
                                <input type="text" 
                                       name="nombre_producto[${index}]" 
                                       value="${prenda.nombre_producto || ''}"
                                       class="prenda-nombre"
                                       data-prenda="${index}">
                            </div>

                            <div class="form-group-editable">
                                <label>Descripción:</label>
                                <textarea name="descripcion[${index}]" 
                                          class="prenda-descripcion"
                                          data-prenda="${index}" style="min-height: 80px;">${prenda.descripcion || ''}</textarea>
                            </div>

                            ${telasHtml}
                            ${variacionesHtml}
                            ${tallasHtml}

                            <div class="prenda-resumen">
                                <strong>📊 Resumen:</strong><br>
                                <small>
                                    Tallas: ${tallas.length > 0 ? tallas.join(', ') : 'N/A'}<br>
                                    Fotos de prenda: ${fotos.length}<br>
                                    Fotos de telas: ${telaFotos.length}
                                </small>
                            </div>
                        </div>

                        <div class="prenda-fotos-section">
                            ${fotosHtml}
                            ${fotosTelasHtml}
                        </div>
                    </div>
                </div>
            `;
        });

        // Agregar información de logos al final (DESPUÉS de todas las prendas)
        if (logoCotizacion) {
            html += '<div style="margin-top: 3rem; padding: 2rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">';
            html += '<h3 style="margin: 0 0 1.5rem 0; font-size: 1.2rem; color: #333; border-bottom: 2px solid #0066cc; padding-bottom: 0.5rem;">Información de Bordado/Logo</h3>';
            
            // ========== DESCRIPCIÓN DEL BORDADO (EDITABLE) ==========
            if (logoCotizacion.descripcion) {
                html += `<div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333; font-size: 0.95rem;">Descripción del Bordado:</label>
                    <textarea name="logo_descripcion" 
                              style="width: 100%; padding: 0.75rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.95rem; font-family: inherit; min-height: 80px; color: #333;">${logoCotizacion.descripcion}</textarea>
                </div>`;
            }
            
            // ========== TÉCNICAS (TABLA EDITABLE Y ELIMINABLE) ==========
            if (logoCotizacion.tecnicas && logoCotizacion.tecnicas.length > 0) {
                html += `<div style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 4px; border-left: 4px solid #0066cc;">
                    <label style="display: block; font-weight: 600; margin-bottom: 1rem; color: #333; font-size: 0.95rem;">Técnicas Disponibles:</label>
                    <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #d0d0d0; border-radius: 4px; overflow: hidden; font-size: 0.9rem;">
                        <thead>
                            <tr style="background: #f0f0f0; border-bottom: 2px solid #d0d0d0;">
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid #d0d0d0;">Técnica</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid #d0d0d0;">Observaciones</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; width: 80px;">Eliminar</th>
                            </tr>
                        </thead>
                        <tbody>`;
                
                logoCotizacion.tecnicas.forEach((tecnica, tecIdx) => {
                    // Para la primera técnica, mostrar las observaciones generales de técnica si existen
                    const obsValue = tecIdx === 0 && logoCotizacion.observaciones_tecnicas ? logoCotizacion.observaciones_tecnicas : '';
                    
                    html += `<tr style="border-bottom: 1px solid #eee;" data-tecnica="${tecIdx}">
                        <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0; font-weight: 500;">
                            <input type="text" 
                                   value="${tecnica}" 
                                   data-field="tecnica_nombre"
                                   data-idx="${tecIdx}"
                                   style="width: 100%; padding: 0.4rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.85rem;">
                        </td>
                        <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0;">
                            <textarea 
                                   data-field="tecnica_obs"
                                   data-idx="${tecIdx}"
                                   style="width: 100%; padding: 0.4rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.85rem; min-height: 40px; resize: vertical; font-family: inherit;" 
                                   placeholder="Agregar observaciones de la técnica...">${obsValue}</textarea>
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <button type="button" 
                                    onclick="eliminarTecnicaDeBordado(${tecIdx})"
                                    style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;">
                                ✕ Eliminar
                            </button>
                        </td>
                    </tr>`;
                });
                
                html += `</tbody>
                    </table>
                </div>`;
            }
            
            // ========== UBICACIONES DEL LOGO (TABLA EDITABLE Y ELIMINABLE) ==========
            if (logoCotizacion.ubicaciones && logoCotizacion.ubicaciones.length > 0) {
                html += `<div style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 4px; border-left: 4px solid #0066cc;">
                    <label style="display: block; font-weight: 600; margin-bottom: 1rem; color: #333; font-size: 0.95rem;">Ubicaciones del Logo:</label>
                    <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #d0d0d0; border-radius: 4px; overflow: hidden; font-size: 0.9rem;">
                        <thead>
                            <tr style="background: #f0f0f0; border-bottom: 2px solid #d0d0d0;">
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid #d0d0d0;">Sección</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid #d0d0d0;">Ubicaciones Seleccionadas</th>
                                <th style="padding: 0.75rem; text-align: left; font-weight: 600; border-right: 1px solid #d0d0d0;">Observaciones</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; width: 80px;">Eliminar</th>
                            </tr>
                        </thead>
                        <tbody>`;
                
                logoCotizacion.ubicaciones.forEach((ubicacion, ubIdx) => {
                    const ubicacionesSeleccionadas = Array.isArray(ubicacion.ubicaciones_seleccionadas) ? ubicacion.ubicaciones_seleccionadas : [];
                    html += `<tr style="border-bottom: 1px solid #eee;" data-ubicacion="${ubIdx}">
                        <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0; font-weight: 500;">
                            <input type="text" 
                                   value="${ubicacion.seccion}" 
                                   data-field="ubicacion_seccion"
                                   data-idx="${ubIdx}"
                                   style="width: 100%; padding: 0.4rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.85rem;">
                        </td>
                        <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0;">
                            <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                                ${ubicacionesSeleccionadas.map((ub, ubItemIdx) => `
                                    <span style="display: inline-flex; align-items: center; gap: 0.3rem; background: #e3f2fd; color: #1976d2; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem;">
                                        ${ub}
                                        <button type="button" 
                                                onclick="eliminarUbicacionItem(${ubIdx}, ${ubItemIdx})"
                                                style="background: none; border: none; color: #1976d2; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>
                                    </span>
                                `).join('')}
                            </div>
                            <div style="display: flex; gap: 0.3rem; margin-top: 0.3rem;">
                                <input type="text" 
                                       data-field="ubicacion_nueva"
                                       data-idx="${ubIdx}"
                                       placeholder="Agregar nueva ubicación..."
                                       style="flex: 1; padding: 0.4rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.85rem;">
                                <button type="button"
                                        onclick="agregarUbicacionNueva(${ubIdx})"
                                        style="background: #0066cc; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 3px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s; white-space: nowrap;">
                                    + Agregar
                                </button>
                            </div>
                        </td>
                        <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0;">
                            <textarea 
                                   data-field="ubicacion_obs"
                                   data-idx="${ubIdx}"
                                   style="width: 100%; padding: 0.4rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.85rem; min-height: 40px; resize: vertical; font-family: inherit;" 
                                   placeholder="Agregar observaciones...">${ubicacion.observaciones || ''}</textarea>
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <button type="button" 
                                    onclick="eliminarUbicacionDeBordado(${ubIdx})"
                                    style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;">
                                ✕ Eliminar
                            </button>
                        </td>
                    </tr>`;
                });
                
                html += `</tbody>
                    </table>
                </div>`;
            }
            
            // ========== FOTOS DEL LOGO ==========
            if (logoCotizacion.fotos && logoCotizacion.fotos.length > 0) {
                html += `<div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #333; font-size: 0.95rem;">Fotos del Bordado:</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;">`;
                logoCotizacion.fotos.forEach(logo => {
                    const logoUrl = logo.url || logo.ruta_webp || logo.ruta_original;
                    if (logoUrl) {
                        html += `<img src="${logoUrl}" alt="Logo" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 1px solid #d0d0d0; transition: transform 0.2s;" onclick="abrirModalImagen('${logoUrl}', 'Logo de cotización')">`;
                    }
                });
                html += `</div></div>`;
            }
            
            html += '</div>';
        }
        
        prendasContainer.innerHTML = html;
        
        console.log('Prendas y logo renderizados con información completa');
    }

    // ============================================================
    // FUNCIONES DE MANIPULACIÓN DE PRENDAS
    // ============================================================
    
    window.eliminarPrendaDelPedido = function(index) {
        if (confirm('¿Estás seguro de que quieres eliminar esta prenda del pedido?')) {
            prendasEliminadas.add(index);
            console.log('Prenda eliminada:', index);
            renderizarPrendasEditables(prendasCargadas);
            
            // Mostrar notificación
            Swal.fire({
                icon: 'success',
                title: 'Prenda eliminada',
                text: 'La prenda ha sido eliminada del pedido',
                timer: 2000,
                showConfirmButton: false
            });
        }
    };

    window.eliminarVariacionDePrenda = function(prendaIndex, variacionIndex) {
        if (confirm('¿Estás seguro de que quieres eliminar esta variación?')) {
            // Encontrar la fila de variación y eliminarla
            const filaVariacion = document.querySelector(`tr[data-variacion="${variacionIndex}"][data-prenda="${prendaIndex}"]`);
            if (filaVariacion) {
                filaVariacion.remove();
                console.log(`Variación ${variacionIndex} eliminada de prenda ${prendaIndex}`);
                
                // Mostrar notificación
                Swal.fire({
                    icon: 'success',
                    title: 'Variación eliminada',
                    text: 'La variación ha sido eliminada',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    };

    window.eliminarTecnicaDeBordado = function(tecnicaIndex) {
        if (confirm('¿Estás seguro de que quieres eliminar esta técnica?')) {
            const filaTecnica = document.querySelector(`tr[data-tecnica="${tecnicaIndex}"]`);
            if (filaTecnica) {
                filaTecnica.remove();
                console.log(`Técnica ${tecnicaIndex} eliminada`);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Técnica eliminada',
                    text: 'La técnica ha sido eliminada',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    };

    window.eliminarUbicacionDeBordado = function(ubicacionIndex) {
        if (confirm('¿Estás seguro de que quieres eliminar esta ubicación?')) {
            const filaUbicacion = document.querySelector(`tr[data-ubicacion="${ubicacionIndex}"]`);
            if (filaUbicacion) {
                filaUbicacion.remove();
                console.log(`Ubicación ${ubicacionIndex} eliminada`);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Ubicación eliminada',
                    text: 'La ubicación ha sido eliminada',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    };

    window.eliminarUbicacionItem = function(ubicacionIndex, itemIndex) {
        if (confirm('¿Estás seguro de que quieres eliminar esta ubicación seleccionada?')) {
            const filaUbicacion = document.querySelector(`tr[data-ubicacion="${ubicacionIndex}"]`);
            if (filaUbicacion) {
                const spans = filaUbicacion.querySelectorAll('span');
                if (spans[itemIndex]) {
                    spans[itemIndex].remove();
                    console.log(`Ubicación seleccionada ${itemIndex} removida de ubicación ${ubicacionIndex}`);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Ubicación removida',
                        text: 'La ubicación seleccionada ha sido removida',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            }
        }
    };

    window.agregarUbicacionNueva = function(ubicacionIndex) {
        const input = document.querySelector(`input[data-field="ubicacion_nueva"][data-idx="${ubicacionIndex}"]`);
        if (input && input.value.trim()) {
            const filaUbicacion = document.querySelector(`tr[data-ubicacion="${ubicacionIndex}"]`);
            if (filaUbicacion) {
                const divUbicaciones = filaUbicacion.querySelector('div[style*="display: flex"]');
                if (divUbicaciones) {
                    const newBadge = document.createElement('span');
                    newBadge.style.cssText = 'display: inline-flex; align-items: center; gap: 0.3rem; background: #e3f2fd; color: #1976d2; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem;';
                    newBadge.innerHTML = `
                        ${input.value.trim()}
                        <button type="button" 
                                onclick="eliminarUbicacionItem(${ubicacionIndex}, this.closest('span').previousElementSibling ? Array.from(this.closest('span').parentElement.querySelectorAll('span')).indexOf(this.closest('span')) : 0)"
                                style="background: none; border: none; color: #1976d2; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>
                    `;
                    divUbicaciones.appendChild(newBadge);
                    input.value = '';
                    console.log(`Ubicación "${input.value}" agregada`);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Ubicación agregada',
                        text: 'La nueva ubicación ha sido agregada',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            }
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Campo vacío',
                text: 'Por favor ingresa una ubicación antes de agregar',
                timer: 1500,
                showConfirmButton: false
            });
        }
    };

    window.quitarTallaDelFormulario = function(prendaIndex, talla) {
        if (confirm(`¿Quitar la talla ${talla} de la prenda ${prendaIndex + 1}?`)) {
            // Buscar el input de esa talla y eliminarlo
            const input = document.querySelector(`input[name="cantidades[${prendaIndex}][${talla}]"]`);
            if (input) {
                const tallaItem = input.closest('.talla-item');
                if (tallaItem) {
                    tallaItem.remove();
                    console.log(`Talla ${talla} removida de prenda ${prendaIndex}`);
                }
            }
        }
    };

    // ============================================================
    // ENVÍO DEL FORMULARIO
    // ============================================================
    
    formCrearPedido.addEventListener('submit', function(e) {
        e.preventDefault();

        const cotizacionId = document.getElementById('cotizacion_id_editable').value;
        
        if (!cotizacionId) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona una cotización',
                text: 'Por favor selecciona una cotización antes de continuar',
                confirmButtonText: 'OK'
            });
            return;
        }

        const prendas = [];
        
        prendasCargadas.forEach((prenda, index) => {
            // Saltar prendas eliminadas
            if (prendasEliminadas.has(index)) {
                console.log(`Saltando prenda eliminada: ${index}`);
                return;
            }

            const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${index}"]`);
            if (!prendasCard) return;
            
            // Obtener valores editados
            const nombreProducto = prendasCard.querySelector(`.prenda-nombre`)?.value || prenda.nombre_producto;
            const descripcion = prendasCard.querySelector(`.prenda-descripcion`)?.value || prenda.descripcion;
            
            // Obtener cantidades por talla
            const cantidadesPorTalla = {};
            const tallaInputs = prendasCard.querySelectorAll('.talla-cantidad');
            tallaInputs.forEach(input => {
                const cantidad = parseInt(input.value) || 0;
                const talla = input.getAttribute('data-talla');
                if (cantidad > 0) {
                    cantidadesPorTalla[talla] = cantidad;
                }
            });

            // Si no hay cantidades, omitir la prenda
            if (Object.keys(cantidadesPorTalla).length === 0) {
                console.log(`Omitiendo prenda sin cantidades: ${index}`);
                return;
            }

            // Obtener géneros seleccionados
            const generosSeleccionados = [];
            const generosCheckboxes = prendasCard.querySelectorAll('.genero-checkbox:checked');
            generosCheckboxes.forEach(checkbox => {
                generosSeleccionados.push(checkbox.value);
            });

            prendas.push({
                index: index,
                nombre_producto: nombreProducto,
                descripcion: descripcion,
                genero: generosSeleccionados.length > 0 ? generosSeleccionados : prenda.variantes?.genero,
                manga: prenda.variantes?.manga,
                broche: prenda.variantes?.broche,
                tiene_bolsillos: prenda.variantes?.tiene_bolsillos,
                tiene_reflectivo: prenda.variantes?.tiene_reflectivo,
                manga_obs: prenda.variantes?.manga_obs,
                bolsillos_obs: prenda.variantes?.bolsillos_obs,
                broche_obs: prenda.variantes?.broche_obs,
                reflectivo_obs: prenda.variantes?.reflectivo_obs,
                observaciones: prenda.variantes?.observaciones,
                cantidades: cantidadesPorTalla,
                fotos: prenda.fotos || [],
                telas: prenda.telaFotos || prenda.telas || [],
                logos: prenda.logos || []
            });
        });

        if (prendas.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Sin prendas con cantidades',
                text: 'Debes agregar cantidades a al menos una prenda',
                confirmButtonText: 'OK'
            });
            return;
        }

        console.log('Prendas a enviar:', prendas);

        // Enviar al servidor
        fetch(`{{ route('asesores.pedidos-produccion.crear-desde-cotizacion', ['cotizacionId' => ':cotizacionId']) }}`.replace(':cotizacionId', cotizacionId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                cotizacion_id: cotizacionId,
                forma_de_pago: formaPagoInput.value,
                prendas: prendas
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta del servidor:', data);
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Pedido de producción creado exitosamente',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.href = data.redirect;
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Error al crear el pedido',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('❌ Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al enviar el formulario: ' + error.message,
                confirmButtonText: 'OK'
            });
        });
    });

    console.log('Script de formulario editable cargado correctamente');
});
