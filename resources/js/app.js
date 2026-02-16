import './bootstrap';

/*
|--------------------------------------------------------------------------
| Bootstrap 4 (requiere jQuery global - cargado desde CDN)
|--------------------------------------------------------------------------
*/
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

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

console.log('✅ jQuery global y Bootstrap 4 cargados correctamente desde Vite');
