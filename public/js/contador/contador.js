// ===== FUNCIONES MODALES =====
function openCotizacionModal(cotizacionId) {
    fetch(`/contador/cotizacion/${cotizacionId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('cotizacionModal').style.display = 'flex';
        })
        .catch(error => console.error('Error:', error));
}

function closeCotizacionModal() {
    document.getElementById('cotizacionModal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.addEventListener('click', function(event) {
    const modal = document.getElementById('cotizacionModal');
    if (event.target === modal) {
        closeCotizacionModal();
    }
});

// ===== NAVEGACIÓN ENTRE SECCIONES =====
document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const section = this.getAttribute('data-section');
        
        // Remover clase active de todos los botones y secciones
        document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.section-content').forEach(s => s.classList.remove('active'));
        
        // Agregar clase active al botón y sección seleccionados
        this.classList.add('active');
        document.getElementById(section + '-section').classList.add('active');
        
        // Cargar formatos si se hace clic en la sección de formatos
        if (section === 'formatos') {
            cargarFormatos();
        }
    });
});

// Cargar formatos al iniciar
window.addEventListener('load', function() {
    cargarFormatos();
});

// ===== FUNCIONES PARA COSTOS DE PRENDAS =====
function cargarComponentes(prendaId) {
    console.log('Iniciando cargarComponentes para prenda:', prendaId);
    const url = document.querySelector('meta[name="route-componentes"]')?.content || '/contador/componentes';
    console.log('URL de fetch:', url);
    
    fetch(url)
        .then(response => {
            console.log('Respuesta recibida:', response.status, response.statusText);
            if (!response.ok) {
                throw new Error('Error en la respuesta: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Componentes JSON parseado:', data);
            const select = document.getElementById('componente-select-' + prendaId);
            console.log('Select encontrado:', select);
            if (select) {
                select.innerHTML = '<option value="">-- Seleccionar --</option>';
                if (data.length === 0) {
                    console.warn('No hay componentes en la base de datos');
                } else {
                    console.log('Agregando ' + data.length + ' componentes al select');
                }
                data.forEach(componente => {
                    console.log('Agregando componente:', componente.nombre);
                    const option = document.createElement('option');
                    option.value = componente.id;
                    option.textContent = componente.nombre;
                    select.appendChild(option);
                });
                console.log('Componentes agregados exitosamente');
            } else {
                console.error('Select no encontrado con ID: componente-select-' + prendaId);
            }
        })
        .catch(error => console.error('Error cargando componentes:', error));
}

function cargarCostos(prendaId) {
    const url = document.querySelector('meta[name="route-costos"]')?.content || `/contador/costos/${prendaId}`;
    
    fetch(url.replace(':prendaId', prendaId))
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('costos-tbody-' + prendaId);
            if (tbody) {
                tbody.innerHTML = '';
                
                data.costos.forEach(costo => {
                    const row = document.createElement('tr');
                    row.style.backgroundColor = '#ffffff';
                    row.innerHTML = `
                        <td style="padding: 0.75rem; border: 1px solid #ddd; color: #333;">${costo.componente.nombre}</td>
                        <td style="padding: 0.75rem; text-align: right; border: 1px solid #ddd; color: #333;">$ ${parseFloat(costo.costo).toFixed(2)}</td>
                        <td style="padding: 0.75rem; text-align: center; border: 1px solid #ddd;">
                            <button type="button" onclick="eliminarCosto(${costo.id}, ${prendaId})" style="background-color: #dc3545; color: white; border: none; padding: 0.25rem 0.5rem; border-radius: 3px; cursor: pointer; font-size: 0.8rem;">Eliminar</button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                const totalElement = document.getElementById('total-costo-' + prendaId);
                if (totalElement) {
                    totalElement.textContent = '$ ' + parseFloat(data.total).toFixed(2);
                }
            }
        })
        .catch(error => console.error('Error cargando costos:', error));
}

function agregarCosto(prendaId) {
    const select = document.getElementById('componente-select-' + prendaId);
    const inputNuevo = document.getElementById('componente-nuevo-' + prendaId);
    const costoInput = document.getElementById('costo-input-' + prendaId);
    
    if (!select || !costoInput) {
        console.error('Elementos no encontrados');
        return;
    }

    let componenteId = select.value;
    let componenteNombre = inputNuevo ? inputNuevo.value : '';
    let costo = parseFloat(costoInput.value);

    if (!costo || costo <= 0) {
        alert('Por favor ingresa un costo válido');
        return;
    }

    // Si hay componente nuevo, crearlo primero
    if (componenteNombre && !componenteId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        fetch('/contador/componente/crear', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                nombre: componenteNombre,
                descripcion: ''
            })
        })
        .then(response => response.json())
        .then(data => {
            componenteId = data.id;
            guardarCosto(prendaId, componenteId, costo);
        })
        .catch(error => console.error('Error creando componente:', error));
    } else if (componenteId) {
        guardarCosto(prendaId, componenteId, costo);
    } else {
        alert('Por favor selecciona o escribe un componente');
    }
}

