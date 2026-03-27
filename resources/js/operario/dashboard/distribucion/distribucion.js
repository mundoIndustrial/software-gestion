import { httpJson } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';
import { abrirModalCostura } from '../costura/modal-asignacion';

function normalizarColorDistribucion(color) {
    const colorLimpio = String(color || '').trim().toLowerCase();
    if (!colorLimpio || colorLimpio === 'sin color') {
        return 'sin_color';
    }

    return colorLimpio.replace(/\s+/g, '_');
}

function esperarDatosDistribucion(timeoutMs = 10000) {
    if (window.datosDistribucion) {
        return Promise.resolve(window.datosDistribucion);
    }

    return new Promise((resolve, reject) => {
        let timeoutId = null;

        const onReady = (event) => {
            window.removeEventListener('costura:datos-distribucion-listos', onReady);
            if (timeoutId) clearTimeout(timeoutId);
            resolve(event?.detail || window.datosDistribucion || null);
        };

        window.addEventListener('costura:datos-distribucion-listos', onReady, { once: true });

        timeoutId = setTimeout(() => {
            window.removeEventListener('costura:datos-distribucion-listos', onReady);
            reject(new Error('timeout_datos_distribucion'));
        }, timeoutMs);
    });
}

export function abrirEditarEncargados(btn) {
    const reciboId = btn.dataset.reciboId;
    const prendaId = btn.dataset.prendaId;
    const numeroRecibo = btn.dataset.numeroRecibo;
    const numeroPedido = btn.dataset.numeroPedido;
    const pedidoId = btn.dataset.pedidoId;
    const nombre = btn.dataset.nombre;
    const tipoRecibo = btn.dataset.tipoRecibo;

    console.log('[EDITAR ENCARGADOS] Abriendo modal para editar:', {
        reciboId,
        prendaId,
        numeroRecibo,
        numeroPedido,
        pedidoId,
        nombre,
        tipoRecibo
    });

    if (!reciboId || !prendaId || !pedidoId || !numeroPedido) {
        mostrarError('Error: Faltan datos necesarios para editar los encargados');
        return;
    }

    // Obtener la distribución actual para cargar los datos en el modal
    const urlApi = `/operario/api/recibos/${reciboId}/distribucion`;
    
    httpJson(urlApi, 'GET')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Abrir el modal de asignación con los datos cargados
                abrirModalCosturaConDatos(pedidoId, prendaId, nombre, tipoRecibo, numeroRecibo, data, numeroPedido);
            } else {
                mostrarError(data.message || 'Error obteniendo la distribución actual');
            }
        })
        .catch(error => {
            console.error('[EDITAR ENCARGADOS] Error:', error);
            mostrarError('Error al obtener la distribución: ' + error.message);
        });
}

function abrirModalCosturaConDatos(pedidoId, prendaId, nombre, tipoRecibo, recibo, datosDistribucion, numeroPedido) {
    // Abrir el modal normalmente
    abrirModalCostura(pedidoId, prendaId, nombre, tipoRecibo, recibo, null, numeroPedido);
    
    // Marcar que estamos en modo edición
    if (window.datosModalCostura) {
        window.datosModalCostura.esEdicion = true;
    }
    
    // Esperar a que el modal esté listo y seleccionar automáticamente la opción de distribución
    setTimeout(() => {
        // Seleccionar automáticamente la opción de distribuir
        if (typeof window.seleccionarOpcionAsignacion === 'function') {
            window.seleccionarOpcionAsignacion('distribuir');
        }
        
        // Esperar a que mostrarContenidoDistribuirModulos se ejecute y establezca window.datosDistribucion
        setTimeout(() => {
            console.log('[EDITAR ENCARGADOS] Verificando si window.datosDistribucion está disponible...');
            console.log('[EDITAR ENCARGADOS] window.datosDistribucion:', window.datosDistribucion);
            
            if (window.datosDistribucion) {
                // Ahora que los datos de distribución están disponibles, cargar los datos existentes
                if (datosDistribucion && datosDistribucion.parciales) {
                    console.log('[EDITAR ENCARGADOS] Cargando datos existentes...');
                    cargarDatosDistribucionExistente(datosDistribucion.parciales);
                }
            } else {
                console.warn('[EDITAR ENCARGADOS] window.datosDistribucion no está disponible después de esperar');
                // Intentar de nuevo después de un tiempo
                setTimeout(() => {
                    if (window.datosDistribucion && datosDistribucion && datosDistribucion.parciales) {
                        console.log('[EDITAR ENCARGADOS] Reintentando cargar datos existentes...');
                        cargarDatosDistribucionExistente(datosDistribucion.parciales);
                    } else {
                        esperarDatosDistribucion(10000).then(() => { if (datosDistribucion && datosDistribucion.parciales) { console.log('[EDITAR ENCARGADOS] Datos de distribucion recibidos tras espera extendida'); cargarDatosDistribucionExistente(datosDistribucion.parciales); } }).catch(() => { console.error('[EDITAR ENCARGADOS] No se pudo cargar window.datosDistribucion dentro del tiempo esperado'); });
                    }
                }, 500);
            }
        }, 500); // Aumentar el tiempo para esperar a que mostrarContenidoDistribuirModulos termine
    }, 100);
}

