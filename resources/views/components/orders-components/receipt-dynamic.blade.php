<link rel="stylesheet" href="{{ asset('css/order-detail-modal.css') }}">

<div class="order-detail-modal-container" style="display: flex; flex-direction: column; width: 100%; height: 100%;">
    <div class="order-detail-card">
        <img src="{{ asset('images/logo.png') }}" alt="Mundo Industrial Logo" class="order-logo" width="150" height="80">
        
        <!-- Fecha -->
        <div id="order-date" class="order-date">
            <div class="fec-label">FECHA</div>
            <div class="date-boxes">
                <div class="date-box day-box" id="receipt-day"></div>
                <div class="date-box month-box" id="receipt-month"></div>
                <div class="date-box year-box" id="receipt-year"></div>
            </div>
        </div>

        <!-- Información Básica -->
        <div id="order-asesora" class="order-asesora">ASESORA: <span id="receipt-asesora-value"></span></div>
        <div id="order-forma-pago" class="order-forma-pago">FORMA DE PAGO: <span id="receipt-forma-pago-value"></span></div>
        <div id="order-cliente" class="order-cliente">CLIENTE: <span id="receipt-cliente-value"></span></div>

        <!-- Descripción/Contenido del Proceso -->
        <div id="order-descripcion" class="order-descripcion">
            <div id="descripcion-text"></div>
        </div>

        <!-- Título del Recibo - DINÁMICO (RECIBO DE COSTURA, RECIBO DE BORDADO, etc.) -->
        <h2 class="receipt-title" id="receipt-title">RECIBO DE COSTURA</h2>

        <!-- Flechas de navegación entre recibos -->
        <div class="arrow-container">
            <button id="prev-arrow" class="arrow-btn" style="display: none;" title="Recibo anterior">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
            <span id="receipt-counter" style="font-weight: bold; font-size: 14px;">Recibo <span id="receipt-number">1</span>/<span id="receipt-total">1</span></span>
            <button id="next-arrow" class="arrow-btn" style="display: none;" title="Siguiente recibo">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>
        </div>

        <!-- Número de Pedido -->
        <div id="order-pedido" class="pedido-number"></div>

        <!-- Separador -->
        <div class="separator-line"></div>

        <!-- Footer -->
        <div class="signature-section">
            <div class="signature-field">
                <span>ENCARGADO DE ORDEN:</span>
                <span id="encargado-value"></span>
            </div>
            <div class="vertical-separator"></div>
            <div class="signature-field">
                <span>PRENDAS ENTREGADAS:</span>
                <span id="prendas-entregadas-value"></span>
            </div>
        </div>
    </div>
</div>

<!-- Botones flotantes para imprimir y cerrar -->
<div style="position: fixed; right: 10px; top: 50%; transform: translateY(-50%); display: flex; flex-direction: column; gap: 12px; z-index: 10000;">
    <button id="print-receipt-btn" type="button" title="Imprimir recibo" style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #27ae60, #229954); border: none; color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
        <i class="fas fa-print"></i>
    </button>
    <button id="close-receipt-btn" type="button" title="Cerrar" style="width: 56px; height: 56px; border-radius: 50%; background: white; border: 2px solid #ddd; color: #333; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 24px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
        <i class="fas fa-times"></i>
    </button>
</div>

<script>
/**
 * ReceiptManager
 * Gestiona la navegación y visualización de recibos dinámicos
 * Reutiliza la estructura y estilos del order-detail-modal.blade.php
 */
class ReceiptManager {
    constructor(datosFactura, contenedorId = null) {
        this.datosFactura = datosFactura;
        this.contenedorId = contenedorId;
        this.recibos = this.generarRecibos(datosFactura);
        this.indexActual = 0;


        this.inicializarEventos();
        this.renderizar();
    }

    /**
     * Generar array de recibos desde datos del pedido
     */
    generarRecibos(datosFactura) {
        const recibos = [];

        datosFactura.prendas.forEach((prenda, prendaIdx) => {
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
        if (btnImprimir) {
            btnImprimir.addEventListener('click', () => this.imprimir());
        }
        if (btnCerrar) {
            btnCerrar.addEventListener('click', () => this.cerrar());
        }
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
        // Actualizar contador
        document.getElementById('receipt-number').textContent = recibo.numero;
        document.getElementById('receipt-total').textContent = recibo.total;

        // Actualizar fecha
        this.actualizarFecha(this.datosFactura.fecha);

        // Actualizar información básica
        document.getElementById('receipt-asesora-value').textContent = 
            this.datosFactura.asesora || 'N/A';
        document.getElementById('receipt-forma-pago-value').textContent = 
            this.datosFactura.formaPago || 'N/A';
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

        if (prenda.de_bodega !== undefined) {
            const origenTexto = prenda.de_bodega == 1 ? 'BODEGA' : 'CONFECCIÓN';
            html += `<strong>Origen:</strong> ${origenTexto}<br>`;
        }

        if (prenda.tallas && Array.isArray(prenda.tallas) && prenda.tallas.length > 0) {
            html += `<br><strong>TALLAS:</strong><br>`;
            const tallasArr = [];
            prenda.tallas.forEach((tallaObj) => {
                if (tallaObj.genero && tallaObj.talla && tallaObj.cantidad) {
                    tallasArr.push(`${tallaObj.genero}-${tallaObj.talla}: ${tallaObj.cantidad}`);
                }
            });
            if (tallasArr.length > 0) {
                html += tallasArr.join(' | ');
            }
        }

        return html || '<em>Sin información</em>';
    }

    /**
     * Generar contenido para recibo de PROCESO
     */
    contenidoProceso(proceso, prenda) {
        let html = `<strong>${proceso.nombre.toUpperCase()}</strong><br>`;
        html += `<em>${prenda.nombre.toUpperCase()}</em><br><br>`;

        if (proceso.especificaciones) {
            if (Array.isArray(proceso.especificaciones)) {
                proceso.especificaciones.forEach(spec => {
                    if (typeof spec === 'string') {
                        html += `• ${spec.toUpperCase()}<br>`;
                    } else if (typeof spec === 'object') {
                        const nombre = (spec.nombre || spec.tipo || '').toUpperCase();
                        const valor = (spec.valor || spec.descripcion || '').toUpperCase();
                        if (nombre && valor) {
                            html += `• <strong>${nombre}:</strong> ${valor}<br>`;
                        }
                    }
                });
            } else {
                html += `${proceso.especificaciones.toUpperCase()}<br>`;
            }
        }

        if (proceso.imagenes && proceso.imagenes.length > 0) {
            html += `<br><strong>IMÁGENES:</strong><br>`;
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
        const contenedor = document.querySelector('.order-detail-modal-container');
        if (contenedor) {
            contenedor.style.display = 'none';
        }
    }
}

// Exportar para uso externo
window.ReceiptManager = ReceiptManager;
</script>

