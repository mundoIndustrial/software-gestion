@push('scripts')
<!-- Scripts para Recibos/Procesos -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>

<!-- Script para el modal de seguimiento -->
<script src="{{ asset('js/ordersjs/tracking-modal-handler.js') }}?v={{ time() }}"></script>

<!-- Scripts para la funcionalidad de Día de Entrega -->
<script src="{{ asset('js/ordersjs/modules/diaEntregaModule.js') }}?v={{ time() }}"></script>

<!-- Scripts para Formatters -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/utils/Formatters.js') }}?v={{ time() }}"></script>

<!-- Script para novedades de recibos (sistema completo) -->
<script src="{{ asset('js/recibos-novedades.js') }}?v={{ time() }}"></script>

<!-- Script con funciones globales (cargado antes del HTML) -->
<script>
// Funciones globales para el modal de celda formateada
function abrirModalCeldaConFormato(titulo, prendas) {
    console.log('[abrirModalCeldaConFormato]  INICIO - Datos recibidos:');
    console.log('[abrirModalCeldaConFormato] Título:', titulo);
    console.log('[abrirModalCeldaConFormato] Prendas tipo:', typeof prendas);
    console.log('[abrirModalCeldaConFormato] Prendas es array:', Array.isArray(prendas));
    console.log('[abrirModalCeldaConFormato] Prendas cantidad:', prendas ? prendas.length : 0);
    console.log('[abrirModalCeldaConFormato] Prendas RAW:', prendas);
    
    let htmlContenido = '';
    
    if (!prendas || prendas.length === 0) {
        htmlContenido = '<div style="text-align: center; color: #9ca3af;">No hay prendas disponibles</div>';
    } else {
        prendas.forEach((prenda, idx) => {
            console.log(`[abrirModalCeldaConFormato] ⚡ Procesando prenda ${idx}:`, prenda);
            
            // Convertir objeto Eloquent a objeto simple si es necesario
            let prendaData = prenda.toJSON ? prenda.toJSON() : prenda;
            console.log(`[abrirModalCeldaConFormato]  Después toJSON:`, prendaData);
            
            // NORMALIZAR datos: convertir objetos a strings
            prendaData = normalizarPrendaData(prendaData);
            console.log(`[abrirModalCeldaConFormato]  Después normalizar:`, prendaData);
            console.log(`[abrirModalCeldaConFormato] Campos principales: nombre="${prendaData.nombre_prenda}", tela="${prendaData.tela}", color="${prendaData.color}", manga="${prendaData.manga}"`);
            
            // Generar HTML formateado como en el recibo
            let prendaHtml = '';
            try {
                // Intentar usar Formatters si está disponible
                if (window.Formatters && typeof window.Formatters.construirDescripcionCostura === 'function') {
                    console.log(`[abrirModalCeldaConFormato] 🎯 Usando window.Formatters.construirDescripcionCostura`);
                    prendaHtml = window.Formatters.construirDescripcionCostura(prendaData);
                } else if (typeof Formatters !== 'undefined' && typeof Formatters.construirDescripcionCostura === 'function') {
                    console.log(`[abrirModalCeldaConFormato] 🎯 Usando Formatters.construirDescripcionCostura (module)`);
                    prendaHtml = Formatters.construirDescripcionCostura(prendaData);
                } else {
                    // Fallback si Formatters no disponible - generar HTML simple
                    console.log(`[abrirModalCeldaConFormato]  Formatters no disponible, usando fallback`);
                    prendaHtml = generarDescripcionSimple(prendaData);
                }
            } catch (e) {
                console.error('[abrirModalCeldaConFormato]  Error al formatear prenda:', e);
                console.error('[abrirModalCeldaConFormato] Stack:', e.stack);
                prendaHtml = generarDescripcionSimple(prendaData);
            }
            
            console.log(`[abrirModalCeldaConFormato] 📄 HTML generado:`, prendaHtml);
            
            htmlContenido += `<div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 8px; border-left: 4px solid #3b82f6;">
                ${prendaHtml}
            </div>`;
        });
    }
    
    console.log('[abrirModalCeldaConFormato]  HTML FINAL A MOSTRAR:', htmlContenido);
    
    // Crear y mostrar el modal
    const modal = document.createElement('div');
    modal.id = 'modal-celda-formateada';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        border-radius: 12px;
        max-width: 800px;
        width: 100%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    `;
    
    content.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px 24px; border-bottom: 1px solid #e5e7eb; background: #f9fafb; border-radius: 12px 12px 0 0;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 600; color: #111827;">${titulo}</h2>
            <button onclick="this.closest('#modal-celda-formateada').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px;">×</button>
        </div>
        <div style="padding: 24px;">
            ${htmlContenido}
        </div>
    `;
    
    modal.appendChild(content);
    document.body.appendChild(modal);
    
    // Cerrar al hacer clic fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
    
    // Cerrar con ESC
    const handleEscape = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', handleEscape);
        }
    };
    document.addEventListener('keydown', handleEscape);
}