function cargarDatosDistribucionExistente(parciales) {
    if (!window.datosDistribucion) {
        // Si no hay datos de distribución, esperar un poco y reintentar
        setTimeout(() => {
            if (window.datosDistribucion) {
                procesarCargarDatosExistente(parciales);
            } else {
                console.warn('[EDITAR ENCARGADOS] No se pudieron cargar los datos de distribución después de esperar');
            }
        }, 500);
        return;
    }
    
    procesarCargarDatosExistente(parciales);
}

function procesarCargarDatosExistente(parciales) {
    console.log('[PROCESAR DATOS] Iniciando procesamiento de datos existentes');
    console.log('[PROCESAR DATOS] window.datosDistribución:', window.datosDistribucion);
    console.log('[PROCESAR DATOS] parciales recibidos:', parciales);
    
    if (!window.datosDistribucion) {
        console.warn('[PROCESAR DATOS] No hay window.datosDistribucion');
        return;
    }
    
    // Limpiar asignaciones anteriores
    window.asignacionesPorModulo = {};
    
    const modulos = window.datosDistribucion.modulos;
    const tallas = window.datosDistribucion.tallas;
    
    console.log('[PROCESAR DATOS] Módulos disponibles:', modulos);
    console.log('[PROCESAR DATOS] Tallas disponibles:', tallas);
    
    // Crear un mapa de tallas con sus colores para lookup rápido
    const mapaTallasConColores = {};
    tallas.forEach(talla => {
        const clave = `${talla.tallaOriginal || talla.talla}_${talla.genero || ''}`;
        mapaTallasConColores[clave] = talla.color;
        
        // Si la talla tiene colores_detalle, guardarlos también
        if (talla.colores_detalle && Array.isArray(talla.colores_detalle)) {
            mapaTallasConColores[clave + '_detalles'] = talla.colores_detalle;
        }
    });
    
    console.log('[PROCESAR DATOS] Mapa de tallas con colores:', mapaTallasConColores);
    
    parciales.forEach(parcial => {
        const encargado = parcial.encargado_costura || parcial.encargado;
        console.log(`[PROCESAR DATOS] Procesando parcial con encargado: ${encargado}`);
        
        if (!encargado) return;
        
        // Buscar el módulo correspondiente al encargado
        const modulo = modulos.find(m => 
            m.encargado.toLowerCase().trim() === encargado.toLowerCase().trim()
        );
        
        console.log(`[PROCESAR DATOS] Módulo encontrado para ${encargado}:`, modulo);
        
        if (!modulo) return;
        
        // Procesar las tallas de este parcial
        const tallasParcial = parcial.tallas || [];
        console.log(`[PROCESAR DATOS] Tallas del parcial ${encargado}:`, tallasParcial);
        
        tallasParcial.forEach(talla => {
            const nombreTalla = talla.talla;
            const cantidad = parseInt(talla.cantidad) || 0;
            let color = talla.color_nombre || null;
            
            // Si el color es null, intentar obtenerlo del mapa de tallas
            if (!color) {
                // Buscar en las tallas disponibles una que coincida
                const tallaDisponible = tallas.find(t => 
                    (t.tallaOriginal || t.talla) === nombreTalla
                );
                
                if (tallaDisponible) {
                    color = tallaDisponible.color;
                    console.log(`[PROCESAR DATOS] Color encontrado para ${nombreTalla}: ${color} (desde tallas disponibles)`);
                }
            }
            
            // Crear ID único que incluya el color (manejar minúsculas y espacios)
            const colorNormalizado = normalizarColorDistribucion(color);
            const tallaIdUnico = `${nombreTalla}_${colorNormalizado}`;
            
            console.log(`[PROCESAR DATOS] Procesando talla: ${nombreTalla}, cantidad: ${cantidad}, color: ${color}, ID único: ${tallaIdUnico}`);
            
            if (nombreTalla && cantidad > 0) {
                if (!window.asignacionesPorModulo[modulo.id]) {
                    window.asignacionesPorModulo[modulo.id] = {};
                }
                
                // Guardar la talla con su ID único (que incluye color)
                window.asignacionesPorModulo[modulo.id][tallaIdUnico] = {
                    cantidad: cantidad,
                    color: color,
                    tallaOriginal: nombreTalla // Guardar el nombre original para referencia
                };
                
                console.log(`[PROCESAR DATOS] Guardada asignación - Módulo ${modulo.id}, ID ${tallaIdUnico}:`, window.asignacionesPorModulo[modulo.id][tallaIdUnico]);
            }
        });
    });
    
    console.log('[PROCESAR DATOS] Asignaciones finales:', window.asignacionesPorModulo);
    
    // Actualizar la interfaz para mostrar los datos cargados
    setTimeout(() => {
        console.log('[PROCESAR DATOS] Intentando actualizar interfaz...');
        console.log('[PROCESAR DATOS] window.datosDistribucion disponible:', !!window.datosDistribucion);
        console.log('[PROCESAR DATOS] window.mostrarCardsEncargados disponible:', typeof window.mostrarCardsEncargados);
        
        if (window.datosDistribucion) {
            const modulosConAsignaciones = Object.keys(window.asignacionesPorModulo || {});
            console.log('[PROCESAR DATOS] Módulos con asignaciones:', modulosConAsignaciones);
            
            // En modo edición, mostrar las cards de encargados en lugar de usar el selector
            if (typeof window.mostrarCardsEncargados === 'function') {
                console.log('[PROCESAR DATOS] Mostrando cards de encargados...');
                window.mostrarCardsEncargados(window.datosDistribucion.tallas, window.datosDistribucion.modulos);
            } else {
                console.error('[PROCESAR DATOS] La función mostrarCardsEncargados no está disponible');
            }
        } else {
            console.error('[PROCESAR DATOS] window.datosDistribucion no está disponible');
        }
    }, 500);
}

