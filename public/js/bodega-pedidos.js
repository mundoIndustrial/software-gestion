/**
 * BODEGA - Sistema de Gestión de Pedidos
 * JavaScript Vanilla • Operaciones AJAX
 * Febrero 2026
 */

/**
 * Obtener CSRF token del meta tag
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

/**
 * Toggle menú de usuario
 */
function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

if (typeof window.usuarioActualId === 'undefined' || window.usuarioActualId === null) {
    const metaUserId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    if (metaUserId) {
        const parsed = Number(metaUserId);
        window.usuarioActualId = Number.isFinite(parsed) ? parsed : metaUserId;
    }
}

if (typeof window.abrirModalNotas !== 'function') {
    window.abrirModalNotas = function(numeroPedido, talla, nombreItem, tipoItem, tallaReal) {
        try {
            window.__notasContext = {
                numero_pedido: numeroPedido || '',
                talla: talla || ''
            };

            const modal = document.getElementById('modalNotas');
            if (!modal) {
                console.error('No existe #modalNotas en esta vista');
                return;
            }

            const numeroSpan = document.getElementById('modalNotasNumeroPedido');
            if (numeroSpan) numeroSpan.textContent = numeroPedido || '';

            const articuloSpan = document.getElementById('modalNotasArticulo');
            if (articuloSpan) {
                let textoArticulo = nombreItem || '';
                if (tipoItem === 'prenda' && tallaReal) {
                    textoArticulo += ` - ${tallaReal}`;
                }
                articuloSpan.textContent = textoArticulo;
            }

            const nuevaContent = document.getElementById('notasNuevaContent') || document.getElementById('nuevaNota');
            if (nuevaContent) nuevaContent.value = '';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.style.display = 'flex';

            if (typeof window.cargarNotas === 'function') {
                window.cargarNotas(numeroPedido, talla);
            }
        } catch (e) {
            console.error('Error en abrirModalNotas fallback:', e);
        }
    };
}

if (typeof window.cargarNotas !== 'function') {
    window.cargarNotas = async function(numeroPedido, talla) {
        try {
            const historial = document.getElementById('notasHistorial');
            if (historial) {
                historial.innerHTML = '<div class="flex justify-center items-center py-8"><span class="text-slate-500">⏳ Cargando notas...</span></div>';
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch('/gestion-bodega/notas/obtener', {
                method: 'POST',
                cache: 'no-store',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    numero_pedido: numeroPedido,
                    talla: talla,
                })
            });

            const data = await response.json().catch(() => null);
            const notas = (data && data.success && Array.isArray(data.data)) ? data.data : [];

            if (!response.ok || !data || data.success === false) {
                if (historial) {
                    historial.innerHTML = '<div class="text-center text-red-600 py-6">Error al cargar notas</div>';
                }
                return;
            }

            let textAreaContent = '';
            if (notas.length === 0) {
                if (historial) {
                    historial.innerHTML = '<div class="text-center text-slate-500 py-6">No hay notas</div>';
                }
            } else {
                let html = '<div class="space-y-4">';
                notas.forEach(nota => {
                    const puedeEditar = (String(nota.usuario_id) === String(window.usuarioActualId)) || (window.__usuarioEsAdmin === true);
                    const contenidoSeguro = String(nota.contenido ?? '').replace(/\\/g, '\\\\').replace(/`/g, '\\`');
                    const botones = puedeEditar ? `
                        <button onclick="window.editarNota(${nota.id}, '${numeroPedido}', '${talla}')" style="border:none;background:#e2e8f0;color:#0f172a;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;" title="Editar">✏️</button>
                        <button onclick="window.eliminarNota(${nota.id}, '${numeroPedido}', '${talla}')" style="border:none;background:#fee2e2;color:#991b1b;border-radius:6px;padding:4px 8px;cursor:pointer;font-size:12px;" title="Eliminar">🗑️</button>
                    ` : '';

                    html += `
                        <div data-nota-id="${nota.id}" data-numero-pedido="${numeroPedido}" data-talla="${talla}" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px;">
                            <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                                <div style="font-weight:700;color:#0f172a;font-size:13px;">${nota.usuario_nombre ?? ''}</div>
                                <div style="display:flex;gap:10px;align-items:center;">
                                    <div style="color:#64748b;font-size:12px;white-space:nowrap;">${nota.fecha_completa ?? ((nota.fecha ?? '') + ' ' + (nota.hora ?? ''))}</div>
                                    ${botones}
                                </div>
                            </div>
                            <div class="nota-contenido" style="margin:0;color:#1e293b;font-size:13px;white-space:pre-wrap;">${nota.contenido ?? ''}</div>
                        </div>
                    `;

                    textAreaContent += `${nota.usuario_nombre ?? ''} - ${nota.contenido ?? ''}\n`;
                });
                html += '</div>';
                if (historial) {
                    historial.innerHTML = html;
                }
            }

            // Mostrar notas en el textarea readonly (solo visual; NO se persiste en observaciones_bodega)
            const tallaNorm = String(talla ?? '').toLowerCase();
            const observacionesInputs = Array.from(
                document.querySelectorAll(`.observaciones-input[data-numero-pedido="${numeroPedido}"]`)
            ).filter(input => String(input?.dataset?.talla ?? '').toLowerCase() === tallaNorm);

            observacionesInputs.forEach(input => {
                input.value = textAreaContent.trim();
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.style.height = 'auto';
                input.style.height = (input.scrollHeight) + 'px';
            });
        } catch (e) {
            console.error('Error en cargarNotas fallback:', e);
            const historial = document.getElementById('notasHistorial');
            if (historial) historial.innerHTML = '<div class="text-center text-red-600 py-6">Error al cargar notas</div>';
        }
    };
}