function guardarCosto(prendaId, componenteId, costo) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    fetch('/contador/costo/guardar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            prenda_cotizacion_id: prendaId,
            componente_prenda_id: componenteId,
            costo: costo
        })
    })
    .then(response => response.json())
    .then(data => {
        const select = document.getElementById('componente-select-' + prendaId);
        const inputNuevo = document.getElementById('componente-nuevo-' + prendaId);
        const costoInput = document.getElementById('costo-input-' + prendaId);
        
        if (select) select.value = '';
        if (inputNuevo) inputNuevo.value = '';
        if (costoInput) costoInput.value = '';
        
        cargarCostos(prendaId);
    })
    .catch(error => console.error('Error guardando costo:', error));
}

function eliminarCosto(costoId, prendaId) {
    if (confirm('¿Estás seguro de que deseas eliminar este costo?')) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        fetch(`/contador/costo/${costoId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            cargarCostos(prendaId);
        })
        .catch(error => console.error('Error eliminando costo:', error));
    }
}

// ===== MODAL PARA COTIZAR PRENDAS =====
let cotizacionActualId = null;

function abrirModalCotizarPrendas(cotizacionId) {
    cotizacionActualId = cotizacionId;
    fetch(`/contador/cotizacion/${cotizacionId}/cotizar-prendas`)
        .then(response => response.text())
        .then(html => {
            const modal = document.createElement('div');
            modal.id = 'cotizarPrendasModal';
            modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 2000;';
            modal.innerHTML = `
                <div style="background: white; border-radius: 8px; width: 90%; max-width: 1000px; max-height: 90vh; overflow-y: auto; padding: 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid #e0e0e0; padding-bottom: 1rem;">
                        <h2 style="color: #1e5ba8; margin: 0;">Cotizar Prendas</h2>
                        <button onclick="cerrarModalCotizarPrendas()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999;">✕</button>
                    </div>
                    <div id="cotizarPrendasContent">
                        ${html}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Cargar datos de prendas
            setTimeout(() => {
                const firstTab = document.querySelector('.prenda-tab');
                if (firstTab) {
                    const prendaId = firstTab.getAttribute('data-prenda-id');
                    cambiarPrendaTab(prendaId);
                }
            }, 100);
        })
        .catch(error => console.error('Error:', error));
}

function cerrarModalCotizarPrendas() {
    const modal = document.getElementById('cotizarPrendasModal');
    if (modal) {
        modal.remove();
    }
}

