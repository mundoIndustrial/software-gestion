export function abrirDetallesRecibos(numeroPedido, prendaId, nombrePrenda, tipoRecibo, pedidoParcialId = null, consecutivoParcial = null, reciboId = null) {
    console.log(' [ABRIR DETALLES RECIBOS] ===== INICIANDO =====');
    console.log(' Parámetros recibidos:', {
        numeroPedido: numeroPedido,
        prendaId: prendaId,
        nombrePrenda: nombrePrenda,
        tipoRecibo: tipoRecibo,
        pedidoParcialId: pedidoParcialId,
        consecutivoParcial: consecutivoParcial,
        reciboId: reciboId,
    });

    let finalNumeroPedido = numeroPedido;
    
    // Si es bodega y no hay número de pedido, usamos '0' como placeholder
    if (!finalNumeroPedido || finalNumeroPedido === '' || finalNumeroPedido === null || finalNumeroPedido === undefined) {
        if (tipoRecibo === 'CORTE-PARA-BODEGA' || tipoRecibo === 'BODEGA') {
            finalNumeroPedido = '0';
        } else {
            console.error(' ERROR: numeroPedido está vacío o undefined', finalNumeroPedido);
            alert('Error: No se pudo determinar el número de pedido');
            return false;
        }
    }

    const numeroPedidoStr = String(finalNumeroPedido).trim();
    console.log(' numeroPedido normalizado:', numeroPedidoStr);

    let url = '/operario/pedido/' + numeroPedidoStr;
    const params = new URLSearchParams();

    if (prendaId) {
        params.append('prenda_id', prendaId);
        console.log(' Prenda ID:', prendaId);
    }

    if (tipoRecibo) {
        params.append('tipo_recibo', tipoRecibo);
        console.log(' Tipo de recibo:', tipoRecibo);
    }

    if (reciboId) {
        params.append('recibo_id', reciboId);
        console.log(' Recibo ID:', reciboId);
    }

    if (pedidoParcialId) {
        params.append('parcial_id', pedidoParcialId);
        if (consecutivoParcial !== null && consecutivoParcial !== undefined && String(consecutivoParcial).trim() !== '') {
            params.append('consecutivo_parcial', String(consecutivoParcial).trim());
        }

        console.log(' Pedido Parcial ID:', pedidoParcialId);
    }

    if (params.toString()) {
        url += '?' + params.toString();
    }

    console.log(' URL a navegar:', url);

    try {
        console.log(' Iniciando navegación...');
        window.location.href = url;
        console.log(' Navegación iniciada exitosamente');
        return false;
    } catch (error) {
        console.error(' Error al navegar:', error);
        return false;
    }
}
