// Crear Pedido - Script NO EDITABLE (refactorizado)
// NOTA: Usa componentes compartidos para reducir duplicación de código
document.addEventListener('DOMContentLoaded', function() {
    const prendasContainer = document.getElementById('prendas-container');
    const formCrearPedido = document.getElementById('formCrearPedido');

    // Inicializar CotizacionSelectorComponent
    window.CotizacionSelectorComponent.init({
        searchInputId: 'cotizacion_search',
        hiddenInputId: 'cotizacion_id',
        dropdownId: 'cotizacion_dropdown',
        selectedDivId: 'cotizacion_selected',
        selectedTextId: 'cotizacion_selected_text',
        cotizaciones: window.cotizacionesData || [],
        onSeleccion: function(data) {
            // Callback cuando se selecciona una cotización
            if (data.logo) {
                cargarCamposLogo(data.logo);
            } else if (data.prendas && data.prendas.length > 0) {
                cargarPrendas(data.prendas);
            } else {
                prendasContainer.innerHTML = '<p class="text-gray-500 text-center py-8">Esta cotización no tiene contenido</p>';
            }
        }
    });

    // Función para cargar campos de LOGO (simplificada)
    function cargarCamposLogo(logoData) {
        // Ocultar prendas y mostrar logo
        const prendasContainerElement = document.getElementById('prendas-container');
        const logoContainerElement = document.getElementById('logo-fields-container');
        
        if (prendasContainerElement) prendasContainerElement.style.display = 'none';
        if (logoContainerElement) logoContainerElement.style.display = 'block';
        
        // Cambiar título
        const paso3Title = document.getElementById('paso3_titulo');
        if (paso3Title) paso3Title.textContent = 'Información del Logo';
        
        // Cargar descripción
        const descElement = document.getElementById('logo_descripcion');
        if (descElement) descElement.value = logoData.descripcion || '';
        
        // Cargar imágenes (simplificado)
        const galeriaContainer = document.getElementById('logo-galeria-imagenes');
        if (galeriaContainer && logoData.fotos) {
            galeriaContainer.innerHTML = logoData.fotos.length > 0
                ? logoData.fotos.map((foto, i) => `<div style="position:relative;width:100%;aspect-ratio:1;border-radius:6px;overflow:hidden;background:#f0f0f0;border:1px solid #ddd"><img src="${foto.url || foto.ruta_webp || ''}" alt="Logo ${i+1}" style="width:100%;height:100%;object-fit:cover;"></div>`).join('')
                : '<p style="grid-column:1/-1;color:#9ca3af;text-align:center;">Sin imágenes</p>';
        }
        
        // Cargar técnicas (simplificado)
        const tecnicasContainer = document.getElementById('logo-tecnicas-seleccionadas');
        if (tecnicasContainer && logoData.tecnicas) {
            tecnicasContainer.innerHTML = logoData.tecnicas.length > 0
                ? logoData.tecnicas.map(t => `<span style="background:#3498db;color:white;padding:6px 12px;border-radius:20px;font-size:0.85rem;font-weight:500;">${t}</span>`).join('')
                : '<span style="color:#9ca3af;">Sin técnicas especificadas</span>';
        }
        
        // Cargar observaciones de técnicas
        const obsTecsElement = document.getElementById('logo_observaciones_tecnicas');
        if (obsTecsElement) obsTecsElement.value = logoData.observaciones_tecnicas || '';
        
        // Cargar ubicaciones (simplificado)
        const ubicacionesContainer = document.getElementById('logo-ubicaciones-seleccionadas');
        if (ubicacionesContainer && logoData.ubicaciones) {
            ubicacionesContainer.innerHTML = logoData.ubicaciones.length > 0
                ? logoData.ubicaciones.map(u => `<div style="background:white;border:1px solid #ddd;padding:8px;border-radius:6px;font-size:0.85rem;">${u}</div>`).join('')
                : '<span style="color:#9ca3af;grid-column:1/-1;">Sin ubicaciones especificadas</span>';
        }
        
        // Cargar observaciones generales (simplificado)
        const obsContainer = document.getElementById('logo-observaciones-generales');
        if (obsContainer && logoData.observaciones_generales) {
            obsContainer.innerHTML = logoData.observaciones_generales.length > 0
                ? logoData.observaciones_generales.map(obs => `<div style="background:white;border-left:3px solid #3498db;padding:8px;border-radius:4px;font-size:0.85rem;color:#334155;">${obs}</div>`).join('')
                : '<span style="color:#9ca3af;">Sin observaciones</span>';
        }
    }

    // Variable global para almacenar prendas cargadas
    let prendasCargadas = [];

    function cargarPrendas(prendas) {
        // Mostrar contenedor de prendas y ocultar el de logo
        prendasContainer.style.display = 'block';
        const logoContainer = document.getElementById('logo-fields-container');
        if (logoContainer) logoContainer.style.display = 'none';
        
        const paso3Title = document.getElementById('paso3_titulo');
        if (paso3Title) paso3Title.textContent = 'Prendas y Cantidades por Talla';
        
        if (!prendas || prendas.length === 0) {
            prendasContainer.innerHTML = '<p class="text-gray-500 text-center py-8">Esta cotización no tiene prendas</p>';
            return;
        }

        prendasCargadas = prendas;
        let html = '';

        prendas.forEach((prenda, index) => {
            const tallas = prenda.tallas || [];
            const imagen = prenda.fotos && prenda.fotos.length > 0 ? prenda.fotos[0] : null;
            const variantes = prenda.variantes || {};
            
            // Construir nombre de prenda
            const nombrePrenda = [prenda.nombre_producto, variantes.tela, variantes.color, variantes.genero]
                .filter(Boolean).join(' ');
            
            // Formatear descripción (simplificado)
            const descripcion = (prenda.descripcion || '')
                .replace(/\s*Tallas:\s*[XS\-:,0-9\s]*(?=\n|$)/gi, '')
                .replace(/\s•\s+([A-Za-záéíóúñÁÉÍÓÚÑ]+):\s*/g, '<br>• <strong>$1</strong>: ')
                .trim();
            
            html += `
                <div class="prenda-card">
                    <div style="display:flex;gap:1rem;align-items:flex-start;">
                        <div style="flex:1;">
                            <div style="font-weight:600;margin-bottom:0.5rem;">Prenda ${index + 1}: ${nombrePrenda}</div>
                            <div style="color:#4b5563;font-size:0.9rem;margin-bottom:0.5rem;"><strong>Descripción:</strong><br>${descripcion}</div>
                            <div style="color:#374151;font-size:0.85rem;">TALLAS: ${tallas.length > 0 ? tallas.map(t => `${t}:0`).join(', ') : 'N/A'}</div>
                        </div>
                        ${imagen ? `<div style="flex-shrink:0;"><img src="${imagen}" alt="${prenda.nombre_producto}" onclick="abrirModalImagen('${imagen}','${prenda.nombre_producto}')" style="width:80px;height:80px;object-fit:cover;border-radius:4px;border:1px solid #e2e8f0;cursor:pointer;"></div>` : ''}
                    </div>
                    
                    <div class="genero-selector" style="margin:1rem 0;padding:1rem;background:#f9fafb;border-radius:4px;">
                        <label style="display:block;font-weight:600;margin-bottom:0.75rem;">Selecciona género(s):</label>
                        <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
                            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                                <input type="checkbox" name="genero[${index}][]" value="dama" class="genero-checkbox" data-prenda="${index}">
                                <span>Dama</span>
                            </label>
                            <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                                <input type="checkbox" name="genero[${index}][]" value="caballero" class="genero-checkbox" data-prenda="${index}">
                                <span>Caballero</span>
                            </label>
                        </div>
                    </div>

                    <div style="font-weight:600;margin:1rem 0 0.5rem 0;">TALLAS A COTIZAR</div>
                    <div class="tallas-grid">
                        ${tallas.length > 0 ? tallas.map(talla => `
                            <div class="talla-group" data-talla="${talla}" data-prenda="${index}">
                                <div class="talla-header">
                                    <label class="talla-label">${talla}</label>
                                    <button type="button" class="btn-eliminar-talla" onclick="eliminarTalla(this)" title="Eliminar talla">✕</button>
                                </div>
                                <input type="number" name="cantidades[${index}][${talla}]" class="talla-input" min="0" value="0" placeholder="0">
                            </div>
                        `).join('') : '<div style="grid-column:1/-1;padding:1rem;background:#f0f9ff;border-radius:4px;text-align:center;color:#0066cc;font-size:0.85rem;"><strong>Sin tallas definidas</strong> - Agrega una talla abajo</div>'}
                    </div>
                    <div class="tallas-actions">
                        <input type="text" class="input-nueva-talla" placeholder="Nueva talla (ej: XS, 3XL, XL)" data-prenda="${index}">
                        <button type="button" class="btn-agregar-talla" onclick="agregarTalla(this)" title="Agregar talla">+ Agregar</button>
                    </div>
                </div>
            `;
        });

        prendasContainer.innerHTML = html;
    }

    // Enviar formulario
    formCrearPedido.addEventListener('submit', function(e) {
        e.preventDefault();

        const cotizacionId = document.getElementById('cotizacion_id').value;
        
        if (!cotizacionId) {
            Swal.fire({
                icon: 'warning',
                title: 'Selecciona una cotización',
                text: 'Por favor selecciona una cotización antes de continuar',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Verificar si es una cotización LOGO
        const isLogo = document.getElementById('logo-fields-container').style.display !== 'none';
        
        let dataToSend = {
            cotizacion_id: cotizacionId,
            forma_de_pago: formaPagoInput.value,
            _token: document.querySelector('input[name="_token"]').value
        };

        if (isLogo) {
            // Para cotizaciones LOGO, no enviar cantidades por talla
            console.log('🎯 Creando pedido de LOGO');
            dataToSend.tipo_cotizacion = 'LOGO';
            // Los datos del logo ya están en la cotización
        } else {
            // Para cotizaciones de PRENDAS, recopilar cantidades por talla
            console.log('📦 Creando pedido de PRENDAS');
            const prendas = [];
            
            prendasCargadas.forEach((prenda, index) => {
                const prendasCard = document.querySelectorAll('.prenda-card')[index];
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
                
                if (Object.keys(cantidadesPorTalla).length > 0) {
                    const observacionesMap = {};
                    if (prenda.variantes?.observaciones) {
                        const obsArray = prenda.variantes.observaciones.split('|').map(o => o.trim());
                        obsArray.forEach(obs => {
                        if (obs.includes('Manga:')) {
                            observacionesMap.manga_obs = obs.replace('Manga:', '').trim();
                        } else if (obs.includes('Bolsillos:')) {
                            observacionesMap.bolsillos_obs = obs.replace('Bolsillos:', '').trim();
                        } else if (obs.includes('Broche:')) {
                            observacionesMap.broche_obs = obs.replace('Broche:', '').trim();
                        } else if (obs.includes('Reflectivo:')) {
                            observacionesMap.reflectivo_obs = obs.replace('Reflectivo:', '').trim();
                        }
                    });
                }
                
                // Recopilar géneros seleccionados del formulario
                const generosSeleccionados = [];
                const generosCheckboxes = prendasCard.querySelectorAll('.genero-checkbox:checked');
                generosCheckboxes.forEach(checkbox => {
                    generosSeleccionados.push(checkbox.value);
                });
                
                prendas.push({
                    index: index,
                    nombre_producto: prenda.nombre_producto,
                    descripcion: prenda.descripcion,
                    tela: prenda.variantes?.tela,
                    tela_referencia: prenda.variantes?.tela_referencia,
                    color: prenda.variantes?.color,
                    genero: generosSeleccionados.length > 0 ? generosSeleccionados : prenda.variantes?.genero,
                    manga: prenda.variantes?.manga,
                    broche: prenda.variantes?.broche,
                    tiene_bolsillos: prenda.variantes?.tiene_bolsillos,
                    tiene_reflectivo: prenda.variantes?.tiene_reflectivo,
                    manga_obs: observacionesMap.manga_obs,
                    bolsillos_obs: observacionesMap.bolsillos_obs,
                    broche_obs: observacionesMap.broche_obs,
                    reflectivo_obs: observacionesMap.reflectivo_obs,
                    observaciones: prenda.variantes?.observaciones,
                    cantidades: cantidadesPorTalla,
                    // ✅ FOTOS DE PRENDA
                    fotos: prenda.fotos || [],
                    // ✅ FOTOS DE TELAS
                    telas: prenda.telaFotos || prenda.telas || [],
                    // ✅ FOTOS DE LOGOS
                    logos: prenda.logos || []
                });
            }
            });
            
            dataToSend.prendas = prendas;
        }

        console.log('📤 Enviando datos:', dataToSend);

        fetch(`/asesores/cotizaciones/${cotizacionId}/crear-pedido-produccion`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: JSON.stringify(dataToSend)
        }))
        .then(response => response.json())
        .then(data => {
            console.log('✅ Respuesta del servidor:', data);
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '✓ Pedido Creado Exitosamente',
                    html: `
                        <div style="text-align: left; padding: 1rem;">
                            <div style="margin-bottom: 1rem; padding: 1rem; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 4px;">
                                <p style="margin: 0.5rem 0; font-size: 0.95rem;">
                                    <strong>Número de Pedido:</strong> <span style="color: #059669;">${data.pedido_numero || 'N/A'}</span>
                                </p>
                                <p style="margin: 0.5rem 0; font-size: 0.95rem;">
                                    <strong>Estado:</strong> <span style="color: #059669;">Creado</span>
                                </p>
                                <p style="margin: 0.5rem 0; font-size: 0.95rem;">
                                    <strong>Fecha:</strong> <span style="color: #059669;">${new Date().toLocaleDateString('es-CO')}</span>
                                </p>
                            </div>
                            <p style="color: #4b5563; font-size: 0.9rem;">
                                El pedido ha sido creado correctamente y está listo para procesamiento.
                            </p>
                        </div>
                    `,
                    confirmButtonText: 'Ver Pedidos',
                    confirmButtonColor: '#059669',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    window.location.href = '/asesores/pedidos-produccion';
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
                title: 'Error de conexión',
                text: 'Error al crear el pedido: ' + error.message,
                confirmButtonText: 'OK'
            });
        });
    });
});