// Cargar notas automáticamente en la columna de observaciones al entrar a la vista
document.addEventListener('DOMContentLoaded', function() {
    try {
        if (typeof window.cargarNotas !== 'function') return;

        const textareas = Array.from(document.querySelectorAll('.observaciones-input'));
        if (textareas.length === 0) return;

        const claves = [];
        const seen = new Set();
        textareas.forEach(t => {
            const numeroPedido = t?.dataset?.numeroPedido;
            const talla = t?.dataset?.talla;
            if (!numeroPedido || !talla) return;
            const key = `${numeroPedido}|${talla}`;
            if (seen.has(key)) return;
            seen.add(key);
            claves.push({ numeroPedido, talla });
        });

        // Ejecutar en serie con pequeños delays para evitar demasiadas requests simultáneas
        const ejecutar = async () => {
            for (const item of claves) {
                await window.cargarNotas(item.numeroPedido, item.talla);
                await new Promise(r => setTimeout(r, 30));
            }
        };

        ejecutar();
    } catch (e) {
        console.error('Error auto-cargando notas para observaciones:', e);
    }
});

if (typeof window.editarNota !== 'function') {
    window.editarNota = function(notaId, numeroPedido, talla) {
        const card = document.querySelector(`[data-nota-id="${notaId}"]`);
        if (!card) return;

        const contentEl = card.querySelector('.nota-contenido');
        if (!contentEl) return;

        if (card.getAttribute('data-editing') === '1') {
            const textarea = card.querySelector('textarea');
            if (textarea) textarea.focus();
            return;
        }

        const originalContent = (contentEl.textContent ?? '').toString();
        card.setAttribute('data-editing', '1');
        card.setAttribute('data-original-content', originalContent);

        const textarea = document.createElement('textarea');
        textarea.value = originalContent;
        textarea.rows = 3;
        textarea.style.width = '100%';
        textarea.style.minHeight = '60px';
        textarea.style.resize = 'vertical';
        textarea.style.border = '1px solid #cbd5e1';
        textarea.style.borderRadius = '8px';
        textarea.style.padding = '8px';
        textarea.style.fontSize = '13px';
        textarea.style.color = '#0f172a';
        textarea.style.background = '#ffffff';

        const actions = document.createElement('div');
        actions.style.display = 'flex';
        actions.style.gap = '8px';
        actions.style.marginTop = '8px';
        actions.style.justifyContent = 'flex-end';

        const btnGuardar = document.createElement('button');
        btnGuardar.type = 'button';
        btnGuardar.textContent = 'Guardar';
        btnGuardar.style.border = 'none';
        btnGuardar.style.background = '#0ea5e9';
        btnGuardar.style.color = '#ffffff';
        btnGuardar.style.borderRadius = '8px';
        btnGuardar.style.padding = '6px 10px';
        btnGuardar.style.cursor = 'pointer';
        btnGuardar.style.fontSize = '12px';

        const btnCancelar = document.createElement('button');
        btnCancelar.type = 'button';
        btnCancelar.textContent = 'Cancelar';
        btnCancelar.style.border = 'none';
        btnCancelar.style.background = '#e2e8f0';
        btnCancelar.style.color = '#0f172a';
        btnCancelar.style.borderRadius = '8px';
        btnCancelar.style.padding = '6px 10px';
        btnCancelar.style.cursor = 'pointer';
        btnCancelar.style.fontSize = '12px';

        actions.appendChild(btnCancelar);
        actions.appendChild(btnGuardar);

        contentEl.replaceWith(textarea);
        textarea.insertAdjacentElement('afterend', actions);

        const focusTextarea = () => {
            textarea.focus();
            const len = textarea.value.length;
            textarea.setSelectionRange(len, len);
        };

        setTimeout(focusTextarea, 0);

        const cancelar = () => {
            const restored = document.createElement('div');
            restored.className = 'nota-contenido';
            restored.style.margin = '0';
            restored.style.color = '#1e293b';
            restored.style.fontSize = '13px';
            restored.style.whiteSpace = 'pre-wrap';
            restored.textContent = card.getAttribute('data-original-content') || '';

            actions.remove();
            textarea.replaceWith(restored);
            card.setAttribute('data-editing', '0');
        };

        btnCancelar.addEventListener('click', cancelar);

        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                cancelar();
            }
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                btnGuardar.click();
            }
        });

        btnGuardar.addEventListener('click', async () => {
            const contenido = (textarea.value || '').trim();
            if (!contenido) {
                alert('La nota no puede estar vacía');
                textarea.focus();
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            btnGuardar.disabled = true;
            btnCancelar.disabled = true;

            try {
                const r = await fetch(`/gestion-bodega/notas/${notaId}/actualizar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ contenido })
                });

                const data = await r.json().catch(() => null);
                if (!r.ok || !data || data.success === false) {
                    alert('Error: ' + (data?.message || 'No se pudo actualizar la nota'));
                    btnGuardar.disabled = false;
                    btnCancelar.disabled = false;
                    return;
                }

                if (typeof window.cargarNotas === 'function') {
                    window.cargarNotas(numeroPedido, talla);
                }
            } catch (err) {
                console.error('Error editarNota:', err);
                alert('Error al actualizar la nota');
                btnGuardar.disabled = false;
                btnCancelar.disabled = false;
            }
        });
    };
}

if (typeof window.eliminarNota !== 'function') {
    window.eliminarNota = function(notaId, numeroPedido, talla) {
        if (!confirm('¿Eliminar esta nota?')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch(`/gestion-bodega/notas/${notaId}/eliminar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            }
        })
        .then(r => r.json())
        .then(data => {
            if (!data || data.success === false) {
                alert('Error: ' + (data?.message || 'No se pudo eliminar la nota'));
                return;
            }
            if (typeof window.cargarNotas === 'function') {
                window.cargarNotas(numeroPedido, talla);
            }
        })
        .catch(err => {
            console.error('Error eliminarNota:', err);
            alert('Error al eliminar la nota');
        });
    };
}

