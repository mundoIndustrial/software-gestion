// Test del flujo completo de seguimiento

// Simulamos que el usuario hace clic en "Ver Seguimiento" para el pedido 45808
// Esto debería:
// 1. Llamar a openOrderTracking(45808)
// 2. Que a su vez llama a ApiClient.getOrderProcesos(45808)
// 3. Que devuelve los procesos

// Primero, vamos a verificar qué rutas se están llamando

console.log('=== TEST DE FLUJO DE SEGUIMIENTO ===');

// Simular la llamada a /api/ordenes/45808/procesos
fetch('/api/ordenes/45808/procesos', {
    headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(response => {
    console.log('Response status:', response.status);
    console.log('Response ok:', response.ok);
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
})
.then(data => {
    console.log('Procesos recibidos:', data);
    console.log('Total procesos:', data.length);
    
    if (data.length > 0) {
        console.log('Primer proceso:', data[0]);
    }
})
.catch(error => {
    console.error('Error:', error);
    
    // Si falla, intentar con la siguiente ruta
    console.log('Intentando con /api/tabla-original/45808/procesos');
    fetch('/api/tabla-original/45808/procesos', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        console.log('Response status (tabla-original):', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Procesos recibidos (tabla-original):', data);
    })
    .catch(error => console.error('Error (tabla-original):', error));
});
