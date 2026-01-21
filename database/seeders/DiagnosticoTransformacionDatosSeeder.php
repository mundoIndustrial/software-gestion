<?php

namespace Database\Seeders;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PrendaVariantePed;
use App\Models\PrendaPedidoColorTela;
use App\Models\PrendaFotoTelaPedido;
use App\Models\User;
use App\Application\Services\PedidoPrendaService;
use Illuminate\Database\Seeder;

class DiagnosticoTransformacionDatosSeeder extends Seeder
{
    public function run(): void
    {
        echo "\n========================================\n";
        echo "🔍 DIAGNÓSTICO: Transformación de Datos Frontend -> Backend\n";
        echo "========================================\n\n";

        try {
            // 1. Crear usuario
            echo "  Creando usuario...\n";
            $asesora = User::factory()->create([
                'name' => 'Asesora Diag ' . time(),
                'email' => 'asesora' . time() . '@test.com',
            ]);
            echo "  Usuario creado\n\n";

            // 2. Crear pedido
            echo "  Creando pedido...\n";
            $pedido = PedidoProduccion::create([
                'numero_pedido' => 88888,
                'cliente' => 'Cliente Diagnóstico',
                'asesor_id' => $asesora->id,
                'forma_de_pago' => 'Contado',
                'estado' => 'pendiente',
            ]);
            echo "  Pedido creado: #{$pedido->numero_pedido}\n\n";

            // 3. Datos EXACTAMENTE como envía el frontend
            echo "  Preparando datos EXACTAMENTE como envía el frontend...\n";
            $datosDelFrontend = [
                'tipo' => 'prenda_nueva',
                'nombre_producto' => 'ERt',
                'descripcion' => 'ERTR',
                'origen' => 'bodega',
                'procesos' => [
                    'reflectivo' => [
                        'tipo' => 'reflectivo',
                        'datos' => [
                            'tipo' => 'reflectivo',
                            'ubicaciones' => ['erwerwe', 'erwer'],
                            'observaciones' => 'werwer',
                            'tallas' => [
                                'dama' => ['S' => 20, 'M' => 20],
                                'caballero' => []
                            ],
                            'imagenes' => [['file' => null]]
                        ]
                    ]
                ],
                'cantidad_talla' => [
                    'dama-S' => 20,
                    'dama-M' => 20
                ],
                'variaciones' => [],
                'telas' => [
                    [
                        'tela' => 'ER',
                        'color' => 'TER',
                        'referencia' => 'TERTER',
                        'imagenes' => [
                            [
                                'file' => null,
                                'nombre' => 'CODIGO DE TELA.png',
                                'tamaño' => 11291
                            ]
                        ]
                    ],
                    [
                        'tela' => 'TERT',
                        'color' => 'ER',
                        'referencia' => 'ERT',
                        'imagenes' => [
                            [
                                'file' => null,
                                'nombre' => 'CODIGO DE TELA.png',
                                'tamaño' => 11291
                            ]
                        ]
                    ]
                ],
                'imagenes' => [
                    [
                        'file' => null,
                        'previewUrl' => 'blob:http://servermi:8000/34b0d377-b1bf-4388-9631-74acc5ce6782',
                        'nombre' => 'CAMISA DRILL.png',
                        'tamaño' => 27952
                    ]
                ]
            ];
            echo "  Datos preparados\n\n";

            // 4. Analizar estructura
            echo "4️⃣  ANÁLISIS DE ESTRUCTURA:\n";
            echo "   Telas recibidas: " . count($datosDelFrontend['telas']) . "\n";
            foreach ($datosDelFrontend['telas'] as $idx => $tela) {
                echo "      Tela $idx: {$tela['tela']} - {$tela['color']}\n";
                echo "         - Imágenes: " . count($tela['imagenes'] ?? []) . "\n";
                foreach ($tela['imagenes'] ?? [] as $img) {
                    echo "            - Nombre: {$img['nombre']}, File: " . (is_null($img['file']) ? 'NULL' : 'OBJECT') . "\n";
                }
            }
            echo "\n";

            // 5. Transformar cantidad_talla
            echo "5️⃣  TRANSFORMANDO cantidad_talla:\n";
            $cantidadTallaOriginal = $datosDelFrontend['cantidad_talla'];
            echo "   Original: " . json_encode($cantidadTallaOriginal) . "\n";
            
            // Convertir de "dama-S" => 20 a ["dama" => ["S" => 20]]
            $cantidadTallaTransformada = [];
            foreach ($cantidadTallaOriginal as $key => $cantidad) {
                list($genero, $talla) = explode('-', $key);
                if (!isset($cantidadTallaTransformada[$genero])) {
                    $cantidadTallaTransformada[$genero] = [];
                }
                $cantidadTallaTransformada[$genero][$talla] = $cantidad;
            }
            echo "   Transformada: " . json_encode($cantidadTallaTransformada) . "\n\n";

            // 6. Transformar telas
            echo "6️⃣  TRANSFORMANDO telas:\n";
            $telasTransformadas = [];
            foreach ($datosDelFrontend['telas'] as $tela) {
                $telaTransformada = [
                    'tela' => $tela['tela'],
                    'color' => $tela['color'],
                    'referencia' => $tela['referencia'],
                    'fotos' => []  // El backend espera 'fotos', no 'imagenes'
                ];
                
                // Las imágenes vienen como objetos vacíos {file: null}
                // El backend espera strings con rutas
                if (!empty($tela['imagenes'])) {
                    foreach ($tela['imagenes'] as $img) {
                        // Problema: file es null, no hay ruta
                        echo "     Imagen de tela: {$img['nombre']}\n";
                        echo "       - File: " . (is_null($img['file']) ? 'NULL' : 'OBJECT') . "\n";
                        echo "       - Tamaño: {$img['tamaño']}\n";
                        
                        // Si file es null, no se puede procesar
                        if (is_null($img['file'])) {
                            echo "        NO SE PUEDE PROCESAR - file es NULL\n";
                        }
                    }
                }
                
                $telasTransformadas[] = $telaTransformada;
            }
            echo "\n";

            // 7. Transformar imágenes de prenda
            echo "7️⃣  TRANSFORMANDO imágenes de prenda:\n";
            if (!empty($datosDelFrontend['imagenes'])) {
                foreach ($datosDelFrontend['imagenes'] as $img) {
                    echo "   📸 Imagen: {$img['nombre']}\n";
                    echo "       - File: " . (is_null($img['file']) ? 'NULL' : 'OBJECT') . "\n";
                    echo "       - PreviewUrl: " . substr($img['previewUrl'], 0, 50) . "...\n";
                    echo "       - Tamaño: {$img['tamaño']}\n";
                    
                    if (is_null($img['file'])) {
                        echo "        NO SE PUEDE PROCESAR - file es NULL\n";
                    }
                }
            }
            echo "\n";

            // 8. Estructura esperada por backend
            echo "8️⃣  ESTRUCTURA ESPERADA POR BACKEND:\n";
            $estructuraEsperada = [
                'nombre_producto' => 'ERt',
                'descripcion' => 'ERTR',
                'de_bodega' => 1,
                'origen' => 'bodega',
                'variaciones' => '{}',
                'fotos' => [],  // Espera array de UploadedFile o strings con rutas
                'logos' => [],
                'procesos' => [
                    'reflectivo' => [
                        'tipo' => 'reflectivo',
                        'tallas' => ['dama' => ['S' => 20, 'M' => 20]]
                    ]
                ],
                'cantidad_talla' => ['dama' => ['S' => 20, 'M' => 20]],
                'telas' => [
                    [
                        'tela' => 'ER',
                        'color' => 'TER',
                        'referencia' => 'TERTER',
                        'fotos' => []  // Espera UploadedFile o strings con rutas
                    ]
                ]
            ];
            echo "  Estructura esperada:\n";
            echo "      - fotos: array de UploadedFile o strings\n";
            echo "      - telas[].fotos: array de UploadedFile o strings\n";
            echo "      - cantidad_talla: {genero: {talla: cantidad}}\n\n";

            // 9. Resumen del problema
            echo "========================================\n";
            echo "🔴 PROBLEMA IDENTIFICADO:\n";
            echo "========================================\n";
            echo "1. Frontend envía: 'imagenes' con {file: null}\n";
            echo "2. Backend espera: 'fotos' con UploadedFile o strings\n";
            echo "3. Frontend envía: 'cantidad_talla' como 'dama-S' => 20\n";
            echo "4. Backend espera: 'cantidad_talla' como {dama: {S: 20}}\n";
            echo "5. Las imágenes tienen file: null, no se pueden procesar\n\n";

            echo "========================================\n";
            echo " SOLUCIÓN NECESARIA:\n";
            echo "========================================\n";
            echo "1. Frontend debe enviar 'fotos' en lugar de 'imagenes'\n";
            echo "2. Frontend debe transformar cantidad_talla antes de enviar\n";
            echo "3. Las imágenes deben ser UploadedFile o URLs válidas\n";
            echo "4. Backend debe mapear 'imagenes' a 'fotos' si es necesario\n\n";

        } catch (\Exception $e) {
            echo "\n ERROR:\n";
            echo "   {$e->getMessage()}\n";
            echo "   Archivo: {$e->getFile()}\n";
            echo "   Línea: {$e->getLine()}\n\n";
            throw $e;
        }
    }
}
