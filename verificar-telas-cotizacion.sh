#!/bin/bash
# Script para verificar telas de cotización COT-00016

echo "🔍 Verificando telas de COT-00016..."
echo ""
echo "Para ver los logs de Laravel:"
echo "  tail -f storage/logs/laravel.log | grep OBTENER-PRENDA-COMPLETA"
echo ""
echo "Para debuggear directamente en la BD:"
echo "  SELECT * FROM prendas_cot WHERE cotizacion_id = 5"
echo "  SELECT * FROM prenda_telas_cot WHERE prenda_cot_id = 5"
echo "  SELECT pt.*, t.nombre as tela_nombre, c.nombre as color_nombre"
echo "    FROM prenda_telas_cot pt"
echo "    LEFT JOIN telas_prendas t ON t.id = pt.tela_id"
echo "    LEFT JOIN colores_prendas c ON c.id = pt.color_id"
echo "    WHERE pt.prenda_cot_id = 5"
echo ""