function cerrarModalCeldaFormateada() {
    const modal = document.getElementById('modal-celda-formateada');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Funciones auxiliares
function generarDescripcionSimple(prenda) {
    console.log('[generarDescripcionSimple]  INPUT:', prenda);
    let html = '';
    
    // Título
    if (prenda.nombre_prenda) {
        html += `<strong style="font-size: 13.4px;">PRENDA: ${prenda.nombre_prenda.toUpperCase()}</strong><br>`;
        console.log('[generarDescripcionSimple]  Nombre agregado');
    }
    
    // Atributos básicos
    if (prenda.tela || prenda.color || prenda.manga) {
        let attrs = [];
        if (prenda.tela) {
            attrs.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
            console.log('[generarDescripcionSimple]  Tela:', prenda.tela);
        }
        if (prenda.color) {
            attrs.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
            console.log('[generarDescripcionSimple]  Color:', prenda.color);
        }
        if (prenda.manga) {
            attrs.push(`<strong>MANGA:</strong> ${prenda.manga.toUpperCase()}`);
            console.log('[generarDescripcionSimple]  Manga:', prenda.manga);
        }
        html += attrs.join(' | ') + '<br>';
    }
    
    // Descripción - Limpiar basura del inicio
    if (prenda.descripcion) {
        console.log('[generarDescripcionSimple] 📝 Descripción RAW:', prenda.descripcion);
        let desc = String(prenda.descripcion);
        // Limpiar líneas de basura del inicio (DSFSDFS, etc)
        desc = desc.split('\n').filter(linea => {
            const trimmed = linea.trim();
            // Saltar líneas basura
            if (!trimmed) return false;
            if (trimmed.match(/^[A-Z]{5,}[A-Z\s]{0,10}$/i) && 
                !trimmed.match(/^(PRENDA|TALLA|TELA|COLOR|MANGA|BOLSILLO|BOTÓN|CREMALLERA|DESCRIPCIÓN|DAMA|HOMBRE)/i)) {
                console.log('[generarDescripcionSimple] 🚫 Línea basura filtrada:', trimmed);
                return false;
            }
            return true;
        }).join('\n');
        
        if (desc.trim()) {
            html += desc + '<br>';
            console.log('[generarDescripcionSimple]  Descripción agregada (después de limpiar)');
        }
    }
    
    console.log('[generarDescripcionSimple] 📄 OUTPUT HTML:', html);
}

// Función para normalizar datos de prenda
function normalizarPrendaData(prendaData) {
    const normalized = { ...prendaData };
    
    // Normalizar campos que puedan ser objetos
    Object.keys(normalized).forEach(key => {
        if (normalized[key] && typeof normalized[key] === 'object' && !Array.isArray(normalized[key])) {
            // Si es un objeto con propiedades comunes, convertir a string
            if (normalized[key].nombre) {
                normalized[key] = normalized[key].nombre;
            } else if (normalized[key].id) {
                normalized[key] = `ID: ${normalized[key].id}`;
            } else {
                normalized[key] = JSON.stringify(normalized[key]);
            }
        }
    });
    
    return normalized;
}

// Función fallback para generar HTML
function generarFallbackHTML(prendaData) {
    return `
        <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
            <h3 style="margin: 0 0 12px 0; font-size: 16px; font-weight: 600; color: #111827;">
                ${prendaData.nombre_prenda || 'Prenda sin nombre'}
            </h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 14px;">
                ${prendaData.talla ? `<div><strong>Talla:</strong> ${prendaData.talla}</div>` : ''}
                ${prendaData.tela ? `<div><strong>Tela:</strong> ${prendaData.tela}</div>` : ''}
                ${prendaData.color ? `<div><strong>Color:</strong> ${prendaData.color}</div>` : ''}
                ${prendaData.manga ? `<div><strong>Manga:</strong> ${prendaData.manga}</div>` : ''}
                ${prendaData.descripcion ? `<div><strong>Descripción:</strong> ${prendaData.descripcion}</div>` : ''}
            </div>
        </div>
    `;
}

// Función para obtener datos de la prenda asociada al recibo (igual que en registros)
function obtenerDatosPrendaRecibo(reciboId, titulo) {
    console.log(`[obtenerDatosPrendaRecibo] 📌 Obteniendo datos para recibo ID: ${reciboId}`);
    
    // Obtener pedido_produccion_id desde la base de datos usando el recibo_id
    fetch(`/api/recibos/${reciboId}/pedido`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(datos => {
            console.log(`[obtenerDatosPrendaRecibo] 📄 Datos del recibo recibidos:`, datos);
            
            if (!datos.pedido_produccion_id) {
                console.error('No se encontró pedido_produccion_id para el recibo:', reciboId);
                alert('No se pudo identificar el pedido asociado a este recibo');
                return;
            }
            
            const pedidoProduccionId = datos.pedido_produccion_id;
            console.log(`[obtenerDatosPrendaRecibo] 📋 Pedido ID encontrado: ${pedidoProduccionId}`);
            
            // Obtener datos de la prenda del pedido (igual que en registros)
            fetch(`/api/pedidos/${pedidoProduccionId}/prendas`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(datosPrendas => {
                    console.log(`[obtenerDatosPrendaRecibo] 📄 Datos de prendas recibidos:`, datosPrendas);
                    
                    if (datosPrendas.data && typeof datosPrendas.data === 'object') {
                        datosPrendas = datosPrendas.data;
                    }
                    
                    if (!datosPrendas.prendas || !Array.isArray(datosPrendas.prendas) || datosPrendas.prendas.length === 0) {
                        console.warn('No se encontraron prendas para el pedido:', pedidoProduccionId);
                        alert('No se encontraron prendas para este pedido');
                        return;
                    }
                    
                    console.log(`[obtenerDatosPrendaRecibo] ✅ Prendas encontradas: ${datosPrendas.prendas.length}`);
                    
                    // Usar las prendas del pedido (igual que en registros)
                    abrirModalCeldaConFormato(titulo, datosPrendas.prendas);
                })
                .catch(error => {
                    console.error('[obtenerDatosPrendaRecibo] Error al obtener datos de prendas:', error);
                    alert('Error al cargar los datos de la prenda: ' + error.message);
                });
        })
        .catch(error => {
            console.error('[obtenerDatosPrendaRecibo] Error al obtener datos del recibo:', error);
            alert('Error al identificar el pedido asociado: ' + error.message);
        });
}

// Función para abrir modal de novedades específicas de recibo (NUEVO SISTEMA)
function openNovedadesModalRecibo(button) {
    // Obtener datos desde los data attributes del botón
    const pedidoId = button.getAttribute('data-pedido-id');
    const numeroRecibo = button.getAttribute('data-numero-recibo');
    const novedadesActuales = button.getAttribute('data-novedades') || '';
    
    console.log(`[openNovedadesModalRecibo] 📝 Abriendo modal para pedido: ${pedidoId}, recibo: ${numeroRecibo}`);
    console.log(`[openNovedadesModalRecibo] Novedades actuales:`, novedadesActuales);
    
    // Esperar a que el script de novedades esté disponible
    if (typeof abrirModalNovedadesRecibo === 'function') {
        abrirModalNovedadesRecibo(pedidoId, numeroRecibo);
        return;
    }
    
    // Si no está disponible, esperar un poco y reintentar
    setTimeout(() => {
        if (typeof abrirModalNovedadesRecibo === 'function') {
            abrirModalNovedadesRecibo(pedidoId, numeroRecibo);
        } else {
            console.warn('[openNovedadesModalRecibo] Sistema nuevo no disponible, usando fallback');
            // Fallback simple: mostrar alerta con las novedades actuales
            alert(`Novedades del recibo ${numeroRecibo}:\n\n${novedadesActuales || 'Sin novedades'}`);
        }
    }, 100);
}

// Funciones para el menú de acciones
document.addEventListener('DOMContentLoaded', function() {
    console.log('[Menu] Inicializando menú de acciones');
});

// Event listeners globales para menú de acciones
document.addEventListener('click', function(e) {
    // Toggle del menú al hacer clic en el botón de acción
    if (e.target.closest('.action-view-btn')) {
        e.preventDefault();
        e.stopPropagation();
        
        const btn = e.target.closest('.action-view-btn');
        const ordenId = btn.getAttribute('data-orden-id');
        const menu = document.querySelector(`.action-menu[data-orden-id="${ordenId}"]`);
        
        if (menu) {
            // Cerrar todos los demás menús
            document.querySelectorAll('.action-menu').forEach(m => {
                if (m !== menu) {
                    m.classList.remove('show', 'active');
                }
            });
            
            // Toggle el menú actual
            menu.classList.toggle('show');
            menu.classList.toggle('active');
        }
    }
    
    // Cerrar menú al hacer clic en una opción
    if (e.target.closest('.action-menu-item')) {
        // Cerrar todos los menús
        document.querySelectorAll('.action-menu').forEach(m => {
            m.classList.remove('show', 'active');
        });
    }
    
    // Cerrar menús al hacer clic fuera
    if (!e.target.closest('.action-view-btn') && !e.target.closest('.action-menu')) {
        document.querySelectorAll('.action-menu').forEach(m => {
            m.classList.remove('show', 'active');
        });
    }
});</script>
@endpush
