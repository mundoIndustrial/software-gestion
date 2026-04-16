<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\PedidosProcesosPrendaTalla;
use Illuminate\Support\Facades\DB;

class PedidoProcesoTallaBuilder
{
    public function crearDesdeDatosExtendidosPorTallas(
        PedidosProcesosPrendaDetalle $proceso,
        array $datosExtendidos,
        array $tallasCantidad = []
    ): void {
        foreach ($datosExtendidos as $genero => $tallasDatos) {
            if (!is_array($tallasDatos)) {
                continue;
            }

            $tallasAgrupadas = [];

            foreach ($tallasDatos as $tallaKey => $tallaData) {
                if (!is_array($tallaData)) {
                    continue;
                }

                $partes = explode('__', (string) $tallaKey, 2);
                $tallaReal = trim($partes[0]);
                $colorNombre = isset($partes[1]) ? trim($partes[1]) : null;
                $cantidad = (int) ($tallasCantidad[$genero][$tallaKey] ?? 0);

                if (!isset($tallasAgrupadas[$tallaReal])) {
                    $tallasAgrupadas[$tallaReal] = [
                        'totalCantidad' => 0,
                        'colores' => [],
                    ];
                }

                $tallasAgrupadas[$tallaReal]['totalCantidad'] += $cantidad;
                $tallasAgrupadas[$tallaReal]['colores'][] = [
                    'nombre' => $colorNombre,
                    'cantidad' => $cantidad,
                    'ubicaciones' => $tallaData['ubicaciones'] ?? [],
                    'observaciones' => $tallaData['observaciones'] ?? '',
                ];
            }

            foreach ($tallasAgrupadas as $tallaReal => $tallaAgrupadaData) {
                // Extraer ubicaciones y observaciones de todos los colores
                $todasLasUbicaciones = [];
                $primerObservacion = null;
                
                foreach ($tallaAgrupadaData['colores'] as $colorData) {
                    if (!empty($colorData['ubicaciones'])) {
                        $todasLasUbicaciones = array_merge($todasLasUbicaciones, $colorData['ubicaciones']);
                    }
                    if (!$primerObservacion && !empty($colorData['observaciones'])) {
                        $primerObservacion = $colorData['observaciones'];
                    }
                }

                DB::table('pedidos_procesos_prenda_tallas')->updateOrInsert(
                    [
                        'proceso_prenda_detalle_id' => $proceso->id,
                        'genero' => strtoupper($genero),
                        'talla' => $tallaReal,
                    ],
                    [
                        'cantidad' => (int) $tallaAgrupadaData['totalCantidad'],
                        'ubicaciones' => !empty($todasLasUbicaciones) ? json_encode($todasLasUbicaciones) : null,
                        'observaciones' => $primerObservacion,
                        'updated_at' => now(),
                    ]
                );

                $tallaProcesoId = DB::table('pedidos_procesos_prenda_tallas')
                    ->where('proceso_prenda_detalle_id', $proceso->id)
                    ->where('genero', strtoupper($genero))
                    ->where('talla', $tallaReal)
                    ->value('id');

                // Guardar ubicaciones y observaciones por color si existen
                foreach ($tallaAgrupadaData['colores'] as $colorData) {
                    // Si hay nombre de color, guardar en tabla de colores
                    if (!empty($colorData['nombre'])) {
                        DB::table('pedidos_procesos_prenda_talla_colores')->updateOrInsert(
                            [
                                'pedidos_procesos_prenda_talla_id' => $tallaProcesoId,
                                'color_nombre' => $colorData['nombre'],
                            ],
                            [
                                'tela_nombre' => null,
                                'ubicaciones' => !empty($colorData['ubicaciones']) ? json_encode($colorData['ubicaciones']) : null,
                                'observaciones' => $colorData['observaciones'] ?? null,
                                'cantidad' => (int) $colorData['cantidad'],
                                'updated_at' => now(),
                            ]
                        );
                    }
                }
            }
        }
    }

