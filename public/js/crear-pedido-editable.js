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
                // Obtener el objeto foto completo del array
                const fotoObjPrincipal = prenda.fotos && prenda.fotos[0] ? prenda.fotos[0] : { url: fotoPrincipal };
                const fotoURLEncoded = encodeURIComponent(JSON.stringify(fotoObjPrincipal));
                
                fotosHtml += `
                    <div style="width: 100%; display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="position: relative; display: inline-block;">
                            <img src="${fotoPrincipal}" alt="${prenda.nombre_producto}" 
                                 class="prenda-foto-principal" 
                                 data-foto-url="${fotoURLEncoded}"
                                 data-prenda-index="${index}"
                                 style="width: 100%; height: 200px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 1px solid #d0d0d0; transition: all 0.2s;"
                                 onclick="abrirModalImagen('${fotoPrincipal}', '${prenda.nombre_producto}')">
                            <button type="button"
                                    onclick="eliminarImagenPrenda(this)"
                                    style="position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" title="Eliminar imagen">×</button>
                        </div>
                `;
                if (fotosAdicionales.length > 0) {
                    fotosHtml += '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(70px, 1fr)); gap: 0.4rem;">';
                    fotosAdicionales.forEach((foto, idx) => {
                        // Obtener el objeto foto completo
                        const fotoObj = prenda.fotos && prenda.fotos[idx + 1] ? prenda.fotos[idx + 1] : { url: foto };
                        const fotoURLEncoded2 = encodeURIComponent(JSON.stringify(fotoObj));
                        
                        fotosHtml += `
                            <div style="position: relative; display: inline-block; width: 100%;">
                                <img src="${foto}" alt="Foto adicional prenda" 
                                     data-foto-url="${fotoURLEncoded2}"
                                     data-prenda-index="${index}"
                                     style="width: 100%; height: 100px; object-fit: cover; cursor: pointer; border: 1px solid #d0d0d0; border-radius: 4px; transition: all 0.2s;"
                                     onclick="abrirModalImagen('${foto}', '${prenda.nombre_producto}')">
                                <button type="button"
                                        onclick="eliminarImagenPrenda(this)"
                                        style="position: absolute; top: 2px; right: 2px; background: #dc3545; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" title="Eliminar imagen">×</button>
                            </div>
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
                telaFotos.forEach((telaFoto, idx) => {
                    const fotoUrl = telaFoto.url || telaFoto.ruta_webp || telaFoto.ruta_original;
                    if (fotoUrl) {
                        const fotoURLEncoded = encodeURIComponent(JSON.stringify(telaFoto));
                        
                        fotosTelasHtml += `
                            <div style="position: relative; display: inline-block; width: 100%;">
                                <img src="${fotoUrl}" alt="Foto de tela" 
                                     data-tela-foto-url="${fotoURLEncoded}"
                                     data-prenda-index="${index}"
                                     style="width: 100%; height: 120px; object-fit: cover; cursor: pointer; border: 1px solid #d0d0d0; border-radius: 4px; transition: all 0.2s;"
                                     onclick="abrirModalImagen('${fotoUrl}', 'Foto de tela')">
                                <button type="button"
                                        onclick="eliminarImagenTela(this)"
                                        style="position: absolute; top: 2px; right: 2px; background: #dc3545; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" title="Eliminar imagen">×</button>
                            </div>
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
                        const logoURLEncoded = encodeURIComponent(JSON.stringify(logo));
                        html += `<div style="position: relative; display: inline-block; width: 100%;">
                            <img src="${logoUrl}" 
                                 alt="Logo" 
                                 data-logo-url="${logoURLEncoded}"
                                 style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 1px solid #d0d0d0; transition: transform 0.2s;" 
                                 onclick="abrirModalImagen('${logoUrl}', 'Logo de cotización')">
                            <button type="button"
                                    onclick="eliminarImagenLogo(this)"
                                    style="position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" title="Eliminar imagen">×</button>
                        </div>`;
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
        Swal.fire({
            title: 'Eliminar prenda',
            text: '¿Estás seguro de que quieres eliminar esta prenda del pedido?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
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
        });
    };

    window.eliminarVariacionDePrenda = function(prendaIndex, variacionIndex) {
        Swal.fire({
            title: 'Eliminar variación',
            text: '¿Estás seguro de que quieres eliminar esta variación?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
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
        });
    };

    window.eliminarTecnicaDeBordado = function(tecnicaIndex) {
        Swal.fire({
            title: 'Eliminar técnica',
            text: '¿Estás seguro de que quieres eliminar esta técnica?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
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
        });
    };

    window.eliminarUbicacionDeBordado = function(ubicacionIndex) {
        Swal.fire({
            title: 'Eliminar ubicación',
            text: '¿Estás seguro de que quieres eliminar esta ubicación?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
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
        });
    };

    window.eliminarUbicacionItem = function(ubicacionIndex, itemIndex) {
        Swal.fire({
            title: 'Eliminar ubicación',
            text: '¿Estás seguro de que quieres eliminar esta ubicación seleccionada?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
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
        });
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
        Swal.fire({
            title: 'Eliminar talla',
            text: `¿Quitar la talla ${talla} de la prenda ${prendaIndex + 1}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
            // Buscar el input de esa talla y eliminarlo
            const input = document.querySelector(`input[name="cantidades[${prendaIndex}][${talla}]"]`);
            if (input) {
                const tallaItem = input.closest('.talla-item');
                if (tallaItem) {
                    tallaItem.remove();
                    console.log(`Talla ${talla} removida de prenda ${prendaIndex}`);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Talla eliminada',
                        text: `La talla ${talla} ha sido removida`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            }
        }
        });
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
        
        // Recopilar fotos de logo que quedan en el DOM (las no eliminadas)
        const fotosLogoGlobales = [];
        const imagenesLogoDOM = document.querySelectorAll('img[data-logo-url]');
        imagenesLogoDOM.forEach(img => {
            const logoJSON = img.getAttribute('data-logo-url');
            if (logoJSON) {
                try {
                    const logo = JSON.parse(decodeURIComponent(logoJSON));
                    fotosLogoGlobales.push(logo);
                } catch (e) {
                    console.error('Error parseando logo:', e);
                }
            }
        });
        
        console.log('📸 Fotos de logo globales encontradas:', fotosLogoGlobales.length);
        
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

            // Recopilar TODOS los datos editados de variaciones
            const variacionesEditadas = {};
            const inputsVariaciones = prendasCard.querySelectorAll('[data-field]');
            inputsVariaciones.forEach(input => {
                const field = input.getAttribute('data-field');
                const value = input.value || '';
                if (field && value) {
                    variacionesEditadas[field] = value;
                }
            });

            // Recopilar telas/colores editadas
            const telasEditadas = [];
            const telaCards = prendasCard.querySelectorAll('[data-prenda="' + index + '"]');
            telaCards.forEach(card => {
                const telaNombre = card.querySelector('[data-field="tela_nombre"]')?.value;
                const telaColor = card.querySelector('[data-field="tela_color"]')?.value;
                const telaRef = card.querySelector('[data-field="tela_ref"]')?.value;
                
                if (telaNombre || telaColor || telaRef) {
                    telasEditadas.push({
                        tela: telaNombre || prenda.tela,
                        color: telaColor || prenda.color,
                        referencia: telaRef || ''
                    });
                }
            });

            // Obtener géneros seleccionados
            const generosSeleccionados = [];
            const generosCheckboxes = prendasCard.querySelectorAll('.genero-checkbox:checked');
            generosCheckboxes.forEach(checkbox => {
                generosSeleccionados.push(checkbox.value);
            });

            // ✅ RECOPILAR FOTOS QUE QUEDAN EN EL DOM (las no eliminadas por el usuario)
            const fotosEnDOM = [];
            const imagenesPrendaDOM = prendasCard.querySelectorAll('img[data-foto-url][data-prenda-index="' + index + '"]');
            imagenesPrendaDOM.forEach(img => {
                // Leer la foto completa del atributo data-foto-url (no usar índice)
                const fotoJSON = img.getAttribute('data-foto-url');
                if (fotoJSON) {
                    try {
                        const foto = JSON.parse(decodeURIComponent(fotoJSON));
                        fotosEnDOM.push(foto);
                    } catch (e) {
                        console.error('Error parseando foto:', e);
                    }
                }
            });

            // ✅ RECOPILAR FOTOS DE TELAS QUE QUEDAN EN EL DOM
            const fotosTelaEnDOM = [];
            const imagenesTelaDOM = prendasCard.querySelectorAll('img[data-tela-foto-url][data-prenda-index="' + index + '"]');
            imagenesTelaDOM.forEach(img => {
                // Leer la foto completa del atributo data-tela-foto-url
                const fotoJSON = img.getAttribute('data-tela-foto-url');
                if (fotoJSON) {
                    try {
                        const foto = JSON.parse(decodeURIComponent(fotoJSON));
                        fotosTelaEnDOM.push(foto);
                    } catch (e) {
                        console.error('Error parseando foto de tela:', e);
                    }
                }
            });

            console.log(`Prenda ${index}: Fotos restantes: ${fotosEnDOM.length}, Fotos tela: ${fotosTelaEnDOM.length}`);
            console.log(`Prenda ${index}: Fotos tela originales: ${prenda.telaFotos?.length || 0}, Fotos tela restantes: ${fotosTelaEnDOM.length}`);

            prendas.push({
                index: index,
                nombre_producto: nombreProducto,
                descripcion: descripcion,
                genero: generosSeleccionados.length > 0 ? generosSeleccionados : prenda.variantes?.genero,
                manga: variacionesEditadas['tipo_manga'] || prenda.variantes?.tipo_manga || prenda.variantes?.manga,
                broche: variacionesEditadas['tipo_broche'] || prenda.variantes?.tipo_broche || prenda.variantes?.broche,
                tiene_bolsillos: variacionesEditadas['tiene_bolsillos'] === 'Sí' ? true : (prenda.variantes?.tiene_bolsillos || false),
                tiene_reflectivo: variacionesEditadas['tiene_reflectivo'] === 'Sí' ? true : (prenda.variantes?.tiene_reflectivo || false),
                manga_obs: variacionesEditadas['tipo_manga_obs'] || prenda.variantes?.obs_manga || '',
                bolsillos_obs: variacionesEditadas['tiene_bolsillos_obs'] || prenda.variantes?.obs_bolsillos || '',
                broche_obs: variacionesEditadas['tipo_broche_obs'] || prenda.variantes?.obs_broche || '',
                reflectivo_obs: variacionesEditadas['tiene_reflectivo_obs'] || prenda.variantes?.obs_reflectivo || '',
                observaciones: prenda.variantes?.observaciones,
                telas_multiples: telasEditadas.length > 0 ? telasEditadas : prenda.telas_multiples,
                cantidades: cantidadesPorTalla,
                fotos: fotosEnDOM.length > 0 ? fotosEnDOM : prenda.fotos || [],
                telas: fotosTelaEnDOM.length > 0 ? fotosTelaEnDOM : (prenda.telaFotos || prenda.telas || []),
                logos: fotosLogoGlobales.length > 0 ? fotosLogoGlobales : (prenda.logos || [])
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
        const url = `/asesores/pedidos-produccion/crear-desde-cotizacion/${cotizacionId}`;
        console.log('📤 URL completa:', url);
        console.log('📤 cotizacionId:', cotizacionId);
        
        fetch(url, {
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
                    // Redirigir a la lista de pedidos
                    window.location.href = '/asesores/pedidos';
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

    /**
     * Actualizar resumen de una prenda (tallas y fotos)
     * DESHABILITADO: El resumen fue removido de la interfaz
     */
    window.actualizarResumenPrenda = function(prendasContainer) {
        // Función disponible pero inactiva
        console.log('actualizarResumenPrenda: Resumen removido de la interfaz');
    };
});

// ============================================================
// FUNCIONES GLOBALES PARA ELIMINAR IMÁGENES
// ============================================================

window.eliminarImagenPrenda = function(button) {
    Swal.fire({
        title: 'Eliminar imagen',
        text: '¿Estás seguro de que quieres eliminar esta imagen? No se guardará en el pedido.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const contenedor = button.closest('div[style*="position: relative"]');
            if (contenedor) {
                contenedor.remove();
                console.log('Imagen de prenda marcada para no guardar');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Imagen eliminada',
                    text: 'La imagen no se incluirá en el pedido',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    });
};

window.eliminarImagenTela = function(button) {
    Swal.fire({
        title: 'Eliminar imagen',
        text: '¿Estás seguro de que quieres eliminar esta imagen? No se guardará en el pedido.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const contenedor = button.closest('div[style*="position: relative"]');
            if (contenedor) {
                contenedor.remove();
                console.log('Imagen de tela marcada para no guardar');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Imagen eliminada',
                    text: 'La imagen no se incluirá en el pedido',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    });
};

window.eliminarImagenLogo = function(button) {
    Swal.fire({
        title: 'Eliminar imagen',
        text: '¿Estás seguro de que quieres eliminar esta imagen? No se guardará en el pedido.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const contenedor = button.closest('div[style*="position: relative"]');
            if (contenedor) {
                contenedor.remove();
                console.log('Imagen de logo marcada para no guardar');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Imagen eliminada',
                    text: 'La imagen no se incluirá en el pedido',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    });
};
