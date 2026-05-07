{{-- Modal para crear recibos parciales por talla y cantidad --}}

<div id="modal-recibo-parcial-overlay" 
     style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9998; animation: fadeIn 0.3s ease-in-out;"
     onclick="if(event.target === this) cerrarModalReciboParcial()">
</div>

<div id="modal-recibo-parcial" 
     style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); z-index: 9999;">
    
    <!-- Header -->
    <div style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; padding: 24px; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 10;">
        <div>
            <h2 style="margin: 0; font-size: 20px; font-weight: 700;">Crear Recibo Parcial</h2>
            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 14px;">Selecciona tallas y cantidades</p>
        </div>
        <button onclick="cerrarModalReciboParcial()" style="background: rgba(255,255,255,0.3); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">
            ✕
        </button>
    </div>

    <!-- Contenido -->
    <div style="padding: 24px;">
        
        <!-- Información de la prenda -->
        <div style="background: #f3f4f6; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <p style="margin: 0 0 8px 0; font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600;">Prenda</p>
            <h3 id="parcial-prenda-nombre" style="margin: 0; font-size: 16px; color: #1f2937; font-weight: 600;">-</h3>
            <p id="parcial-proceso-nombre" style="margin: 8px 0 0 0; font-size: 13px; color: #6b7280;">-</p>
        </div>

        <!-- Sección de edición de prenda (si es supervisor_pedidos) -->
        <div id="parcial-editar-prenda-section" style="display: none; background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <button onclick="toggleEditarPrendaSeccion()" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer; padding: 0; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600; color: #1f2937; font-size: 14px;">✏️ Editar descripción y origen</span>
                <span id="parcial-editar-toggle-icon" style="color: #059669; font-size: 18px;">▼</span>
            </button>

            <div id="parcial-editar-contenido" style="display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #86efac;">
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px; font-size: 13px;">Descripción</label>
                    <div style="display:flex; gap:8px; margin-bottom:8px; flex-wrap:wrap;">
                        <button type="button" onclick="aplicarColorDescripcionParcial('#dc2626')" style="border:1px solid #d1d5db; background:#fff; color:#dc2626; border-radius:6px; padding:4px 8px; font-size:12px; cursor:pointer;">
                            Color rojo
                        </button>
                        <button type="button" onclick="quitarColorDescripcionParcial()" style="border:1px solid #d1d5db; background:#fff; color:#374151; border-radius:6px; padding:4px 8px; font-size:12px; cursor:pointer;">
                            Quitar color
                        </button>
                        <button type="button" onclick="limpiarFormatoDescripcionParcial()" style="border:1px solid #d1d5db; background:#fff; color:#374151; border-radius:6px; padding:4px 8px; font-size:12px; cursor:pointer;">
                            Limpiar formato
                        </button>
                    </div>
                    <div id="parcial-editar-descripcion"
                         contenteditable="true"
                         data-placeholder="Edita la descripción de la prenda..."
                         style="width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit; font-size: 13px; min-height: 70px; box-sizing: border-box; line-height:1.4; white-space:pre-wrap;"></div>
                </div>

                <div>
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 6px; font-size: 13px;">¿De dónde viene?</label>
                    <select id="parcial-editar-de-bodega" style="width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit; font-size: 13px; background-color: white; cursor: pointer;">
                        <option value="1">De bodega</option>
                        <option value="0">Confección</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sección de datos del anexo (ubicaciones/observaciones del proceso) -->
        <div id="parcial-editar-proceso-section" style="display: none; background: #eff6ff; border: 1px solid #93c5fd; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
            <button onclick="toggleEditarProcesoSeccion()" style="background: none; border: none; width: 100%; text-align: left; cursor: pointer; padding: 0; display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600; color: #1f2937; font-size: 14px;">Editar ubicaciones y observaciones del anexo</span>
                <span id="parcial-proceso-toggle-icon" style="color: #2563eb; font-size: 18px;">▼</span>
            </button>
            <div id="parcial-editar-proceso-contenido" style="display: none; margin-top: 12px; padding-top: 12px; border-top: 1px solid #93c5fd;">
                <div style="margin-bottom: 12px;">
                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:6px; font-size:13px;">Ubicaciones (una por línea)</label>
                    <textarea id="parcial-editar-ubicaciones" placeholder="Ej: PECHO&#10;ESPALDA&#10;MANGA DERECHA" style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-family:inherit; font-size:13px; resize:vertical; min-height:72px; box-sizing:border-box;"></textarea>
                </div>
                <div>
                    <label style="display:block; font-weight:600; color:#374151; margin-bottom:6px; font-size:13px;">Observaciones del proceso</label>
                    <textarea id="parcial-editar-observaciones-proceso" placeholder="Observaciones para este anexo..." style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px; font-family:inherit; font-size:13px; resize:vertical; min-height:72px; box-sizing:border-box;"></textarea>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <div id="parcial-error" style="display: none; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; color: #991b1b; margin-bottom: 16px;">
            <p style="margin: 0; font-weight: 600;">Error</p>
            <p id="parcial-error-message" style="margin: 8px 0 0 0; font-size: 14px;"></p>
        </div>

        <!-- Loading State -->
        <div id="parcial-loading" style="display: none; text-align: center; padding: 40px;">
            <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top: 4px solid #8b5cf6; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
            <p style="margin-top: 16px; color: #6b7280; font-size: 14px;">Cargando información...</p>
        </div>

        <!-- Selector de Tallas -->
        <div id="parcial-content" style="display: none;">
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 12px; font-size: 14px;">
                    Selecciona tallas:
                </label>
                <div id="parcial-tallas-list" style="display: grid; gap: 8px;">
                    <!-- Se llenará dinámicamente -->
                </div>
            </div>

            <!-- Cantidad por talla -->
            <div style="margin-bottom: 24px; background: #f9fafb; border-radius: 8px; padding: 16px; border: 1px solid #e5e7eb;">
                <label style="display: block; font-weight: 600; color: #1f2937; margin-bottom: 12px; font-size: 14px;">
                    Cantidades:
                </label>
                <div id="parcial-cantidades-list" style="display: grid; gap: 12px;">
                    <!-- Se llenará dinámicamente -->
                </div>
                <div id="parcial-total-info" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb; font-weight: 600; color: #1f2937; font-size: 13px;">
                    Total a enviar: <span id="parcial-total-cantidad">0</span>
                </div>
            </div>

        </div>

        <!-- Botones de acción -->
        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
            <button onclick="cerrarModalReciboParcial()" 
                    style="background: #e5e7eb; color: #374151; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s;">
                Cancelar
            </button>
            <button id="parcial-guardar-btn" 
                    onclick="guardarReciboParcial()"
                    style="background: #8b5cf6; color: white; border: none; padding: 10px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.2s; display: none;">
                <i class="fas fa-check"></i> Crear Recibo
            </button>
        </div>

    </div>