function cambiarPrendaTab(prendaId) {
    // Ocultar todos los contenidos
    document.querySelectorAll('.prenda-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Desactivar todos los tabs
    document.querySelectorAll('.prenda-tab').forEach(tab => {
        tab.style.backgroundColor = '#f0f0f0';
        tab.style.color = '#333';
    });
    
    // Mostrar contenido seleccionado
    const content = document.getElementById('prenda-content-' + prendaId);
    if (content) {
        content.style.display = 'block';
    }
    
    // Activar tab seleccionado
    const tab = document.querySelector(`[data-prenda-id="${prendaId}"]`);
    if (tab) {
        tab.style.backgroundColor = '#1e5ba8';
        tab.style.color = 'white';
    }
    
    // Cargar datos
    cargarComponentes(prendaId);
    cargarCostos(prendaId);
}

function filtrarComponentes(prendaId) {
    const input = document.getElementById('componente-search-' + prendaId);
    const dropdown = document.getElementById('componentes-dropdown-' + prendaId);
    const searchTerm = input.value.toLowerCase().trim();
    
    if (!searchTerm) {
        dropdown.style.display = 'none';
        return;
    }

    console.log('Buscando componentes con término:', searchTerm);

    fetch('/contador/componentes')
        .then(response => {
            console.log('Respuesta status:', response.status);
            return response.json();
        })
        .then(componentes => {
            console.log('Componentes obtenidos:', componentes);
            const filtered = componentes.filter(c => c.nombre.toLowerCase().includes(searchTerm));
            console.log('Componentes filtrados:', filtered);
            
            dropdown.innerHTML = '';
            
            // Mostrar componentes filtrados
            filtered.forEach(componente => {
                const item = document.createElement('div');
                item.style.cssText = 'padding: 0.75rem 1rem; border-bottom: 1px solid #eee; cursor: pointer; transition: background-color 0.2s; color: #333; font-weight: 500;';
                item.textContent = componente.nombre;
                item.onmouseover = () => item.style.backgroundColor = '#f0f0f0';
                item.onmouseout = () => item.style.backgroundColor = 'transparent';
                item.onclick = () => {
                    input.value = componente.nombre;
                    input.dataset.componenteId = componente.id;
                    dropdown.style.display = 'none';
                };
                dropdown.appendChild(item);
            });
            
            // Opción para crear nuevo componente
            if (filtered.length === 0 && searchTerm.length > 0) {
                const createItem = document.createElement('div');
                createItem.style.cssText = 'padding: 0.75rem 1rem; background-color: #e8f4f8; cursor: pointer; font-weight: 600; color: #1e5ba8; border-top: 2px solid #1e5ba8; border-bottom: 2px solid #1e5ba8;';
                createItem.textContent = '+ Crear: "' + searchTerm + '"';
                createItem.onmouseover = () => createItem.style.backgroundColor = '#d0e8f0';
                createItem.onmouseout = () => createItem.style.backgroundColor = '#e8f4f8';
                createItem.onclick = () => {
                    crearNuevoComponente(searchTerm, prendaId);
                };
                dropdown.appendChild(createItem);
            }
            
            dropdown.style.display = 'block';
        })
        .catch(error => console.error('Error en filtrarComponentes:', error));
}

function crearNuevoComponente(nombre, prendaId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    fetch('/contador/componente/crear', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            nombre: nombre,
            descripcion: ''
        })
    })
    .then(response => response.json())
    .then(componente => {
        const input = document.getElementById('componente-search-' + prendaId);
        input.value = componente.nombre;
        input.dataset.componenteId = componente.id;
        document.getElementById('componentes-dropdown-' + prendaId).style.display = 'none';
    })
    .catch(error => console.error('Error:', error));
}

function agregarCostoPrenda(prendaId) {
    const input = document.getElementById('componente-search-' + prendaId);
    const costoInput = document.getElementById('costo-input-' + prendaId);
    
    let componenteId = input.dataset.componenteId;
    let costo = parseFloat(costoInput.value) || 0;

    if (!componenteId) {
        alert('Por favor selecciona o crea un componente');
        return;
    }

    guardarCosto(prendaId, componenteId, costo);
    input.value = '';
    input.dataset.componenteId = '';
    costoInput.value = '';
}

function guardarFormatoCotizacion(cotizacionId) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    
    fetch('/contador/formato/guardar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
            cotizacion_id: cotizacionId
        })
    })
    .then(response => response.json())
    .then(data => {
        alert('✓ Formato guardado exitosamente');
        cerrarModalCotizarPrendas();
        cargarFormatos();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al guardar el formato');
    });
}

// ===== FUNCIONES PARA FORMATOS =====
function cargarFormatos() {
    fetch('/contador/formatos')
        .then(response => response.json())
        .then(formatos => {
            const list = document.getElementById('formatos-list');
            list.innerHTML = '';

            if (formatos.length === 0) {
                list.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #999;">No hay formatos creados aún</div>';
                return;
            }

            formatos.forEach(formato => {
                const card = document.createElement('div');
                card.style.cssText = 'background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); transition: all 0.3s ease;';
                
                const cotizacion = formato.cotizacion;
                const fecha = new Date(formato.created_at).toLocaleDateString('es-ES');
                
                card.innerHTML = `
                    <div style="padding: 1.5rem; border-bottom: 2px solid #e0e0e0;">
                        <h3 style="color: #1e5ba8; margin: 0 0 0.5rem 0; font-size: 1.1rem;">📄 ${cotizacion.numero_cotizacion}</h3>
                        <p style="color: #666; margin: 0; font-size: 0.9rem;">Cliente: <strong>${cotizacion.cliente}</strong></p>
                        <p style="color: #666; margin: 0.5rem 0 0 0; font-size: 0.9rem;">Asesora: <strong>${cotizacion.asesora}</strong></p>
                    </div>
                    <div style="padding: 1.5rem;">
                        <div style="margin-bottom: 1rem;">
                            <p style="color: #333; margin: 0 0 0.5rem 0; font-weight: 600;">Costo Total:</p>
                            <p style="color: #1e5ba8; font-size: 1.3rem; margin: 0; font-weight: 700;">$ ${parseFloat(formato.costo_total).toFixed(2)}</p>
                        </div>
                        <p style="color: #999; font-size: 0.85rem; margin: 0;">Creado: ${fecha}</p>
                    </div>
                    <div style="padding: 1rem; background: #f8f9fa; display: flex; gap: 0.5rem;">
                        <button onclick="verFormatoDetalle(${formato.id})" style="flex: 1; padding: 0.75rem; background: linear-gradient(135deg, #1e5ba8 0%, #2b7ec9 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            👁️ Ver
                        </button>
                        <button onclick="eliminarFormato(${formato.id})" style="flex: 1; padding: 0.75rem; background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                            🗑️ Eliminar
                        </button>
                    </div>
                `;
                
                list.appendChild(card);
            });
        })
        .catch(error => console.error('Error cargando formatos:', error));
}

