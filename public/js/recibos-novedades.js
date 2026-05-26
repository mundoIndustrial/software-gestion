/**
 * RECIBOS - Sistema de Novedades
 * Basado en el sistema de notas de despacho
 * Cada usuario agrega sus propias novedades sin editar las de otros
 */

// Variables globales
window.usuarioActualId = window.usuarioActualId || null;
window.__novedadesContext = {};

/**
 * Obtener CSRF token
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function esErrorCsrf(status, message = '') {
    const msg = String(message || '').toLowerCase();
    return Number(status) === 419 || msg.includes('csrf token mismatch');
}

function mostrarAlertaSesionExpirada() {
    const modalExistente = document.getElementById('modalSesionExpiradaNovedades');
    if (modalExistente) {
        modalExistente.remove();
    }

    const html = `
        <div id="modalSesionExpiradaNovedades" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:100007;display:flex;align-items:center;justify-content:center;padding:1rem;">
            <div style="background:#fff;border-radius:12px;box-shadow:0 20px 40px rgba(0,0,0,.25);width:min(100%,460px);overflow:hidden;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid #fecaca;background:#dc2626;display:flex;align-items:center;gap:.55rem;">
                    <span class="material-symbols-rounded" style="color:#fff;font-size:20px;line-height:1;">warning</span>
                    <h3 style="margin:0;color:#fff;font-size:1rem;font-weight:700;">Sesion expirada</h3>
                </div>
                <div style="padding:1rem 1.25rem;">
                    <p style="margin:0;color:#374151;font-size:.95rem;">Tu sesion expiro por inactividad. Recarga la pagina para continuar.</p>
                </div>
                <div style="background:#f9fafb;padding:1rem 1.25rem;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:.55rem;">
                    <button type="button" onclick="document.getElementById('modalSesionExpiradaNovedades')?.remove()" style="border:0;border-radius:10px;background:#94a3b8;color:#fff;font-weight:600;padding:.6rem .95rem;cursor:pointer;">Cerrar</button>
                    <button type="button" onclick="window.location.reload()" style="border:0;border-radius:10px;background:#16a34a;color:#fff;font-weight:600;padding:.6rem .95rem;cursor:pointer;">Recargar pagina</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', html);
}

/**
 * Forzar estilo de overlay/modal para vistas sin utilidades CSS
 */
function forzarEstiloModalDinamico(modalId, zIndex = '100004') {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.right = '0';
    modal.style.bottom = '0';
    modal.style.left = '0';
    modal.style.background = 'rgba(0, 0, 0, 0.75)';
    modal.style.zIndex = zIndex;
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.padding = '1rem';
    modal.style.overflow = 'auto';

    const panel = modal.firstElementChild;
    if (panel) {
        panel.style.width = '100%';
        panel.style.maxWidth = '32rem';
        panel.style.margin = '0 auto';
    }
}

/**
 * Mostrar alerta
 */
function mostrarAlerta(titulo, mensaje, tipo = 'info') {
    const modal = document.getElementById('modalAlerta');
    if (!modal) {
        alert(`${titulo}: ${mensaje}`);
        return;
    }
    
    const header = document.getElementById('alertaHeader');
    const tituloEl = document.getElementById('alertaTitulo');
    const icono = document.getElementById('alertaIcono');
    const mensajeEl = document.getElementById('alertaMensaje');
    
    const configuraciones = {
        success: {
            bgColor: 'bg-green-600',
            borderColor: 'border-green-200',
            icono: 'check_circle'
        },
        error: {
            bgColor: 'bg-red-600',
            borderColor: 'border-red-200',
            icono: 'error'
        },
        warning: {
            bgColor: 'bg-orange-600',
            borderColor: 'border-orange-200',
            icono: 'warning'
        },
        info: {
            bgColor: 'bg-blue-600',
            borderColor: 'border-blue-200',
            icono: 'info'
        }
    };
    
    const config = configuraciones[tipo] || configuraciones.info;
    
    if (header) {
        header.className = `${config.bgColor} px-6 py-4 border-b ${config.borderColor}`;
    }
    if (tituloEl) {
        tituloEl.textContent = titulo;
    }
    if (icono) {
        icono.textContent = config.icono;
    }
    if (mensajeEl) {
        mensajeEl.textContent = mensaje;
    }

    // Forzar comportamiento de overlay aunque no existan clases utilitarias CSS
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.right = '0';
    modal.style.bottom = '0';
    modal.style.left = '0';
    modal.style.background = 'rgba(0, 0, 0, 0.5)';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.zIndex = '100003';
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.style.display = 'flex';
}

/**
 * Cerrar modal de alerta
 */
function cerrarModalAlerta() {
    const modal = document.getElementById('modalAlerta');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.style.display = 'none';
    }
}

/**
 * Mostrar modal de éxito (sin depender de componentes de la vista)
 */