export function abrirDistribucionRecibo(btn) {
    const reciboId = btn.dataset.reciboId;
    const prendaId = btn.dataset.prendaId;
    const numeroRecibo = btn.dataset.numeroRecibo;
    const ordenCard = btn.closest('.orden-card-simple');

    console.log('[VER DISTRIBUCIÓN] Toggling distribución:', {
        reciboId,
        prendaId,
        numeroRecibo
    });

    if (!reciboId) {
        mostrarError('Error: No se pudo determinar el ID del recibo');
        return;
    }

    // Buscar si ya existe la sección de distribución (buscar como hermano siguiente)
    let distribucionSection = ordenCard?.nextElementSibling;
    
    // Validar que sea la sección de distribución correcta
    if (distribucionSection && !distribucionSection.classList.contains('distribucion-parciales-section')) {
        distribucionSection = null;
    }
    
    if (distribucionSection) {
        console.log('[VER DISTRIBUCIÓN] Sección encontrada, iniciando toggle');
        console.log('[VER DISTRIBUCIÓN] Clases actuales:', distribucionSection.className);
        
        // Si ya existe, toggle (mostrar/ocultar)
        const isHidden = distribucionSection.classList.contains('hidden');
        console.log('[VER DISTRIBUCIÓN] Está oculta:', isHidden);
        
        distribucionSection.classList.toggle('hidden');
        console.log('[VER DISTRIBUCIÓN] Clases después de toggle:', distribucionSection.className);
        console.log('[VER DISTRIBUCIÓN] style.display:', distribucionSection.style.display);
        
        // Cambiar el texto del botón
        btn.textContent = isHidden ? 'OCULTAR' : 'VER DISTRIBUCIÓN';
        
        // Re-agregar el ícono
        const icon = document.createElement('span');
        icon.className = 'material-symbols-rounded';
        icon.textContent = isHidden ? 'visibility_off' : 'visibility';
        btn.prepend(icon);
        
        console.log('[VER DISTRIBUCIÓN] Toggle completado. Nuevo texto:', btn.textContent);
        return;
    }

    // Si no existe, obtener datos y crear
    obtennerDistribucionParciales(reciboId, numeroRecibo, ordenCard, btn);
}