if (typeof window.cerrarModalNotas !== 'function') {
    window.cerrarModalNotas = function() {
        const modal = document.getElementById('modalNotas');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.style.display = '';
    };
}

// Normalizar comportamiento aunque la vista defina sus propias funciones
(function() {
    const originalAbrirModalNotas = window.abrirModalNotas;
    const originalCerrarModalNotas = window.cerrarModalNotas;

    window.abrirModalNotas = function(...args) {
        const result = (typeof originalAbrirModalNotas === 'function') ? originalAbrirModalNotas.apply(this, args) : undefined;
        const modal = document.getElementById('modalNotas');
        if (modal && !modal.classList.contains('hidden')) {
            modal.classList.add('flex');
            modal.style.display = 'flex';
        }
        return result;
    };

    window.cerrarModalNotas = function(...args) {
        const result = (typeof originalCerrarModalNotas === 'function') ? originalCerrarModalNotas.apply(this, args) : undefined;
        const modal = document.getElementById('modalNotas');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.style.display = '';
        }
        if (document && document.body) {
            document.body.style.overflow = '';
        }
        return result;
    };
})();

if (typeof window.guardarNota !== 'function') {
    window.guardarNota = async function() {
        try {
            const ctx = window.__notasContext || {};
            const numeroPedido = ctx.numero_pedido || document.getElementById('modalNotasNumeroPedido')?.textContent || '';
            const talla = ctx.talla || '';

            const textarea = document.getElementById('notasNuevaContent') || document.getElementById('nuevaNota');
            const contenido = (textarea?.value || '').trim();

            if (!numeroPedido || !talla) {
                console.error('Contexto de notas incompleto', { numeroPedido, talla });
                return;
            }

            if (!contenido) {
                alert('Por favor, escribe una nota antes de guardar');
                return;
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch('/gestion-bodega/notas/guardar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    numero_pedido: numeroPedido,
                    talla: talla,
                    contenido: contenido,
                })
            });

            const data = await response.json().catch(() => null);
            if (!response.ok || !data || data.success === false) {
                alert('Error: ' + ((data && data.message) ? data.message : 'No se pudo guardar la nota'));
                return;
            }

            if (textarea) textarea.value = '';

            if (typeof window.cargarNotas === 'function') {
                window.cargarNotas(numeroPedido, talla);
            }

            // Cerrar modal al guardar
            if (typeof window.cerrarModalNotas === 'function') {
                window.cerrarModalNotas();
            }
        } catch (e) {
            console.error('Error en guardarNota fallback:', e);
            alert('Error al guardar la nota: ' + (e.message || e));
        }
    };
}

/**
 * Cerrar menú de usuario cuando se haga click fuera
 */
document.addEventListener('click', function(event) {
    const userMenu = document.getElementById('userMenu');
    const userMenuBtn = document.getElementById('userMenuBtn');
    
    if (userMenu && userMenuBtn && !userMenu.contains(event.target) && !userMenuBtn.contains(event.target)) {
        userMenu.classList.add('hidden');
    }
});

/**
 * Filtrar tabla según criterios
 */
function filterTable() {
    const searchInput = document.getElementById('searchInput');
    const searchValue = (searchInput?.value || '').toLowerCase().trim();

    const rows = document.querySelectorAll('.pedido-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowText = row.getAttribute('data-search') || '';

        let showRow = true;

        if (searchValue && !rowText.includes(searchValue)) showRow = false;

        row.style.display = showRow ? '' : 'none';
        if (showRow) visibleCount++;
    });
}

/**
 * Limpiar búsqueda
 */
function limpiarBuscador() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        filterTable();
    }
}

/**
 * Actualizar tabla sin recargar la página
 */
async function actualizarTabla() {
    const btnActualizar = document.getElementById('btnActualizar');
    if (btnActualizar) {
        btnActualizar.disabled = true;
        btnActualizar.innerHTML = '⏳ Actualizando...';
    }

    try {
        const url = window.location.pathname;
        
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }

        const html = await response.text();
        
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');
        
        const newTableBody = newDoc.getElementById('pedidosTableBody');
        const currentTableBody = document.getElementById('pedidosTableBody');
        
        if (newTableBody && currentTableBody) {
            currentTableBody.innerHTML = newTableBody.innerHTML;
            
            inicializarColoresDelaPagina();
            
            limpiarBuscador();
            
            mostrarToast('✓ Tabla actualizada correctamente');
        }
    } catch (error) {
        console.error('Error al actualizar la tabla:', error);
        mostrarToast(' Error al actualizar la tabla');
    } finally {
        if (btnActualizar) {
            btnActualizar.disabled = false;
            btnActualizar.innerHTML = ' Actualizar';
        }
    }
}

/**
 * Mostrar notificación Toast
 */
function mostrarToast(mensaje) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    if (toast && toastMessage) {
        toastMessage.textContent = mensaje;
        toast.classList.remove('hidden');
        toast.style.display = 'flex';
        
        setTimeout(() => {
            toast.classList.add('hidden');
            toast.style.display = 'none';
        }, 3000);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // ==================== ELEMENTOS DOM ====================
    const searchInput = document.getElementById('searchInput');

    // ==================== INICIALIZACIÓN ====================
    initializeEventListeners();

    /**
     * Inicializar event listeners
     */
    function initializeEventListeners() {
        // Filtro de búsqueda
        if (searchInput) searchInput.addEventListener('input', filterTable);
    }

});