function mostrarModalExito(mensaje, titulo = 'Éxito') {
    const modalExistente = document.getElementById('modalExitoNovedades');
    if (modalExistente) {
        modalExistente.remove();
    }

    const modalHTML = `
        <div id="modalExitoNovedades" style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:100006;display:flex;align-items:center;justify-content:center;padding:1rem;">
            <div style="background:#fff;border-radius:12px;box-shadow:0 20px 40px rgba(0,0,0,.25);width:min(100%,460px);overflow:hidden;">
                <div style="padding:1rem 1.25rem;border-bottom:1px solid #bbf7d0;background:#16a34a;display:flex;align-items:center;gap:.55rem;">
                    <span class="material-symbols-rounded" style="color:#fff;font-size:20px;line-height:1;">check_circle</span>
                    <h3 style="margin:0;color:#fff;font-size:1rem;font-weight:700;">${titulo}</h3>
                </div>
                <div style="padding:1rem 1.25rem;">
                    <p style="margin:0;color:#374151;font-size:.95rem;">${mensaje}</p>
                </div>
                <div style="background:#f9fafb;padding:1rem 1.25rem;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;">
                    <button type="button" onclick="cerrarModalExitoNovedades()" style="border:0;border-radius:10px;background:#16a34a;color:#fff;font-weight:600;padding:.6rem .95rem;cursor:pointer;">Entendido</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    const modal = document.getElementById('modalExitoNovedades');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                cerrarModalExitoNovedades();
            }
        });
    }
}

function cerrarModalExitoNovedades() {
    const modal = document.getElementById('modalExitoNovedades');
    if (modal) {
        modal.remove();
    }
}

/**
 * Abrir modal de novedades de recibo (Estilo Despacho)
 */
function abrirModalNovedadesRecibo(pedidoId, numeroRecibo) {
    console.log('[abrirModalNovedadesRecibo]  Iniciando apertura de modal');
    console.log('[abrirModalNovedadesRecibo]  Pedido ID:', pedidoId, 'Número Recibo:', numeroRecibo);
    
    try {
        window.__novedadesContext = {
            pedido_id: pedidoId,
            numero_recibo: numeroRecibo
        };

        const modal = document.getElementById('novedadesEditModal');
        console.log('[abrirModalNovedadesRecibo]  Modal encontrado:', !!modal);
        
        if (!modal) {
            console.error('[abrirModalNovedadesRecibo]  No existe #novedadesEditModal');
            return;
        }

        // Configurar título
        const titulo = modal.querySelector('.bg-slate-900 h2');
        if (titulo) {
            titulo.innerHTML = `💬 Novedades - Pedido <span id="modalNovedadesNumeroPedido">#${pedidoId}</span> - Recibo ${numeroRecibo}`;
            console.log('[abrirModalNovedadesRecibo]  Título configurado');
        }

        // Limpiar textarea de nueva novedad
        const nuevaContent = document.getElementById('novedadesNuevaContent');
        if (nuevaContent) {
            nuevaContent.value = '';
            console.log('[abrirModalNovedadesRecibo]  Textarea limpiado');
        }

        // Mostrar el modal
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.style.display = 'flex';
        console.log('[abrirModalNovedadesRecibo]  Modal mostrado');

        // Cargar novedades existentes
        console.log('[abrirModalNovedadesRecibo] 📥 Cargando novedades...');
        cargarNovedadesRecibo(pedidoId, numeroRecibo);

        // Enfocar el textarea
        setTimeout(() => {
            if (nuevaContent) {
                nuevaContent.focus();
                console.log('[abrirModalNovedadesRecibo]  Textarea enfocado');
            }
        }, 100);

    } catch (error) {
        console.error('[abrirModalNovedadesRecibo] 💥 Error:', error);
        mostrarAlerta('Error', 'No se pudo abrir el modal de novedades', 'error');
    }
}

/**
 * Cargar novedades del recibo
 */