function obtennerDistribucionParciales(reciboId, numeroRecibo, ordenCard, btn) {
    console.log('[DISTRIBUCION] Obteniendo parciales del recibo:', reciboId);

    const urlApi = `/operario/api/recibos/${reciboId}/distribucion`;
    
    httpJson(urlApi, 'GET')
        .then(response => {
            console.log('[DISTRIBUCION] Response:', response);
            
            if (!response.ok) {
                console.error('[DISTRIBUCION] HTTP Error:', response.status, response.statusText);
                mostrarError(`Error HTTP ${response.status}: ${response.statusText}`);
                return;
            }
            
            return response.json();
        })
        .then(data => {
            if (!data) {
                console.error('[DISTRIBUCION] Sin datos');
                return;
            }
            
            console.log('[DISTRIBUCION] Datos parseados:', data);
            
            if (data.success) {
                console.log('[DISTRIBUCION] Parciales obtenidos exitosamente:', data);
                mostrarDistribucionCards(data, numeroRecibo, ordenCard, btn);
            } else {
                const errorMsg = data.message || 'Error desconocido al obtener distribución';
                console.error('[DISTRIBUCION] Error en respuesta:', errorMsg);
                mostrarError(errorMsg);
            }
        })
        .catch(error => {
            console.error('[DISTRIBUCION] Error en petición:', error);
            console.error('[DISTRIBUCION] Stack trace:', error.stack);
            mostrarError('Error al obtener la distribución de parciales: ' + (error.message || 'Error desconocido'));
        });
}

function mostrarDistribucionCards(datos, numeroRecibo, ordenCard, btn) {
    const parciales = datos.parciales || [];
    const totalParciales = datos.total_parciales || 0;
    const numeroPedido = datos.recibo?.numero_pedido || numeroRecibo; // Obtener número de pedido real de la respuesta

    console.log('[DISTRIBUCION CARDS] Preparando cards con', totalParciales, 'parciales');
    console.log('[DISTRIBUCION CARDS] Número de pedido real:', numeroPedido);
    console.log('[DISTRIBUCION CARDS] Datos de parciales:', parciales);

    if (!ordenCard) {
        console.error('[DISTRIBUCION CARDS] No se encontró orden card');
        return;
    }

    // Crear el HTML de las tarjetas con el número de pedido correcto
    const cardsHTML = crearHTMLDistribucionCards(parciales, numeroPedido, totalParciales);

    // Crear contenedor de distribución
    const distribucionSection = document.createElement('div');
    distribucionSection.className = 'distribucion-parciales-section';
    distribucionSection.innerHTML = cardsHTML;

    // Insertar después de la orden-card
    ordenCard.insertAdjacentElement('afterend', distribucionSection);

    // Cambiar el texto del botón a "OCULTAR" y el ícono
    if (btn) {
        btn.innerHTML = '<span class="material-symbols-rounded">visibility_off</span> OCULTAR';
    }

    console.log('[DISTRIBUCION CARDS] Cards insertadas en el DOM');
}

