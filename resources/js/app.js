import './bootstrap';

/*
|--------------------------------------------------------------------------
| Alpine y Chart
|--------------------------------------------------------------------------
*/
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.start();

console.log(' Alpine y Chart cargados desde Vite');

/*
|--------------------------------------------------------------------------
| Operario Module - Load scripts for operario layout and dashboard
|--------------------------------------------------------------------------
*/
const currentPath = window.location?.pathname || '';
const isOperarioRoute = currentPath.startsWith('/operario');

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
