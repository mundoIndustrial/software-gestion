/**
 * SIMULACIÓN DEL FLUJO COMPLETO
 * Copia y pega esto en la consola del navegador (F12) 
 * para simular exactamente lo que hace el formulario
 */

// 1. SIMULAR PRIMER REQUEST
console.log('=== SIMULANDO PRIMER REQUEST ===');
fetch('/asesores/pedidos-produccion/crear-desde-cotizacion', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
    },
    body: JSON.stringify({
        cotizacion_id: 13,  // CAMBIAR a tu cotización ID
        forma_de_pago: 'Contado',
        prendas: []
    })
})
.then(r => r.json())
.then(data => {
    console.log('✅ RESPUESTA PRIMER REQUEST:', data);
    
    // 2. VERIFICAR SI ES COMBINADA
    const esCombinada = data.es_combinada === true || data.es_combinada === 'true';
    console.log('🎯 ¿Es COMBINADA?', esCombinada);
    console.log('🎯 es_combinada value:', data.es_combinada);
    
    if (!esCombinada) {
        console.log('❌ NO ES COMBINADA, abortando segundo request');
        return;
    }
    
    // 3. SIMULAR SEGUNDO REQUEST
    console.log('=== SIMULANDO SEGUNDO REQUEST ===');
    const pedidoId = data.pedido_id;
    console.log('Usando pedido_id:', pedidoId);
    
    return fetch('/asesores/pedidos/guardar-logo-pedido', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: JSON.stringify({
            pedido_id: pedidoId,
            logo_cotizacion_id: data.logo_cotizacion_id || 1,
            cotizacion_id: 13,
            forma_de_pago: 'Contado',
            descripcion: 'TEST',
            cantidad: 100,
            tecnicas: ['BORDADO'],
            ubicaciones: ['Pecho'],
            fotos: []
        })
    })
    .then(r => r.json())
    .then(data => {
        console.log('✅ RESPUESTA SEGUNDO REQUEST:', data);
    });
})
.catch(err => console.error('❌ ERROR:', err));
