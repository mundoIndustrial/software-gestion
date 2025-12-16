// Crear Pedido - Script completo sin módulos ES6
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('cotizacion_search');
    const hiddenInput = document.getElementById('cotizacion_id');
    const dropdown = document.getElementById('cotizacion_dropdown');
    const selectedDiv = document.getElementById('cotizacion_selected');
    const selectedText = document.getElementById('cotizacion_selected_text');
    
    const prendasContainer = document.getElementById('prendas-container');
    const clienteInput = document.getElementById('cliente');
    const asesoraInput = document.getElementById('asesora');
    const formaPagoInput = document.getElementById('forma_de_pago');
    const numeroPedidoInput = document.getElementById('numero_pedido');
    const formCrearPedido = document.getElementById('formCrearPedido');

    // El número de pedido se asignará automáticamente en el servidor
    // No cargar desde /next-pedido ya que la cola lo genera atomicamente


    // Debug: Mostrar datos disponibles
    console.log('📊 Datos de cotizaciones recibidos:', window.cotizacionesData);
    console.log('👤 Asesor actual:', window.asesorActualNombre);

    // Obtener cotizaciones (ya están filtradas por asesor en el servidor)
    const misCotizaciones = window.cotizacionesData || [];
    
    console.log('📋 Mis cotizaciones (después de filtrar):', misCotizaciones.length);
    console.log('📋 Datos completos:', misCotizaciones);

    // Función para mostrar las opciones filtradas
    function mostrarOpciones(filtro = '') {
        const filtroLower = filtro.toLowerCase();
        const opciones = misCotizaciones.filter(cot => {
            return cot.numero.toLowerCase().includes(filtroLower) ||
                   (cot.numero_cotizacion && cot.numero_cotizacion.toLowerCase().includes(filtroLower)) ||
                   cot.cliente.toLowerCase().includes(filtroLower);
        });

        console.log(`🔍 Filtro: "${filtro}", Resultados: ${opciones.length}`);

        if (misCotizaciones.length === 0) {
            dropdown.innerHTML = '<div style="padding: 1rem; color: #ef4444; text-align: center;"><strong>⚠️ No hay cotizaciones aprobadas</strong><br><small>No tienes cotizaciones en estado APROBADA_COTIZACIONES o APROBADO_PARA_PEDIDO</small></div>';
        } else if (opciones.length === 0) {
            dropdown.innerHTML = `<div style="padding: 1rem; color: #9ca3af; text-align: center;">No se encontraron cotizaciones<br><small>Total disponibles: ${misCotizaciones.length}</small></div>`;
        } else {
            dropdown.innerHTML = opciones.map(cot => {
                // Función para escapar valores null y strings
                const escape = (val) => {
                    if (!val) return '';
                    return String(val).replace(/'/g, "\\'");
                };
                
                return `
                <div onclick="seleccionarCotizacion(${cot.id}, '${escape(cot.numero)}', '${escape(cot.cliente)}', '${escape(cot.asesora)}', '${escape(cot.formaPago)}', ${cot.prendasCount})" 
                     style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; transition: background 0.2s;" 
                     onmouseover="this.style.background = '#f0f9ff'" 
                     onmouseout="this.style.background = 'white'">
                    <div style="font-weight: 600; color: #1f2937;">
                        ${cot.numero}${cot.numero_cotizacion ? ` <span style="color: #0066cc; font-size: 0.875rem;">(${cot.numero_cotizacion})</span>` : ''}
                    </div>
                    <div style="font-size: 0.875rem; color: #6b7280;">
                        Cliente: <strong>${cot.cliente}</strong> | ${cot.prendasCount} prendas
                    </div>
                    ${cot.formaPago ? `<div style="font-size: 0.75rem; color: #9ca3af;">Forma de pago: ${cot.formaPago}</div>` : ''}
                </div>
            `;
            }).join('');
        }

        dropdown.style.display = 'block';
    }

    // Evento de búsqueda
    searchInput.addEventListener('input', function() {
        mostrarOpciones(this.value);
    });

    // Mostrar dropdown al hacer click (sin filtro)
    searchInput.addEventListener('click', function() {
        console.log('✅ Click en input. Total cotizaciones disponibles:', misCotizaciones.length);
        mostrarOpciones();
    });

    // Mostrar dropdown al hacer focus
    searchInput.addEventListener('focus', function() {
        console.log('✅ Focus en input. Total cotizaciones disponibles:', misCotizaciones.length);
        if (this.value === '') {
            mostrarOpciones();
        }
    });

    // Cerrar dropdown al hacer click afuera
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && e.target !== dropdown) {
            dropdown.style.display = 'none';
        }
    });

    // Función global para seleccionar cotización
    window.seleccionarCotizacion = function(id, numero, cliente, asesora, formaPago, prendasCount) {
        hiddenInput.value = id;
        searchInput.value = `${numero} - ${cliente}`;
        dropdown.style.display = 'none';
        
        // Mostrar resumen
        selectedDiv.style.display = 'block';
        selectedText.textContent = `${numero} - ${cliente} (${prendasCount} prendas)`;
        
        // Actualizar campos de información
        document.getElementById('numero_cotizacion').value = numero;
        clienteInput.value = cliente;
        asesoraInput.value = asesora;
        formaPagoInput.value = formaPago || '';

        // Cargar prendas de la cotización
        fetch(`/asesores/cotizaciones/${id}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(response => {
                console.log('📥 Datos de cotización recibidos:', response);
                
                // La respuesta tiene estructura {success: true, data: {...}}
                const data = response.data || response;
                
                console.log('📊 Datos extraídos:', data);
                
                // Extraer forma_pago si existe
                let formaPago = '';
                if (data.especificaciones && Array.isArray(data.especificaciones.forma_pago)) {
                    if (data.especificaciones.forma_pago.length > 0) {
                        formaPago = data.especificaciones.forma_pago[0].valor || '';
                    }
                }
                
                if (formaPago) {
                    console.log('✅ Forma de pago desde servidor:', formaPago);
                    formaPagoInput.value = formaPago;
                } else {
                    console.log('⚠️ No hay forma de pago en los datos');
                }
                
                console.log('📋 Prendas a cargar:', data.prendas);
                cargarPrendas(data.prendas);
            })
            .catch(error => {
                console.error('Error:', error);
                prendasContainer.innerHTML = '<p class="text-red-500">Error al cargar las prendas: ' + error.message + '</p>';
            });
    };

    // Variable global para almacenar prendas cargadas
    let prendasCargadas = [];

    function cargarPrendas(prendas) {
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
            
            let linea1 = prenda.nombre_producto || '';
            const variacionesPrincipales = [];
            if (variantes.tela) variacionesPrincipales.push(variantes.tela);
            if (variantes.color) variacionesPrincipales.push(variantes.color);
            if (variantes.genero) variacionesPrincipales.push(variantes.genero);
            
            if (variacionesPrincipales.length > 0) {
                linea1 += ' ' + variacionesPrincipales.join(' ');
            }
            
            let linea2 = prenda.descripcion || '';
            
            // Eliminar las tallas que vienen en la descripción original (para evitar duplicación)
            linea2 = linea2.replace(/\s*Tallas:\s*[XS\-:,0-9\s]*(?=\n|$)/gi, '').trim();
            
            // Normalizar espacios múltiples y espacios alrededor de viñetas
            linea2 = linea2.replace(/\s+/g, ' ').replace(/\s*•\s*/g, ' • ');
            
            // Formatear descripción: 
            // 1. Convertir • en saltos de línea
            // 2. Aplicar negrita a palabras después de viñetas y dos puntos
            let descripcionFormateada = linea2
                // Primero, agregar negrita a palabras después de viñetas (incluir caracteres acentuados)
                .replace(/\s•\s+([A-Za-záéíóúñÁÉÍÓÚÑ]+):\s*/g, '<br>• <strong>$1</strong>: ')
                // Aplicar negrita a las palabras clave en el resto del texto
                .replace(/(\s|^)MANGA(\s)/g, '$1<strong>MANGA</strong>$2')
                .replace(/(\s|^)REFLECTIVO(\s)/g, '$1<strong>REFLECTIVO</strong>$2')
                .replace(/(\s|^)BOLSILLO(\s)/g, '$1<strong>BOLSILLO</strong>$2')
                .replace(/(\s|^)BROCHE(\s)/g, '$1<strong>BROCHE</strong>$2');
            
            // Agregar espacio inicial si no comienza con <br>
            if (!descripcionFormateada.startsWith('<br>')) {
                descripcionFormateada = descripcionFormateada.trim();
            }
            
            let linea3 = 'TALLAS: ';
            if (tallas && tallas.length > 0) {
                linea3 += tallas.map(t => `${t}:0`).join(', ');
            } else {
                linea3 += 'N/A: 0';
            }
            
            let descripcionCompleta = `
                <div style="font-size: 0.9rem; line-height: 1.6; color: #1f2937; text-align: left;">
                    <div style="font-weight: 600; margin-bottom: 0.5rem; text-align: left;">
                        Prenda ${index + 1}: ${linea1}
                    </div>
                    <div style="margin-bottom: 0.5rem; color: #4b5563; white-space: pre-wrap; text-align: left;">
                        <strong>Descripción:</strong><br>${descripcionFormateada}
                    </div>
                    <div style="color: #374151; margin-top: 0.5rem; font-size: 0.85rem; text-align: left;">
                        ${linea3}
                    </div>
                </div>
            `;
            
            html += `
                <div class="prenda-card">
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="flex: 1;">
                            <div class="prenda-descripcion" style="font-size: 0.9rem;">
                                ${descripcionCompleta}
                            </div>
                        </div>
                        ${imagen ? `
                            <div style="flex-shrink: 0;">
                                <img src="${imagen}" alt="${prenda.nombre_producto}" onclick="abrirModalImagen('${imagen}', '${prenda.nombre_producto}')" style="
                                    width: 80px;
                                    height: 80px;
                                    object-fit: cover;
                                    border-radius: 4px;
                                    border: 1px solid #e2e8f0;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                " onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='none'">
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="genero-selector" style="margin: 1rem 0; padding: 1rem; background: #f9fafb; border-radius: 4px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 0.75rem; color: #1f2937;">
                            Selecciona género(s):
                        </label>
                        <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="genero[${index}][]" value="dama" class="genero-checkbox" data-prenda="${index}" style="cursor: pointer;">
                                <span style="font-size: 0.9rem; color: #374151;">Dama</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="genero[${index}][]" value="caballero" class="genero-checkbox" data-prenda="${index}" style="cursor: pointer;">
                                <span style="font-size: 0.9rem; color: #374151;">Caballero</span>
                            </label>
                        </div>
                    </div>

                    <div style="font-weight: 600; margin: 1rem 0 0.5rem 0; color: #1f2937;">TALLAS A COTIZAR</div>
                    
                    <div class="tallas-grid">
            `;

            if (tallas && tallas.length > 0) {
                tallas.forEach((talla, tallaIndex) => {
                    html += `
                        <div class="talla-group" data-talla="${talla}" data-prenda="${index}">
                            <div class="talla-header">
                                <label class="talla-label">${talla}</label>
                                <button type="button" class="btn-eliminar-talla" onclick="eliminarTalla(this)" title="Eliminar talla">
                                    ✕
                                </button>
                            </div>
                            <input type="number" 
                                   name="cantidades[${index}][${talla}]" 
                                   class="talla-input" 
                                   min="0" 
                                   value="0" 
                                   placeholder="0">
                        </div>
                    `;
                });
            } else {
                html += `
                    <div style="grid-column: 1 / -1; padding: 1rem; background: #f0f9ff; border-radius: 4px; text-align: center; color: #0066cc; font-size: 0.85rem;">
                        <strong>Sin tallas definidas</strong> - Agrega una talla abajo
                    </div>
                `;
            }

            html += `
                    </div>
                    <div class="tallas-actions">
                        <input type="text" class="input-nueva-talla" placeholder="Nueva talla (ej: XS, 3XL, XL)" data-prenda="${index}">
                        <button type="button" class="btn-agregar-talla" onclick="agregarTalla(this)" title="Agregar talla">
                            + Agregar
                        </button>
                    </div>
                </div>
            `;
        });

        prendasContainer.innerHTML = html;
        console.log('✅ Prendas cargadas exitosamente');
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
                    cantidades: cantidadesPorTalla
                });
            }
        });

        const dataToSend = {
            cotizacion_id: cotizacionId,
            forma_de_pago: formaPagoInput.value,
            prendas: prendas,
            _token: document.querySelector('input[name="_token"]').value
        };

        console.log('📤 Enviando datos:', dataToSend);

        fetch(`/asesores/cotizaciones/${cotizacionId}/crear-pedido-produccion`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'Accept': 'application/json'
            },
            body: JSON.stringify(dataToSend)
        })
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
