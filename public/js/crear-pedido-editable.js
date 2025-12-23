// Crear Pedido - Script EDITABLE con soporte para edición y eliminación de prendas

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

/**
 * FUNCIÓN: Cambiar entre tabs
 * Maneja la activación y desactivación de tabs
 */
window.cambiarTab = function(tabName, element = null) {
    console.log('🔄 Cambiando a tab:', tabName);
    
    // Ocultar todos los tabs
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(tab => {
        tab.classList.remove('active');
        tab.style.display = 'none';
    });
    
    // Mostrar el tab seleccionado
    const tabSeleccionado = document.getElementById(`tab-${tabName}`);
    if (tabSeleccionado) {
        tabSeleccionado.classList.add('active');
        tabSeleccionado.style.display = 'block';
    }
    
    // Actualizar estilos de botones
    const tabButtons = document.querySelectorAll('.tab-button-editable');
    tabButtons.forEach(btn => {
        btn.classList.remove('active');
        btn.style.color = '#64748b';
        btn.style.background = 'none';
        btn.style.borderBottomColor = 'transparent';
    });
    
    // Activar botón del tab actual
    if (element) {
        element.classList.add('active');
        element.style.color = 'white';
        element.style.background = 'linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%)';
        element.style.borderBottomColor = '#0ea5e9';
    } else {
        const activeBtn = document.querySelector(`.tab-button-editable[data-tab="${tabName}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
            activeBtn.style.color = 'white';
            activeBtn.style.background = 'linear-gradient(135deg, #1e40af 0%, #0ea5e9 100%)';
            activeBtn.style.borderBottomColor = '#0ea5e9';
        }
    }
};

// ============================================================
// VARIABLES GLOBALES (fuera del DOMContentLoaded)
// ============================================================
let tallasDisponiblesCotizacion = []; // Tallas disponibles en la cotización

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

    // Variables locales del DOMContentLoaded
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
                    
                    // Extraer todas las tallas disponibles de la cotización
                    tallasDisponiblesCotizacion = [];
                    if (prendasCargadas && prendasCargadas.length > 0) {
                        prendasCargadas.forEach(prenda => {
                            if (prenda.tallas && Array.isArray(prenda.tallas)) {
                                prenda.tallas.forEach(talla => {
                                    if (!tallasDisponiblesCotizacion.includes(talla)) {
                                        tallasDisponiblesCotizacion.push(talla);
                                    }
                                });
                            }
                        });
                    }
                    console.log('📏 Tallas disponibles en la cotización:', tallasDisponiblesCotizacion);
                    
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
                    
                    renderizarPrendasEditables(prendasCargadas, data.logo, data.especificaciones, esReflectivo, data.reflectivo, esLogo, tipoCotizacion);
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
    
    function renderizarPrendasEditables(prendas, logoCotizacion = null, especificacionesCotizacion = null, esReflectivo = false, datosReflectivo = null, esLogo = false, tipoCotizacion = 'P') {
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
            
            // ✅ AGREGAR ATRIBUTO data-tipo-cotizacion AL CONTENEDOR
            prendasContainer.setAttribute('data-tipo-cotizacion', tipoCotizacion);
            prendasContainer.innerHTML = html;
            return;
        }

        // Crear estructura de tabs
        let html = '';
        let prendasTabHtml = '';
        let logoTabHtml = '';
        
        // Verificar si hay prendas y logo para mostrar los tabs correspondientes
        const tienePrendas = prendas && prendas.length > 0;
        const tieneLogoPrendas = logoCotizacion && (logoCotizacion.descripcion || logoCotizacion.tecnicas || logoCotizacion.ubicaciones || logoCotizacion.fotos);
        
        // Crear estructura de tabs solo si hay prendas O hay logo
        if (tienePrendas || tieneLogoPrendas) {
            // Tab Navigation
            html += `<div style="
                display: flex;
                gap: 0;
                margin-bottom: 0;
                border-bottom: 2px solid #e2e8f0;
                background: white;
                border-radius: 12px 12px 0 0;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
                overflow: hidden;
                width: 100%;
            ">`;
            
            if (tienePrendas) {
                html += `<button type="button" class="tab-button-editable active" data-tab="prendas" onclick="cambiarTab('prendas', this)" style="
                    padding: 1rem 1.5rem;
                    background: none;
                    border: none;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 0.95rem;
                    color: #64748b;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    border-bottom: 3px solid transparent;
                    position: relative;
                    bottom: -2px;
                ">
                    <i class="fas fa-box"></i> PRENDAS
                </button>`;
            }
            
            if (tieneLogoPrendas) {
                const tabActivoLogo = !tienePrendas ? 'active' : '';
                html += `<button type="button" class="tab-button-editable ${tabActivoLogo}" data-tab="logo" onclick="cambiarTab('logo', this)" style="
                    padding: 1rem 1.5rem;
                    background: none;
                    border: none;
                    cursor: pointer;
                    font-weight: 600;
                    font-size: 0.95rem;
                    color: #64748b;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    border-bottom: 3px solid transparent;
                    position: relative;
                    bottom: -2px;
                ">
                    <i class="fas fa-tools"></i> LOGO
                </button>`;
            }
            
            html += `</div>`;
            
            // Tab Content Wrapper
            html += `<div class="tab-content-wrapper" style="
                background: white;
                border-radius: 0 0 12px 12px;
                padding: 2rem;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
                width: 100%;
                display: block;
                box-sizing: border-box;
                max-width: 100%;
                margin: 0;
            ">`;
            
            // Tab Prendas
            if (tienePrendas) {
                html += `<div id="tab-prendas" class="tab-content active" style="display: block;">`;
            }
        }

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

            // LOG PARA DEBUGUEO
            console.log(`👕 Prenda ${index}:`, prenda);
            console.log(`   - telaFotos recibidas:`, telaFotos);
            console.log(`   - variantes.telas_multiples:`, variantes.telas_multiples);
            if (variantes.telas_multiples && variantes.telas_multiples.length > 0) {
                variantes.telas_multiples.forEach((tela, idx) => {
                    console.log(`      - Tela ${idx}: id=${tela.id}, nombre=${tela.nombre_tela}, color=${tela.color}`);
                });
            }

            let nombreProenda = prenda.nombre_producto || '';
            const variacionesPrincipales = [];
            if (variantes.color) variacionesPrincipales.push(variantes.color);
            if (variacionesPrincipales.length > 0) {
                nombreProenda += ' (' + variacionesPrincipales.join(' - ') + ')';
            }

            // Generar HTML de tallas editables (TABLA ESTILO SIMILAR A TELAS)
            let tallasHtml = '';
            if (tallas.length > 0) {
                tallasHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
                tallasHtml += '<div style="padding: 0.75rem 1rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 600; display: flex; justify-content: space-between; align-items: center; width: 100%;">';
                tallasHtml += '<div style="display: flex; gap: 1rem; flex: 1;"><div style="flex: 1.5;">Talla - Introduce cantidades</div><div style="flex: 1;">Cantidad</div><div style="width: 100px; text-align: center;">Acción</div></div>';
                tallasHtml += '<button type="button" onclick="mostrarModalAgregarTalla(' + index + ')" style="background: #4ade80; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 0.4rem; white-space: nowrap; flex-shrink: 0;">+ Talla</button>';
                tallasHtml += '</div>';
                
                tallas.forEach(talla => {
                    tallasHtml += `<div style="padding: 1rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1.5fr 1fr 100px; gap: 1rem; align-items: center; transition: background 0.2s; width: 100%;">
                        <div style="display: flex; flex-direction: column;">
                            <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Talla</label>
                            <div style="font-weight: 500; color: #1f2937;">${talla}</div>
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Cantidad</label>
                            <input type="number" 
                                   name="cantidades[${index}][${talla}]" 
                                   class="talla-cantidad"
                                   min="0" 
                                   value="0" 
                                   placeholder="0"
                                   data-talla="${talla}"
                                   data-prenda="${index}"
                                   style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;">
                        </div>
                        <div style="text-align: center;">
                            <button type="button" class="btn-quitar-talla" onclick="quitarTallaDelFormulario(${index}, '${talla}')" style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;">
                                ✕ Quitar
                            </button>
                        </div>
                    </div>`;
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
                    campo: 'tipo_manga',
                    esCheckbox: false
                });
            }
            if (variantes.tipo_broche) {
                variacionesArray.push({
                    tipo: 'Broche/Botón',
                    valor: variantes.tipo_broche,
                    obs: variantes.obs_broche,
                    campo: 'tipo_broche',
                    esCheckbox: false
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
                variacionesHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
                variacionesHtml += '<div style="padding: 0.5rem 0.75rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 600; display: grid; grid-template-columns: 1fr 80px 1.2fr 45px; gap: 0.5rem; align-items: center; width: 100%; font-size: 0.85rem;">';
                variacionesHtml += '<div>📋 Variaciones</div>';
                variacionesHtml += '<div style="text-align: center;">Valor</div>';
                variacionesHtml += '<div>Observaciones</div>';
                variacionesHtml += '<div style="text-align: center;">Acción</div>';
                variacionesHtml += '</div>';
                
                variacionesArray.forEach((variacion, varIdx) => {
                    let inputHtml = '';
                    if (variacion.esCheckbox) {
                        // Para campos booleanos, mostrar checkbox grande
                        const isChecked = variacion.valor === true || variacion.valor === 'Sí' || variacion.valor === 1 ? 'checked' : '';
                        inputHtml = `<input type="checkbox" 
                                           ${isChecked}
                                           data-field="${variacion.campo}" 
                                           data-prenda="${index}"
                                           data-variacion="${varIdx}"
                                           style="width: 24px; height: 24px; cursor: pointer; accent-color: #0066cc;">`;
                    } else {
                        // Para campos de texto, mostrar input text
                        inputHtml = `<input type="text" 
                                           value="${variacion.valor}" 
                                           data-field="${variacion.campo}" 
                                           data-prenda="${index}"
                                           data-variacion="${varIdx}"
                                           style="width: 100%; padding: 0.4rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem; transition: border-color 0.2s; box-sizing: border-box;">`;
                    }
                    
                    variacionesHtml += `<div style="padding: 0.6rem 0.75rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1fr 80px 1.2fr 45px; gap: 0.5rem; align-items: center; transition: background 0.2s; width: 100%; font-size: 0.85rem;" data-variacion="${varIdx}" data-prenda="${index}">
                        <div style="font-weight: 500; color: #1f2937; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${variacion.tipo}</div>
                        <div style="display: flex; justify-content: center; align-items: center;">
                            ${inputHtml}
                        </div>
                        <div style="display: flex; align-items: center;">
                            <textarea 
                                   data-field="${variacion.campo}_obs" 
                                   data-prenda="${index}"
                                   data-variacion="${varIdx}"
                                   style="width: 100%; padding: 0.4rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.8rem; min-height: 36px; resize: vertical; font-family: inherit; box-sizing: border-box;" placeholder="...">${variacion.obs || ''}</textarea>
                        </div>
                        <div style="display: flex; justify-content: center; align-items: center;">
                            <button type="button" 
                                    class="btn-eliminar-variacion" 
                                    onclick="eliminarVariacionDePrenda(${index}, ${varIdx})"
                                    title="Eliminar variación"
                                    style="background: #dc3545; color: white; border: none; padding: 0.4rem; border-radius: 4px; cursor: pointer; font-size: 1rem; font-weight: bold; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; min-width: auto; flex-shrink: 0;">
                                ✕
                            </button>
                        </div>
                    </div>`;
                });
                
                variacionesHtml += '</div>';
            }

            // Generar HTML de telas/colores múltiples (EDITABLE - MODERNO Y RESPONSIVO)
            let telasHtml = '';
            // Combinar prenda.telas (con IDs) con variantes.telas_multiples (con detalles)
            const telasMapeadas = [];
            const telasMultiples = variantes.telas_multiples || [];
            const telasDelServidor = prenda.telas || [];
            
            // Mapear: usar telas_multiples como fuente principal, pero agregar IDs de prenda.telas
            telasMultiples.forEach((telaMult, idx) => {
                const telaDelServidor = telasDelServidor[idx];
                telasMapeadas.push({
                    id: telaDelServidor?.id || null,
                    nombre_tela: telaMult.nombre_tela || telaMult.tela || '',
                    color: telaMult.color || '',
                    referencia: telaDelServidor?.referencia || telaMult.referencia || '',
                });
            });
            
            const telasParaTabla = telasMapeadas.length > 0 ? telasMapeadas : telasMultiples;
            
            if (telasParaTabla && telasParaTabla.length > 0) {
                // Detectar si tela_id es null en TODAS las fotos (para hacer distribución por orden)
                const todasLasFotosConTelaIdNull = telaFotos.length > 0 && telaFotos.every(f => f.tela_id === null);
                
                console.log(`   - Todas las fotos con tela_id null? ${todasLasFotosConTelaIdNull}`);
                console.log(`   - Total de fotos: ${telaFotos.length}, Total de telas: ${telasParaTabla.length}`);
                console.log(`   - Telas mapeadas:`, telasMapeadas);
                
                // Si todas las fotos tienen tela_id null, distribuirlas por orden
                const fotosDistribuidas = {};
                if (todasLasFotosConTelaIdNull && telaFotos.length > 0) {
                    const fotosXTela = Math.ceil(telaFotos.length / telasParaTabla.length);
                    telasParaTabla.forEach((tela, telaIdx) => {
                        const inicio = telaIdx * fotosXTela;
                        const fin = inicio + fotosXTela;
                        fotosDistribuidas[telaIdx] = telaFotos.slice(inicio, fin);
                        console.log(`   - Tela ${telaIdx}: fotos ${inicio}-${fin-1}`);
                    });
                }
                
                telasHtml = '<div style="margin-top: 1.5rem; padding: 0; background: transparent; width: 100%;">';
                telasHtml += '<div style="padding: 0.75rem 1rem; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border-radius: 6px 6px 0 0; font-weight: 600; display: grid; grid-template-columns: 1fr 1fr 1fr 100px; gap: 1rem; align-items: center; width: 100%;">';
                telasHtml += '<div>Telas</div>';
                telasHtml += '<div>Color</div>';
                telasHtml += '<div>Referencia</div>';
                telasHtml += '<div style="text-align: center;">Fotos:</div>';
                telasHtml += '</div>';
                
                telasParaTabla.forEach((tela, telaIdx) => {
                    // Obtener fotos específicas de esta tela
                    let fotosDeTela = [];
                    
                    if (todasLasFotosConTelaIdNull) {
                        // Usar fotos distribuidas por orden
                        fotosDeTela = fotosDistribuidas[telaIdx] || [];
                    } else {
                        // Filtrar fotos por tela_id
                        const telaId = tela.id;
                        fotosDeTela = telaId ? telaFotos.filter(f => f.tela_id === telaId) : [];
                    }
                    
                    console.log(`   - Tela ${telaIdx} (id=${tela.id}): fotos encontradas=${fotosDeTela.length}`);
                    
                    let fotosTelaHtml = '';
                    if (fotosDeTela.length > 0) {
                        // Mostrar máximo 1-2 fotos pequeñas
                        const fotosMostrar = fotosDeTela.slice(0, 2);
                        fotosTelaHtml = '<div style="display: flex; gap: 0.4rem; flex-wrap: wrap; justify-content: center;">';
                        fotosMostrar.forEach((telaFoto, fotoIdx) => {
                            const fotoUrl = telaFoto.url || telaFoto.ruta_webp || telaFoto.ruta_original;
                            if (fotoUrl) {
                                fotosTelaHtml += `
                                    <div style="position: relative; display: inline-block; width: 60px; height: 60px;">
                                        <img src="${fotoUrl}" alt="Foto de tela" 
                                             style="width: 100%; height: 100%; object-fit: cover; cursor: pointer; border: 1px solid #d0d0d0; border-radius: 4px; transition: all 0.2s;"
                                             ondblclick="abrirModalImagen('${fotoUrl}', 'Foto de tela')"
                                             title="Doble click para ver a mayor tamaño">
                                        <button type="button"
                                                onclick="eliminarImagenTela(this)"
                                                style="position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border: none; width: 20px; height: 20px; border-radius: 50%; cursor: pointer; font-weight: bold; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" title="Eliminar imagen">×</button>
                                    </div>
                                `;
                            }
                        });
                        if (fotosDeTela.length > 2) {
                            fotosTelaHtml += `<div style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border: 1px solid #d0d0d0; border-radius: 4px; background: #f5f5f5; font-size: 0.75rem; color: #666; text-align: center; padding: 0.25rem;">+${fotosDeTela.length - 2}</div>`;
                        }
                        fotosTelaHtml += '</div>';
                    } else {
                        // Si no hay fotos para esta tela específica
                        fotosTelaHtml = '<div style="font-size: 0.75rem; color: #999;">Sin fotos</div>';
                    }
                    
                    // Obtener valores de tela desde el objeto mapeado
                    const nombreTela = tela.nombre_tela || '';
                    const colorTela = typeof tela.color === 'object' ? (tela.color?.nombre || tela.color?.name || '') : (tela.color || '');
                    const referenciaTela = tela.referencia || '';
                    
                    console.log(`   - Tela ${telaIdx}: nombre="${nombreTela}", color="${colorTela}", referencia="${referenciaTela}"`);
                    
                    telasHtml += `<div style="padding: 1rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1fr 1fr 1fr 100px; gap: 1rem; align-items: center; transition: background 0.2s; width: 100%;">
                        <div style="display: flex; flex-direction: column;">
                            <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Tela</label>
                            <input type="text" value="${nombreTela}" data-field="tela_nombre" data-idx="${telaIdx}" data-prenda="${index}" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;" placeholder="Ej: Algodón">
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Color</label>
                            <input type="text" value="${colorTela}" data-field="tela_color" data-idx="${telaIdx}" data-prenda="${index}" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;" placeholder="Ej: Rojo">
                        </div>
                        <div style="display: flex; flex-direction: column;">
                            <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Referencia</label>
                            <input type="text" value="${referenciaTela}" data-field="tela_ref" data-idx="${telaIdx}" data-prenda="${index}" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;" placeholder="Ej: REF-001">
                        </div>
                        <div style="display: flex; justify-content: center; align-items: center;">
                            ${fotosTelaHtml}
                        </div>
                    </div>`;
                });
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
                        </div>

                        <div class="prenda-fotos-section">
                            ${fotosHtml}
                        </div>
                    </div>

                    ${variacionesHtml}
                    ${tallasHtml}
                    ${telasHtml}
                </div>
            `;
        });

        // Cerrar tab de prendas si existe y abrir tab de logo
        if (tienePrendas || tieneLogoPrendas) {
            if (tienePrendas) {
                html += `</div>`; // cierra #tab-prendas
            }

            if (tieneLogoPrendas) {
                html += `<div id="tab-logo" class="tab-content" style="display: none;">`;
                html += `<div style="margin-top: 1rem; padding: 2rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">`;
                html += `<h3 style="margin: 0 0 1.5rem 0; font-size: 1.1rem; color: #1f2937; border-bottom: 3px solid #0066cc; padding-bottom: 0.75rem;">📋 Información del Logo</h3>`;
            }
        }

        // ========== SECCIÓN DE LOGO COMPLETA (para cotizaciones combinadas) ==========
        if (logoCotizacion) {
            // Función helper para parsear datos JSON
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
            let logoSeccionesSeleccionadasTab = [];
            
            // Cargar ubicaciones iniciales
            if (ubicacionesArray && ubicacionesArray.length > 0) {
                ubicacionesArray.forEach(ubicacion => {
                    if (typeof ubicacion === 'object' && ubicacion.ubicacion) {
                        logoSeccionesSeleccionadasTab.push({
                            id: window.generarUUID(),
                            ubicacion: ubicacion.ubicacion,
                            opciones: Array.isArray(ubicacion.opciones) ? ubicacion.opciones : [],
                            tallas: Array.isArray(ubicacion.tallas) ? ubicacion.tallas.map(t => t.talla || t) : [],
                            tallasCantidad: ubicacion.tallasCantidad || (Array.isArray(ubicacion.tallas) ? ubicacion.tallas.reduce((acc, t) => {
                                acc[t.talla || t] = t.cantidad || 0;
                                return acc;
                            }, {}) : {}),
                            observaciones: ubicacion.observaciones || ''
                        });
                    }
                });
            }
            
            // Asignar a variable global para acceso desde modales
            window.logoSeccionesSeleccionadasTab = logoSeccionesSeleccionadasTab;
            
            // ========== DESCRIPCIÓN (EDITABLE) ==========
            html += `<div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">DESCRIPCIÓN</label>
                <textarea id="logo_descripcion" name="logo_descripcion" 
                          style="width: 100%; padding: 0.75rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; font-family: inherit; min-height: 100px; color: #333;">${logoCotizacion.descripcion || ''}</textarea>
            </div>`;
            
            // ========== FOTOS (EDITABLES) ==========
            html += `<div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">IMÁGENES (MÁXIMO 5)</label>
                    <button type="button" onclick="abrirModalAgregarFotosLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
                </div>
                <div id="galeria-fotos-logo" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;">`;
            
            // Cargar fotos iniciales
            if (logoCotizacion.fotos && logoCotizacion.fotos.length > 0) {
                logoCotizacion.fotos.forEach((foto) => {
                    const fotoUrl = foto.url || foto.ruta_webp || foto.ruta_original;
                    if (fotoUrl) {
                        html += `<div style="position: relative; display: inline-block; width: 100%;">
                            <img src="${fotoUrl}" 
                                 alt="Foto" 
                                 style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 1px solid #d0d0d0;" 
                                 onclick="abrirModalImagen('${fotoUrl}', 'Foto del logo')">
                            <button type="button" 
                                    style="position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;">×</button>
                        </div>`;
                    }
                });
            }
            
            html += `</div></div>`;
            
            // ========== TÉCNICAS (EDITABLE) ==========
            html += `<div style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 4px; border-left: 4px solid #0066cc;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">Técnicas Disponibles</label>
                    <button type="button" onclick="agregarTecnicaTabLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
                </div>
                
                <select id="selector_tecnicas_logo" style="width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                    <option value="">-- SELECCIONA UNA TÉCNICA --</option>
                    <option value="BORDADO">BORDADO</option>
                    <option value="DTF">DTF</option>
                    <option value="ESTAMPADO">ESTAMPADO</option>
                    <option value="SUBLIMADO">SUBLIMADO</option>
                </select>
                
                <div id="tecnicas_seleccionadas_logo" style="display: flex; flex-wrap: wrap; gap: 0.5rem;"></div>
            </div>`;
            
            // ========== OBSERVACIONES DE TÉCNICAS (EDITABLE) ==========
            html += `<div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 700; margin-bottom: 0.5rem; color: #1f2937; font-size: 0.95rem;">Observaciones de Técnicas</label>
                <textarea id="logo_observaciones_tecnicas" name="logo_observaciones_tecnicas" 
                          style="width: 100%; padding: 0.75rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; font-family: inherit; min-height: 80px; color: #333;">${logoCotizacion.observaciones_tecnicas || ''}</textarea>
            </div>`;
            
            // ========== TALLAS A COTIZAR - CONSOLIDADAS EN UBICACIONES (ELIMINAR) ==========
            // Las tallas ahora se manejan dentro de cada sección en el modal de ubicaciones
            // Esta tabla vieja ha sido eliminada
            

            // ========== UBICACIÓN (TABLA EDITABLE) ==========
            html += `<div style="margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem; margin: 0; flex-shrink: 0;">📍 UBICACIÓN</label>
                    <input type="text" id="seccion_prenda_logo_tab" placeholder="Ej: CAMISA, JEAN, GORRA" style="flex: 1; padding: 0.6rem 0.75rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box;" />
                    <button type="button" onclick="agregarSeccionLogoTab()" title="Agregar nueva sección" style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border: none; border-radius: 50%; width: 32px; height: 32px; cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center; font-weight: bold; transition: all 0.3s; box-shadow: 0 2px 8px rgba(0,102,204,0.3); flex-shrink: 0;">+</button>
                </div>
                
                <table style="width: 100%; border-collapse: separate; border-spacing: 0; background: white; border-radius: 8px; overflow: hidden; font-size: 0.9rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white;">
                            <th style="padding: 1rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); color: white;">Sección</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); color: white;">Tallas</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); color: white;">Ubicaciones</th>
                            <th style="padding: 1rem; text-align: left; font-weight: 600; border-right: 1px solid rgba(255,255,255,0.1); color: white;">Obs.</th>
                            <th style="padding: 1rem; text-align: center; font-weight: 600; width: 100px; color: white;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="logo-ubicaciones-tbody-tab">`;
            
            if (logoSeccionesSeleccionadasTab && logoSeccionesSeleccionadasTab.length > 0) {
                logoSeccionesSeleccionadasTab.forEach((seccion) => {
                    if (!seccion.id) {
                        seccion.id = window.generarUUID();
                    }
                    const seccionId = seccion.id;
                    const tallasConCantidad = seccion.tallas && seccion.tallas.length > 0 
                        ? seccion.tallas.map(t => `${t} (${seccion.tallasCantidad && seccion.tallasCantidad[t] ? seccion.tallasCantidad[t] : 0})`).join(', ')
                        : '—';
                    html += `<tr style="border-bottom: 1px solid #e5e7eb; transition: all 0.2s;" data-seccion-id="${seccionId}" onmouseover="this.style.backgroundColor = '#f8fafb';" onmouseout="this.style.backgroundColor = 'white';">
                        <td style="padding: 1rem; border-right: 1px solid #e5e7eb;">
                            <strong style="font-weight: 600; color: #1f2937;">${seccion.ubicacion}</strong>
                        </td>
                        <td style="padding: 1rem; border-right: 1px solid #e5e7eb; font-size: 0.8rem; color: #666;">
                            ${tallasConCantidad}
                        </td>
                        <td style="padding: 1rem; border-right: 1px solid #e5e7eb;">
                            <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;" id="opciones-${seccionId}">
                                ${seccion.opciones && seccion.opciones.length > 0 ? seccion.opciones.map((opcion, opIdx) => `
                                    <span style="display: inline-flex; align-items: center; gap: 0.3rem; background: #dbeafe; color: #1976d2; padding: 0.4rem 0.7rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">
                                        ${opcion}
                                        <button type="button" onclick="eliminarUbicacionItemTab('${seccionId}', ${opIdx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.85rem; line-height: 1; margin-left: 0.1rem;">×</button>
                                    </span>
                                `).join('') : '<span style="color: #999; font-size: 0.75rem;">Sin ubicaciones</span>'}
                            </div>
                        </td>
                        <td style="padding: 1rem; border-right: 1px solid #e5e7eb;">
                            <textarea class="logo-ubicacion-obs-tab" data-seccion-id="${seccionId}" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.75rem; min-height: 40px; resize: vertical; font-family: inherit; background: #fafafa; box-sizing: border-box;"
                                      onfocus="this.style.borderColor = '#0066cc'; this.style.backgroundColor = 'white';"
                                      onblur="this.style.borderColor = '#d0d0d0'; this.style.backgroundColor = '#fafafa';"
                                      placeholder="...">${seccion.observaciones || ''}</textarea>
                        </td>
                        <td style="padding: 1rem; text-align: center; display: flex; gap: 0.4rem; justify-content: center;">
                            <button type="button" onclick="editarSeccionLogoTab('${seccionId}')" title="Editar sección" style="background: #3b82f6; color: white; border: none; padding: 0.5rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; min-width: 35px; hover: background: #2563eb;">✏</button>
                            <button type="button" onclick="eliminarSeccionLogoTab('${seccionId}')" title="Eliminar sección" style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.9rem; transition: all 0.2s; min-width: 35px; hover: background: #c82333;">✕</button>
                        </td>
                    </tr>`;
                });
            } else {
                html += `<tr><td colspan="5" style="padding: 1rem; text-align: center; color: #999;">Sin ubicaciones definidas. Agrega una haciendo clic en el botón +</td></tr>`;
            }
            
            html += `</tbody>
                </table>
            </div>`;
            
            if (tieneLogoPrendas) {
                html += `</div>`; // cierra el contenedor principal con estilos
                html += '</div>'; // cierra #tab-logo
            }
        }

        // Cerrar tab-content-wrapper si se crearon tabs
        if (tienePrendas || tieneLogoPrendas) {
            html += '</div>'; // cierra tab-content-wrapper
        }
        
        // ✅ AGREGAR ATRIBUTO data-tipo-cotizacion AL CONTENEDOR
        prendasContainer.setAttribute('data-tipo-cotizacion', tipoCotizacion);
        prendasContainer.innerHTML = html;
        
        console.log('Prendas y logo renderizados con información completa');
        
        // ============================================================
        // CARGAR TÉCNICAS EN EL TAB LOGO (SI ES COTIZACIÓN COMBINADA)
        // ============================================================
        if (tieneLogoPrendas && logoCotizacion && logoCotizacion.tecnicas) {
            setTimeout(() => {
                const galeriaFotos = document.getElementById('galeria-fotos-logo');
                const tecnicasSeleccionadasDiv = document.getElementById('tecnicas_seleccionadas_logo');
                
                // Renderizar fotos iniciales
                if (galeriaFotos && logoCotizacion.fotos && logoCotizacion.fotos.length > 0) {
                    galeriaFotos.innerHTML = '';
                    logoCotizacion.fotos.forEach((foto, idx) => {
                        const fotoUrl = foto.url || foto.ruta_webp || foto.ruta_original;
                        if (fotoUrl) {
                            const div = document.createElement('div');
                            div.style.cssText = 'position: relative; display: inline-block; width: 100%;';
                            div.innerHTML = `
                                <img src="${fotoUrl}" 
                                     alt="Foto" 
                                     style="width: 100%; height: 120px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 1px solid #d0d0d0;" 
                                     onclick="abrirModalImagen('${fotoUrl}', 'Foto del logo')">
                                <button type="button" 
                                        style="position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;">×</button>
                            `;
                            galeriaFotos.appendChild(div);
                        }
                    });
                }
                
                // Renderizar técnicas seleccionadas
                if (tecnicasSeleccionadasDiv && logoCotizacion.tecnicas && logoCotizacion.tecnicas.length > 0) {
                    tecnicasSeleccionadasDiv.innerHTML = '';
                    logoCotizacion.tecnicas.forEach((tecnica, idx) => {
                        const tecnicaText = typeof tecnica === 'object' ? tecnica.nombre : tecnica;
                        const span = document.createElement('span');
                        span.style.cssText = 'display: inline-flex; align-items: center; gap: 0.5rem; background: #e3f2fd; color: #1976d2; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.9rem; font-weight: 600;';
                        span.innerHTML = `
                            ${tecnicaText}
                            <button type="button" onclick="eliminarTecnicaDelTabLogo(${idx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-size: 1.2rem; padding: 0;">×</button>
                        `;
                        tecnicasSeleccionadasDiv.appendChild(span);
                    });
                }
            }, 50);
        }
        
        // ============================================================
        // EVENT LISTENERS PARA ACTUALIZAR TÍTULO DE PRENDA EN TIEMPO REAL
        // ============================================================
        
        // Agregar listeners a todos los inputs de "Nombre del Producto"
        const nombreProductoInputs = document.querySelectorAll('.prenda-nombre');
        nombreProductoInputs.forEach(input => {
            input.addEventListener('input', function() {
                const prendasIndex = this.dataset.prenda;
                const nuevoNombre = this.value.trim();
                
                // Encontrar el elemento .prenda-title de la tarjeta correspondiente
                const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendasIndex}"]`);
                if (prendasCard) {
                    const prendasTitle = prendasCard.querySelector('.prenda-title');
                    if (prendasTitle) {
                        // Actualizar el título con el nuevo nombre
                        prendasTitle.textContent = `🧥 Prenda ${parseInt(prendasIndex) + 1}: ${nuevoNombre}`;
                    }
                }
            });
        });
    }

    // ============================================================
    // RENDERIZAR CAMPOS SOLO PARA LOGO (sin prendas)
    // ============================================================
    
    // Arrays globales para almacenar datos editables del LOGO
    let logoTecnicasSeleccionadas = [];
    let logoSeccionesSeleccionadas = [];
    let logoFotosSeleccionadas = [];  // Array para guardar fotos editables
    let logoCotizacionId = null;  // ID del LogoCotizacion para guardar en BD

    // Opciones por ubicación (mismas del formulario de bordado)
    const logoOpcionesPorUbicacion = {
        'CAMISA': ['PECHO', 'ESPALDA', 'MANGA IZQUIERDA', 'MANGA DERECHA', 'CUELLO'],
        'JEAN_SUDADERA': ['PIERNA IZQUIERDA', 'PIERNA DERECHA', 'BOLSILLO TRASERO', 'BOLSILLO RELOJERO'],
        'GORRAS': ['FRENTE', 'LATERAL', 'TRASERA']
    };
    
    // Asignar a variable global para acceso desde funciones
    window.logoOpcionesPorUbicacion = logoOpcionesPorUbicacion;

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
        
        // ========== TALLAS A COTIZAR (TABLA EDITABLE) ==========
        // Parsear tallas de las ubicaciones
        let tallasArray = [];
        if (ubicacionesArray && ubicacionesArray.length > 0) {
            ubicacionesArray.forEach(ub => {
                if (ub.tallas && Array.isArray(ub.tallas)) {
                    ub.tallas.forEach(talla => {
                        // Evitar duplicados
                        const yaBuscada = tallasArray.find(t => t.talla === talla.talla);
                        if (!yaBuscada) {
                            tallasArray.push(talla);
                        }
                    });
                }
            });
        }
        
        html += `<div style="margin-bottom: 1.5rem;">
            <label style="display: block; font-weight: 700; margin-bottom: 0.75rem; color: #1f2937; font-size: 0.95rem;">🧵 TALLAS A COTIZAR</label>
            <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-size: 0.9rem;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-right: 1px solid #cbd5e1;">Talla</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-right: 1px solid #cbd5e1;">Cantidad</th>
                        <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; width: 80px;">Acción</th>
                    </tr>
                </thead>
                <tbody id="logo-tallas-tbody">`;
        
        if (tallasArray.length > 0) {
            tallasArray.forEach((talla, idx) => {
                html += `<tr style="border-bottom: 1px solid #e0e0e0;">
                    <td style="padding: 0.75rem 1rem; border-right: 1px solid #cbd5e1;">
                        <input type="text" value="${talla.talla || ''}" data-talla-idx="${idx}" class="logo-talla-nombre" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem;">
                    </td>
                    <td style="padding: 0.75rem 1rem; border-right: 1px solid #cbd5e1;">
                        <input type="number" value="${talla.cantidad || 0}" data-talla-idx="${idx}" class="logo-talla-cantidad" min="0" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem;">
                    </td>
                    <td style="padding: 0.75rem 1rem; text-align: center;">
                        <button type="button" onclick="eliminarTallaLogo(${idx})" style="background: #dc3545; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s;">✕ Eliminar</button>
                    </td>
                </tr>`;
            });
        } else {
            html += `<tr><td colspan="3" style="padding: 1rem; text-align: center; color: #999;">Sin tallas definidas</td></tr>`;
        }
        
        html += `</tbody>
            </table>
        </div>`;
        
        // ========== UBICACIÓN (TABLA EDITABLE) ==========
        html += `<div style="margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <label style="display: block; font-weight: 700; color: #1f2937; font-size: 0.95rem;">📍 UBICACIÓN</label>
                <button type="button" class="btn-add" onclick="agregarSeccionLogo()" style="background: #0066cc; color: white; border: none; border-radius: 50%; width: 28px; height: 28px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; font-weight: bold;">+</button>
            </div>
            
            <select id="seccion_prenda_logo" style="width: 100%; margin-bottom: 12px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                <option value="">-- SELECCIONA UNA OPCIÓN --</option>
                <option value="CAMISA">CAMISA</option>
                <option value="JEAN_SUDADERA">JEAN/SUDADERA</option>
                <option value="GORRAS">GORRAS</option>
            </select>
            
            <div id="errorSeccionPrendaLogo" style="display: none; color: #ef4444; font-size: 0.85rem; font-weight: 600; padding: 0.5rem; background: #fee2e2; border-radius: 4px; margin-bottom: 10px;">
                ⚠️ Debes seleccionar una ubicación
            </div>
            
            <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #cbd5e1; border-radius: 6px; overflow: hidden; font-size: 0.9rem;">
                <thead>
                    <tr style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white;">
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-right: 1px solid #cbd5e1;">Sección</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-right: 1px solid #cbd5e1;">Ubicaciones Seleccionadas</th>
                        <th style="padding: 0.75rem 1rem; text-align: left; font-weight: 600; border-right: 1px solid #cbd5e1;">Observaciones</th>
                        <th style="padding: 0.75rem 1rem; text-align: center; font-weight: 600; width: 80px;">Acción</th>
                    </tr>
                </thead>
                <tbody id="logo-ubicaciones-tbody">`;
        
        if (logoSeccionesSeleccionadas.length > 0) {
            logoSeccionesSeleccionadas.forEach((seccion, idx) => {
                const ubicacionesText = Array.isArray(seccion.opciones) ? seccion.opciones.join(', ') : '';
                html += `<tr style="border-bottom: 1px solid #e0e0e0;" data-ubicacion-idx="${idx}">
                    <td style="padding: 0.75rem 1rem; border-right: 1px solid #cbd5e1;">
                        <input type="text" value="${seccion.ubicacion}" class="logo-ubicacion-nombre" data-ubicacion-idx="${idx}" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem;">
                    </td>
                    <td style="padding: 0.75rem 1rem; border-right: 1px solid #cbd5e1;">
                        <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                            ${seccion.opciones.map((opcion, opIdx) => `
                                <span style="display: inline-flex; align-items: center; gap: 0.3rem; background: #e3f2fd; color: #1976d2; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem;">
                                    ${opcion}
                                    <button type="button" onclick="eliminarUbicacionItem(${idx}, ${opIdx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>
                                </span>
                            `).join('')}
                        </div>
                    </td>
                    <td style="padding: 0.75rem 1rem; border-right: 1px solid #cbd5e1;">
                        <textarea class="logo-ubicacion-obs" data-ubicacion-idx="${idx}" style="width: 100%; padding: 0.5rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.85rem; min-height: 40px; resize: vertical; font-family: inherit;" placeholder="Observaciones...">${seccion.observaciones || ''}</textarea>
                    </td>
                    <td style="padding: 0.75rem 1rem; text-align: center;">
                        <button type="button" onclick="eliminarSeccionLogo(${idx})" style="background: #dc3545; color: white; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s;">✕ Eliminar</button>
                    </td>
                </tr>`;
            });
        } else {
            html += `<tr><td colspan="4" style="padding: 1rem; text-align: center; color: #999;">Sin ubicaciones definidas. Agrega una haciendo clic en el botón +</td></tr>`;
        }
        
        html += `</tbody>
            </table>
        </div>`;
        
        html += `</div>`;
        
        // ✅ AGREGAR ATRIBUTO data-tipo-cotizacion AL CONTENEDOR
        prendasContainer.setAttribute('data-tipo-cotizacion', tipoCotizacion);
        prendasContainer.innerHTML = html;
        
        // Renderizar datos cargados
        renderizarFotosLogo();
        renderizarTecnicasLogo();
        renderizarSeccionesLogo();
        
        // ====== AGREGAR EVENT LISTENERS PARA CAPTURAR CAMBIOS EN TABLA ======
        // Listeners para tallas
        setTimeout(() => {
            const tallasInputs = document.querySelectorAll('.logo-talla-nombre, .logo-talla-cantidad');
            tallasInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const idx = parseInt(this.dataset.tallaIdx);
                    const fila = this.closest('tr');
                    if (fila) {
                        const nombreInput = fila.querySelector('.logo-talla-nombre');
                        const cantidadInput = fila.querySelector('.logo-talla-cantidad');
                        if (nombreInput && cantidadInput && tallasArray[idx]) {
                            tallasArray[idx].talla = nombreInput.value.trim().toUpperCase();
                            tallasArray[idx].cantidad = parseInt(cantidadInput.value) || 0;
                        }
                    }
                });
            });
            
            // Listeners para ubicaciones
            const ubicacionNombres = document.querySelectorAll('.logo-ubicacion-nombre');
            const ubicacionObs = document.querySelectorAll('.logo-ubicacion-obs');
            
            ubicacionNombres.forEach(input => {
                input.addEventListener('change', function() {
                    const idx = parseInt(this.dataset.ubicacionIdx);
                    if (logoSeccionesSeleccionadas[idx]) {
                        logoSeccionesSeleccionadas[idx].ubicacion = this.value.trim().toUpperCase();
                    }
                });
            });
            
            ubicacionObs.forEach(input => {
                input.addEventListener('change', function() {
                    const idx = parseInt(this.dataset.ubicacionIdx);
                    if (logoSeccionesSeleccionadas[idx]) {
                        logoSeccionesSeleccionadas[idx].observaciones = this.value;
                    }
                });
            });
        }, 100);
        
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
    
    // ====== FUNCIONES DE ACTUALIZACIÓN DE TALLAS Y UBICACIONES EN TABLA ======
    window.eliminarTallaLogo = function(index) {
        // Obtener todas las tallas del formulario y eliminar por índice
        const tbody = document.getElementById('logo-tallas-tbody');
        if (tbody && tbody.rows[index]) {
            tbody.deleteRow(index);
        }
    };
    
    window.eliminarUbicacionItem = function(ubicacionIdx, itemIdx) {
        if (logoSeccionesSeleccionadas[ubicacionIdx]) {
            logoSeccionesSeleccionadas[ubicacionIdx].opciones.splice(itemIdx, 1);
            // Re-renderizar la tabla
            const tbody = document.getElementById('logo-ubicaciones-tbody');
            const fila = tbody.rows[ubicacionIdx];
            if (fila) {
                const ubicacionesText = logoSeccionesSeleccionadas[ubicacionIdx].opciones.join(', ');
                const celda = fila.cells[1];
                if (celda) {
                    celda.innerHTML = `<div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                        ${logoSeccionesSeleccionadas[ubicacionIdx].opciones.map((opcion, opIdx) => `
                            <span style="display: inline-flex; align-items: center; gap: 0.3rem; background: #e3f2fd; color: #1976d2; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem;">
                                ${opcion}
                                <button type="button" onclick="eliminarUbicacionItem(${ubicacionIdx}, ${opIdx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>
                            </span>
                        `).join('')}
                    </div>`;
                }
            }
        }
    };
    
    window.agregarUbicacionNueva = function(ubicacionIdx) {
        const input = document.getElementById(`logo-ubicaciones-tbody`).rows[ubicacionIdx]?.cells[1]?.querySelector('input[data-field="ubicacion_nueva"]');
        if (input && input.value.trim()) {
            const nuevaUbicacion = input.value.trim().toUpperCase();
            if (!logoSeccionesSeleccionadas[ubicacionIdx].opciones.includes(nuevaUbicacion)) {
                logoSeccionesSeleccionadas[ubicacionIdx].opciones.push(nuevaUbicacion);
                input.value = '';
                // Re-renderizar
                const celda = input.closest('td');
                if (celda) {
                    celda.innerHTML = `<div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                        ${logoSeccionesSeleccionadas[ubicacionIdx].opciones.map((opcion, opIdx) => `
                            <span style="display: inline-flex; align-items: center; gap: 0.3rem; background: #e3f2fd; color: #1976d2; padding: 0.3rem 0.6rem; border-radius: 3px; font-size: 0.8rem;">
                                ${opcion}
                                <button type="button" onclick="eliminarUbicacionItem(${ubicacionIdx}, ${opIdx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>
                            </span>
                        `).join('')}
                    </div>`;
                }
            }
        }
    };

    // ====== FUNCIONES DE OBSERVACIONES LOGO ======
    window.agregarObservacionLogo = function() {
        logoObservacionesGenerales.push('');
        renderizarObservacionesLogo();
    };

    // ====== FUNCIONES PARA TAB LOGO EN COTIZACIONES COMBINADAS ======
    // eliminarTallaLogoTab() - FUNCIÓN ANTIGUA ELIMINADA
    // Las tallas ahora se eliminan desde el modal de cada sección
    

    // Función auxiliar para generar UUID
    window.generarUUID = function() {
        return 'sec_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    };

    window.agregarSeccionLogoTab = function() {
        const input = document.getElementById('seccion_prenda_logo_tab');
        const seccion = input.value.trim().toUpperCase();
        
        if (!seccion) {
            input.style.border = '2px solid #ef4444';
            input.style.background = '#fee2e2';
            input.classList.add('shake');
            
            setTimeout(() => {
                input.style.border = '1px solid #d0d0d0';
                input.style.background = '';
                input.classList.remove('shake');
            }, 600);
            
            Swal.fire({
                icon: 'warning',
                title: 'Campo vacío',
                text: 'Por favor escribe el nombre de la sección',
                timer: 2000
            });
            return;
        }
        
        // Verificar si ya existe
        if (!window.logoSeccionesSeleccionadasTab) {
            window.logoSeccionesSeleccionadasTab = [];
        }
        
        if (window.logoSeccionesSeleccionadasTab.some(s => s.ubicacion.toUpperCase() === seccion)) {
            input.style.border = '2px solid #ef4444';
            input.style.background = '#fee2e2';
            
            setTimeout(() => {
                input.style.border = '1px solid #d0d0d0';
                input.style.background = '';
            }, 600);
            
            Swal.fire({
                icon: 'info',
                title: 'Sección duplicada',
                text: 'Esta sección ya existe',
                timer: 2000
            });
            return;
        }
        
        // Limpiar el input
        input.value = '';
        input.style.border = '1px solid #d0d0d0';
        input.style.background = '';
        
        // Obtener opciones disponibles para esta sección
        const logoOpcionesDisponibles = window.logoOpcionesPorUbicacion[seccion] || [];
        
        // Crear ID único para la nueva sección
        const seccionId = window.generarUUID();
        
        // Guardar temporalmente
        window.logoSeccionTempTab = {
            id: seccionId,
            ubicacion: seccion,
            opciones: [],
            tallas: [],
            tallasCantidad: {},
            observaciones: ''
        };
        
        // Abrir el modal de edición directamente (sin intermediate modal)
        abrirModalSeccionEditarTab(seccion, logoOpcionesDisponibles, null);
    };

    window.abrirModalSeccionEditarTab = function(ubicacion, opcionesDisponibles, seccionData) {
        // seccionData será null si es crear, o un objeto con datos si es editar
        const isEditar = seccionData !== null;
        const tituloModal = isEditar ? 'Editar Sección' : 'Configurar Sección';
        const textoBtnGuardar = isEditar ? '✓ Actualizar' : '✓ Guardar';
        const fnGuardar = isEditar ? 'guardarSeccionTabEdicion()' : 'guardarSeccionTab()';
        
        let html = `
            <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;" id="modalSeccionTab">
                <div style="background: white; border-radius: 12px; padding: 2rem; max-width: 550px; width: 100%; box-shadow: 0 20px 50px rgba(0,0,0,0.3); max-height: 85vh; overflow-y: auto;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h2 style="margin: 0; color: #1e40af; font-size: 1.25rem; font-weight: 700;">${tituloModal}</h2>
                        <button type="button" onclick="cerrarModalSeccionTab()" style="background: none; border: none; color: #999; font-size: 1.6rem; cursor: pointer; padding: 0; width: 30px; height: 30px;">×</button>
                    </div>
                    
                    <!-- 1. Nombre de la Sección -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e40af; font-size: 0.9rem;">1. Nombre de la Sección</label>
                        <input type="text" id="nombreSeccionTab" value="${ubicacion}" style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box;" ${isEditar ? 'readonly' : ''}>
                    </div>
                    
                    <!-- 2. Ubicaciones -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.9rem;">2. Ubicaciones</label>
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <input type="text" id="inputUbicacionTab" placeholder="Busca o escribe una ubicación..." list="opcionesUbicacionList" style="flex: 1; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.9rem; box-sizing: border-box;">
                            <button type="button" onclick="agregarUbicacionDesdeInputTab()" style="background: #27ae60; color: white; border: none; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem; white-space: nowrap;">✓ Agregar</button>
                        </div>
                        <datalist id="opcionesUbicacionList"></datalist>
                        <div id="opcionesSeccionTab" style="display: flex; flex-direction: column; gap: 0.4rem; padding: 1rem; background: #f9f9f9; border-radius: 6px; min-height: 50px; max-height: 200px; overflow-y: auto;"></div>
                    </div>
                    
                    <!-- 3. Tallas -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1e40af; font-size: 0.9rem;">3. Tallas</label>
                        <div id="tallasSeccionTab" style="display: flex; flex-wrap: wrap; gap: 0.5rem; padding: 1rem; background: #f9f9f9; border-radius: 6px; min-height: 45px;"></div>
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.75rem; align-items: flex-start;">
                            <input type="text" id="nuevaTallaTab" placeholder="Ej: S, M, L, XL" style="flex: 1; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.85rem; box-sizing: border-box;">
                            <input type="number" id="nuevaTallaCantidadTab" placeholder="Cant." min="1" value="1" style="width: 70px; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.85rem; box-sizing: border-box;">
                            <button type="button" onclick="agregarTallaSeccionTab()" style="background: #3b82f6; color: white; border: none; padding: 0.6rem 1rem; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem; white-space: nowrap;">+ Agregar</button>
                        </div>
                    </div>
                    
                    <!-- 4. Observaciones -->
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #1e40af; font-size: 0.9rem;">4. Observaciones</label>
                        <textarea id="obsSeccionTab" placeholder="Notas importantes..." style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 6px; font-size: 0.85rem; min-height: 70px; box-sizing: border-box; font-family: inherit; resize: none;">${seccionData && seccionData.observaciones ? seccionData.observaciones : ''}</textarea>
                    </div>
                    
                    <!-- Botones -->
                    <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                        <button type="button" onclick="cerrarModalSeccionTab()" style="background: #f0f0f0; color: #333; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem;">Cancelar</button>
                        <button type="button" onclick="${fnGuardar}" style="background: #0066cc; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9rem;">${textoBtnGuardar}</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', html);
        
        // Agregar opciones disponibles
        setTimeout(() => {
            // Llenar el datalist con opciones disponibles
            const datalist = document.getElementById('opcionesUbicacionList');
            if (datalist && opcionesDisponibles.length > 0) {
                opcionesDisponibles.forEach(opcion => {
                    const option = document.createElement('option');
                    option.value = opcion;
                    datalist.appendChild(option);
                });
            }
            
            // Cargar ubicaciones existentes si estamos editando
            const container = document.getElementById('opcionesSeccionTab');
            if (container && seccionData && seccionData.opciones && seccionData.opciones.length > 0) {
                seccionData.opciones.forEach(opcion => {
                    const label = document.createElement('label');
                    label.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 4px; background: #dbeafe; border: 1px solid #bfdbfe; transition: all 0.2s; font-size: 0.85rem;';
                    label.innerHTML = `
                        <input type="checkbox" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;" class="opcion-seccion-tab">
                        <span style="flex: 1; color: #1e40af; font-weight: 500;">${opcion}</span>
                        <button type="button" onclick="this.parentElement.remove(); window.logoSeccionTempTab.opciones = window.logoSeccionTempTab.opciones.filter(o => o !== '${opcion}');" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; font-size: 0.9rem; font-weight: bold;">×</button>
                    `;
                    label.addEventListener('mouseover', () => label.style.background = '#c8e6f5');
                    label.addEventListener('mouseout', () => label.style.background = '#dbeafe');
                    container.appendChild(label);
                });
            }
        }, 10);
        
        // Cargar tallas existentes si estamos editando
        setTimeout(() => {
            const container = document.getElementById('tallasSeccionTab');
            if (container && seccionData && seccionData.tallas && seccionData.tallas.length > 0) {
                seccionData.tallas.forEach(talla => {
                    const cantidad = seccionData.tallasCantidad && seccionData.tallasCantidad[talla] ? seccionData.tallasCantidad[talla] : 0;
                    const chip = document.createElement('span');
                    chip.style.cssText = 'display: inline-flex; align-items: center; gap: 0.4rem; background: #dbeafe; color: #1e40af; padding: 0.3rem 0.8rem; border-radius: 16px; font-size: 0.8rem; font-weight: 500;';
                    chip.innerHTML = `${talla} (${cantidad}) <button type="button" onclick="eliminarTallaSeccionTab('${talla}')" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>`;
                    container.appendChild(chip);
                });
            }
        }, 10);
    };
    
    window.agregarUbicacionDesdeInputTab = function() {
        const input = document.getElementById('inputUbicacionTab');
        const valor = input.value.trim().toUpperCase();
        
        if (!valor) {
            Swal.fire({
                icon: 'warning',
                title: 'Escribe una ubicación',
                timer: 1500
            });
            return;
        }
        
        // Verificar si ya existe
        if (window.logoSeccionTempTab.opciones.includes(valor)) {
            Swal.fire({
                icon: 'info',
                title: 'Ubicación duplicada',
                text: 'Esta ubicación ya fue agregada',
                timer: 1500
            });
            return;
        }
        
        // Agregar a opciones temporales
        window.logoSeccionTempTab.opciones.push(valor);
        
        // Mostrar en el contenedor
        const container = document.getElementById('opcionesSeccionTab');
        const label = document.createElement('label');
        label.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 4px; background: #dbeafe; border: 1px solid #bfdbfe; transition: all 0.2s; font-size: 0.85rem;';
        label.innerHTML = `
            <input type="checkbox" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: #0066cc;" class="opcion-seccion-tab">
            <span style="flex: 1; color: #1e40af; font-weight: 500;">${valor}</span>
            <button type="button" onclick="this.parentElement.remove(); window.logoSeccionTempTab.opciones = window.logoSeccionTempTab.opciones.filter(o => o !== '${valor}');" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0; font-size: 0.9rem; font-weight: bold;">×</button>
        `;
        container.appendChild(label);
        
        // Resetear input
        input.value = '';
        input.focus();
    };


    window.agregarOpcionSeccionTab = function() {
        const input = document.getElementById('nuevaOpcionTab');
        const valor = input.value.trim().toUpperCase();
        if (!valor) return;
        
        if (!window.logoSeccionTempTab.opciones.includes(valor)) {
            window.logoSeccionTempTab.opciones.push(valor);
            const container = document.getElementById('opcionesSeccionTab');
            const label = document.createElement('label');
            label.style.cssText = 'display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.5rem; border-radius: 4px; background: #dbeafe; border: 1px solid #bfdbfe; transition: all 0.2s; font-size: 0.85rem;';
            label.innerHTML = `
                <input type="checkbox" checked style="width: 18px; height: 18px; cursor: pointer; accent-color: #1e40af;" class="opcion-seccion-tab">
                <span style="flex: 1; color: #1e40af; font-weight: 500;">${valor}</span>
                <button type="button" onclick="this.parentElement.remove(); window.logoSeccionTempTab.opciones = window.logoSeccionTempTab.opciones.filter(o => o !== '${valor}');" style="background: none; border: none; color: #1e40af; cursor: pointer; padding: 0; font-size: 0.9rem;">×</button>
            `;
            container.appendChild(label);
            input.value = '';
            input.focus();
        }
    };
    
    window.agregarTallaSeccionTab = function() {
        const inputTalla = document.getElementById('nuevaTallaTab');
        const inputCantidad = document.getElementById('nuevaTallaCantidadTab');
        const container = document.getElementById('tallasSeccionTab');
        
        if (!inputTalla || !inputCantidad || !container) {
            console.error('Elementos del modal no encontrados');
            return;
        }
        
        const talla = inputTalla.value.trim().toUpperCase();
        const cantidad = parseInt(inputCantidad.value) || 0;
        
        if (!talla || cantidad <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Datos inválidos',
                text: 'Ingresa una talla y una cantidad mayor a 0',
                timer: 2000
            });
            return;
        }
        
        if (!window.logoSeccionTempTab) {
            window.logoSeccionTempTab = {
                id: window.generarUUID(),
                opciones: [],
                tallas: [],
                tallasCantidad: {},
                observaciones: ''
            };
        }
        
        if (!window.logoSeccionTempTab.tallasCantidad) {
            window.logoSeccionTempTab.tallasCantidad = {};
        }
        
        if (!window.logoSeccionTempTab.tallas.includes(talla)) {
            window.logoSeccionTempTab.tallas.push(talla);
            window.logoSeccionTempTab.tallasCantidad[talla] = cantidad;
            
            const chip = document.createElement('span');
            chip.style.cssText = 'display: inline-flex; align-items: center; gap: 0.4rem; background: #dbeafe; color: #1e40af; padding: 0.3rem 0.8rem; border-radius: 16px; font-size: 0.8rem; font-weight: 500;';
            chip.id = `chip-${talla}`;
            chip.innerHTML = `${talla} (${cantidad}) <button type="button" onclick="eliminarTallaSeccionTab('${talla}')" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.9rem;">×</button>`;
            container.appendChild(chip);
            inputTalla.value = '';
            inputCantidad.value = '1';
            inputTalla.focus();
            
            console.log('Talla agregada:', talla, 'Cantidad:', cantidad);
        } else {
            Swal.fire({
                icon: 'info',
                title: 'Talla duplicada',
                text: 'Esta talla ya fue agregada',
                timer: 2000
            });
        }
    };
    
    window.eliminarTallaSeccionTab = function(talla) {
        if (window.logoSeccionTempTab.tallas.includes(talla)) {
            window.logoSeccionTempTab.tallas = window.logoSeccionTempTab.tallas.filter(t => t !== talla);
            if (window.logoSeccionTempTab.tallasCantidad) {
                delete window.logoSeccionTempTab.tallasCantidad[talla];
            }
            // Re-renderizar chip
            const container = document.getElementById('tallasSeccionTab');
            if (container) {
                const chip = Array.from(container.children).find(c => c.textContent.includes(talla));
                if (chip) chip.remove();
            }
        }
    };
    
    window.eliminarTallaDelModalTab = function(talla) {
        if (window.logoSeccionTempTab.tallas.includes(talla)) {
            window.logoSeccionTempTab.tallas = window.logoSeccionTempTab.tallas.filter(t => t !== talla);
            if (window.logoSeccionTempTab.tallasCantidad) {
                delete window.logoSeccionTempTab.tallasCantidad[talla];
            }
        }
    };
    
    window.guardarSeccionTab = function() {
        const nombreInput = document.getElementById('nombreSeccionTab');
        const obsInput = document.getElementById('obsSeccionTab');
        
        const seccionName = nombreInput.value.trim().toUpperCase() || window.logoSeccionTempTab.ubicacion;
        window.logoSeccionTempTab.ubicacion = seccionName;
        window.logoSeccionTempTab.observaciones = obsInput.value.trim();
        
        // Las opciones ya están en window.logoSeccionTempTab.opciones (se actualizan dinámicamente)
        
        // Agregar al array global
        if (!window.logoSeccionesSeleccionadasTab) {
            window.logoSeccionesSeleccionadasTab = [];
        }
        window.logoSeccionesSeleccionadasTab.push(window.logoSeccionTempTab);
        
        // Renderizar la fila en la tabla
        const tbody = document.getElementById('logo-ubicaciones-tbody-tab');
        if (tbody) {
            const seccionId = window.logoSeccionTempTab.id;
            // Mostrar tallas con cantidades
            const tallasText = window.logoSeccionTempTab.tallas && window.logoSeccionTempTab.tallas.length > 0 
                ? window.logoSeccionTempTab.tallas.map(t => `${t} (${window.logoSeccionTempTab.tallasCantidad && window.logoSeccionTempTab.tallasCantidad[t] ? window.logoSeccionTempTab.tallasCantidad[t] : 0})`).join(', ')
                : '—';
            const tr = document.createElement('tr');
            tr.style.cssText = 'border-bottom: 1px solid #e5e7eb; transition: all 0.2s;';
            tr.onmouseover = function() { this.style.backgroundColor = '#f9fafb'; };
            tr.onmouseout = function() { this.style.backgroundColor = 'white'; };
            tr.setAttribute('data-seccion-id', seccionId);
            tr.innerHTML = `
                <td style="padding: 0.75rem; font-weight: 500; color: #1f2937;">
                    <input type="text" value="${window.logoSeccionTempTab.ubicacion}" class="logo-ubicacion-nombre-tab" data-seccion-id="${seccionId}" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.8rem; background: #f5f5f5; box-sizing: border-box;"
                           onfocus="this.style.borderColor = '#0066cc'; this.style.backgroundColor = 'white';"
                           onblur="this.style.borderColor = '#d0d0d0'; this.style.backgroundColor = '#f5f5f5';">
                </td>
                <td style="padding: 0.75rem; color: #666; font-size: 0.75rem;">
                    <span style="display: inline-block; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${tallasText}">${tallasText}</span>
                </td>
                <td style="padding: 0.75rem;">
                    <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;" id="opciones-${seccionId}">
                        ${window.logoSeccionTempTab.opciones.map((opcion, opIdx) => `
                            <span style="display: inline-flex; align-items: center; gap: 0.2rem; background: #dbeafe; color: #1e40af; padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.7rem; font-weight: 500;">
                                ${opcion}
                                <button type="button" onclick="eliminarUbicacionItemTab('${seccionId}', ${opIdx})" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.8rem; line-height: 1; margin-left: 0.2rem;">×</button>
                            </span>
                        `).join('')}
                    </div>
                </td>
                <td style="padding: 0.75rem;">
                    <textarea class="logo-ubicacion-obs-tab" data-seccion-id="${seccionId}" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.75rem; min-height: 45px; resize: none; font-family: inherit; background: #fafafa; box-sizing: border-box;"
                              onfocus="this.style.borderColor = '#0066cc'; this.style.backgroundColor = 'white';"
                              onblur="this.style.borderColor = '#d0d0d0'; this.style.backgroundColor = '#fafafa';"
                              placeholder="...">${window.logoSeccionTempTab.observaciones}</textarea>
                </td>
                <td style="padding: 0.75rem; text-align: center; display: flex; gap: 0.4rem; justify-content: center;">
                    <button type="button" onclick="editarSeccionLogoTab('${seccionId}')" 
                            style="background: #0066cc; color: white; border: none; padding: 0.4rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600; transition: all 0.3s; white-space: nowrap;">
                        ✏ Editar
                    </button>
                    <button type="button" onclick="eliminarSeccionLogoTab('${seccionId}')" 
                            style="background: #ef4444; color: white; border: none; padding: 0.4rem 0.6rem; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: 600; transition: all 0.3s; white-space: nowrap;">
                        ✕ Eliminar
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        }
        
        cerrarModalSeccionTab();
    };
    
    window.cerrarModalSeccionTab = function() {
        const modal = document.getElementById('modalSeccionTab');
        if (modal) modal.remove();
        window.logoSeccionTempTab = null;
        window.logoSeccionEditIdTab = null;
    };

    window.editarSeccionLogoTab = function(seccionId) {
        const seccion = window.logoSeccionesSeleccionadasTab.find(s => s.id === seccionId);
        if (!seccion) return;
        
        // Marcar que estamos en modo edición
        window.logoSeccionEditIdTab = seccionId;
        
        // Crear objeto temporal con datos de la sección
        window.logoSeccionTempTab = {
            id: seccion.id,
            ubicacion: seccion.ubicacion || '',
            opciones: [...(seccion.opciones || [])],
            tallas: [...(seccion.tallas || [])],
            tallasCantidad: { ...(seccion.tallasCantidad || {}) },
            observaciones: seccion.observaciones || ''
        };
        
        // Obtener opciones disponibles
        const opcionesDisponibles = window.logoOpcionesPorUbicacion[seccion.ubicacion] || [];
        
        // Abrir modal con datos precargados - pasar los datos de la sección
        abrirModalSeccionEditarTab(seccion.ubicacion, opcionesDisponibles, seccion);
    };

    // Función duplicada eliminada - se usa abrirModalSeccionEditarTab en su lugar
    


    window.guardarSeccionTabEdicion = function() {
        const nombreInput = document.getElementById('nombreSeccionTab');
        const obsInput = document.getElementById('obsSeccionTab');
        
        const seccionName = nombreInput.value.trim().toUpperCase() || window.logoSeccionTempTab.ubicacion;
        window.logoSeccionTempTab.ubicacion = seccionName;
        window.logoSeccionTempTab.observaciones = obsInput.value.trim();
        
        // Las opciones ya están en window.logoSeccionTempTab.opciones (se actualizan dinámicamente)
        
        // Actualizar en el array global
        const index = window.logoSeccionesSeleccionadasTab.findIndex(s => s.id === window.logoSeccionEditIdTab);
        if (index !== -1) {
            window.logoSeccionesSeleccionadasTab[index] = window.logoSeccionTempTab;
        }
        
        // Re-renderizar la fila en la tabla
        const seccionId = window.logoSeccionTempTab.id;
        const tallasText = window.logoSeccionTempTab.tallas && window.logoSeccionTempTab.tallas.length > 0 
            ? window.logoSeccionTempTab.tallas.map(t => `${t} (${window.logoSeccionTempTab.tallasCantidad && window.logoSeccionTempTab.tallasCantidad[t] ? window.logoSeccionTempTab.tallasCantidad[t] : 0})`).join(', ')
            : '—';
        
        // Actualizar la fila en la tabla
        const filaExistente = document.querySelector(`tr[data-seccion-id="${seccionId}"]`);
        if (filaExistente) {
            // Actualizar nombre
            filaExistente.children[0].innerHTML = `
                <input type="text" value="${window.logoSeccionTempTab.ubicacion}" class="logo-ubicacion-nombre-tab" data-seccion-id="${seccionId}" style="width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.8rem; background: #f5f5f5; box-sizing: border-box;"
                       onfocus="this.style.borderColor = '#0066cc'; this.style.backgroundColor = 'white';"
                       onblur="this.style.borderColor = '#d0d0d0'; this.style.backgroundColor = '#f5f5f5';">
            `;
            
            // Actualizar tallas
            filaExistente.children[1].innerHTML = tallasText;
            
            // Actualizar ubicaciones
            const opcionesDiv = filaExistente.children[2].querySelector(`div`) || filaExistente.children[2];
            if (opcionesDiv) {
                opcionesDiv.innerHTML = `
                    <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;" id="opciones-${seccionId}">
                        ${window.logoSeccionTempTab.opciones && window.logoSeccionTempTab.opciones.length > 0 ? window.logoSeccionTempTab.opciones.map((opcion, opIdx) => `
                            <span style="display: inline-flex; align-items: center; gap: 0.2rem; background: #dbeafe; color: #1e40af; padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.7rem; font-weight: 500;">
                                ${opcion}
                                <button type="button" onclick="eliminarUbicacionItemTab('${seccionId}', ${opIdx})" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 0.8rem; line-height: 1; margin-left: 0.2rem;">×</button>
                            </span>
                        `).join('') : '<span style="color: #999; font-size: 0.75rem;">Sin ubicaciones</span>'}
                    </div>
                `;
            }
            
            // Actualizar observaciones (encontrar textarea)
            const textareaExistente = filaExistente.querySelector('textarea.logo-ubicacion-obs-tab');
            if (textareaExistente) {
                textareaExistente.value = window.logoSeccionTempTab.observaciones;
            }
        }
        
        cerrarModalSeccionTab();
    };

    window.editarSeccionLogoTab = function(seccionId) {
        if (!window.logoSeccionesSeleccionadasTab) return;
        
        // Buscar la sección a editar
        const seccion = window.logoSeccionesSeleccionadasTab.find(s => s.id === seccionId);
        if (!seccion) return;
        
        // Guardar el ID para saber que estamos editando
        window.logoSeccionEditIdTab = seccionId;
        
        // Cargar los datos en la variable temporal
        window.logoSeccionTempTab = { ...seccion };
        
        // Obtener opciones disponibles
        const logoOpcionesDisponibles = window.logoOpcionesPorUbicacion[seccion.ubicacion] || [];
        
        // Abrir el modal en modo edición
        abrirModalSeccionEditarTab(seccion.ubicacion, logoOpcionesDisponibles, seccion);
    };

    window.eliminarSeccionLogoTab = function(seccionId) {
        if (!window.logoSeccionesSeleccionadasTab) return;
        
        // Eliminar del array por ID
        window.logoSeccionesSeleccionadasTab = window.logoSeccionesSeleccionadasTab.filter(s => s.id !== seccionId);
        
        // Eliminar de la tabla por ID
        const tbody = document.getElementById('logo-ubicaciones-tbody-tab');
        if (tbody) {
            const filaAEliminar = document.querySelector(`tr[data-seccion-id="${seccionId}"]`);
            if (filaAEliminar) {
                filaAEliminar.remove();
            }
        }
    };

    window.eliminarUbicacionItemTab = function(seccionId, itemIdx) {
        if (!window.logoSeccionesSeleccionadasTab) return;
        
        // Buscar la sección por ID
        const seccion = window.logoSeccionesSeleccionadasTab.find(s => s.id === seccionId);
        if (!seccion) return;
        
        seccion.opciones.splice(itemIdx, 1);
        
        // Re-renderizar las ubicaciones en el contenedor específico
        const opcionesDiv = document.getElementById(`opciones-${seccionId}`);
        if (opcionesDiv) {
            opcionesDiv.innerHTML = `
                ${seccion.opciones.map((opcion, opIdx) => `
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; box-shadow: 0 2px 4px rgba(30,64,175,0.1);">
                        ${opcion}
                        <button type="button" onclick="eliminarUbicacionItemTab('${seccionId}', ${opIdx})" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 1rem; transition: all 0.2s; line-height: 1;" onmouseover="this.style.transform = 'scale(1.3)';" onmouseout="this.style.transform = 'scale(1)';">×</button>
                    </span>
                `).join('')}
            `;
        }
    };

    window.agregarTecnicaTabLogo = function() {
        const select = document.getElementById('selector_tecnicas_logo');
        const tecnicaValue = select.value.trim();
        
        if (!tecnicaValue) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona una técnica',
                text: 'Debes seleccionar una técnica antes de agregarla',
                timer: 2000
            });
            return;
        }
        
        // Obtener el array global de técnicas (crear si no existe)
        if (!window.logoTecnicasSeleccionadasTab) {
            window.logoTecnicasSeleccionadasTab = [];
        }
        
        // Verificar que no esté duplicada
        if (window.logoTecnicasSeleccionadasTab.includes(tecnicaValue)) {
            Swal.fire({
                icon: 'info',
                title: 'Técnica duplicada',
                text: 'Esta técnica ya ha sido agregada',
                timer: 2000
            });
            return;
        }
        
        window.logoTecnicasSeleccionadasTab.push(tecnicaValue);
        
        const tecnicasDiv = document.getElementById('tecnicas_seleccionadas_logo');
        if (tecnicasDiv) {
            const span = document.createElement('span');
            span.style.cssText = 'display: inline-flex; align-items: center; gap: 0.5rem; background: #e3f2fd; color: #1976d2; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.9rem; font-weight: 600;';
            const idx = window.logoTecnicasSeleccionadasTab.length - 1;
            span.innerHTML = `
                ${tecnicaValue}
                <button type="button" onclick="eliminarTecnicaDelTabLogo(${idx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-size: 1.2rem; padding: 0;">×</button>
            `;
            tecnicasDiv.appendChild(span);
        }
        
        select.value = '';
    };

    // agregarTallaAlTab() - FUNCIÓN ANTIGUA ELIMINADA
    // Las tallas ahora se agregan en el modal de cada sección
    

    window.agregarUbicacionNuevaTab = function(seccionId) {
        const input = document.querySelector(`.ubicacion-nueva-input-tab[data-seccion-id="${seccionId}"]`);
        if (!input || !input.value.trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Ingresa una ubicación',
                text: 'Por favor escribe el nombre de la ubicación',
                timer: 2000
            });
            return;
        }
        
        const nuevaUbicacion = input.value.trim().toUpperCase();
        
        if (!window.logoSeccionesSeleccionadasTab) {
            window.logoSeccionesSeleccionadasTab = [];
        }
        
        // Buscar la sección por ID
        const seccion = window.logoSeccionesSeleccionadasTab.find(s => s.id === seccionId);
        if (!seccion) return;
        
        if (seccion.opciones.includes(nuevaUbicacion)) {
            Swal.fire({
                icon: 'info',
                title: 'Ubicación duplicada',
                text: 'Esta ubicación ya existe en esta sección',
                timer: 2000
            });
            return;
        }
        
        seccion.opciones.push(nuevaUbicacion);
        input.value = '';
        input.focus();
        
        // Re-renderizar las ubicaciones en el contenedor específico
        const opcionesDiv = document.getElementById(`opciones-${seccionId}`);
        if (opcionesDiv) {
            opcionesDiv.innerHTML = `
                ${seccion.opciones.map((opcion, opIdx) => `
                    <span style="display: inline-flex; align-items: center; gap: 0.4rem; background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.85rem; font-weight: 500; box-shadow: 0 2px 4px rgba(30,64,175,0.1);">
                        ${opcion}
                        <button type="button" onclick="eliminarUbicacionItemTab('${seccionId}', ${opIdx})" style="background: none; border: none; color: #1e40af; cursor: pointer; font-weight: bold; padding: 0; font-size: 1rem; transition: all 0.2s; line-height: 1;" onmouseover="this.style.transform = 'scale(1.3)';" onmouseout="this.style.transform = 'scale(1)';">×</button>
                    </span>
                `).join('')}
            `;
        }
    };

    window.eliminarTecnicaDelTabLogo = function(index) {
        if (!window.logoTecnicasSeleccionadasTab) return;
        window.logoTecnicasSeleccionadasTab.splice(index, 1);
        
        const tecnicasDiv = document.getElementById('tecnicas_seleccionadas_logo');
        if (tecnicasDiv) {
            tecnicasDiv.innerHTML = '';
            if (window.logoTecnicasSeleccionadasTab.length > 0) {
                window.logoTecnicasSeleccionadasTab.forEach((tecnica, idx) => {
                    const span = document.createElement('span');
                    span.style.cssText = 'display: inline-flex; align-items: center; gap: 0.5rem; background: #e3f2fd; color: #1976d2; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.9rem; font-weight: 600;';
                    span.innerHTML = `
                        ${tecnica}
                        <button type="button" onclick="eliminarTecnicaDelTabLogo(${idx})" style="background: none; border: none; color: #1976d2; cursor: pointer; font-size: 1.2rem; padding: 0;">×</button>
                    `;
                    tecnicasDiv.appendChild(span);
                });
            }
        }
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
                    // El contenedor es un div con display: grid que es el padre directo del input
                    const tallaRow = input.closest('div[style*="display: grid"]');
                    if (tallaRow) {
                        tallaRow.remove();
                        console.log(`✅ Talla ${talla} removida de prenda ${prendaIndex}`);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Talla eliminada',
                            text: `La talla ${talla} ha sido removida`,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        console.error('No se encontró el contenedor de talla');
                    }
                } else {
                    console.error('No se encontró el input de talla');
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

        // ✅ DETECTAR TIPO DE COTIZACIÓN Y SI TIENE LOGO
        const tipoCotizacionElement = document.querySelector('[data-tipo-cotizacion]');
        const tipoCotizacion = tipoCotizacionElement?.dataset.tipoCotizacion || 'P';
        
        const esLogo = logoTecnicasSeleccionadas.length > 0 || 
                       logoSeccionesSeleccionadas.length > 0 || 
                       logoFotosSeleccionadas.length > 0;
        
        const esCombinada = tipoCotizacion === 'PL';
        const esLogoSolo = tipoCotizacion === 'L';

        console.log('🎯 Análisis de cotización:', {
            tipoCotizacion: tipoCotizacion,
            esCombinada: esCombinada,
            esLogoSolo: esLogoSolo,
            esLogo: esLogo,
            logoTecnicas: logoTecnicasSeleccionadas.length,
            logoSecciones: logoSeccionesSeleccionadas.length,
            logoFotos: logoFotosSeleccionadas.length
        });

        if (esLogoSolo || esCombinada) {
            // ============================================================
            // FLUJO PARA LOGO SOLO (Tipo L) o COMBINADA (Tipo PL)
            // ============================================================
            if (esLogoSolo) {
                console.log('🎨 [LOGO SOLO] Preparando datos de LOGO para enviar');
            } else {
                console.log('🎨 [COMBINADA PL] Preparando pedidos de PRENDAS y LOGO para enviar');
            }

            // Para COMBINADA (PL), preparar prendas; para LOGO SOLO, enviar vacío
            let prendasParaEnviar = [];
            if (esCombinada) {
                // Recopilar prendas igual que en el flujo normal
                prendasCargadas.forEach((prenda, index) => {
                    if (prendasEliminadas.has(index)) {
                        console.log(`Saltando prenda eliminada: ${index}`);
                        return;
                    }

                    const prendasCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${index}"]`);
                    if (!prendasCard) return;
                    
                    const tallasInputs = prendasCard.querySelectorAll('.talla-input');
                    const cantidadesPorTalla = {};
                    
                    tallasInputs.forEach(input => {
                        const talla = input.closest('.talla-group')?.getAttribute('data-talla');
                        const cantidad = parseInt(input.value) || 0;
                        if (talla && cantidad > 0) {
                            cantidadesPorTalla[talla] = cantidad;
                        }
                    });
                    
                    prendasParaEnviar.push({
                        index: index,
                        nombre_producto: prenda.nombre_producto,
                        cantidades: cantidadesPorTalla
                    });
                });
                console.log('📦 [COMBINADA] Prendas a enviar:', prendasParaEnviar);
            }

            // Crear el pedido primero
            const bodyCrearPedido = {
                cotizacion_id: cotizacionId,
                forma_de_pago: formaPagoInput.value,
                prendas: prendasParaEnviar  // Vacío para LOGO SOLO, lleno para COMBINADA
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

                // ✅ DIFERENCIACIÓN: Depende del tipo de cotización
                // - LOGO SOLO (L): dataCrearPedido.logo_pedido_id
                // - COMBINADA (PL): dataCrearPedido.pedido_id (del pedidos_produccion)
                const esCombinada = (dataCrearPedido.es_combinada === true || dataCrearPedido.es_combinada === 'true' || dataCrearPedido.tipo_cotizacion === 'PL');
                const pedidoId = esCombinada ? dataCrearPedido.pedido_id : (dataCrearPedido.logo_pedido_id || dataCrearPedido.pedido_id);
                
                console.log('🎯 [PRIMER REQUEST COMPLETADO] Respuesta completa del servidor:', dataCrearPedido);
                console.log('🎯 [LOGO] DETECTANDO TIPO:', {
                    esCombinada: esCombinada,
                    'dataCrearPedido.es_combinada': dataCrearPedido.es_combinada,
                    'typeof es_combinada': typeof dataCrearPedido.es_combinada,
                    'dataCrearPedido.tipo_cotizacion': dataCrearPedido.tipo_cotizacion,
                    pedidoId: pedidoId,
                    'dataCrearPedido.pedido_id': dataCrearPedido.pedido_id,
                    'dataCrearPedido.logo_pedido_id': dataCrearPedido.logo_pedido_id
                });
                
                // ✅ CORREGIDO: Usar logo_cotizacion_id devuelto por el servidor (más confiable)
                // Si no viene en la respuesta, usar la variable global como fallback
                const logoCotizacionIdAUsar = dataCrearPedido.logo_cotizacion_id || logoCotizacionId;

                // ✅ NUEVO: Calcular cantidad total (suma de todas las tallas del logo)
                let cantidadTotal = 0;
                const tallaInputs = document.querySelectorAll('.logo-talla-cantidad');
                console.log('📍 [CANTIDAD] Buscando inputs .logo-talla-cantidad, encontrados:', tallaInputs.length);
                
                tallaInputs.forEach((input, idx) => {
                    const cantidad = parseInt(input.value) || 0;
                    console.log('   Talla ' + idx + ': ' + cantidad);
                    cantidadTotal += cantidad;
                });
                
                console.log('📦 [LOGO] Cantidad total calculada (suma de tallas):', cantidadTotal);

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
                    cotizacion_id: cotizacionId,  // ✅ NUEVO: Enviar cotizacion_id para que se guarde en BD
                    forma_de_pago: formaPagoInput.value,  // ✅ NUEVO: Enviar forma de pago
                    descripcion: descripcionLogoPedido,
                    cantidad: cantidadTotal, // ✅ NUEVO: Enviar cantidad total
                    tecnicas: logoTecnicasSeleccionadas,
                    observaciones_tecnicas: observacionesTecnicas,
                    ubicaciones: logoSeccionesSeleccionadas,
                    fotos: logoFotosSeleccionadas
                };

                console.log('🎨 [LOGO] Datos del LOGO pedido a guardar:', bodyLogoPedido);

                // ✅ CRÍTICO: Solo hacer fetch para COMBINADA (PL)
                // Para LOGO SOLO, el pedido ya se creó en el primer request
                console.log('\n==================== DECISIÓN CRÍTICA ====================');
                console.log('⚠️  [DECISIÓN] Valor de esCombinada:', esCombinada);
                console.log('⚠️  [DECISIÓN] Tipo de esCombinada:', typeof esCombinada);
                console.log('⚠️  [DECISIÓN] ¿!esCombinada (NO combinada)?', !esCombinada);
                console.log('⚠️  [DECISIÓN] ¿esCombinada (SÍ combinada)?', esCombinada);
                console.log('==========================================================\n');
                
                if (!esCombinada) {
                    // Para LOGO SOLO, saltarse el segundo fetch y mostrar éxito directamente
                    console.log('📍 [LOGO SOLO] Es LOGO SOLO, no enviar segundo request');
                    return Promise.resolve({
                        success: true,
                        numero_pedido_logo: dataCrearPedido.numero_pedido || 'LOGO-PENDIENTE',
                        logo_pedido: {
                            numero_pedido: dataCrearPedido.numero_pedido || 'LOGO-PENDIENTE'
                        }
                    });
                }

                console.log('📍 [COMBINADA] ¡¡¡ ES COMBINADA, ENVIANDO SEGUNDO REQUEST !!!');
                console.log('📍 [COMBINADA] URL: /asesores/pedidos/guardar-logo-pedido');
                console.log('📍 [COMBINADA] BODY:', bodyLogoPedido);
                
                return fetch('/asesores/pedidos/guardar-logo-pedido', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify(bodyLogoPedido)
                });
            })
            .then(response => {
                console.log('✅ [RESPUESTA SEGUNDO REQUEST] Status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('✅ [RESPUESTA SEGUNDO REQUEST JSON] Respuesta completa:', data);

                if (data.success) {
                    // Para LOGO SOLO, mostrar éxito con número de LOGO
                    if (esLogoSolo) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Pedido de LOGO creado exitosamente\nNúmero de LOGO: ' + (data.logo_pedido?.numero_pedido || data.numero_pedido_logo || ''),
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = '/asesores/pedidos';
                        });
                    } else if (esCombinada) {
                        // Para COMBINADA (PL), mostrar AMBOS números
                        const numeroPrendas = data.numero_pedido_produccion || data.pedido_produccion?.numero_pedido || 'N/A';
                        const numeroLogo = data.numero_pedido_logo || data.logo_pedido?.numero_pedido || 'N/A';
                        
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            html: '<p style="font-size: 16px; line-height: 1.8;">' +
                                  'Pedidos creados exitosamente<br><br>' +
                                  '<strong>📦 Pedido Producción:</strong> ' + numeroPrendas + '<br>' +
                                  '<strong>🎨 Pedido Logo:</strong> ' + numeroLogo +
                                  '</p>',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = '/asesores/pedidos';
                        });
                    }
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

            return;  // Salir aquí (tanto LOGO SOLO como COMBINADA terminan aquí)
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

