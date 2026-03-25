import { httpJson } from '../api/http';
import { mostrarError, mostrarExito } from '../ui/messages';

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
        const badgeClass = `badge-estado-${parcial.proceso_estado?.toLowerCase().replace(/\s+/g, '-')}`;
        
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
                        ${parcial.proceso_estado || 'Pendiente'}
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
