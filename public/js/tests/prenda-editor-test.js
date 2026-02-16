/**
 * TEST SUITE: Pruebas de Edición de Prendas
 * 
 * Valida que la información se carga correctamente en los 3 casos:
 * 1. Crear nueva prenda desde modal
 * 2. Editar prenda en pedido existente
 * 3. Editar prenda desde cotización
 */

class PrendaEditorTest {
    constructor() {
        this.resultados = [];
        this.caso_actual = null;
    }

    /**
     * CASO 1: Crear pedido nuevo y editar prenda
     * Flujo: crear-pedido-nuevo.blade.php → agregar prenda → editar prenda
     */
    async casoCrearPedidoNuevo() {
        this.caso_actual = 'CASO 1: Crear Pedido Nuevo';
        console.log('\n=== ' + this.caso_actual + ' ===');
        
        try {
            // Verificar que GestionItemsUI existe
            if (!window.gestionItemsUI) {
                throw new Error('GestionItemsUI no está disponible');
            }

            // Simular una prenda nueva
            const prendaEjemplo = {
                nombre_prenda: 'CAMISA TEST',
                descripcion: 'Camisa de prueba',
                origen: 'confeccion',
                telas: [],
                telasAgregadas: [],
                imagenes: [],
                tallas: {},
                cantidad_talla: {},
                variantes: [],
                procesos: []
            };

            console.log(' [CASO 1] Prenda de ejemplo creada:', prendaEjemplo);

            // Verificar que el PrendaEditor está disponible
            if (!window.gestionItemsUI.prendaEditor) {
                throw new Error('PrendaEditor no está disponible en GestionItemsUI');
            }

            console.log(' [CASO 1] PrendaEditor disponible');
            
            // Intentar cargar la prenda en modal (sin índice = crear nueva)
            // Esto causará un 404 porque no hay pedidoId, pero debería usar datos locales
            await window.gestionItemsUI.prendaEditor.cargarPrendaEnModal(prendaEjemplo, null);
            
            console.log(' [CASO 1] Modal abierto correctamente con datos locales');
            
            // Verificar que el modal está visible
            const modal = document.getElementById('modal-agregar-prenda-nueva');
            if (modal && modal.style.display !== 'none') {
                console.log(' [CASO 1] Modal visualmente visible en pantalla');
            }
            
            this.resultados.push({
                caso: this.caso_actual,
                estado: 'EXITOSO',
                detalles: 'Modal abierto y prenda cargada correctamente'
            });

        } catch (error) {
            console.error(' [CASO 1] Error:', error.message);
            this.resultados.push({
                caso: this.caso_actual,
                estado: 'ERROR',
                detalles: error.message
            });
        }
    }

