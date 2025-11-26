<?php
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$variantes = DB::select('
SELECT vp.id, vp.tipo_manga_id, tm.nombre as manga_nombre, vp.color_id, cp.nombre as color_nombre, 
       vp.tela_id, tp.nombre as tela_nombre, tp.referencia, vp.descripcion_adicional
FROM variantes_prenda vp
LEFT JOIN tipos_manga tm ON vp.tipo_manga_id = tm.id
LEFT JOIN colores_prenda cp ON vp.color_id = cp.id
LEFT JOIN telas_prenda tp ON vp.tela_id = tp.id
WHERE vp.prenda_cotizacion_id IN (
    SELECT id FROM prendas_cotizaciones WHERE cotizacion_id = 14
)
');

echo json_encode($variantes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
