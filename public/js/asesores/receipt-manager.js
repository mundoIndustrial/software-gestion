/**
 * ReceiptManager
 * Gestiona la navegación y visualización de recibos dinámicos
 * Reutiliza la estructura y estilos del order-detail-modal.blade.php
 */
class ReceiptManager {
    constructor(datosFactura, prendasIndex = null, contenedorId = null) {
        // ===== DEBUG: Verificar datos de entrada =====
        console.group('[ReceiptManager] ✅ CONSTRUCTOR INICIADO');
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
        console.log('✅ [ReceiptManager] Constructor completado. Recibos generados:', this.recibos.length);
        this.renderizar();
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
            
            if (prenda.procesos && Array.isArray(prenda.procesos)) {
                console.log(`  - Procesando ${prenda.procesos.length} procesos`);
                prenda.procesos.forEach((proceso, procesoIdx) => {
                    // Usar nombre_proceso o tipo_proceso como fallback (campos que viene del backend)
                    const nombreProceso = proceso.nombre_proceso || proceso.tipo_proceso || proceso.nombre || 'Proceso';
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
        const contenido = this.generarContenido(recibo);
        
        // Establecer el contenido
        document.getElementById('descripcion-text').innerHTML = contenido;
        
        // Actualizar ancho y metraje específico para esta prenda
        this.actualizarAnchoMetrajePorPrenda(recibo.prenda);

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
        
        // Buscar los elementos existentes en el recibo
        const anchoSpan = document.getElementById('ancho-valor');
        const metrajeSpan = document.getElementById('metraje-valor');
        
        if (anchoSpan && metrajeSpan) {
            // Si la prenda tiene datos de ancho/metraje, usarlos
            if (prenda.ancho_metraje && (prenda.ancho_metraje.ancho || prenda.ancho_metraje.metraje)) {
                anchoSpan.textContent = prenda.ancho_metraje.ancho + ' m';
                metrajeSpan.textContent = prenda.ancho_metraje.metraje + ' m';
                
                console.log('[ReceiptManager] Ancho/Metraje actualizado para prenda:', {
                    prenda: prenda.nombre,
                    ancho: prenda.ancho_metraje.ancho,
                    metraje: prenda.ancho_metraje.metraje
                });
            } else {
                // Si no hay datos para esta prenda, mostrar guiones
                anchoSpan.textContent = '--';
                metrajeSpan.textContent = '--';
                
                console.log('[ReceiptManager] Sin datos de ancho/metraje para prenda, mostrando guiones:', {
                    prenda: prenda.nombre
                });
            }
        } else {
            console.log('[ReceiptManager] No se encontraron los elementos ancho-valor o metraje-valor en el DOM');
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
     * Formato obligatorio con 5 bloques enumerados con puntos
     */
    construirDescripcionCostura(prenda) {
        const lineas = [];

        //  Nombre de la prenda (título)
        if (prenda.nombre) {
            const numeroPrenda = prenda.numero || 1;
            lineas.push(`<strong style="font-size: 12px;">PRENDA ${numeroPrenda}: ${prenda.nombre.toUpperCase()}</strong>`);
        }

        //  Línea técnica (una sola línea)
        const lineaTecnica = this.armarLineaTecnica(prenda);
        if (lineaTecnica) {
            lineas.push(lineaTecnica);
        }

        //  Descripción base de la prenda (si existe) - SIN ETIQUETA
        if (prenda.descripcion && prenda.descripcion.trim()) {
            lineas.push(prenda.descripcion.toUpperCase());
        }

        // 4️⃣ Detalles técnicos enumerados con puntos (sin asteriscos) - SIN ETIQUETA
        const detallesTecnicos = this.armarDetallesTecnicos(prenda);
        if (detallesTecnicos.length > 0) {
            detallesTecnicos.forEach((detalle) => {
                lineas.push(`• ${detalle}`);
            });
        }

        // 5️⃣ Tallas (bloque final)
        const tallasBloques = this.armarTallasBloques(prenda);
        if (tallasBloques.length > 0) {
            lineas.push('');
            lineas.push('<strong>TALLAS</strong>');
            tallasBloques.forEach(bloque => {
                lineas.push(bloque);
            });
        }

        return lineas.join('<br>') || '<em>Sin información</em>';
    }

    /**
     * Armar línea técnica: TELA: ... | COLOR: ... | REF: ... | MANGA: ...
     */
    armarLineaTecnica(prenda) {
        const partes = [];

        if (prenda.tela) {
            partes.push(`<strong>TELA:</strong> ${prenda.tela.toUpperCase()}`);
        }

        if (prenda.color) {
            partes.push(`<strong>COLOR:</strong> ${prenda.color.toUpperCase()}`);
        }

        if (prenda.ref || prenda.referencia) {
            const ref = prenda.ref || prenda.referencia;
            partes.push(`<strong>REF:</strong> ${ref.toUpperCase()}`);
        }

        // Manga (de variantes, sin repetir por talla)
        if (prenda.variantes && Array.isArray(prenda.variantes) && prenda.variantes.length > 0) {
            const primerVariante = prenda.variantes[0];
            if (primerVariante.manga) {
                let mangaTexto = primerVariante.manga.toUpperCase();
                if (primerVariante.manga_obs && primerVariante.manga_obs.trim()) {
                    mangaTexto += ` (${primerVariante.manga_obs.toUpperCase()})`;
                }
                partes.push(`<strong>MANGA:</strong> ${mangaTexto}`);
            }
        }

        return partes.length > 0 ? partes.join(' | ') : null;
    }

    /**
     * Armar detalles técnicos enumerados (sin asteriscos)
     * Reglas:
     * - Mostrar BOLSILLOS solo si existe
     * - Mostrar BOTÓN o BROCHE una sola vez
     * - No repetir por talla
     * - Si no hay observaciones, no mostrar el ítem
     */
    armarDetallesTecnicos(prenda) {
        const detalles = [];

        if (!prenda.variantes || !Array.isArray(prenda.variantes) || prenda.variantes.length === 0) {
            return detalles;
        }

        // Tomar primer variante para obtener datos únicos (no repetir por talla)
        const primerVariante = prenda.variantes[0];

        // BOLSILLOS - mostrar si tiene observaciones (independiente del booleano tiene_bolsillos)
        if (primerVariante.bolsillos_obs && primerVariante.bolsillos_obs.trim()) {
            detalles.push(`<strong>BOLSILLOS:</strong> ${primerVariante.bolsillos_obs.toUpperCase()}`);
        }

        // BROCHE/BOTÓN - usar el nombre del tipo que ya viene del backend
        if (primerVariante.broche_obs && primerVariante.broche_obs.trim()) {
            let etiqueta = 'BROCHE/BOTÓN';
            
            // El backend ya carga primerVariante.broche con el nombre del tipo
            if (primerVariante.broche) {
                etiqueta = primerVariante.broche.toUpperCase();
            }
            
            detalles.push(`<strong>${etiqueta}:</strong> ${primerVariante.broche_obs.toUpperCase()}`);
        }

        return detalles;
    }

    /**
     * Armar bloques de tallas
     * Formato: 
     *   DAMA: S: 10, M: 20
     *   CABALLERO: M: 10
     * Orden: DAMA → CABALLERO
     */
    armarTallasBloques(prenda) {
        const bloques = [];

        if (!prenda.tallas || Object.keys(prenda.tallas).length === 0) {
            return bloques;
        }

        // Separar tallas por género si existe esa información
        const tallasDama = {};
        const tallasCalballero = {};
        
        // Procesar tallas - pueden venir ANIDADAS: {"dama": {"L": 30, "S": 20}}
        Object.entries(prenda.tallas).forEach(([key, value]) => {
            //  Si value es un OBJETO (anidado: {"L": 30, "S": 20})
            if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
                // Desanidar: {"dama": {"L": 30}} → tallasDama["L"] = 30
                const genero = key.toLowerCase();
                Object.entries(value).forEach(([talla, cantidad]) => {
                    if (genero === 'dama') {
                        tallasDama[talla] = cantidad;
                    } else if (genero === 'caballero') {
                        tallasCalballero[talla] = cantidad;
                    }
                });

            } 
            //  Si value es un NÚMERO (aplanado: "dama-L" → 30)
            else if (typeof value === 'number' || typeof value === 'string') {
                // Aplanado: "dama-L" → 30
                if (key.includes('-')) {
                    const [genero, talla] = key.split('-');
                    if (genero.toLowerCase() === 'dama') {
                        tallasDama[talla] = value;
                    } else if (genero.toLowerCase() === 'caballero') {
                        tallasCalballero[talla] = value;
                    }
                } else {
                    // Si no tiene '-', usar género de prenda
                    const genero = prenda.genero || 'dama';
                    if (genero.toLowerCase() === 'dama') {
                        tallasDama[key] = value;
                    } else if (genero.toLowerCase() === 'caballero') {
                        tallasCalballero[key] = value;
                    }
                }

            } else {

            }
        });

        // Construir bloques
        if (Object.keys(tallasDama).length > 0) {
            const tallasStr = Object.entries(tallasDama)
                .map(([talla, cant]) => `<span style="color: red;"><strong>${talla}: ${cant}</strong></span>`)
                .join(', ');
            bloques.push(`DAMA: ${tallasStr}`);
        }

        if (Object.keys(tallasCalballero).length > 0) {
            const tallasStr = Object.entries(tallasCalballero)
                .map(([talla, cant]) => `<span style="color: red;"><strong>${talla}: ${cant}</strong></span>`)
                .join(', ');
            bloques.push(`CABALLERO: ${tallasStr}`);
        }

        return bloques;
    }

    /**
     * Formato fallback sin enumeración (para otros tipos de costura)
     */
    contenidoCosturaSinFormato(prenda) {
        let html = `<strong>${prenda.nombre.toUpperCase()}</strong><br><br>`;

        if (prenda.color) {
            html += `<strong>Color:</strong> ${prenda.color.toUpperCase()}<br>`;
        }

        if (prenda.tela) {
            html += `<strong>Tela:</strong> ${prenda.tela.toUpperCase()}<br>`;
        }

        if (prenda.origen) {
            const origenTexto = prenda.origen.toLowerCase() === 'bodega' ? 'BODEGA' : 'CONFECCIÓN';
            html += `<strong>Origen:</strong> ${origenTexto}<br>`;
        }

        if (prenda.tallas) {
            html += `<br><strong>TALLAS:</strong><br>`;
            const tallasArr = [];
            Object.entries(prenda.tallas).forEach(([talla, cant]) => {
                tallasArr.push(`${talla}: ${cant}`);
            });
            html += tallasArr.join(' | ');
        }

        return html || '<em>Sin información</em>';
    }

    /**
     * Generar contenido para recibo de PROCESO
     */
    contenidoProceso(proceso, prenda) {
        // Usar nombre_proceso o tipo_proceso (campos que vienen del backend), con fallback a nombre
        const nombreProceso = proceso.nombre_proceso || proceso.tipo_proceso || proceso.nombre || 'Proceso';
        
        let html = `<strong>${nombreProceso.toUpperCase()}</strong><br>`;
        html += `<em>${prenda.nombre.toUpperCase()}</em><br><br>`;

        if (proceso.observaciones) {
            html += `<strong>Observaciones:</strong> ${proceso.observaciones.toUpperCase()}<br><br>`;
        }

        if (proceso.ubicaciones && Array.isArray(proceso.ubicaciones) && proceso.ubicaciones.length > 0) {
            html += `<strong>Ubicaciones:</strong><br>`;
            proceso.ubicaciones.forEach(loc => {
                html += `• ${loc.toUpperCase()}<br>`;
            });
            html += '<br>';
        }

        if (proceso.tallas) {
            html += `<strong>TALLAS:</strong><br>`;
            const tallasArr = [];
            Object.entries(proceso.tallas).forEach(([talla, cant]) => {
                tallasArr.push(`${talla}: ${cant}`);
            });
            html += tallasArr.join(' | ');
        }

        if (proceso.imagenes && proceso.imagenes.length > 0) {
            html += `<br><br><strong>IMÁGENES DE REFERENCIA:</strong><br>`;
            proceso.imagenes.forEach((img, idx) => {
                html += `[Imagen ${idx + 1}] `;
            });
        }

        return html || '<em>Sin información</em>';
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