/**
 * Guardar detalles de un pedido completo
 */
async function guardarPedidoCompleto(numeroPedido) {
    try {
        // Recolectar solo filas con datos válidos del pedido
        const detalles = [];
        const filas = document.querySelectorAll(`tr[data-numero-pedido="${numeroPedido}"]`);
        
        filas.forEach((fila, index) => {
            const areaSelect = fila.querySelector('.area-select');
            const estadoSelect = fila.querySelector('.estado-select');
            
            // Obtener talla desde el data-talla del selector (más confiable)
            const talla = estadoSelect?.getAttribute('data-talla');
            
            // Saltar si no hay talla
            if (!talla) {
                return;
            }
            
            // Obtener ASESOR desde data-asesor de la fila
            const asesor = fila.getAttribute('data-asesor') || '';
            
            // Obtener EMPRESA desde data-empresa (ir hacia arriba si es necesario por rowspan)
            let empresa = fila.getAttribute('data-empresa') || '';
            if (!empresa) {
                // Si no está en la fila actual, buscar en la celda td[data-empresa]
                const empresaCell = fila.parentElement.querySelector('td[data-empresa]');
                empresa = empresaCell?.getAttribute('data-empresa') || '';
            }
            
            // Obtener CANTIDAD desde la celda de cantidad
            const cantidadCell = fila.querySelector('td[data-cantidad]');
            const cantidad = cantidadCell?.getAttribute('data-cantidad') || '0';
            
            // Obtener nombre de la prenda/artículo desde la columna DESCRIPCIÓN
            // Buscar en el contexto del pedido actual (porque el td[data-prenda-nombre] puede tener rowspan)
            const numeroPedidoActual = fila.getAttribute('data-numero-pedido');
            let descripcionCell = fila.querySelector('td[data-prenda-nombre]');
            
            // Si no está en la fila actual (por rowspan), buscar en todo el grupo del pedido
            if (!descripcionCell) {
                descripcionCell = document.querySelector(`tr[data-numero-pedido="${numeroPedidoActual}"] td[data-prenda-nombre]`);
            }
            
            let nombrePrenda = '';
            if (descripcionCell) {
                // Para prendas: <div class="font-bold text-slate-900 mb-2">{{ $nombre }}</div>
                // Para EPPs: <div class="font-semibold text-slate-900">{{ $nombre }}</div>
                const nombreDiv = descripcionCell.querySelector('div.font-bold');
                if (nombreDiv) {
                    nombrePrenda = nombreDiv.textContent.trim();
                } else {
                    const nombreDivEpp = descripcionCell.querySelector('div.font-semibold');
                    if (nombreDivEpp) {
                        nombrePrenda = nombreDivEpp.textContent.trim();
                    }
                }
            }
            
            const pendientesInput = fila.querySelector('.pendientes-input');
            const fechaInput = fila.querySelector('.fecha-input');
            
            // Valores actuales
            const pendientes = pendientesInput?.value || '';
            const fecha = fechaInput?.value || '';
            const area = areaSelect?.value || '';
            const estado = estadoSelect?.value || '';
            
            // Valores originales (para comparar cambios)
            const areaOriginal = areaSelect?.getAttribute('data-original-area') || '';
            const estadoOriginal = estadoSelect?.getAttribute('data-original-estado') || '';
            
            // Incluir si: tiene datos en campos de texto O cambió area O cambió estado
            const tieneContenidoTexto = pendientes || fecha;
            const areaEsCambiado = area !== areaOriginal;
            const estadoEsCambiado = estado !== estadoOriginal;
            
            // Incluir si hay cambios (verdaderos — son EPPs, válidos de incluir si cambiaron estado)
            if (tieneContenidoTexto || areaEsCambiado || estadoEsCambiado) {
                
                detalles.push({
                    talla: talla,  // Enviar talla tal como está (puede ser hash único para EPPs)
                    asesor: asesor,  // Guardar asesor
                    empresa: empresa,  // Guardar empresa
                    cantidad: parseInt(cantidad) || 0,  // Guardar cantidad como número
                    prenda_nombre: nombrePrenda,  // Guardar nombre de la prenda/artículo
                    pendientes: pendientes || null,
                    fecha_entrega: fecha || null,
                    area: area || null,
                    estado_bodega: estado || null,
                });
            }
        });
        
        if (detalles.length === 0) {
            alert('No hay cambios para guardar');
            return;
        }
        
        // DEBUG: Log de data que se envía
        console.log('📤 ENVIANDO DETALLES:', detalles);
        
        // Preparar URL
        const url = `/gestion-bodega/pedidos/${numeroPedido}/guardar-completo`;
        
        // Enviar al servidor
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                numero_pedido: numeroPedido,
                detalles: detalles,
            }),
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            console.error('Error al guardar:', data);
            alert('Error: ' + (data.message || 'No se pudieron guardar los cambios'));
            return;
        }
        
        // Mostrar modal de éxito
        mostrarModalExito(data.message);
        
        // Recargar la página para mostrar los datos guardados
        setTimeout(() => {
            location.reload();
        }, 800);
    } catch (error) {
        console.error('Error en guardarPedidoCompleto:', error);
        alert('Error: ' + error.message);
    }
}



/**
 * Funciones de Modal
 */

/**
 * Abrir modal con la factura del pedido
 */
