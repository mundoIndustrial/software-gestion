<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PedidoProduccion;
use App\Application\Services\PedidoPrendaService;

/**
 * Test: Crear pedido COMPLETO con TODAS las relaciones
 * 
 * Uso:
 * php artisan db:seed --class=TestPedidoCompletoGuardadoSeeder
 * 
 * Luego ejecutar:
 * php verificar-guardado-pedido.php
 */
class TestPedidoCompletoGuardadoSeeder extends Seeder
{
    public function run()
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════\n";
        echo "🧪 TEST: CREAR PEDIDO COMPLETO CON TODAS LAS RELACIONES\n";
        echo "═══════════════════════════════════════════════════════\n\n";

        DB::beginTransaction();
        
        try {
            // 1️⃣ CREAR O OBTENER CLIENTE
            echo "1️⃣  Creando cliente de prueba...\n";
            $cliente = \App\Models\Cliente::firstOrCreate(
                ['nombre' => 'TEST CLIENTE COMPLETO SA']
            );
            echo "   Cliente ID: {$cliente->id}\n\n";

            // 1.5️⃣ OBTENER UN ASESOR VÁLIDO
            $asesor = \App\Models\User::first();
            if (!$asesor) {
                throw new \Exception('No hay usuarios en la BD. Crea al menos un usuario primero.');
            }

            // 2️⃣ OBTENER NÚMERO DE PEDIDO
            echo "2️⃣  Generando número de pedido...\n";
            $secuencia = DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->lockForUpdate()
                ->first();
            
            $numeroPedido = $secuencia->siguiente;
            
            DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->increment('siguiente');
            
            echo "   Número de pedido: {$numeroPedido}\n\n";

            // 3️⃣ CREAR PEDIDO PRINCIPAL
            echo "3️⃣  Creando pedido principal...\n";
            $pedido = PedidoProduccion::create([
                'numero_pedido' => $numeroPedido,
                'cliente' => 'TEST CLIENTE COMPLETO SA',
                'cliente_id' => $cliente->id,
                'asesor_id' => $asesor->id,
                'forma_de_pago' => 'CONTADO',
                'estado' => 'PENDIENTE_SUPERVISOR',
                'fecha_de_creacion_de_orden' => now(),
                'cantidad_total' => 0,
            ]);
            echo "   Pedido ID: {$pedido->id}\n";
            echo "   Número: {$pedido->numero_pedido}\n";
            echo "   Asesor: {$asesor->name}\n\n";

            // 4️⃣ PREPARAR DATOS DE PRENDAS COMPLETAS
            echo "4️⃣  Preparando datos de prendas con TODO incluido...\n";
            
            $prendas = [
                // PRENDA 1: Camisa con bordado y reflectivo
                [
                    'nombre_producto' => 'TEST CAMISA CORPORATIVA COMPLETA',
                    'descripcion' => 'Camisa de prueba con todas las relaciones',
                    'genero' => 'dama',
                    'variaciones' => [], // Requerido por PedidoPrendaService
                    
                    // TALLAS (CRÍTICO)
                    'cantidad_talla' => [
                        'dama' => [
                            'S' => 10,
                            'M' => 20,
                            'L' => 15,
                        ]
                    ],
                    
                    // VARIANTES
                    'color_id' => $this->obtenerOCrearColor('AZUL MARINO TEST'),
                    'tela_id' => $this->obtenerOCrearTela('DRILL TEST'),
                    'tipo_manga_id' => $this->obtenerOCrearManga('MANGA LARGA'),
                    'tipo_broche_boton_id' => $this->obtenerOCrearBroche('BOTONES PERLADOS'),
                    'tiene_bolsillos' => true,
                    'tiene_reflectivo' => false,
                    'obs_manga' => 'Manga larga con puño ajustable',
                    'obs_bolsillos' => '2 bolsillos frontales',
                    'obs_broche' => 'Botones blancos',
                    'obs_reflectivo' => '',
                    
                    // IMÁGENES DE PRENDA
                    'imagenes' => [
                        'test/camisa-frontal.jpg',
                        'test/camisa-posterior.jpg',
                    ],
                    
                    // TELAS CON FOTOS
                    'telas' => [
                        [
                            'color_id' => $this->obtenerOCrearColor('AZUL MARINO TEST'),
                            'tela_id' => $this->obtenerOCrearTela('DRILL TEST'),
                            'referencia' => 'TEL-001-TEST',
                            'observaciones' => 'Tela principal azul marino',
                            'fotos' => [
                                'test/tela-azul-muestra.jpg',
                                'test/tela-azul-textura.jpg',
                            ]
                        ],
                        [
                            'color_id' => $this->obtenerOCrearColor('BLANCO TEST'),
                            'tela_id' => $this->obtenerOCrearTela('DRILL TEST'),
                            'referencia' => 'TEL-002-TEST',
                            'observaciones' => 'Tela para cuello y puños',
                            'fotos' => [
                                'test/tela-blanca-muestra.jpg',
                            ]
                        ]
                    ],
                    
                    // PROCESOS CON TALLAS E IMÁGENES
                    'procesos' => [
                        [
                            'tipo_proceso_id' => $this->obtenerOCrearTipoProceso('BORDADO'),
                            'tipo' => 'bordado',
                            'observaciones' => 'Logo corporativo en pecho izquierdo',
                            'ubicaciones' => ['pecho_izquierdo'],
                            'tallas' => [
                                'dama' => [
                                    'S' => 10,
                                    'M' => 20,
                                    'L' => 15,
                                ]
                            ],
                            'imagenes' => [
                                'test/logo-bordado-referencia.jpg',
                            ]
                        ],
                        [
                            'tipo_proceso_id' => $this->obtenerOCrearTipoProceso('REFLECTIVO'),
                            'tipo' => 'reflectivo',
                            'observaciones' => 'Franjas reflectivas en mangas',
                            'ubicaciones' => ['manga_izquierda', 'manga_derecha'],
                            'tallas' => [
                                'dama' => [
                                    'S' => 5,
                                    'M' => 10,
                                ]
                            ],
                            'imagenes' => []
                        ]
                    ]
                ],
                
                // PRENDA 2: Pantalón con reflectivo
                [
                    'nombre_producto' => 'TEST PANTALÓN CARGO COMPLETO',
                    'descripcion' => 'Pantalón de prueba con procesos',
                    'genero' => 'caballero',
                    'variaciones' => [], // Requerido por PedidoPrendaService
                    
                    'cantidad_talla' => [
                        'caballero' => [
                            '30' => 8,
                            '32' => 15,
                            '34' => 12,
                        ]
                    ],
                    
                    'color_id' => $this->obtenerOCrearColor('GRIS OXFORD TEST'),
                    'tela_id' => $this->obtenerOCrearTela('DRILL RESISTENTE TEST'),
                    'tipo_manga_id' => null,
                    'tipo_broche_boton_id' => $this->obtenerOCrearBroche('CREMALLERA'),
                    'tiene_bolsillos' => true,
                    'tiene_reflectivo' => true,
                    'obs_manga' => '',
                    'obs_bolsillos' => '6 bolsillos cargo reforzados',
                    'obs_broche' => 'Cremallera metálica',
                    'obs_reflectivo' => 'Cintas reflectivas en piernas',
                    
                    'imagenes' => [
                        'test/pantalon-frontal.jpg',
                    ],
                    
                    'telas' => [
                        [
                            'color_id' => $this->obtenerOCrearColor('GRIS OXFORD TEST'),
                            'tela_id' => $this->obtenerOCrearTela('DRILL RESISTENTE TEST'),
                            'referencia' => 'TEL-DRILL-TEST',
                            'observaciones' => 'Drill resistente',
                            'fotos' => [
                                'test/tela-drill-gris.jpg',
                            ]
                        ]
                    ],
                    
                    'procesos' => [
                        [
                            'tipo_proceso_id' => $this->obtenerOCrearTipoProceso('REFLECTIVO'),
                            'tipo' => 'reflectivo',
                            'observaciones' => 'Cintas reflectivas horizontales',
                            'ubicaciones' => ['pierna_izquierda', 'pierna_derecha'],
                            'tallas' => [
                                'caballero' => [
                                    '30' => 8,
                                    '32' => 15,
                                    '34' => 12,
                                ]
                            ],
                            'imagenes' => [
                                'test/reflectivo-piernas.jpg',
                            ]
                        ]
                    ]
                ]
            ];

            echo "   Preparadas 2 prendas con TODOS los datos\n\n";

            // 5️⃣ GUARDAR PRENDAS USANDO PedidoPrendaService
            echo "5️⃣  Guardando prendas con PedidoPrendaService...\n";
            $pedidoPrendaService = app(PedidoPrendaService::class);
            $pedidoPrendaService->guardarPrendasEnPedido($pedido, $prendas);
            echo "   Prendas guardadas\n\n";

            // 6️⃣ ACTUALIZAR CANTIDAD TOTAL
            echo "6️⃣  Calculando cantidad total...\n";
            $cantidadTotal = DB::table('prenda_pedido_tallas')
                ->whereIn('prenda_pedido_id', $pedido->prendas()->pluck('id'))
                ->sum('cantidad');
            
            $pedido->update(['cantidad_total' => $cantidadTotal]);
            echo "   Cantidad total: {$cantidadTotal}\n\n";

            DB::commit();

            // 7️⃣ VERIFICAR QUE SE GUARDÓ TODO
            echo "7️⃣  VERIFICANDO QUE SE GUARDÓ EN TODAS LAS TABLAS...\n";
            echo "─────────────────────────────────────────────────────\n";
            
            $prendasGuardadas = $pedido->prendas;
            $prendasIds = $prendasGuardadas->pluck('id')->toArray();
            
            $verificacion = [
                'pedidos_produccion' => 1,
                'prendas_pedido' => count($prendasIds),
                'prenda_pedido_tallas' => DB::table('prenda_pedido_tallas')
                    ->whereIn('prenda_pedido_id', $prendasIds)
                    ->count(),
                'prenda_pedido_variantes' => DB::table('prenda_pedido_variantes')
                    ->whereIn('prenda_pedido_id', $prendasIds)
                    ->count(),
                'prenda_pedido_colores_telas' => DB::table('prenda_pedido_colores_telas')
                    ->whereIn('prenda_pedido_id', $prendasIds)
                    ->count(),
                'prenda_fotos_pedido' => DB::table('prenda_fotos_pedido')
                    ->whereIn('prenda_pedido_id', $prendasIds)
                    ->count(),
                'prenda_fotos_tela_pedido' => DB::table('prenda_fotos_tela_pedido')
                    ->whereIn('prenda_pedido_colores_telas_id',
                        DB::table('prenda_pedido_colores_telas')
                            ->whereIn('prenda_pedido_id', $prendasIds)
                            ->pluck('id')
                    )
                    ->count(),
                'pedidos_procesos_prenda_detalles' => DB::table('pedidos_procesos_prenda_detalles')
                    ->whereIn('prenda_pedido_id', $prendasIds)
                    ->count(),
                'pedidos_procesos_prenda_tallas' => DB::table('pedidos_procesos_prenda_tallas')
                    ->whereIn('proceso_prenda_detalle_id',
                        DB::table('pedidos_procesos_prenda_detalles')
                            ->whereIn('prenda_pedido_id', $prendasIds)
                            ->pluck('id')
                    )
                    ->count(),
                'pedidos_procesos_imagenes' => DB::table('pedidos_procesos_imagenes')
                    ->whereIn('proceso_prenda_detalle_id',
                        DB::table('pedidos_procesos_prenda_detalles')
                            ->whereIn('prenda_pedido_id', $prendasIds)
                            ->pluck('id')
                    )
                    ->count(),
            ];

            $errores = [];
            foreach ($verificacion as $tabla => $cantidad) {
                $esperado = $this->cantidadEsperada($tabla);
                $icono = $cantidad > 0 ? '' : '';
                $estado = $cantidad >= $esperado ? 'OK' : 'FALTA';
                
                echo "   {$icono} {$tabla}: {$cantidad} registros [{$estado}]\n";
                
                if ($cantidad < $esperado) {
                    $errores[] = "{$tabla} (esperado: >= {$esperado}, obtenido: {$cantidad})";
                }
            }

            echo "\n";
            echo "═══════════════════════════════════════════════════════\n";
            
            if (empty($errores)) {
                echo " ¡TEST EXITOSO! TODAS LAS TABLAS TIENEN DATOS\n";
                echo "═══════════════════════════════════════════════════════\n\n";
                echo "📦 Pedido de prueba creado: {$pedido->numero_pedido}\n";
                echo "🔍 Ejecuta para ver detalles:\n";
                echo "   php verificar-guardado-pedido.php {$pedido->numero_pedido}\n\n";
            } else {
                echo " TEST FALLIDO - TABLAS SIN DATOS:\n";
                echo "═══════════════════════════════════════════════════════\n\n";
                foreach ($errores as $error) {
                    echo "    {$error}\n";
                }
                echo "\n";
            }

        } catch (\Exception $e) {
            DB::rollBack();
            echo "\n";
            echo " ERROR EN TEST:\n";
            echo "   {$e->getMessage()}\n";
            echo "   Archivo: {$e->getFile()}:{$e->getLine()}\n\n";
            throw $e;
        }
    }

    /**
     * Obtener o crear color
     */
    private function obtenerOCrearColor(string $nombre): int
    {
        $color = DB::table('colores_prenda')->where('nombre', $nombre)->first();
        
        if ($color) {
            return $color->id;
        }
        
        return DB::table('colores_prenda')->insertGetId([
            'nombre' => $nombre,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Obtener o crear tela
     */
    private function obtenerOCrearTela(string $nombre): int
    {
        $tela = DB::table('telas_prenda')->where('nombre', $nombre)->first();
        
        if ($tela) {
            return $tela->id;
        }
        
        return DB::table('telas_prenda')->insertGetId([
            'nombre' => $nombre,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Obtener o crear tipo de manga
     */
    private function obtenerOCrearManga(string $nombre): int
    {
        $manga = DB::table('tipos_manga')->where('nombre', $nombre)->first();
        
        if ($manga) {
            return $manga->id;
        }
        
        return DB::table('tipos_manga')->insertGetId([
            'nombre' => $nombre,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Obtener o crear tipo de broche
     */
    private function obtenerOCrearBroche(string $nombre): int
    {
        $broche = DB::table('tipos_broche_boton')->where('nombre', $nombre)->first();
        
        if ($broche) {
            return $broche->id;
        }
        
        return DB::table('tipos_broche_boton')->insertGetId([
            'nombre' => $nombre,
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Obtener o crear tipo de proceso
     */
    private function obtenerOCrearTipoProceso(string $nombre): int
    {
        $proceso = DB::table('tipos_procesos')->where('nombre', 'like', "%{$nombre}%")->first();
        
        if ($proceso) {
            return $proceso->id;
        }
        
        return DB::table('tipos_procesos')->insertGetId([
            'nombre' => $nombre,
            'descripcion' => "Proceso: {$nombre}",
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Cantidad esperada mínima por tabla
     */
    private function cantidadEsperada(string $tabla): int
    {
        $esperados = [
            'pedidos_produccion' => 1,
            'prendas_pedido' => 2,
            'prenda_pedido_tallas' => 6, // 3 tallas dama + 3 tallas caballero
            'prenda_pedido_variantes' => 2, // 1 por prenda
            'prenda_pedido_colores_telas' => 3, // 2 telas prenda1 + 1 tela prenda2
            'prenda_fotos_pedido' => 3, // 2 fotos prenda1 + 1 foto prenda2
            'prenda_fotos_tela_pedido' => 4, // 2+1 telas prenda1 + 1 tela prenda2
            'pedidos_procesos_prenda_detalles' => 3, // 2 procesos prenda1 + 1 proceso prenda2
            'pedidos_procesos_prenda_tallas' => 8, // Tallas de los 3 procesos
            'pedidos_procesos_imagenes' => 2, // 1 imagen bordado + 1 imagen reflectivo
        ];

        return $esperados[$tabla] ?? 0;
    }
}