async function cargarNovedadesRecibo(pedidoId, numeroRecibo) {
    console.log('[cargarNovedadesRecibo]  Iniciando carga de novedades');
    console.log('[cargarNovedadesRecibo]  Pedido ID:', pedidoId, 'Número Recibo:', numeroRecibo);
    
    try {
        const historial = document.getElementById('novedadesHistorial');
        console.log('[cargarNovedadesRecibo]  Contenedor encontrado:', !!historial);
        
        if (!historial) return;

        // Mostrar loading
        historial.innerHTML = '<div class="flex justify-center items-center py-8"><span class="text-slate-500"> Cargando novedades...</span></div>';
        console.log('[cargarNovedadesRecibo]  Loading mostrado');

        const response = await fetch(`/recibos-novedades/${pedidoId}/${numeroRecibo}`);
        console.log('[cargarNovedadesRecibo]  Response status:', response.status);
        
        const data = await response.json();
        console.log('[cargarNovedadesRecibo]Datos recibidos:', data);

        if (data.success && data.data && data.data.length > 0) {
            console.log('[cargarNovedadesRecibo]  Éxito - Novedades encontradas:', data.data.length);
            
            // Construir historial con formato de notas individuales
            const novedadesHTML = data.data.map(novedad => {
                const fecha = novedad.creado_en;
                const usuario = novedad.creado_por_nombre || 'Sistema';
                const rol = novedad.creado_por_rol;
                const usuarioConRol = rol ? `${usuario} - ${rol}` : usuario;
                const tipo = novedad.tipo_novedad.toUpperCase();
                
                // Información de edición
                const editado = novedad.editado;
                const editadoEn = novedad.editado_en;
                const editadoPor = novedad.editado_por_nombre;
                const editadoInfo = editado && editadoEn && editadoPor 
                    ? `Editado por ${editadoPor} el ${editadoEn}` 
                    : '';
                
                // Debug: verificar IDs
                console.log('[cargarNovedadesRecibo]  Procesando novedad:', {
                    id: novedad.id,
                    creado_por: novedad.creado_por,
                    creado_por_nombre: novedad.creado_por_nombre,
                    creado_por_rol: novedad.creado_por_rol,
                    creado_en: novedad.creado_en,
                    editado: novedad.editado,
                    editado_en: novedad.editado_en,
                    editado_por: novedad.editado_por_nombre,
                    usuarioActualId: window.usuarioActualId,
                    sonIguales: novedad.creado_por == window.usuarioActualId
                });
                
                return `
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-3 relative" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:12px 14px;margin-bottom:10px;position:relative;">
                        <div class="flex justify-between items-start mb-2" style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px;">
                            <div class="flex items-center gap-2" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded ${getTipoColor(novedad.tipo_novedad)}" style="${getTipoBadgeStyle(novedad.tipo_novedad)}display:inline-block;padding:3px 8px;font-size:11px;font-weight:700;border-radius:999px;">
                                    ${tipo}
                                </span>
                                <span class="text-xs text-gray-500" style="font-size:11px;color:#6b7280;">${usuarioConRol}</span>
                                ${editado ? '<span class="inline-block px-2 py-1 text-xs font-semibold rounded bg-orange-100 text-orange-800" style="display:inline-block;padding:3px 8px;font-size:11px;font-weight:700;border-radius:999px;background:#ffedd5;color:#9a3412;">EDITADO</span>' : ''}
                            </div>
                            <span class="text-xs text-gray-400" style="font-size:11px;color:#9ca3af;white-space:nowrap;">${fecha}</span>
                        </div>
                        <div class="text-sm text-gray-700 whitespace-pre-wrap" style="font-size:14px;color:#374151;white-space:pre-wrap;">${novedad.novedad_texto}</div>
                        ${editadoInfo ? `<div class="text-xs text-orange-600 italic mt-2" style="font-size:11px;color:#c2410c;font-style:italic;margin-top:6px;">${editadoInfo}</div>` : ''}
                        <div class="flex gap-2 mt-3" style="display:flex;gap:8px;margin-top:10px;">
                            ${novedad.creado_por == window.usuarioActualId ? `
                                <button 
                                    onclick="editarNovedad(${novedad.id}, '${novedad.novedad_texto.replace(/'/g, "\\'")}')"
                                    class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded transition"
                                    style="display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:0;border-radius:8px;background:#3b82f6;color:#fff;font-size:12px;font-weight:600;cursor:pointer;"
                                    title="Editar novedad">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">edit</span>
                                    Editar
                                </button>
                                <button 
                                    onclick="eliminarNovedad(${novedad.id})"
                                    class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-xs rounded transition"
                                    style="display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:0;border-radius:8px;background:#ef4444;color:#fff;font-size:12px;font-weight:600;cursor:pointer;"
                                    title="Eliminar novedad">
                                    <span class="material-symbols-rounded" style="font-size: 14px;">delete</span>
                                    Eliminar
                                </button>
                            ` : `
                                <span class="text-xs text-gray-400 italic" style="font-size:11px;color:#9ca3af;font-style:italic;">Solo lectura</span>
                            `}
                        </div>
                    </div>
                `;
            }).join('');
            
            historial.innerHTML = novedadesHTML || '<div class="text-center text-gray-500 py-8">Sin novedades registradas</div>';
            console.log('[cargarNovedadesRecibo] HTML renderizado');
        } else {
            console.log('[cargarNovedadesRecibo]  Sin novedades encontradas');
            historial.innerHTML = '<div class="text-center text-gray-500 py-8">Sin novedades registradas</div>';
        }

    } catch (error) {
        console.error('[cargarNovedadesRecibo] 💥 Error cargando novedades:', error);
        const historial = document.getElementById('novedadesHistorial');
        if (historial) {
            historial.innerHTML = '<div class="text-center text-red-500 py-8">Error al cargar novedades</div>';
        }
    }
}

/**
 * Obtener color para tipo de novedad
 */
function getTipoColor(tipo) {
    const colores = {
        'observacion': 'bg-blue-100 text-blue-800',
        'problema': 'bg-red-100 text-red-800',
        'cambio': 'bg-yellow-100 text-yellow-800',
        'aprobacion': 'bg-green-100 text-green-800',
        'rechazo': 'bg-red-100 text-red-800',
        'correccion': 'bg-orange-100 text-orange-800'
    };
    return colores[tipo] || 'bg-gray-100 text-gray-800';
}

function getTipoBadgeStyle(tipo) {
    const estilos = {
        'observacion': 'background:#dbeafe;color:#1e40af;',
        'problema': 'background:#fee2e2;color:#991b1b;',
        'cambio': 'background:#fef9c3;color:#854d0e;',
        'aprobacion': 'background:#dcfce7;color:#166534;',
        'rechazo': 'background:#fee2e2;color:#991b1b;',
        'correccion': 'background:#ffedd5;color:#9a3412;'
    };
    return estilos[tipo] || 'background:#f3f4f6;color:#374151;';
}

/**
 * Guardar nueva novedad
 */
