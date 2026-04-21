/**
 * PedidosRecibosModule.js
 * Módulo principal para gestión de recibos dinámicos en pedidos
 * 
 * Integra: ModalManager, CloseButtonManager, NavigationManager, 
 *          GalleryManager, ReceiptRenderer, y utilidades
 */

import { ReceiptBuilder } from './utils/ReceiptBuilder.js';
import { ReceiptRenderer } from './components/ReceiptRenderer.js';
import { NavigationManager } from './components/NavigationManager.js';
import { GalleryManager } from './components/GalleryManager.js';
import { CloseButtonManager } from './components/CloseButtonManager.js';
import { ModalManager } from './components/ModalManager.js';
import { Formatters } from './utils/Formatters.js';

export class PedidosRecibosModule {
    constructor() {
        this.modalManager = new ModalManager();
        this.validaciones();
    }

    /**
     * Valida que existan los elementos necesarios en el DOM
     */
    validaciones() {
        const elementosCriticos = [
            'order-detail-modal-wrapper',
            'modal-overlay'
        ];

        const faltantes = elementosCriticos.filter(id => !document.getElementById(id));
        if (faltantes.length > 0) {

        }

        // Elementos opcionales - solo advertencia
        const elementosOpcionales = [
            'receipt-title',
            'cliente-value',
            'asesora-value'
        ];
        const opcionalesFaltantes = elementosOpcionales.filter(id => !document.getElementById(id));
        if (opcionalesFaltantes.length > 0) {

        }
    }