function eliminarTalla(btn) {
    const tallaGroup = btn.closest('.talla-group');
    const talla = tallaGroup.getAttribute('data-talla');
    
    Swal.fire({
        title: '¿Eliminar talla?',
        text: `¿Estás seguro de que deseas eliminar la talla ${talla}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Animar la desaparición
            tallaGroup.style.transition = 'all 0.3s ease-out';
            tallaGroup.style.opacity = '0';
            tallaGroup.style.transform = 'translateX(-100%)';
            
            // Eliminar del DOM después de la animación
            setTimeout(() => {
                tallaGroup.remove();
                
                // Mostrar toast de éxito
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: `Talla ${talla} eliminada`
                });
            }, 300);
            
            console.log(`🗑️ Talla eliminada: ${talla}`);
        }
    });
}


function agregarTalla(btn) {
    const input = btn.previousElementSibling;
    const nuevaTalla = input.value.trim().toUpperCase();
    const prendasIndex = input.getAttribute('data-prenda');
    
    if (!nuevaTalla) {
        alert('Por favor ingresa el nombre de la talla');
        return;
    }
    
    const tallaGroup = document.createElement('div');
    tallaGroup.className = 'talla-group';
    tallaGroup.setAttribute('data-talla', nuevaTalla);
    tallaGroup.setAttribute('data-prenda', prendasIndex);
    
    tallaGroup.innerHTML = `
        <div class="talla-header">
            <label class="talla-label">${nuevaTalla}</label>
            <button type="button" class="btn-eliminar-talla" onclick="eliminarTalla(this)" title="Eliminar talla">
                ✕
            </button>
        </div>
        <input type="number" 
               name="cantidades[${prendasIndex}][${nuevaTalla}]" 
               class="talla-input" 
               min="0" 
               value="0" 
               placeholder="0">
    `;
    
    const tallasGrid = input.closest('.tallas-actions').previousElementSibling;
    tallasGrid.appendChild(tallaGroup);
    
    input.value = '';
    input.focus();
}