async function abrirModalFactura(pedidoId) {
    const modal = document.getElementById('modalFactura');
    const contenido = document.getElementById('facturaContenido');
    
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    contenido.innerHTML = '<div class="flex justify-center items-center py-12"><span class="text-slate-500">⏳ Cargando factura...</span></div>';
    
    try {
        const response = await fetch(`/gestion-bodega/pedidos/${pedidoId}/factura-datos`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (!data) {
            contenido.innerHTML = '<div class="text-center text-red-600 py-6"> Error al cargar la factura</div>';
            return;
        }

        if (data.success === false) {
            contenido.innerHTML = '<div class="text-center text-red-600 py-6"> Error: ' + (data.message || 'No se pudieron cargar las prendas del pedido.') + '</div>';
            return;
        }

        const payload = (data && typeof data === 'object' && data.data) ? data.data : data;

        // Generar HTML de la factura
        const htmlFactura = generarHTMLFactura(payload);
        contenido.innerHTML = htmlFactura;
    } catch (error) {
        console.error('Error cargando factura:', error);
        contenido.innerHTML = '<div class="text-center text-red-600 py-6"> Error: ' + error.message + '</div>';
    }
}

/**
 * Cerrar modal
 */
function cerrarModalFactura() {
    const modal = document.getElementById('modalFactura');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

/**
 * Generar HTML de la factura
 */
function generarHTMLFactura(datos) {
    if (!datos || !datos.prendas || !Array.isArray(datos.prendas)) {
        return '<div style="color: #dc2626; padding: 1rem; border: 1px solid #fca5a5; border-radius: 6px; background: #fee2e2;"> Error: No se pudieron cargar las prendas del pedido.</div>';
    }

    // Generar las tarjetas de prendas
    const prendasHTML = datos.prendas.map((prenda, idx) => {
        // Variantes tabla
        let variantesHTML = '';
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            // Verificar qué columnas tienen datos
            const tieneManga = prenda.variantes.some(v => v.manga);
            const tieneBroche = prenda.variantes.some(v => v.broche);
            const tieneBolsillos = prenda.variantes.some(v => v.bolsillos);
            
            variantesHTML = `
                <table style="width: 100%; font-size: 11px; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                            <th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Talla</th>
                            <th style="padding: 6px 8px; text-align: center; font-weight: 600; color: #374151;">Cantidad</th>
                            ${tieneManga ? `<th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Manga</th>` : ''}
                            ${tieneBroche ? `<th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Botón/Broche</th>` : ''}
                            ${tieneBolsillos ? `<th style="padding: 6px 8px; text-align: left; font-weight: 600; color: #374151;">Bolsillos</th>` : ''}
                        </tr>
                    </thead>
                    <tbody>
                        ${prenda.variantes.map((var_item, varIdx) => `
                            <tr style="background: ${varIdx % 2 === 0 ? '#ffffff' : '#f9fafb'}; border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 6px 8px; font-weight: 600; color: #374151;">${var_item.talla}</td>
                                <td style="padding: 6px 8px; text-align: center; color: #6b7280;">${var_item.cantidad}</td>
                                ${tieneManga ? `
                                    <td style="padding: 6px 8px; color: #6b7280; font-size: 11px;">
                                        ${var_item.manga ? `<strong>${var_item.manga}</strong>` : '—'}
                                        ${var_item.manga_obs ? `<br><em style="color: #9ca3af; font-size: 10px;">${var_item.manga_obs}</em>` : ''}
                                    </td>
                                ` : ''}
                                ${tieneBroche ? `
                                    <td style="padding: 6px 8px; color: #6b7280; font-size: 11px;">
                                        ${var_item.broche ? `<strong>${var_item.broche}</strong>` : '—'}
                                        ${var_item.broche_obs ? `<br><em style="color: #9ca3af; font-size: 10px;">${var_item.broche_obs}</em>` : ''}
                                    </td>
                                ` : ''}
                                ${tieneBolsillos ? `
                                    <td style="padding: 6px 8px; color: #6b7280; font-size: 11px;">
                                        ${var_item.bolsillos ? `<strong>Sí</strong>` : '—'}
                                        ${var_item.bolsillos_obs ? `<br><em style="color: #9ca3af; font-size: 10px;">${var_item.bolsillos_obs}</em>` : ''}
                                    </td>
                                ` : ''}
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        }

        // Tela y color
        let telaHTML = '';
        if (prenda.telas_array && Array.isArray(prenda.telas_array) && prenda.telas_array.length > 0) {
            telaHTML = `
                <div style="margin-bottom: 12px;">
                    ${prenda.telas_array.map(tela => `
                        <div style="padding: 6px 0; border-bottom: 1px solid #f3f4f6;">
                            <span style="font-size: 11px; color: #374151;">
                                <strong>Tela:</strong> ${tela.tela_nombre || '—'} 
                                <strong style="margin-left: 12px;">Color:</strong> ${tela.color_nombre || '—'}
                                ${tela.referencia ? `<strong style="margin-left: 12px;">Ref:</strong> ${tela.referencia}` : ''}
                            </span>
                        </div>
                    `).join('')}
                </div>
            `;
        } else if (prenda.tela || prenda.color) {
            telaHTML = `
                <div style="margin-bottom: 12px; font-size: 11px; color: #374151;">
                    <strong>Tela:</strong> ${prenda.tela || '—'} 
                    ${prenda.color ? `<strong style="margin-left: 12px;">Color:</strong> ${prenda.color}` : ''}
                </div>
            `;
        }

        // Procesos
        let procesosHTML = '';
        if (prenda.procesos && Array.isArray(prenda.procesos) && prenda.procesos.length > 0) {
            procesosHTML = `
                <div style="margin-bottom: 0;">
                    ${prenda.procesos.map(proc => `
                        <div style="padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                            <div style="font-weight: 600; color: #374151; margin-bottom: 4px; font-size: 11px;">${proc.nombre || proc.tipo}</div>
                            ${proc.ubicaciones && proc.ubicaciones.length > 0 ? `
                                <div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">
                                     ${Array.isArray(proc.ubicaciones) ? proc.ubicaciones.join(' • ') : proc.ubicaciones}
                                </div>
                            ` : ''}
                            ${proc.tallas && (proc.tallas.dama && Object.keys(proc.tallas.dama).length > 0 || proc.tallas.caballero && Object.keys(proc.tallas.caballero).length > 0 || proc.tallas.unisex && Object.keys(proc.tallas.unisex).length > 0 || proc.tallas.sobremedida && Object.keys(proc.tallas.sobremedida).length > 0) ? `
                                <div style="font-size: 10px; color: #6b7280; margin-bottom: 2px;">
                                    ${[
                                        ...(proc.tallas.dama && Object.keys(proc.tallas.dama).length > 0 ? [`Dama: ${Object.entries(proc.tallas.dama).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : []),
                                        ...(proc.tallas.caballero && Object.keys(proc.tallas.caballero).length > 0 ? [`Caballero: ${Object.entries(proc.tallas.caballero).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : []),
                                        ...(proc.tallas.unisex && Object.keys(proc.tallas.unisex).length > 0 ? [`Unisex: ${Object.entries(proc.tallas.unisex).map(([talla, cantidad]) => `${talla}(${cantidad})`).join(', ')}`] : []),
                                        ...(proc.tallas.sobremedida && Object.keys(proc.tallas.sobremedida).length > 0 ? [`Sobremedida: ${Object.entries(proc.tallas.sobremedida).map(([genero, cantidad]) => `${genero}(${cantidad})`).join(', ')}`] : [])
                                    ].join(' • ')}
                                </div>
                            ` : ''}
                            ${proc.observaciones ? `
                                <div style="font-size: 10px; color: #6b7280;">
                                    ${proc.observaciones}
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        }

        return `
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 16px; padding: 16px;">
                <!-- Header simple -->
                <div style="border-bottom: 1px solid #e5e7eb; padding-bottom: 8px; margin-bottom: 12px;">
                    <div style="font-size: 14px; font-weight: 600; color: #374151;">PRENDA ${idx + 1}: ${prenda.nombre}${prenda.de_bodega ? ' <span style="color: #ea580c; font-weight: bold;">- SE SACA DE BODEGA</span>' : ''}</div>
                    ${prenda.descripcion ? `<div style="font-size: 12px; color: #6b7280; margin-top: 2px;">${prenda.descripcion}</div>` : ''}
                </div>
                
                <!-- Telas (movido aquí) -->
                ${telaHTML}
                
                <!-- Imagen pequeña -->
                ${(prenda.imagenes && prenda.imagenes.length > 0) ? `
                    <div style="float: right; margin-left: 12px; margin-bottom: 8px;">
                        <img src="${prenda.imagenes[0].ruta || prenda.imagenes[0].url || prenda.imagenes[0]}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb;">
                    </div>
                ` : ''}
                
                <!-- Contenido compacto -->
                <div style="${(prenda.imagenes && prenda.imagenes.length > 0) ? 'margin-right: 100px;' : ''}">
                    <!-- Variantes -->
                    ${variantesHTML}
                    
                    <!-- Procesos -->
                    ${procesosHTML}
                </div>
                
                <div style="clear: both;"></div>
            </div>
        `;
    }).join('');

    // EPPs
    const eppsHTML = (datos.epps && datos.epps.length > 0) ? `
        <div style="margin: 12px 0; padding: 0; background: #ffffff; border-radius: 6px; border: 1px solid #e0e7ff; overflow: hidden;">
            <div style="font-size: 12px !important; font-weight: 700; color: #1e40af; background: #f0f9ff; margin: 0; padding: 12px 12px; border-bottom: 2px solid #bfdbfe;">EPP (${datos.epps.length})</div>
            <div style="padding: 12px; space-y: 8px;">
                ${datos.epps.map(epp => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px; margin-bottom: 8px; border-left: 3px solid #3b82f6; border-radius: 2px; background: #f8fafc;">
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #1e40af; margin-bottom: 4px;">${epp.nombre_completo || epp.nombre}</div>
                            ${epp.observaciones && epp.observaciones !== '—' && epp.observaciones !== '-' ? `<div style="font-size: 11px; color: #475569;">${epp.observaciones}</div>` : ''}
                        </div>
                        <div style="font-weight: 600; color: #1e40af; font-size: 14px; margin-left: 12px;">
                            ${epp.cantidad}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    ` : '';

    // Totales
    const totalHTML = `
        <div style="margin: 12px 0; padding: 12px; background: #f3f4f6; border-radius: 6px; border: 2px solid #d1d5db; text-align: right;">
            <div style="font-size: 12px; margin-bottom: 8px;">
                <strong>Total Ítems:</strong> ${datos.total_items || 0}
            </div>
            ${datos.valor_total ? `
                <div style="font-size: 12px; margin-bottom: 8px;">
                    <strong>Subtotal:</strong> $${parseFloat(datos.valor_total).toLocaleString('es-CO')}
                </div>
            ` : ''}
            ${datos.total_general ? `
                <div style="font-size: 14px; font-weight: 700; color: #1e40af; padding-top: 8px; border-top: 2px solid #d1d5db;">
                    <strong>Total:</strong> $${parseFloat(datos.total_general).toLocaleString('es-CO')}
                </div>
            ` : ''}
        </div>
    `;

    return `
        <div>
            <!-- Header factura -->
            <div style="background: #1e3a8a; color: white; padding: 16px; border-radius: 6px; margin-bottom: 12px; text-align: center;">
                <div style="font-size: 18px; font-weight: 700; margin-bottom: 8px;">FACTURA DE PEDIDO</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 12px;">
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Número</div>
                        <div style="font-weight: 600;">${datos.numero_pedido}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Cliente</div>
                        <div style="font-weight: 600;">${datos.cliente}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Asesora</div>
                        <div style="font-weight: 600;">${datos.asesora || datos.asesor || 'N/A'}</div>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 12px; margin-top: 8px;">
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Forma de Pago</div>
                        <div style="font-weight: 600;">${datos.forma_de_pago || 'N/A'}</div>
                    </div>
                    <div>
                        <div style="font-size: 10px; opacity: 0.8;">Fecha</div>
                        <div style="font-weight: 600;">${datos.fecha || 'N/A'}</div>
                    </div>
                </div>
            </div>

            ${datos.observaciones ? `
                <div style="background: #fef3c7; border: 1px solid #fcd34d; padding: 12px; border-radius: 6px; margin-bottom: 12px; font-size: 11px;">
                    <strong style="color: #92400e;"> Observaciones:</strong>
                    <div style="margin-top: 4px; white-space: pre-wrap; color: #666;">${datos.observaciones}</div>
                </div>
            ` : ''}

            <!-- Prendas -->
            ${prendasHTML}

            <!-- EPPs -->
            ${eppsHTML}

            <!-- Totales -->
            ${totalHTML}
        </div>
    `;
}

// Inicializar cuando el DOM esté listo (solo colorear filas, sin auto-save)
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar colores de todas las filas
    inicializarColoresDelaPagina();
    
    // Agregar listener para cambios de estado (solo colorea la fila afectada)
    const selectoresEstado = document.querySelectorAll('.estado-select');
    selectoresEstado.forEach(selector => {
        selector.addEventListener('change', function() {
            colorearFilaPorEstado(this);
        });
    });
});

/**
 * Configuración de colores para bodega
 */
if (typeof window.BodegaConfig === 'undefined') {
    window.BodegaConfig = {
        ESTADO_COLORES: {
            'entregado': 'rgba(59, 130, 246, 0.1)',  // Azul claro
            'pendiente': 'rgba(234, 179, 8, 0.1)',   // Amarillo claro
            'anulado': 'rgba(239, 68, 68, 0.1)'      // Rojo claro
        },
        SELECTOR_COLORES: {
            'entregado': { bg: '#dbeafe', text: '#0c4a6e' },  // Azul muy claro
            'pendiente': { bg: '#fef3c7', text: '#78350f' },  // Amarillo muy claro
            'anulado': { bg: '#fee2e2', text: '#991b1b' }     // Rojo muy claro
        }
    };
}

/**
 * Obtener todas las celdas de datos de una fila (excluyendo Asesor, Empresa, Artículo)
 */
function obtenerCeldasDatos(fila) {
    const todasLasCeldas = fila.querySelectorAll('td');
    const celdasDatos = [];
    
    todasLasCeldas.forEach(celda => {
        // Incluir solo si NO tiene atributos de agrupación
        if (!celda.hasAttribute('data-asesor') && 
            !celda.hasAttribute('data-empresa') && 
            !celda.hasAttribute('data-prenda-nombre')) {
            celdasDatos.push(celda);
        }
    });
    
    return celdasDatos;
}

/**
 * Colorear fila según estado del selector
 * Solo colorea la fila donde cambió el estado (sin afectar otras filas)
 */
function colorearFilaPorEstado(selectorEstado) {
    const fila = selectorEstado.closest('tr');
    if (!fila) return;
    
    const estado = selectorEstado.value.trim().toLowerCase();
    const celdasDatos = obtenerCeldasDatos(fila);
    
    // Limpiar color anterior de todas las celdas
    celdasDatos.forEach(celda => {
        celda.style.backgroundColor = '';
    });
    
    // No aplicar color si no hay estado seleccionado (valor vacío)
    if (!estado || estado === '') {
        aplicarColorAlSelector(selectorEstado, '');
        return;
    }
    
    // Aplicar nuevo color según estado
    if (window.BodegaConfig.ESTADO_COLORES.hasOwnProperty(estado)) {
        const color = window.BodegaConfig.ESTADO_COLORES[estado];
        celdasDatos.forEach(celda => {
            celda.style.backgroundColor = color;
        });
    }
    
    // Aplicar color al selector
    aplicarColorAlSelector(selectorEstado, estado);
}

/**
 * Aplicar color al selector basado en el estado
 */
function aplicarColorAlSelector(selector, estado) {
    const estadoNormalizado = estado.trim().toLowerCase();
    
    // Limpiar estilos previos
    selector.style.backgroundColor = '';
    selector.style.color = '';
    
    // Aplicar nuevo color si existe
    if (window.BodegaConfig.SELECTOR_COLORES.hasOwnProperty(estadoNormalizado)) {
        const colores = window.BodegaConfig.SELECTOR_COLORES[estadoNormalizado];
        selector.style.backgroundColor = colores.bg;
        selector.style.color = colores.text;
    }
}

/**
 * Colorear todas las filas según su estado (usada en DOMContentLoaded)
 */
function inicializarColoresDelaPagina() {
    const selectoresEstado = document.querySelectorAll('.estado-select');
    selectoresEstado.forEach(selector => {
        colorearFilaPorEstado(selector);
    });
}

/**
 * Mostrar modal de éxito
 */
function mostrarModalExito(mensaje) {
    const modal = document.getElementById('modalExito');
    const modalMensaje = document.getElementById('modalMensajeExito');
    
    if (modal && modalMensaje) {
        modalMensaje.textContent = mensaje || 'Cambios guardados correctamente';
        modal.style.display = 'flex';
        
        // Cerrar modal al hacer click en el botón
        const btnCerrar = document.getElementById('btnCerrarModalExito');
        if (btnCerrar) {
            btnCerrar.onclick = function() {
                modal.style.display = 'none';
            };
        }
        
        // Cerrar modal al hacer click fuera
        modal.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        };
    }
}

/**
 * Guardar fila completa de bodega_detalles_talla
 */
function guardarFilaCompleta(btnGuardar, numeroPedido, talla) {
    // Obtener todos los valores de la fila
    const pendientesInput = document.querySelector(
        `.pendientes-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
    );
    const fechaPedidoInput = document.querySelector(
        `.fecha-pedido-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
    );
    const fechaEntregaInput = document.querySelector(
        `.fecha-input[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
    );
    const areaSelect = document.querySelector(
        `.area-select[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
    );
    const estadoSelect = document.querySelector(
        `.estado-select[data-numero-pedido="${numeroPedido}"][data-talla="${talla}"]`
    );

    // Validar que existan los elementos
    if (!pendientesInput || !estadoSelect) {
        alert('Error: No se encontraron los campos de la fila');
        return;
    }

    // Obtener la fila para extraer otros campos
    const fila = pendientesInput.closest('tr');
    const asesor = (fila.getAttribute('data-asesor') || '').trim();
    const empresa = (fila.getAttribute('data-empresa') || '').trim();
    
    // Obtener CANTIDAD desde el atributo data-cantidad del estado-select
    const cantidad = parseInt(estadoSelect.getAttribute('data-cantidad') || '0');

    // Obtener los datos de los IDs
    const pedidoProduccionId = pendientesInput.getAttribute('data-pedido-produccion-id');
    const reciboPrendaId = pendientesInput.getAttribute('data-recibo-prenda-id');
    
    // Obtener PRENDA_NOMBRE desde el select
    const prendaNombre = estadoSelect.getAttribute('data-prenda-nombre') || '';
    
    const lastUpdatedAt = new Date().toISOString();

    console.log(`[GUARDAR] numeroPedido=${numeroPedido}, talla=${talla}`);
    console.log(`[GUARDAR] asesor='${asesor}' (largo: ${asesor.length})`);
    console.log(`[GUARDAR] empresa='${empresa}' (largo: ${empresa.length})`);
    console.log(`[GUARDAR] data-asesor=${fila.getAttribute('data-asesor')}`);
    console.log(`[GUARDAR] data-empresa=${fila.getAttribute('data-empresa')}`);
    console.log(`[GUARDAR] cantidad final: ${cantidad}`);

    const datosAGuardar = {
        numero_pedido: numeroPedido,
        talla: talla,
        prenda_nombre: prendaNombre,
        cantidad: cantidad,
        asesor: asesor,
        empresa: empresa,
        pendientes: pendientesInput.value.trim(),
        fecha_pedido: fechaPedidoInput?.value || null,
        fecha_entrega: fechaEntregaInput?.value || null,
        area: areaSelect?.value || null,
        estado_bodega: estadoSelect?.value || null,
        pedido_produccion_id: pedidoProduccionId,
        recibo_prenda_id: reciboPrendaId,
        last_updated_at: lastUpdatedAt,
    };

    // Mostrar spinner de carga
    const textoOriginal = btnGuardar.textContent;
    btnGuardar.textContent = '⏳ Guardando...';
    btnGuardar.disabled = true;

    fetch('/gestion-bodega/detalles-talla/guardar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify(datosAGuardar)
    })
    .then(response => response.json())
    .then(data => {
        btnGuardar.textContent = textoOriginal;
        btnGuardar.disabled = false;

        if (data.success) {
            btnGuardar.textContent = ' Guardado';
            setTimeout(() => {
                btnGuardar.textContent = textoOriginal;
            }, 2000);
            
            if (window.mostrarModalExito) {
                mostrarModalExito('✓ Cambios guardados exitosamente');
            }
            
            if (data.data?.updated_at) {
                if (observacionesInput) observacionesInput.dataset.updatedAt = data.data.updated_at;
                if (fechaPedidoInput) fechaPedidoInput.dataset.updatedAt = data.data.updated_at;
                if (fechaEntregaInput) fechaEntregaInput.dataset.updatedAt = data.data.updated_at;
            }

            if (data.data && data.data.estado_bodega && estadoSelect) {
                estadoSelect.value = data.data.estado_bodega;
                estadoSelect.setAttribute('data-original-estado', data.data.estado_bodega);
            }
        } else if (data.conflict) {
            alert('Conflicto de edición: Otro usuario modificó este registro.\n\nPor favor, recarga la página para los cambios más recientes.');
            location.reload();
        } else {
            btnGuardar.textContent = ' Error';
            setTimeout(() => {
                btnGuardar.textContent = textoOriginal;
            }, 2000);
            alert('Error: ' + (data.message || 'No se pudieron guardar los cambios'));
        }
    })
    .catch(error => {
        btnGuardar.textContent = textoOriginal;
        btnGuardar.disabled = false;
        console.error('Error:', error);
        alert('Error al guardar: ' + error.message);
    });
}







