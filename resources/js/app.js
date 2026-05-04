import './bootstrap';

/*
|--------------------------------------------------------------------------
| Alpine
|--------------------------------------------------------------------------
*/
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

const moduleName = document.body?.dataset?.module || '';

// Chart no es necesario en insumos/materiales.
// En el resto de módulos se mantiene disponible en window.Chart.
if (moduleName !== 'insumos-materiales') {
    import('chart.js/auto')
        .then(({ default: Chart }) => {
            window.Chart = Chart;
        })
        .catch((error) => {
            console.error('[app.js] Error cargando Chart:', error);
        });
}

/*
|--------------------------------------------------------------------------
| Operario Module - Load scripts for operario layout and dashboard
|--------------------------------------------------------------------------
*/
const currentPath = window.location?.pathname || '';
const isOperarioRoute = currentPath.startsWith('/operario') || currentPath.startsWith('/entregas-talleres');

if (isOperarioRoute) {
    Promise.all([
        import('./operario/layout/index'),
        import('./operario/dashboard/index'),
    ]).catch((error) => {
        console.error('[app.js] Error cargando modulo operario:', error);
    });
}

/*
|--------------------------------------------------------------------------
| Recepcion Despacho Module - Load scripts for recepcion-despacho
|--------------------------------------------------------------------------
*/
const isRecepcionRoute = currentPath.startsWith('/recepcion-despacho');

if (isRecepcionRoute) {
    import('./recepcion-despacho/entry.js').catch((error) => {
        console.error('[app.js] Error cargando modulo recepcion-despacho:', error);
    });
}