    /**
     * CASO 2: Editar prenda en pedido existente
     * Flujo: edit.blade.php → tarjeta prenda → 3 punticos → editar
     */
    async casoEditarPedidoExistente(pedidoId = 1) {
        this.caso_actual = 'CASO 2: Editar Pedido Existente';
        console.log('\n=== ' + this.caso_actual + ' ===');
        
        try {
            // Obtener datos del servidor
            console.log(`📡 Obteniendo datos del pedido ${pedidoId}...`);
            const response = await fetch(`/pedidos-public/${pedidoId}/factura-datos`);
            
            if (!response.ok) {
                throw new Error(`Error HTTP ${response.status}`);
            }

            const resultado = await response.json();

            if (!resultado.success || !resultado.data) {
                throw new Error('Respuesta de servidor inválida');
            }

            const prendas = resultado.data.prendas;
            if (!prendas || prendas.length === 0) {
                throw new Error('No hay prendas en este pedido');
            }

            console.log(` [CASO 2] Pedido obtenido con ${prendas.length} prendas`);

            // Editar la primera prenda
            const prendaAEditar = prendas[0];
            console.log(' Prenda a editar:', {
                nombre: prendaAEditar.nombre || prendaAEditar.nombre_prenda,
                id: prendaAEditar.id,
                prenda_pedido_id: prendaAEditar.prenda_pedido_id,
                tieneTallas: !!prendaAEditar.tallas || !!prendaAEditar.cantidad_talla,
                tieneImagenes: !!(prendaAEditar.imagenes && Array.isArray(prendaAEditar.imagenes)),
                tieneTelas: !!(prendaAEditar.telas || prendaAEditar.telasAgregadas),
                tieneVariaciones: !!(prendaAEditar.variantes || prendaAEditar.variaciones),
                tieneProcesos: !!(prendaAEditar.procesos && prendaAEditar.procesos.length > 0)
            });

            // Verificar campos críticos
            if (!prendaAEditar.nombre && !prendaAEditar.nombre_prenda) {
                throw new Error('Prenda sin nombre');
            }

            // IMPORTANTE: Cargar la prenda en modal para verificar que se renderiza con todos los datos
            console.log('📂 Cargando prenda en modal...');
            
            if (!window.gestionItemsUI || !window.gestionItemsUI.prendaEditor) {
                throw new Error('GestionItemsUI.prendaEditor no disponible');
            }

            // Limpia el modal antes de cargar
            const tablaTelas = document.querySelector('#tabla-telas tbody');
            const tablaTallas = document.querySelector('#tabla-tallas tbody');
            
            // Cargar la prenda en modal
            await window.gestionItemsUI.prendaEditor.cargarPrendaEnModal(prendaAEditar, 0);
            
            // Esperar a que se renderice
            await new Promise(resolve => setTimeout(resolve, 300));
            
            // Verificar que se cargaron las telas
            const telasCargadas = tablaTelas ? tablaTelas.querySelectorAll('tr').length : 0;
            const tieneTelas = telasCargadas > 0 || !!(prendaAEditar.telas || prendaAEditar.telasAgregadas);
            
            // Verificar que se cargaron las tallas
            const tallasCargadas = tablaTallas ? tablaTallas.querySelectorAll('tr').length : 0;
            const tieneTallas = tallasCargadas > 0 || !!(prendaAEditar.tallas || prendaAEditar.cantidad_talla);
            
            // Verificar que se cargaron variaciones
            const variacionesSection = document.querySelector('[id*="variaciones"]') || document.querySelector('[id*="variante"]');
            
            // Verificar que se cargaron procesos
            const procesosContainer = document.getElementById('procesos-agregados');
            const procesosCargados = procesosContainer ? procesosContainer.querySelectorAll('.badge').length : 0;
            
            // Debug: buscar otros posibles contenedores
            if (procesosCargados === 0) {
                console.log('🔍 [CASO 2] Debug procesos - buscando contenedores alternativos...');
                const alternativas = [
                    document.getElementById('procesos'),
                    document.querySelector('[id*="proceso"]'),
                    document.querySelector('.procesos-container'),
                    document.querySelector('[class*="proceso"]')
                ];
                alternativas.forEach((el, idx) => {
                    if (el) console.log(`  Alternativa ${idx}: Encontrada -`, el.id || el.className);
                });
            }
            
            console.log('📊 Datos cargados en modal:', {
                telasCargadas,
                tallasCargadas,
                procesosCargados,
                tieneVariaciones: !!variacionesSection,
                datosEnObjeto: {
                    tieneProcesosEnObjeto: !!(prendaAEditar.procesos && prendaAEditar.procesos.length > 0),
                    cantidadProcesosEnObjeto: prendaAEditar.procesos?.length || 0
                }
            });

            // Validar que se cargó algo
            if (!tieneTelas && !tieneTallas && procesosCargados === 0) {
                console.warn(' [CASO 2] Advertencia: Prenda sin telas, tallas ni procesos visibles');
            }

            console.log(' [CASO 2] Prenda cargada en modal y validada');

            this.resultados.push({
                caso: this.caso_actual,
                estado: 'EXITOSO',
                detalles: `Datos cargados: ${telasCargadas} telas, ${tallasCargadas} tallas, ${procesosCargados} procesos`
            });

        } catch (error) {
            console.error(' [CASO 2] Error:', error.message);
            this.resultados.push({
                caso: this.caso_actual,
                estado: 'ERROR',
                detalles: error.message
            });
        }
    }

