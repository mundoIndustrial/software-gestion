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

    console.log('[DISTRIBUCION CARDS] Preparando cards con', totalParciales, 'parciales');
    console.log('[DISTRIBUCION CARDS] Datos de parciales:', parciales);

    if (!ordenCard) {
        console.error('[DISTRIBUCION CARDS] No se encontró orden card');
        return;
    }

    // Crear el HTML de las tarjetas
    const cardsHTML = crearHTMLDistribucionCards(parciales, numeroRecibo, totalParciales);

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
        
        // Limpiar después de la animación
// Registrar función global
window.abrirDistribucionRecibo = abrirDistribucionRecibo;
