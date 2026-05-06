/**
 * ReceiptManager
 * Gestiona la navegación y visualización de recibos dinámicos
 * Reutiliza la estructura y estilos del order-detail-modal.blade.php
 */

// Variable para almacenar Formatters cuando se cargue el módulo
let FormattersCostura = null;

// Función para cargar Formatters de manera asíncrona
async function cargarFormattersCostura() {
    if (FormattersCostura) return;
    
    try {
        // Intentar cargar el módulo ES6 de Formatters
        const moduloFormatters = await import('/js/modulos/pedidos-recibos/utils/Formatters.js');
        FormattersCostura = moduloFormatters.Formatters;
        console.log('[ReceiptManager] Formatters cargado exitosamente:', FormattersCostura);
    } catch (error) {
        console.warn('[ReceiptManager] No se pudo cargar Formatters:', error);
        FormattersCostura = null;
    }
}

class ReceiptManager {
    constructor(datosFactura, prendasIndex = null, contenedorId = null) {
        // ===== DEBUG: Verificar datos de entrada =====
        console.group('[ReceiptManager]  CONSTRUCTOR INICIADO');
        console.log('═══════════════════════════════════════════════════════════');
        console.log('📥 PARÁMETROS RECIBIDOS EN CONSTRUCTOR:');
        console.log('  datosFactura.cliente:', datosFactura.cliente);
        console.log('  datosFactura.asesor:', datosFactura.asesor);
        console.log('  datosFactura.asesora:', datosFactura.asesora);
        console.log('  datosFactura.forma_de_pago:', datosFactura.forma_de_pago);
        console.log('  datosFactura.numero_pedido:', datosFactura.numero_pedido);
        console.log('  datosFactura.prendas:', datosFactura.prendas);
        console.log('  Número de prendas:', datosFactura.prendas ? datosFactura.prendas.length : 'UNDEFINED');
        console.log('  prendasIndex filtro:', prendasIndex);
        console.log('═══════════════════════════════════════════════════════════');
        
        if (datosFactura.prendas && datosFactura.prendas.length > 0) {
            const primeraPrenda = datosFactura.prendas[0];
            console.group('Primera prenda - Análisis detallado:');
            console.log('  Campos disponibles:', Object.keys(primeraPrenda));
            console.log('  Tiene "procesos"?', 'procesos' in primeraPrenda);
            console.log('  procesos valor:', primeraPrenda.procesos);
            console.log('  procesos es array?', Array.isArray(primeraPrenda.procesos));
            console.log('  procesos length:', primeraPrenda.procesos ? primeraPrenda.procesos.length : 'N/A');
            console.groupEnd();
        }
        console.groupEnd();
        // ===== FIN DEBUG =====
        
        this.datosFactura = datosFactura;
        this.contenedorId = contenedorId;
        this.prendasIndex = prendasIndex;  // ← Índice de prenda para filtrar (null = todas)
        
        // Generar todos los recibos pero después filtrar
        const todosRecibos = this.generarRecibos(datosFactura);
        this.recibos = prendasIndex !== null ? this.filtrarRecibosDePrend(todosRecibos, prendasIndex) : todosRecibos;
        this.indexActual = 0;

        if (prendasIndex !== null) {

        }

        this.inicializarEventos();
        this.crearSelectorPrendas();
        console.log(' [ReceiptManager] Constructor completado. Recibos generados:', this.recibos.length);
        
        // IMPORTANTE: Esperar a que Formatters cargue ANTES de renderizar
        cargarFormattersCostura().then(() => {
            console.log('[ReceiptManager] Formatters cargado. Procediendo a renderizar...');
            this.renderizar();
        }).catch(err => {
            console.warn('[ReceiptManager] Error al cargar Formatters, usando fallback:', err);
            this.renderizar();
        });
    }

    /**
     * Filtrar recibos de una prenda específica
     */
    filtrarRecibosDePrend(recibos, prendasIndex) {
        return recibos.filter(r => r.prendaIndex === prendasIndex);
    }

