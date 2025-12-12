<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$db = $app->make('db');
$db->delete('DELETE FROM migrations WHERE migration = ?', ['2025_12_12_create_reflectivo_cotizacion_table']);
echo "✅ Registro de migración eliminado\n";
?>
