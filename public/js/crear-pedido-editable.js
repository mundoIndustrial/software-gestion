// Crear Pedido - Script EDITABLE con soporte para edición y eliminación de prendas

/**
 * FUNCIÓN: Abre una imagen en modal para ampliarla
 * @param {string} url - URL de la imagen a ampliar
 * @param {string} titulo - Título del modal (nombre de la foto)
 */
window.abrirModalImagen = function(url, titulo = 'Imagen') {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.95);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        cursor: pointer;
        padding: 2rem;
    `;
    
    const container = document.createElement('div');
    container.style.cssText = `
        position: relative;
        max-width: 90%;
        max-height: 90%;
        display: flex;
        flex-direction: column;
        align-items: center;
    `;
    
    const img = document.createElement('img');
    img.src = url;
    img.alt = titulo;
    img.style.cssText = `
        max-width: 100%;
        max-height: 80vh;
        object-fit: contain;
        border-radius: 8px;
        box-shadow: 0 0 30px rgba(255,255,255,0.3);
    `;
    
    const title = document.createElement('div');
    title.style.cssText = `
        color: white;
        text-align: center;
        margin-top: 1rem;
        font-size: 1.1rem;
        font-weight: 500;
    `;
    title.textContent = titulo;
    
    const closeBtn = document.createElement('button');
    closeBtn.textContent = '✕ Cerrar';
    closeBtn.style.cssText = `
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: rgba(255,255,255,0.2);
        color: white;
        border: 1px solid white;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background 0.2s;
    `;
    closeBtn.onmouseover = () => closeBtn.style.background = 'rgba(255,255,255,0.4)';
    closeBtn.onmouseout = () => closeBtn.style.background = 'rgba(255,255,255,0.2)';
    closeBtn.onclick = (e) => {
        e.stopPropagation();
        modal.remove();
    };
    
    container.appendChild(img);
    container.appendChild(title);
    modal.appendChild(container);
    modal.appendChild(closeBtn);
    
    modal.onclick = () => modal.remove();
    img.onclick = (e) => e.stopPropagation();
    
    document.body.appendChild(modal);
};

/**
 * FUNCIÓN HELPER: Procesa imágenes restantes después de eliminar una
 * Actualiza los índices y asegura que todos los datos sean consistentes
 * 
 * @param {number|null} prendaIndex - Índice de la prenda (null si es logo global)
 * @param {string} tipo - Tipo de imagen: 'prenda', 'tela', 'logo' o 'reflectivo'
 */
function procesarImagenesRestantes(prendaIndex, tipo = 'prenda') {
    if (prendaIndex === null || prendaIndex === undefined) {
        // Procesamiento para imágenes globales (logo, reflectivo)
        console.log(`🔄 Procesando imágenes restantes de ${tipo}...`);
        
        if (tipo === 'logo') {
            const imagenesLogo = document.querySelectorAll('img[data-logo-url]');
            console.log(`   📸 Imágenes de logo restantes: ${imagenesLogo.length}`);
            imagenesLogo.forEach((img, idx) => {
                console.log(`     - Logo ${idx + 1} será incluido`);
            });
        } else if (tipo === 'reflectivo') {
            const imagenesReflectivo = document.querySelectorAll('.reflectivo-foto-item');
            console.log(`   📸 Imágenes de reflectivo restantes: ${imagenesReflectivo.length}`);
            imagenesReflectivo.forEach((item, idx) => {
                const fotoId = item.getAttribute('data-foto-id');
                console.log(`     - Reflectivo ID ${fotoId} será incluido`);
            });
        }
    } else {
        // Procesamiento para imágenes de prenda específica
        const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
        
        if (prendasCard) {
            if (tipo === 'prenda') {
                const imagenesPrenda = prendasCard.querySelectorAll('img[data-foto-url]');
                console.log(`🔄 Procesando imágenes restantes de prenda ${prendaIndex + 1}`);
                console.log(`   📸 Imágenes de prenda restantes: ${imagenesPrenda.length}`);
                imagenesPrenda.forEach((img, idx) => {
                    console.log(`     - Foto ${idx + 1} de prenda será incluida`);
                });
            } else if (tipo === 'tela') {
                const imagenesTela = prendasCard.querySelectorAll('img[data-tela-foto-url]');
                console.log(`🔄 Procesando imágenes restantes de telas para prenda ${prendaIndex + 1}`);
                console.log(`   📸 Imágenes de tela restantes: ${imagenesTela.length}`);
                imagenesTela.forEach((img, idx) => {
                    console.log(`     - Foto de tela ${idx + 1} será incluida`);
                });
            }
        }
    }
    
    console.log(`✅ Procesamiento completado. Las imágenes restantes están listas para ser enviadas al servidor.`);
}

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
        
        // Mostrar/ocultar tabs según tipo de cotización
        mostrarOcultarTabs(id);
    };

    // ============================================================
    // MOSTRAR/OCULTAR TABS SEGÚN TIPO DE COTIZACIÓN
    // ============================================================
    
    function mostrarOcultarTabs(cotizacionId) {
        const cotizacion = misCotizaciones.find(c => c.id === cotizacionId);
        const tabsContainer = document.getElementById('tabs-pedido-container');
        const tabLogoBtn = document.getElementById('tab-logo-btn');
        const logoFormContainer = document.getElementById('logo-form-container');
        
        if (!cotizacion || !tabsContainer) return;

        // Mostrar tabs container
        tabsContainer.style.display = 'flex';

        // Mostrar tab de logo solo si es cotización combinada (PL) o logo puro (L)
        const esCombinada = cotizacion.tipo_cotizacion_codigo === 'PL';
        const esLogo = cotizacion.tipo_cotizacion_codigo === 'L';
        
        if (esCombinada) {
            // Mostrar ambos tabs y formulario de logo
            if (tabLogoBtn) {
                tabLogoBtn.style.display = 'flex';
            }
            if (logoFormContainer) {
                logoFormContainer.style.display = 'block';
            }
        } else if (esLogo) {
            // Mostrar solo tab de logo y formulario
            if (tabLogoBtn) {
                tabLogoBtn.style.display = 'flex';
            }
            if (logoFormContainer) {
                logoFormContainer.style.display = 'block';
            }
            // Cambiar a tab logo automáticamente
            document.getElementById('tab-logo-btn').click();
        } else {
            // Ocultar tab de logo (solo prendas)
            if (tabLogoBtn) {
                tabLogoBtn.style.display = 'none';
            }
            if (logoFormContainer) {
                logoFormContainer.style.display = 'none';
            }
            // Asegurar que el tab de prendas esté activo
            cambiarTabPedido('prendas');
        }
    }

    // Hacer la función global accesible desde HTML
    window.mostrarOcultarTabs = mostrarOcultarTabs;

    // ============================================================
    // CAMBIAR TAB - Función para manejar cambio de tabs
    // ============================================================
    
    window.cambiarTabPedido = function(tab, event) {
        if (event) {
            event.preventDefault();
        }

        // Remover clase active de todos los botones
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });

        // Remover clase active de todos los contenidos
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // Agregar clase active al botón actual
        if (event && event.target) {
            const btn = event.target.closest('.tab-button');
            if (btn) {
                btn.classList.add('active');
            }
        } else {
            // Si no hay event (llamada desde JS), buscar el botón por tab
            const btnPorTab = document.querySelector(`.tab-button[onclick*="'${tab}'"]`);
            if (btnPorTab) {
                btnPorTab.classList.add('active');
            }
        }

        // Mostrar el tab correspondiente
        const tabContent = document.getElementById('tab-' + tab);
        if (tabContent) {
            tabContent.classList.add('active');
        }
    };

    function cargarPrendasDesdeCotizacion(cotizacionId) {
        console.log('📥 Cargando prendas de cotización:', cotizacionId);
        fetch(`/asesores/pedidos-produccion/obtener-datos-cotizacion/${cotizacionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    prendasContainer.innerHTML = `<p style="color: #ef4444;">Error: ${data.error}</p>`;
                } else {
                    console.log('Datos de cotización obtenidos:', data);
                    prendasCargadas = data.prendas || [];
                    prendasEliminadas.clear(); // Limpiar eliminadas
                    
                    // Actualizar forma de pago con los datos completos del servidor
                    if (data.forma_pago) {
                        formaPagoInput.value = data.forma_pago;
                        console.log('✅ Forma de pago actualizada:', data.forma_pago);
                    }
                    
                    // Mostrar información de logos si existe
                    if (data.logo && data.logo.fotos && data.logo.fotos.length > 0) {
                        console.log('Logos encontrados:', data.logo.fotos.length);
                    }
                    
                    // Mostrar especificaciones generales
                    if (data.especificaciones) {
                        console.log('📋 Especificaciones de cotización:', data.especificaciones);
                        console.log('📋 Tipo de especificaciones:', typeof data.especificaciones);
                        console.log('📋 Es array?:', Array.isArray(data.especificaciones));
                    } else {
                        console.log('⚠️ No hay especificaciones en data');
                    }
                    
                    // Pasar tipo de cotización para renderizado diferente
                    const tipoCotizacion = data.tipo_cotizacion_codigo || 'PL';
                    const esReflectivo = tipoCotizacion === 'RF';
                    const esLogo = tipoCotizacion === 'L';
                    
                    console.log('🎯 Tipo de cotización:', tipoCotizacion);
                    console.log('📦 ¿Es Reflectivo?:', esReflectivo);
                    console.log('🎨 ¿Es Logo?:', esLogo);
                    console.log('📊 Data Logo:', data.logo);
                    
                    // GUARDAR ID DEL LOGO COTIZACION para usar después
                    if (esLogo && data.logo) {
                        logoCotizacionId = data.logo.id;
                        console.log('🎨 LogoCotizacion ID guardado:', logoCotizacionId);
                    }
                    
                    // Cambiar título y alerta dinámicamente
                    const paso3Titulo = document.getElementById('paso3_titulo_logo');
                    const paso3Alerta = document.getElementById('paso3_alerta_logo');
                    
                    console.log('📌 paso3Titulo element:', paso3Titulo);
                    console.log('📌 paso3Alerta element:', paso3Alerta);
                    
                    if (paso3Titulo && paso3Alerta) {
                        if (esLogo) {
                            // Actualizar solo el texto del título
                            paso3Titulo.textContent = 'Pedido de Logo';
                            paso3Alerta.innerHTML = 'ℹ️ Completa la información del logo: descripción, ubicaciones, técnicas y observaciones.';
                        } else {
                            paso3Titulo.textContent = 'Prendas y Cantidades (Editables)';
                            paso3Alerta.innerHTML = 'ℹ️ Puedes editar los campos de cada prenda, cambiar cantidades por talla, o eliminar prendas que no desees incluir en el pedido.';
                        }
                        console.log('✅ Título y alerta actualizados');
                    } else {
                        console.warn('⚠️ No se encontraron los elementos paso3_titulo_logo o paso3_alerta_logo');
                    }
                    
                    renderizarPrendasEditables(prendasCargadas, data.logo, data.especificaciones, esReflectivo, data.reflectivo, esLogo);
                    
                    // Si es combinada (PL), también renderizar el logo en el tab de logo
                    if ((tipoCotizacion === 'PL' || tipoCotizacion === 'L') && data.logo) {
                        console.log('🎨 RENDERIZANDO INFORMACIÓN DE LOGO EN TAB');
                        renderizarLogoEnTab(data.logo);
                    }
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                prendasContainer.innerHTML = `<p style="color: #ef4444;">Error al cargar las prendas: ${error.message}</p>`;
            });
    // ============================================================
    // RENDERIZAR INFORMACIÓN DE LOGO EN TAB
    // ============================================================
    
    function renderizarPrendasEditables(prendasCargadas, logoCotizacion, especificaciones, esReflectivo, datosReflectivo, esLogo) {
    function renderizarLogoEnTab(logoCotizacion) {
        if (!logoCotizacion) return;
        
        const logoTabContent = document.getElementById('logo-tab-content');
        if (!logoTabContent) {
            console.warn('⚠️ No se encontró elemento #logo-tab-content');
            return;
        }

        console.log('🎨 Renderizando logo en tab:', logoCotizacion);

        let html = `<div class="logo-info-card" style="background: white; border-radius: 12px; padding: 2rem; border: 1px solid #e0e0e0;">`;
        
        // Descripción
        if (logoCotizacion.descripcion) {
            html += `<div class="form-group-editable" style="margin-bottom: 1.5rem;">
                <label style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>📝</span> Descripción del Logo
                </label>
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 6px; border-left: 3px solid #2196F3;">
                    <p style="margin: 0; color: #333; line-height: 1.5; white-space: pre-wrap;">${logoCotizacion.descripcion}</p>
                </div>
            </div>`;
        }

        // Técnicas
        let tecnicas = [];
        if (logoCotizacion.tecnicas) {
            if (Array.isArray(logoCotizacion.tecnicas)) {
                tecnicas = logoCotizacion.tecnicas;
            } else if (typeof logoCotizacion.tecnicas === 'string') {
                try {
                    tecnicas = JSON.parse(logoCotizacion.tecnicas);
                    if (!Array.isArray(tecnicas)) tecnicas = [tecnicas];
                } catch (e) {
                    tecnicas = logoCotizacion.tecnicas.split(',').map(t => t.trim());
                }
            }
        }

        if (tecnicas.length > 0) {
            html += `<div class="form-group-editable" style="margin-bottom: 1.5rem;">
                <label style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>🎯</span> Técnicas
                </label>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">`;
            
            const colores = {
                'BORDADO': '#4CAF50',
                'DTF': '#2196F3',
                'ESTAMPADO': '#FF9800',
                'SUBLIMADO': '#9C27B0'
            };

            tecnicas.forEach(tecnica => {
                const tecnicaText = typeof tecnica === 'object' ? (tecnica.nombre || tecnica) : tecnica;
                const color = colores[tecnicaText] || '#666';
                html += `<span style="background: ${color}; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; font-weight: 500;">
                    ${tecnicaText}
                </span>`;
            });

            html += `</div></div>`;
        }

        // Ubicaciones
        let ubicaciones = [];
        if (logoCotizacion.ubicaciones) {
            if (Array.isArray(logoCotizacion.ubicaciones)) {
                ubicaciones = logoCotizacion.ubicaciones;
            } else if (typeof logoCotizacion.ubicaciones === 'string') {
                try {
                    ubicaciones = JSON.parse(logoCotizacion.ubicaciones);
                    if (!Array.isArray(ubicaciones)) ubicaciones = [ubicaciones];
                } catch (e) {
                    console.warn('Error parseando ubicaciones:', e);
                    ubicaciones = [];
                }
            }
        }

        if (ubicaciones.length > 0) {
            html += `<div class="form-group-editable" style="margin-bottom: 1.5rem;">
                <label style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>📍</span> Ubicaciones
                </label>
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 6px;">`;
            
            ubicaciones.forEach((ub, idx) => {
                const ubicacionText = typeof ub === 'object' ? (ub.ubicacion || ub) : ub;
                const opciones = typeof ub === 'object' && ub.opciones ? ub.opciones : [];
                
                html += `<div style="margin-bottom: ${idx < ubicaciones.length - 1 ? '1rem' : '0'}; padding-bottom: ${idx < ubicaciones.length - 1 ? '1rem' : '0'}; border-bottom: ${idx < ubicaciones.length - 1 ? '1px solid #e0e0e0' : 'none'};">
                    <p style="margin: 0 0 0.5rem 0; color: #333; font-weight: 500;">• ${ubicacionText}</p>`;
                
                if (Array.isArray(opciones) && opciones.length > 0) {
                    html += `<div style="margin-left: 1.5rem; color: #666; font-size: 0.9rem;">
                        ${opciones.join(', ')}
                    </div>`;
                }
                
                html += `</div>`;
            });

            html += `</div></div>`;
        }

        // Observaciones
        if (logoCotizacion.observaciones_tecnicas) {
            html += `<div class="form-group-editable" style="margin-bottom: 1.5rem;">
                <label style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>📋</span> Observaciones Técnicas
                </label>
                <div style="background: #fffde7; padding: 1rem; border-radius: 6px; border-left: 3px solid #FBC02D;">
                    <p style="margin: 0; color: #333; line-height: 1.5; white-space: pre-wrap;">${logoCotizacion.observaciones_tecnicas}</p>
                </div>
            </div>`;
        }

        // Fotos
        if (logoCotizacion.fotos && Array.isArray(logoCotizacion.fotos) && logoCotizacion.fotos.length > 0) {
            html += `<div class="form-group-editable">
                <label style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>🖼️</span> Galería de Fotos (${logoCotizacion.fotos.length})
                </label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem;">`;

            logoCotizacion.fotos.forEach((foto, idx) => {
                const fotoUrl = typeof foto === 'string' ? foto : (foto.url || foto);
                html += `<div style="position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer;" onclick="abrirModalImagen('${fotoUrl}', 'Foto Logo ${idx + 1}')">
                    <img src="${fotoUrl}" alt="Logo foto ${idx + 1}" style="width: 100%; height: 120px; object-fit: cover; display: block; transition: transform 0.2s;">
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;" class="foto-overlay" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                        <span style="color: white; font-size: 1.5rem;">🔍</span>
                    </div>
                </div>`;
            });

            html += `</div></div>`;
        }

        html += `</div>`;
        logoTabContent.innerHTML = html;
    }

        if (!prendas || prendas.length === 0) {
            // Si no hay prendas pero hay LOGO, mostrar campos LOGO
            if (esLogo && logoCotizacion) {
                console.log('🎨 RENDERIZANDO COTIZACIÓN TIPO LOGO (sin prendas)');
                renderizarCamposLogo(logoCotizacion);
                return;
            }
            prendasContainer.innerHTML = '<p class="text-gray-500 text-center py-8">Esta cotización no tiene prendas</p>';
            return;
        }

        // Si es REFLECTIVO, mostrar información completa y editable
        if (esReflectivo) {
            console.log('📦 RENDERIZANDO COTIZACIÓN TIPO REFLECTIVO');
            console.log('📦 Datos reflectivo:', datosReflectivo);
            
            // Parsear ubicaciones del reflectivo
            let ubicacionesReflectivo = [];
            if (datosReflectivo && datosReflectivo.ubicacion) {
                try {
                    ubicacionesReflectivo = typeof datosReflectivo.ubicacion === 'string' 
                        ? JSON.parse(datosReflectivo.ubicacion) 
                        : datosReflectivo.ubicacion;
                    console.log('📍 Ubicaciones parseadas:', ubicacionesReflectivo);
                } catch (e) {
                    console.error('Error parseando ubicaciones:', e);
                    ubicacionesReflectivo = [];
                }
            }
            
            let html = '';
            
            // Renderizar cada prenda con su información de reflectivo
            prendas.forEach((prenda, index) => {
                console.log(`👕 Prenda ${index + 1}:`, prenda);
                console.log(`   - Tallas:`, prenda.tallas);
                console.log(`   - Tipo de tallas:`, typeof prenda.tallas);
                
                html += `
                <div class="prenda-card-editable reflectivo-card" data-prenda-index="${index}" style="margin-bottom: 2rem; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden;">
                    <div style="background: linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%); padding: 1.25rem; color: white;">
                        <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600;">
                            <i class="fas fa-tshirt" style="margin-right: 0.5rem;"></i>Prenda ${index + 1}
                        </h3>
                    </div>
                    
                    <div style="padding: 1.5rem;">
                        <!-- Tipo de Prenda (Editable) -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e40af;">Tipo de Prenda:</label>
                            <input type="text" 
                                   name="reflectivo_tipo_prenda[${index}]" 
                                   value="${prenda.nombre_producto || ''}"
                                   placeholder="Ej: Camiseta, Pantalón, Chaqueta..."
                                   style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem;">
                        </div>
                        
                        <!-- Descripción del Reflectivo para esta prenda (Editable) -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e40af;">Descripción del Reflectivo:</label>
                            <textarea name="reflectivo_descripcion[${index}]" 
                                      placeholder="Describe el reflectivo para esta prenda (tipo, tamaño, color, ubicación, etc.)..."
                                      style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.95rem; min-height: 100px; font-family: inherit;">${prenda.descripcion || ''}</textarea>
                        </div>
                        
                        <!-- Ubicaciones del Reflectivo (Mostrar las que vienen de la cotización) -->
                        ${ubicacionesReflectivo && ubicacionesReflectivo.length > 0 ? `
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1e40af;">
                                <i class="fas fa-map-marker-alt" style="margin-right: 0.5rem;"></i>Ubicaciones del Reflectivo:
                            </label>
                            <div style="display: grid; gap: 0.75rem;">
                                ${ubicacionesReflectivo.map((ubicacion, ubIdx) => {
                                    const ubicacionNombre = ubicacion.ubicacion || ubicacion;
                                    const ubicacionDesc = ubicacion.descripcion || '';
                                    return `
                                    <div style="border: 1px solid #e2e8f0; border-left: 4px solid #0ea5e9; border-radius: 8px; padding: 1rem; background: #f9fafb;">
                                        <div style="margin-bottom: ${ubicacionDesc ? '0.5rem' : '0'};">
                                            <input type="text" 
                                                   name="reflectivo_ubicaciones[${index}][${ubIdx}][ubicacion]" 
                                                   value="${ubicacionNombre}"
                                                   placeholder="Ubicación (ej: Pecho, Espalda, Mangas...)"
                                                   style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem; font-weight: 600; color: #1e40af;">
                                        </div>
                                        ${ubicacionDesc ? `
                                        <div>
                                            <textarea name="reflectivo_ubicaciones[${index}][${ubIdx}][descripcion]" 
                                                      placeholder="Descripción adicional..."
                                                      style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem; min-height: 60px; font-family: inherit; color: #64748b;">${ubicacionDesc}</textarea>
                                        </div>
                                        ` : ''}
                                    </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- Tallas (Editable con cantidades y botón eliminar) -->
                        ${prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0 ? `
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1e40af;">
                                <i class="fas fa-ruler" style="margin-right: 0.5rem;"></i>Tallas y Cantidades:
                            </label>
                            <div id="tallas-container-${index}" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.75rem;">
                                ${prenda.tallas.map((talla, tallaIdx) => {
                                    console.log(`     Talla ${tallaIdx}:`, talla);
                                    return `
                                    <div class="talla-item-reflectivo" data-talla="${talla}" data-prenda="${index}" style="background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 0.75rem; position: relative;">
                                        <button type="button" 
                                                onclick="eliminarTallaReflectivo(${index}, '${talla}')"
                                                style="position: absolute; top: 4px; right: 4px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; z-index: 10; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"
                                                title="Eliminar talla">
                                            ×
                                        </button>
                                        <label style="display: block; font-weight: 600; color: #1e40af; margin-bottom: 0.4rem; font-size: 0.85rem;">${talla}</label>
                                        <input type="number" 
                                               class="talla-cantidad"
                                               data-talla="${talla}"
                                               name="reflectivo_cantidades[${index}][${talla}]" 
                                               min="0" 
                                               value="0"
                                               placeholder="0"
                                               style="width: 100%; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                        ` : `<p style="color: #94a3b8; font-style: italic; margin-bottom: 1.5rem;">
                            <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>Sin tallas definidas
                            ${prenda.tallas ? ' (tallas: ' + JSON.stringify(prenda.tallas) + ')' : ''}
                        </p>`}
                    </div>
                </div>
                `;
            });
            
            // Imágenes del Reflectivo (generales para toda la cotización)
            if (datosReflectivo && datosReflectivo.fotos && datosReflectivo.fotos.length > 0) {
                console.log('📸 Fotos del reflectivo encontradas:', datosReflectivo.fotos);
                html += `
                <div style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 2rem;">
                    <h4 style="color: #1e40af; font-size: 1.1rem; font-weight: 600; margin: 0 0 1rem 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.75rem;">
                        <i class="fas fa-images" style="margin-right: 0.5rem;"></i>Imágenes del Reflectivo (${datosReflectivo.fotos.length})
                    </h4>
                    <div id="reflectivo-fotos-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem;">
                        ${datosReflectivo.fotos.map((foto, fotoIdx) => {
                            const fotoUrl = foto.url || foto.ruta_webp || '/storage/' + foto.ruta_webp;
                            const fotoId = foto.id || fotoIdx;
                            return `
                            <div class="reflectivo-foto-item" data-foto-id="${fotoId}" style="position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;" 
                                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 6px 16px rgba(0, 0, 0, 0.15)'" 
                                 onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'">
                                <button type="button" 
                                        onclick="eliminarFotoReflectivoPedido(${fotoId})"
                                        style="position: absolute; top: 5px; right: 5px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: bold; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                    ×
                                </button>
                                <img src="${fotoUrl}" 
                                     alt="Reflectivo ${fotoIdx + 1}" 
                                     style="width: 100%; height: 150px; object-fit: cover; cursor: pointer; transition: transform 0.3s ease;"
                                     onmouseover="this.style.transform='scale(1.05)'"
                                     onmouseout="this.style.transform=''"
                                     onclick="abrirModalImagen('${fotoUrl}', 'Reflectivo - Imagen ${fotoIdx + 1}')">
                                <input type="hidden" name="reflectivo_fotos_incluir[]" value="${fotoId}">
                            </div>
                            `;
                        }).join('')}
                    </div>
                </div>
                `;
            } else {
                console.log('⚠️ No hay fotos del reflectivo o datosReflectivo es null');
            }
            
            prendasContainer.innerHTML = html;
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
                    tipo: variantes.tipo_broche,
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
                    let inputHtml = '';
                    if (variacion.esCheckbox) {
                        // Para campos booleanos, mostrar checkbox
                        const isChecked = variacion.valor === true || variacion.valor === 'Sí' || variacion.valor === 1 ? 'checked' : '';
                        inputHtml = `<input type="checkbox" 
                                           ${isChecked}
                                           data-field="${variacion.campo}" 
                                           data-prenda="${index}"
                                           data-variacion="${varIdx}"
                                           style="width: 20px; height: 20px; cursor: pointer; accent-color: #0066cc;">`;
                    } else {
                        // Para campos de texto, mostrar input text
                        inputHtml = `<input type="text" 
                                           value="${variacion.valor}" 
                                           data-field="${variacion.campo}" 
                                           data-prenda="${index}"
                                           data-variacion="${varIdx}"
                                           style="width: 100%; padding: 0.4rem; border: 1px solid #ccc; border-radius: 3px; font-size: 0.85rem;">`;
                    }
                    
                    variacionesHtml += `<tr style="border-bottom: 1px solid #eee;" data-variacion="${varIdx}" data-prenda="${index}">
                        <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0; font-weight: 500;">${variacion.tipo}</td>
                        <td style="padding: 0.75rem; border-right: 1px solid #d0d0d0; text-align: center;">
                            ${inputHtml}
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
// VARIABLES GLOBALES PARA LOGO
// ============================================================

let logoTecnicasSeleccionadas = [];
let logoSeccionesSeleccionadas = [];
let logoFotosSeleccionadas = [];  // Array para guardar fotos editables
let logoObservacionesGenerales = [];  // Observaciones del logo
let logoCotizacionId = null;  // ID del LogoCotizacion para guardar en BD

    // ============================================================
    // RENDERIZAR CAMPOS SOLO PARA LOGO (sin prendas)
    // ============================================================
    
    // Opciones por ubicación (mismas del formulario de bordado)
    const logoOpcionesPorUbicacion = {
        'CAMISA': ['PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO'],
        'JEAN_SUDADERA': ['PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO'],
        'GORRAS': ['FRENTE', 'LATERAL', 'TRASERA']
    };

    function renderizarCamposLogo(logoCotizacion) {
        console.log('🎨 Renderizando campos LOGO únicamente');
        console.log('📦 Datos logo completos:', logoCotizacion);
        
        // Resetear arrays globales
        logoTecnicasSeleccionadas = [];
        logoSeccionesSeleccionadas = [];
        logoObservacionesGenerales = [];
        
        // Función helper para parsear datos JSON si es necesario
        function parseArrayData(data) {
            if (!data) return [];
            if (Array.isArray(data)) return data;
            if (typeof data === 'string') {
                try {
                    return JSON.parse(data);
                } catch (e) {
                    console.warn('⚠️ No se pudo parsear:', data);
                    return [];
                }
            }
            return [];
        }
        
        // Parsear ubicaciones
        let ubicacionesArray = parseArrayData(logoCotizacion.ubicaciones);
        console.log('📍 Ubicaciones parseadas:', ubicacionesArray);
        
        // Cargar técnicas iniciales
        if (logoCotizacion.tecnicas && logoCotizacion.tecnicas.length > 0) {
            logoCotizacion.tecnicas.forEach(tecnica => {
                const tecnicaText = typeof tecnica === 'object' ? tecnica.nombre : tecnica;
                logoTecnicasSeleccionadas.push(tecnicaText);
            });
        }
        
        // Cargar ubicaciones iniciales
        if (ubicacionesArray && ubicacionesArray.length > 0) {
            ubicacionesArray.forEach(ubicacion => {
                if (typeof ubicacion === 'object' && ubicacion.ubicacion) {
                    logoSeccionesSeleccionadas.push({
                        ubicacion: ubicacion.ubicacion,
                        opciones: Array.isArray(ubicacion.opciones) ? ubicacion.opciones : [],
                        observaciones: ubicacion.observaciones || ''
                    });
                }
            });
        }
        
        // Parsear observaciones generales
        let observacionesArray = parseArrayData(logoCotizacion.observaciones_generales);
        console.log('📝 Observaciones parseadas:', observacionesArray);
        
        if (observacionesArray && observacionesArray.length > 0) {
            observacionesArray.forEach(obs => {
                if (typeof obs === 'object') {
                    logoObservacionesGenerales.push(obs.descripcion || obs.texto || obs.nombre || '');
                } else {
                    logoObservacionesGenerales.push(obs);
                }
            });
        }
        
        // Cambiar el título del paso 3
        const paso3Titulo = document.getElementById('paso3_titulo_logo');
        if (paso3Titulo) {
            paso3Titulo.textContent = 'Información del Logo';
        }
        
        let html = '<div style="margin-top: 1rem; padding: 2rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">';
        html += '<h2 style="margin: 0 0 1.5rem 0; font-size: 1.3rem; color: #1f2937; border-bottom: 3px solid #0066cc; padding-bottom: 0.75rem;">📋 Información del Logo</h2>';
        
        // ========== DESCRIPCIÓN (EDITABLE) ==========
        html += `<div style="margin-bottom: 1.5rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">DESCRIPCIÓN</label>
            <textarea id="logo_descripcion" name="logo_descripcion" 
                      style="width: 100%; padding: 0.75rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; font-family: inherit; min-height: 100px; color: #333;">${logoCotizacion.descripcion || ''}</textarea>
        </div>`;
        
        // ========== FOTOS (EDITABLES) ==========
        // Cargar fotos iniciales
        logoFotosSeleccionadas = [];
        if (logoCotizacion.fotos && logoCotizacion.fotos.length > 0) {
            logoCotizacion.fotos.forEach((foto) => {
                const fotoUrl = foto.url || foto.ruta_webp || foto.ruta_original;
                if (fotoUrl) {
                    logoFotosSeleccionadas.push({
                        url: fotoUrl,
                        preview: fotoUrl,
                        existing: true,
                        id: foto.id
                    });
                }
            });
        }
        
        html += `<div style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">IMÁGENES (MÁXIMO 5)</label>
                <button type="button" onclick="abrirModalAgregarFotosLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
            </div>
            <div id="galeria-fotos-logo" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;"></div>
        </div>`;
        
        // ========== TÉCNICAS (EDITABLES) ==========
        html += `<div style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 4px; border-left: 4px solid #0066cc;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">Técnicas disponibles</label>
                <button type="button" class="btn-add" onclick="agregarTecnicaLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
            </div>
            
            <select id="selector_tecnicas_logo" class="input-large" style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                <option value="">-- SELECCIONA UNA TÉCNICA --</option>
                <option value="BORDADO">BORDADO</option>
                <option value="DTF">DTF</option>
                <option value="ESTAMPADO">ESTAMPADO</option>
                <option value="SUBLIMADO">SUBLIMADO</option>
            </select>
            
            <div class="tecnicas-seleccionadas-logo" id="tecnicas_seleccionadas_logo" style="display: flex; flex-wrap: wrap; gap: 0.5rem;"></div>
        </div>`;
        
        // ========== OBSERVACIONES DE TÉCNICAS (EDITABLE) ==========
        html += `<div style="margin-bottom: 1.5rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">Observaciones de Técnicas</label>
            <textarea id="logo_observaciones_tecnicas" name="logo_observaciones_tecnicas" 
                      style="width: 100%; padding: 0.75rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; font-family: inherit; min-height: 80px; color: #333;">${logoCotizacion.observaciones_tecnicas || ''}</textarea>
        </div>`;
        
        // ========== UBICACIÓN (EDITABLE CON MODAL) ==========
        html += `<div style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 4px; border-left: 4px solid #0066cc;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">Ubicación</label>
                <button type="button" class="btn-add" onclick="agregarSeccionLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
            </div>
            
            <label for="seccion_prenda_logo" style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem;">Selecciona la sección a agregar:</label>
            <select id="seccion_prenda_logo" style="width: 100%; margin-bottom: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                <option value="">-- SELECCIONA UNA OPCIÓN --</option>
                <option value="CAMISA">CAMISA</option>
                <option value="JEAN_SUDADERA">JEAN/SUDADERA</option>
                <option value="GORRAS">GORRAS</option>
            </select>
            
            <div id="errorSeccionPrendaLogo" style="display: none; color: #ef4444; font-size: 0.85rem; font-weight: 600; padding: 0.5rem; background: #fee2e2; border-radius: 4px; margin-bottom: 10px;">
                ⚠️ Debes seleccionar una ubicación
            </div>
            
            <div class="secciones-agregadas-logo" id="secciones_agregadas_logo" style="display: flex; flex-direction: column; gap: 0.75rem;"></div>
        </div>`;
        
        html += `</div>`;
        
        prendasContainer.innerHTML = html;
        
        // Renderizar datos cargados
        renderizarFotosLogo();
        renderizarTecnicasLogo();
        renderizarSeccionesLogo();
        
        console.log('✅ Campos LOGO renderizados correctamente');
    }

    // ====== FUNCIONES DE FOTOS LOGO ======
    window.renderizarFotosLogo = function() {
        const container = document.getElementById('galeria-fotos-logo');
        if (!container) return;
        container.innerHTML = '';
        
        if (logoFotosSeleccionadas.length === 0) {
            container.innerHTML = '<p style="grid-column: 1/-1; color: #9ca3af; text-align: center; padding: 2rem;">Sin imágenes</p>';
            return;
        }
        
        logoFotosSeleccionadas.forEach((foto, idx) => {
            const div = document.createElement('div');
            div.style.cssText = 'position: relative; display: inline-block; width: 100%; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.15); transition: all 0.3s; group: 1;';
            div.innerHTML = `
                <img src="${foto.preview}" 
                     alt="Imagen ${idx + 1}" 
                     style="width: 100%; height: 120px; object-fit: cover; cursor: pointer; transition: transform 0.2s; display: block;" 
                     onmouseover="this.style.transform='scale(1.05)'"
                     onmouseout="this.style.transform=''"
                     onclick="abrirModalImagen('${foto.preview}', 'Logo - Imagen ${idx + 1}')">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0); transition: background 0.2s;" class="overlay-foto" onmouseover="this.parentElement.querySelector('.btn-eliminar-foto').style.opacity='1'; this.style.background='rgba(0,0,0,0.3)'" onmouseout="this.parentElement.querySelector('.btn-eliminar-foto').style.opacity='0'; this.style.background='rgba(0,0,0,0)'"></div>
                <button type="button" onclick="eliminarFotoLogo(${idx})" 
                        style="position: absolute; top: 8px; right: 8px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; z-index: 10; padding: 0; line-height: 1;" 
                        class="btn-eliminar-foto">×</button>
            `;
            container.appendChild(div);
        });
    };

    window.abrirModalAgregarFotosLogo = function() {
        if (logoFotosSeleccionadas.length >= 5) {
            Swal.fire({
                icon: 'warning',
                title: 'Límite de imágenes',
                text: 'Ya has alcanzado el máximo de 5 imágenes permitidas',
                confirmButtonColor: '#0066cc'
            });
            return;
        }
        
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = true;
        
        input.addEventListener('change', (e) => {
            manejarArchivosFotosLogo(e.target.files);
        });
        
        input.click();
    };

    window.manejarArchivosFotosLogo = function(files) {
        const espacioDisponible = 5 - logoFotosSeleccionadas.length;
        
        if (files.length > espacioDisponible) {
            Swal.fire({
                icon: 'warning',
                title: 'Demasiadas imágenes',
                text: `Solo puedes agregar ${espacioDisponible} imagen${espacioDisponible !== 1 ? 's' : ''} más. Máximo 5 en total.`,
                confirmButtonColor: '#0066cc'
            });
            return;
        }
        
        let fotosAgregadas = 0;
        
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    logoFotosSeleccionadas.push({
                        file: file,
                        preview: e.target.result,
                        existing: false
                    });
                    fotosAgregadas++;
                    
                    if (fotosAgregadas === files.length) {
                        renderizarFotosLogo();
                        Swal.fire({
                            icon: 'success',
                            title: 'Imágenes agregadas',
                            text: `Se agregaron ${fotosAgregadas} imagen${fotosAgregadas !== 1 ? 's' : ''} correctamente`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    };

    window.eliminarFotoLogo = function(index) {
        const fotoAEliminar = logoFotosSeleccionadas[index];
        
        // Si es una foto existente (de la BD), eliminarla del servidor
        if (fotoAEliminar && fotoAEliminar.existing && fotoAEliminar.id) {
            console.log('🗑️ Eliminando foto existente de la BD:', fotoAEliminar.id);
            
            // Obtener ID de la cotización de la URL
            const urlParams = new URLSearchParams(window.location.search);
            const cotizacionId = urlParams.get('cotizacion') || document.querySelector('input[name="cotizacion_id"]')?.value;
            
            if (!cotizacionId) {
                console.warn('⚠️ No se encontró el ID de la cotización');
            } else {
                // Enviar petición al servidor para eliminar la foto
                fetch(`/asesores/logos/${cotizacionId}/eliminar-foto`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || 
                                       document.querySelector('input[name="_token"]')?.value
                    },
                    body: JSON.stringify({
                        foto_id: fotoAEliminar.id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ Foto eliminada del servidor:', fotoAEliminar.id);
                        // Quitar de array local
                        logoFotosSeleccionadas.splice(index, 1);
                        renderizarFotosLogo();
                    } else {
                        console.error('❌ Error al eliminar foto:', data.message);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'No se pudo eliminar la imagen'
                        });
                    }
                })
                .catch(error => {
                    console.error('❌ Error en petición:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al eliminar la imagen'
                    });
                });
            }
        } else {
            // Si es una foto nueva (no guardada en BD), simplemente quitarla del array
            console.log('🗑️ Eliminando foto nueva del array');
            logoFotosSeleccionadas.splice(index, 1);
            renderizarFotosLogo();
        }
    };

    // ====== FUNCIONES DE TÉCNICAS LOGO ======
    window.agregarTecnicaLogo = function() {
        const selector = document.getElementById('selector_tecnicas_logo');
        const tecnica = selector.value;
        
        if (!tecnica) {
            alert('Selecciona una técnica');
            return;
        }
        
        if (logoTecnicasSeleccionadas.includes(tecnica)) {
            alert('Esta técnica ya está agregada');
            return;
        }
        
        logoTecnicasSeleccionadas.push(tecnica);
        selector.value = '';
        renderizarTecnicasLogo();
    };

    window.renderizarTecnicasLogo = function() {
        const container = document.getElementById('tecnicas_seleccionadas_logo');
        if (!container) return;
        container.innerHTML = '';
        
        logoTecnicasSeleccionadas.forEach((tecnica, index) => {
            const badge = document.createElement('span');
            badge.style.cssText = 'background: #0066cc; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;';
            badge.innerHTML = `
                ${tecnica}
                <span style="cursor: pointer; font-weight: bold; font-size: 1rem;" onclick="eliminarTecnicaLogo(${index})">×</span>
            `;
            container.appendChild(badge);
        });
    };

    window.eliminarTecnicaLogo = function(index) {
        logoTecnicasSeleccionadas.splice(index, 1);
        renderizarTecnicasLogo();
    };

    // ====== FUNCIONES DE UBICACIONES LOGO ======
    // Variable temporal para saber si estamos editando
    let logoUbicacionEditIndex = null;
    let logoUbicacionTempNombre = '';

    window.agregarSeccionLogo = function() {
        const selector = document.getElementById('seccion_prenda_logo');
        const ubicacion = selector.value;
        const errorDiv = document.getElementById('errorSeccionPrendaLogo');
        
        if (!ubicacion) {
            selector.style.border = '2px solid #ef4444';
            selector.style.background = '#fee2e2';
            selector.classList.add('shake');
            errorDiv.style.display = 'block';
            
            setTimeout(() => {
                selector.style.border = '';
                selector.style.background = '';
                selector.classList.remove('shake');
            }, 600);
            
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 3000);
            
            return;
        }
        
        selector.style.border = '';
        selector.style.background = '';
        errorDiv.style.display = 'none';
        
        // Crear modal con opciones
        const opciones = logoOpcionesPorUbicacion[ubicacion] || [];
        logoUbicacionTempNombre = ubicacion;
        logoUbicacionEditIndex = null;
        
        abrirModalUbicacionLogo(ubicacion, opciones, null);
    };

    window.editarSeccionLogo = function(index) {
        const seccion = logoSeccionesSeleccionadas[index];
        const opciones = logoOpcionesPorUbicacion[seccion.ubicacion] || [];
        logoUbicacionTempNombre = seccion.ubicacion;
        logoUbicacionEditIndex = index;
        
        abrirModalUbicacionLogo(seccion.ubicacion, opciones, seccion);
    };

    window.abrirModalUbicacionLogo = function(ubicacion, opciones, seccionActual) {
        let html = `
            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;" id="modalUbicacionLogo">
                <div style="background: white; border-radius: 16px; padding: 2rem; max-width: 600px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-height: 90vh; overflow-y: auto;">
                    
                    <!-- Header del modal -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #f0f0f0;">
                        <h2 style="margin: 0; color: #1e40af; font-size: 1.3rem; font-weight: 700;">Editar Ubicación</h2>
                        <button type="button" onclick="cerrarModalUbicacionLogo()" style="background: none; border: none; color: #999; font-size: 1.8rem; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">×</button>
                    </div>
                    
                    <!-- Sección 1: Nombre de la sección -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">1. Nombre de la Sección</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #999; font-size: 1.2rem;">👕</span>
                            <input type="text" id="nombreSeccionLogo" value="${ubicacion}" placeholder="Ej: CAMISA, JEAN, GORRA" style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.5rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 1rem; transition: all 0.3s; box-sizing: border-box;">
                        </div>
                    </div>
                    
                    <!-- Sección 2: Ubicaciones -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; font-weight: 700; margin-bottom: 1rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">2. Ubicaciones Disponibles</label>
                        <div id="opcionesUbicacionLogo" style="display: flex; flex-direction: column; gap: 0.5rem; padding: 1rem; background: #f9f9f9; border-radius: 8px; max-height: 250px; overflow-y: auto;"></div>
                    </div>
                    
                    <!-- Sección 3: Agregar personalizado -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">3. Agregar Personalizado</label>
                        <div style="display: flex; gap: 0.75rem;">
                            <input type="text" id="nuevaOpcionLogo" placeholder="Ej: BOLSILLO, MANGA" style="flex: 1; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95rem; transition: all 0.3s; box-sizing: border-box;">
                            <button type="button" onclick="agregarOpcionPersonalizadaLogo()" style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s; white-space: nowrap;">+ Agregar</button>
                        </div>
                    </div>
                    
                    <!-- Sección 4: Observaciones -->
                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px;">4. Observaciones</label>
                        <textarea id="obsUbicacionLogo" placeholder="Añade cualquier observación o nota importante..." style="width: 100%; padding: 0.75rem; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95rem; resize: vertical; min-height: 80px; box-sizing: border-box; font-family: inherit; transition: all 0.3s;">${seccionActual && seccionActual.observaciones ? seccionActual.observaciones : ''}</textarea>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" onclick="cerrarModalUbicacionLogo()" style="background: #f0f0f0; color: #333; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s;">Cancelar</button>
                        <button type="button" onclick="guardarUbicacionLogo()" style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.3s;">✓ Guardar</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', html);
        
        // Agregar opciones como checkboxes
        setTimeout(() => {
            const container = document.getElementById('opcionesUbicacionLogo');
            if (container) {
                // Agregar opciones predefinidas
                if (opciones.length > 0) {
                    opciones.forEach(opcion => {
                        const isChecked = seccionActual && seccionActual.opciones.includes(opcion);
                        const label = document.createElement('label');
                        label.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.75rem; border-radius: 8px; transition: all 0.2s; background: white; border: 1px solid #e0e0e0;';
                        label.innerHTML = `
                            <input type="checkbox" value="${opcion}" ${isChecked ? 'checked' : ''} style="width: 20px; height: 20px; cursor: pointer; accent-color: #0066cc;" class="opcion-ubicacion-logo">
                            <span style="flex: 1; font-weight: 500; color: #333;">${opcion}</span>
                            <button type="button" onclick="eliminarOpcionLogo('${opcion}')" style="background: none; border: none; color: #ef4444; font-size: 1.2rem; cursor: pointer; padding: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;">×</button>
                        `;
                        label.addEventListener('mouseover', () => label.style.background = '#f0f7ff');
                        label.addEventListener('mouseout', () => label.style.background = 'white');
                        container.appendChild(label);
                    });
                }
            }
        }, 10);
        
        // Mejorar inputs con estilos al enfocar
        setTimeout(() => {
            const inputs = document.querySelectorAll('#modalUbicacionLogo input[type="text"], #modalUbicacionLogo textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = '#0066cc';
                    this.style.boxShadow = '0 0 0 3px rgba(0, 102, 204, 0.1)';
                });
                input.addEventListener('blur', function() {
                    this.style.borderColor = '#e0e0e0';
                    this.style.boxShadow = 'none';
                });
            });
        }, 20);
    };

    window.agregarOpcionPersonalizadaLogo = function() {
        const input = document.getElementById('nuevaOpcionLogo');
        const opcion = input.value.trim().toUpperCase();
        
        if (!opcion) {
            alert('Escribe una ubicación');
            return;
        }
        
        const container = document.getElementById('opcionesUbicacionLogo');
        if (!container) return;
        
        // Verificar si ya existe
        const yaExiste = Array.from(container.querySelectorAll('input[type="checkbox"]')).some(
            cb => cb.value.toUpperCase() === opcion
        );
        
        if (yaExiste) {
            alert('Esta ubicación ya está agregada');
            return;
        }
        
        const label = document.createElement('label');
        label.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.75rem; border-radius: 6px; transition: background 0.2s; background: #e8f5e9; border: 1px solid #27ae60;';
        label.innerHTML = `
            <input type="checkbox" value="${opcion}" checked style="width: 18px; height: 18px; cursor: pointer;" class="opcion-ubicacion-logo">
            <span style="flex: 1;">${opcion}</span>
            <button type="button" onclick="eliminarOpcionLogo('${opcion}')" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; padding: 0;">×</button>
        `;
        label.addEventListener('mouseover', () => label.style.background = '#c8e6c9');
        label.addEventListener('mouseout', () => label.style.background = '#e8f5e9');
        container.appendChild(label);
        
        input.value = '';
        input.focus();
    };

    window.eliminarOpcionLogo = function(opcion) {
        const container = document.getElementById('opcionesUbicacionLogo');
        if (!container) return;
        
        Array.from(container.querySelectorAll('input[type="checkbox"]')).forEach(cb => {
            if (cb.value === opcion) {
                cb.closest('label').remove();
            }
        });
    };

    window.cerrarModalUbicacionLogo = function() {
        const modal = document.getElementById('modalUbicacionLogo');
        if (modal) modal.remove();
    };

    window.guardarUbicacionLogo = function() {
        const nombreNuevo = document.getElementById('nombreSeccionLogo').value.trim().toUpperCase();
        const checkboxes = document.querySelectorAll('#opcionesUbicacionLogo input[type="checkbox"]:checked');
        const obs = document.getElementById('obsUbicacionLogo').value;
        
        if (!nombreNuevo) {
            alert('Ingresa un nombre para la sección');
            return;
        }
        
        if (checkboxes.length === 0) {
            alert('Selecciona al menos una ubicación');
            return;
        }
        
        const opciones = Array.from(checkboxes).map(cb => cb.value);
        
        if (logoUbicacionEditIndex !== null) {
            // Editar existente
            logoSeccionesSeleccionadas[logoUbicacionEditIndex] = {
                ubicacion: nombreNuevo,
                opciones: opciones,
                observaciones: obs
            };
        } else {
            // Agregar nuevo
            logoSeccionesSeleccionadas.push({
                ubicacion: nombreNuevo,
                opciones: opciones,
                observaciones: obs
            });
        }
        
        cerrarModalUbicacionLogo();
        document.getElementById('seccion_prenda_logo').value = '';
        renderizarSeccionesLogo();
    };

    window.renderizarSeccionesLogo = function() {
        const container = document.getElementById('secciones_agregadas_logo');
        if (!container) return;
        container.innerHTML = '';
        
        logoSeccionesSeleccionadas.forEach((seccion, index) => {
            const div = document.createElement('div');
            div.style.cssText = 'background: white; border: 2px solid #3498db; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem;';
            
            const opcionesText = Array.isArray(seccion.opciones) ? seccion.opciones.join(', ') : seccion;
            const ubicacionText = seccion.ubicacion || seccion;
            const obsText = seccion.observaciones || '';
            
            div.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 0.5rem 0; color: #1e40af; font-size: 0.95rem;">${ubicacionText}</h4>
                        <p style="margin: 0 0 0.5rem 0; color: #666; font-size: 0.85rem;"><strong>Ubicación:</strong> ${opcionesText}</p>
                        ${obsText ? `<p style="margin: 0; color: #666; font-size: 0.85rem;"><strong>Observaciones:</strong> ${obsText}</p>` : ''}
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                        <button type="button" onclick="editarSeccionLogo(${index})" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; justify-content: center; font-weight: bold;" title="Editar">✎</button>
                        <button type="button" onclick="eliminarSeccionLogo(${index})" style="background: #ef4444; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center;">×</button>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });
    };

    window.eliminarSeccionLogo = function(index) {
        logoSeccionesSeleccionadas.splice(index, 1);
        renderizarSeccionesLogo();
    };

    // ====== FUNCIONES DE OBSERVACIONES LOGO ======
    window.agregarObservacionLogo = function() {
        logoObservacionesGenerales.push('');
        renderizarObservacionesLogo();
    };

    window.renderizarObservacionesLogo = function() {
        const container = document.getElementById('observaciones_lista_logo');
        if (!container) return;
        container.innerHTML = '';
        
        logoObservacionesGenerales.forEach((obs, index) => {
            const fila = document.createElement('div');
            fila.style.cssText = 'display: flex; gap: 10px; align-items: stretch; padding: 10px; background: white; border-radius: 6px; border: 1px solid #ddd;';
            fila.innerHTML = `
                <textarea class="logo-obs-input" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; resize: vertical; min-height: 60px; font-family: inherit;">${obs}</textarea>
                <button type="button" onclick="eliminarObservacionLogo(${index})" style="background: #f44336; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; font-size: 1rem; flex-shrink: 0; height: fit-content;">✕</button>
            `;
            
            // Actualizar array cuando se escribe
            const textarea = fila.querySelector('.logo-obs-input');
            textarea.addEventListener('input', (e) => {
                logoObservacionesGenerales[index] = e.target.value;
            });
            
            container.appendChild(fila);
        });
    };

    window.eliminarObservacionLogo = function(index) {
        logoObservacionesGenerales.splice(index, 1);
        renderizarObservacionesLogo();
    };


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
    // FUNCIONES HELPER PARA RECOLECTAR DATOS
    // ============================================================

    /**
     * Recolecta datos de una prenda específica desde el formulario
     * @param {Element} card - Card element de la prenda
     * @param {number} index - Índice de la prenda
     * @returns {Object} Datos de la prenda o null si no tiene cantidades
     */
    function recolectarDatosPrenda(card, index) {
        if (!card) return null;

        const prenda = prendasCargadas[index];
        if (!prenda) return null;

        // Obtener valores editados
        const nombreProducto = card.querySelector(`.prenda-nombre`)?.value || prenda.nombre_producto;
        const descripcion = card.querySelector(`.prenda-descripcion`)?.value || prenda.descripcion || '';

        // Obtener cantidades por talla
        const cantidadesPorTalla = {};
        const tallaInputs = card.querySelectorAll('.talla-cantidad');
        tallaInputs.forEach(input => {
            const cantidad = parseInt(input.value) || 0;
            const talla = input.getAttribute('data-talla');
            if (cantidad > 0) {
                cantidadesPorTalla[talla] = cantidad;
            }
        });

        // Si no hay cantidades, omitir
        if (Object.keys(cantidadesPorTalla).length === 0) {
            return null;
        }

        // Recopilar variaciones editadas
        const variacionesEditadas = {};
        const inputsVariaciones = card.querySelectorAll('[data-field]');
        inputsVariaciones.forEach(input => {
            const field = input.getAttribute('data-field');
            const value = input.type === 'checkbox' ? (input.checked ? 1 : 0) : (input.value || '');
            if (field && value !== '') {
                variacionesEditadas[field] = value;
            }
        });

        // Recopilar telas
        const telasEditadas = [];
        const telaCards = card.querySelectorAll('[data-prenda="' + index + '"]');
        telaCards.forEach(telaCard => {
            const telaNombre = telaCard.querySelector('[data-field="tela_nombre"]')?.value;
            const telaColor = telaCard.querySelector('[data-field="tela_color"]')?.value;
            const telaRef = telaCard.querySelector('[data-field="tela_ref"]')?.value;
            
            if (telaNombre || telaColor || telaRef) {
                telasEditadas.push({
                    tela: telaNombre || prenda.tela || '',
                    color: telaColor || prenda.color || '',
                    referencia: telaRef || ''
                });
            }
        });

        // Obtener géneros seleccionados
        const generosSeleccionados = [];
        const generosCheckboxes = card.querySelectorAll('.genero-checkbox:checked');
        generosCheckboxes.forEach(checkbox => {
            generosSeleccionados.push(checkbox.value);
        });

        // Recopilar fotos
        const fotosEnDOM = [];
        const imagenesPrendaDOM = card.querySelectorAll('img[data-foto-url][data-prenda-index="' + index + '"]');
        imagenesPrendaDOM.forEach(img => {
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

        // Retornar objeto con todos los datos
        return {
            nombre_producto: nombreProducto,
            descripcion: descripcion,
            cantidades_por_talla: cantidadesPorTalla,
            variaciones: variacionesEditadas,
            telas: telasEditadas,
            generos: generosSeleccionados,
            fotos: fotosEnDOM,
            prenda_id: prenda.id || null
        };
    }

    /**
     * Obtiene técnicas seleccionadas del formulario
     * @returns {Array} Array de técnicas seleccionadas
     */
    function obtenerTecnicasSeleccionadas() {
        const tecnicas = [];
        document.querySelectorAll('input[name="logo_tecnicas"]:checked').forEach(checkbox => {
            tecnicas.push(checkbox.value);
        });
        return tecnicas;
    }

    /**
     * Obtiene ubicaciones seleccionadas del formulario
     * @returns {Array} Array de ubicaciones
     */
    function obtenerUbicacionesSeleccionadas() {
        try {
            const ubicacionesText = document.getElementById('logo_ubicaciones')?.value || '[]';
            return JSON.parse(ubicacionesText);
        } catch (e) {
            console.warn('Error parseando ubicaciones:', e);
            return [];
        }
    }

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

        // Obtener información de cotización
        const cotizacion = misCotizaciones.find(c => c.id === parseInt(cotizacionId));
        const esCombinada = cotizacion && cotizacion.tipo_cotizacion_codigo === 'PL';
        const esLogo = cotizacion && cotizacion.tipo_cotizacion_codigo === 'L';
        
        console.log('🎯 [SUBMIT] Tipo cotización:', cotizacion?.tipo_cotizacion_codigo);
        console.log('🎯 [SUBMIT] ¿Es combinada?:', esCombinada);
        console.log('🎯 [SUBMIT] ¿Es logo puro?:', esLogo);

        // ============================================================
        // CASO 1: COTIZACIÓN COMBINADA (Crear 2 pedidos)
        // ============================================================
        if (esCombinada) {
            console.log('🎯 [COMBINADA] Detectada cotización COMBINADA - crear ambos pedidos');
            
            // Paso 1: Crear pedido de PRENDAS
            const prendasParaEnviar = [];
            document.querySelectorAll('.prenda-card-editable').forEach((card, index) => {
                if (!prendasEliminadas.has(index)) {
                    // Recolectar datos de la prenda igual a como está en el flujo normal
                    const prendaData = recolectarDatosPrenda(card, index);
                    if (prendaData) {
                        prendasParaEnviar.push(prendaData);
                    }
                }
            });

            console.log('📦 [COMBINADA] Prendas a enviar:', prendasParaEnviar.length);
            console.log('📖 [COMBINADA] Datos prendas:', prendasParaEnviar);

            const bodyCrearPrendas = {
                cotizacion_id: cotizacionId,
                forma_de_pago: formaPagoInput.value,
                prendas: prendasParaEnviar
            };

            // Crear primero el pedido de PRENDAS
            fetch(`/asesores/pedidos-produccion/crear-desde-cotizacion/${cotizacionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify(bodyCrearPrendas)
            })
            .then(response => response.json())
            .then(dataPrendas => {
                console.log('✅ [COMBINADA] Pedido de prendas creado:', dataPrendas);
                
                if (!dataPrendas.success) {
                    throw new Error(dataPrendas.message || 'Error al crear pedido de prendas');
                }

                // Paso 2: Crear pedido de LOGO
                const bodyCrearLogo = {
                    cotizacion_id: cotizacionId,
                    forma_de_pago: formaPagoInput.value,
                    prendas: []  // Sin prendas, es solo LOGO
                };

                return Promise.all([
                    Promise.resolve(dataPrendas),
                    fetch(`/asesores/pedidos-produccion/crear-desde-cotizacion/${cotizacionId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify(bodyCrearLogo)
                    }).then(response => response.json())
                ]);
            })
            .then(([dataPrendas, dataLogo]) => {
                console.log('✅ [COMBINADA] Respuesta Logo:', dataLogo);

                if (!dataLogo.success) {
                    throw new Error(dataLogo.message || 'Error al crear pedido de logo');
                }

                // Paso 3: Guardar datos específicos del LOGO
                const logoPedidoId = dataLogo.logo_pedido_id || dataLogo.pedido_id;
                const logoCotizacionIdAUsar = dataLogo.logo_cotizacion_id || logoCotizacionId;

                const bodyLogoPedido = {
                    pedido_id: logoPedidoId,
                    logo_cotizacion_id: logoCotizacionIdAUsar,
                    descripcion: document.getElementById('logo_descripcion')?.value || '',
                    tecnicas: obtenerTecnicasSeleccionadas(),
                    observaciones_tecnicas: document.getElementById('logo_observaciones')?.value || '',
                    ubicaciones: obtenerUbicacionesSeleccionadas(),
                    fotos: logoFotosSeleccionadas || []
                };

                console.log('🎨 [COMBINADA] Guardando datos de logo:', bodyLogoPedido);

                return Promise.all([
                    Promise.resolve(dataPrendas),
                    Promise.resolve(dataLogo),
                    fetch('/asesores/pedidos/guardar-logo-pedido', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify(bodyLogoPedido)
                    }).then(response => response.json())
                ]);
            })
            .then(([dataPrendas, dataLogo, dataLogoPedido]) => {
                console.log('✅ [COMBINADA] Datos logo guardados:', dataLogoPedido);

                if (!dataLogoPedido.success) {
                    throw new Error(dataLogoPedido.message || 'Error al guardar datos del logo');
                }

                // ✅ ÉXITO: Mostrar ambos números
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    html: `
                        <div style="text-align: left;">
                            <p><strong>Ambos pedidos fueron creados exitosamente:</strong></p>
                            <p style="margin-top: 1rem;">
                                📦 <strong>Pedido de Prendas:</strong> <span style="color: #0066cc; font-weight: bold;">${dataPrendas.pedido?.numero_pedido || 'PED-' + dataPrendas.pedido_id}</span>
                            </p>
                            <p style="margin-top: 0.5rem;">
                                🎨 <strong>Pedido de Logo:</strong> <span style="color: #0066cc; font-weight: bold;">${dataLogo.pedido?.numero_pedido || 'LOGO-' + dataLogo.pedido_id}</span>
                            </p>
                        </div>
                    `,
                    confirmButtonText: 'Ir a Pedidos'
                }).then(() => {
                    window.location.href = '/asesores/pedidos';
                });
            })
            .catch(error => {
                console.error('❌ [COMBINADA] Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error: ' + error.message,
                    confirmButtonText: 'OK'
                });
            });

            return;
        }

        // ✅ DETECTAR SI ES LOGO PURO O PRENDAS
        const esLogoSolo = esLogo || logoTecnicasSeleccionadas.length > 0 || 
                           logoSeccionesSeleccionadas.length > 0 || 
                           logoFotosSeleccionadas.length > 0;

        console.log('🎨 Enviando formulario...', {
            esLogo: esLogoSolo,
            logoTecnicas: logoTecnicasSeleccionadas.length,
            logoSecciones: logoSeccionesSeleccionadas.length,
            logoFotos: logoFotosSeleccionadas.length
        });

        if (esLogoSolo) {

            // ============================================================
            // FLUJO PARA LOGO
            // ============================================================
            console.log('🎨 [LOGO] Preparando datos de LOGO para enviar');

            // Crear el pedido primero
            const bodyCrearPedido = {
                cotizacion_id: cotizacionId,
                forma_de_pago: formaPagoInput.value,
                prendas: []  // Sin prendas, es solo LOGO
            };

            console.log('📤 [LOGO] Enviando creación de pedido...', bodyCrearPedido);

            fetch(`/asesores/pedidos-produccion/crear-desde-cotizacion/${cotizacionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify(bodyCrearPedido)
            })
            .then(response => response.json())
            .then(dataCrearPedido => {
                console.log('✅ [LOGO] Pedido creado:', dataCrearPedido);

                if (!dataCrearPedido.success) {
                    throw new Error(dataCrearPedido.message || 'Error al crear pedido');
                }

                // ✅ MEJORADO: Usar logo_pedido_id si está disponible, sino usar pedido_id
                const pedidoId = dataCrearPedido.logo_pedido_id || dataCrearPedido.pedido_id;
                
                // ✅ CORREGIDO: Usar logo_cotizacion_id devuelto por el servidor (más confiable)
                // Si no viene en la respuesta, usar la variable global como fallback
                const logoCotizacionIdAUsar = dataCrearPedido.logo_cotizacion_id || logoCotizacionId;

                // Ahora guardar los datos específicos de LOGO
                const descripcionLogoPedido = document.getElementById('logo_descripcion')?.value || '';
                const observacionesTecnicas = document.getElementById('logo_observaciones_tecnicas')?.value || '';

                console.log('🎨 [LOGO] Capturando descripción:', descripcionLogoPedido);
                console.log('🎨 [LOGO] Técnicas seleccionadas (array):', logoTecnicasSeleccionadas);
                console.log('🎨 [LOGO] Observaciones técnicas:', observacionesTecnicas);
                console.log('🎨 [LOGO] Ubicaciones seleccionadas:', logoSeccionesSeleccionadas);

                const bodyLogoPedido = {
                    pedido_id: pedidoId,
                    logo_cotizacion_id: logoCotizacionIdAUsar,  // ← Usar valor del servidor
                    descripcion: descripcionLogoPedido,
                    tecnicas: logoTecnicasSeleccionadas,
                    observaciones_tecnicas: observacionesTecnicas,
                    ubicaciones: logoSeccionesSeleccionadas,
                    fotos: logoFotosSeleccionadas
                };

                console.log('🎨 [LOGO] Datos del LOGO pedido a guardar:', bodyLogoPedido);

                return fetch('/asesores/pedidos/guardar-logo-pedido', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify(bodyLogoPedido)
                });
            })
            .then(response => response.json())
            .then(data => {
                console.log('✅ [LOGO] Respuesta del servidor:', data);

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Pedido de LOGO creado exitosamente\nNúmero de LOGO: ' + (data.logo_pedido?.numero_pedido || ''),
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = '/asesores/pedidos';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al guardar el LOGO',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('❌ [LOGO] Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error: ' + error.message,
                    confirmButtonText: 'OK'
                });
            });

            return;  // Salir aquí
        }

        // ============================================================
        // FLUJO PARA PRENDAS (PRENDA/REFLECTIVO)
        // ============================================================
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
        
        // Recopilar fotos del reflectivo que quedan en el DOM (las no eliminadas)
        const fotosReflectivoGlobales = [];
        const fotosReflectivoInputs = document.querySelectorAll('input[name="reflectivo_fotos_incluir[]"]');
        console.log('🔍 Inputs de fotos reflectivo encontrados:', fotosReflectivoInputs.length);
        fotosReflectivoInputs.forEach(input => {
            const fotoId = parseInt(input.value);
            if (!isNaN(fotoId)) {
                fotosReflectivoGlobales.push(fotoId);
                console.log('  ✅ Foto ID agregada:', fotoId);
            } else {
                console.warn('  ⚠️ ID inválido:', input.value);
            }
        });
        console.log('📸 Fotos de reflectivo seleccionadas (total):', fotosReflectivoGlobales);
        
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
            let descripcion = prendasCard.querySelector(`.prenda-descripcion`)?.value || prenda.descripcion;
            
            // Para cotizaciones reflectivas, recopilar descripción y ubicaciones
            const descripcionReflectivoInput = prendasCard.querySelector(`textarea[name="reflectivo_descripcion[${index}]"]`);
            if (descripcionReflectivoInput) {
                descripcion = descripcionReflectivoInput.value || '';
                
                // Agregar ubicaciones a la descripción
                const ubicacionesInputs = prendasCard.querySelectorAll(`input[name^="reflectivo_ubicaciones[${index}]"][name$="[ubicacion]"]`);
                if (ubicacionesInputs.length > 0) {
                    const ubicaciones = [];
                    ubicacionesInputs.forEach(input => {
                        if (input.value) {
                            ubicaciones.push(input.value);
                        }
                    });
                    if (ubicaciones.length > 0) {
                        descripcion += '\n\nUbicaciones del reflectivo:\n' + ubicaciones.join(', ');
                    }
                }
            }
            
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
                let value;
                
                // Distinguir entre checkbox e input text
                if (input.type === 'checkbox') {
                    value = input.checked ? 1 : 0;
                } else {
                    value = input.value || '';
                }
                
                if (field && value !== '') {
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
        console.log('📤 Fotos reflectivo a enviar:', fotosReflectivoGlobales);
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                cotizacion_id: cotizacionId,
                forma_de_pago: formaPagoInput.value,
                prendas: prendas,
                reflectivo_fotos_ids: fotosReflectivoGlobales
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
});

// ============================================================
// FUNCIONES GLOBALES PARA ELIMINAR TALLAS (REFLECTIVO)
// ============================================================

/**
 * Elimina una talla de la cotización reflectiva
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} talla - Nombre de la talla a eliminar (ej: "XS", "S", "M", etc)
 */
window.eliminarTallaReflectivo = function(prendaIndex, talla) {
    Swal.fire({
        title: 'Eliminar talla',
        text: `¿Estás seguro de que quieres eliminar la talla ${talla}? No se incluirá en el pedido.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Encontrar y eliminar el elemento visual de la talla
            const tallaElement = document.querySelector(`.talla-item-reflectivo[data-talla="${talla}"][data-prenda="${prendaIndex}"]`);
            if (tallaElement) {
                tallaElement.remove();
                console.log(`✅ Talla ${talla} eliminada de la prenda ${prendaIndex + 1}`);
                
                // Mostrar notificación de éxito
                Swal.fire({
                    icon: 'success',
                    title: 'Talla eliminada',
                    text: `La talla ${talla} no se incluirá en el pedido`,
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                console.warn(`⚠️ No se encontró el elemento de talla ${talla} para prenda ${prendaIndex}`);
            }
        }
    });
};

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
                // Obtener la información de la foto antes de eliminar
                const img = contenedor.querySelector('img');
                const prendaIndex = img?.getAttribute('data-prenda-index');
                
                contenedor.remove();
                console.log(`✅ Imagen de prenda ${prendaIndex} eliminada. Las imágenes restantes se procesarán correctamente.`);
                
                // Procesar imágenes restantes
                procesarImagenesRestantes(prendaIndex);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Imagen eliminada',
                    text: 'La imagen no se incluirá en el pedido. Las imágenes restantes han sido procesadas.',
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
                // Obtener la información de la foto antes de eliminar
                const img = contenedor.querySelector('img');
                const prendaIndex = img?.getAttribute('data-prenda-index');
                
                contenedor.remove();
                console.log(`✅ Imagen de tela de prenda ${prendaIndex} eliminada. Las imágenes restantes se procesarán correctamente.`);
                
                // Procesar imágenes restantes
                procesarImagenesRestantes(prendaIndex, 'tela');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Imagen eliminada',
                    text: 'La imagen no se incluirá en el pedido. Las imágenes restantes han sido procesadas.',
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
                console.log(`✅ Imagen de logo eliminada. Las imágenes restantes del logo se procesarán correctamente.`);
                
                // Procesar imágenes restantes del logo
                procesarImagenesRestantes(null, 'logo');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Imagen eliminada',
                    text: 'La imagen no se incluirá en el pedido. Las imágenes restantes han sido procesadas.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    });
};

// Función para eliminar fotos del reflectivo
window.eliminarFotoReflectivoPedido = function(fotoId) {
    Swal.fire({
        title: 'Eliminar imagen',
        text: '¿Estás seguro de que quieres eliminar esta imagen del reflectivo? No se guardará en el pedido.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const contenedor = document.querySelector(`.reflectivo-foto-item[data-foto-id="${fotoId}"]`);
            if (contenedor) {
                contenedor.remove();
                console.log(`✅ Foto del reflectivo ID ${fotoId} eliminada. Las imágenes restantes se procesarán correctamente.`);
                
                // Procesar imágenes restantes del reflectivo
                procesarImagenesRestantes(null, 'reflectivo');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Imagen eliminada',
                    text: 'La imagen no se incluirá en el pedido. Las imágenes restantes del reflectivo han sido procesadas.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    });
};

// Función para abrir modal de imagen (si no existe)
if (typeof window.abrirModalImagen === 'undefined') {
    window.abrirModalImagen = function(url, titulo) {
        Swal.fire({
            title: titulo || 'Imagen',
            imageUrl: url,
            imageAlt: titulo || 'Imagen',
            width: '80%',
            showCloseButton: true,
            showConfirmButton: false
        });
    };
}
