import { crearBotonAgregarNovedad } from './novedadButtons';

const DASHBOARD_DEBUG = false;

export function initReciboFilters() {
    // Flag para saber si estamos en modo Control de Calidad
    let __enModoControlCalidad = false;
    window.__enModoControlCalidad = false;
    
    // Guardar HTML original de recibos normales
    let htmlRecibosOriginal = null;
    
    // Guardar el HTML original cuando la pagina carga
    const ordenesList = document.getElementById('ordenesList');
    if (ordenesList) {
        // Esperar a que el DOM esta completamente listo
        setTimeout(() => {
            htmlRecibosOriginal = ordenesList.innerHTML;
            if (DASHBOARD_DEBUG) console.log('[INIT] HTML de recibos originales guardado');
        }, 100);
    }
    
    // Exponer globalmente para usarlo en recargarRecibosNormales
    window.__htmlRecibosOriginal = () => htmlRecibosOriginal;

    function aplicarTemaDashboard(filtroPrincipal) {
        const body = document.body;
        const titleText = document.getElementById('dashboardPageTitleText');
        const titleIcon = document.getElementById('dashboardPageTitleIcon');
        const theme = filtroPrincipal === 'reflectivo' ? 'reflectivo' : 'costura';

        if (body) {
            body.setAttribute('data-dashboard-theme', theme);
        }

        if (titleText) {
            titleText.textContent = theme === 'reflectivo' ? 'RECIBOS DE REFLECTIVO' : 'RECIBOS DE COSTURA';
        }

        if (titleIcon) {
            titleIcon.textContent = theme === 'reflectivo' ? 'auto_awesome' : 'checkroom';
        }
    }

    function obtenerFiltroPrincipalActivo() {
        const filtroActivo = window.__dashboardFiltroPrincipalActivo
            || document.querySelector('.badge-filtro[data-filtro].badge-filtro-active')?.dataset?.filtro;
        
        if (filtroActivo) return filtroActivo;
        
        // Si no hay filtro activo pero existen botones con data-filtro, el default es costura
        if (document.querySelector('.badge-filtro[data-filtro]')) {
            return 'costura';
        }
        
        // Si no hay botones de filtro por tipo, mostrar todos
        return 'todos';
    }

    // Exponer obtenerFiltroPrincipalActivo globalmente
    window.obtenerFiltroPrincipalActivo = obtenerFiltroPrincipalActivo;

    function obtenerFiltroEncargadoActivo() {
        return window.__vistaCosturaEncargadoFiltro || 'todos';
    }

    function actualizarBadgeSinEncargado(filtroPrincipal) {
        const badgeCount = document.getElementById('badgeSinEncargadoCount');
        if (!badgeCount) return;

        const totalGlobal = parseInt(badgeCount.dataset.totalGlobal || '0', 10);
        if (document.querySelector('.operario-dashboard')?.dataset?.userRole === 'vista-costura') {
            badgeCount.textContent = String(totalGlobal);
            return;
        }

        const ordenesList = document.getElementById('ordenesList');
        if (!ordenesList) {
            badgeCount.textContent = '0';
            return;
        }

        const cards = Array.from(ordenesList.querySelectorAll('.orden-card-simple'));
        const atributo = filtroPrincipal === 'reflectivo' ? 'sinEncargadoReflectivo' : 'sinEncargadoCostura';

        const totalSinEncargado = cards.filter((card) => {
            const tipos = String(card.dataset.tipoRecibo || '')
                .split(',')
                .map((valor) => valor.trim())
                .filter(Boolean);

            if (!tipos.includes(filtroPrincipal)) {
                return false;
            }

            return String(card.dataset[atributo] || '0') === '1';
        }).length;

        badgeCount.textContent = String(totalSinEncargado);
    }

    function actualizarBadgeControlCalidad(filtroPrincipal) {
        const badgeCount = document.getElementById('badgeControlCalidadCount');
        if (!badgeCount) return;

        const contadorCostura = parseInt(badgeCount.dataset.contadorCostura || '0', 10);
        const contadorReflectivo = parseInt(badgeCount.dataset.contadorReflectivo || '0', 10);

        let totalMostrar = 0;

        if (filtroPrincipal === 'costura') {
            totalMostrar = contadorCostura;
        } else if (filtroPrincipal === 'reflectivo') {
            totalMostrar = contadorReflectivo;
        } else {
            // Para "todos", no mostrar contador
            badgeCount.style.display = 'none';
            return;
        }

        badgeCount.textContent = String(totalMostrar);
        badgeCount.style.display = totalMostrar > 0 ? 'inline-flex' : 'none';
    }

    function aplicarFiltrosDashboard(filtroPrincipal) {
        if (DASHBOARD_DEBUG) console.log(' [FILTRO] Iniciando filtro:', filtroPrincipal);

        const ordenesList = document.getElementById('ordenesList');
        if (!ordenesList) {
            console.error(' ordenesList no encontrado');
            return;
        }

        const filtroEncargado = obtenerFiltroEncargadoActivo();
        const ordenCards = ordenesList.querySelectorAll('.orden-card-simple');
        let mostradas = 0;
        let ocultadas = 0;

        actualizarBadgeSinEncargado(filtroPrincipal);
        actualizarBadgeControlCalidad(filtroPrincipal);

        ordenCards.forEach((card, index) => {
            const tipoRecibo = card.dataset.tipoRecibo;
            const numeroPedido = card.dataset.numero;
            const nombrePrenda = card.dataset.prenda;

            if (DASHBOARD_DEBUG) console.log(
                `Tarjeta ${index + 1}: Pedido=${numeroPedido}, Prenda=${nombrePrenda}, data-tipo-recibo="${tipoRecibo}"`
            );

            if (filtroPrincipal === 'todos') {
                card.style.display = '';
                const elementosFiltrables = card.querySelectorAll('[data-visible-filtro]');
                elementosFiltrables.forEach((elemento) => {
                    elemento.style.display = '';
                });
                mostradas++;
                return;
            }

            const tipos = tipoRecibo ? tipoRecibo.split(',').map((t) => t.trim()) : [];
            const coincideFiltroPrincipal = tipos.includes(filtroPrincipal);
            const atributoSinEncargado =
                filtroPrincipal === 'reflectivo' ? 'sinEncargadoReflectivo' : 'sinEncargadoCostura';
            const coincideFiltroEncargado =
                filtroEncargado !== 'sin-encargado' || String(card.dataset[atributoSinEncargado] || '0') === '1';

            if (coincideFiltroPrincipal && coincideFiltroEncargado) {
                if (DASHBOARD_DEBUG) console.log(`  Mostrando (contiene "${filtroPrincipal}" en [${tipos.join(', ')}])`);
                card.style.display = '';
                const elementosFiltrables = card.querySelectorAll('[data-visible-filtro]');
                elementosFiltrables.forEach((elemento) => {
                    const filtrosElemento = (elemento.dataset.visibleFiltro || '')
                        .split(',')
                        .map((valor) => valor.trim())
                        .filter(Boolean);

                    elemento.style.display = filtrosElemento.includes(filtroPrincipal) ? '' : 'none';
                });
                mostradas++;
            } else {
                if (DASHBOARD_DEBUG) console.log(`  Ocultando (no coincide con filtros activos)`);
                card.style.display = 'none';
                ocultadas++;
            }
        });

        // Actualizar paginacion despues de aplicar filtros
        window.__resetDashboardPagination?.();

        // Ordenar tarjetas segun el filtro activo (especialmente para vista-costura)
        ordenarTarjetas(filtroPrincipal);

        if (DASHBOARD_DEBUG) console.log(` [FILTRO] Filtro completado: ${mostradas} mostradas, ${ocultadas} ocultadas`);
    }

    /**
     * Ordena las tarjetas en el DOM segun el filtro activo
     */
    function ordenarTarjetas(filtro) {
        const ordenesList = document.getElementById('ordenesList');
        if (!ordenesList) return;

        const cards = Array.from(ordenesList.querySelectorAll('.orden-card-simple'));
        if (cards.length === 0) return;
        const userRole = document.querySelector('.operario-dashboard')?.dataset?.userRole || '';
        const esLiderReflectivo = userRole === 'lider-reflectivo';

        if (DASHBOARD_DEBUG) console.log(` [ORDENAMIENTO] Ordenando tarjetas para filtro: ${filtro}`);

        cards.sort((a, b) => {
            let valA, valB;
            if (filtro === 'reflectivo') {
                valA = parseInt(a.dataset.fechaCreacionReflectivo || '0');
                valB = parseInt(b.dataset.fechaCreacionReflectivo || '0');
                return valA - valB;
            }

            if (filtro === 'costura' && userRole === 'vista-costura') {
                valA = parseInt(a.dataset.fechaCreacionCostura || '0');
                valB = parseInt(b.dataset.fechaCreacionCostura || '0');
                return valA - valB;
            }

            if (esLiderReflectivo) {
                // lider-reflectivo (costura): ordenar por fecha_de_asignacion_encargado
                valA = parseInt(a.dataset.fechaAsignacionCostura || a.dataset.fechaCreacionCostura || '0');
                valB = parseInt(b.dataset.fechaAsignacionCostura || b.dataset.fechaCreacionCostura || '0');
                return valB - valA;
            }

            // Costura (otros roles): mantener mas reciente primero
            valA = parseInt(a.dataset.fechaAsignacionCostura || '0');
            valB = parseInt(b.dataset.fechaAsignacionCostura || '0');
            return valB - valA;
        });

        // Re-insertar en el DOM en el nuevo orden
        const fragment = document.createDocumentFragment();
        cards.forEach(card => fragment.appendChild(card));
        ordenesList.appendChild(fragment);
    }


    window.filtrarPrendasPorRecibo = function (filtro) {
        const userRole = document.querySelector('.operario-dashboard')?.dataset?.userRole || '';
        if (userRole === 'vista-costura') {
            const url = new URL(window.location.href);
            url.searchParams.set('filtro', filtro);
            url.searchParams.delete('page');
            window.location.href = url.toString();
            return;
        }
        window.__dashboardFiltroPrincipalActivo = filtro;
        document.querySelectorAll('.badge-filtro[data-filtro]').forEach((btn) => {
            btn.classList.remove('badge-filtro-active');
        });
        const btnFiltro = document.querySelector(`[data-filtro="${filtro}"]`);
        if (btnFiltro) {
            btnFiltro.classList.add('badge-filtro-active');
        }

        // Si estamos saliendo de Control de Calidad, recargar los datos normales
        if (window.__enModoControlCalidad === true && filtro !== 'control-calidad') {
            if (DASHBOARD_DEBUG) console.log('[SALIENDO DE CC] Reabilitando filtros normales');
            window.__enModoControlCalidad = false;
            recargarRecibosNormales();
            return;
        }

        aplicarTemaDashboard(filtro);
        aplicarFiltrosDashboard(filtro);
        window.__applyDashboardSearchFilter?.();
    };

    // Funcion para recargar recibos normales sin recargar la pagina
    function recargarRecibosNormales() {
        const ordenesList = document.getElementById('ordenesList');
        if (!ordenesList) return;

        if (DASHBOARD_DEBUG) console.log('[RECARGAR RECIBOS] Restaurando recibos normales...');
        
        const htmlOriginal = window.__htmlRecibosOriginal?.();
        if (!htmlOriginal) {
            console.error('[RECARGAR RECIBOS] No hay HTML original guardado');
            return;
        }

        // Restaurar el HTML original
        ordenesList.innerHTML = htmlOriginal;
        if (DASHBOARD_DEBUG) console.log('[RECARGAR RECIBOS] Recibos normales restaurados');

        // Aplicar los filtros despues de restaurar
        aplicarTemaDashboard(obtenerFiltroPrincipalActivo());
        aplicarFiltrosDashboard(obtenerFiltroPrincipalActivo());
        window.__applyDashboardSearchFilter?.();
    }

    // Exponer funcion para reaplicar filtros (usada por realtime.js)
    window.reaplicarFiltrosDashboard = function () {
        const filtro = obtenerFiltroPrincipalActivo();
        aplicarTemaDashboard(filtro);
        aplicarFiltrosDashboard(filtro);
        window.__applyDashboardSearchFilter?.();
    };

    window.filtrarVistaCosturaEncargados = function (modo = 'todos') {
        const userRole = document.querySelector('.operario-dashboard')?.dataset?.userRole || '';
        window.__vistaCosturaEncargadoFiltro = modo;

        document.querySelectorAll('.badge-filtro[data-encargado-filtro]').forEach((btn) => {
            btn.classList.toggle('badge-filtro-active', btn.dataset.encargadoFiltro === modo);
        });

        // Si estamos viendo Control de Calidad, cualquier cambio de encargado debe restaurar el HTML normal
        if (window.__enModoControlCalidad === true) {
            window.__enModoControlCalidad = false;
            recargarRecibosNormales();
            return;
        }

        if (userRole === 'vista-costura') {
            const url = new URL(window.location.href);
            const current = url.searchParams.get('encargado') || 'todos';

            if (modo === 'todos') {
                url.searchParams.delete('encargado');
            } else {
                url.searchParams.set('encargado', modo);
            }
            url.searchParams.delete('page');
            if (current === modo) {
                actualizarBadgeSinEncargado(obtenerFiltroPrincipalActivo());
                return;
            }
            window.location.href = url.toString();
            return;
        }

        aplicarFiltrosDashboard(obtenerFiltroPrincipalActivo());
        window.__applyDashboardSearchFilter?.();
    };

    if (document.getElementById('vistaCosturaEncargadoFilters')) {
        const encargadosQuery = new URL(window.location.href).searchParams.get('encargado') || 'todos';
        window.__vistaCosturaEncargadoFiltro = window.__vistaCosturaEncargadoFiltro || encargadosQuery;
        window.filtrarVistaCosturaEncargados(window.__vistaCosturaEncargadoFiltro);
    } else {
        actualizarBadgeSinEncargado(obtenerFiltroPrincipalActivo());
        actualizarBadgeControlCalidad(obtenerFiltroPrincipalActivo());
    }

    window.__dashboardFiltroPrincipalActivo = obtenerFiltroPrincipalActivo();
    aplicarTemaDashboard(obtenerFiltroPrincipalActivo());

    // Funcion para cargar recibos en Control de Calidad
    window.filtrarControlCalidad = function() {
        const filtroPrincipal = obtenerFiltroPrincipalActivo();
        const tipoRecibo = filtroPrincipal === 'reflectivo' ? 'REFLECTIVO' : 'COSTURA';
        if (DASHBOARD_DEBUG) console.log('[FILTRO_CC] Cargando recibos en Control de Calidad:', tipoRecibo);
        cargarRecibosControlCalidad(tipoRecibo);
    };

    // Manejar click en boton "Control de calidad"
    window.cargarRecibosControlCalidad = function(tipoRecibo) {
        if (DASHBOARD_DEBUG) console.log('[CONTROL_CALIDAD_FILTRO] Cargando recibos en CC para tipo:', tipoRecibo);
        window.__enModoControlCalidad = true;
        
        const urlApi = `/operario/api/recibos/control-calidad/${encodeURIComponent(tipoRecibo)}`;
        
        fetch(urlApi, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (DASHBOARD_DEBUG) console.log('[CONTROL_CALIDAD_FILTRO] Respuesta:', data);
            
            if (data.success && data.data) {
                mostrarRecibosControlCalidad(data.data, tipoRecibo);
            } else {
                const ordenesList = document.getElementById('ordenesList');
                if (ordenesList) {
                    ordenesList.innerHTML = '<div style="padding: 2rem; text-align: center; color: #999;">No hay recibos en Control de Calidad para este tipo</div>';
                }
            }
        })
        .catch(error => {
            console.error('[CONTROL_CALIDAD_FILTRO] Error:', error);
            const ordenesList = document.getElementById('ordenesList');
            if (ordenesList) {
                ordenesList.innerHTML = '<div style="padding: 2rem; text-align: center; color: #f00;">Error al cargar recibos en Control de Calidad</div>';
            }
        });
    };

    // Funcion para mostrar recibos en Control de Calidad
    function mostrarRecibosControlCalidad(recibos, tipoRecibo) {
        const ordenesList = document.getElementById('ordenesList');
        if (!ordenesList) return;

        if (!recibos || recibos.length === 0) {
            ordenesList.innerHTML = '<div style="padding: 2rem; text-align: center; color: #999;">No hay recibos en Control de Calidad para este tipo</div>';
            return;
        }

        const htmlRecibos = recibos.map(recibo => {
            const botonAgregarNovedad = crearBotonAgregarNovedad({
                numeroPedido: recibo.numero_pedido,
                prendaId: recibo.prenda_id,
                nombrePrenda: recibo.nombre_prenda,
                numeroRecibo: recibo.consecutivo_actual,
            });
            const esParcial = Boolean(recibo.es_parcial || recibo.parcial_id || recibo.pedido_parcial_id || recibo.id_parcial);
            const parcialId = recibo.parcial_id || recibo.pedido_parcial_id || recibo.id_parcial || null;
            const consecutivoParcial = recibo.consecutivo_parcial || recibo.consecutivo_actual || '';
            const tipoReciboNav = esParcial ? 'PARCIAL' : tipoRecibo;
            const distribucionBtn = recibo.tiene_parciales 
                ? `<button class="btn-ver-distribucion" 
                        onclick="abrirDistribucionReciboCC(this, '${tipoRecibo}');"
                        data-recibo-id="${recibo.id}"
                        data-prenda-id="${recibo.prenda_id}"
                        data-numero-recibo="${recibo.consecutivo_actual}"
                        data-tipo-recibo="${tipoRecibo}">
                        <span class="material-symbols-rounded">share</span>
                        VER DISTRIBUCI�N
                  </button>`
                : '';

            return `
                <div class="orden-card-simple" 
                     data-numero="${recibo.numero_pedido}"
                     data-numero-recibo="${recibo.consecutivo_actual}"
                     data-prenda="${recibo.nombre_prenda.toLowerCase()}"
                     data-prenda-id="${recibo.prenda_id}"
                     data-cliente="${recibo.cliente.toLowerCase()}"
                     data-tipo-recibo="${String(tipoRecibo).toLowerCase()}">
                    
                    <div class="orden-body">
                        <div class="orden-left">
                            <div class="orden-top">
                                <div class="orden-numero-section">
                                    <h4 class="orden-numero">#${recibo.consecutivo_actual}</h4>
                                    <span class="estado-badge pendiente">EN CC</span>
                                </div>
                            </div>
                            
                            <div class="orden-cliente">
                                <p class="cliente-label">CLIENTE</p>
                                <p class="cliente-name">${recibo.cliente}</p>
                            </div>
                            
                            <div class="orden-prendas">
                                <p class="prendas-label"><strong>${recibo.nombre_prenda}</strong></p>
                            </div>
                        </div>
                        
                        <div class="orden-buttons">
                            <button class="btn-ver-recibo-parcial" 
                                    onclick="abrirDetallesRecibos('${recibo.numero_pedido}', ${recibo.prenda_id}, '${String(recibo.nombre_prenda || '').replace(/'/g, "\\'")}', '${tipoReciboNav}', ${parcialId ? Number(parcialId) : 'null'}, '${String(consecutivoParcial).replace(/'/g, "\\'")}')">
                                <span class="material-symbols-rounded">visibility</span>
                                VER RECIBO
                            </button>
                            ${botonAgregarNovedad}
                            ${distribucionBtn}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        ordenesList.innerHTML = htmlRecibos;
        
        // Reinicializar la paginaci�n despu�s de cargar los recibos de Control de Calidad
        window.__updateDashboardPagination?.();
    }

    // Funcion para abrir distribucion de recibos en CC
    window.abrirDistribucionReciboCC = function(btn, tipoRecibo) {
        const reciboId = btn.dataset.reciboId;
        const prendaId = btn.dataset.prendaId;
        const numeroRecibo = btn.dataset.numeroRecibo;
        const ordenCard = btn.closest('.orden-card-simple');

        if (DASHBOARD_DEBUG) console.log('[DISTRIBUCION_CC] Abriendo distribucion CC:', { reciboId, tipoRecibo });

        if (!reciboId) {
            console.error('[DISTRIBUCION_CC] No se pudo determinar el ID del recibo');
            return;
        }

        // Buscar si ya existe la secci�n de distribuci�n (como hermano de la orden-card)
        let distribucionSection = ordenCard?.nextElementSibling;
        
        // Validar que sea la secci�n de distribuci�n correcta
        if (distribucionSection && !distribucionSection.classList.contains('distribucion-parciales-cc-section')) {
            distribucionSection = null;
        }
        
        if (distribucionSection) {
            if (DASHBOARD_DEBUG) console.log('[DISTRIBUCION_CC] Secci�n encontrada, iniciando toggle');
            
            // Si ya existe, toggle (mostrar/ocultar)
            const isHidden = distribucionSection.style.display === 'none';
            distribucionSection.style.display = isHidden ? 'block' : 'none';
            
            // Cambiar el texto del bot�n
            btn.innerHTML = isHidden ? '<span class="material-symbols-rounded">visibility_off</span> OCULTAR' : '<span class="material-symbols-rounded">share</span> VER DISTRIBUCI�N';
            
            if (DASHBOARD_DEBUG) console.log('[DISTRIBUCION_CC] Toggle completado');
            return;
        }

        // Si no existe, obtener datos y crear
        obtennerDistribucionParciales_CC(reciboId, numeroRecibo, ordenCard, btn);
    };

    function obtennerDistribucionParciales_CC(reciboId, numeroRecibo, ordenCard, btn) {
        if (DASHBOARD_DEBUG) console.log('[DISTRIBUCION_CC] Obteniendo parciales del recibo:', reciboId);

        const urlApi = `/operario/api/recibos/${reciboId}/distribucion-control-calidad`;
        
        fetch(urlApi, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (DASHBOARD_DEBUG) console.log('[DISTRIBUCION_CC] Datos parseados:', data);
            
            if (data.success) {
                if (DASHBOARD_DEBUG) console.log('[DISTRIBUCION_CC] Parciales obtenidos exitosamente:', data);
                mostrarDistribucionCards_CC(data, numeroRecibo, ordenCard, btn);
            } else {
                console.error('[DISTRIBUCION_CC] Error en respuesta:', data.message);
                alert('Error: ' + (data.message || 'No se pudieron obtener los parciales'));
            }
        })
        .catch(error => {
            console.error('[DISTRIBUCION_CC] Error en fetch:', error);
            alert('Error al cargar distribuci�n');
        });
    }

    function mostrarDistribucionCards_CC(datos, numeroRecibo, ordenCard, btn) {
        const parciales = datos.parciales || [];
        const totalParciales = datos.total_parciales || 0;

        if (DASHBOARD_DEBUG) console.log('[DISTRIBUCION_CC CARDS] Preparando cards con', totalParciales, 'parciales');

        if (!ordenCard) {
            console.error('[DISTRIBUCION_CC CARDS] No se encontr� orden card');
            return;
        }

        // Crear el HTML de las tarjetas
        const cardsHTML = crearHTMLDistribucionCards_CC(parciales, numeroRecibo, totalParciales);

        // Crear contenedor de distribuci�n
        const distribucionSection = document.createElement('div');
        distribucionSection.className = 'distribucion-parciales-cc-section';
        distribucionSection.innerHTML = cardsHTML;

        // Insertar despues de la orden-card
        ordenCard.insertAdjacentElement('afterend', distribucionSection);

        // Cambiar el texto del bot�n a "OCULTAR"
        if (btn) {
            btn.innerHTML = '<span class="material-symbols-rounded">visibility_off</span> OCULTAR';
        }

        if (DASHBOARD_DEBUG) console.log('[DISTRIBUCION_CC CARDS] Cards insertadas en el DOM');
    }

    function crearHTMLDistribucionCards_CC(parciales, numeroRecibo, totalParciales) {
        if (totalParciales === 0) {
            return `
                <div class="parcial-card parcial-card-vacio">
                    <div class="parcial-header">
                        <h4 class="parcial-title">No hay parciales en Control de Calidad</h4>
                    </div>
                </div>
            `;
        }

        // Generar tarjetas para cada parcial
        const parcialCards = parciales.map((parcial, index) => {
            // Generar el HTML de tallas
            const tallas = parcial.tallas || [];
            let tallasHTML = '';
            
            if (tallas && tallas.length > 0) {
                // Agrupar por talla
                const tallasSumadas = tallas.reduce((acc, talla) => {
                    const key = (talla.talla || '').toUpperCase();
                    if (!acc[key]) {
                        acc[key] = 0;
                    }
                    acc[key] += talla.cantidad || 0;
                    return acc;
                }, {});

                tallasHTML = Object.entries(tallasSumadas)
                    .map(([talla, cantidad]) => `<span class="talla-item">${talla}: <strong>${cantidad}</strong></span>`)
                    .join('');
            }
            
            return `
                <div class="parcial-card" data-parcial-id="${parcial.id}">
                    <div class="parcial-header">
                        <div class="parcial-numero">
                            <h4 class="parcial-title">Parcial #${parcial.consecutivo_parcial}</h4>
                            <span class="parcial-tipo-recibo">${parcial.tipo_recibo}</span>
                        </div>
                        <span class="badge-estado badge-estado-control-calidad">
                            Control de Calidad
                        </span>
                    </div>
                    
                    <div class="parcial-body">
                        <div class="parcial-row">
                            <div class="parcial-info-group full-width">
                                <span class="parcial-label">Prenda</span>
                                <span class="parcial-value">
                                    ${parcial.nombre_prenda}
                                </span>
                            </div>
                        </div>

                        <div class="parcial-row">
                            <div class="parcial-info-group full-width">
                                <span class="parcial-label">Recibo Original</span>
                                <span class="parcial-value">
                                    Recibo #${parcial.consecutivo_parcial}
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

                        <div class="parcial-row parcial-acciones">
                            <button class="btn-ver-recibo-parcial" 
                                    onclick="verReciboParcial(${parcial.id}, '${String(parcial.consecutivo_parcial).replace(/'/g, "\\'")}'  , '${parcial.pedido_numero}', ${parcial.prenda_id || 'null'})">
                                <span class="material-symbols-rounded">visibility</span>
                                VER RECIBO
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        return parcialCards;
    }
}
