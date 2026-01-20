/**
 * ReceiptRenderer.js
 * Renderiza el contenido del recibo en el modal
 */

import { Formatters } from '../utils/Formatters.js';

export class ReceiptRenderer {
    /**
     * Renderiza un recibo completo en el modal
     */
    static renderizar(modalManager, prendaData, reciboIndice, tipoProceso, datosPedido, recibos) {
        console.log(`%c[ReceiptRenderer] Renderizando recibo (índice ${reciboIndice})`, 'color: #10b981;');

        // Obtener el recibo
        const recibo = recibos && recibos[reciboIndice] ? recibos[reciboIndice] : null;
        if (!recibo) {
            console.error('[ReceiptRenderer] Recibo no encontrado en índice', reciboIndice);
            return;
        }

        // Actualizar título
        this._actualizarTitulo(tipoProceso, recibo);

        // Llenar información básica
        this._llenarInformacionBasica(datosPedido);

        // Llenar descripción de la prenda
        this._llenarDescripcion(prendaData, recibo, tipoProceso);

        // Guardar datos en estado
        modalManager.setState({
            prendaPedidoId: prendaData.prenda_pedido_id || prendaData.id,
            imagenesActuales: recibo.imagenes && Array.isArray(recibo.imagenes) ? recibo.imagenes : []
        });

        console.log('[ReceiptRenderer] Recibo renderizado correctamente');
    }

    /**
     * Actualiza el título del modal
     */
    static _actualizarTitulo(tipoProceso, recibo) {
        const titleElement = document.querySelector('.receipt-title');
        if (titleElement) {
            const nombreRecibo = String(tipoProceso || recibo.tipo_proceso || recibo.nombre_proceso || 'Recibo').toUpperCase();
            titleElement.textContent = `RECIBO DE ${nombreRecibo}`;
            console.log(`%c[ReceiptRenderer] Título: RECIBO DE ${nombreRecibo}`, 'color: #10b981;');
        }
    }

    /**
     * Llena la información básica del pedido
     */
    static _llenarInformacionBasica(datosPedido) {
        // Fecha
        const dayBox = document.querySelector('.day-box');
        const monthBox = document.querySelector('.month-box');
        const yearBox = document.querySelector('.year-box');

        if (dayBox && monthBox && yearBox) {
            const fecha = Formatters.parsearFecha(datosPedido.fecha);
            const { day, month, year } = Formatters.formatearFecha(fecha);
            
            dayBox.textContent = day;
            monthBox.textContent = month;
            yearBox.textContent = year;
            
            console.log('[ReceiptRenderer] Fecha establecida:', `${day}/${month}/${year}`);
        }

        // Cliente
        const clienteValue = document.getElementById('cliente-value');
        if (clienteValue) clienteValue.textContent = datosPedido.cliente || '-';

        // Asesora
        const asesoraValue = document.getElementById('asesora-value');
        if (asesoraValue) asesoraValue.textContent = datosPedido.asesora || '-';

        // Forma de pago
        const formaPagoValue = document.getElementById('forma-pago-value');
        if (formaPagoValue) formaPagoValue.textContent = datosPedido.forma_de_pago || '-';

        // Número de pedido
        const pedidoNumber = document.querySelector('.pedido-number');
        if (pedidoNumber) pedidoNumber.textContent = `#${datosPedido.numero_pedido}`;

        // Encargado
        const encargadoValue = document.getElementById('encargado-value');
        if (encargadoValue) encargadoValue.textContent = '-';

        // Prendas entregadas
        const prendasValue = document.getElementById('prendas-entregadas-value');
        if (prendasValue) prendasValue.textContent = '0/0';
    }

    /**
     * Llena la descripción de la prenda
     */
    static _llenarDescripcion(prendaData, recibo, tipoProceso) {
        const descripcionText = document.getElementById('descripcion-text');
        if (!descripcionText) return;

        let html = '';
        const tipoProcesoBajo = String(tipoProceso || '').toLowerCase();

        // Determinar si es costura
        if (tipoProcesoBajo === 'costura' || tipoProcesoBajo === 'costura-bodega') {
            console.log('[ReceiptRenderer] Renderizando formato de COSTURA');
            
            // Intentar usar ReceiptManager si existe
            if (typeof window.ReceiptManager !== 'undefined' && window.ReceiptManager.prototype.construirDescripcionCostura) {
                const rm = new window.ReceiptManager({prendas: []}, null, null);
                html = rm.construirDescripcionCostura(prendaData);
                console.log('[ReceiptRenderer]  Usando ReceiptManager');
            } else {
                // Usar formateador
                html = Formatters.construirDescripcionCostura(prendaData);
                console.log('[ReceiptRenderer]  Usando Formatters');
            }
        } else {
            // Para otros procesos
            console.log('[ReceiptRenderer] Renderizando formato de PROCESO especializado');
            html = Formatters.construirDescripcionProceso(prendaData, recibo);
        }

        descripcionText.innerHTML = html;
    }
}
