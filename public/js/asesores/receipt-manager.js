/**
 * ReceiptManager
 * Gestiona la navegación y visualización de recibos dinámicos
 * Reutiliza la estructura y estilos del order-detail-modal.blade.php
 */
class ReceiptManager {
    constructor(datosFactura, prendasIndex = null, contenedorId = null) {
        this.datosFactura = datosFactura;
        this.contenedorId = contenedorId;
        this.prendasIndex = prendasIndex;  // ← Índice de prenda para filtrar (null = todas)
        
        // Generar todos los recibos pero después filtrar
        const todosRecibos = this.generarRecibos(datosFactura);
        this.recibos = prendasIndex !== null ? this.filtrarRecibosDePrend(todosRecibos, prendasIndex) : todosRecibos;
        this.indexActual = 0;

        console.log(' [RECEIPT MANAGER] Inicializado');
        console.log(' Total de recibos:', this.recibos.length);
        if (prendasIndex !== null) {
            console.log(' Filtrado para prenda:', prendasIndex);
        }
        console.log('📄 Recibos:', this.recibos);

        this.inicializarEventos();
        this.crearSelectorPrendas();
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

        datosFactura.prendas.forEach((prenda, prendaIdx) => {
            // 1. Agregar recibo de COSTURA para la prenda
            let tituloCostura = "RECIBO DE COSTURA";
            if (prenda.origen && prenda.origen.toLowerCase() === 'bodega') {
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

            // 2. Agregar recibo para cada PROCESO de la prenda
            if (prenda.procesos && Array.isArray(prenda.procesos)) {
                prenda.procesos.forEach((proceso, procesoIdx) => {
                    recibos.push({
                        numero: recibos.length + 1,
                        prendaIndex: prendaIdx,
                        procesoIndex: procesoIdx,
                        prenda: prenda,
                        proceso: proceso,
                        titulo: `RECIBO DE ${proceso.nombre.toUpperCase()}`,
                        subtitulo: `PRENDA ${prenda.numero}: ${prenda.nombre.toUpperCase()}`
                    });
                });
            }
        });

        // Actualizar total en cada recibo
        const total = recibos.length;
        recibos.forEach(r => r.total = total);

        return recibos;
    }

    /**
     * Inicializar eventos de botones
     */
    inicializarEventos() {
        const btnAnterior = document.getElementById('prev-arrow');
        const btnSiguiente = document.getElementById('next-arrow');
        const btnImprimir = document.getElementById('print-receipt-btn');
        const btnCerrar = document.getElementById('close-receipt-btn');

        if (btnAnterior) {
            btnAnterior.addEventListener('click', () => this.navegar('anterior'));
        }
        if (btnSiguiente) {
            btnSiguiente.addEventListener('click', () => this.navegar('siguiente'));
        }
        
        // Crear botón imprimir si no existe
        if (!btnImprimir) {
            const container = document.querySelector('.signature-section') || document.querySelector('.order-detail-card');
            if (container) {
                const divBotones = document.createElement('div');
                divBotones.style.cssText = `
                    display: flex;
                    gap: 1rem;
                    margin-top: 2rem;
                    justify-content: flex-end;
                `;
                
                const printBtn = document.createElement('button');
                printBtn.id = 'print-receipt-btn';
                printBtn.innerHTML = '<i class="fas fa-print"></i> Imprimir';
                printBtn.style.cssText = `
                    background: #10b981;
                    color: white;
                    padding: 0.75rem 1.5rem;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: all 0.2s ease;
                `;
                printBtn.addEventListener('mouseover', () => printBtn.style.background = '#059669');
                printBtn.addEventListener('mouseout', () => printBtn.style.background = '#10b981');
                printBtn.addEventListener('click', () => this.imprimir());
                
                divBotones.appendChild(printBtn);
                container.appendChild(divBotones);
            }
        } else {
            btnImprimir.addEventListener('click', () => this.imprimir());
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
        console.log('🔄 [RECEIPT MANAGER] Cambiando a prenda:', prendasIndex);
        
        this.prendasIndex = prendasIndex;
        
        // Regenerar recibos según la prenda seleccionada
        const todosRecibos = this.generarRecibos(this.datosFactura);
        this.recibos = prendasIndex !== null ? this.filtrarRecibosDePrend(todosRecibos, prendasIndex) : todosRecibos;
        
        // Resetear a primer recibo
        this.indexActual = 0;
        
        console.log(' Recibos después de filtro:', this.recibos.length);
        
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
     * Renderizar recibo actual
     */
    renderizar() {
        const recibo = this.recibos[this.indexActual];

        console.log(`📄 Renderizando recibo ${recibo.numero}/${recibo.total}:`, recibo);

        // Actualizar contador
        document.getElementById('receipt-number').textContent = recibo.numero;
        document.getElementById('receipt-total').textContent = recibo.total;

        // Actualizar fecha
        this.actualizarFecha(this.datosFactura.fecha);

        // Actualizar información básica
        document.getElementById('receipt-asesora-value').textContent = 
            this.datosFactura.asesora || 'N/A';
        document.getElementById('receipt-forma-pago-value').textContent = 
            this.datosFactura.forma_de_pago || 'N/A';
        document.getElementById('receipt-cliente-value').textContent = 
            this.datosFactura.cliente || 'N/A';

        // Actualizar título (DINÁMICO)
        document.getElementById('receipt-title').textContent = recibo.titulo;

        // Actualizar número de pedido
        document.getElementById('order-pedido').textContent = 
            '#' + (this.datosFactura.numero_pedido || '00000');

        // Generar contenido del recibo
        const contenido = this.generarContenido(recibo);
        document.getElementById('descripcion-text').innerHTML = contenido;

        // Actualizar estado de botones
        this.actualizarBotones();
    }

    /**
     * Actualizar fecha en el recibo
     */
    actualizarFecha(fechaStr) {
        if (!fechaStr) {
            document.getElementById('receipt-day').textContent = '--';
            document.getElementById('receipt-month').textContent = '--';
            document.getElementById('receipt-year').textContent = '----';
            return;
        }

        let fecha;
        if (typeof fechaStr === 'string') {
            if (fechaStr.includes('/')) {
                const [day, month, year] = fechaStr.split('/');
                fecha = new Date(year, parseInt(month) - 1, day);
            } else if (fechaStr.includes('-')) {
                const fechaParte = fechaStr.split(' ')[0];
                const [year, month, day] = fechaParte.split('-');
                fecha = new Date(year, parseInt(month) - 1, parseInt(day));
            } else {
                fecha = new Date(fechaStr);
            }
        } else {
            fecha = new Date(fechaStr);
        }

        if (!isNaN(fecha)) {
            document.getElementById('receipt-day').textContent = fecha.getDate();
            document.getElementById('receipt-month').textContent = fecha.getMonth() + 1;
            document.getElementById('receipt-year').textContent = fecha.getFullYear();
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
        // Validar tipo de costura
        const esCostura = prenda.origen && 
            (prenda.origen.toLowerCase() === 'bodega' || prenda.origen.toLowerCase() === 'confección');
        
        if (!esCostura) {
            // Fallback al formato anterior si no es costura/costura-bodega
            return this.contenidoCosturaSinFormato(prenda);
        }

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
                console.log(`[RECEIPT]  Tallas desanidadas para ${genero}:`, value);
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
                console.log(`[RECEIPT]  Talla aplanada procesada: ${key} = ${value}`);
            } else {
                console.warn('[RECEIPT]  Formato de talla desconocido:', key, value);
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
        let html = `<strong>${proceso.nombre.toUpperCase()}</strong><br>`;
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
     * Imprimir recibo actual
     */
    imprimir() {
        window.print();
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
}

// Exportar para uso externo
window.ReceiptManager = ReceiptManager;

console.log(' [RECEIPT MANAGER] receipt-manager.js cargado correctamente');