    /**
     * Generar array de recibos desde datos del pedido
     */
    generarRecibos(datosFactura) {
        const recibos = [];
        console.group('[ReceiptManager.generarRecibos] Procesando prendas');
        console.log('Total de prendas a procesar:', datosFactura.prendas.length);
        
        datosFactura.prendas.forEach((prenda, prendaIdx) => {
            console.group(`Procesando Prenda ${prendaIdx}: ${prenda.nombre}`);
            
            // 1. Agregar recibo de COSTURA para la prenda
            let tituloCostura = "RECIBO DE COSTURA";
            if (prenda.de_bodega == 1) {
                tituloCostura = "RECIBO DE COSTURA-BODEGA";
            }

            recibos.push({
                numero: recibos.length + 1,
                prendaIndex: prendaIdx,
                procesoIndex: null,
                prenda: prenda,
                proceso: null,
                titulo: tituloCostura,
                subtitulo: `PRENDA ${prenda.numero}: ${prenda.nombre.toUpperCase()}`
            });
            
            console.log(`✓ Agregado: "${tituloCostura}"`);

            // 2. Agregar recibo para cada PROCESO de la prenda
            console.log('Verificando procesos:');
            console.log('  - prenda.procesos existe?', 'procesos' in prenda);
            console.log('  - prenda.procesos valor:', prenda.procesos);
            console.log('  - Es array?', Array.isArray(prenda.procesos));
            console.log('  - de_bodega:', prenda.de_bodega);
            
            if (prenda.procesos && Array.isArray(prenda.procesos)) {
                console.log(`  - Procesando ${prenda.procesos.length} procesos`);
                prenda.procesos.forEach((proceso, procesoIdx) => {
                    // Usar nombre_proceso o tipo_proceso como fallback (campos que viene del backend)
                    const nombreProceso = proceso.nombre_proceso || proceso.tipo_proceso || proceso.nombre || 'Proceso';
                    const esReflectivo = (nombreProceso.toLowerCase() === 'reflectivo');
                    
                    // FILTRO: No crear recibo separado para Reflectivo cuando de_bodega = false
                    // (El Reflectivo ya aparece dentro de la descripción de costura)
                    if (esReflectivo && !prenda.de_bodega) {
                        console.log(`    Proceso ${procesoIdx}: "${nombreProceso}" - ⏭️ IGNORADO (Reflectivo con de_bodega=false)`);
                        return; // Skip este proceso
                    }
                    
                    console.log(`    Proceso ${procesoIdx}: "${nombreProceso}"`);
                    
                    recibos.push({
                        numero: recibos.length + 1,
                        prendaIndex: prendaIdx,
                        procesoIndex: procesoIdx,
                        prenda: prenda,
                        proceso: proceso,
                        titulo: `RECIBO DE ${nombreProceso.toUpperCase()}`,
                        subtitulo: `PRENDA ${prenda.numero}: ${prenda.nombre.toUpperCase()}`
                    });
                });
            } else {
                console.log('  -  Sin procesos o procesos no es array');
            }
            
            console.groupEnd();
        });

        // Actualizar total en cada recibo
        const total = recibos.length;
        recibos.forEach(r => r.total = total);
        
        console.log('Total de recibos generados:', total);
        console.log('Recibos:', recibos);
        console.groupEnd();

        return recibos;
    }

    /**
     * Inicializar eventos de botones
     */
    inicializarEventos() {
        const btnAnterior = document.getElementById('prev-arrow');
        const btnSiguiente = document.getElementById('next-arrow');
        const btnCerrar = document.getElementById('close-receipt-btn');

        if (btnAnterior) {
            btnAnterior.addEventListener('click', () => this.navegar('anterior'));
        }
        
        if (btnSiguiente) {
            btnSiguiente.addEventListener('click', () => this.navegar('siguiente'));
        }
        
        // Botón cerrar (ya viene del close-receipt-btn agregado en invoice-from-list.js)
        if (btnCerrar) {
            btnCerrar.addEventListener('click', () => this.cerrar());
        }
    }

