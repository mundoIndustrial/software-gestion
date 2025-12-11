<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TableConfigController extends Controller
{
    /**
     * Guardar anchos de columnas en archivo JSON
     */
    public function saveColumnWidths(Request $request)
    {
        try {
            $widths = $request->input('widths', []);
            
            if (empty($widths)) {
                return response()->json(['success' => false, 'message' => 'No widths provided'], 400);
            }

            // Guardar en archivo JSON en lugar de CSS
            $jsonPath = storage_path('app/column-widths.json');
            $data = json_encode(['widths' => $widths, 'updated_at' => now()->toIso8601String()], JSON_PRETTY_PRINT);
            
            $bytes = file_put_contents($jsonPath, $data);
            
            if ($bytes === false) {
                return response()->json(['success' => false, 'message' => 'Could not write to file'], 500);
            }

            // Verificar que se guardó
            $verify = file_get_contents($jsonPath);
            $verified = $verify !== false && strpos($verify, 'widths') !== false;

            return response()->json([
                'success' => true,
                'message' => 'Column widths saved successfully',
                'widths' => $widths,
                'bytes_written' => $bytes,
                'verified' => $verified
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
              ->header('Pragma', 'no-cache')
              ->header('Expires', '0');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener anchos de columnas guardados
     */
    public function getColumnWidths()
    {
        try {
            $jsonPath = storage_path('app/column-widths.json');
            
            if (!file_exists($jsonPath)) {
                return response()->json(['widths' => []]);
            }

            $data = json_decode(file_get_contents($jsonPath), true);
            return response()->json($data ?? ['widths' => []])
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            return response()->json(['widths' => []]);
        }
    }

    /**
     * Generar reglas CSS para los anchos de columnas
     */
    private function generateCSSRules(array $widths): string
    {
        $rules = '';

        foreach ($widths as $key => $width) {
            preg_match('/col_(\d+)/', $key, $matches);
            
            if (!isset($matches[1])) {
                continue;
            }

            $columnIndex = (int)$matches[1] + 1;
            $widthValue = (int)$width;

            $rules .= ".table-row .table-cell:nth-child({$columnIndex}) {\n";
            $rules .= "    min-width: {$widthValue}px !important;\n";
            $rules .= "    max-width: {$widthValue}px !important;\n";
            $rules .= "    width: {$widthValue}px !important;\n";
            $rules .= "    flex: 0 0 {$widthValue}px !important;\n";
            $rules .= "}\n\n";
        }

        return $rules;
    }
}
