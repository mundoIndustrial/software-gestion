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
    visorCostosActual = { id: cotizacionId, cliente: cliente, prendas: [] };
    
    // Fetch de costos
    fetch(`/contador/cotizacion/${cotizacionId}/costos`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.prendas.length > 0) {
                visorCostosActual.prendas = data.prendas;
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
                alert('No hay prendas con costos calculados');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los costos');
        });
}

/**
 * Cierra el modal visor de costos
 */
function cerrarVisorCostos() {
    document.getElementById('visorCostosModal').style.display = 'none';
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
 * Muestra la prenda en el visor
 */
function mostrarPrendaVisor(indice) {
    const prenda = visorCostosActual.prendas[indice];
    
    if (!prenda) return;
    
    // Actualizar índice
    document.getElementById('visorIndice').textContent = `${indice + 1} / ${visorCostosActual.prendas.length}`;
    
    // Actualizar título
    document.getElementById('visorTitulo').textContent = prenda.nombre_producto || 'Prenda';
    document.getElementById('visorCliente').textContent = `Cliente: ${visorCostosActual.cliente}`;
    
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
                <div style="font-weight: 700; color: #1e5ba8; margin-bottom: 0.75rem; font-size: 0.95rem;">📋 Detalles de la Prenda:</div>
                
                <!-- Línea de atributos -->
                <div style="color: #333; font-size: 0.9rem; line-height: 1.6; margin-bottom: 0.75rem;">
                    ${detallesLinea.join(' | ')}
                </div>
                
                <!-- Descripción + Especificaciones -->
                ${prenda.descripcion ? `
                    <div style="color: #333; font-size: 0.9rem; line-height: 1.6;">
                        ${prenda.descripcion}
                    </div>
                ` : ''}
            </div>
            
            <!-- Contenedor de Tabla e Imágenes -->
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                <!-- Tabla de Costos con filas dinámicas -->
                <div style="flex: 1; overflow-x: auto;">
                    <table style="width: auto; border-collapse: collapse; background: white; border: 2px solid #333; border-radius: 8px; table-layout: auto;">
                    <tbody>
                        <!-- Filas dinámicas según items -->
                        ${Array(cantidadFilas).fill(0).map((_, idx) => {
                            const item = prenda.items && prenda.items[idx];
                            return `
                                <tr style="border-bottom: 2px solid #333;">
                                    <td style="padding: 0.35rem 0.5rem; border-right: 2px solid #333; font-size: 0.75rem; color: #666; line-height: 1.2; word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                                        ${item ? item.item : ''}
                                    </td>
                                    <td style="padding: 0.35rem 0.5rem; text-align: right; font-size: 0.75rem; color: #666; line-height: 1.2; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; width: 80px; min-width: 80px;">
                                        ${item ? '$' + parseFloat(item.precio || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : ''}
                                    </td>
                                </tr>
                            `;
                        }).join('')}
                        
                        <!-- Fila de Total -->
                        <tr style="background: #f5f5f5; font-weight: 700; border-top: 2px solid #333;">
                            <td style="padding: 0.35rem 0.5rem; border-right: 2px solid #333; color: #ef4444; font-size: 0.75rem; line-height: 1.2; word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                                TOTAL COSTO
                            </td>
                            <td style="padding: 0.35rem 0.5rem; text-align: right; color: #333; font-size: 0.75rem; line-height: 1.2; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; width: 80px; min-width: 80px;">
                                ${parseFloat(prenda.costo_total || 0).toLocaleString('es-CO', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                            </td>
                        </tr>
                    </tbody>
                    </table>
                </div>
                
                <!-- Sección de Imágenes -->
                <div style="display: flex; flex-direction: column; gap: 0.75rem; justify-content: flex-start; align-items: center; min-width: 280px; padding: 0.5rem;">
                    <div style="font-weight: 600; color: #333; font-size: 0.8rem; width: 100%; text-align: center;">IMÁGENES</div>
                    ${prenda.fotos && prenda.fotos.length > 0 ? prenda.fotos.map((foto, idx) => `
                        <img src="${foto}" alt="Prenda ${idx + 1}" style="width: 100%; height: 280px; max-width: 280px; border-radius: 4px; border: 1px solid #ddd; object-fit: contain; background: #f5f5f5;">
                    `).join('') : '<div style="width: 100%; height: 280px; max-width: 280px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.75rem;">Sin imágenes</div>'}
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('visorCostosContenido').innerHTML = html;
    
    // Ajustar altura del modal automáticamente
    setTimeout(() => {
        const modalContent = document.getElementById('visorCostosModalContent');
        const contenido = document.getElementById('visorCostosContenido');
        if (modalContent) {
            modalContent.style.height = 'auto';
        }
        // Aplicar scroll interno si es necesario
        if (contenido) {
            contenido.style.overflowY = 'auto';
        }
    }, 0);
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