    /**
     * Crear selector de prendas en el arrow-container
     */
    crearSelectorPrendas() {
        const arrowContainer = document.querySelector('.arrow-container');
        if (!arrowContainer) return;

        // Solo crear si:
        // 1. Hay más de una prenda
        // 2. Se abrió sin filtro de prenda (prendasIndex === null)
        if (this.datosFactura.prendas.length <= 1 || this.prendasIndex !== null) return;

        // Crear selector
        const selector = document.createElement('select');
        selector.id = 'prenda-selector';
        selector.style.cssText = `
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            background: white;
            color: #374151;
            font-weight: 500;
        `;

        // Opción "Todas las prendas"
        const optionTodas = document.createElement('option');
        optionTodas.value = '';
        optionTodas.text = ' Todas las prendas';
        optionTodas.selected = true;
        selector.appendChild(optionTodas);

        // Opciones para cada prenda
        this.datosFactura.prendas.forEach((prenda, idx) => {
            const option = document.createElement('option');
            option.value = idx;
            option.text = `${idx + 1}. ${prenda.nombre.toUpperCase()}`;
            selector.appendChild(option);
        });

        // Event listener para cambiar de prenda
        selector.addEventListener('change', (e) => {
            const valor = e.target.value;
            const prendasIndex = valor === '' ? null : parseInt(valor);
            this.cambiarPrend(prendasIndex);
        });

        // Insertar antes del contador
        const counter = arrowContainer.querySelector('#receipt-counter');
        if (counter) {
            arrowContainer.insertBefore(selector, counter);
            selector.style.marginRight = '1rem';
        } else {
            arrowContainer.appendChild(selector);
        }
    }

    /**
     * Cambiar prenda seleccionada y filtrar recibos
     */
    cambiarPrend(prendasIndex) {

        
        this.prendasIndex = prendasIndex;
        
        // Regenerar recibos según la prenda seleccionada
        const todosRecibos = this.generarRecibos(this.datosFactura);
        this.recibos = prendasIndex !== null ? this.filtrarRecibosDePrend(todosRecibos, prendasIndex) : todosRecibos;
        
        // Resetear a primer recibo
        this.indexActual = 0;
        

        
        // Renderizar el primer recibo de la nueva prenda
        this.renderizar();
    }

    /**
     * Navegar entre recibos
     */
    navegar(direccion) {
        if (direccion === 'siguiente' && this.indexActual < this.recibos.length - 1) {
            this.indexActual++;
            this.renderizar();
        } else if (direccion === 'anterior' && this.indexActual > 0) {
            this.indexActual--;
            this.renderizar();
        }
    }

