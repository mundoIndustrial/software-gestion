// Variables globales para el visor de costos
let visorCostosActual = {
    cotizacionId: null,
    cliente: null,
    prendas: [],
    indiceActual: 0
};

/**
 * Abre el modal visor de costos
 */
function abrirModalVisorCostos(cotizacionId, cliente) {
    visorCostosActual = { cotizacionId: cotizacionId, cliente: cliente, prendas: [], indiceActual: 0 };
    
    // Primero obtener los nombres de las prendas desde el endpoint de cotización
    fetch(`/contador/cotizacion/${cotizacionId}`)
        .then(response => response.json())
        .then(cotizacionData => {
            // Mapear nombres de prendas
            const prendasNombres = {};
            if (cotizacionData.prendas_cotizaciones && Array.isArray(cotizacionData.prendas_cotizaciones)) {
                cotizacionData.prendas_cotizaciones.forEach((prenda, idx) => {
                    prendasNombres[idx] = prenda.nombre_prenda || `Prenda ${idx + 1}`;
                });
            }
            
            // Ahora obtener los costos
            return fetch(`/contador/cotizacion/${cotizacionId}/costos`)
                .then(response => response.json())
                .then(data => ({ costos: data, nombres: prendasNombres }));
        })
        .then(({ costos, nombres }) => {
            console.log('Datos de costos recibidos:', costos);
            if (costos.success && costos.prendas.length > 0) {
                // Asignar nombres a las prendas
                costos.prendas.forEach((prenda, idx) => {
                    if (!prenda.nombre_producto || prenda.nombre_producto === 'Prenda sin nombre') {
                        prenda.nombre_producto = nombres[idx] || `Prenda ${idx + 1}`;
                    }
                });
                
                visorCostosActual.prendas = costos.prendas;
                console.log('Prendas cargadas:', visorCostosActual.prendas);
                document.getElementById('visorCostosModal').style.display = 'flex';
                
                // Resetear scroll al abrir
                setTimeout(() => {
                    const contenido = document.getElementById('visorCostosContenido');
                    if (contenido) {
                        contenido.scrollTop = 0;
                    }
                }, 0);
                
                mostrarPrendaVisor(0);
            } else {
                // Mostrar modal de "sin costos" en lugar de alerta
                mostrarModalSinCostos(cliente);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarModalErrorCostos(error.message);
        });
}

/**
 * Muestra un modal cuando no hay costos calculados
 */
function mostrarModalSinCostos(cliente) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5000;
    `;
    
    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 8px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
        ">
            <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
            <h2 style="color: #1e5ba8; margin: 0 0 1rem 0; font-size: 1.5rem;">Sin Costos Calculados</h2>
            <p style="color: #666; margin: 0 0 1.5rem 0; line-height: 1.6;">
                No hay costos calculados para la cotización del cliente <strong>${cliente}</strong>.
            </p>
            <p style="color: #999; margin: 0 0 2rem 0; font-size: 0.9rem;">
                Por favor, calcula los costos de las prendas primero usando la opción "Calcular Costos".
            </p>
            <button onclick="this.closest('div').parentElement.remove()" style="
                background: #1e5ba8;
                color: white;
                border: none;
                padding: 0.75rem 2rem;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 600;
                font-size: 1rem;
            ">
                Entendido
            </button>
        </div>
    `;
    
    document.body.appendChild(modal);
}

/**
 * Muestra un modal cuando hay error al cargar costos
 */
