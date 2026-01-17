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

        console.log('📋 [RECEIPT MANAGER] Inicializado');
        console.log('📊 Total de recibos:', this.recibos.length);
        if (prendasIndex !== null) {
            console.log('🔍 Filtrado para prenda:', prendasIndex);
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
        optionTodas.text = '📋 Todas las prendas';
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
        
        console.log('📊 Recibos después de filtro:', this.recibos.length);
        
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
     */
    contenidoCostura(prenda) {
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

console.log('✅ [RECEIPT MANAGER] receipt-manager.js cargado correctamente');