// ============================================================
// GESTIÓN DINÁMICA DE TALLAS EN PRENDAS
// ============================================================

/**
 * Mostrar modal para agregar una talla a una prenda
 * @param {number} prendaIndex - Índice de la prenda
 */
window.mostrarModalAgregarTalla = function(prendaIndex) {
    console.log('🔘 Botón "+ Talla" clickeado para prenda:', prendaIndex);
    console.log('📏 tallasDisponiblesCotizacion actual:', tallasDisponiblesCotizacion);
    
    // Obtener tallas actuales de la prenda
    const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) {
        console.error('❌ No se encontró la tarjeta de prenda con índice:', prendaIndex);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se encontró la prenda'
        });
        return;
    }

    console.log('✅ Tarjeta de prenda encontrada');

    // Obtener tallas actuales
    const tallasActuales = Array.from(prendaCard.querySelectorAll('input[data-talla]')).map(input => input.dataset.talla);
    
    console.log('📏 Tallas actuales de prenda ' + prendaIndex + ':', tallasActuales);
    console.log('📏 Tallas disponibles en cotización:', tallasDisponiblesCotizacion);
    
    // Filtrar tallas disponibles que no estén en la prenda actual
    const tallasDisponibles = tallasDisponiblesCotizacion.filter(talla => !tallasActuales.includes(talla));
    
    console.log('📏 Tallas disponibles para agregar:', tallasDisponibles);

    if (!tallasDisponiblesCotizacion || tallasDisponiblesCotizacion.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin tallas cargadas',
            text: 'La cotización no tiene tallas definidas. Por favor, selecciona una cotización válida.'
        });
        return;
    }

    if (tallasDisponibles.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin tallas disponibles',
            text: 'Ya tienes todas las tallas disponibles en esta prenda.'
        });
        return;
    }

    // Mostrar modal de selección
    Swal.fire({
        title: 'Agregar Talla',
        html: `
            <div style="text-align: left;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1f2937;">Selecciona una talla para agregar:</label>
                <select id="selector_talla_agregar" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.95rem; font-family: inherit;">
                    <option value="">-- SELECCIONA UNA TALLA --</option>
                    ${tallasDisponibles.map(talla => `<option value="${talla}">${talla}</option>`).join('')}
                </select>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4ade80',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Agregar',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            console.log('✅ Modal abierto');
            const selector = document.getElementById('selector_talla_agregar');
            if (selector) {
                selector.focus();
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const tallaSeleccionada = document.getElementById('selector_talla_agregar').value;
            console.log('📏 Talla seleccionada:', tallaSeleccionada);
            if (tallaSeleccionada) {
                agregarTallaAlFormulario(prendaIndex, tallaSeleccionada);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona una talla',
                    text: 'Por favor selecciona una talla para continuar'
                });
            }
        }
    });
};

/**
 * Agregar una talla al formulario de una prenda
 * @param {number} prendaIndex - Índice de la prenda
 * @param {string} talla - Talla a agregar
 */
window.agregarTallaAlFormulario = function(prendaIndex, talla) {
    const prendaCard = document.querySelector(`.prenda-card-editable[data-prenda-index="${prendaIndex}"]`);
    if (!prendaCard) {
        console.error('No se encontró la tarjeta de prenda');
        return;
    }

    // Verificar si la talla ya existe
    const inputExistente = prendaCard.querySelector(`input[data-talla="${talla}"]`);
    if (inputExistente) {
        Swal.fire({
            icon: 'warning',
            title: 'Talla duplicada',
            text: `La talla ${talla} ya está en esta prenda.`
        });
        return;
    }

    // Encontrar el contenedor de tallas buscando por todos los divs que tengan inputs de talla
        let tallasContainer = null;
        const allDivs = prendaCard.querySelectorAll('div[style*="margin-top: 1.5rem"]');
        for (let div of allDivs) {
            if (div.querySelector('input[data-talla]')) {
                tallasContainer = div;
                break;
            }
        }
        
        if (!tallasContainer) {
            console.error('No se encontró el contenedor de tallas');
            console.log('Divs encontrados:', allDivs.length);
            return;
        }

        // Crear el HTML de la nueva talla
        const nuevoTallaHtml = `<div style="padding: 1rem; background: white; border: 1px solid #e0e0e0; border-top: none; display: grid; grid-template-columns: 1.5fr 1fr 100px; gap: 1rem; align-items: center; transition: background 0.2s; width: 100%;">
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Talla</label>
                <div style="font-weight: 500; color: #1f2937;">${talla}</div>
            </div>
            <div style="display: flex; flex-direction: column;">
                <label style="font-size: 0.75rem; color: #666; font-weight: 600; text-transform: uppercase; margin-bottom: 0.4rem;">Cantidad</label>
                <input type="number" 
                       name="cantidades[${prendaIndex}][${talla}]" 
                       class="talla-cantidad"
                       min="0" 
                       value="0" 
                       placeholder="0"
                       data-talla="${talla}"
                       data-prenda="${prendaIndex}"
                       style="width: 100%; padding: 0.6rem; border: 1px solid #d0d0d0; border-radius: 4px; font-size: 0.9rem; transition: border-color 0.2s;">
            </div>
            <div style="text-align: center;">
                <button type="button" class="btn-quitar-talla" onclick="quitarTallaDelFormulario(${prendaIndex}, '${talla}')" style="background: #dc3545; color: white; border: none; padding: 0.5rem 0.75rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.3rem; white-space: nowrap;">
                    ✕ Quitar
                </button>
            </div>
        </div>`;

        // Insertar el nuevo elemento antes del cierre del contenedor
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = nuevoTallaHtml;
        const newElement = tempDiv.firstElementChild;
        
        // Insertar antes del cierre (buscar el último elemento que no sea un div de talla)
        const ultimoTallaRow = tallasContainer.querySelector('div[style*="border-top: none"]:last-of-type');
        if (ultimoTallaRow) {
            ultimoTallaRow.insertAdjacentElement('afterend', newElement);
        } else {
            tallasContainer.appendChild(newElement);
        }

        console.log(`✅ Talla ${talla} agregada a prenda ${prendaIndex}`);
        
        Swal.fire({
            icon: 'success',
            title: 'Talla agregada',
            text: `La talla ${talla} ha sido agregada a la prenda ${prendaIndex + 1}`,
            timer: 1500,
            showConfirmButton: false
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