function mostrarModalErrorCostos(mensaje) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 5000;
    `;
    
    modal.innerHTML = `
        <div style="
            background: white;
            border-radius: 8px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            text-align: center;
        ">
            <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
            <h2 style="color: #ef4444; margin: 0 0 1rem 0; font-size: 1.5rem;">Error al Cargar Costos</h2>
            <p style="color: #666; margin: 0 0 1.5rem 0; line-height: 1.6;">
                Ocurrió un error al intentar cargar los costos de la cotización.
            </p>
            <p style="color: #999; margin: 0 0 2rem 0; font-size: 0.9rem;">
                ${mensaje || 'Por favor, intenta de nuevo más tarde.'}
            </p>
            <button onclick="this.closest('div').parentElement.remove()" style="
                background: #ef4444;
                color: white;
                border: none;
                padding: 0.75rem 2rem;
                border-radius: 4px;
                cursor: pointer;
                font-weight: 600;
                font-size: 1rem;
            ">
                Cerrar
            </button>
        </div>
    `;
    
    document.body.appendChild(modal);
}

/**
 * Cierra el modal visor de costos
 */
function cerrarVisorCostos() {
    document.getElementById('visorCostosModal').style.display = 'none';
    
    // Limpiar tabs
    const tabsContainer = document.getElementById('visorCostosTabsContainer');
    if (tabsContainer) {
        tabsContainer.innerHTML = '';
    }
    
    visorCostosActual = {
        cotizacionId: null,
        cliente: null,
        prendas: [],
        indiceActual: 0
    };
}

/**
 * Navega a la prenda anterior
 */
function visorCostosAnterior() {
    if (visorCostosActual.indiceActual > 0) {
        visorCostosActual.indiceActual--;
        mostrarPrendaVisor(visorCostosActual.indiceActual);
    }
}

/**
 * Navega a la próxima prenda
 */
function visorCostosProximo() {
    if (visorCostosActual.indiceActual < visorCostosActual.prendas.length - 1) {
        visorCostosActual.indiceActual++;
        mostrarPrendaVisor(visorCostosActual.indiceActual);
    }
}

/**
 * Genera los tabs de prendas dinámicamente
 */
function generarTabsPrendas() {
    const tabsContainer = document.getElementById('visorCostosTabsContainer');
    if (!tabsContainer) {
        console.error('visorCostosTabsContainer no encontrado');
        return;
    }
    
    // Limpiar tabs existentes antes de generar nuevos
    tabsContainer.innerHTML = '';
    
    console.log('Generando tabs para', visorCostosActual.prendas.length, 'prendas');
    
    visorCostosActual.prendas.forEach((prenda, idx) => {
        const tab = document.createElement('button');
        tab.setAttribute('data-prenda-tab', idx);
        
        // Determinar el nombre de la prenda
        let nombrePrenda = prenda.nombre_producto || prenda.nombre || `Prenda ${idx + 1}`;
        
        // Si el nombre está vacío o es muy corto, usar un nombre genérico
        if (!nombrePrenda || nombrePrenda.trim() === '') {
            nombrePrenda = `Prenda ${idx + 1}`;
        }
        
        tab.textContent = nombrePrenda;
        tab.style.cssText = `
            padding: 12px 24px;
            background: ${idx === 0 ? 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)' : '#6b7280'};
            color: white;
            border: none;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s;
            white-space: nowrap;
            text-transform: lowercase;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        `;
        
        tab.onclick = () => mostrarPrendaVisor(idx);
        
        tab.onmouseover = function() {
            if (idx !== visorCostosActual.indiceActual) {
                this.style.background = '#4b5563';
            }
        };
        
        tab.onmouseout = function() {
            if (idx !== visorCostosActual.indiceActual) {
                this.style.background = '#374151';
            }
        };
        
        tabsContainer.appendChild(tab);
    });
}

/**
 * Muestra la prenda en el visor
 */
function mostrarPrendaVisor(indice) {
    const prenda = visorCostosActual.prendas[indice];
    
    if (!prenda) return;
    
    // Actualizar índice actual
    visorCostosActual.indiceActual = indice;
    
    // Generar tabs de prendas si no existen
    generarTabsPrendas();
    
    // Actualizar estilo del tab activo
    const tabs = document.querySelectorAll('[data-prenda-tab]');
    tabs.forEach((tab, idx) => {
        if (idx === indice) {
            tab.style.background = 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)';
            tab.style.color = 'white';
        } else {
            tab.style.background = '#374151';
            tab.style.color = '#d1d5db';
        }
    });
    
    // Construir detalles en una línea compacta
    let detallesLinea = [];
    if (prenda.color) detallesLinea.push(`<strong>Color:</strong> ${prenda.color}`);
    if (prenda.tela) {
        const tela = prenda.tela_referencia ? `${prenda.tela} (Ref: ${prenda.tela_referencia})` : prenda.tela;
        detallesLinea.push(`<strong>Tela:</strong> ${tela}`);
    }
    if (prenda.manga_nombre) detallesLinea.push(`<strong>Manga:</strong> ${prenda.manga_nombre}`);
    
    // Calcular cantidad de filas: items + 1 fila de total
    const cantidadItems = prenda.items ? prenda.items.length : 0;
    const cantidadFilas = Math.max(cantidadItems, 1); // Mínimo 1 fila
    
    // Construir HTML del contenido - Diseño compacto como en la imagen
    let html = `
        <div style="padding: 0; margin-top: -1.5rem; transform: scale(0.8); transform-origin: top left; width: 125%;">
            <!-- Sección Detalles Compacta -->
            <div style="margin-bottom: 1.5rem; margin-top: 0.5rem;">
                <!-- Línea de atributos -->
                <div style="color: #333; font-size: 0.9rem; line-height: 1.6; margin-bottom: 0.75rem; height: 1.5rem;">
                    ${detallesLinea.join(' | ')}
                </div>
                
                <!-- Descripción + Especificaciones -->
                ${prenda.descripcion ? `<div id="descripcionPrenda" style="color: #333; font-size: 0.9rem; line-height: 1.8; white-space: pre-wrap; word-wrap: break-word;"></div>` : ''}
            </div>
            
            <!-- Contenedor de Tabla e Imágenes -->
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <!-- Tabla de Costos con filas dinámicas -->
                <div style="flex: 1; overflow-x: auto;">
                    <div style="background: white; border-radius: 8px; padding: 0; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); overflow: hidden;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #1e40af; border-bottom: 2px solid #1e40af;">
                                    <th style="padding: 8px 10px; text-align: left; font-weight: 700; color: white; border-right: 1px solid #163a8f; font-size: 0.9rem;">CONCEPTO</th>
                                    <th style="padding: 8px 10px; text-align: right; font-weight: 700; color: white; font-size: 0.9rem;">VALOR</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Filas dinámicas según items -->
                                ${Array(cantidadFilas).fill(0).map((_, idx) => {
                                    const item = prenda.items && prenda.items[idx];
                                    return `
                                        <tr style="border-bottom: 1px solid #e2e8f0;">
                                            <td style="padding: 6px 10px; color: #333; font-weight: 500; border-right: 1px solid #e2e8f0; font-size: 0.85rem;">
                                                ${item ? item.item : ''}
                                            </td>
                                            <td style="padding: 6px 10px; text-align: right; color: #333; font-weight: 600; font-size: 0.85rem;">
                                                ${item ? '$' + parseFloat(item.precio || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : ''}
                                            </td>
                                        </tr>
                                    `;
                                }).join('')}
                                
                                <!-- Fila de Total -->
                                <tr style="background: #1e40af; border-top: 2px solid #1e40af;">
                                    <td style="padding: 8px 10px; color: white; font-weight: 700; border-right: 1px solid #163a8f; font-size: 0.9rem;">
                                        TOTAL COSTO
                                    </td>
                                    <td style="padding: 8px 10px; text-align: right; color: white; font-weight: 700; font-size: 1rem;">
                                        $${parseFloat(prenda.costo_total || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Sección de Imágenes -->
                <div style="display: flex; flex-direction: column; gap: 0.75rem; justify-content: flex-start; align-items: center; min-width: 280px; padding: 0.5rem; margin-top: -5px;">
                    ${(() => {
                        const totalImagenes = (prenda.fotos?.length || 0) + (prenda.tela_fotos?.length || 0);
                        if (totalImagenes === 0) {
                            return '<div style="width: 100%; height: 280px; max-width: 280px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.75rem;">Sin imágenes</div>';
                        }
                        const primeraImagen = prenda.fotos?.[0] || prenda.tela_fotos?.[0];
                        return `
                            <div style="position: relative; width: 100%; max-width: 280px; cursor: pointer;" onclick="abrirLightboxImagenes(${indice})">
                                <img src="${primeraImagen}" alt="Prenda" style="width: 100%; height: 280px; border-radius: 4px; border: 1px solid #ddd; object-fit: contain; background: #f5f5f5;">
                                ${totalImagenes > 1 ? `
                                    <div style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; backdrop-filter: blur(4px);">
                                        IMAGENES ( ${totalImagenes} )
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    })()}
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('visorCostosContenido').innerHTML = html;
    
    // Procesar descripción para formatear títulos en negrilla y agregar saltos de línea
    if (prenda.descripcion) {
        const descripcionDiv = document.getElementById('descripcionPrenda');
        if (descripcionDiv) {
            let texto = prenda.descripcion.trim();
            
            // Títulos a buscar
            const labels = ['Reflectivo', 'Bolsillos', 'Botón', 'Broche', 'Otros detalles', 'TALLAS', 'DESCRIPCIÓN'];
            
            // Reemplazar títulos con versión en negrilla y agregar saltos de línea
            labels.forEach(label => {
                const regex = new RegExp(`(\\*\\*\\*\\s)?${label}:`, 'gi');
                texto = texto.replace(regex, `\n<strong>${label}:</strong>`);
            });
            
            // Limpiar saltos de línea múltiples al inicio
            texto = texto.replace(/^\n+/, '');
            
            // Convertir saltos de línea en <br>
            texto = texto.replace(/\n/g, '<br>');
            
            descripcionDiv.innerHTML = texto;
        }
    }
    
    // Ajustar altura del modal automáticamente
    setTimeout(() => {
        const modalContent = document.getElementById('visorCostosModalContent');
        const contenido = document.getElementById('visorCostosContenido');
        
        if (modalContent && contenido) {
            // Calcular altura total del contenido
            const headerHeight = modalContent.querySelector('div:first-child').offsetHeight;
            const contentHeight = contenido.scrollHeight;
            const totalHeight = headerHeight + contentHeight;
            
            // Establecer altura máxima pero permitir que crezca según el contenido
            const maxHeight = window.innerHeight * 0.9; // 90vh
            
            if (totalHeight < maxHeight) {
                // Si el contenido cabe, usar altura automática
                modalContent.style.height = 'auto';
                contenido.style.overflowY = 'visible';
            } else {
                // Si no cabe, usar max-height y scroll
                modalContent.style.maxHeight = maxHeight + 'px';
                contenido.style.overflowY = 'auto';
            }
        }
    }, 50);
}

// Cerrar modal al presionar ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarVisorCostos();
    }
});

// Cerrar modal al hacer clic en el fondo
document.getElementById('visorCostosModal')?.addEventListener('click', function(event) {
    if (event.target === this) {
        cerrarVisorCostos();
    }
});

// Navegación con flechas del teclado
document.addEventListener('keydown', function(event) {
    if (document.getElementById('visorCostosModal').style.display === 'flex') {
        if (event.key === 'ArrowLeft') {
            visorCostosAnterior();
        } else if (event.key === 'ArrowRight') {
            visorCostosProximo();
        }
    }
});

