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
        // Obtener el recibo
        const recibo = recibos && recibos[reciboIndice] ? recibos[reciboIndice] : null;
        if (!recibo) {
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
            imagenesActuales: recibo.imagenes && Array.isArray(recibo.imagenes) ? recibo.imagenes : [],
            prendaData: prendaData
        });


    }

    /**
     * Actualiza el título del modal
     */
    static _actualizarTitulo(tipoProceso, recibo) {
        const titleElement = document.querySelector('.receipt-title');
        if (titleElement) {
            const nombreRecibo = String(tipoProceso || recibo.tipo_proceso || recibo.nombre_proceso || 'Recibo').toUpperCase();
            titleElement.textContent = 'RECIBO DE ' + nombreRecibo;
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
        }

        // Cliente
        const clienteValue = document.getElementById('cliente-value');
        if (clienteValue) {
            const valor = datosPedido.cliente || '-';
            clienteValue.textContent = valor;
            console.log('✅ [ReceiptRenderer] Cliente actualizado:', valor);
        } else {
            console.warn('⚠️ [ReceiptRenderer] Elemento #cliente-value NO encontrado');
        }

        // Asesor
        const asesorValue = document.getElementById('asesora-value');
        if (asesorValue) {
            const valor = datosPedido.asesor || datosPedido.asesora || '-';
            asesorValue.textContent = valor;
            console.log('✅ [ReceiptRenderer] Asesor actualizado:', valor);
        } else {
            console.warn('⚠️ [ReceiptRenderer] Elemento #asesora-value NO encontrado');
        }

        // Forma de pago
        const formaPagoValue = document.getElementById('forma-pago-value');
        if (formaPagoValue) {
            const valor = datosPedido.forma_de_pago || '-';
            formaPagoValue.textContent = valor;
            console.log('✅ [ReceiptRenderer] Forma de pago actualizada:', valor);
        } else {
            console.warn('⚠️ [ReceiptRenderer] Elemento #forma-pago-value NO encontrado');
        }

        // Número de pedido
        const pedidoNumber = document.querySelector('.pedido-number');
        if (pedidoNumber) {
            // En supervisor-pedidos, mostrar vacío hasta que se apruebe
            let numero = '';
            if (!window.location.href.includes('supervisor-pedidos')) {
                numero = datosPedido.numero_pedido || datosPedido.numero || '';
            }
            pedidoNumber.textContent = '#' + numero;
        }

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
        if (!descripcionText) {
            console.warn('⚠️ [ReceiptRenderer] Elemento #descripcion-text NO encontrado');
            return;
        }

        console.log('🔍 [ReceiptRenderer._llenarDescripcion] prendaData completo:', {
            nombre: prendaData.nombre,
            numero: prendaData.numero,
            tela: prendaData.tela,
            color: prendaData.color,
            ref: prendaData.ref,
            variantes: prendaData.variantes,
            descripcion: prendaData.descripcion,
            tallas: prendaData.tallas,
            genero: prendaData.genero
        });

        let html = '';
        const tipoProcesoBajo = String(tipoProceso || '').toLowerCase();

        // Determinar si es costura
        if (tipoProcesoBajo === 'costura' || tipoProcesoBajo === 'costura-bodega') {
            // Usar formateador directamente
            html = Formatters.construirDescripcionCostura(prendaData);
            console.log('✅ [ReceiptRenderer._llenarDescripcion] HTML de costura generado:', html);
        } else {
            // Para otros procesos
            html = Formatters.construirDescripcionProceso(prendaData, recibo);
            console.log('✅ [ReceiptRenderer._llenarDescripcion] HTML de proceso generado:', html);
        }

        descripcionText.innerHTML = html;
        console.log('✅ [ReceiptRenderer._llenarDescripcion] Descripción actualizada en el DOM');
    }
}

