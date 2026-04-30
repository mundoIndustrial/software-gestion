<!-- Modal de lista de prendas con opción de agregar -->
<script>
    window.__cacheBloqueoPrendasLista = window.__cacheBloqueoPrendasLista || new Map();
    function _mostrarModalPrendaBloqueadaDesdeLista(mensaje) {
        const texto = mensaje || 'Esta prenda no se puede editar en este momento.';
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Edicion bloqueada',
                text: texto,
                confirmButtonText: 'Entendido'
            });
            return;
        }
        alert(texto);
    }

    async function _validarBloqueoEdicionPrendaDesdeLista(item, pedidoId) {
        const prendaId = item?.id || item?.prenda_pedido_id;
        if (!pedidoId || !prendaId) {
            return { bloqueada: false, mensaje: '' };
        }

        const cacheKey = `${pedidoId}:${prendaId}`;
        if (window.__cacheBloqueoPrendasLista.has(cacheKey)) {
            return window.__cacheBloqueoPrendasLista.get(cacheKey);
        }

        try {
            const fetchUrl = `/api/asesores/pedidos-produccion/${pedidoId}/prenda/${prendaId}/datos`;
            const response = await fetch(fetchUrl, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            let payload = null;
            try { payload = await response.json(); } catch (_) { payload = null; }

            if (response.status === 409) {
                const resultado = {
                    bloqueada: true,
                    mensaje: payload?.message || 'Esta prenda no se puede editar por estado de insumos.'
                };
                window.__cacheBloqueoPrendasLista.set(cacheKey, resultado);
                return resultado;
            }

            if (!response.ok) {
                return { bloqueada: false, mensaje: '' };
            }

            const bloqueada = payload?.prenda?.puede_editar === false || payload?.prenda?.bloqueo_edicion?.bloqueada;
            const resultado = {
                bloqueada: !!bloqueada,
                mensaje: payload?.prenda?.bloqueo_edicion?.mensaje || payload?.message || ''
            };
            window.__cacheBloqueoPrendasLista.set(cacheKey, resultado);
            return resultado;
        } catch (error) {
            console.warn('[ListaPrendas] No se pudo validar bloqueo previo:', error);
            return { bloqueada: false, mensaje: '' };
        }
    }

    async function intentarEditarPrendaDesdeLista(elemento, idx) {
        const item = JSON.parse(elemento.closest('[data-prenda]').getAttribute('data-prenda'));
        const pedidoId = window.datosEdicionPedido?.id;
        const card = elemento.closest('[data-prenda]');
        const mensajeBloqueo = card?.getAttribute('data-bloqueo-mensaje') || '';
        const yaBloqueada = card?.getAttribute('data-bloqueada') === '1';
        const bloqueoValidado = card?.getAttribute('data-bloqueo-validado') === '1';

        console.log('[ONCLICK] Prenda clickeada:', item.nombre_prenda || item.nombre, 'idx:', idx);

        if (yaBloqueada) {
            _mostrarModalPrendaBloqueadaDesdeLista(mensajeBloqueo);
            return;
        }

        if (!bloqueoValidado) {
            const validacion = await _validarBloqueoEdicionPrendaDesdeLista(item, pedidoId);
            if (validacion.bloqueada) {
                _mostrarModalPrendaBloqueadaDesdeLista(validacion.mensaje);
                return;
            }
        }

        Swal.close();
        setTimeout(function() {
            if (typeof window.editarPrendaDePedido === 'function') {
                console.log('[ONCLICK] Abriendo modal completo via editarPrendaDePedido');
                window.editarPrendaDePedido(item, idx, pedidoId);
            } else {
                console.error('[ONCLICK-ERROR] editarPrendaDePedido no disponible');
                const editor = window.prendaEditorGlobal;
                if (editor && typeof editor.cargarPrendaEnModal === 'function') {
                    window._editandoPrendaDePedido = { pedidoId: pedidoId, prendaIndex: idx };
                    editor.cargarPrendaEnModal(item, idx);
                }
            }
        }, 150);
    }

    async function intentarEliminarPrendaDesdeLista(elemento, idx) {
        const item = JSON.parse(elemento.closest('[data-prenda]').getAttribute('data-prenda'));
        const pedidoId = window.datosEdicionPedido?.id;
        const card = elemento.closest('[data-prenda]');
        const mensajeBloqueo = card?.getAttribute('data-bloqueo-mensaje') || '';
        const yaBloqueada = card?.getAttribute('data-bloqueada') === '1';
        const bloqueoValidado = card?.getAttribute('data-bloqueo-validado') === '1';

        console.log('[ONCLICK-ELIMINAR] Prenda a eliminar:', item.nombre_prenda || item.nombre, 'idx:', idx);

        if (yaBloqueada) {
            _mostrarModalPrendaBloqueadaDesdeLista(mensajeBloqueo);
            return;
        }

        if (!bloqueoValidado) {
            const validacion = await _validarBloqueoEdicionPrendaDesdeLista(item, pedidoId);
            if (validacion.bloqueada) {
                _mostrarModalPrendaBloqueadaDesdeLista(validacion.mensaje);
                return;
            }
        }

        if (typeof window.abrirModalEliminarPrenda === 'function') {
            window.abrirModalEliminarPrenda(item, idx, pedidoId);
        } else {
            console.error('[ONCLICK-ERROR] abrirModalEliminarPrenda no disponible');
        }
    }

    async function precargarBloqueosPrendasEnLista(prendas, pedidoId) {
        const tareas = prendas.map(async (item) => {
            const prendaId = item?.id || item?.prenda_pedido_id;
            if (!prendaId) return;

            const card = document.querySelector(`[data-prenda-id="${prendaId}"]`);
            if (!card) return;

            const validacion = await _validarBloqueoEdicionPrendaDesdeLista(item, pedidoId);
            if (!validacion.bloqueada) return;

            card.setAttribute('data-bloqueada', '1');
            card.setAttribute('data-bloqueo-mensaje', validacion.mensaje || '');
            card.style.borderColor = '#9ca3af';
            card.style.background = '#f3f4f6';

            const clickable = card.querySelector('[data-action="editar-card"]');
            if (clickable) {
                clickable.style.cursor = 'not-allowed';
                clickable.title = validacion.mensaje || 'Edicion bloqueada por estado';
            }

            const btnEditar = card.querySelector('[data-action="editar-btn"]');
            if (btnEditar) {
                btnEditar.disabled = true;
                btnEditar.style.background = '#9ca3af';
                btnEditar.style.cursor = 'not-allowed';
                btnEditar.style.transform = 'none';
                btnEditar.title = validacion.mensaje || 'Edicion bloqueada por estado';
            }

            const btnEliminar = card.querySelector('[data-action="eliminar-btn"]');
            if (btnEliminar) {
                btnEliminar.disabled = true;
                btnEliminar.style.background = '#9ca3af';
                btnEliminar.style.cursor = 'not-allowed';
                btnEliminar.style.transform = 'none';
                btnEliminar.title = validacion.mensaje || 'Eliminacion bloqueada por estado';
            }
        });

        await Promise.allSettled(tareas);
    }

    async function hidratarBloqueosAntesDeRender(prendas, pedidoId) {
        const tareas = (prendas || []).map(async (item) => {
            const validacion = await _validarBloqueoEdicionPrendaDesdeLista(item, pedidoId);
            item.puede_editar = !validacion.bloqueada;
            item.bloqueada_edicion = !!validacion.bloqueada;
            item.bloqueo_edicion = item.bloqueo_edicion || {};
            item.bloqueo_edicion.bloqueada = !!validacion.bloqueada;
            item.bloqueo_edicion.mensaje = validacion.mensaje || item.bloqueo_edicion.mensaje || '';
        });

        await Promise.allSettled(tareas);
    }

    /**
     * Abre el modal de prendas del pedido con lista editable
     */
    function abrirEditarPrendas() {
        Validator.requireEdicionPedido(async () => {
            const datos = window.datosEdicionPedido;
            const prendas = datos.prendas || [];
            await hidratarBloqueosAntesDeRender(prendas, datos.id);
            // Siempre mostrar lista, aunque esté vacía
            window.prendasEdicion = {
                pedidoId: datos.id, // Siempre usar el ID real de la BD
                prendas: prendas
            };
            
            let htmlListaPrendas = `<div style="display: grid; grid-template-columns: 1fr; gap: 0.75rem;">`;
            
            if (prendas.length === 0) {
                // Mostrar mensaje simple sin botón de agregar
                htmlListaPrendas += `
                    <div style="text-align: center; padding: 2rem; background: #f9fafb; border-radius: 8px; border: 2px dashed #d1d5db;">
                        <p style="color: #6b7280; margin: 0;">Este pedido no tiene prendas</p>
                    </div>
                `;
            } else {
                prendas.forEach((item, idx) => {
                    const nombre = item.nombre_prenda || item.nombre || 'Prenda sin nombre';
                    const bloqueoInicialConocido = typeof item?.puede_editar !== 'undefined' || !!item?.bloqueo_edicion;
                    const bloqueadaInicial = item?.bloqueada_edicion === true
                        || item?.puede_editar === false
                        || item?.bloqueo_edicion?.bloqueada === true;
                    const mensajeBloqueoInicial = item?.bloqueo_edicion?.mensaje || '';
                    // Calcular cantidad total desde tallas
                    let cantidad = 0;
                    if (item.tallas && typeof item.tallas === 'object') {
                        Object.values(item.tallas).forEach(generoTallas => {
                            if (generoTallas && typeof generoTallas === 'object') {
                                Object.values(generoTallas).forEach(qty => {
                                    cantidad += parseInt(qty) || 0;
                                });
                            }
                        });
                    }
                    if (!cantidad && item.cantidad) cantidad = item.cantidad;
                    
                    // Serializar item para inyectarlo como dato literal en el onclick
                    const itemJson = JSON.stringify(item).replace(/"/g, '&quot;');
                    const cardStyle = bloqueadaInicial
                        ? 'background: #f3f4f6; border: 2px solid #9ca3af; border-radius: 8px; padding: 1rem; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s ease;'
                        : 'background: white; border: 2px solid #1e40af; border-radius: 8px; padding: 1rem; display: flex; justify-content: space-between; align-items: center; transition: all 0.3s ease;';
                    const cardHoverIn = bloqueadaInicial ? '' : "this.style.background='#eff6ff'; this.style.borderColor='#1e40af';";
                    const cardHoverOut = bloqueadaInicial ? '' : "this.style.background='white'; this.style.borderColor='#1e40af';";
                    const cursorCard = bloqueadaInicial ? 'not-allowed' : 'pointer';
                    const btnEditarStyle = bloqueadaInicial
                        ? 'background: #9ca3af; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: not-allowed; transition: all 0.2s; transform: none;'
                        : 'background: #1e40af; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;';
                    const btnEliminarStyle = bloqueadaInicial
                        ? 'background: #9ca3af; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: not-allowed; transition: all 0.2s; transform: none;'
                        : 'background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;';
                    const disabledAttr = bloqueadaInicial ? 'disabled' : '';
                    const tooltipAttr = bloqueadaInicial && mensajeBloqueoInicial
                        ? `title="${mensajeBloqueoInicial.replace(/"/g, '&quot;')}"`
                        : '';
                    htmlListaPrendas += `
                        <div data-bloqueo-validado="${bloqueoInicialConocido ? '1' : '0'}" data-bloqueada="${bloqueadaInicial ? '1' : '0'}" data-bloqueo-mensaje="${(mensajeBloqueoInicial || '').replace(/"/g, '&quot;')}" data-prenda-id="${item.id || item.prenda_pedido_id || ''}" data-prenda="${itemJson}" style="${cardStyle}" onmouseover="${cardHoverIn}" onmouseout="${cardHoverOut}">
                            <div data-action="editar-card" style="flex: 1; cursor: ${cursorCard};" onclick="intentarEditarPrendaDesdeLista(this, ${idx})" ${tooltipAttr}>
                                <h4 style="margin: 0; color: #1f2937; font-size: 0.95rem; font-weight: 700;">${nombre.toUpperCase()}</h4>
                                <p style="margin: 0.5rem 0 0 0; color: #6b7280; font-size: 0.85rem;">Cantidad: <strong>${cantidad}</strong></p>
                            </div>
                            <div style="display: flex; gap: 0.5rem; margin-left: 1rem;">
                                <button type="button" data-action="editar-btn" style="${btnEditarStyle}" onmouseover="if(!this.disabled){this.style.opacity='0.8'; this.style.transform='scale(1.05)';}" onmouseout="if(!this.disabled){this.style.opacity='1'; this.style.transform='scale(1)';}" onclick="intentarEditarPrendaDesdeLista(this, ${idx})" ${disabledAttr} ${tooltipAttr}>
                                     Editar
                                </button>
                                <button type="button" data-action="eliminar-btn" style="${btnEliminarStyle}" onmouseover="if(!this.disabled){this.style.opacity='0.8'; this.style.transform='scale(1.05)';}" onmouseout="if(!this.disabled){this.style.opacity='1'; this.style.transform='scale(1)';}" onclick="intentarEliminarPrendaDesdeLista(this, ${idx})" ${disabledAttr} ${tooltipAttr}>
                                     Eliminar
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                // Botón de agregar más prendas removido
            }
            
            htmlListaPrendas += '</div>';
            
            // Crear HTML con header mejorado
            const htmlConHeader = `
                <div style="background: white; border-radius: 6px; width: 100%; display: flex; flex-direction: column; box-shadow: 0 8px 30px rgba(0,0,0,0.3); overflow: hidden;">
                    <!-- Header Azul con mejor espaciado -->
                    <div style="padding: 20px; background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <h3 style="margin: 0; color: white; font-size: 20px; font-weight: 700; flex: 1;">
                            Selecciona una Prenda para Editar
                        </h3>
                        <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                            <button onclick="agregarNuevaPrendaAPedido();" 
                                style="background: #16a34a; border: none; cursor: pointer; color: white; padding: 10px 16px; line-height: 1; transition: all 0.2s; font-weight: bold; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 14px; white-space: nowrap;"
                                onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'"
                                onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                                ＋ Agregar Prenda
                            </button>
                            <button onclick="abrirModalEditarPedido(window.datosEdicionPedido.id || window.datosEdicionPedido.numero_pedido, window.datosEdicionPedido, 'editar');" 
                                style="background: #ef4444; border: none; cursor: pointer; color: white; padding: 10px 16px; line-height: 1; transition: all 0.2s; font-weight: bold; border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 14px; white-space: nowrap;"
                                onmouseover="this.style.opacity='0.8'; this.style.transform='scale(1.05)'"
                                onmouseout="this.style.opacity='1'; this.style.transform='scale(1)'">
                                ← Volver
                            </button>
                        </div>
                    </div>
                    
                    <!-- Content -->
                    <div style="padding: 24px; background: #fafafa; overflow-y: auto; max-height: 65vh; min-height: 200px;">
                        ${htmlListaPrendas}
                    </div>
                </div>
            `;
            
            Swal.fire({
                html: htmlConHeader,
                width: '800px',
                showConfirmButton: false,
                allowOutsideClick: true,
                allowEscapeKey: true,
                customClass: {
                    container: 'swal-centered-container prendas-modal-container',
                    popup: 'swal-centered-popup prendas-modal-popup',
                    htmlContainer: 'prendas-modal-html'
                },
                didOpen: (modal) => {
                    const container = modal.closest('.swal2-container');
                    if (container) {
                        container.style.display = 'flex';
                        container.style.alignItems = 'center';
                        container.style.justifyContent = 'center';
                        container.style.height = '100vh';
                        container.style.zIndex = '999998';
                    }
                    modal.style.marginTop = '0';
                    modal.style.marginBottom = '0';
                }
            });
        });
    }
</script>