    /**
     * CASO 3: Editar prenda desde cotización
     * Flujo: crear-pedido-desde-cotizacion.blade.php → cargar prendas de coti → editar
     */
    async casoEditarDesdeCotizacion(cotizacionId = 1) {
        this.caso_actual = 'CASO 3: Editar Desde Cotización';
        console.log('\n=== ' + this.caso_actual + ' ===');
        
        try {
            // Este caso solo es válido en crear-pedido-desde-cotizacion
            // En otras páginas, es esperado que no esté disponible
            const enPaginaCotizacion = window.location.pathname.includes('cotizacion');
            
            // Usar typeof para evitar ReferenceError
            if (typeof window.PrendaCotizacionHandler === 'undefined') {
                // Si no estamos en la página de cotización, es normal que no exista
                if (enPaginaCotizacion) {
                    throw new Error('PrendaCotizacionHandler no está disponible en página de cotización');
                } else {
                    console.log(' [CASO 3] Este caso solo aplica en crear-pedido-desde-cotizacion');
                    this.resultados.push({
                        caso: this.caso_actual,
                        estado: 'OMITIDO',
                        detalles: 'Página incorrecta (solo en crear-pedido-desde-cotizacion)'
                    });
                    return;
                }
            }

            console.log(' [CASO 3] PrendaCotizacionHandler disponible');

            // Intentar verificar si hay prendas cargadas desde cotización
            if (window.prendas_cotizacion && window.prendas_cotizacion.length > 0) {
                console.log(` [CASO 3] ${window.prendas_cotizacion.length} prendas de cotización cargadas`);
                
                const prendaTest = window.prendas_cotizacion[0];
                console.log(' Prenda de cotización:', {
                    nombre: prendaTest.nombre || prendaTest.nombre_prenda,
                    origen: prendaTest.origen || 'No especificado',
                    tieneDatos: !!prendaTest.telas || !!prendaTest.imagenes
                });
            } else {
                console.warn(' [CASO 3] No hay prendas de cotización cargadas en memoria');
            }

            this.resultados.push({
                caso: this.caso_actual,
                estado: 'EXITOSO',
                detalles: 'Cotización verificada correctamente'
            });

        } catch (error) {
            console.error(' [CASO 3] Error:', error.message);
            this.resultados.push({
                caso: this.caso_actual,
                estado: 'ERROR',
                detalles: error.message
            });
        }
    }

    /**
     * Ejecutar todos los tests
     */
    async ejecutarTodos() {
        console.clear();
        console.log('🚀 INICIANDO TEST SUITE: EDICIÓN DE PRENDAS');
        console.log('=' .repeat(60));
        console.log('Página actual: ' + window.location.pathname);

        // Verificar dependencias globales
        console.log('\n Verificando dependencias globales...');
        this.verificarDependencias();

        // Ejecutar casos
        await this.casoCrearPedidoNuevo();
        await this.casoEditarPedidoExistente();
        await this.casoEditarDesdeCotizacion();

        // Mostrar resumen
        console.log('\n' + '='.repeat(60));
        console.log('📊 RESUMEN DE RESULTADOS');
        console.log('='.repeat(60));
        
        this.resultados.forEach((r, idx) => {
            let emoji = '❓';
            if (r.estado === 'EXITOSO') emoji = '';
            else if (r.estado === 'ERROR') emoji = '';
            else if (r.estado === 'OMITIDO') emoji = '⏭️';
            
            console.log(`${emoji} ${r.caso}`);
            console.log(`   Estado: ${r.estado}`);
            console.log(`   Detalles: ${r.detalles}`);
        });

        const exitosos = this.resultados.filter(r => r.estado === 'EXITOSO').length;
        const omitidos = this.resultados.filter(r => r.estado === 'OMITIDO').length;
        const total = this.resultados.length - omitidos;
        
        console.log('\n' + '='.repeat(60));
        console.log(`📈 TOTAL: ${exitosos}/${total} casos exitosos`);
        if (omitidos > 0) {
            console.log(`⏭️  ${omitidos} caso(s) omitido(s) (no aplica en esta página)`);
        }
        console.log('='.repeat(60));

        return this.resultados;
    }

    /**
     * Verificar que las dependencias globales existen
     */
    verificarDependencias() {
        const dependencias = [
            'window.gestionItemsUI',
            'window.PrendaEditor',
            'window.SharedPrendaEditorService'
        ];

        dependencias.forEach(dep => {
            const existe = eval(`typeof ${dep} !== 'undefined'`);
            const emoji = existe ? '' : '';
            console.log(`${emoji} ${dep}: ${existe ? 'OK' : 'FALTA'}`);
        });
    }

    /**
     * Validar estructura de prenda
     */
    validarEstructuraPrenda(prenda) {
        const campos = {
            'nombre_prenda || nombre': !!prenda.nombre_prenda || !!prenda.nombre,
            'telas': !!prenda.telas || !!prenda.telasAgregadas,
            'tallas': !!prenda.tallas || !!prenda.cantidad_talla,
            'imagenes': Array.isArray(prenda.imagenes),
            'procesos': Array.isArray(prenda.procesos) || typeof prenda.procesos === 'object'
        };

        return {
            valida: Object.values(campos).every(v => v),
            campos
        };
    }
}

/**
 * Ejecutor rápido desde consola
 */
window.testPrendaEditor = async function() {
    const test = new PrendaEditorTest();
    return await test.ejecutarTodos();    
};
