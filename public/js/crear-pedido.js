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

    // Cargar próximo número de pedido
    fetch('/asesores/pedidos/next-pedido')
        .then(response => response.json())
        .then(data => {
            numeroPedidoInput.value = data.siguiente_pedido;
        });

    // Obtener cotizaciones filtradas por asesor
    const misCotizaciones = window.cotizacionesData.filter(cot => cot.asesora === window.asesorActualNombre);

    // Función para mostrar las opciones filtradas
    function mostrarOpciones(filtro = '') {
        const filtroLower = filtro.toLowerCase();
        const opciones = misCotizaciones.filter(cot => {
            return cot.numero.toLowerCase().includes(filtroLower) ||
                   cot.cliente.toLowerCase().includes(filtroLower);
        });

        if (opciones.length === 0) {
            dropdown.innerHTML = '<div style="padding: 1rem; color: #9ca3af; text-align: center;">No se encontraron cotizaciones</div>';
        } else {
            dropdown.innerHTML = opciones.map(cot => `
                <div onclick="seleccionarCotizacion(${cot.id}, '${cot.numero.replace(/'/g, "\\'")}', '${cot.cliente.replace(/'/g, "\\'")}', '${cot.asesora.replace(/'/g, "\\'")}', '${cot.formaPago.replace(/'/g, "\\'")}', ${cot.prendasCount})" 
                     style="padding: 0.75rem 1rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; transition: background 0.2s;" 
                     onmouseover="this.style.background = '#f0f9ff'" 
                     onmouseout="this.style.background = 'white'">
                    <div style="font-weight: 600; color: #1f2937;">${cot.numero}</div>
                    <div style="font-size: 0.875rem; color: #6b7280;">
                        Cliente: <strong>${cot.cliente}</strong> | ${cot.prendasCount} prendas
                    </div>
                    ${cot.formaPago ? `<div style="font-size: 0.75rem; color: #9ca3af;">Forma de pago: ${cot.formaPago}</div>` : ''}
                </div>
            `).join('');
        }

        dropdown.style.display = 'block';
    }

    // Evento de búsqueda
    searchInput.addEventListener('input', function() {
        mostrarOpciones(this.value);
    });

    // Mostrar dropdown al hacer click
    searchInput.addEventListener('focus', function() {
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
            .then(data => {
                console.log('📥 Datos de cotización recibidos:', data);
                
                if (data.forma_pago) {
                    console.log('✅ Forma de pago desde servidor:', data.forma_pago);
                    formaPagoInput.value = data.forma_pago;
                } else {
                    console.log('⚠️ No hay forma de pago en los datos');
                }
                
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
            const detalles = [];
            if (variantes.manga) detalles.push(`MANGA ${variantes.manga.toUpperCase()}`);
            if (variantes.tiene_bolsillos) detalles.push('CON BOLSILLO');
            if (variantes.broche) detalles.push(`BROCHE ${variantes.broche.toUpperCase()}`);
            if (variantes.tiene_reflectivo) detalles.push('CON REFLECTIVO');
            
            if (detalles.length > 0) {
                linea2 = linea2 ? linea2 + ' ' + detalles.join(' ') : detalles.join(' ');
            }
            
            let linea3 = 'TALLAS: ';
            if (tallas && tallas.length > 0) {
                linea3 += tallas.map(t => `${t}:0`).join(', ');
            } else {
                linea3 += 'N/A: 0';
            }
            
            let descripcionCompleta = `
                <div style="font-size: 0.9rem; line-height: 1.6; color: #1f2937;">
                    <div style="font-weight: 600; margin-bottom: 0.5rem;">
                        Prenda ${index + 1}: ${linea1}
                    </div>
                    <div style="margin-bottom: 0.5rem; color: #4b5563;">
                        <strong>Descripción:</strong> ${linea2}
                    </div>
                    <div style="color: #374151;">
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
                
                prendas.push({
                    index: index,
                    nombre_producto: prenda.nombre_producto,
                    descripcion: prenda.descripcion,
                    tela: prenda.variantes?.tela,
                    tela_referencia: prenda.variantes?.tela_referencia,
                    color: prenda.variantes?.color,
                    genero: prenda.variantes?.genero,
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
                    title: 'Creado exitosamente',
                    text: 'El pedido ha sido creado correctamente',
                    timer: 1500,
                    timerProgressBar: true,
                    showConfirmButton: false
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
    
    if (confirm(`¿Eliminar la talla ${talla}?`)) {
        tallaGroup.style.opacity = '0.5';
        tallaGroup.style.pointerEvents = 'none';
        
        const input = tallaGroup.querySelector('.talla-input');
        input.disabled = true;
        input.value = '';
        
        tallaGroup.classList.add('talla-eliminada');
        
        btn.textContent = '✓';
        btn.style.background = '#10b981';
        btn.disabled = true;
    }
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
