// FunciÃ³n para cargar recibos dinÃ¡micamente cuando se navega entre procesos
window.cargarReciboDinamico = async function(pedidoId, tipoProceso) {
    try {
        console.log(' [CARGAR DINAMICO] ========== INICIANDO ==========');
        console.log(' [CARGAR DINAMICO] Datos:', { pedidoId, tipoProceso });
        console.log(' [CARGAR DINAMICO] Ãndice actual:', window.procesoCarouselIndex);
        console.log(' [CARGAR DINAMICO] Procesos disponibles:', window.todosProcesosDisponibles);
        
        // Determinar la ruta correcta segÃºn la vista actual
        const pathActual = (window.location?.pathname || '').toString();
        const esControlCalidad = pathActual.includes('/control-calidad/');
        const baseApi = esControlCalidad ? '/control-calidad/api/pedido' : '/operario/api/pedido';
        
        // Hacer fetch a la API para obtener datos actualizados
        const url = `${baseApi}/${pedidoId}${window.location.search}`;
        console.log(' [CARGAR DINAMICO] URL API:', url);
        console.log(' [CARGAR DINAMICO] Es Control Calidad:', esControlCalidad);
        console.log(' [CARGAR DINAMICO] window.location.search:', window.location.search);
        
        const { response, payload: result } = await window.OrderDetailMobileService.getPedidoDinamico(url);
        
        console.log(' [CARGAR DINAMICO] Respuesta HTTP:', {
            ok: response.ok,
            status: response.status,
            statusText: response.statusText,
            contentType: response.headers.get('content-type')
        });
        
        if (!response.ok) {
            throw new Error(`Error en API: ${response.status}`);
        }
        
        console.log(' [CARGAR DINAMICO] JSON recibido:', {
            success: result.success,
            tieneData: !!result.data,
            dataKeys: result.data ? Object.keys(result.data).slice(0, 10) : null
        });
        
        if (result.success && result.data) {
            console.log(' [CARGAR DINAMICO] Datos vÃ¡lidos obtenidos');
            console.log(' [CARGAR DINAMICO] Data.prendas:', result.data.prendas?.length);
            
            // Resetear prendaCarouselIndex para que muestre desde el principio
            window.prendaCarouselIndex = 0;
            
            console.log(' [CARGAR DINAMICO] Llamando a llenarReciboCosturaMobile...');
            
            // Llenar con los nuevos datos
            window.llenarReciboCosturaMobile(result.data);
            
            // Actualizar fotos para la primera prenda del nuevo proceso
            if (window.actualizarFotosPrenda) {
                window.actualizarFotosPrenda();
            }
            
            // Actualizar nÃºmero de recibo en el header
            if (window.actualizarNumeroPrendaHeader) {
                window.actualizarNumeroPrendaHeader();
            }
            
            console.log(' [CARGAR DINAMICO] llenarReciboCosturaMobile completado');
        } else {
            throw new Error('Respuesta invÃ¡lida de la API: ' + JSON.stringify(result));
        }
    } catch (error) {
        console.error(' [CARGAR DINAMICO] Error:', error);
        console.error(' [CARGAR DINAMICO] Stack:', error.stack);
        alert('Error al cargar el recibo: ' + error.message);
    }
};