</div>

<script>
    function escapeHtmlParcialDescripcion(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function setDescripcionParcialEditorValue(value) {
        const descripcionEl = document.getElementById('parcial-editar-descripcion');
        if (!descripcionEl) return;
        const raw = String(value || '').trim();
        descripcionEl.innerHTML = raw ? raw : '';
        descripcionEl.dataset.empty = raw ? 'false' : 'true';
    }

    function getDescripcionParcialEditorValue() {
        const descripcionEl = document.getElementById('parcial-editar-descripcion');
        if (!descripcionEl) return null;
        const html = String(descripcionEl.innerHTML || '')
            .replace(/<div><br><\/div>/gi, '')
            .trim();
        return html || null;
    }

    function setUbicacionesAnexoValue(values) {
        const el = document.getElementById('parcial-editar-ubicaciones');
        if (!el) return;
        const list = Array.isArray(values) ? values : [];
        el.value = list.map((x) => String(x || '').trim()).filter(Boolean).join('\n');
    }

    function getUbicacionesAnexoValue() {
        const el = document.getElementById('parcial-editar-ubicaciones');
        if (!el) return [];
        return String(el.value || '')
            .split('\n')
            .map((x) => x.trim())
            .filter(Boolean);
    }

    function setObservacionesAnexoValue(value) {
        const el = document.getElementById('parcial-editar-observaciones-proceso');
        if (!el) return;
        el.value = String(value || '');
    }

    function getObservacionesAnexoValue() {
        const el = document.getElementById('parcial-editar-observaciones-proceso');
        if (!el) return null;
        const value = String(el.value || '').trim();
        return value || null;
    }

    window.aplicarColorDescripcionParcial = function(color) {
        document.execCommand('styleWithCSS', false, true);
        document.execCommand('foreColor', false, color || '#dc2626');
    };

    window.quitarColorDescripcionParcial = function() {
        document.execCommand('styleWithCSS', false, true);
        document.execCommand('foreColor', false, '#000000');
    };

    window.limpiarFormatoDescripcionParcial = function() {
        document.execCommand('removeFormat', false, null);
    };

    /**
     * Estado global del modal de recibo parcial
     */
    window.modalReciboParcialState = {
        prendaId: null,
        tipoProceso: null,
        prendaNombre: null,
        tallas: [],
        tallasCantidad: {},
        pedidoId: null,
        mode: 'crear_recibo',
        consecutivoReciboId: null,
        ubicacionesAnexo: [],
        observacionesAnexo: ''
    };

    /**
     * Abre el modal de recibo parcial
     * @param {number} prendaId - ID de la prenda
     * @param {string} tipoProceso - Tipo de proceso (Costura, Bordado, etc.)
     * @param {number} pedidoId - ID del pedido
     */
    window.abrirModalReciboParcial = async function(prendaId, tipoProceso, pedidoId, options = {}) {
        // Encontrar datos de la prenda en el estado global
        const penda = window.selectorRecibosState.prendas.find(p => p.id === prendaId);
        if (!penda) {
            alert('Prenda no encontrada');
            return;
        }

        // Regla de negocio:
        // Si el recibo base de COSTURA ya está aprobado/activo con consecutivo,
        // no se permite crear anexos de COSTURA hasta anular ese recibo base.
        const tipoProcesoLower = String(tipoProceso || '').trim().toLowerCase();
        const esIntentoAnexoCostura = tipoProcesoLower === 'costura' && (options.mode || 'crear_recibo') === 'crear_recibo';
        if (esIntentoAnexoCostura) {
            const reciboCosturaBase = penda?.recibos?.COSTURA || penda?.consecutivos?.COSTURA || null;
            const tieneConsecutivoBase =
                (typeof reciboCosturaBase === 'object' && reciboCosturaBase)
                    ? !!(reciboCosturaBase.consecutivo_actual || reciboCosturaBase.numero_recibo || reciboCosturaBase.numeroRecibo)
                    : !!reciboCosturaBase;
            const estaBaseAprobadaOActiva =
                (typeof reciboCosturaBase === 'object' && reciboCosturaBase)
                    ? (Number(reciboCosturaBase.activo) === 1 || String(reciboCosturaBase.estado || '').toUpperCase() === 'APROBADO')
                    : tieneConsecutivoBase;
            const estaBaseAnulada =
                (typeof reciboCosturaBase === 'object' && reciboCosturaBase)
                    ? String(reciboCosturaBase.estado || '').toUpperCase() === 'ANULADO'
                    : false;

            if (tieneConsecutivoBase && estaBaseAprobadaOActiva && !estaBaseAnulada) {
                const mensajeBloqueo = 'No se puede generar anexo de COSTURA porque el recibo base ya está aprobado con consecutivo. Primero debes anular el recibo de COSTURA.';
                if (typeof window.mostrarModalConfirmar === 'function') {
                    window.mostrarModalConfirmar(
                        'Acción no permitida',
                        mensajeBloqueo,
                        '#ef4444',
                        () => {}
                    );
                } else {
                    alert(mensajeBloqueo);
                }
                return;
            }
        }

        window.modalReciboParcialState.prendaId = prendaId;
        window.modalReciboParcialState.tipoProceso = tipoProceso;
        window.modalReciboParcialState.prendaNombre = penda.nombre;
        window.modalReciboParcialState.pedidoId = pedidoId;
        window.modalReciboParcialState.tallas = [];
        window.modalReciboParcialState.tallasCantidad = {};
        window.modalReciboParcialState.mode = options.mode || 'crear_recibo';
        window.modalReciboParcialState.consecutivoReciboId = options.consecutivoReciboId || null;
        window.modalReciboParcialState.ubicacionesAnexo = [];
        window.modalReciboParcialState.observacionesAnexo = '';

        // Reset visual para evitar que se arrastre selección de un anexo anterior
        const tallasListEl = document.getElementById('parcial-tallas-list');
        if (tallasListEl) tallasListEl.innerHTML = '';
        const cantidadesListEl = document.getElementById('parcial-cantidades-list');
        if (cantidadesListEl) cantidadesListEl.innerHTML = '<p style="color: #9ca3af; text-align: center; padding: 12px;">Selecciona tallas para ver cantidades editable</p>';
        const totalEl = document.getElementById('parcial-total-cantidad');
        if (totalEl) totalEl.textContent = '0';
        const errorEl = document.getElementById('parcial-error');
        if (errorEl) errorEl.style.display = 'none';
        const errorMsgEl = document.getElementById('parcial-error-message');
        if (errorMsgEl) errorMsgEl.textContent = '';

        const overlay = document.getElementById('modal-recibo-parcial-overlay');
        const modal = document.getElementById('modal-recibo-parcial');
        const loading = document.getElementById('parcial-loading');
        const content = document.getElementById('parcial-content');
        const error = document.getElementById('parcial-error');

        // Mostrar modal con loading
        overlay.style.display = 'block';
        modal.style.display = 'block';
        loading.style.display = 'block';
        content.style.display = 'none';
        error.style.display = 'none';

        // Actualizar información de la prenda
        const modoEntregaParcial = window.modalReciboParcialState.mode === 'entrega_parcial';
        document.getElementById('parcial-prenda-nombre').textContent = penda.nombre || '-';
        document.getElementById('parcial-proceso-nombre').textContent = modoEntregaParcial
            ? 'ENTREGA PARCIAL DE COSTURA'
            : `RECIBO DE ${tipoProceso.toUpperCase()}`;
        const tituloEl = document.querySelector('#modal-recibo-parcial h2');
        const subtituloHeaderEl = document.querySelector('#modal-recibo-parcial h2 + p');
        const botonGuardarEl = document.getElementById('parcial-guardar-btn');
        if (tituloEl) {
            tituloEl.textContent = modoEntregaParcial ? 'Registrar Entrega Parcial' : 'Crear Recibo Parcial';
        }
        if (subtituloHeaderEl) {
            subtituloHeaderEl.textContent = modoEntregaParcial ? 'Selecciona tallas y cantidades entregadas' : 'Selecciona tallas y cantidades';
        }
        if (botonGuardarEl) {
            botonGuardarEl.innerHTML = modoEntregaParcial
                ? '<i class="fas fa-check"></i> Guardar entrega'
                : '<i class="fas fa-check"></i> Crear Recibo';
        }

        // Mostrar/ocultar sección de edición de prenda (COSTURA/REFLECTIVO, supervisor_pedidos, y no en modo entrega_parcial)
        const editarPrendaSection = document.getElementById('parcial-editar-prenda-section');
        // Nota: esSupervisorPedidos viene como string 'true'/'false', comparar con 'true'
        const esSupervisorPedidos = String(window.selectorRecibosState?.esSupervisorPedidos || '') === 'true';
        const tipoProcesoLowerEdicion = String(tipoProceso || '').toLowerCase();
        const permiteEdicionPrenda = tipoProcesoLowerEdicion === 'costura' || tipoProcesoLowerEdicion === 'reflectivo';
        const mostrarEditarPrenda = esSupervisorPedidos && !modoEntregaParcial && permiteEdicionPrenda;
        const editarProcesoSection = document.getElementById('parcial-editar-proceso-section');
        const tiposConDatosProcesoAnexo = ['reflectivo', 'bordado', 'estampado', 'dtf', 'sublimado'];
        const mostrarEditarProcesoAnexo = esSupervisorPedidos
            && !modoEntregaParcial
            && tiposConDatosProcesoAnexo.includes(tipoProcesoLowerEdicion);

        if (editarPrendaSection) {
            editarPrendaSection.style.display = mostrarEditarPrenda ? 'block' : 'none';

            if (mostrarEditarPrenda) {
                // Llenar campos con datos actuales de la prenda
                const descripcionEl = document.getElementById('parcial-editar-descripcion');
                const deBodegaEl = document.getElementById('parcial-editar-de-bodega');

                if (descripcionEl) {
                    setDescripcionParcialEditorValue(penda.descripcion || '');
                }
                if (deBodegaEl) {
                    deBodegaEl.value = penda.de_bodega ? '1' : '0';
                }

                // Asegurar que el contenido expandible está colapsado por defecto
                const contenidoEl = document.getElementById('parcial-editar-contenido');
                if (contenidoEl) {
                    contenidoEl.style.display = 'none';
                }
                const toggleIcon = document.getElementById('parcial-editar-toggle-icon');
                if (toggleIcon) {
                    toggleIcon.textContent = '▶';
                }
            }
        }

        if (editarProcesoSection) {
            editarProcesoSection.style.display = mostrarEditarProcesoAnexo ? 'block' : 'none';
            const contenidoProc = document.getElementById('parcial-editar-proceso-contenido');
            const iconProc = document.getElementById('parcial-proceso-toggle-icon');
            if (contenidoProc) contenidoProc.style.display = 'none';
            if (iconProc) iconProc.textContent = '▶';

            if (mostrarEditarProcesoAnexo) {
                const procesoEncontrado = Array.isArray(penda?.procesos)
                    ? penda.procesos.find((p) => String(p?.tipo_proceso || p?.nombre_proceso || '').toUpperCase() === String(tipoProceso || '').toUpperCase())
                    : null;
                const ubicacionesBase = Array.isArray(procesoEncontrado?.ubicaciones_array)
                    ? procesoEncontrado.ubicaciones_array
                    : (Array.isArray(procesoEncontrado?.ubicaciones) ? procesoEncontrado.ubicaciones : []);
                const observacionesBase = String(
                    procesoEncontrado?.observaciones_generales ||
                    procesoEncontrado?.observaciones ||
                    ''
                );
                window.modalReciboParcialState.ubicacionesAnexo = ubicacionesBase.filter(Boolean);
                window.modalReciboParcialState.observacionesAnexo = observacionesBase;
                setUbicacionesAnexoValue(window.modalReciboParcialState.ubicacionesAnexo);
                setObservacionesAnexoValue(window.modalReciboParcialState.observacionesAnexo);
            }
        }

        // Cargar tallas disponibles
        try {
            // Extraer tallas del proceso específico (no de la prenda)
            let tallas = [];
            const tipoProcesoUpper = modoEntregaParcial ? 'COSTURA' : String(tipoProceso || '').toUpperCase();
            const esCosturaBase = tipoProcesoUpper === 'COSTURA' || tipoProcesoUpper === 'COSTURA-BODEGA';

            // Helper: normaliza estructura relacional de tallas con colores desde la prenda
            const normalizarTallasConColoresDesdePrenda = (origen) => {
                if (!origen || typeof origen !== 'object' || Array.isArray(origen)) return [];

                const normalizadas = [];
                const pushItem = (generoKey, tallaKey, color, cantidad) => {
                    const tallaStr = String(tallaKey || '').trim();
                    const generoStr = generoKey ? String(generoKey).toUpperCase() : null;
                    const colorStr = color !== null && color !== undefined && String(color).trim() !== '' ? String(color).trim() : null;
                    const qty = parseInt(cantidad) || 0;
                    if (!tallaStr || qty <= 0) return;
                    normalizadas.push({ talla: tallaStr, cantidad: qty, genero: generoStr, color: colorStr });
                };

                const keys = Object.keys(origen);
                const esPorGenero = keys.some(k => ['dama', 'caballero', 'unisex', 'sobremedida', 'DAMA', 'CABALLERO', 'UNISEX'].includes(String(k)));

                if (esPorGenero) {
                    keys.forEach(gKey => {
                        const generoVal = origen[gKey];
                        if (!generoVal || typeof generoVal !== 'object') return;
                        Object.entries(generoVal).forEach(([tallaKey, colorList]) => {
                            if (Array.isArray(colorList)) {
                                colorList.forEach(item => {
                                    pushItem(gKey, tallaKey, item?.color ?? null, item?.cantidad ?? item);
                                });
                            } else if (colorList && typeof colorList === 'object') {
                                pushItem(gKey, tallaKey, colorList.color ?? null, colorList.cantidad ?? 0);
                            } else {
                                pushItem(gKey, tallaKey, null, colorList);
                            }
                        });
                    });
                } else {
                    Object.entries(origen).forEach(([tallaKey, qty]) => pushItem(null, tallaKey, null, qty));
                }

                return normalizadas;
            };

            // Helper: normaliza talla_colores [{genero,talla,color_nombre,cantidad}, ...]
            // y consolida duplicados por genero+talla+color.
            const normalizarDesdeTallaColoresArray = (arr) => {
                if (!Array.isArray(arr) || arr.length === 0) return [];
                const map = new Map();

                arr.forEach((item) => {
                    const talla = String(item?.talla || '').trim();
                    const genero = item?.genero ? String(item.genero).toUpperCase() : null;
                    const colorRaw = item?.color_nombre ?? item?.color ?? null;
                    const color = colorRaw !== null && colorRaw !== undefined && String(colorRaw).trim() !== ''
                        ? String(colorRaw).trim()
                        : null;
                    const cantidad = parseInt(item?.cantidad || 0, 10) || 0;
                    if (!talla || cantidad <= 0) return;

                    const key = `${genero || ''}__${talla}__${color || ''}`;
                    const prev = map.get(key) || { talla, genero, color, cantidad: 0 };
                    prev.cantidad += cantidad;
                    map.set(key, prev);
                });

                return Array.from(map.values());
            };

            // PRIORIDAD 1: talla_colores explícito de la prenda
            if (Array.isArray(penda.talla_colores) && penda.talla_colores.length > 0) {
                const candidatas = normalizarDesdeTallaColoresArray(penda.talla_colores);
                if (candidatas.length > 0) {
                    tallas = candidatas;
                }
            }

            // PRIORIDAD 2: si la prenda tiene tallas por color, usarlas siempre para anexos
            // (las tallas del proceso vienen de pedidos_procesos_prenda_tallas y no incluyen color)
            if (!esCosturaBase && penda.tallas && typeof penda.tallas === 'object' && !Array.isArray(penda.tallas)) {
                const candidatas = normalizarTallasConColoresDesdePrenda(penda.tallas);
                const tieneColor = candidatas.some(t => t && t.color);
                if (candidatas.length > 0 && tieneColor) {
                    tallas = candidatas;
                }
            }
            
            // Buscar el proceso que coincide con tipoProceso
            // Importante: para COSTURA/COSTURA-BODEGA NO usar penda.procesos, porque ahí pueden venir anexos
            // con tallas parciales y eso haría que el modal muestre solo esas tallas.
            if (tallas.length === 0 && !esCosturaBase && penda.procesos && Array.isArray(penda.procesos)) {
                const procesoEncontrado = penda.procesos.find(p => {
                    // Comparar con tipo_proceso (STRING) o tipo_recibo
                    const tipo = String(p.tipo_proceso || p.nombre_proceso || '').toUpperCase();
                    const tipoParam = tipoProcesoUpper;
                    return tipo === tipoParam;
                });
                
                if (procesoEncontrado) {
                    // PRIORIDAD 3: talla_colores del proceso seleccionado
                    if (tallas.length === 0 && Array.isArray(procesoEncontrado.talla_colores) && procesoEncontrado.talla_colores.length > 0) {
                        const candidatas = normalizarDesdeTallaColoresArray(procesoEncontrado.talla_colores);
                        if (candidatas.length > 0) {
                            tallas = candidatas;
                        }
                    }

                    // Obtener tallas del proceso (pueden estar en diferentes formatos)
                    if (tallas.length === 0 && procesoEncontrado.tallas_transformadas) {
                        // Formato transformado: {dama: {...}, caballero: {...}, unisex: {...}}
                        const generos = ['DAMA', 'CABALLERO', 'UNISEX'];
                        generos.forEach(genero => {
                            const tallasPorGenero = procesoEncontrado.tallas_transformadas[genero.toLowerCase()] || {};
                            Object.entries(tallasPorGenero).forEach(([talla, cantidad]) => {
                                tallas.push({
                                    talla: talla,
                                    cantidad: cantidad,
                                    genero: genero
                                });
                            });
                        });
                    } else if (tallas.length === 0 && procesoEncontrado.tallas && Array.isArray(procesoEncontrado.tallas)) {
                        // Formato array directo
                        tallas = procesoEncontrado.tallas;
                    }
                }
            }
            
            // Si no encontró tallas en procesos, usar las de la prenda como fallback
            if (tallas.length === 0 && penda.tallas && Array.isArray(penda.tallas)) {
                tallas = penda.tallas;
            }

            // Caso: tallas relacionales con colores (estructura: {DAMA:{S:[{color,cantidad}]} ...})
            // Normalizar a lista plana con color.
            if (tallas.length === 0 && penda.tallas && typeof penda.tallas === 'object' && !Array.isArray(penda.tallas)) {
                const candidatas = normalizarTallasConColoresDesdePrenda(penda.tallas);
                if (candidatas.length > 0) {
                    tallas = candidatas;
                }
            }

            // Caso especial: COSTURA/COSTURA-BODEGA (recibo base) suele traer tallas como objeto
            // Formatos soportados:
            // - { dama: { S: 2 }, caballero: { M: 3 } }
            // - { 'dama-S': 2, 'caballero-M': 3 }
            // - { S: 5, M: 3 }
            if (tallas.length === 0 && esCosturaBase && penda.tallas && typeof penda.tallas === 'object' && !Array.isArray(penda.tallas)) {
                const normalizadas = [];
                const origen = penda.tallas;

                const pushTalla = (tallaKey, cantidad, genero = null) => {
                    const tallaStr = String(tallaKey || '').trim();
                    const qty = parseInt(cantidad) || 0;
                    if (!tallaStr || qty <= 0) return;
                    normalizadas.push({ talla: tallaStr, cantidad: qty, genero: genero });
                };

                // Detectar estructura anidada por género
                const keys = Object.keys(origen);
                const puedeSerAnidado = keys.some(k => ['dama', 'caballero', 'unisex'].includes(String(k).toLowerCase()));
                if (puedeSerAnidado) {
                    keys.forEach(k => {
                        const generoLower = String(k).toLowerCase();
                        const genero = generoLower === 'dama' ? 'DAMA' : (generoLower === 'caballero' ? 'CABALLERO' : (generoLower === 'unisex' ? 'UNISEX' : null));
                        const value = origen[k];
                        if (value && typeof value === 'object' && !Array.isArray(value)) {
                            Object.entries(value).forEach(([tallaKey, qty]) => pushTalla(tallaKey, qty, genero));
                        }
                    });
                } else {
                    // Claves planas: S, M o dama-S, caballero-M
                    Object.entries(origen).forEach(([tallaKey, qty]) => {
                        const rawKey = String(tallaKey);
                        const match = rawKey.match(/^(dama|caballero|unisex)[-_](.+)$/i);
                        if (match) {
                            const generoLower = String(match[1]).toLowerCase();
                            const genero = generoLower === 'dama' ? 'DAMA' : (generoLower === 'caballero' ? 'CABALLERO' : 'UNISEX');
                            const tallaReal = match[2];
                            pushTalla(tallaReal, qty, genero);
                        } else {
                            pushTalla(rawKey, qty, null);
                        }
                    });
                }

                if (normalizadas.length > 0) {
                    tallas = normalizadas;
                }
            }

            if (tallas.length === 0) {
                throw new Error('No se encontraron tallas para esta prenda');
            }

            window.modalReciboParcialState.tallas = tallas;
            
            // Renderizar tallas
            renderizarTallasEnModal(tallas);

            loading.style.display = 'none';
            content.style.display = 'block';
            document.getElementById('parcial-guardar-btn').style.display = 'inline-flex';

        } catch (err) {
            loading.style.display = 'none';
            error.style.display = 'block';
            document.getElementById('parcial-error-message').textContent = err.message || 'Error al cargar tallas';
        }
    };

    /**
     * Renderiza las tallas disponibles en el modal
     * @param {Array} tallas - Lista de tallas
     */
    function renderizarTallasEnModal(tallas) {
        const container = document.getElementById('parcial-tallas-list');
        container.innerHTML = '';

        // Agrupar por color para selección (si no hay color, usar 'Sin color')
        const grupos = {};
        const ordenColores = [];
        tallas.forEach((talla, idx) => {
            const colorLabel = (talla && talla.color) ? String(talla.color) : 'Sin color';
            if (!grupos[colorLabel]) {
                grupos[colorLabel] = [];
                ordenColores.push(colorLabel);
            }
            grupos[colorLabel].push({ talla, idx });
        });

        ordenColores.forEach(colorLabel => {
            container.innerHTML += `
                <div style="margin-top: 10px; padding: 10px 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <div style="font-weight: 700; color: #111827; font-size: 13px; text-transform: uppercase; letter-spacing: 0.02em;">${colorLabel}</div>
                </div>
            `;

            (grupos[colorLabel] || []).forEach(({ talla, idx }) => {
                const tallaId = `parcial-talla-${idx}`;
                const generoLabel = talla.genero ? ` (${talla.genero})` : '';
                const disponibleLabel = talla.cantidad ? ` - Disponible: ${talla.cantidad}` : '';
                const labelText = `${talla.talla}${generoLabel}${disponibleLabel}`;

                const html = `
                    <label style="display: flex; align-items: center; padding: 12px; background: white; border: 2px solid #e5e7eb; border-radius: 6px; cursor: pointer; transition: all 0.2s; position: relative;">
                        <input type="checkbox" id="${tallaId}" class="parcial-talla-checkbox" data-talla-index="${idx}" 
                               style="width: 18px; height: 18px; margin-right: 12px; cursor: pointer;"
                               onchange="actualizarCantidadTalla(${idx})">
                        <span style="flex: 1; font-weight: 500; color: #1f2937;">${labelText}</span>
                        <span style="color: #8b5cf6; font-weight: 600; font-size: 14px;">
                            ${talla.cantidad || 0}
                        </span>
                    </label>
                `;
                container.innerHTML += html;
            });
        });
    }

    /**
     * Actualiza la sección de cantidades según las tallas seleccionadas
     * @param {number} tallaIndex - Índice de la talla
     */
    window.actualizarCantidadTalla = function(tallaIndex) {
        const checkbox = document.querySelector(`.parcial-talla-checkbox[data-talla-index="${tallaIndex}"]`);
        const talla = window.modalReciboParcialState.tallas[tallaIndex];
        const cantidadesContainer = document.getElementById('parcial-cantidades-list');

        if (!checkbox) return;

        if (checkbox.checked) {
            // Agregar campo de cantidad
            window.modalReciboParcialState.tallasCantidad[tallaIndex] = talla.cantidad || 0;
        } else {
            // Remover campo de cantidad
            delete window.modalReciboParcialState.tallasCantidad[tallaIndex];
        }

        // Re-renderizar campos de cantidad
        renderizarCantidadesEnModal();
    };

    /**
     * Renderiza los campos de cantidad para las tallas seleccionadas
     */
    function renderizarCantidadesEnModal() {
        const container = document.getElementById('parcial-cantidades-list');
        container.innerHTML = '';

        let totalCantidad = 0;
        const tallas = window.modalReciboParcialState.tallas;
        const tallasCantidad = window.modalReciboParcialState.tallasCantidad;

        Object.keys(tallasCantidad).forEach(tallaIndex => {
            const idx = parseInt(tallaIndex);
            const talla = tallas[idx];
            const cantidadActual = tallasCantidad[idx];
            const maxCantidad = talla.cantidad || 0;

            const colorLabel = talla.color ? ` - ${talla.color}` : '';

            const html = `
                <div style="display: grid; grid-template-columns: 1fr 120px; gap: 12px; align-items: center;">
                    <label style="font-weight: 500; color: #1f2937; font-size: 13px;">
                        ${talla.talla}${colorLabel} (${talla.genero || 'General'}):
                    </label>
                    <input type="number" 
                           id="parcial-cantidad-${idx}" 
                           class="parcial-cantidad-input"
                           data-talla-index="${idx}"
                           min="1" 
                           max="${maxCantidad}" 
                           value="${cantidadActual}"
                           placeholder="Cantidad"
                           onchange="actualizarCantidadInput(${idx})"
                           style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; font-weight: 500; width: 100%; box-sizing: border-box;">
                </div>
            `;
            container.innerHTML += html;
            totalCantidad += cantidadActual;
        });

        // Actualizar total
        document.getElementById('parcial-total-cantidad').textContent = totalCantidad;

        // Si no hay tallas seleccionadas
        if (Object.keys(tallasCantidad).length === 0) {
            container.innerHTML = '<p style="color: #9ca3af; text-align: center; padding: 12px;">Selecciona tallas para ver cantidades editable</p>';
        }
    };

    /**
     * Actualiza el valor de cantidad en el estado
     * @param {number} tallaIndex - Índice de la talla
     */
    window.actualizarCantidadInput = function(tallaIndex) {
        const input = document.getElementById(`parcial-cantidad-${tallaIndex}`);
        if (input) {
            const valor = Math.max(1, Math.min(parseInt(input.value) || 0, parseInt(input.max) || 999));
            window.modalReciboParcialState.tallasCantidad[tallaIndex] = valor;
            renderizarCantidadesEnModal();
        }
    };

    /**
     * Guarda el recibo parcial
     */
    window.toggleEditarPrendaSeccion = function() {
        const contenido = document.getElementById('parcial-editar-contenido');
        const icon = document.getElementById('parcial-editar-toggle-icon');
        if (contenido) {
            const isVisible = contenido.style.display !== 'none';
            contenido.style.display = isVisible ? 'none' : 'block';
            if (icon) {
                icon.textContent = isVisible ? '▶' : '▼';
            }
        }
    };

    window.toggleEditarProcesoSeccion = function() {
        const contenido = document.getElementById('parcial-editar-proceso-contenido');
        const icon = document.getElementById('parcial-proceso-toggle-icon');
        if (contenido) {
            const isVisible = contenido.style.display !== 'none';
            contenido.style.display = isVisible ? 'none' : 'block';
            if (icon) {
                icon.textContent = isVisible ? '▶' : '▼';
            }
        }
    };

    window.guardarReciboParcial = async function() {
        const tallasCantidad = window.modalReciboParcialState.tallasCantidad;
        const modoEntregaParcial = window.modalReciboParcialState.mode === 'entrega_parcial';

        // Validar que hay tallas seleccionadas
        if (Object.keys(tallasCantidad).length === 0) {
            alert('Por favor selecciona al menos una talla');
            return;
        }

        // Validar que todas las cantidades son mayores a 0
        for (let tallaIndex in tallasCantidad) {
            if (tallasCantidad[tallaIndex] <= 0) {
                alert('Todas las cantidades deben ser mayores a 0');
                return;
            }
        }

        const btn = document.getElementById('parcial-guardar-btn');
        const loading = document.getElementById('parcial-loading');
        const content = document.getElementById('parcial-content');

        btn.disabled = true;
        loading.style.display = 'block';
        content.style.display = 'none';

        try {
            // Guardar cambios de la prenda si es supervisor_pedidos y hay cambios
            const seccionEditar = document.getElementById('parcial-editar-prenda-section');
            if (seccionEditar && seccionEditar.style.display !== 'none') {
                const descripcionEl = document.getElementById('parcial-editar-descripcion');
                const deBodegaEl = document.getElementById('parcial-editar-de-bodega');

                if (descripcionEl || deBodegaEl) {
                    const prendaEditResponse = await fetch(`/api/supervisor-pedidos/prendas-pedido/${window.modalReciboParcialState.prendaId}/editar-recibo`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            descripcion: descripcionEl ? getDescripcionParcialEditorValue() : null,
                            de_bodega: deBodegaEl ? (deBodegaEl.value === '1') : null,
                            pedido_id: window.modalReciboParcialState.pedidoId,
                            tipo_recibo: window.modalReciboParcialState.tipoProceso,
                        })
                    });

                    if (!prendaEditResponse.ok) {
                        const errorData = await prendaEditResponse.json();
                        throw new Error(errorData.message || `Error al editar prenda: HTTP ${prendaEditResponse.status}`);
                    }

                    const prendaEditResult = await prendaEditResponse.json();
                    if (!prendaEditResult.success) {
                        throw new Error(prendaEditResult.message || 'Error al editar prenda');
                    }

                    console.log('[Recibo Parcial] Prenda editada:', prendaEditResult);
                }
            }

            // Construir datos de las tallas seleccionadas
            const tallasSeleccionadas = [];
            Object.keys(tallasCantidad).forEach(tallaIndex => {
                const idx = parseInt(tallaIndex);
                const talla = window.modalReciboParcialState.tallas[idx];
                tallasSeleccionadas.push({
                    talla: talla.talla,
                    cantidad: tallasCantidad[idx],
                    genero: talla.genero,
                    color_nombre: talla.color || null
                });
            });

            const totalCantidad = tallasSeleccionadas.reduce((sum, item) => sum + (parseInt(item.cantidad) || 0), 0);
            let response;

            if (modoEntregaParcial) {
                const consecutivoReciboId = Number(window.modalReciboParcialState.consecutivoReciboId || 0);

                const payload = {
                    entregado: true,
                    modo: 'parcial',
                    cantidad_entregada: totalCantidad,
                    detalle_tallas: tallasSeleccionadas,
                };

                if (consecutivoReciboId > 0) {
                    payload.consecutivo_recibo_id = consecutivoReciboId;
                }

                console.log('[Entrega Parcial] Guardando:', payload);

                response = await fetch(`/api/prendas-entregas/${window.modalReciboParcialState.prendaId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
            } else {
                const payload = {
                    pedido_id: window.modalReciboParcialState.pedidoId,
                    prenda_id: window.modalReciboParcialState.prendaId,
                    tipo_proceso: window.modalReciboParcialState.tipoProceso,
                    tallas: tallasSeleccionadas,
                    ubicaciones: getUbicacionesAnexoValue(),
                    observaciones: getObservacionesAnexoValue()
                };

                console.log('[Recibo Parcial] Guardando:', payload);

                response = await fetch('/api/recibos-parciales', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
            }

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();

            if (result.success) {
                console.log(modoEntregaParcial ? '[Entrega Parcial] Guardada exitosamente:' : '[Recibo Parcial] Creado exitosamente:', result.data);
                mostrarMensajeExito(
                    modoEntregaParcial
                        ? (result.message || 'Entrega parcial registrada correctamente.')
                        : `Recibo ${result.data.tipo_recibo} parcial creado. Recarga el modal para verlo actualizado.`
                );
                const pedidoId = modoEntregaParcial
                    ? window.modalReciboParcialState.pedidoId
                    : result.data.pedido_id;
                cerrarModalReciboParcial();
                
                // Recargar datos del selector con el pedido_id de la respuesta
                cargarDatosRecibos(pedidoId);
            } else {
                throw new Error(result.message || 'Error al crear recibo parcial');
            }

        } catch (error) {
            console.error('[Recibo Parcial] Error:', error);
            document.getElementById('parcial-error').style.display = 'block';
            document.getElementById('parcial-error-message').textContent = error.message;
            content.style.display = 'block';
        } finally {
            btn.disabled = false;
            loading.style.display = 'none';
        }
    };

    /**
     * Cierra el modal de recibo parcial
     */
    window.cerrarModalReciboParcial = function() {
        const overlay = document.getElementById('modal-recibo-parcial-overlay');
        const modal = document.getElementById('modal-recibo-parcial');
        
        if (overlay) overlay.style.display = 'none';
        if (modal) modal.style.display = 'none';

        // Limpiar DOM (selección) para que no se arrastre al siguiente pedido
        const tallasListEl = document.getElementById('parcial-tallas-list');
        if (tallasListEl) tallasListEl.innerHTML = '';
        const cantidadesListEl = document.getElementById('parcial-cantidades-list');
        if (cantidadesListEl) cantidadesListEl.innerHTML = '';
        const totalEl = document.getElementById('parcial-total-cantidad');
        if (totalEl) totalEl.textContent = '0';
        const errorEl = document.getElementById('parcial-error');
        if (errorEl) errorEl.style.display = 'none';
        const errorMsgEl = document.getElementById('parcial-error-message');
        if (errorMsgEl) errorMsgEl.textContent = '';

        // Limpiar campos de edición de prenda
        const descripcionEl = document.getElementById('parcial-editar-descripcion');
        if (descripcionEl) setDescripcionParcialEditorValue('');
        const deBodegaEl = document.getElementById('parcial-editar-de-bodega');
        if (deBodegaEl) deBodegaEl.value = '1';
        const editarPrendaSection = document.getElementById('parcial-editar-prenda-section');
        if (editarPrendaSection) editarPrendaSection.style.display = 'none';
        const contenidoEl = document.getElementById('parcial-editar-contenido');
        if (contenidoEl) contenidoEl.style.display = 'none';
        const toggleIcon = document.getElementById('parcial-editar-toggle-icon');
        if (toggleIcon) toggleIcon.textContent = '▶';
        const editarProcesoSection = document.getElementById('parcial-editar-proceso-section');
        if (editarProcesoSection) editarProcesoSection.style.display = 'none';
        const contenidoProcesoEl = document.getElementById('parcial-editar-proceso-contenido');
        if (contenidoProcesoEl) contenidoProcesoEl.style.display = 'none';
        const toggleProcesoIcon = document.getElementById('parcial-proceso-toggle-icon');
        if (toggleProcesoIcon) toggleProcesoIcon.textContent = '▶';
        setUbicacionesAnexoValue([]);
        setObservacionesAnexoValue('');

        // Limpiar estado
        window.modalReciboParcialState = {
            prendaId: null,
            tipoProceso: null,
            prendaNombre: null,
            tallas: [],
            tallasCantidad: {},
            pedidoId: null,
            mode: 'crear_recibo',
            consecutivoReciboId: null,
            ubicacionesAnexo: [],
            observacionesAnexo: ''
        };
    };

    // Cerrar al presionar ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('modal-recibo-parcial').style.display === 'block') {
            cerrarModalReciboParcial();
        }
    });
</script>

<style>
    /* Estilos específicos para spinners */
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    #parcial-editar-descripcion:empty:before {
        content: attr(data-placeholder);
        color: #9ca3af;
    }

    #parcial-editar-descripcion:focus {
        outline: none;
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 0 2px rgba(139, 92, 246, 0.15);
    }
</style>