    /**
     * Renderizar recibo actual en el DOM
     */
    renderizar() {
        const recibo = this.recibos[this.indexActual];
        
        console.log('═══════════════════════════════════════════════════════════');
        console.log('[ReceiptManager.renderizar] INICIANDO RENDERIZACIÓN');
        console.log('═══════════════════════════════════════════════════════════');
        console.log('Recibo actual:', recibo);
        console.log('datosFactura completo:', this.datosFactura);
        console.log('═══════════════════════════════════════════════════════════');

        // Actualizar contador
        document.getElementById('receipt-number').textContent = recibo.numero;
        document.getElementById('receipt-total').textContent = recibo.total;

        // Actualizar fecha
        const fechaAMostrar = this.datosFactura.fecha || this.datosFactura.fecha_creacion;
        console.log('[ReceiptManager] DEBUG FECHA:', {
            'datosFactura.fecha': this.datosFactura.fecha,
            'datosFactura.fecha_creacion': this.datosFactura.fecha_creacion,
            'fechaAMostrar': fechaAMostrar
        });
        this.actualizarFecha(fechaAMostrar);

        // Limpiar datos antiguos de ancho/metraje
        this.limpiarDatosAntiguos();

        // Actualizar información básica
        console.log('─────────────────────────────────────────────────────────────');
        console.log('[ReceiptManager.renderizar] ACTUALIZANDO CAMPOS:');
        console.log('─────────────────────────────────────────────────────────────');
        console.log('1. ASESOR:');
        console.log('   - datosFactura.asesor:', this.datosFactura.asesor);
        
        // Actualizar ancho y metraje si hay datos disponibles
        if (window.datosAnchoMetraje) {
            console.log('[ReceiptManager] Actualizando ancho y metraje en el recibo...');
            this.actualizarAnchoMetraje();
        }
        
        console.log('   - datosFactura.asesora:', this.datosFactura.asesora);
        const asesorElem = document.getElementById('asesora-value');
        console.log('   - Elemento #asesora-value existe?', !!asesorElem);
        if (asesorElem) {
            const valorAsesor = this.datosFactura.asesor || this.datosFactura.asesora || 'N/A';
            asesorElem.textContent = valorAsesor;
            console.log('   - ✓ Asignado:', valorAsesor);
            console.log('   - Contenido ahora:', asesorElem.textContent);
        } else {
            console.log('   - ✗ ELEMENTO NO ENCONTRADO');
        }
        
        console.log('2. FORMA DE PAGO:');
        console.log('   - datosFactura.forma_de_pago:', this.datosFactura.forma_de_pago);
        const formaPagoElem = document.getElementById('forma-pago-value');
        console.log('   - Elemento #forma-pago-value existe?', !!formaPagoElem);
        if (formaPagoElem) {
            const valorFormaPago = this.datosFactura.forma_de_pago || 'N/A';
            formaPagoElem.textContent = valorFormaPago;
            console.log('   - ✓ Asignado:', valorFormaPago);
            console.log('   - Contenido ahora:', formaPagoElem.textContent);
        } else {
            console.log('   - ✗ ELEMENTO NO ENCONTRADO');
        }
        
        console.log('3. CLIENTE:');
        console.log('   - datosFactura.cliente:', this.datosFactura.cliente);
        const clienteElem = document.getElementById('cliente-value');
        console.log('   - Elemento #cliente-value existe?', !!clienteElem);
        if (clienteElem) {
            const valorCliente = this.datosFactura.cliente || 'N/A';
            clienteElem.textContent = valorCliente;
            console.log('   - ✓ Asignado:', valorCliente);
            console.log('   - Contenido ahora:', clienteElem.textContent);
        } else {
            console.log('   - ✗ ELEMENTO NO ENCONTRADO');
        }
        console.log('─────────────────────────────────────────────────────────────');

        // Actualizar título (DINÁMICO)
        document.getElementById('receipt-title').textContent = recibo.titulo;

        // Actualizar número de pedido
        document.getElementById('order-pedido').textContent = 
            '#' + (this.datosFactura.numero_pedido || '00000');

        // Generar contenido del recibo
        let contenido = this.generarContenido(recibo);
        // Evitar desbordes: normalizar espacios no separables que bloquean el salto de línea
        contenido = String(contenido).replace(/&nbsp;|\u00a0/g, ' ');
        
        // Establecer el contenido
        document.getElementById('descripcion-text').innerHTML = contenido;
        
        // Cargar y agregar metrajes por color a la descripción
        if (recibo.prenda && recibo.prenda.prenda_pedido_id) {
            this.agregarMetrajesPorColor(recibo.prenda);
        }
        
        // Actualizar estado de botones
        this.actualizarBotones();
    }

