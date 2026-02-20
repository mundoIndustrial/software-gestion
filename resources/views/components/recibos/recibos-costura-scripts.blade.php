@push('scripts')
<!-- Scripts para Recibos/Procesos -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/loader.js') }}"></script>

<!-- Script para el modal de seguimiento -->
<script src="{{ asset('js/orders js/tracking-modal-handler.js') }}?v={{ time() }}"></script>

<!-- Scripts para la funcionalidad de Día de Entrega -->
<script src="{{ asset('js/orders js/modules/diaEntregaModule.js') }}?v={{ time() }}"></script>

<!-- Scripts para Formatters -->
<script type="module" src="{{ asset('js/modulos/pedidos-recibos/utils/Formatters.js') }}?v={{ time() }}"></script>

<!-- Script para el modal de novedades -->
<script src="{{ asset('js/orders js/novedades-modal.js') }}?v={{ time() }}"></script>

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
    mostrarModalCeldaFormateado(titulo, htmlContenido);
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
    return html;
}

function normalizarPrendaData(prenda) {
    if (!prenda) return prenda;
    
    console.log('[normalizarPrendaData] INPUT - Prenda original:', prenda);
    
    const normalizado = { ...prenda };
    
    // Lista ampliada de campos que deben ser strings
    const stringsFields = [
        'nombre', 'nombre_prenda', 'tela', 'color', 'manga', 'ref', 'referencia',
        'descripcion', 'genero', 'broche', 'numero', 'numero_prenda'
    ];
    
    for (let field of stringsFields) {
        if (normalizado[field]) {
            console.log(`[normalizarPrendaData] Campo "${field}": tipo=${typeof normalizado[field]}, valor=`, normalizado[field]);
            // Si es un objeto con propiedad 'nombre', extraer el valor
            if (typeof normalizado[field] === 'object' && normalizado[field] !== null) {
                normalizado[field] = normalizado[field].nombre || normalizado[field].name || String(normalizado[field]);
                console.log(`[normalizarPrendaData]   → Convertido a: "${normalizado[field]}"`);
            } else if (normalizado[field] !== null && normalizado[field] !== undefined) {
                normalizado[field] = String(normalizado[field]).trim();
            }
        }
    }
    
    // Asegurar que genero tenga un valor por defecto
    if (!normalizado.genero || normalizado.genero === '') {
        normalizado.genero = 'DAMA';
    }
    
    // Normalizar tallas (puede ser array de objetos o array de strings)
    if (normalizado.tallas && Array.isArray(normalizado.tallas)) {
        console.log('[normalizarPrendaData] Tallas detectadas:', normalizado.tallas.length);
        normalizado.tallas = normalizado.tallas.map(t => {
            if (typeof t === 'object' && t !== null) {
                return {
                    genero: String(t.genero || '').toUpperCase(),
                    talla: String(t.talla || ''),
                    cantidad: parseInt(t.cantidad) || 0
                };
            }
            return t;
        });
    } else {
        normalizado.tallas = [];
    }
    
    console.log('[normalizarPrendaData] OUTPUT - Prenda normalizada:', normalizado);
    return normalizado;
}

function mostrarModalCeldaFormateado(titulo, contenidoHtml) {
    console.log('[mostrarModalCeldaFormateado] 🔓 Abriendo modal');
    console.log('[mostrarModalCeldaFormateado] Título:', titulo);
    console.log('[mostrarModalCeldaFormateado] HTML contenido (primeros 500 chars):', contenidoHtml.substring(0, 500));
    
    // Crear el modal si no existe
    let modal = document.getElementById('modal-celda-formateada');
    if (!modal) {
        modal = document.createElement('div');
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
        document.body.appendChild(modal);
    }
    
    // Contenido del modal
    modal.innerHTML = `
        <div style="background: white; border-radius: 16px; max-width: 800px; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);">
            <div style="padding: 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;">${titulo}</h3>
                <button onclick="cerrarModalCeldaFormateada()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s;">
                    ×
                </button>
            </div>
            <div style="padding: 24px; color: #374151; line-height: 1.6;">
                ${contenidoHtml}
            </div>
        </div>
    `;
    
    // Mostrar el modal
    modal.style.display = 'flex';
    
    // Cerrar al hacer clic fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            cerrarModalCeldaFormateada();
        }
    });
}

// Cargar Formatters y hacerlo disponible globalmente
setTimeout(() => {
    // Intentar importar Formatters y hacerlo disponible globalmente
    import('{{ asset('js/modulos/pedidos-recibos/utils/Formatters.js') }}')
        .then(module => {
            window.Formatters = module.Formatters;
            console.log('✅ Formatters cargado y disponible globalmente');
        })
        .catch(error => {
            console.warn('⚠️ No se pudo cargar Formatters, se usará fallback');
        });
}, 100);

function verDetallesRecibo(reciboId) {
    // Buscar la fila del recibo para obtener el pedido_produccion_id
    const fila = document.querySelector(`tr[data-orden-id="${reciboId}"]`);
    if (!fila) {
        alert('No se encontró el recibo');
        return;
    }
    
    // Intentar obtener el enlace del pedido para extraer el pedido_produccion_id
    const enlacePedido = fila.querySelector('a[href*="/registros/"]');
    let pedidoProduccionId = null;
    
    if (enlacePedido) {
        const href = enlacePedido.getAttribute('href');
        const match = href.match(/\/registros\/(\d+)/);
        if (match) {
            pedidoProduccionId = match[1];
        }
    }
    
    if (!pedidoProduccionId) {
        alert('No se pudo identificar el pedido asociado');
        return;
    }
    
    // Redirigir a la vista de detalles del pedido
    window.location.href = `/registros/${pedidoProduccionId}`;
}

// Función para cerrar el modal overlay
function closeModalOverlay() {
    const modal = document.getElementById('modal-overlay');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Funciones para el menú de acciones
document.addEventListener('DOMContentLoaded', function() {
    // Manejo del menú de acciones
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
    });
    
    // Cerrar menús al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.action-view-btn') && !e.target.closest('.action-menu')) {
            document.querySelectorAll('.action-menu').forEach(m => {
                m.classList.remove('show', 'active');
            });
        }
    });
});
</script>
@endpush