    /**
     * FUNCIÓN PRINCIPAL: Abre un recibo específico en el modal
     * 
     * @param {number} pedidoId - ID del pedido
     * @param {number} prendaId - ID de la prenda
     * @param {string} tipoRecibo - Tipo de recibo (STRING)
     * @param {number} prendaIndex - Índice de la prenda (opcional)
     */
    async abrirRecibo(pedidoId, prendaId, tipoRecibo, prendaIndex = null, options = {}) {
        // VALIDACIÓN: Bloquear COSTURA-BODEGA en supervisor-pedidos y registros
        const esSupervisorPedidos = window.location.pathname.includes('/supervisor-pedidos');
        const esRegistros = window.location.pathname.includes('/registros');
        if ((esSupervisorPedidos || esRegistros) && tipoRecibo === 'costura-bodega') {
            console.warn(' [PedidosRecibosModule] Se intentó abrir recibo COSTURA-BODEGA - BLOQUEADO');
            return;
        }
        
        // Validaciones
        if (typeof tipoRecibo !== 'string') {

            alert('Error: tipo de recibo debe ser texto');
            return;
        }

        if (typeof prendaId !== 'number') {

            alert('Error: ID de prenda debe ser número');
            return;
        }

        try {
            const targetConsecutivoOption = String(options?.targetConsecutivo ?? '').trim();
            const targetReciboIdOption = String(options?.targetReciboId ?? '').trim();
            const esVistaRecibosCostura = window.location.pathname.includes('/recibos-costura');
            // Resetear cualquier galería previa para evitar que quede pegada entre recibos
            GalleryManager.resetGaleria(this.modalManager);
            
            // Limpiar estado del modal para evitar caché entre recibos
            this.modalManager.limpiarEstado();

            // Actualizar estado con los nuevos datos
            this.modalManager.setState({
                pedidoId,
                prendaId,
                tipoProceso: tipoRecibo,
                prendaIndex,
                objetivoConsecutivo: options?.targetConsecutivo ?? null,
                objetivoReciboId: options?.targetReciboId ?? null
            });

            // Mostrar modal
            this.modalManager.abrirModal();

            // Determinar el endpoint según el contexto
            let endpoint;
            if (window.location.pathname.includes('/registros')) {
                // Contexto de registros
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else if (window.location.pathname.includes('insumos/materiales')) {
                // Contexto de insumos - usar endpoint de registros para evitar filtro de de_bodega
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else {
                // Contexto público (accesible para cualquier usuario autenticado)
                endpoint = `/pedidos-public/${pedidoId}/recibos-datos`;
            }

            console.log(' [PedidosRecibosModule] Endpoint seleccionado:', endpoint);
            console.log(' [PedidosRecibosModule] Parámetros recibidos:', {
                pedidoId,
                prendaId,
                tipoRecibo,
                prendaIndex,
                options
            });

            // Obtener datos del servidor y, en recibos-costura, el recibo objetivo exacto.
            const targetReciboPromise = (esVistaRecibosCostura && targetReciboIdOption !== '')
                ? fetch(`/recibos-costura/recibo/${encodeURIComponent(targetReciboIdOption)}`)
                    .then(async (resp) => {
                        if (!resp.ok) return null;
                        const payload = await resp.json();
                        return payload?.success ? (payload.recibo || null) : null;
                    })
                    .catch(() => null)
                : Promise.resolve(null);

            const [response, targetReciboData] = await Promise.all([
                fetch(endpoint),
                targetReciboPromise
            ]);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            // Si el recibo objetivo es parcial, usar siempre el flujo de parcial.
            // Ese flujo obtiene tallas desde pedidos_parciales_tallas.
            const esTargetParcial = Boolean(targetReciboData?.es_parcial);
            const targetPedidoParcialId = Number(targetReciboData?.pedido_parcial_id || 0);
            if (esVistaRecibosCostura && esTargetParcial && targetPedidoParcialId > 0) {
                const nombreAnexo = `${String(targetReciboData?.tipo_recibo || tipoRecibo || 'COSTURA').toUpperCase()} ANEXO`;
                console.log('[PedidosRecibosModule.abrirRecibo] Recibo parcial detectado, redirigiendo a abrirReciboParcial:', {
                    pedidoId,
                    prendaId,
                    tipoRecibo,
                    pedidoParcialId: targetPedidoParcialId
                });
                return this.abrirReciboParcial(
                    Number(pedidoId),
                    Number(prendaId),
                    String(targetReciboData?.tipo_recibo || tipoRecibo || 'COSTURA'),
                    targetPedidoParcialId,
                    nombreAnexo
                );
            }

            let datos = await response.json();            
            // Manejar respuesta envuelta en { success: true, data: {...} }
            if (datos.data && typeof datos.data === 'object') {
                datos = datos.data;
            }

            // Normalización: el backend puede enviar `proceso.tallas` como ARRAY de detalles
            // (con ubicaciones/observaciones por talla). El renderer/Formatters espera
            // `proceso.tallas_detalle` para pintar "UBICACIONES POR TALLA".
            if (datos && Array.isArray(datos.prendas)) {
                datos.prendas.forEach((prenda) => {
                    if (!prenda || !Array.isArray(prenda.procesos)) return;
                    prenda.procesos.forEach((proceso) => {
                        if (!proceso) return;

                        // Preferir ubicaciones_array (si ya viene decodificado del backend)
                        if (!proceso.ubicaciones && Array.isArray(proceso.ubicaciones_array)) {
                            proceso.ubicaciones = proceso.ubicaciones_array;
                        }

                        // Caso: `tallas` viene como array -> es el detalle por talla
                        if (Array.isArray(proceso.tallas)) {
                            // Alias para que Formatters lo tome
                            if (!Array.isArray(proceso.tallas_detalle) || proceso.tallas_detalle.length === 0) {
                                proceso.tallas_detalle = proceso.tallas;
                            }
                        }

                        // Caso: modo_tallas=general y viene observaciones_por_talla como objeto
                        // -> convertir a tallas_detalle para que Formatters pinte "OBSERVACIONES POR TALLA"
                        // IMPORTANTE: no pisar un tallas_detalle ya válido del backend.
                        const modo = String(proceso.modo_tallas || '').toLowerCase();
                        const tallasDetalleValido = Array.isArray(proceso.tallas_detalle) && proceso.tallas_detalle.length > 0;
                        if (modo === 'general' && !tallasDetalleValido && proceso.observaciones_por_talla && typeof proceso.observaciones_por_talla === 'object') {
                            const normalizarObs = (raw) => {
                                const s = String(raw ?? '').trim();
                                if (!s) return '';
                                return s;
                            };
                            const obtenerCantidadReal = (generoKey, tallaKey) => {
                                const tallasObj = proceso?.tallas;
                                if (!tallasObj || typeof tallasObj !== 'object') return 1;

                                const generoRaw =
                                    tallasObj[generoKey] ||
                                    tallasObj[String(generoKey || '').toUpperCase()] ||
                                    tallasObj[String(generoKey || '').toLowerCase()];
                                if (!generoRaw || typeof generoRaw !== 'object') return 1;

                                const cantidad =
                                    Number(generoRaw[tallaKey] || 0) ||
                                    Number(generoRaw[String(tallaKey || '').toUpperCase()] || 0) ||
                                    Number(generoRaw[String(tallaKey || '').toLowerCase()] || 0);

                                return Number.isFinite(cantidad) && cantidad > 0 ? cantidad : 1;
                            };

                            const out = [];
                            const mapGenero = {
                                dama: 'DAMA',
                                caballero: 'CABALLERO',
                                unisex: 'UNISEX'
                            };

                            Object.keys(mapGenero).forEach((k) => {
                                const grupo = proceso.observaciones_por_talla[k];
                                if (!grupo || typeof grupo !== 'object') return;
                                Object.keys(grupo).forEach((tallaKey) => {
                                    const obs = normalizarObs(grupo[tallaKey]);
                                    if (!obs) return;
                                    out.push({
                                        genero: mapGenero[k],
                                        talla: tallaKey,
                                        cantidad: obtenerCantidadReal(k, tallaKey),
                                        observaciones: obs
                                    });
                                });
                            });

                            if (out.length > 0) {
                                proceso.tallas_detalle = out;
                            }
                        }
                    });
                });
            }
            
            console.group('[PedidosRecibosModule.abrirRecibo] 📥 DATOS RECIBIDOS DEL ENDPOINT');
            console.log('Endpoint:', endpoint);
            console.log('Cliente:', datos.cliente);
            console.log('Asesor:', datos.asesor);
            console.log('Forma de pago:', datos.forma_de_pago);
            console.log('Número pedido:', datos.numero_pedido);
            console.log('Total prendas:', datos.prendas ? datos.prendas.length : 'UNDEFINED');
            console.log('IDs de prendas disponibles:', datos.prendas ? datos.prendas.map(p => p.id) : 'NONE');
            console.log('Buscando prenda_id:', prendaId);
            console.groupEnd();
                        
            this.modalManager.setState({ datosCompletos: datos });

            // Validar que existan prendas
            if (!datos.prendas || !Array.isArray(datos.prendas)) {
                throw new Error('No se encontraron prendas en los datos del pedido');
            }

            // Encontrar la prenda
            console.log(`[PedidosRecibosModule]  Buscando prenda con ID ${prendaId} entre ${datos.prendas.length} prendas`);
            console.log(`[PedidosRecibosModule]  IDs disponibles:`, datos.prendas.map(p => ({ id: p.id, nombre: p.nombre || p.nombre_prenda })));
            
            const prendaData = datos.prendas.find(p => p.id == prendaId);
            
            if (!prendaData) {
                console.error(`[PedidosRecibosModule]  Prenda ${prendaId} no encontrada`);
                console.error(`[PedidosRecibosModule]  Búsqueda realizada con:`, {
                    buscarPor: 'id',
                    valorBuscado: prendaId,
                    tipoDeComparacion: '== (flexible)',
                    prendasDisponibles: datos.prendas.map(p => ({
                        id: p.id,
                        tipoId: typeof p.id,
                        nombre: p.nombre || p.nombre_prenda
                    }))
                });
                throw new Error(`Prenda ${prendaId} no encontrada`);
            }
            
            console.log(`[PedidosRecibosModule]  Prenda encontrada:`, {
                id: prendaData.id,
                nombre: prendaData.nombre || prendaData.nombre_prenda,
                tieneRecibos: !!prendaData.recibos,
                totalRecibos: prendaData.recibos ? prendaData.recibos.length : 0
            });

            // Debug: Verificar si los recibos están llegando desde el backend
            console.log(' [PedidosRecibosModule] Prenda encontrada:', {
                prendaId,
                prendaData: prendaData,
                recibos: prendaData.recibos,
                tieneRecibos: !!prendaData.recibos
            });



            // Construir lista de recibos
            const recibos = ReceiptBuilder.construirListaRecibos(prendaData);
            const targetConsecutivo = targetConsecutivoOption !== ''
                ? targetConsecutivoOption
                : String(targetReciboData?.consecutivo_actual ?? '').trim();
            let reciboIndice = -1;

            // Priorizar consecutivo exacto cuando venga desde tabla de recibos-costura.
            if (targetConsecutivo !== '') {
                reciboIndice = recibos.findIndex((r) => String(r?.consecutivo_actual ?? '').trim() === targetConsecutivo);
                if (reciboIndice !== -1) {
                    console.log('[PedidosRecibosModule.abrirRecibo] Recibo seleccionado por consecutivo exacto:', {
                        targetConsecutivo,
                        indice: reciboIndice,
                        tipo: recibos[reciboIndice]?.tipo || recibos[reciboIndice]?.tipo_proceso
                    });
                }
            }

            if (reciboIndice === -1) {
                reciboIndice = ReceiptBuilder.encontrarReceibo(recibos, tipoRecibo);
            }

            // Fallback: permitir variaciones de COSTURA (incluye anexos) hacia el recibo base.
            if (reciboIndice === -1) {
                const tipoLower = String(tipoRecibo || '').toLowerCase().trim();
                const esCostura = tipoLower === 'costura' || tipoLower === 'costura-bodega' || /^costura([\s_-]+anexo(\s+\d+)?)?$/.test(tipoLower);

                if (esCostura) {
                    const candidatos = ['costura', 'costura-bodega'];
                    for (const candidato of candidatos) {
                        const idx = ReceiptBuilder.encontrarReceibo(recibos, candidato);
                        if (idx !== -1) {
                            console.warn('[PedidosRecibosModule.abrirRecibo] Fallback tipoRecibo:', {
                                solicitado: tipoRecibo,
                                usando: candidato
                            });
                            reciboIndice = idx;
                            tipoRecibo = candidato;
                            break;
                        }
                    }
                }
            }

            if (reciboIndice === -1) {
                throw new Error(`Recibo "${tipoRecibo}" no encontrado`);
            }

            // Detectar si es un parcial (independientemente de la vista)
            // Buscar en prendaData.recibos.parciales si hay un parcial para este tipo_recibo
            let esParcialDetectado = false;
            let pedidoParcialIdDetectado = 0;

            if (prendaData.recibos && Array.isArray(prendaData.recibos.parciales)) {
                const parcialMatch = prendaData.recibos.parciales.find(p =>
                    String(p.tipo_recibo || '').toUpperCase() === String(tipoRecibo || '').toUpperCase()
                );
                if (parcialMatch && parcialMatch.id) {
                    esParcialDetectado = true;
                    pedidoParcialIdDetectado = Number(parcialMatch.id || 0);
                    console.log('[PedidosRecibosModule.abrirRecibo] Parcial detectado en prendaData.recibos.parciales:', {
                        tipoRecibo,
                        parcialId: pedidoParcialIdDetectado,
                        parcial: parcialMatch
                    });
                }
            }

            if (esParcialDetectado && pedidoParcialIdDetectado > 0) {
                const nombreAnexo = `${String(tipoRecibo || 'PROCESO').toUpperCase()} ANEXO`;
                console.log('[PedidosRecibosModule.abrirRecibo] Recibo parcial detectado, redirigiendo a abrirReciboParcial:', {
                    pedidoId,
                    prendaId,
                    tipoRecibo,
                    pedidoParcialId: pedidoParcialIdDetectado
                });
                return this.abrirReciboParcial(
                    Number(pedidoId),
                    Number(prendaId),
                    String(tipoRecibo || 'COSTURA'),
                    pedidoParcialIdDetectado,
                    nombreAnexo
                );
            }

            // Si el endpoint de pedido trae solo el recibo activo por tipo, forzar la metadata
            // de la fila seleccionada en /recibos-costura para respetar el consecutivo clickeado.
            if (esVistaRecibosCostura && reciboIndice !== -1) {
                const reciboObjetivo = recibos[reciboIndice];
                const consecutivoObjetivo = targetConsecutivo || String(targetReciboData?.consecutivo_actual ?? '').trim();

                if (consecutivoObjetivo !== '') {
                    reciboObjetivo.consecutivo_actual = consecutivoObjetivo;
                    reciboObjetivo.numero_recibo = consecutivoObjetivo;
                }
                if (targetReciboData?.created_at) {
                    reciboObjetivo.created_at = targetReciboData.created_at;
                }
                if (targetReciboData?.tipo_recibo) {
                    reciboObjetivo.tipo_recibo = targetReciboData.tipo_recibo;
                }

                console.log('[PedidosRecibosModule.abrirRecibo] Meta objetivo aplicada para recibos-costura:', {
                    targetReciboId: targetReciboIdOption || null,
                    targetConsecutivo: consecutivoObjetivo || null,
                    consecutivoRenderizado: reciboObjetivo.consecutivo_actual
                });
            }

            this.modalManager.setState({
                procesosActuales: recibos,
                procesoActualIndice: reciboIndice
            });

            // Renderizar
            this._renderizarRecibo(prendaData, reciboIndice, tipoRecibo, datos, recibos);

            // Habilitación final
            this.modalManager.habilitarInteraccion();
            this.modalManager.configurarZIndex();

        } catch (err) {

            alert('Error al cargar el recibo: ' + err.message);
            this.modalManager.cerrarModal();
        }
    }

    /**
     * Abre un recibo parcial (anexo) reutilizando la misma pipeline de renderizado.
     * Sigue el mismo flujo que abrirRecibo() pero inyecta las tallas del parcial
     * en el objeto recibo ANTES de renderizar.
     *
     * @param {number} pedidoId    - ID del pedido de producción
     * @param {number} prendaId    - ID de la prenda
     * @param {string} tipoRecibo  - Tipo del proceso base (ej: "BORDADO")
     * @param {number} parcialId   - ID del recibo parcial (pedidos_parciales)
     * @param {string} nombreAnexo - Nombre para mostrar (ej: "BORDADO ANEXO 2")
     */
    async abrirReciboParcial(pedidoId, prendaId, tipoRecibo, parcialId, nombreAnexo) {
        // Validaciones
        if (typeof tipoRecibo !== 'string') {
            alert('Error: tipo de recibo debe ser texto');
            return;
        }
        if (typeof prendaId !== 'number') {
            alert('Error: ID de prenda debe ser número');
            return;
        }
        if (!parcialId) {
            alert('Error: ID de recibo parcial requerido');
            return;
        }

        try {
            // Resetear estado
            GalleryManager.resetGaleria(this.modalManager);
            this.modalManager.limpiarEstado();
            this.modalManager.setState({
                pedidoId,
                prendaId,
                tipoProceso: tipoRecibo,
                prendaIndex: null
            });

            // Mostrar modal
            this.modalManager.abrirModal();

            // Determinar endpoint de datos del pedido (misma lógica que abrirRecibo)
            let endpoint;
            if (window.location.pathname.includes('/registros')) {
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else if (window.location.pathname.includes('insumos/materiales')) {
                endpoint = `/registros/${pedidoId}/recibos-datos`;
            } else {
                endpoint = `/pedidos-public/${pedidoId}/recibos-datos`;
            }

            console.log('[PedidosRecibosModule.abrirReciboParcial] Cargando datos en paralelo:', {
                endpoint,
                parcialEndpoint: `/api/recibos-parciales/${parcialId}`
            });

            // Fetch en paralelo: datos del pedido + datos del parcial
            const [pedidoResponse, parcialResponse] = await Promise.all([
                fetch(endpoint),
                fetch(`/api/recibos-parciales/${parcialId}`)
            ]);

            if (!pedidoResponse.ok) throw new Error(`HTTP ${pedidoResponse.status} al cargar pedido`);
            if (!parcialResponse.ok) throw new Error(`HTTP ${parcialResponse.status} al cargar parcial`);

            let datos = await pedidoResponse.json();
            const parcialResult = await parcialResponse.json();

            if (!parcialResult.success) {
                throw new Error(parcialResult.message || 'Error al cargar recibo parcial');
            }

            // Desempaquetar datos del pedido
            if (datos.data && typeof datos.data === 'object') {
                datos = datos.data;
            }

            // Consecutivo real del ANEXO (viene del recibo parcial)
            const parcialData = parcialResult.data?.parcial || parcialResult.data || null;
            const consecutivoAnexo = parcialData?.consecutivo_actual ?? parcialData?.numero_recibo ?? null;

            // Para anexos: la fecha del recibo debe venir de pedidos_parciales.created_at
            // El renderer usa datosPedido.fecha para pintar los cuadros de fecha.
            if (parcialData && parcialData.created_at) {
                // Normalizar a solo fecha para evitar desfases por zona horaria (ej: 23:00 -> día siguiente)
                const createdAtStr = String(parcialData.created_at);
                // Soportar formatos: "YYYY-MM-DD HH:MM:SS" o ISO "YYYY-MM-DDTHH:MM:SS..."
                const soloFecha = createdAtStr.includes('T')
                    ? createdAtStr.split('T')[0]
                    : createdAtStr.substring(0, 10);

                datos.fecha = soloFecha;
            }

            this.modalManager.setState({ datosCompletos: datos });

            // Validar prendas
            if (!datos.prendas || !Array.isArray(datos.prendas)) {
                throw new Error('No se encontraron prendas en los datos del pedido');
            }

            // Encontrar la prenda
            const prendaData = datos.prendas.find(p => p.id == prendaId);
            if (!prendaData) {
                throw new Error(`Prenda ${prendaId} no encontrada`);
            }

            // Construir recibos normalmente
            const recibos = ReceiptBuilder.construirListaRecibos(prendaData);
            let reciboIndice = ReceiptBuilder.encontrarReceibo(recibos, tipoRecibo);

            // Fallback: anexos de COSTURA se guardan como "COSTURA" pero el recibo base
            // en prendas de bodega se construye como "costura-bodega".
            if (reciboIndice === -1) {
                const tipoLower = String(tipoRecibo || '').toLowerCase();
                const esCostura = tipoLower === 'costura' || tipoLower === 'costura-bodega';
                if (esCostura) {
                    const candidatos = ['costura'];
                    for (const candidato of candidatos) {
                        const idx = ReceiptBuilder.encontrarReceibo(recibos, candidato);
                        if (idx !== -1) {
                            console.warn('[PedidosRecibosModule.abrirReciboParcial] Fallback tipoRecibo:', {
                                solicitado: tipoRecibo,
                                usando: candidato
                            });
                            reciboIndice = idx;
                            tipoRecibo = candidato;
                            break;
                        }
                    }
                }
            }

            // Caso especial: en /recibos-costura ReceiptBuilder puede excluir el recibo base
            // (ej: costura-bodega cuando prenda.de_bodega == 1). Para anexos de costura
            // necesitamos un recibo objetivo para inyectar tallas y renderizar.
            if (reciboIndice === -1) {
                const tipoLower = String(tipoRecibo || '').toLowerCase();
                const esCostura = tipoLower === 'costura' || tipoLower === 'costura-bodega';
                const esVistaRecibosCostura = window.location.pathname.includes('/recibos-costura');
                if (esCostura && esVistaRecibosCostura) {
                    const tipoSintetico = 'costura';
                    console.warn('[PedidosRecibosModule.abrirReciboParcial] Recibo base no encontrado; creando recibo sintético para renderizar anexo', {
                        solicitado: tipoRecibo,
                        tipo_sintetico: tipoSintetico,
                        de_bodega: prendaData?.de_bodega
                    });

                    recibos.unshift({
                        tipo: tipoSintetico,
                        tipo_proceso: 'Costura',
                        nombre_proceso: 'Costura',
                        estado: 'Pendiente',
                        es_base: true,
                        ubicaciones: [],
                        observaciones: '',
                        imagenes: Array.isArray(prendaData?.imagenes) ? prendaData.imagenes : [],
                        tallas: prendaData?.tallas || {}
                    });

                    reciboIndice = 0;
                    tipoRecibo = tipoSintetico;
                }
            }

            // Fallback genérico para anexos: si el recibo base/proceso no existe en los
            // datos del pedido, crear uno sintético usando el tipo solicitado.
            // Esto cubre casos como REFLECTIVO ANEXO donde existe el parcial, pero la
            // prenda no trae un proceso reflectivo base en `prenda.procesos`.
            if (reciboIndice === -1) {
                const tipoSintetico = String(tipoRecibo || parcialData?.tipo_recibo || '')
                    .trim()
                    .toLowerCase();
                const nombreSintetico = String(
                    nombreAnexo ||
                    tipoRecibo ||
                    parcialData?.tipo_recibo ||
                    'Proceso'
                ).trim();
                const tiposSoportados = new Set([
                    'costura',
                    'costura-bodega',
                    'reflectivo',
                    'bordado',
                    'estampado',
                    'dtf',
                    'sublimado'
                ]);

                if (tipoSintetico && tiposSoportados.has(tipoSintetico)) {
                    console.warn('[PedidosRecibosModule.abrirReciboParcial] Recibo base no encontrado; creando recibo sintético para renderizar anexo', {
                        solicitado: tipoRecibo,
                        tipo_sintetico: tipoSintetico,
                        nombre_sintetico: nombreSintetico,
                        parcial_id: parcialId
                    });

                    const imagenesSinteticas = [];
                    if (Array.isArray(prendaData?.imagenes)) {
                        imagenesSinteticas.push(...prendaData.imagenes);
                    }
                    if (Array.isArray(prendaData?.imagenes_tela)) {
                        imagenesSinteticas.push(...prendaData.imagenes_tela);
                    }

                    recibos.unshift({
                        tipo: tipoSintetico,
                        tipo_proceso: nombreSintetico,
                        nombre_proceso: nombreSintetico,
                        estado: parcialData?.estado || 'Pendiente',
                        es_base: false,
                        es_parcial: true,
                        origen: 'PARCIAL',
                        ubicaciones: [],
                        observaciones: '',
                        imagenes: imagenesSinteticas,
                        tallas: prendaData?.tallas || {},
                        activo: parcialData?.estado === 'APROBADO' ? 1 : 0,
                        tipo_recibo: String(parcialData?.tipo_recibo || tipoSintetico).toUpperCase(),
                        numero_recibo: consecutivoAnexo,
                        consecutivo_actual: consecutivoAnexo,
                        created_at: parcialData?.created_at || null,
                        fecha_activacion: parcialData?.fecha_activacion || null
                    });

                    reciboIndice = 0;
                    tipoRecibo = tipoSintetico;
                }
            }

            if (reciboIndice === -1) {
                throw new Error(`Recibo "${tipoRecibo}" no encontrado`);
            }

            // === INYECCIÓN DE TALLAS DEL PARCIAL (antes de renderizar) ===
            const recibo = recibos[reciboIndice];
            const tallasFormato = parcialResult.data.tallas_formato;
            const tallasFormatoColores = parcialResult.data.tallas_formato_colores;
            const tallasArrayParcial = parcialResult.data.tallas;
            const tallasDetalleParcial = parcialResult.data.tallas_detalle;

            console.log('[PedidosRecibosModule.abrirReciboParcial] Inyectando tallas del parcial:', {
                tallasOriginales: recibo.tallas,
                tallasDelParcial: tallasFormato,
                nombreAnexo
            });

            // Sobrescribir tallas del recibo con las del parcial
            recibo.tallas = (tallasFormatoColores && Object.keys(tallasFormatoColores).length > 0)
                ? tallasFormatoColores
                : tallasFormato;

            // Guardar originals para restaurar después (antes de filtrar)
            const tallaColoresOriginal = prendaData.talla_colores;
            const tallasOriginal = prendaData.tallas;

            // Filtrar tallas del parcial para que el renderer solo muestre las del parcial
            // Esto asegura que no muestre todas las tallas de la prenda
            const tallasParcialSet = new Set();
            if (Array.isArray(tallasArrayParcial) && tallasArrayParcial.length > 0) {
                tallasArrayParcial.forEach(t => {
                    const genero = String(t.genero || 'CABALLERO').toUpperCase();
                    const talla = String(t.talla || '').toUpperCase();
                    tallasParcialSet.add(`${genero}|${talla}`);
                });
                recibo.talla_colores = tallasArrayParcial;
            } else {
                delete recibo.talla_colores;
            }

            // Filtrar talla_colores de prendaData si existen
            if (prendaData.talla_colores && Array.isArray(prendaData.talla_colores)) {
                prendaData.talla_colores = prendaData.talla_colores.filter(tc => {
                    const genero = String(tc.genero || 'CABALLERO').toUpperCase();
                    const talla = String(tc.talla || '').toUpperCase();
                    return tallasParcialSet.has(`${genero}|${talla}`);
                });
            }

            // Filtrar prendaData.tallas (objeto con estructura {DAMA: {...}, CABALLERO: {...}, UNISEX: {...}})
            // para solo incluir tallas del parcial
            if (prendaData.tallas && typeof prendaData.tallas === 'object') {
                const tallasFiltradas = {};
                Object.keys(prendaData.tallas).forEach(genero => {
                    const tallasDelGenero = prendaData.tallas[genero];
                    tallasFiltradas[genero] = Array.isArray(tallasDelGenero)
                        ? tallasDelGenero.filter(t => tallasParcialSet.has(`${genero}|${String(t.talla || '').toUpperCase()}`))
                        : typeof tallasDelGenero === 'object'
                            ? Object.fromEntries(
                                Object.entries(tallasDelGenero).filter(([tallaKey]) =>
                                    tallasParcialSet.has(`${genero}|${String(tallaKey).toUpperCase()}`)
                                )
                            )
                            : tallasDelGenero;
                });
                prendaData.tallas = tallasFiltradas;
            }

            // Marcar como parcial para que el renderer sepa limpiar consecutivo
            recibo._esParcial = true;
            recibo._nombreAnexo = nombreAnexo || tipoRecibo;
            // Asegurar consecutivo del anexo como fuente de verdad para el renderer
            if (consecutivoAnexo) {
                recibo.numero_recibo = consecutivoAnexo;
            }

            // Inyectar fecha_activacion del parcial (si existe)
            recibo.fecha_activacion = parcialData?.fecha_activacion || null;

            // Inyectar detalles por talla (observaciones/ubicaciones) del anexo
            // para que Formatters pinte OBSERVACIONES/UBICACIONES POR TALLA solo de las tallas anexadas.
            if (Array.isArray(tallasDetalleParcial) && tallasDetalleParcial.length > 0) {
                recibo.tallas_detalle = tallasDetalleParcial;
            } else {
                delete recibo.tallas_detalle;
            }

            this.modalManager.setState({
                procesosActuales: recibos,
                procesoActualIndice: reciboIndice
            });

            // Renderizar con la pipeline normal (tallas ya están inyectadas)
            this._renderizarRecibo(prendaData, reciboIndice, tipoRecibo, datos, recibos);

            // Restaurar originales para no mutar el estado permanentemente
            prendaData.talla_colores = tallaColoresOriginal;
            prendaData.tallas = tallasOriginal;

            // Post-renderizado: ajustar título y consecutivo para el anexo
            const titleEl = document.querySelector('.receipt-title');
            if (titleEl) {
                const tipoReciboLower = String(tipoRecibo || '').toLowerCase();
                const tituloTipo = tipoReciboLower.includes('costura') ? 'COSTURA' : String(tipoRecibo || '').toUpperCase();
                titleEl.textContent = 'RECIBO DE ' + tituloTipo;
            }

            const pedidoNumberEl = document.querySelector('#order-pedido') || document.querySelector('.pedido-number');
            if (pedidoNumberEl) {
                pedidoNumberEl.textContent = consecutivoAnexo ? ('#' + consecutivoAnexo) : '#-';
            }

            console.log('[PedidosRecibosModule.abrirReciboParcial] ✓ Renderizado completo con tallas del parcial');

            // Habilitación final
            this.modalManager.habilitarInteraccion();
            this.modalManager.configurarZIndex();

        } catch (err) {
            console.error('[PedidosRecibosModule.abrirReciboParcial] Error:', err);
            alert('Error al cargar el recibo parcial: ' + err.message);
            this.modalManager.cerrarModal();
        }
    }

    /**
     * Renderiza el recibo y configura componentes
     */
    _renderizarRecibo(prendaData, reciboIndice, tipoProceso, datosPedido, recibos) {
        // Mantener prendaData y recibos actuales en el estado para impresión/navegación.
        // (printReceiptModal lee prendaData desde el estado)
        this.modalManager.setState({
            prendaData,
            procesosActuales: recibos,
            procesoActualIndice: reciboIndice,
            tipoProceso
        });

        // Crear botón X
        CloseButtonManager.crearBotonCierre(this.modalManager);

        // Renderizar contenido
        ReceiptRenderer.renderizar(
            this.modalManager,
            prendaData,
            reciboIndice,
            tipoProceso,
            datosPedido,
            recibos
        );

        // Configurar navegación
        NavigationManager.configurarFlechas(
            this.modalManager,
            prendaData,
            (prendaData, indice, tipo) => this._onProcesoCambiado(prendaData, indice, tipo, datosPedido, recibos)
        );
    }

    /**
     * Callback cuando cambia de proceso vía navegación
     */
    _onProcesoCambiado(prendaData, nuevoIndice, tipoRecibo, datosPedido, recibos) {
        // Renderizar nuevo recibo
        ReceiptRenderer.renderizar(
            this.modalManager,
            prendaData,
            nuevoIndice,
            tipoRecibo,
            datosPedido,
            recibos
        );

        // Actualizar flechas
        NavigationManager.actualizarVisibilidad(this.modalManager);

        // Cerrar galería si estaba abierta
        const galeria = document.getElementById('galeria-modal-costura');
        if (galeria && galeria.style.display !== 'none') {
            GalleryManager.cerrarGaleria();
        }
    }

    /**
     * Abre la galería de imágenes
     */
    async abrirGaleria() {
        const mostroGaleria = await GalleryManager.abrirGaleria(this.modalManager);
        if (mostroGaleria) {
            GalleryManager.actualizarBotonesEstilo(true);
        } else {
            // Usar galería original solo si existe y evitando recursión
            if (window.toggleGaleria && window.originalToggleGaleria) {
                return window.originalToggleGaleria.call(this);
            }
        }
    }

    /**
     * Cierra la galería
     */
    cerrarGaleria() {
        GalleryManager.cerrarGaleria();
    }

    /**
     * Cierra el recibo/modal
     */
    cerrarRecibo() {
        CloseButtonManager.forzarCierre(this.modalManager);
    }

    /**
     * Obtiene el estado actual del modal
     */
    getEstado() {
        return this.modalManager.getState();
    }
}

// Instancia global singleton
window.pedidosRecibosModule = new PedidosRecibosModule();

/**
 * FUNCIÓN GLOBAL compatibilidad con código antiguo
 * Mantiene la API antigua mientras usa el nuevo módulo
 */
window.openOrderDetailModalWithProcess = async function(
    pedidoId,
    prendaId,
    tipoRecibo,
    prendaIndex = null,
    targetConsecutivo = null,
    targetReciboId = null
) {
    return window.pedidosRecibosModule.abrirRecibo(
        pedidoId,
        prendaId,
        tipoRecibo,
        prendaIndex,
        {
            targetConsecutivo,
            targetReciboId
        }
    );
};

 if (typeof window.printReceiptModal !== 'function') {
     window.printReceiptModal = function() {
         const wrapper = document.getElementById('order-detail-modal-wrapper');
         if (!wrapper) {
             window.print();
             return;
         }

        // Nuevo flujo: imprimir usando el diseño/paginación del ejemplo "sistema-de-recibos-mundo-industrial".
        // Se genera un HTML limpio para impresión (A4), sin depender del layout actual del modal.
        try {
            const estado = window.pedidosRecibosModule && typeof window.pedidosRecibosModule.getEstado === 'function'
                ? window.pedidosRecibosModule.getEstado()
                : null;

            console.log('[printReceiptModal]  ESTADO COMPLETO:', {
                estado,
                'estado.datosCompletos': estado?.datosCompletos,
                'estado.prendaData': estado?.prendaData,
                'estado.procesosActuales': estado?.procesosActuales,
                'estado.procesoActualIndice': estado?.procesoActualIndice,
                'estado.tipoProceso': estado?.tipoProceso
            });

            const datosPedido = estado && estado.datosCompletos ? estado.datosCompletos : {};
            const prendaData = estado && estado.prendaData ? estado.prendaData : null;
            const recibos = estado && Array.isArray(estado.procesosActuales) ? estado.procesosActuales : [];
            const reciboActual = (recibos && typeof estado?.procesoActualIndice === 'number') ? recibos[estado.procesoActualIndice] : null;
            const tipoProceso = estado && estado.tipoProceso ? estado.tipoProceso : (reciboActual?.tipo || reciboActual?.tipo_proceso || '');

            console.log('[printReceiptModal]  DATOS EXTRAÍDOS:', {
                datosPedido,
                prendaData,
                recibos,
                reciboActual,
                tipoProceso
            });

            // Fallback DOM: si por algún motivo el estado no trae datos (se ve en COSTURA en algunas vistas),
            // leer lo que ya está pintado en el modal.
            const getDomText = (sel) => {
                try {
                    const el = wrapper.querySelector(sel);
                    if (!el) return '';
                    return String(el.textContent || '').trim();
                } catch (_) {
                    return '';
                }
            };

            const getDomHtmlText = (sel) => {
                try {
                    const el = wrapper.querySelector(sel);
                    if (!el) return '';
                    return String(el.innerText || el.textContent || '').trim();
                } catch (_) {
                    return '';
                }
            };

            const esc = (v) => {
                const s = String(v ?? '');
                return s
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const normalizarLista = (raw) => {
                if (!raw) return [];
                if (Array.isArray(raw)) return raw.map(x => String(x)).filter(Boolean);
                if (typeof raw === 'string') {
                    // intentar JSON primero
                    const s = raw.trim();
                    if ((s.startsWith('[') && s.endsWith(']')) || (s.startsWith('{') && s.endsWith('}'))) {
                        try {
                            const parsed = JSON.parse(s);
                            if (Array.isArray(parsed)) return parsed.map(x => String(x)).filter(Boolean);
                        } catch (_) {}
                    }
                    return s.split(/\r?\n|\s*\|\s*|\s*,\s*/g).map(x => x.trim()).filter(Boolean);
                }
                return [];
            };

            const buildTallasResumen = (tallas, tallaColores = null) => {
                const normalizarGenero = (g) => String(g || '').trim().toUpperCase();
                const normalizarTalla = (t) => String(t || '').trim().toUpperCase();
                const normalizarColor = (c) => {
                    const s = String(c || '').trim().toUpperCase();
                    return s || 'SIN COLOR';
                };

                const formatGeneroGroup = (mapGeneroATallas) => {
                    // mapGeneroATallas: { CABALLERO: Map(talla->cantidad), DAMA: ... }
                    const partes = [];
                    Object.keys(mapGeneroATallas).forEach((gen) => {
                        const m = mapGeneroATallas[gen];
                        if (!m) return;
                        const items = [];
                        for (const [tallaKey, cant] of m.entries()) {
                            const n = Number(cant || 0);
                            if (!tallaKey || !n) continue;
                            items.push(`${tallaKey}: ${n}`);
                        }
                        if (items.length > 0) {
                            partes.push(`${gen}: ${items.join(', ')}`);
                        }
                    });
                    return partes.join(' | ');
                };

                // === 1) Prioridad: tallas por color (array estilo backend: [{genero,talla,color_nombre,cantidad}, ...]) ===
                const coloresArr = Array.isArray(tallaColores) ? tallaColores : null;
                if (coloresArr && coloresArr.length > 0) {
                    // Agrupar por color -> género -> talla
                    const byColor = new Map();
                    coloresArr.forEach((row) => {
                        const genero = normalizarGenero(row?.genero);
                        const talla = normalizarTalla(row?.talla);
                        const color = normalizarColor(row?.color_nombre || row?.color);
                        const cantidad = Number(row?.cantidad || 0);
                        if (!genero || !talla || !cantidad) return;

                        if (!byColor.has(color)) byColor.set(color, new Map());
                        const byGenero = byColor.get(color);
                        if (!byGenero.has(genero)) byGenero.set(genero, new Map());
                        const byTalla = byGenero.get(genero);
                        byTalla.set(talla, (Number(byTalla.get(talla) || 0) + cantidad));
                    });

                    const partesColor = [];
                    for (const [color, byGenero] of byColor.entries()) {
                        const mapGeneroATallas = {};
                        for (const [gen, byTalla] of byGenero.entries()) {
                            mapGeneroATallas[gen] = byTalla;
                        }
                        const strGenero = formatGeneroGroup(mapGeneroATallas);
                        if (strGenero) {
                            partesColor.push(`${color}: ${strGenero}`);
                        }
                    }
                    return partesColor.join(' | ');
                }

                // === 2) Array simple: [{genero,talla,cantidad}] (agrupar por género sin repetir) ===
                if (Array.isArray(tallas)) {
                    const mapGeneroATallas = {};
                    const mapSobremedida = {}; // Separar sobremedida por género
                    
                    tallas.forEach((t) => {
                        const genero = normalizarGenero(t?.genero);
                        const cantidad = Number(t?.cantidad || 0);
                        if (!genero || !cantidad) return;
                        
                        // Si es sobremedida, manejarlo por separado
                        if (t?.es_sobremedida) {
                            if (!mapSobremedida[genero]) mapSobremedida[genero] = 0;
                            mapSobremedida[genero] += cantidad;
                        } else {
                            const talla = normalizarTalla(t?.talla);
                            if (!talla) return;
                            if (!mapGeneroATallas[genero]) mapGeneroATallas[genero] = new Map();
                            const m = mapGeneroATallas[genero];
                            m.set(talla, (Number(m.get(talla) || 0) + cantidad));
                        }
                    });
                    
                    const generosSobremedida = Object.keys(mapSobremedida);
                    const tieneSobremedida = generosSobremedida.length > 0;
                    const tieneTallasNormales = Object.keys(mapGeneroATallas).length > 0;
                    
                    // Si solo hay sobremedida, devolver formato especial sin "TALLAS:"
                    if (tieneSobremedida && !tieneTallasNormales) {
                        const partesSobremedida = generosSobremedida.map(g => `${g}: ${mapSobremedida[g]}`);
                        return 'SOBREMEDIDA_SIN_TALLAS:' + partesSobremedida.join('|');
                    }
                    
                    const partes = [];
                    
                    // Primero agregar sobremedida si existe
                    if (tieneSobremedida) {
                        const partesSobremedida = generosSobremedida.map(g => `${g}: ${mapSobremedida[g]}`);
                        partes.push('SOBREMEDIDA_CON_TALLAS:' + partesSobremedida.join('|'));
                    }
                    
                    // Luego agregar tallas normales
                    const tallasNormalesStr = formatGeneroGroup(mapGeneroATallas);
                    if (tallasNormalesStr) {
                        partes.push(tallasNormalesStr);
                    }
                    
                    return partes.join('\n');
                }

                // === 3) Objeto: {GENERO: {talla: cantidad}} o {GENERO: {talla: [{color,cantidad}]}} ===
                if (tallas && typeof tallas === 'object') {
                    // Si detectamos arrays con colores, agrupar por color igual
                    let tieneColores = false;
                    for (const genKey of Object.keys(tallas)) {
                        const v = tallas[genKey];
                        if (!v || typeof v !== 'object') continue;
                        for (const tallaKey of Object.keys(v)) {
                            const cell = v[tallaKey];
                            if (Array.isArray(cell) && cell.length > 0 && cell[0] && (cell[0].color || cell[0].color_nombre)) {
                                tieneColores = true;
                                break;
                            }
                        }
                        if (tieneColores) break;
                    }

                    if (tieneColores) {
                        const byColor = new Map();
                        Object.keys(tallas).forEach((genKey) => {
                            const genero = normalizarGenero(genKey);
                            const v = tallas[genKey];
                            if (!v || typeof v !== 'object') return;
                            Object.keys(v).forEach((tallaKey) => {
                                const talla = normalizarTalla(tallaKey);
                                const cell = v[tallaKey];
                                if (!Array.isArray(cell)) return;
                                cell.forEach((item) => {
                                    const color = normalizarColor(item?.color || item?.color_nombre);
                                    const cantidad = Number(item?.cantidad || 0);
                                    if (!cantidad) return;
                                    if (!byColor.has(color)) byColor.set(color, new Map());
                                    const byGenero = byColor.get(color);
                                    if (!byGenero.has(genero)) byGenero.set(genero, new Map());
                                    const byTalla = byGenero.get(genero);
                                    byTalla.set(talla, (Number(byTalla.get(talla) || 0) + cantidad));
                                });
                            });
                        });

                        const partesColor = [];
                        for (const [color, byGenero] of byColor.entries()) {
                            const mapGeneroATallas = {};
                            for (const [gen, byTalla] of byGenero.entries()) {
                                mapGeneroATallas[gen] = byTalla;
                            }
                            const strGenero = formatGeneroGroup(mapGeneroATallas);
                            if (strGenero) {
                                partesColor.push(`${color}: ${strGenero}`);
                            }
                        }
                        return partesColor.join(' | ');
                    }

                    const mapGeneroATallas = {};
                    Object.keys(tallas).forEach((generoKey) => {
                        const genero = normalizarGenero(generoKey);
                        const tallasGenero = tallas[generoKey];
                        if (!tallasGenero || typeof tallasGenero !== 'object') return;
                        if (!mapGeneroATallas[genero]) mapGeneroATallas[genero] = new Map();
                        const m = mapGeneroATallas[genero];
                        Object.keys(tallasGenero).forEach((tallaKey) => {
                            const cant = Number(tallasGenero[tallaKey] || 0);
                            if (!cant) return;
                            const talla = normalizarTalla(tallaKey);
                            m.set(talla, (Number(m.get(talla) || 0) + cant));
                        });
                    });
                    return formatGeneroGroup(mapGeneroATallas);
                }

                return '';
            };

            const buildObservacionesPorTalla = () => {
                // Preferir `tallas_detalle` si existe en el recibo/proceso.
                const detalle = reciboActual?.tallas_detalle || reciboActual?.tallasDetalle || null;
                if (!Array.isArray(detalle) || detalle.length === 0) return [];

                return detalle.map((d) => {
                    // Si es sobremedida, usar "SOBREMEDIDA" como talla
                    const talla = d?.es_sobremedida ? 'SOBREMEDIDA' : String(d?.talla || d?.nombre_talla || '').trim();
                    const genero = String(d?.genero || '').trim();

                    // Observaciones/ubicaciones: soportar múltiples formatos
                    let obs = [];
                    if (Array.isArray(d?.observaciones)) obs = d.observaciones;
                    else if (typeof d?.observaciones === 'string') obs = normalizarLista(d.observaciones);
                    else if (Array.isArray(d?.ubicaciones)) obs = d.ubicaciones;
                    else if (typeof d?.ubicaciones === 'string') obs = normalizarLista(d.ubicaciones);

                    obs = obs.map(x => String(x).trim()).filter(Boolean);
                    return {
                        genero: genero ? genero.toUpperCase() : '',
                        talla: talla ? talla.toUpperCase() : '-',
                        observaciones: obs
                    };
                }).filter(x => x.observaciones && x.observaciones.length > 0);
            };

            const fechaDOM = (() => {
                const d = getDomText('.day-box');
                const m = getDomText('.month-box');
                const y = getDomText('.year-box');
                if (d && m && y) return `${d}/${m}/${y}`;
                return '';
            })();
            const fecha = String(datosPedido.fecha || fechaDOM || '').trim();
            const asesor = String(datosPedido.asesor || datosPedido.asesora || getDomText('#asesora-value') || '').trim();
            const formaPago = String(datosPedido.forma_de_pago || getDomText('#forma-pago-value') || '').trim();
            const cliente = String(datosPedido.cliente || getDomText('#cliente-value') || '').trim();

            const descripcionDOM = getDomHtmlText('#descripcion-text');

            // Intentar extraer nombre/color/tallas desde el texto del modal si prendaData no está.
            const extraerDesdeDescripcion = (raw) => {
                const out = { prendaNombre: '', prendaColor: '', tallas: '' };
                const s = String(raw || '').replace(/\r/g, '').trim();
                if (!s) return out;
                const mPrenda = s.match(/PR\s*ENDA\s*\d+\s*:\s*([^\n]+)/i) || s.match(/PRENDA\s*\d+\s*:\s*([^\n]+)/i);
                if (mPrenda && mPrenda[1]) out.prendaNombre = String(mPrenda[1]).trim();
                const mTallas = s.match(/TALLAS\s*:\s*([^\n]+)/i);
                if (mTallas && mTallas[1]) out.tallas = String(mTallas[1]).trim();
                // Color: algunas vistas lo imprimen como "COLOR:" o como segunda línea fuerte.
                const mColor = s.match(/COLOR\s*:\s*([^\n]+)/i);
                if (mColor && mColor[1]) out.prendaColor = String(mColor[1]).trim();
                return out;
            };

            const extra = extraerDesdeDescripcion(descripcionDOM);

            const prendaNombre = String(prendaData?.nombre || prendaData?.nombre_prenda || extra.prendaNombre || '').trim();
            const prendaColor = String(prendaData?.color || extra.prendaColor || '').trim();
            const prendaDescripcion = String(prendaData?.descripcion || '').trim();

            // COSTURA y algunos recibos base no traen `ubicaciones` en el objeto recibo.
            // Para COSTURA: usar solo la descripción limpia de la prenda, sin repetir campos ya mostrados
            const ubicaciones = (() => {
                if (tipoProceso && tipoProceso.toUpperCase() === 'COSTURA') {
                    // Para COSTURA: solo la descripción principal de la prenda
                    const desc = prendaData?.descripcion || '';
                    return desc ? [desc] : [];
                }
                
                // Para otros tipos: mantener lógica original
                const raw = (
                    reciboActual?.ubicaciones ||
                    reciboActual?.ubicaciones_array ||
                    reciboActual?.observaciones ||
                    prendaData?.descripcion
                );

                // Si no hay nada en data/estado, usar lo que se ve en el modal como bloque único.
                if ((!raw || (Array.isArray(raw) && raw.length === 0)) && descripcionDOM) {
                    return [descripcionDOM];
                }

                return normalizarLista(raw);
            })();

            const tallasResumen = (() => {
                // Para procesos, priorizar tallas_detalle que viene desde la BD
                let tallasProceso = null;
                console.log('[printReceiptModal]  DEBUG - Datos de tallas disponibles:', {
                    tipoProceso,
                    reciboActual,
                    prendaData,
                    'reciboActual.tallas_detalle': reciboActual?.tallas_detalle,
                    'reciboActual.tallas': reciboActual?.tallas,
                    'prendaData.tallas': prendaData?.tallas,
                    'ES COSTURA': tipoProceso?.toUpperCase() === 'COSTURA',
                    'variantes': reciboActual?.variantes || prendaData?.variantes
                });
                
                if (reciboActual) {
                    // Para cualquier tipo de recibo (incluyendo COSTURA), usar tallas_detalle si está disponible
                    if (Array.isArray(reciboActual.tallas_detalle) && reciboActual.tallas_detalle.length > 0) {
                        tallasProceso = reciboActual.tallas_detalle;
                        console.log('[printReceiptModal]  Usando tallas_detalle del reciboActual:', tallasProceso);
                    } else if (tipoProceso.toUpperCase() === 'COSTURA') {
                        // Para COSTURA: usar variantes si no hay tallas_detalle
                        const variantes = reciboActual?.variantes || prendaData?.variantes || [];
                        console.log('[printReceiptModal]  COSTURA - Variantes encontradas:', variantes);
                        
                        if (variantes.length > 0) {
                            // Convertir variantes al formato tallas_detalle
                            tallasProceso = variantes.map(v => ({
                                genero: v.genero || '',
                                talla: v.talla || '',
                                cantidad: v.cantidad || 0,
                                es_sobremedida: v.es_sobremedida || false,
                                observaciones: v.observaciones || []
                            }));
                            console.log('[printReceiptModal]  COSTURA - Variantes convertidas a tallas_detalle:', tallasProceso);
                        }
                    } else if (prendaData?.procesos && Array.isArray(prendaData.procesos)) {
                        // Buscar el proceso actual en prendaData.procesos por tipo
                        const procesoActual = prendaData.procesos.find(p => 
                            (p.tipo_proceso || '').toUpperCase() === tipoProceso.toUpperCase() ||
                            (p.nombre_proceso || '').toUpperCase() === tipoProceso.toUpperCase() ||
                            (p.tipo || '').toUpperCase() === tipoProceso.toUpperCase()
                        );
                        
                        if (procesoActual && Array.isArray(procesoActual.tallas_detalle) && procesoActual.tallas_detalle.length > 0) {
                            tallasProceso = procesoActual.tallas_detalle;
                            console.log('[printReceiptModal]  Usando tallas_detalle del proceso encontrado en prendaData:', tallasProceso);
                        } else if (procesoActual && procesoActual.tallas_detalle) {
                            console.log('[printReceiptModal]  procesoActual.tallas_detalle existe pero no es array válido:', procesoActual.tallas_detalle);
                        }
                        
                        // Si no hay tallas_detalle, intentar con tallas del proceso
                        if (!tallasProceso && procesoActual?.tallas) {
                            tallasProceso = procesoActual.tallas;
                            console.log('[printReceiptModal]  Usando tallas del proceso encontrado en prendaData:', tallasProceso);
                        }
                    }
                }
                
                // Solución de raíz:
                // - Resumen de TALLAS debe salir de `tallas` (totales por talla).
                // - `tallas_detalle` se reserva para la sección de OBSERVACIONES POR TALLA.
                // Así evitamos contaminar el resumen cuando el detalle viene unitario.
                const fuenteTallasResumen = reciboActual?.tallas || prendaData?.tallas || tallasProceso || null;

                const str = buildTallasResumen(
                    fuenteTallasResumen,
                    reciboActual?.talla_colores || prendaData?.talla_colores || null
                );
                console.log('[printReceiptModal]Resultado buildTallasResumen:', str);
                if (str) return str;
                
                // Fallback para COSTURA: intentar desde variantes
                if (tipoProceso && tipoProceso.toUpperCase() === 'COSTURA') {
                    const variantes = reciboActual?.variantes || prendaData?.variantes || [];
                    if (Array.isArray(variantes) && variantes.length > 0) {
                        const tallasMap = new Map();
                        variantes.forEach(v => {
                            const talla = v?.talla || '';
                            const genero = v?.genero || '';
                            const cantidad = Number(v?.cantidad || 0);
                            if (talla && cantidad > 0) {
                                const key = genero ? `${genero.toUpperCase()}:${talla.toUpperCase()}` : talla.toUpperCase();
                                tallasMap.set(key, (tallasMap.get(key) || 0) + cantidad);
                            }
                        });
                        
                        if (tallasMap.size > 0) {
                            const generosMap = new Map();
                            tallasMap.forEach((cantidad, key) => {
                                const [genero, talla] = key.includes(':') ? key.split(':') : ['', key];
                                if (!generosMap.has(genero)) generosMap.set(genero, new Map());
                                generosMap.get(genero).set(talla, cantidad);
                            });
                            
                            const partes = [];
                            generosMap.forEach((tallasGen, genero) => {
                                const tallasStr = Array.from(tallasGen.entries())
                                    .map(([t, c]) => `${t}-${c}`)
                                    .join(' ');
                                partes.push(genero ? `${genero}: ${tallasStr}` : tallasStr);
                            });
                            return partes.join(' | ');
                        }
                    }
                }
                
                return String(extra.tallas || '').trim();
            })();
            const observacionesPorTalla = buildObservacionesPorTalla();

            console.log('[printReceiptModal] 🏁 VALORES FINALES PARA HTML:', {
                tallasResumen,
                observacionesPorTalla,
                'tallasResumen tipo': typeof tallasResumen,
                'tallasResumen longitud': tallasResumen?.length,
                'tallasResumen vacío': !tallasResumen,
                'observacionesPorTalla longitud': observacionesPorTalla?.length
            });

            const receiptTitleEl = wrapper.querySelector('#receipt-title');
            const receiptTitle = receiptTitleEl ? receiptTitleEl.textContent.trim() : '';
            const titulo = receiptTitle || ('RECIBO DE ' + String(tipoProceso || '').toUpperCase());

            // Consecutivo real del recibo actual (fuente de verdad para impresión)
            // 1) Preferir el dato estructurado del estado (reciboActual.numero_recibo)
            // 2) Fallback: leer lo que ya está pintado en el modal (#order-pedido)
            const numeroReciboActual = reciboActual?.numero_recibo ?? reciboActual?.numeroRecibo ?? null;
            const numeroReciboDesdeDOM = (() => {
                try {
                    const el = document.querySelector('#order-pedido') || document.querySelector('.pedido-number');
                    const raw = el ? String(el.textContent || '').trim() : '';
                    if (!raw) return '';
                    // raw puede ser "#7" o "7"; normalizar a solo número
                    return raw.startsWith('#') ? raw.slice(1).trim() : raw;
                } catch (_) {
                    return '';
                }
            })();

            // Número a mostrar en impresión: si no existe, NO inventar un consecutivo.
            const numeroReciboFinal = (numeroReciboActual !== null && numeroReciboActual !== undefined && String(numeroReciboActual).trim() !== '')
                ? String(numeroReciboActual).trim()
                : String(numeroReciboDesdeDOM || '').trim();
            const numeroParaImpresion = numeroReciboFinal ? ('#' + numeroReciboFinal) : '';

            // Paginación (misma lógica del ejemplo): estimación por altura total de 4 columnas.
            // Ajuste: agrupar por género sin repetirlo en cada talla.
            const pages = [];
            let currentBlocks = []; // [{type:'genero', genero} | {type:'talla', genero, talla, observaciones}]
            let currentHeightMm = 0;

            // Altura dinámica de la sección de observaciones por talla:
            // - Arranca con una altura por defecto más parecida a la vista.
            // - Crece gradualmente si el contenido lo requiere, hasta el máximo permitido en hoja.
            const MIN_AVAILABLE_HEIGHT_MM = 75;
            const MAX_AVAILABLE_HEIGHT_MM = 110;
            const GEN_HEADER_HEIGHT_MM = 4;
            const TALLA_TITLE_HEIGHT_MM = 6;
            const LINE_HEIGHT_MM = 3.2;

            const estimarAlturaTotalNecesariaMm = (items) => {
                if (!Array.isArray(items) || items.length === 0) return 0;
                const gruposPorGenero = new Map();
                items.forEach((t) => {
                    const key = String(t?.genero || '').toUpperCase();
                    if (!gruposPorGenero.has(key)) gruposPorGenero.set(key, []);
                    gruposPorGenero.get(key).push(t);
                });

                let total = 0;
                for (const [generoKey, tallasGrupo] of gruposPorGenero.entries()) {
                    if (generoKey) total += GEN_HEADER_HEIGHT_MM;
                    (tallasGrupo || []).forEach((tg) => {
                        const obsLen = Array.isArray(tg?.observaciones) ? tg.observaciones.length : 0;
                        total += TALLA_TITLE_HEIGHT_MM + (obsLen * LINE_HEIGHT_MM);
                    });
                }
                return total;
            };

            const totalNecesarioMm = estimarAlturaTotalNecesariaMm(observacionesPorTalla);
            const cabeEnUnaHojaConMax = totalNecesarioMm <= (MAX_AVAILABLE_HEIGHT_MM * 4);
            const availableHeightMm = cabeEnUnaHojaConMax
                ? Math.max(MIN_AVAILABLE_HEIGHT_MM, Math.min(MAX_AVAILABLE_HEIGHT_MM, Math.ceil(totalNecesarioMm / 4)))
                : MAX_AVAILABLE_HEIGHT_MM;

            const AVAILABLE_HEIGHT_MM = availableHeightMm;
            const TOTAL_COLUMN_CAPACITY_MM = AVAILABLE_HEIGHT_MM * 4;

            const pushPage = () => {
                if (!currentBlocks.length) return;
                pages.push({ blocks: currentBlocks, num: pages.length + 1 });
                currentBlocks = [];
                currentHeightMm = 0;
            };

            const addGeneroHeaderIfNeeded = (genero) => {
                if (!genero) return;
                const last = currentBlocks.length ? currentBlocks[currentBlocks.length - 1] : null;
                const yaEsta = last && last.type === 'genero' && last.genero === genero;
                if (yaEsta) return;

                // Si no cabe el header, cortar página.
                if (currentHeightMm + GEN_HEADER_HEIGHT_MM > TOTAL_COLUMN_CAPACITY_MM && currentBlocks.length > 0) {
                    pushPage();
                }

                currentBlocks.push({ type: 'genero', genero });
                currentHeightMm += GEN_HEADER_HEIGHT_MM;
            };

            // Agrupar por género manteniendo orden
            const grupos = new Map();
            observacionesPorTalla.forEach((t) => {
                const key = t.genero || '';
                if (!grupos.has(key)) grupos.set(key, []);
                grupos.get(key).push(t);
            });

            for (const [generoKey, tallasGrupo] of grupos.entries()) {
                const generoUpper = generoKey ? String(generoKey).toUpperCase() : '';

                // Encabezado de género (una vez por grupo, y se repetirá solo si hay salto de página)
                addGeneroHeaderIfNeeded(generoUpper);

                tallasGrupo.forEach((tallaItem) => {
                    let remainingObs = Array.isArray(tallaItem.observaciones) ? [...tallaItem.observaciones] : [];
                    let isFirstPart = true;

                    while (remainingObs.length > 0) {
                        // Si estamos iniciando un nuevo “bloque” en una página vacía o recién se cortó,
                        // y el grupo tiene género, repetir el header para contexto.
                        if (currentBlocks.length === 0) {
                            addGeneroHeaderIfNeeded(generoUpper);
                        } else {
                            // Si el último block es de otra cosa y el género se perdió por page-break,
                            // lo reinsertamos cuando corresponde.
                            const last = currentBlocks[currentBlocks.length - 1];
                            const generoPresente = last && ((last.type === 'genero' && last.genero === generoUpper) || (last.type === 'talla' && last.genero === generoUpper));
                            if (!generoPresente) {
                                addGeneroHeaderIfNeeded(generoUpper);
                            }
                        }

                        const titleH = isFirstPart ? TALLA_TITLE_HEIGHT_MM : 0;
                        const availableSpace = TOTAL_COLUMN_CAPACITY_MM - currentHeightMm - titleH;
                        const maxObsThatFit = Math.floor(availableSpace / LINE_HEIGHT_MM);

                        if (maxObsThatFit <= 0 && currentBlocks.length > 0) {
                            pushPage();
                            continue;
                        }

                        const take = remainingObs.splice(0, Math.max(0, maxObsThatFit));
                        if (take.length > 0) {
                            currentBlocks.push({
                                type: 'talla',
                                genero: generoUpper,
                                talla: isFirstPart ? tallaItem.talla : (tallaItem.talla + ' (cont.)'),
                                observaciones: take
                            });
                            currentHeightMm += titleH + (take.length * LINE_HEIGHT_MM);
                        }

                        isFirstPart = false;

                        if (remainingObs.length > 0) {
                            pushPage();
                        }
                    }
                });
            }

            pushPage();

            const totalPages = (pages && Array.isArray(pages) && pages.length > 0) ? pages.length : 1;

            const renderHeader = (pageNum) => {
                const pageLabel = totalPages > 1 ? (`PÁGINA ${pageNum}`) : '';

                // Mostrar el número real del recibo (ej: #7). Si no existe, no mostrar nada.
                const reciboLabel = numeroParaImpresion ? numeroParaImpresion : '';

                // Para COSTURA: renderizar estructura específica con campos separados
                if (tipoProceso && tipoProceso.toUpperCase() === 'COSTURA') {
                    const variantes = reciboActual?.variantes || prendaData?.variantes || [];
                    const primeraVariante = variantes.length > 0 ? variantes[0] : {};
                    
                    // Extraer datos específicos de COSTURA
                    const manga = primeraVariante?.manga || prendaData?.manga || '';
                    const obsManga = primeraVariante?.manga_obs || primeraVariante?.obs_manga || prendaData?.obs_manga || '';
                    const broche = primeraVariante?.broche || prendaData?.broche || '';
                    const obsBroche = primeraVariante?.broche_obs || primeraVariante?.obs_broche || prendaData?.obs_broche || '';
                    const tieneBolsillos = primeraVariante?.tiene_bolsillos || primeraVariante?.bolsillos || prendaData?.tiene_bolsillos || false;
                    const obsBolsillos = primeraVariante?.bolsillos_obs || primeraVariante?.obs_bolsillos || prendaData?.obs_bolsillos || '';
                    const tieneReflectivo = primeraVariante?.tiene_reflectivo || prendaData?.tiene_reflectivo || false;
                    const obsReflectivo = primeraVariante?.obs_reflectivo || prendaData?.obs_reflectivo || '';
                    
                    // Construir línea compacta de TELAS: Nombre / Color | REF: ref | Manga con obs
                    const telaPartesPrincipal = [];
                    if (prendaData?.tela) telaPartesPrincipal.push(esc(prendaData.tela));
                    if (prendaColor) telaPartesPrincipal.push(esc(prendaColor));
                    const telaMain = telaPartesPrincipal.join(' / ');
                    
                    const refText = prendaData?.ref_tela || prendaData?.referencia || prendaData?.ref || '';
                    
                    const mangaInfo = [];
                    if (manga) mangaInfo.push(`MANGA: ${esc(manga)}${obsManga ? ` (${esc(obsManga)})` : ''}`);
                    
                    const telaLinea = [];
                    if (telaMain) telaLinea.push(telaMain);
                    if (refText) telaLinea.push(`REF: ${refText}`);
                    const telaLineaTexto = telaLinea.join(' | ');
                    const mangaLineaTexto = mangaInfo.join('');
                    
                    return `
                        <img src="/images/logo.png" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
                        <div id="order-date" class="order-date">
                          <div class="fec-label">FECHA</div>
                          <div class="date-boxes">
                            <div class="date-box day-box">${esc((fechaDOM || '').split('/')[0] || '')}</div>
                            <div class="date-box month-box">${esc((fechaDOM || '').split('/')[1] || '')}</div>
                            <div class="date-box year-box">${esc((fechaDOM || '').split('/')[2] || '')}</div>
                          </div>
                        </div>

                        <div class="header-right">
                          ${pageLabel ? `<div class="page-label">${esc(pageLabel)}</div>` : ''}
                          <div class="receipt-title-print">${esc(titulo).toUpperCase()}</div>
                          ${reciboLabel ? `<div class="recibo-number-print">${esc(reciboLabel)}</div>` : ''}
                          <div class="cliente-print"><span class="label">CLIENTE:</span> <span class="value">${esc(cliente || '-')}</span></div>
                        </div>

                        <div class="meta">
                          <div style="grid-column: 1 / 3;"><span class="label">ASESOR:</span> <span class="value">${esc(asesor || '-')}</span></div>
                          <div style="grid-column: 1 / 3;"><span class="label">FORMA DE PAGO:</span> <span class="value">${esc(formaPago || '-')}</span></div>
                        </div>

                        <div class="prenda-info">
                          <div class="prenda-name">PRENDA 1: ${esc(prendaNombre || '-').toUpperCase()}</div>
                        </div>
                        
                        <div class="costura-section" style="line-height: 1.4; font-size: 11px;">
                          ${telaLineaTexto ? `
                          <div style="margin-bottom: 4px;">
                            <span style="font-weight: 700;">TELAS: ${telaLineaTexto}</span>
                          </div>
                          ` : ''}
                          
                          ${mangaLineaTexto ? `
                          <div style="margin-bottom: 4px;">
                            <span style="font-weight: 700;">${mangaLineaTexto}</span>
                          </div>
                          ` : ''}
                          
                          ${broche ? `
                          <div style="margin-bottom: 4px;">
                            <span style="font-weight: 700;">BROCHE/BOTÓN:</span> ${esc(broche)}${obsBroche ? ` (${esc(obsBroche)})` : ''}
                          </div>
                          ` : ''}
                          
                          ${tieneBolsillos ? `
                          <div style="margin-bottom: 4px;">
                            <span style="font-weight: 700;">TIENE BOLSILLOS:</span> SÍ${obsBolsillos ? ` - ${esc(obsBolsillos)}` : ''}
                          </div>
                          ` : ''}
                          
                          ${tieneReflectivo ? `
                          <div style="margin-bottom: 4px;">
                            <span style="font-weight: 700;">TIENE REFLECTIVO:</span> SÍ${obsReflectivo ? ` - ${esc(obsReflectivo)}` : ''}
                          </div>
                          ` : ''}
                        </div>
                        
                        ${(ubicaciones && ubicaciones.length > 0) ? `
                        <div style="margin: 4px 0; font-size: 11px;">
                          ${ubicaciones.map(u => `<div>${esc(u).toUpperCase()}</div>`).join('')}
                        </div>
                        ` : ''}
                        
                        ${tallasResumen ? `
                        <div class="section">
                          ${mostrarTituloTallas ? '<h4>TALLAS:</h4>' : ''}
                          <div class="tallas-resumen" style="color: inherit; font-weight: 900; white-space: pre-line;">${contenidoTallasFinal}</div>
                        </div>
                        ` : '<!-- SIN TALLAS - tallasResumen está vacío -->'}
                        
                        ${observacionesPorTalla.length > 0 ? `
                        <div class="section observations-section">
                          <h4>OBSERVACIONES POR TALLA:</h4>
                          <div class="tallas-columns">
                            ${(function() {
                                const grupos = new Map();
                                observacionesPorTalla.forEach((t) => {
                                    const key = t.genero || '';
                                    if (!grupos.has(key)) grupos.set(key, []);
                                    grupos.get(key).push(t);
                                });
                                
                                let html = '';
                                for (const [generoKey, tallasGrupo] of grupos.entries()) {
                                    const generoUpper = generoKey ? generoKey.toUpperCase() : '';
                                    if (generoUpper) {
                                        html += `<div class="genero-header">${esc(generoUpper)}</div>`;
                                    }
                                    tallasGrupo.forEach((tallaItem) => {
                                        const lis = (tallaItem.observaciones || []).map(obs => `<li>${esc(obs).toUpperCase()}</li>`).join('');
                                        html += `
                                            <div class="talla-item">
                                              <div class="talla-title">${esc(tallaItem.talla).toUpperCase()}</div>
                                              <ul class="observaciones-list">${lis}</ul>
                                            </div>
                                        `;
                                    });
                                }
                                return html;
                            })()}
                          </div>
                        </div>
                        ` : ''}
                    `;
                }
                
                // Para otros tipos de recibo: mantener estructura original
                const ubicHtml = (ubicaciones && ubicaciones.length > 0)
                    ? ubicaciones.map(u => `<div>${esc(u).toUpperCase()}</div>`).join('')
                    : '<div>-</div>';

                return `
                    <img src="/images/logo.png" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
                    <div id="order-date" class="order-date">
                      <div class="fec-label">FECHA</div>
                      <div class="date-boxes">
                        <div class="date-box day-box">${esc((fechaDOM || '').split('/')[0] || '')}</div>
                        <div class="date-box month-box">${esc((fechaDOM || '').split('/')[1] || '')}</div>
                        <div class="date-box year-box">${esc((fechaDOM || '').split('/')[2] || '')}</div>
                      </div>
                    </div>

                    <div class="header-right">
                      ${pageLabel ? `<div class="page-label">${esc(pageLabel)}</div>` : ''}
                      <div class="receipt-title-print">${esc(titulo).toUpperCase()}</div>
                      ${reciboLabel ? `<div class="recibo-number-print">${esc(reciboLabel)}</div>` : ''}
                      <div class="cliente-print"><span class="label">CLIENTE:</span> <span class="value">${esc(cliente || '-')}</span></div>
                    </div>

                    <div class="meta">
                      <div style="grid-column: 1 / 3;"><span class="label">ASESOR:</span> <span class="value">${esc(asesor || '-')}</span></div>
                      <div style="grid-column: 1 / 3;"><span class="label">FORMA DE PAGO:</span> <span class="value">${esc(formaPago || '-')}</span></div>
                    </div>

                    <div class="prenda-info">
                      <div class="prenda-name">${esc(prendaNombre || '-').toUpperCase()}</div>
                      ${prendaColor && prendaColor !== '-' ? `<div class="prenda-color">${esc(prendaColor).toUpperCase()}</div>` : ''}
                      ${prendaDescripcion ? `<div class="prenda-descripcion" style="font-size: 11px; margin-top: 4px; color: #333;">${esc(prendaDescripcion)}</div>` : ''}
                    </div>
                    <div class="section">
                      <h4>UBICACIONES:</h4>
                      <div class="ubicaciones-list">${ubicHtml}</div>
                    </div>
                    ${tallasResumen ? `
                    <div class="section">
                      ${mostrarTituloTallas ? '<h4>TALLAS:</h4>' : ''}
                      <div class="tallas-resumen" style="color: inherit; font-weight: 900; white-space: pre-line;">${contenidoTallasFinal}</div>
                    </div>
                    ` : ''}
                `;
            };

            // Detectar si es solo sobremedida (basado en el contenido procesado)
            const esSoloSobremedida = tallasResumen && tallasResumen.includes('SOBREMEDIDA:</span>') && !tallasResumen.includes('<span style="color: #d32f2f');
            
            // Para impresión, necesitamos saber si el contenido original era solo sobremedida
            const contenidoOriginalEsSoloSobremedida = (() => {
                if (Array.isArray(reciboActual?.tallas_detalle) && reciboActual.tallas_detalle.length > 0) {
                    return reciboActual.tallas_detalle.every(t => t.es_sobremedida);
                }
                if (tipoProceso?.toUpperCase() === 'COSTURA') {
                    const variantes = reciboActual?.variantes || prendaData?.variantes || [];
                    return variantes.length > 0 && variantes.every(v => v.es_sobremedida);
                }
                return false;
            })();

            let mostrarTituloTallas = true;
            let contenidoTallasFinal = tallasResumen;
            
            console.log('[printReceiptModal] DEBUG tallasResumen original:', tallasResumen);
            
            if (tallasResumen) {
                if (tallasResumen.startsWith('SOBREMEDIDA_SIN_TALLAS:')) {
                    console.log('[printReceiptModal] Procesando SOBREMEDIDA_SIN_TALLAS');
                    const partes = tallasResumen.substring('SOBREMEDIDA_SIN_TALLAS:'.length).split('|');
                    mostrarTituloTallas = false;
                    contenidoTallasFinal = '<span style="color: #000; font-weight: 900;">SOBREMEDIDA:</span><br>' + 
                        partes.map(p => `<span style="color: #d32f2f; font-weight: 900;">${p}</span>`).join(' | ');
                    console.log('[printReceiptModal] Resultado procesado:', contenidoTallasFinal);
                } else if (tallasResumen.includes('SOBREMEDIDA_CON_TALLAS:')) {
                    console.log('[printReceiptModal] Procesando SOBREMEDIDA_CON_TALLAS');
                    const lineas = tallasResumen.split('\n');
                    contenidoTallasFinal = lineas.map(linea => {
                        if (linea.startsWith('SOBREMEDIDA_CON_TALLAS:')) {
                            const partes = linea.substring('SOBREMEDIDA_CON_TALLAS:'.length).split('|');
                            return '<span style="color: #000; font-weight: 900;">SOBREMEDIDA:</span><br>' + 
                                partes.map(p => `<span style="color: #d32f2f; font-weight: 900;">${p}</span>`).join(' | ');
                        }
                        return `<span style="color: #d32f2f; font-weight: 900;">${linea}</span>`;
                    }).join('<br>');
                    console.log('[printReceiptModal] Resultado procesado:', contenidoTallasFinal);
                } else {
                    console.log('[printReceiptModal] Tallas normales');
                    // Tallas normales sin sobremedida
                    contenidoTallasFinal = `<span style="color: #d32f2f; font-weight: 900;">${tallasResumen}</span>`;
                }
                
                console.log('[printReceiptModal] Variables finales:', {
                    mostrarTituloTallas,
                    contenidoTallasFinal
                });
            }

            const renderTallaItem = (t) => {
                const lis = (t.observaciones || []).map(obs => `<li>${esc(obs).toUpperCase()}</li>`).join('');
                return `
                    <div class="talla-item">
                      <div class="talla-title">${esc(t.talla).toUpperCase()}</div>
                      <ul class="observaciones-list">${lis}</ul>
                    </div>
                `;
            };

            const renderGeneroHeader = (genero) => {
                if (!genero) return '';
                return `
                    <div class="genero-header">${esc(genero).toUpperCase()}</div>
                `;
            };

            const renderTallasSection = (blocks) => {
                const itemsHtml = (Array.isArray(blocks) ? blocks : []).map((b) => {
                    if (b && b.type === 'genero') return renderGeneroHeader(b.genero);
                    if (b && b.type === 'talla') return renderTallaItem(b);
                    return '';
                }).join('');

                // Si no hay bloques, no mostrar la sección completa
                if (!Array.isArray(blocks) || blocks.length === 0) {
                    return '';
                }

                return `
                    <div class="section observations-section" style="margin-top: 2px;">
                      <h4 style="margin-bottom: 2px; font-size: 11px;">OBSERVACIONES POR TALLA</h4>
                      <div class="tallas-columns">${itemsHtml}</div>
                    </div>
                `;
            };

            const renderFooter = () => {
                return `
                    <div class="separator-line"></div>
                    <div class="footer">
                      <div>ENCARGADO DE ORDEN:<br><span style="font-weight:700">-</span></div>
                      <div>PRENDAS ENTREGADAS:<br><span style="font-weight:700">0/0</span></div>
                    </div>
                `;
            };

            const pagesHtml = (pages.length > 0 ? pages : [{ blocks: [], num: 1 }]).map((p) => {
                return `
                    <div class="page">
                      <div class="receipt-card">
                        ${renderHeader(p.num)}
                        ${renderTallasSection(p.blocks)}
                      </div>
                    </div>
                `;
            }).join('');

            const css = `
                :root { --page-width: 180mm; --page-height: 297mm; --page-padding: 5mm; --brand-font: Inter, ui-sans-serif, system-ui, sans-serif; }
                * { box-sizing: border-box; }
                html, body { margin: 0; padding: 0; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                body { font-family: var(--brand-font); color: #111; }
                @media print {
                  @page { size: A4 portrait; margin: 0; }
                  body { background: #fff; margin: 0; padding: 0; }
                  .no-print { display: none !important; }
                  .page { width: var(--page-width); height: var(--page-height); padding: var(--page-padding); page-break-after: always; position: relative; overflow: hidden; }
                  .receipt-card { height: 100%; border: 4px solid #111; border-radius: 20px; padding: 30px 30px 60px 30px; box-shadow: none; }
                  /* Altura fija para que el contenido no sobrepase el separator/footer y fluya por columnas */
                  .tallas-columns {
                    height: ${AVAILABLE_HEIGHT_MM}mm;
                    overflow: hidden;
                    column-count: 4;
                    -webkit-column-count: 4;
                    -moz-column-count: 4;
                    column-fill: auto;
                    column-gap: 3px;
                    -webkit-column-gap: 3px;
                    -moz-column-gap: 3px;
                  }
                }
                .page { width: var(--page-width); min-height: var(--page-height); padding: var(--page-padding); box-sizing: border-box; position: relative; overflow: hidden; margin: 0 auto; }
                .receipt-card { width: 100%; border: 4px solid #111; border-radius: 20px; padding: 30px 30px 70px 30px; position: relative; }
                .order-logo { display: block; margin: -70px auto 20px auto; width: 200px; height: auto; }
                .order-date { position: absolute; top: 110px; left: 20px; background: #000; border-radius: 10px; padding: 8px; color: #fff; text-align: center; width: 180px; box-shadow: 0 2px 5px rgba(0,0,0,0.3); }
                .fec-label { font-weight: 900; font-size: 12px; margin-bottom: 4px; text-transform: uppercase; }
                .date-boxes { display: flex; justify-content: space-between; gap: 6px; }
                .date-box { background: #fff; color: #000; border-radius: 6px; padding: 8px 0; width: 52px; font-weight: 900; font-size: 14px; }
                .header-right { position: absolute; top: 110px; right: 30px; text-align: right; width: 55%; }
                .page-label { font-weight: 900; font-size: 11px; opacity: 0.85; line-height: 1; margin: 0 0 2px 0; text-align: right; }
                .receipt-title-print { font-weight: 900; text-transform: uppercase; text-align: right; font-size: 16px; line-height: 1.1; margin: 0; }
                .recibo-number-print { color: #d32f2f; font-weight: 900; font-size: 24px; line-height: 1; margin-top: 2px; text-align: right; }
                .cliente-print { font-weight: 900; font-size: 12px; margin-top: 6px; text-align: right; }
                .meta { display:grid; grid-template-columns: 1fr 1fr; gap: 5px 20px; font-size: 12px; font-weight: 700; margin: 0 0 15px 0; padding-top: 35px; }
                .meta .label { opacity: 0.7; }
                .prenda-info { margin-bottom: 15px; }
                .prenda-name { font-weight: 900; font-size: 16px; text-transform: uppercase; }
                .prenda-color { font-weight: 700; font-size: 14px; text-transform: uppercase; }
                .section { margin-bottom: 15px; font-size: 12px; }
                .section h4 { margin: 0 0 5px 0; font-weight: 900; text-transform: uppercase; font-size: 12px; }
                .tallas-resumen { color:#d32f2f; font-weight: 900; }
                
                /* Estilos específicos para COSTURA */
                .costura-section { margin-bottom: 15px; font-size: 11px; }
                .costura-row { margin-bottom: 6px; }
                .costura-field { display: flex; gap: 4px; margin-bottom: 2px; }
                .costura-field .label { font-weight: 900; min-width: 140px; }
                .costura-field .value { font-weight: 700; }
                .costura-obs { margin-left: 144px; font-weight: 500; font-style: italic; color: #333; }
                .descripcion-list { font-size: 11px; line-height: 1.3; }
                .observations-section { margin-bottom: 15px; }
                .observations-section h4 { margin: 0 0 5px 0; font-weight: 900; text-transform: uppercase; font-size: 12px; }
                /* Vista previa en popup: mantener el mismo flujo por columnas que en impresión */
                .tallas-columns {
                  height: ${AVAILABLE_HEIGHT_MM}mm;
                  overflow: hidden;
                  column-count: 4;
                  -webkit-column-count: 4;
                  -moz-column-count: 4;
                  column-fill: auto;
                  column-gap: 4px;
                  -webkit-column-gap: 4px;
                  -moz-column-gap: 4px;
                }
                .genero-header { break-inside: avoid; page-break-inside: avoid; margin: 0 0 4px 0; font-size: 11px; font-weight: 900; text-transform: uppercase; }
                /* Permitir cortes dentro de una talla para que las columnas queden llenas (sin huecos) */
                .talla-item { break-inside: auto; page-break-inside: auto; margin-bottom: 5px; font-size: 10px; padding-right: 1px; }
                .talla-title { font-weight: 900; text-decoration: underline; margin-bottom: 1px; font-size: 10.5px; }
                .observaciones-list { list-style:none; padding: 0; margin: 0; break-inside: auto; page-break-inside: auto; }
                .observaciones-list li { margin-bottom: 1px; line-height: 1.1; break-inside: auto; page-break-inside: auto; }
                .observaciones-list li::before { content: "• "; margin-right: 1px; }
                .separator-line { position: absolute; bottom: 60px; left: 0; right: 0; height: 1mm; background: #111; }
                .footer { position:absolute; bottom: 0; left:0; right:0; display:grid; grid-template-columns: 1fr 1fr; font-size: 11px; font-weight: 800; }
                .footer > div { padding: 10px 15px; border-right: 1mm solid #111; min-height: 50px; }
                .footer > div:last-child { border-right: none; }
                body.singlepage .receipt-card {
                  height: auto !important;
                  min-height: 0 !important;
                  padding: 20px 20px 70px 20px;
                  display: block;
                }
                /* Singlepage: NO forzar el recibo a ocupar toda la hoja; solo usar la altura necesaria */
                body.singlepage .page {
                  height: auto !important;
                  min-height: 0 !important;
                  overflow: visible !important;
                }
                body.singlepage .tallas-columns { height: ${AVAILABLE_HEIGHT_MM}mm !important; overflow: hidden !important; }
                body.singlepage .separator-line { position: absolute !important; bottom: 60px !important; left: 0 !important; right: 0 !important; }
                body.singlepage .footer { position: absolute !important; bottom: 0 !important; left: 0 !important; right: 0 !important; }
            `;

            const bodyClass = totalPages > 1 ? 'multipage' : 'singlepage';

            const html = `<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Impresión Recibo</title>
  <style>${css}</style>
</head>
<body class="${bodyClass}">
  ${pagesHtml}
  <script>
    window.addEventListener('load', () => { setTimeout(() => window.print(), 50); });
  </script>
</body>
</html>`;

            console.log('[printReceiptModal] 🖨️ HTML FINAL PARA IMPRESIÓN:', {
                'longitud HTML': html?.length,
                'contiene TALLAS': html?.includes('TALLAS'),
                'contiene tallas-resumen': html?.includes('tallas-resumen'),
                'contiene CABALLERO': html?.includes('CABALLERO'),
                'fragmento TALLAS': html?.match(/<h4>TALLAS:<\/h4>.*?<\/div>/s)?.[0]?.substring(0, 200)
            });

            const w = window.open('', '_blank');
            if (!w) {
                window.print();
                return;
            }
            w.document.open();
            w.document.write(html);
            w.document.close();
            return;
        } catch (e) {
            console.warn('[printReceiptModal] Error en impresión nueva, usando fallback window.print():', e);
            window.print();
        }
     };
 }

/**
 * FUNCIÓN GLOBAL para abrir recibo parcial (anexo)
 * Usa la pipeline de renderizado completa, inyectando tallas del parcial
 */
window.openOrderDetailModalWithParcial = async function(parcialId, prendaId, tipoString, pedidoIdOverride = null, nombreAnexoOverride = null) {
    const pedidoId = pedidoIdOverride || window.selectorRecibosState?.pedidoId;
    const nombreAnexo = nombreAnexoOverride || window.selectorRecibosState?.nombreProcesoAnexo || tipoString;

    if (!pedidoId) {
        console.error('[openOrderDetailModalWithParcial] pedidoId no disponible en selectorRecibosState');
        alert('Error: No se pudo determinar el pedido');
        return;
    }

    return window.pedidosRecibosModule.abrirReciboParcial(
        pedidoId, prendaId, tipoString, parcialId, nombreAnexo
    );
};

/**
 * FUNCIÓN GLOBAL para cerrar recibos
 */
window.cerrarModalRecibos = function() {
    window.pedidosRecibosModule.cerrarRecibo();
};

/**
 * FUNCIÓN GLOBAL para abrir imagen en grande desde la galería
 * Disponible en todas las vistas que usen PedidosRecibosModule
 */
if (typeof window.abrirModalImagenProcesoGrande !== 'function') {
    window.abrirModalImagenProcesoGrande = function(indice, fotos) {
        GalleryManager.abrirModalImagenProcesoGrande(indice, fotos);
    };
}

// Compatibilidad: algunos templates aún llaman onclick="toggleFactura()"
// Siempre sobreescribir para que funcione en todas las vistas (supervisor-pedidos, recibos-costura, insumos, etc.)
// Guarda la versión anterior como fallback para cuando NO hay estado activo de recibo
const _originalToggleFactura = window.toggleFactura;

window.toggleFactura = function() {
    console.log('[toggleFactura-PRM] Toggle entre recibo y galería');
    
    // Verificar si PedidosRecibosModule tiene estado activo
    const estado = window.pedidosRecibosModule ? window.pedidosRecibosModule.getEstado() : null;
    const tieneEstadoActivo = estado && estado.pedidoId && (estado.imagenesActuales.length > 0 || estado.prendaPedidoId);
    
    if (!tieneEstadoActivo) {
        // Sin estado activo → usar la implementación original si existe
        console.log('[toggleFactura-PRM] Sin estado activo, delegando a implementación original');
        if (_originalToggleFactura) return _originalToggleFactura.call(this);
        return;
    }
    
    const galeria = document.getElementById('galeria-modal-costura');
    const estaEnGaleria = galeria && (galeria.style.display === 'flex' || galeria.style.display === 'block');
    
    const btnFactura = document.getElementById('btn-factura');
    const btnGaleria = document.getElementById('btn-galeria');
    
    if (estaEnGaleria) {
        // Estamos en galería → cerrar galería y mostrar recibo
        console.log('[toggleFactura-PRM] Cerrando galería, mostrando recibo');
        GalleryManager.cerrarGaleria();
        // Mostrar btn-factura (con icono de galería para indicar que se puede ir a galería)
        if (btnFactura) {
            btnFactura.style.display = 'block';
            btnFactura.style.visibility = 'visible';
            btnFactura.style.zIndex = '10';
            const icon = btnFactura.querySelector('i');
            if (icon) { icon.className = 'fas fa-images'; btnFactura.title = 'Ver galería'; }
        }
        // Ocultar btn-galeria
        if (btnGaleria) {
            btnGaleria.style.display = 'none';
            btnGaleria.style.visibility = 'hidden';
            btnGaleria.style.zIndex = '-1';
        }
    } else {
        // Estamos en recibo → abrir galería
        console.log('[toggleFactura-PRM] Abriendo galería');
        if (window.toggleGaleria) {
            window.toggleGaleria();
        }
        // Ocultar btn-factura
        if (btnFactura) {
            btnFactura.style.display = 'none';
            btnFactura.style.visibility = 'hidden';
            btnFactura.style.zIndex = '-1';
        }
        // Mostrar btn-galeria (con icono de recibo para indicar que se puede volver)
        if (btnGaleria) {
            btnGaleria.style.display = 'block';
            btnGaleria.style.visibility = 'visible';
            btnGaleria.style.zIndex = '10';
            const icon = btnGaleria.querySelector('i');
            if (icon) { icon.className = 'fas fa-receipt'; btnGaleria.title = 'Ver recibos'; }
        }
    }
};

// Interceptar toggleGaleria original
const originalToggleGaleria = window.toggleGaleria;
window.originalToggleGaleria = originalToggleGaleria; // Guardar referencia para evitar recursión

window.toggleGaleria = async function() {
    // Evitar recursión infinita
    if (window.toggleGaleria._calling) {
        console.warn('[toggleGaleria]  Evitando recursión infinita');
        return;
    }
    
    window.toggleGaleria._calling = true;
    
    try {
        // Si hay estado de recibos dinámicos, usar la nueva galería
        const estado = window.pedidosRecibosModule.getEstado();
        if (estado.pedidoId && (estado.imagenesActuales.length > 0 || estado.prendaPedidoId)) {
            return window.pedidosRecibosModule.abrirGaleria();
        }
        
        // Si no, usar la galería original
        if (originalToggleGaleria) {
            return originalToggleGaleria.call(this);
        }
    } finally {
        window.toggleGaleria._calling = false;
    }
};

