import './bootstrap';

import './operario/layout/index';
import './operario/dashboard/index';

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