function crearHTMLDistribucionCards(parciales, numeroRecibo, totalParciales) {
    if (totalParciales === 0) {
        return `
            <div class="parcial-card parcial-card-vacio">
                <div class="parcial-header">
                    <h4 class="parcial-title">No hay parciales</h4>
                </div>
                <div class="parcial-body">
                    <div class="parcial-info">
                        <span class="material-symbols-rounded">info</span>
                        <p>No hay parciales creados para este recibo #${numeroRecibo}</p>
                    </div>
                </div>
            </div>
        `;
    }

    // Generar tarjetas para cada parcial
    const parcialCards = parciales.map((parcial, index) => {
        const estadoParcial = String(parcial.proceso_estado || 'En Progreso');
        const badgeClass = `badge-estado-${estadoParcial.toLowerCase().replace(/\s+/g, '-')}`;
        
        // Generar el HTML de tallas (S: 23, M: 1, L: 20, etc.)
        const tallasHTML = generarTallasHTML(parcial.tallas || []);
        
        return `
            <div class="parcial-card" data-parcial-id="${parcial.id}">
                <div class="parcial-header">
                    <div class="parcial-numero">
                        <h4 class="parcial-title">Parcial #${parcial.consecutivo_parcial}</h4>
                        <span class="parcial-tipo-recibo">${parcial.tipo_recibo}</span>
                    </div>
                    <span class="badge-estado ${badgeClass}">
                        ${estadoParcial}
                    </span>
                </div>
                
                <div class="parcial-body">
                    <div class="parcial-row">
                        <div class="parcial-info-group">
                            <span class="parcial-label">Módulo/Encargado</span>
                            <span class="parcial-value parcial-encargado">
                                <span class="material-symbols-rounded">person</span>
                                ${parcial.encargado || 'SIN ASIGNAR'}
                            </span>
                        </div>
                        <div class="parcial-info-group">
                            <span class="parcial-label">Área</span>
                            <span class="parcial-value parcial-area">
                                <span class="material-symbols-rounded">location_on</span>
                                ${parcial.area || 'SIN ASIGNAR'}
                            </span>
                        </div>
                    </div>
                    
                    <div class="parcial-row">
                        <div class="parcial-info-group full-width">
                            <span class="parcial-label">Recibo Original</span>
                            <span class="parcial-value">
                                Recibo #${parcial.consecutivo_original}
                            </span>
                        </div>
                    </div>

                    ${tallasHTML ? `
                    <div class="parcial-row parcial-tallas-row">
                        <div class="parcial-tallas-container">
                            ${tallasHTML}
                        </div>
                    </div>
                    ` : ''}

                    <div class="parcial-row parcial-acciones">
                        <button class="btn-ver-recibo-parcial" 
                                onclick="verReciboParcial(${parcial.id}, '${String(parcial.consecutivo_parcial).replace(/'/g, "\\'")}'  , '${numeroRecibo}', ${parcial.prenda_pedido_id || 'null'})">
                            <span class="material-symbols-rounded">visibility</span>
                            VER RECIBO
                        </button>
                        <button class="btn-deshacer-parcial" 
                                onclick="deshacerParcial(${parcial.id}, this)"
                                data-parcial-id="${parcial.id}">
                            <span class="material-symbols-rounded">undo</span>
                            DESHACER PARTE
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    return parcialCards;
}

function generarTallasHTML(tallas) {
    if (!tallas || tallas.length === 0) {
        console.log('[TALLAS] Sin tallas para este parcial');
        return '';
    }

    console.log('[TALLAS] Procesando tallas:', tallas);

    // Agrupar por talla y sumar cantidades
    const tallasSumadas = tallas.reduce((acc, talla) => {
        const key = talla.talla.toUpperCase();
        if (!acc[key]) {
            acc[key] = 0;
        }
        acc[key] += talla.cantidad || 0;
        return acc;
    }, {});

    console.log('[TALLAS] Tallas sumadas:', tallasSumadas);

    // Generar HTML con formato: S: 23, M: 1, L: 20
    const tallasHTML = Object.entries(tallasSumadas)
        .map(([talla, cantidad]) => `<span class="talla-item">${talla}: <strong>${cantidad}</strong></span>`)
        .join('');

    return tallasHTML;
}

/**
 * Deshacer un parcial específico
 */
async function deshacerParcial(parcialId, btn) {
    if (!confirm('¿Estás seguro de que deseas deshacer esta parte? Se eliminará de procesos_prenda y recibo_por_partes.')) {
        return;
    }

    try {
        console.log('[DESHACER PARCIAL] Eliminando parcial:', parcialId);

        const response = await fetch(`/operario/api/parciales/${parcialId}/deshacer`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        console.log('[DESHACER PARCIAL] Response status:', response.status);

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            console.log('[DESHACER PARCIAL] Parcial eliminado exitosamente');
            
            // Animar y eliminar la tarjeta
            const parcialCard = btn.closest('.parcial-card');
            if (parcialCard) {
                parcialCard.style.opacity = '0';
                parcialCard.style.transform = 'scale(0.9)';
                parcialCard.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    parcialCard.remove();
                    console.log('[DESHACER PARCIAL] Tarjeta removida del DOM');
                    
                    // Mostrar mensaje de éxito
                    showSuccessMessage('Parte deshacha correctamente');
                }, 300);
            }
        } else {
            console.error('[DESHACER PARCIAL] Error en respuesta:', data);
            alert('Error: ' + (data.message || 'No se pudo deshacer el parcial'));
        }
    } catch (error) {
        console.error('[DESHACER PARCIAL] Error:', error);
        alert('Error al deshacer la parte: ' + error.message);
    }
}

function showSuccessMessage(message) {
    const notification = document.createElement('div');
    notification.className = 'notification notification-success';
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #10b981;
        color: white;
        padding: 12px 20px;
        border-radius: 6px;
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Ver detalles del recibo parcial
 * Abre la página de detalles mostrando las tallas asignadas al parcial
 */
async function verReciboParcial(parcialId, consecutivoParcial, numeroPedido, prendaPedidoId) {
    try {
        // Sanitizar y asegurar tipos correctos
        const sanitizedParcialId = parseInt(parcialId, 10);
        const sanitizedNumeroPedido = String(numeroPedido).trim();
        const sanitizedConsecutivoParcial = String(consecutivoParcial).trim().replace(/[^0-9.]/g, '');
        const sanitizedPrendaId = prendaPedidoId && prendaPedidoId !== 'null' ? parseInt(prendaPedidoId, 10) : null;

        console.log('[VER RECIBO PARCIAL] Parámetros sanitizados', {
            parcialId: sanitizedParcialId,
            consecutivoParcial: sanitizedConsecutivoParcial,
            numeroPedido: sanitizedNumeroPedido,
            prendaPedidoId: sanitizedPrendaId
        });

        if (!sanitizedNumeroPedido || isNaN(sanitizedNumeroPedido)) {
            console.error('[VER RECIBO PARCIAL] numeroPedido es inválido');
            alert('Error: No se pudo determinar el número de pedido');
            return;
        }

        if (!sanitizedParcialId || isNaN(sanitizedParcialId)) {
            console.error('[VER RECIBO PARCIAL] parcialId es inválido');
            alert('Error: ID de parcial inválido');
            return;
        }

        // Construir URL de navegación usando window.location.origin
        const baseUrl = window.location.origin || 'http://localhost:8000';
        let url = baseUrl + '/operario/pedido/' + sanitizedNumeroPedido;
        const params = new URLSearchParams();

        // Parámetros de la prenda
        if (sanitizedPrendaId) {
            params.append('prenda_id', sanitizedPrendaId);
        }

        // Parámetros del parcial
        params.append('tipo_recibo', 'PARCIAL');
        params.append('parcial_id', sanitizedParcialId);
        params.append('consecutivo_parcial', sanitizedConsecutivoParcial);

        if (params.toString()) {
            url += '?' + params.toString();
        }

        console.log('[VER RECIBO PARCIAL] URL de navegación completa:', url);

        // Navegar a la vista de detalles usando método seguro
        setTimeout(() => {
            window.location.href = url;
        }, 100);

    } catch (error) {
        console.error('[VER RECIBO PARCIAL] Error:', error);
        alert('Error al abrir los detalles del recibo parcial: ' + error.message);
    }
}
        
        // Limpiar después de la animación
// Registrar función global
window.abrirDistribucionRecibo = abrirDistribucionRecibo;
window.deshacerParcial = deshacerParcial;
window.verReciboParcial = verReciboParcial;