function verFormatoDetalle(formatoId) {
    fetch(`/contador/formato/${formatoId}`)
        .then(response => response.json())
        .then(formato => {
            const modal = document.createElement('div');
            modal.id = 'formatoDetalleModal';
            modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 3000;';
            
            const cotizacion = formato.cotizacion;
            let contenidoPrendas = '';
            
            formato.costos_por_prenda.forEach((prenda, index) => {
                let filasCostos = '';
                prenda.costos.forEach(costo => {
                    filasCostos += `
                        <tr style="background-color: #ffffff;">
                            <td style="padding: 0.75rem; border: 1px solid #ddd; color: #333;">${costo.componente}</td>
                            <td style="padding: 0.75rem; text-align: right; border: 1px solid #ddd; color: #333;">$ ${parseFloat(costo.costo).toFixed(2)}</td>
                        </tr>
                    `;
                });
                
                contenidoPrendas += `
                    <div style="margin-bottom: 2rem; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
                        <div style="padding: 1.5rem; border-bottom: 2px solid #e0e0e0;">
                            <h4 style="color: #1e5ba8; margin: 0 0 1rem 0;">Prenda ${index + 1}</h4>
                            ${prenda.imagen_url ? `<img src="${prenda.imagen_url}" alt="Prenda" style="max-width: 100%; height: auto; border-radius: 8px; margin-bottom: 1rem;">` : ''}
                            <p style="color: #333; line-height: 1.6; margin: 0;">${prenda.prenda_descripcion}</p>
                        </div>
                        <div style="padding: 1.5rem;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background-color: #1e5ba8;">
                                        <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd; font-weight: 700; color: white;">Componente</th>
                                        <th style="padding: 0.75rem; text-align: right; border: 1px solid #ddd; font-weight: 700; color: white;">Costo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${filasCostos}
                                    <tr style="background-color: #f5f5f5; font-weight: 700;">
                                        <td style="padding: 0.75rem; border: 1px solid #ddd; color: #333;">TOTAL</td>
                                        <td style="padding: 0.75rem; text-align: right; border: 1px solid #ddd; color: #1e5ba8;">$ ${parseFloat(prenda.total).toFixed(2)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            });
            
            modal.innerHTML = `
                <div style="background: white; border-radius: 12px; width: 90%; max-width: 900px; max-height: 90vh; overflow-y: auto; padding: 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 2px solid #e0e0e0; padding-bottom: 1rem;">
                        <h2 style="color: #1e5ba8; margin: 0;">Detalle del Formato</h2>
                        <button onclick="document.getElementById('formatoDetalleModal').remove()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999;">✕</button>
                    </div>
                    
                    <div style="margin-bottom: 2rem; background: #f8f9fa; padding: 1.5rem; border-radius: 8px;">
                        <h3 style="color: #1e5ba8; margin: 0 0 1rem 0;">Información de la Cotización</h3>
                        <p style="color: #333; margin: 0.5rem 0;"><strong>Número:</strong> ${cotizacion.numero_cotizacion}</p>
                        <p style="color: #333; margin: 0.5rem 0;"><strong>Cliente:</strong> ${cotizacion.cliente}</p>
                        <p style="color: #333; margin: 0.5rem 0;"><strong>Asesora:</strong> ${cotizacion.asesora}</p>
                    </div>
                    
                    ${contenidoPrendas}
                    
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-top: 2rem;">
                        <h3 style="color: #1e5ba8; margin: 0 0 1rem 0;">Resumen Total</h3>
                        <p style="color: #333; font-size: 1.2rem; margin: 0;"><strong>Costo Total:</strong> <span style="color: #1e5ba8; font-size: 1.4rem;">$ ${parseFloat(formato.costo_total).toFixed(2)}</span></p>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
        })
        .catch(error => console.error('Error:', error));
}

function eliminarFormato(formatoId) {
    if (confirm('¿Estás seguro de que deseas eliminar este formato?')) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        fetch(`/contador/formato/${formatoId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            cargarFormatos();
        })
        .catch(error => console.error('Error:', error));
    }
}