async function guardarNovedad() {
    console.log('[guardarNovedad]  Iniciando guardado de nueva novedad');
    
    try {
        const ctx = window.__novedadesContext || {};
        const pedidoId = ctx.pedido_id;
        const numeroRecibo = ctx.numero_recibo;
        const nuevaContent = document.getElementById('novedadesNuevaContent');
        
        console.log('[guardarNovedad]  Contexto:', ctx);
        console.log('[guardarNovedad]  Textarea encontrado:', !!nuevaContent);
        
        if (!pedidoId || !numeroRecibo || !nuevaContent) {
            console.error('[guardarNovedad]  Faltan datos para guardar la novedad');
            mostrarAlerta('Error', 'Faltan datos para guardar la novedad', 'error');
            return;
        }

        const nuevaNovedadTexto = nuevaContent.value.trim();
        console.log('[guardarNovedad]  Texto a guardar:', nuevaNovedadTexto);
        
        if (!nuevaNovedadTexto) {
            console.warn('[guardarNovedad]  Texto vacío');
            mostrarAlerta('Error', 'La novedad no puede estar vacía', 'warning');
            return;
        }

        // Mostrar loading
        const btnGuardar = document.querySelector('button[onclick="guardarNovedad()"]');
        if (btnGuardar) {
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = 'Guardando...';
            console.log('[guardarNovedad]  Botón en estado loading');
        }

        console.log('[guardarNovedad]  Enviando request a API...');
        const response = await fetch(`/recibos-novedades/${pedidoId}/${numeroRecibo}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                novedades: nuevaNovedadTexto,
                tipo_novedad: 'observacion',
                prendas_ids: [] // Aplica a todas las prendas
            })
        });

        const result = await response.json().catch(() => null);
        console.log('[guardarNovedad]Respuesta API:', result);

        if (esErrorCsrf(response.status, result?.message)) {
            mostrarAlertaSesionExpirada();
            return;
        }

        if (result.success) {
            console.log('[guardarNovedad]  Éxito al guardar');
            mostrarModalExito('Novedad agregada correctamente', 'Éxito');
            
            // Limpiar textarea
            nuevaContent.value = '';
            console.log('[guardarNovedad]  Textarea limpiado');
            
            // Recargar novedades en tiempo real
            console.log('[guardarNovedad] 📥 Recargando novedades...');
            await cargarNovedadesRecibo(pedidoId, numeroRecibo);
            
            // Actualizar el botón en la tabla principal (si existe)
            console.log('[guardarNovedad]  Actualizando botón principal...');
            actualizarBotonNovedadesEnTabla(pedidoId, numeroRecibo);
        } else {
            console.error('[guardarNovedad]  Error en respuesta:', result.message);
            mostrarAlerta(' Error', result.message || 'No se pudo guardar la novedad', 'error');
        }

    } catch (error) {
        console.error('[guardarNovedad] 💥 Error:', error);
        if (esErrorCsrf(error?.status, error?.message)) {
            mostrarAlertaSesionExpirada();
            return;
        }
        mostrarAlerta(' Error', 'Error de conexión al guardar la novedad', 'error');
    } finally {
        // Restaurar botón
        const btnGuardar = document.querySelector('button[onclick="guardarNovedad()"]');
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '✓ Guardar Novedad';
            console.log('[guardarNovedad]  Botón restaurado');
        }
    }
}

/**
 * Eliminar novedad
 */
async function eliminarNovedad(novedadId) {
    console.log('[eliminarNovedad]  Iniciando eliminación de novedad ID:', novedadId);
    
    try {
        // Limpiar cualquier modal existente primero
        const modalExistente = document.getElementById('modalConfirmarEliminar');
        if (modalExistente) {
            console.log('[eliminarNovedad]  Limpiando modal existente');
            modalExistente.remove();
        }
        
        // Crear modal de confirmación
        const modalHTML = `
            <div id="modalConfirmarEliminar" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999" style="z-index: 100004;">
                <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4" style="background:#fff;border-radius:12px;box-shadow:0 20px 40px rgba(0,0,0,.25);width:min(100%,560px);margin:1rem;overflow:hidden;">
                    <div class="bg-red-600 px-6 py-4 border-b border-red-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2" style="margin:0;color:#fff;font-size:1rem;font-weight:700;display:flex;align-items:center;gap:.5rem;">
                            <span class="material-symbols-rounded">warning</span>
                            Confirmar Eliminación
                        </h3>
                        <button onclick="cerrarModalConfirmarEliminar()" class="text-white hover:text-red-200 text-2xl leading-none">✕</button>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-gray-700 mb-4">¿Estás seguro de que deseas eliminar esta novedad?</p>
                        <p class="text-sm text-gray-500 italic">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex gap-3 justify-end">
                        <button type="button" onclick="cerrarModalConfirmarEliminar()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium rounded-lg transition">
                            Cancelar
                        </button>
                        <button type="button" id="btnConfirmarEliminar" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition">
                            Eliminar Novedad
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        console.log('[eliminarNovedad]  Modal HTML creado');
        
        // Agregar modal al body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        forzarEstiloModalDinamico('modalConfirmarEliminar', '100004');

        const modalEliminar = document.getElementById('modalConfirmarEliminar');
        if (modalEliminar) {
            const panel = modalEliminar.firstElementChild;
            if (panel) {
                panel.style.background = '#fff';
                panel.style.borderRadius = '12px';
                panel.style.boxShadow = '0 20px 40px rgba(0,0,0,.25)';
                panel.style.width = 'min(100%, 560px)';
                panel.style.margin = '1rem';
                panel.style.overflow = 'hidden';
            }

            const header = modalEliminar.querySelector('.bg-red-600');
            if (header) {
                header.style.padding = '1rem 1.25rem';
                header.style.borderBottom = '1px solid #fecaca';
                header.style.background = '#dc2626';
                header.style.display = 'flex';
                header.style.justifyContent = 'space-between';
                header.style.alignItems = 'center';
                header.style.gap = '.75rem';
            }

            const titulo = modalEliminar.querySelector('h3');
            if (titulo) {
                titulo.style.margin = '0';
                titulo.style.color = '#fff';
                titulo.style.fontSize = '1rem';
                titulo.style.fontWeight = '700';
                titulo.style.display = 'flex';
                titulo.style.alignItems = 'center';
                titulo.style.gap = '.5rem';
            }

            const cerrarBtn = modalEliminar.querySelector('button[onclick="cerrarModalConfirmarEliminar()"]');
            if (cerrarBtn) {
                cerrarBtn.innerHTML = '&times;';
                cerrarBtn.style.border = '0';
                cerrarBtn.style.background = 'transparent';
                cerrarBtn.style.color = '#fff';
                cerrarBtn.style.fontSize = '1.6rem';
                cerrarBtn.style.lineHeight = '1';
                cerrarBtn.style.cursor = 'pointer';
                cerrarBtn.style.padding = '0 .25rem';
            }

            const cuerpo = modalEliminar.querySelector('.px-6.py-4');
            if (cuerpo) {
                cuerpo.style.padding = '1rem 1.25rem';
            }

            const parrafos = cuerpo ? cuerpo.querySelectorAll('p') : [];
            if (parrafos.length > 0) {
                parrafos[0].style.margin = '0 0 .55rem 0';
                parrafos[0].style.color = '#374151';
                parrafos[0].style.fontSize = '.95rem';
            }
            if (parrafos.length > 1) {
                parrafos[1].style.margin = '0';
                parrafos[1].style.color = '#6b7280';
                parrafos[1].style.fontSize = '.82rem';
                parrafos[1].style.fontStyle = 'italic';
            }

            const footer = modalEliminar.querySelector('.bg-gray-50');
            if (footer) {
                footer.style.background = '#f9fafb';
                footer.style.padding = '1rem 1.25rem';
                footer.style.borderTop = '1px solid #e5e7eb';
                footer.style.display = 'flex';
                footer.style.gap = '.6rem';
                footer.style.justifyContent = 'flex-end';
            }

            const botones = footer ? footer.querySelectorAll('button') : [];
            if (botones.length > 0) {
                botones[0].style.border = '0';
                botones[0].style.borderRadius = '10px';
                botones[0].style.background = '#e5e7eb';
                botones[0].style.color = '#1f2937';
                botones[0].style.fontWeight = '600';
                botones[0].style.padding = '.6rem .9rem';
                botones[0].style.cursor = 'pointer';
            }
            if (botones.length > 1) {
                botones[1].style.border = '0';
                botones[1].style.borderRadius = '10px';
                botones[1].style.background = '#dc2626';
                botones[1].style.color = '#fff';
                botones[1].style.fontWeight = '600';
                botones[1].style.padding = '.6rem .9rem';
                botones[1].style.cursor = 'pointer';
            }
        }
        console.log('[eliminarNovedad]  Modal agregado al DOM');
        
        // Obtener botones inmediatamente (sin timeout)
        const btnConfirmar = document.getElementById('btnConfirmarEliminar');
        const btnCancelar = document.querySelector('#modalConfirmarEliminar button[onclick="cerrarModalConfirmarEliminar()"]');
        const btnCerrar = document.querySelector('#modalConfirmarEliminar .bg-red-600 button');
        
        console.log('[eliminarNovedad]  Botones encontrados:');
        console.log('[eliminarNovedad]   - btnConfirmar:', !!btnConfirmar);
        console.log('[eliminarNovedad]   - btnCancelar:', !!btnCancelar);
        console.log('[eliminarNovedad]   - btnCerrar:', !!btnCerrar);
        
        if (btnConfirmar) {
            console.log('[eliminarNovedad]  Adjuntando evento onclick a btnConfirmar');
            
            // Adjuntar evento directamente sin timeout
            btnConfirmar.onclick = async function(event) {
                console.log('[eliminarNovedad]  Botón confirmar clickeado!', event);
                console.log('[eliminarNovedad]  Event target:', event.target);
                
                // Prevenir comportamiento por defecto
                event.preventDefault();
                event.stopPropagation();
                
                // Mostrar loading
                this.disabled = true;
                this.innerHTML = 'Eliminando...';
                console.log('[eliminarNovedad]  Botón en estado loading');
                
                try {
                    console.log('[eliminarNovedad]  Enviando DELETE request...');
                    const response = await fetch(`/recibos-novedades/${novedadId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': getCsrfToken()
                        }
                    });

                    const result = await response.json().catch(() => null);
                    console.log('[eliminarNovedad]Respuesta DELETE:', result);

                    if (esErrorCsrf(response.status, result?.message)) {
                        cerrarModalConfirmarEliminar();
                        mostrarAlertaSesionExpirada();
                        return;
                    }

                    if (result.success) {
                        console.log('[eliminarNovedad]  Eliminación exitosa');
                        mostrarModalExito('Novedad eliminada correctamente', 'Éxito');
                        cerrarModalConfirmarEliminar();
                        
                        // Recargar novedades en tiempo real
                        const ctx = window.__novedadesContext || {};
                        console.log('[eliminarNovedad] 📥 Recargando novedades...');
                        await cargarNovedadesRecibo(ctx.pedido_id, ctx.numero_recibo);
                        
                        // Actualizar el botón en la tabla principal (si existe)
                        console.log('[eliminarNovedad]  Actualizando botón principal...');
                        actualizarBotonNovedadesEnTabla(ctx.pedido_id, ctx.numero_recibo);
                    } else {
                        console.error('[eliminarNovedad]  Error en respuesta:', result.message);
                        mostrarAlerta(' Error', result.message || 'No se pudo eliminar la novedad', 'error');
                    }
                } catch (error) {
                    console.error('[eliminarNovedad] 💥 Error en DELETE:', error);
                    if (esErrorCsrf(error?.status, error?.message)) {
                        cerrarModalConfirmarEliminar();
                        mostrarAlertaSesionExpirada();
                        return;
                    }
                    mostrarAlerta(' Error', 'Error de conexión al eliminar la novedad', 'error');
                } finally {
                    // Restaurar botón (por si el modal no se cierra inmediatamente)
                    this.disabled = false;
                    this.innerHTML = 'Eliminar Novedad';
                    console.log('[eliminarNovedad]  Botón restaurado');
                }
            };
            
            console.log('[eliminarNovedad]  Evento onclick adjuntado inmediatamente');
            
        } else {
            console.error('[eliminarNovedad]  No se encontró el botón de confirmación');
        }
        
    } catch (error) {
        console.error('[eliminarNovedad] 💥 Error al crear modal:', error);
        mostrarAlerta(' Error', 'Error al abrir el modal de confirmación', 'error');
    }
}

/**
 * Cerrar modal de novedades
 */
function cerrarModalNovedades() {
    const modal = document.getElementById('novedadesEditModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.style.display = 'none';
    }
    
    // Limpiar contexto
    window.__novedadesContext = {};
}

/**
 * Editar novedad existente
 */
function editarNovedad(novedadId, textoActual) {
    try {
        // Verificación de seguridad adicional en frontend
        const ctx = window.__novedadesContext || {};
        console.log('[editarNovedad] Verificando permisos para novedad:', novedadId);
        
        // Esta función solo debería llamarse si el usuario es el autor, pero agregamos doble verificación
        if (!window.usuarioActualId) {
            mostrarAlerta('Error', 'No se pudo identificar al usuario actual', 'error');
            return;
        }
        
        // Crear modal de edición
        const modalHTML = `
            <div id="modalEditarNovedad" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-9999" style="z-index: 100003;">
                <div class="bg-white rounded-lg shadow-2xl max-w-md w-full mx-4">
                    <div class="bg-blue-600 px-6 py-4 border-b border-blue-200 flex justify-between items-center" style="padding:1rem 1.25rem;border-bottom:1px solid #bfdbfe;background:#2563eb;display:flex;justify-content:space-between;align-items:center;gap:.75rem;">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <span class="material-symbols-rounded" style="font-size:18px;line-height:1;">edit</span>
                            Editar Novedad
                        </h3>
                        <button onclick="cerrarModalEditarNovedad()" class="text-white hover:text-blue-200 text-2xl leading-none">✕</button>
                    </div>
                    <div class="px-6 py-4">
                        <label class="block text-sm font-bold text-slate-900 mb-3">Editar Novedad:</label>
                        <textarea
                            id="editarNovedadTextarea"
                            class="w-full px-4 py-3 border border-slate-300 rounded-lg text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-700 outline-none transition resize-none"
                            placeholder="Edita tu novedad aquí..."
                            rows="4"
                            maxlength="500"
                        >${textoActual}</textarea>
                        <div class="text-xs text-gray-500 mt-1">
                            <span id="editarCharCount">${textoActual.length}</span>/500 caracteres
                        </div>
                    </div>
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex gap-3 justify-end">
                        <button type="button" onclick="cerrarModalEditarNovedad()" class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium rounded-lg transition">
                            Cancelar
                        </button>
                        <button type="button" onclick="guardarEdicionNovedad(${novedadId})" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // Agregar modal al body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        forzarEstiloModalDinamico('modalEditarNovedad', '100005');

        const modalEditar = document.getElementById('modalEditarNovedad');
        if (modalEditar) {
            const panel = modalEditar.firstElementChild;
            if (panel) {
                panel.style.background = '#fff';
                panel.style.borderRadius = '12px';
                panel.style.boxShadow = '0 20px 40px rgba(0,0,0,.25)';
                panel.style.width = 'min(100%, 560px)';
                panel.style.margin = '1rem';
                panel.style.overflow = 'hidden';
            }

            const header = modalEditar.querySelector('.bg-blue-600');
            if (header) {
                header.style.padding = '1rem 1.25rem';
                header.style.borderBottom = '1px solid #bfdbfe';
                header.style.background = '#2563eb';
                header.style.display = 'flex';
                header.style.justifyContent = 'space-between';
                header.style.alignItems = 'center';
                header.style.gap = '.75rem';
            }

            const titulo = modalEditar.querySelector('h3');
            if (titulo) {
                titulo.style.margin = '0';
                titulo.style.color = '#fff';
                titulo.style.fontSize = '1rem';
                titulo.style.fontWeight = '700';
                titulo.style.display = 'flex';
                titulo.style.alignItems = 'center';
                titulo.style.gap = '.5rem';
            }

            const cerrarBtn = modalEditar.querySelector('button[onclick="cerrarModalEditarNovedad()"]');
            if (cerrarBtn) {
                cerrarBtn.innerHTML = '&times;';
                cerrarBtn.style.border = '0';
                cerrarBtn.style.background = 'transparent';
                cerrarBtn.style.color = '#fff';
                cerrarBtn.style.fontSize = '1.6rem';
                cerrarBtn.style.lineHeight = '1';
                cerrarBtn.style.cursor = 'pointer';
                cerrarBtn.style.padding = '0 .25rem';
            }

            const cuerpo = modalEditar.querySelector('.px-6.py-4');
            if (cuerpo) {
                cuerpo.style.padding = '1rem 1.25rem';
            }

            const label = modalEditar.querySelector('label[for], label');
            if (label) {
                label.style.display = 'block';
                label.style.color = '#0f172a';
                label.style.fontSize = '.9rem';
                label.style.fontWeight = '700';
                label.style.margin = '0 0 .6rem 0';
            }

            const contador = modalEditar.querySelector('#editarCharCount')?.parentElement;
            if (contador) {
                contador.style.marginTop = '.5rem';
                contador.style.fontSize = '.78rem';
                contador.style.color = '#6b7280';
            }

            const footer = modalEditar.querySelector('.bg-gray-50');
            if (footer) {
                footer.style.background = '#f9fafb';
                footer.style.padding = '1rem 1.25rem';
                footer.style.borderTop = '1px solid #e5e7eb';
                footer.style.display = 'flex';
                footer.style.gap = '.6rem';
                footer.style.justifyContent = 'flex-end';
            }

            const botones = footer ? footer.querySelectorAll('button') : [];
            if (botones.length > 0) {
                botones[0].style.border = '0';
                botones[0].style.borderRadius = '10px';
                botones[0].style.background = '#e5e7eb';
                botones[0].style.color = '#1f2937';
                botones[0].style.fontWeight = '600';
                botones[0].style.padding = '.6rem .9rem';
                botones[0].style.cursor = 'pointer';
            }
            if (botones.length > 1) {
                botones[1].style.border = '0';
                botones[1].style.borderRadius = '10px';
                botones[1].style.background = '#2563eb';
                botones[1].style.color = '#fff';
                botones[1].style.fontWeight = '600';
                botones[1].style.padding = '.6rem .9rem';
                botones[1].style.cursor = 'pointer';
            }
        }
        
        // Configurar contador de caracteres
        const textarea = document.getElementById('editarNovedadTextarea');
        const charCount = document.getElementById('editarCharCount');

        if (textarea) {
            textarea.style.width = '100%';
            textarea.style.minHeight = '110px';
            textarea.style.maxHeight = '240px';
            textarea.style.padding = '.75rem .9rem';
            textarea.style.border = '1px solid #cbd5e1';
            textarea.style.borderRadius = '10px';
            textarea.style.fontSize = '.92rem';
            textarea.style.color = '#1f2937';
            textarea.style.outline = 'none';
            textarea.style.resize = 'vertical';
            textarea.style.boxSizing = 'border-box';
        }
        
        if (textarea && charCount) {
            textarea.addEventListener('input', function() {
                charCount.textContent = this.value.length;
            });
        }
        
        // Enfocar textarea
        setTimeout(() => {
            if (!textarea) return;
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        }, 100);
        
    } catch (error) {
        console.error('[editarNovedad] Error:', error);
        mostrarAlerta('Error', 'No se pudo abrir el editor de novedades', 'error');
    }
}

/**
 * Guardar edición de novedad
 */
async function guardarEdicionNovedad(novedadId) {
    try {
        const textarea = document.getElementById('editarNovedadTextarea');
        if (!textarea) return;
        
        const nuevoTexto = textarea.value.trim();
        if (!nuevoTexto) {
            mostrarAlerta('Error', 'La novedad no puede estar vacía', 'warning');
            return;
        }
        
        // Mostrar loading
        const btnGuardar = document.querySelector('button[onclick="guardarEdicionNovedad(' + novedadId + ')"]');
        if (btnGuardar) {
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = 'Guardando...';
        }
        
        const response = await fetch(`/recibos-novedades/${novedadId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({
                novedad_texto: nuevoTexto
            })
        });
        
        const result = await response.json().catch(() => null);

        if (esErrorCsrf(response.status, result?.message)) {
            cerrarModalEditarNovedad();
            mostrarAlertaSesionExpirada();
            return;
        }
        
        if (result.success) {
            mostrarModalExito('Novedad actualizada correctamente', 'Éxito');
            cerrarModalEditarNovedad();
            
            // Recargar novedades en tiempo real
            const ctx = window.__novedadesContext || {};
            await cargarNovedadesRecibo(ctx.pedido_id, ctx.numero_recibo);
            
            // Actualizar el botón en la tabla principal (si existe)
            actualizarBotonNovedadesEnTabla(ctx.pedido_id, ctx.numero_recibo);
        } else {
            mostrarAlerta(' Error', result.message || 'No se pudo actualizar la novedad', 'error');
        }
        
    } catch (error) {
        console.error('[guardarEdicionNovedad] Error:', error);
        if (esErrorCsrf(error?.status, error?.message)) {
            cerrarModalEditarNovedad();
            mostrarAlertaSesionExpirada();
            return;
        }
        mostrarAlerta(' Error', 'Error de conexión al actualizar la novedad', 'error');
    } finally {
        // Restaurar botón
        const btnGuardar = document.querySelector('button[onclick="guardarEdicionNovedad(' + novedadId + ')"]');
        if (btnGuardar) {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = 'Guardar Cambios';
        }
    }
}

/**
 * Actualizar el botón de novedades en la tabla principal
 */
async function actualizarBotonNovedadesEnTabla(pedidoId, numeroRecibo) {
    try {
        // Buscar el botón de novedades en la tabla principal (específicamente el botón, no la fila)
        const botonNovedades = document.querySelector(`button.btn-edit-novedades[data-pedido-id="${pedidoId}"][data-numero-recibo="${numeroRecibo}"]`);
        
        if (!botonNovedades) {
            console.log('[actualizarBotonNovedadesEnTabla] No se encontró el botón en la tabla principal');
            return;
        }
        
        // Obtener las novedades actualizadas
        const response = await fetch(`/recibos-novedades/${pedidoId}/${numeroRecibo}`);
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            // Construir el texto de novedades actualizado
            const novedadesTexto = data.data.map(novedad => {
                const usuario = novedad.creado_por_nombre || 'Sistema';
                const fecha = novedad.creado_en;
                const tipo = novedad.tipo_novedad.toUpperCase();
                return `${usuario}-${tipo}-${fecha}\n${novedad.novedad_texto}`;
            }).join('\n\n');
            
            // Actualizar el data-novedades del botón
            botonNovedades.setAttribute('data-novedades', novedadesTexto);
            
            // Actualizar el span que muestra el texto de novedades (si existe)
            const spanNovedades = botonNovedades.querySelector('span');
            if (spanNovedades) {
                // Mostrar solo las primeras 2-3 novedades más recientes
                const novedadesLimitadas = data.data.slice(0, 3).map(novedad => {
                    const usuario = novedad.creado_por_nombre || 'Sistema';
                    const textoCorto = novedad.novedad_texto.length > 30 
                        ? novedad.novedad_texto.substring(0, 30) + '...' 
                        : novedad.novedad_texto;
                    return `${usuario}: ${textoCorto}`;
                }).join(' | ');
                
                spanNovedades.textContent = novedadesLimitadas;
            }
            
            // Agregar una pequeña animación para indicar que se actualizó
            botonNovedades.classList.add('bg-green-100', 'border-green-500');
            setTimeout(() => {
                botonNovedades.classList.remove('bg-green-100', 'border-green-500');
            }, 1000);
            
            console.log('[actualizarBotonNovedadesEnTabla] Botón actualizado correctamente');
        } else {
            // Si no hay novedades, limpiar el botón
            botonNovedades.setAttribute('data-novedades', '');
            const spanNovedades = botonNovedades.querySelector('span');
            if (spanNovedades) {
                spanNovedades.textContent = '';
            }
        }
        
    } catch (error) {
        console.error('[actualizarBotonNovedadesEnTabla] Error:', error);
    }
}

/**
 * Cerrar modal de confirmación
 */
function cerrarModalConfirmarEliminar() {
    console.log('[cerrarModalConfirmarEliminar]  Iniciando cierre de modal');
    
    const modal = document.getElementById('modalConfirmarEliminar');
    console.log('[cerrarModalConfirmarEliminar]  Modal encontrado:', !!modal);
    
    if (modal) {
        console.log('[cerrarModalConfirmarEliminar]  Eliminando modal del DOM');
        modal.remove();
        console.log('[cerrarModalConfirmarEliminar]  Modal eliminado');
    } else {
        console.warn('[cerrarModalConfirmarEliminar]  No se encontró el modal para cerrar');
    }
}

/**
 * Cerrar modal de edición
 */
function cerrarModalEditarNovedad() {
    const modal = document.getElementById('modalEditarNovedad');
    if (modal) {
        modal.remove();
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Obtener ID del usuario actual desde meta tag
    const usuarioMeta = document.querySelector('meta[name="user-id"]');
    if (usuarioMeta) {
        window.usuarioActualId = usuarioMeta.getAttribute('content');
        console.log('[Debug] ID de usuario actual desde meta:', window.usuarioActualId);
    } else {
        console.warn('[Debug] No se encontró meta tag user-id');
        // Fallback: intentar obtener de otra manera
        const userIdElement = document.getElementById('current-user-id');
        if (userIdElement) {
            window.usuarioActualId = userIdElement.value;
            console.log('[Debug] ID de usuario actual desde elemento:', window.usuarioActualId);
        }
    }
    
    // Auto-ajustar altura de textarea
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
});

// Atajos de teclado (globales)
document.addEventListener('keydown', function(e) {
    // Ctrl+Enter para guardar nueva novedad
    if (e.ctrlKey && e.key === 'Enter') {
        const nuevaTextarea = document.getElementById('nuevaNovedadTextarea');
        if (document.activeElement === nuevaTextarea) {
            e.preventDefault();
            guardarNuevaNovedad();
        }
    }
    
    // ESC para cerrar modal
    if (e.key === 'Escape') {
        const modal = document.getElementById('novedadesEditModal');
        if (modal && modal.style.display === 'flex') {
            cerrarModalNovedades();
        }
    }
});