    /**
     * Actualizar ancho y metraje específico para una prenda
     */
    actualizarAnchoMetrajePorPrenda(prenda) {
        console.log('[ReceiptManager] Actualizando ancho/metraje para prenda específica:', {
            nombre: prenda.nombre,
            id: prenda.id,
            prenda_pedido_id: prenda.prenda_pedido_id,
            tiene_ancho_metraje: !!prenda.ancho_metraje,
            ancho_metraje_valor: prenda.ancho_metraje
        });
        
        // NOTA: La sección de ancho/metraje ahora está escondida
        // Los metrajes por color se muestran directamente en la descripción de tallas
        console.log('[ReceiptManager] Datos de ancho/metraje para prenda:', {
            prenda: prenda.nombre,
            ancho_metraje: prenda.ancho_metraje
        });
    }

    /**
     * Cargar metrajes por color desde la API y agregarlos a la descripción
     */
    agregarMetrajesPorColor(prenda) {
        // No hacer nada si no tenemos ID de prenda
        if (!prenda.prenda_pedido_id || !this.datosFactura.numero_pedido) {
            console.log('[ReceiptManager] Sin ID de prenda o número de pedido, omitiendo carga de metrajes');
            return;
        }

        const pedido = this.datosFactura.numero_pedido;
        const prendaId = prenda.prenda_pedido_id;

        // Fetch async para obtener metrajes
        fetch(`/insumos/materiales/${pedido}/obtener-ancho-metraje-prenda/${prendaId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                    console.log('[ReceiptManager.agregarMetrajesPorColor] Sin datos de metraje');
                    return;
                }

                // Agrupar metrajes por color
                const metrajesPorColor = {};
                data.data.forEach(item => {
                    if (item.color) {
                        metrajesPorColor[item.color] = item.metraje;
                    }
                });

                console.log('[ReceiptManager.agregarMetrajesPorColor] Metrajes cargados:', metrajesPorColor);

                // Buscar y reemplazar colores en la descripción
                const descripcionEl = document.getElementById('descripcion-text');
                if (!descripcionEl) return;

                let html = descripcionEl.innerHTML;

                // Para cada color, buscar en el HTML y agregar el metraje
                Object.entries(metrajesPorColor).forEach(([color, metraje]) => {
                    // Buscar patrón como:
                    // <strong>VERDE MILITAR:</strong> S-2, M-6, XL-3
                    // o
                    // <span style="color: red;"><strong>VERDE MILITAR:</strong> S-2, M-6, XL-3</span>
                    
                    if (!metraje) {
                        console.log(`[ReceiptManager] Color ${color} sin metraje, omitiendo`);
                        return;
                    }
                    
                    const colorUpperCase = color.toUpperCase();
                    
                    // Buscar desde <strong>COLOR:</strong> hasta antes del siguiente <br, </span>, </div>, o fin de línea
                    // Patrón: <strong>VERDE MILITAR:</strong> .... [tallas] antes de <br o </span>
                    const regex = new RegExp(
                        `(<strong>${colorUpperCase}:</strong>\\s*[^<]*?\\d[^<]*)(<br|<\\/span>|<\\/div>|$)`,
                        'gi'
                    );
                    
                    const reemplazo = (match, contenido, cierre) => {
                        // Solo agregar metraje si no ya existe
                        if (!contenido.includes('Metraje:')) {
                            // Remover el cierre si existe, lo agregaremos después
                            const contenidoLimpio = contenido.trim();
                            return `${contenidoLimpio} - Metraje: ${metraje} m${cierre}`;
                        }
                        return match;
                    };
                    
                    html = html.replace(regex, reemplazo);
                    
                    console.log(`[ReceiptManager] Procesado color ${color} con metraje ${metraje}m`);
                });

                // Actualizar HTML
                descripcionEl.innerHTML = html;
                console.log('[ReceiptManager.agregarMetrajesPorColor] Descripción actualizada');
            })
            .catch(error => {
                console.warn('[ReceiptManager.agregarMetrajesPorColor] Error al cargar metrajes:', error);
            });
    }

    /**
     * Generar contenido según tipo de recibo
     */
    generarContenido(recibo) {
        if (recibo.procesoIndex === null) {
            // Es recibo de COSTURA
            return this.contenidoCostura(recibo.prenda);
        } else {
            // Es recibo de PROCESO
            return this.contenidoProceso(recibo.proceso, recibo.prenda);
        }
    }

    /**
     * Generar contenido para recibo de COSTURA
     * Aplica el nuevo formato enumerado con puntos
     */
    contenidoCostura(prenda) {
        // Validar tipo de costura - siempre es costura si llegó aquí
        // de_bodega solo indica si es de bodega o confección, pero ambas son COSTURA
        return this.construirDescripcionCostura(prenda);
    }

    /**
     * Actualizar fecha en el recibo
     */
    actualizarFecha(fechaStr) {
        if (!fechaStr) {
            // Intentar actualizar ambos conjuntos de elementos
            const dayBoxes = document.querySelectorAll('.day-box');
            const monthBoxes = document.querySelectorAll('.month-box');
            const yearBoxes = document.querySelectorAll('.year-box');
            
            dayBoxes.forEach(el => el.textContent = '--');
            monthBoxes.forEach(el => el.textContent = '--');
            yearBoxes.forEach(el => el.textContent = '----');
            
            document.getElementById('receipt-day').textContent = '--';
            document.getElementById('receipt-month').textContent = '--';
            document.getElementById('receipt-year').textContent = '----';
            return;
        }

        // Si la fecha es un string, intentar parsearla
        let fecha;
        if (typeof fechaStr === 'string') {
            // Formato esperado: DD/MM/YYYY o YYYY-MM-DD
            const partes = fechaStr.split('/');
            if (partes.length === 3) {
                // Formato DD/MM/YYYY
                fecha = new Date(partes[2], partes[1] - 1, partes[0]);
            } else {
                // Intentar formato YYYY-MM-DD
                fecha = new Date(fechaStr);
            }
        } else if (fechaStr instanceof Date) {
            fecha = fechaStr;
        } else {
            fecha = new Date();
        }

        if (!isNaN(fecha)) {
            const day = fecha.getDate();
            const month = fecha.getMonth() + 1;
            const year = fecha.getFullYear();
            
            console.log('[ReceiptManager.actualizarFecha] Fecha parseada correctamente:', {
                fechaOriginal: fechaStr,
                day,
                month,
                year
            });
            
            // Actualizar elementos visibles (.day-box, .month-box, .year-box)
            const dayBoxes = document.querySelectorAll('.day-box');
            const monthBoxes = document.querySelectorAll('.month-box');
            const yearBoxes = document.querySelectorAll('.year-box');
            
            console.log('[ReceiptManager.actualizarFecha] Elementos encontrados:', {
                dayBoxes: dayBoxes.length,
                monthBoxes: monthBoxes.length,
                yearBoxes: yearBoxes.length
            });
            
            dayBoxes.forEach(el => el.textContent = day);
            monthBoxes.forEach(el => el.textContent = month);
            yearBoxes.forEach(el => el.textContent = year);
            
            // Actualizar elementos ocultos (#receipt-day, #receipt-month, #receipt-year) para compatibilidad
            const receiptDay = document.getElementById('receipt-day');
            const receiptMonth = document.getElementById('receipt-month');
            const receiptYear = document.getElementById('receipt-year');
            
            if (receiptDay) receiptDay.textContent = day;
            if (receiptMonth) receiptMonth.textContent = month;
            if (receiptYear) receiptYear.textContent = year;
            
            console.log('[ReceiptManager.actualizarFecha] Fecha actualizada en elementos del DOM');
        }
    }

    /**
     * Generar contenido según tipo de recibo
     */
    generarContenido(recibo) {
        if (recibo.procesoIndex === null) {
            // Es recibo de COSTURA
            return this.contenidoCostura(recibo.prenda);
        } else {
            // Es recibo de PROCESO
            return this.contenidoProceso(recibo.proceso, recibo.prenda);
        }
    }

    /**
     * Generar contenido para recibo de COSTURA
     * Aplica el nuevo formato enumerado con puntos
     */
    contenidoCostura(prenda) {
        // Validar tipo de costura - siempre es costura si llegó aquí
        // de_bodega solo indica si es de bodega o confección, pero ambas son COSTURA
        return this.construirDescripcionCostura(prenda);
    }

    /**
     * Construir descripción dinámica para recibo de COSTURA/COSTURA-BODEGA
     * REUTILIZA Formatters.construirDescripcionCostura() de pedidos-recibos
     */
    construirDescripcionCostura(prenda) {
        // Usar Formatters (debe estar disponible en este punto)
        if (FormattersCostura && typeof FormattersCostura.construirDescripcionCostura === 'function') {
            console.log('[ReceiptManager.construirDescripcionCostura] Usando Formatters.construirDescripcionCostura');
            return FormattersCostura.construirDescripcionCostura(prenda);
        }
        
        // Si Formatters no está disponible, mostrar error
        console.error('[ReceiptManager.construirDescripcionCostura]  Formatters no disponible');
        return '<em style="color: red;">Error: No se pudo cargar el formateador de descripciones</em>';
    }



    /**
     * Generar contenido para recibo de PROCESO
     * REUTILIZA Formatters.construirDescripcionProceso() de pedidos-recibos
     */
    contenidoProceso(proceso, prenda) {
        // Usar Formatters para procesos (debe estar disponible en este punto)
        if (FormattersCostura && typeof FormattersCostura.construirDescripcionProceso === 'function') {
            console.log('[ReceiptManager.contenidoProceso] Usando Formatters.construirDescripcionProceso');
            return FormattersCostura.construirDescripcionProceso(prenda, proceso);
        }
        
        // Si Formatters no está disponible, mostrar error
        console.error('[ReceiptManager.contenidoProceso]  Formatters no disponible');
        return '<em style="color: red;">Error: No se pudo cargar el formateador de procesos</em>';
    }

    /**
     * Actualizar estado de botones
     */
    actualizarBotones() {
        const btnAnterior = document.getElementById('prev-arrow');
        const btnSiguiente = document.getElementById('next-arrow');

        if (btnAnterior) {
            btnAnterior.style.display = this.indexActual > 0 ? 'block' : 'none';
        }
        if (btnSiguiente) {
            btnSiguiente.style.display = this.indexActual < this.recibos.length - 1 ? 'block' : 'none';
        }
    }

    /**
     * Cerrar vista de recibos
     */
    cerrar() {
        const modal = document.getElementById('modal-recibos-overlay');
        if (modal) {
            modal.remove();
        }
    }
    
    /**
     * Actualiza el ancho y metraje en el recibo (sistema universal)
     */
    actualizarAnchoMetraje() {
        // Verificar si hay datos de ancho/metraje disponibles globalmente
        if (!window.datosAnchoMetraje) {
            console.log('[ReceiptManager] No hay datos de ancho/metraje disponibles globalmente');
            return;
        }
        
        const { ancho, metraje, pedido, fecha } = window.datosAnchoMetraje;
        
        // Verificar si los datos son recientes (máximo 24 horas)
        const fechaDatos = new Date(fecha);
        const ahora = new Date();
        const horasDiferencia = (ahora - fechaDatos) / (1000 * 60 * 60);
        
        if (horasDiferencia > 24) {
            console.log('[ReceiptManager] Datos de ancho/metraje muy antiguos, ignorando');
            return;
        }
        
        // Actualizar el flotante (si existe)
        let anchoMetrajeElement = document.getElementById('ancho-metraje-disponible');
        
        if (anchoMetrajeElement) {
            anchoMetrajeElement.innerHTML = `
                <div style="font-size: 0.65rem; opacity: 0.8; margin-bottom: 2px;">PEDIDO: ${pedido}</div>
                ANCHO DISPONIBLE: ${ancho.toFixed(2)} m<br>
                METRAJE DISPONIBLE: ${metraje.toFixed(2)} m
            `;
        }
        
        // Actualizar la línea del recibo HTML (si existe)
        const anchoSpan = document.getElementById('ancho-valor');
        const metrajeSpan = document.getElementById('metraje-valor');
        
        if (anchoSpan && metrajeSpan) {
            anchoSpan.textContent = ancho.toFixed(2) + ' m';
            metrajeSpan.textContent = metraje.toFixed(2) + ' m';
            
            console.log('[ReceiptManager] Valores actualizados en la línea del recibo HTML:', { ancho, metraje });
        }
        
        console.log('[ReceiptManager] Ancho y metraje actualizados en el recibo (sistema universal)');
    }
    
    /**
     * Limpia los datos de ancho y metraje si son muy antiguos
     */
    limpiarDatosAntiguos() {
        if (!window.datosAnchoMetraje) {
            return;
        }
        
        const fechaDatos = new Date(window.datosAnchoMetraje.fecha);
        const ahora = new Date();
        const horasDiferencia = (ahora - fechaDatos) / (1000 * 60 * 60);
        
        if (horasDiferencia > 24) {
            console.log('[ReceiptManager] Limpiando datos de ancho/metraje antiguos');
            delete window.datosAnchoMetraje;
            
            // Eliminar el elemento del DOM si existe
            const anchoMetrajeElement = document.getElementById('ancho-metraje-disponible');
            if (anchoMetrajeElement) {
                anchoMetrajeElement.remove();
            }
        }
    }
}

// Exportar para uso externo
window.ReceiptManager = ReceiptManager;

/**
 * Función global universal para actualizar ancho y metraje
 * Puede ser llamada desde cualquier módulo del sistema
 */
window.actualizarAnchoMetrajeUniversal = function(ancho, metraje, pedido = null) {
    console.log('[actualizarAnchoMetrajeUniversal] Actualizando datos globalmente...');
    console.log('  - Ancho:', ancho, 'm');
    console.log('  - Metraje:', metraje, 'm');
    console.log('  - Pedido:', pedido || 'No especificado');
    
    // Guardar los datos globalmente
    window.datosAnchoMetraje = {
        ancho: parseFloat(ancho),
        metraje: parseFloat(metraje),
        pedido: pedido || 'SIN PEDIDO',
        fecha: new Date().toISOString(),
        modulo: window.location.pathname // Para saber desde qué módulo se actualizó
    };
    
    // Si hay un ReceiptManager activo, actualizarlo inmediatamente
    if (window.receiptManager && typeof window.receiptManager.actualizarAnchoMetraje === 'function') {
        console.log('[actualizarAnchoMetrajeUniversal] Actualizando recibo activo...');
        window.receiptManager.actualizarAnchoMetraje();
    }
    
    // Disparar evento para que otros módulos puedan reaccionar
    const evento = new CustomEvent('anchoMetrajeActualizado', {
        detail: window.datosAnchoMetraje
    });
    window.dispatchEvent(evento);
    
    console.log('[actualizarAnchoMetrajeUniversal] Datos actualizados y evento disparado');
};

/**
 * Función global para obtener los datos actuales de ancho y metraje
 */
window.obtenerAnchoMetrajeActual = function() {
    return window.datosAnchoMetraje || null;
};

/**
 * Función global para limpiar los datos de ancho y metraje
 */
window.limpiarAnchoMetraje = function() {
    console.log('[limpiarAnchoMetraje] Limpiando datos globales...');
    delete window.datosAnchoMetraje;
    
    // Eliminar el elemento del DOM si existe
    const anchoMetrajeElement = document.getElementById('ancho-metraje-disponible');
    if (anchoMetrajeElement) {
        anchoMetrajeElement.remove();
    }
    
    // Disparar evento de limpieza
    const evento = new CustomEvent('anchoMetrajeLimpiado');
    window.dispatchEvent(evento);
    
    console.log('[limpiarAnchoMetraje] Datos limpiados y evento disparado');
};