    public function crearDesdeMapaSimple(
        PedidosProcesosPrendaDetalle $proceso,
        array $tallas,
        array $datosExtendidos = []
    ): void {
        $generoMap = ['dama' => 'DAMA', 'caballero' => 'CABALLERO', 'unisex' => 'UNISEX'];

        // Para determinar si es realmente sobremedida, verificar si la prenda tiene es_sobremedida=1
        $prendaPedido = $proceso->prendaPedido;
        $prendasTallasCs = $prendaPedido->tallas()
            ->where('es_sobremedida', 1)
            ->exists();

        foreach ($tallas as $generoBD => $tallasCant) {
            if (!is_array($tallasCant) || empty($tallasCant)) {
                continue;
            }

            if (strtolower($generoBD) === 'sobremedida') {
                Log::info('[PedidoProcesoTallaBuilder::crearDesdeMapaSimple] SOBREMEDIDA detectado', [
                    'proceso_id' => $proceso->id,
                    'tallasCant_keys' => array_keys($tallasCant),
                    'prenda_tiene_sobremedida' => $prendasTallasCs,
                ]);
                foreach ($tallasCant as $tallaParaSobremedida => $cantidad) {
                    $cantidad = (int) $cantidad;
                    if ($cantidad > 0) {
                        // Solo marcar como sobremedida si la prenda tiene es_sobremedida=1
                        $esSobremedida = $prendasTallasCs ? 1 : 0;
                        Log::info('[PedidoProcesoTallaBuilder::crearDesdeMapaSimple] Guardando', [
                            'genero' => 'UNISEX',
                            'talla' => strtoupper($tallaParaSobremedida),
                            'es_sobremedida' => $esSobremedida,
                        ]);
                        PedidosProcesosPrendaTalla::create([
                            'proceso_prenda_detalle_id' => $proceso->id,
                            'genero' => 'UNISEX',
                            'talla' => strtoupper($tallaParaSobremedida),
                            'cantidad' => $cantidad,
                            'es_sobremedida' => $esSobremedida,
                        ]);
                    }
                }

                continue;
            }

            $generoEnum = $generoMap[strtolower($generoBD)] ?? strtoupper($generoBD);

            foreach ($tallasCant as $tallaKey => $cantidad) {
                $cantidad = (int) $cantidad;
                if ($cantidad <= 0) {
                    continue;
                }

                $partes = explode('__', (string) $tallaKey);
                $tallaReal = $partes[0];
                $colorNombre = $partes[1] ?? null;

                $ubicacionesTalla = null;
                $observacionesTalla = null;

                if (!empty($datosExtendidos)) {
                    $generoLower = strtolower($generoBD);
                    $tallaDatos = $datosExtendidos[$generoLower][$tallaKey] ?? null;

                    if ($tallaDatos) {
                        if (isset($tallaDatos['ubicaciones']) && !empty($tallaDatos['ubicaciones'])) {
                            $ubicacionesTalla = json_encode($tallaDatos['ubicaciones']);
                        }
                        if (isset($tallaDatos['observaciones'])) {
                            $observacionesTalla = $tallaDatos['observaciones'];
                        }
                    }
                }

                $tallaCreada = PedidosProcesosPrendaTalla::create([
                    'proceso_prenda_detalle_id' => $proceso->id,
                    'genero' => $generoEnum,
                    'talla' => (string) $tallaReal,
                    'cantidad' => $cantidad,
                    'es_sobremedida' => 0,
                    'ubicaciones' => $ubicacionesTalla,
                    'observaciones' => $observacionesTalla,
                ]);

                if (!empty($colorNombre)) {
                    DB::table('pedidos_procesos_prenda_talla_colores')->insert([
                        'pedidos_procesos_prenda_talla_id' => $tallaCreada->id,
                        'color_nombre' => $colorNombre,
                        'tela_nombre' => null,
                        'cantidad' => $cantidad,
                        'ubicaciones' => $ubicacionesTalla,
                        'observaciones' => $observacionesTalla,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function crearDesdeMapaConAsignaciones(
        PedidosProcesosPrendaDetalle $proceso,
        array $tallas,
        array $asignacionesColores = [],
        array $datosExtendidos = []
    ): void {
        $generoMap = ['dama' => 'DAMA', 'caballero' => 'CABALLERO', 'unisex' => 'UNISEX'];

        // Para determinar si es realmente sobremedida, verificar si la prenda tiene es_sobremedida=1
        $prendaPedido = $proceso->prendaPedido;
        $prendasTallasCs = $prendaPedido->tallas()
            ->where('es_sobremedida', 1)
            ->exists();

        foreach ($tallas as $generoBD => $tallasCant) {
            if (!is_array($tallasCant) || empty($tallasCant)) {
                continue;
            }

            if (strtolower($generoBD) === 'sobremedida') {
                Log::info('[PedidoProcesoTallaBuilder::crearDesdeMapaConAsignaciones] SOBREMEDIDA detectado', [
                    'proceso_id' => $proceso->id,
                    'tallasCant_keys' => array_keys($tallasCant),
                    'prenda_tiene_sobremedida' => $prendasTallasCs,
                ]);
                foreach ($tallasCant as $tallaParaSobremedida => $cantidad) {
                    $cantidad = (int) $cantidad;
                    if ($cantidad > 0) {
                        // Solo marcar como sobremedida si la prenda tiene es_sobremedida=1
                        $esSobremedida = $prendasTallasCs ? 1 : 0;
                        Log::info('[PedidoProcesoTallaBuilder::crearDesdeMapaConAsignaciones] Guardando', [
                            'genero' => 'UNISEX',
                            'talla' => strtoupper($tallaParaSobremedida),
                            'es_sobremedida' => $esSobremedida,
                        ]);
                        PedidosProcesosPrendaTalla::create([
                            'proceso_prenda_detalle_id' => $proceso->id,
                            'genero' => 'UNISEX',
                            'talla' => strtoupper($tallaParaSobremedida),
                            'cantidad' => $cantidad,
                            'es_sobremedida' => $esSobremedida,
                        ]);
                    }
                }

                continue;
            }

            $generoEnum = $generoMap[strtolower($generoBD)] ?? null;
            if (!$generoEnum) {
                continue;
            }

            $tieneFormatoTallaColor = false;
            foreach (array_keys($tallasCant) as $key) {
                if (str_contains((string) $key, '__')) {
                    $tieneFormatoTallaColor = true;
                    break;
                }
            }

            if ($tieneFormatoTallaColor) {
                $this->crearTallasConColoresEmbebidos($proceso, $generoBD, $generoEnum, $tallasCant, $asignacionesColores, $datosExtendidos);
                continue;
            }

            $this->crearTallasNormalesConAsignaciones($proceso, $generoBD, $generoEnum, $tallasCant, $asignacionesColores, $datosExtendidos);
        }
    }

    private function crearTallasConColoresEmbebidos(
        PedidosProcesosPrendaDetalle $proceso,
        string $generoBD,
        string $generoEnum,
        array $tallasCant,
        array $asignacionesColores,
        array $datosExtendidos
    ): void {
        $tallasAgrupadas = [];

        foreach ($tallasCant as $tallaColorKey => $cantidad) {
            $cantidad = (int) $cantidad;
            if ($cantidad <= 0) {
                continue;
            }

            $partes = explode('__', (string) $tallaColorKey, 2);
            $tallaReal = trim($partes[0]);
            $colorNombre = isset($partes[1]) ? trim($partes[1]) : null;

            if (!isset($tallasAgrupadas[$tallaReal])) {
                $tallasAgrupadas[$tallaReal] = [
                    'totalCantidad' => 0,
                    'colores' => [],
                ];
            }

            $tallasAgrupadas[$tallaReal]['totalCantidad'] += $cantidad;

            if ($colorNombre) {
                $tallasAgrupadas[$tallaReal]['colores'][] = [
                    'nombre' => $colorNombre,
                    'cantidad' => $cantidad,
                ];
            }
        }

        $telaGuardar = null;
        $generoNormalizado = strtolower(trim($generoBD));
        foreach ($asignacionesColores as $asignacion) {
            if (
                is_array($asignacion) &&
                isset($asignacion['genero'], $asignacion['tela']) &&
                strtolower(trim((string) $asignacion['genero'])) === $generoNormalizado
            ) {
                $telaGuardar = $asignacion['tela'];
                break;
            }
        }

        foreach ($tallasAgrupadas as $tallaReal => $data) {
            $tallaProceso = PedidosProcesosPrendaTalla::create([
                'proceso_prenda_detalle_id' => $proceso->id,
                'genero' => $generoEnum,
                'talla' => $tallaReal,
                'cantidad' => $data['totalCantidad'],
                'es_sobremedida' => 0,
            ]);

            foreach ($data['colores'] as $colorItem) {
                $claveDataExtendidos = "{$tallaReal}__{$colorItem['nombre']}";
                $observacionesColor = null;
                $ubicacionesColor = null;

                if (!empty($datosExtendidos)) {
                    $generoKey = strtolower(trim($generoBD));
                    if (isset($datosExtendidos[$generoKey][$claveDataExtendidos])) {
                        $datosColor = $datosExtendidos[$generoKey][$claveDataExtendidos];
                        $observacionesColor = $datosColor['observaciones'] ?? null;
                        $ubicacionesColor = $datosColor['ubicaciones'] ?? null;
                    } elseif (isset($datosExtendidos[$generoKey][$tallaReal])) {
                        $datosColor = $datosExtendidos[$generoKey][$tallaReal];
                        $observacionesColor = $datosColor['observaciones'] ?? null;
                        $ubicacionesColor = $datosColor['ubicaciones'] ?? null;
                    }
                }

                $tallaProceso->coloresAsignados()->create([
                    'color_nombre' => $colorItem['nombre'],
                    'tela_nombre' => $telaGuardar,
                    'cantidad' => (int) $colorItem['cantidad'],
                    'observaciones' => $observacionesColor,
                    'ubicaciones' => !empty($ubicacionesColor) ? json_encode($ubicacionesColor) : null,
                ]);
            }
        }
    }

    private function crearTallasNormalesConAsignaciones(
        PedidosProcesosPrendaDetalle $proceso,
        string $generoBD,
        string $generoEnum,
        array $tallasCant,
        array $asignacionesColores,
        array $datosExtendidos
    ): void {
        foreach ($tallasCant as $talla => $cantidad) {
            $cantidad = (int) $cantidad;
            if ($cantidad <= 0) {
                continue;
            }

            $generoNormalizado = strtolower(trim($generoBD));
            $tallaNormalizada = trim((string) $talla);
            $claveEncontrada = $this->encontrarClaveAsignacion($generoNormalizado, $tallaNormalizada, $asignacionesColores);

            $observacionesTalla = null;
            $ubicacionesTalla = null;
            if (!empty($datosExtendidos)) {
                $generoKey = strtolower(trim($generoBD));
                if (isset($datosExtendidos[$generoKey][$talla])) {
                    $datosTalla = $datosExtendidos[$generoKey][$talla];
                    $observacionesTalla = $datosTalla['observaciones'] ?? null;
                    $ubicacionesTalla = $datosTalla['ubicaciones'] ?? null;
                }
            }

            $tallaProceso = PedidosProcesosPrendaTalla::create([
                'proceso_prenda_detalle_id' => $proceso->id,
                'genero' => $generoEnum,
                'talla' => $talla,
                'cantidad' => $cantidad,
                'es_sobremedida' => 0,
                'observaciones' => $observacionesTalla,
                'ubicaciones' => !empty($ubicacionesTalla) ? json_encode($ubicacionesTalla) : null,
            ]);

            if ($claveEncontrada && isset($asignacionesColores[$claveEncontrada])) {
                $asignacion = $asignacionesColores[$claveEncontrada];
                $telaGuardar = $asignacion['tela'] ?? null;

                if (isset($asignacion['colores']) && is_array($asignacion['colores'])) {
                    foreach ($asignacion['colores'] as $colorItem) {
                        $colorNombre = $colorItem['nombre'] ?? null;
                        if (!$colorNombre) {
                            continue;
                        }

                        $claveColorDataExtendidos = "{$talla}__{$colorNombre}";
                        $observacionesColor = null;
                        $ubicacionesColor = null;

                        if (!empty($datosExtendidos)) {
                            $generoKey = strtolower(trim($generoBD));
                            if (isset($datosExtendidos[$generoKey][$claveColorDataExtendidos])) {
                                $datosColor = $datosExtendidos[$generoKey][$claveColorDataExtendidos];
                                $observacionesColor = $datosColor['observaciones'] ?? null;
                                $ubicacionesColor = $datosColor['ubicaciones'] ?? null;
                            } elseif (isset($datosExtendidos[$generoKey][$talla])) {
                                $datosTalla = $datosExtendidos[$generoKey][$talla];
                                $observacionesColor = $datosTalla['observaciones'] ?? null;
                                $ubicacionesColor = $datosTalla['ubicaciones'] ?? null;
                            }
                        }

                        $tallaProceso->coloresAsignados()->create([
                            'color_nombre' => $colorNombre,
                            'tela_nombre' => $telaGuardar,
                            'cantidad' => (int) ($colorItem['cantidad'] ?? 1),
                            'observaciones' => $observacionesColor,
                            'ubicaciones' => !empty($ubicacionesColor) ? json_encode($ubicacionesColor) : null,
                        ]);
                    }
                }
            }
        }
    }

    private function encontrarClaveAsignacion(string $generoNormalizado, string $tallaNormalizada, array $asignacionesColores): ?string
    {
        $posiblesClaves = [
            "{$generoNormalizado}-Letra-{$tallaNormalizada}",
            "{$generoNormalizado}-Número-{$tallaNormalizada}",
            "{$generoNormalizado}-{$tallaNormalizada}",
        ];

        foreach ($posiblesClaves as $clave) {
            if (isset($asignacionesColores[$clave])) {
                return $clave;
            }
        }

        foreach ($asignacionesColores as $clave => $asignacion) {
            if (
                is_array($asignacion) &&
                isset($asignacion['genero'], $asignacion['talla']) &&
                strtolower(trim((string) $asignacion['genero'])) === $generoNormalizado &&
                trim((string) $asignacion['talla']) === $tallaNormalizada
            ) {
                return $clave;
            }
        }

        return null;
    }
}
