import './bootstrap';

/*
|--------------------------------------------------------------------------
| jQuery (instalado por npm)
|--------------------------------------------------------------------------
*/
import $ from 'jquery';
window.$ = $;
window.jQuery = $;

/*
|--------------------------------------------------------------------------
| Bootstrap 4 (requiere jQuery)
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

console.log('✅ jQuery y Bootstrap cargados correctamente desde Vite');
