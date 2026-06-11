<?php

namespace App\Application\Operario\Services;

use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;
use App\Models\PrendaBodega;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final class ReciboTallerDistributionService
{
    public function distribuir(
        PedidoProduccion $pedido,
        object $recibo,
        int $pedidoId,
        int $prendaId,
        string $tipoReciboReal,
        int $consecutivoOriginal,
        array $requestData,
        bool $esBodega = false,
        ?int $prendaBodegaId = null
    ): array {
        $subtipoTaller = (string) ($requestData['subtipo_taller'] ?? 'unico');
        $esEdicion = (bool) ($requestData['es_edicion'] ?? false);

        if ($subtipoTaller === 'unico') {
            return $this->procesarUnico(
                $pedido,
                $prendaId,
                $tipoReciboReal,
                $consecutivoOriginal,
                (string) ($requestData['encargado'] ?? ''),
                $esBodega,
                $prendaBodegaId
            );
        }

        $asignaciones = $this->normalizarAsignacionesTalleres($requestData);

        if (empty($asignaciones)) {
            throw new DomainException('No hay asignaciones de talleres');
        }

        return $esEdicion
            ? $this->procesarDistribucionMultiple($pedido, $recibo, $pedidoId, $prendaId, $tipoReciboReal, $consecutivoOriginal, $asignaciones, $esBodega, $prendaBodegaId)
            : $this->procesarDistribucionMultiple($pedido, $recibo, $pedidoId, $prendaId, $tipoReciboReal, $consecutivoOriginal, $asignaciones, $esBodega, $prendaBodegaId);
    }

    private function normalizarAsignacionesTalleres(array $requestData): array
    {
        if (!empty($requestData['asignaciones']) && is_array($requestData['asignaciones'])) {
            return array_values(array_filter(array_map(function ($asignacion) {
                if (!is_array($asignacion)) {
                    return null;
                }

                $encargado = trim((string) ($asignacion['encargado'] ?? ''));
                $tallas = $this->normalizarTallasAsignacion((array) ($asignacion['tallas'] ?? []));

                if ($encargado === '' || empty($tallas)) {
                    return null;
                }

                return [
                    'encargado' => $encargado,
                    'tallas' => $tallas,
                    'is_nueva_parte' => (bool) ($asignacion['is_nueva_parte'] ?? false),
                ];
            }, $requestData['asignaciones'])));
        }

        $asignacionesPorTaller = (array) ($requestData['asignaciones_por_taller'] ?? []);
        $talleresSeleccionados = array_values((array) ($requestData['talleres_seleccionados'] ?? []));
        $tallasDistribucion = (array) ($requestData['tallas_distribucion'] ?? []);
        $esEdicion = (bool) ($requestData['es_edicion'] ?? false);

        if (empty($asignacionesPorTaller)) {
            return [];
        }

        $asignacionesNormalizadas = [];

        foreach ($asignacionesPorTaller as $tallerIdStr => $asignacionesTallas) {
            $taller = $this->resolverTallerDesdeSeleccion($tallerIdStr, $talleresSeleccionados);
            $encargado = trim((string) ($taller['name'] ?? $taller['nombre'] ?? ''));

            if ($encargado === '') {
                continue;
            }

            $tallasNuevas = [];
            $tallasExistentes = [];

            foreach ((array) $asignacionesTallas as $tallaRaw => $datosAsignacion) {
                $cantidad = 0;
                $esNueva = false;
                $colorNombre = null;

                if (is_array($datosAsignacion) || is_object($datosAsignacion)) {
                    $cantidad = (int) ($datosAsignacion['cantidad'] ?? $datosAsignacion->cantidad ?? 0);
                    $esNueva = (bool) ($datosAsignacion['es_nueva_parte'] ?? $datosAsignacion['es_nueva'] ?? false);
                    $colorNombre = $datosAsignacion['color_nombre'] ?? $datosAsignacion->color_nombre ?? null;
                } else {
                    $cantidad = (int) $datosAsignacion;
                }

                if ($cantidad <= 0) {
                    continue;
                }

                $tallaInfo = $this->resolverTallaDistribucion((string) $tallaRaw, $tallasDistribucion);
                $tallaObj = [
                    'talla' => $tallaInfo['base'] ?? (string) $tallaRaw,
                    'cantidad' => $cantidad,
                    'color_nombre' => $colorNombre ?: ($tallaInfo['color'] ?? null),
                    'genero' => $tallaInfo['genero'] ?? null,
                ];

                if ($esNueva) {
                    $tallasNuevas[] = $tallaObj;
                } else {
                    $tallasExistentes[] = $tallaObj;
                }
            }

            if ($esEdicion) {
                if (!empty($tallasNuevas)) {
                    $asignacionesNormalizadas[] = [
                        'encargado' => $encargado,
                        'tallas' => $tallasNuevas,
                        'is_nueva_parte' => true,
                    ];
                }

                continue;
            }

            $tallasParaGuardar = array_values(array_filter(array_merge($tallasNuevas, $tallasExistentes)));
            if (empty($tallasParaGuardar)) {
                continue;
            }

            $asignacionesNormalizadas[] = [
                'encargado' => $encargado,
                'tallas' => $tallasParaGuardar,
            ];
        }

        return $asignacionesNormalizadas;
    }

    private function normalizarTallasAsignacion(array $tallas): array
    {
        return array_values(array_filter(array_map(function ($talla) {
            if (!is_array($talla)) {
                return null;
            }

            $cantidad = (int) ($talla['cantidad'] ?? 0);
            $nombreTalla = trim((string) ($talla['talla'] ?? ''));

            if ($cantidad <= 0 || $nombreTalla === '') {
                return null;
            }

            return [
                'talla' => $nombreTalla,
                'cantidad' => $cantidad,
                'color_nombre' => isset($talla['color_nombre']) ? (string) $talla['color_nombre'] : null,
                'genero' => isset($talla['genero']) ? (string) $talla['genero'] : null,
            ];
        }, $tallas)));
    }

    private function resolverTallerDesdeSeleccion(string|int $tallerId, array $talleresSeleccionados): array
    {
        foreach ($talleresSeleccionados as $taller) {
            if (!is_array($taller) && !is_object($taller)) {
                continue;
            }

            $tallerArray = is_array($taller) ? $taller : (array) $taller;
            $idTaller = (string) ($tallerArray['id'] ?? '');
            if ($idTaller !== '' && $idTaller === (string) $tallerId) {
                return $tallerArray;
            }
        }

        return [];
    }

    private function resolverTallaDistribucion(string $tallaRaw, array $tallasDistribucion): array
    {
        $partes = explode('_', $tallaRaw);
        if (count($partes) < 3) {
            return ['base' => trim($tallaRaw)];
        }

        $generoNorm = array_pop($partes);
        $colorNorm = array_pop($partes);
        $base = trim(implode('_', $partes));

        foreach ($tallasDistribucion as $talla) {
            if (!is_array($talla) && !is_object($talla)) {
                continue;
            }

            $tallaArray = is_array($talla) ? $talla : (array) $talla;
            $baseTalla = trim((string) ($tallaArray['tallaOriginal'] ?? explode(' ', (string) ($tallaArray['talla'] ?? ''))[0] ?? ''));
            $colorTalla = $this->normalizarTextoClave((string) ($tallaArray['color'] ?? ''));
            $generoTalla = $this->normalizarTextoClave((string) ($tallaArray['genero'] ?? ''));

            if (
                $baseTalla === $base &&
                $colorTalla === $this->normalizarTextoClave($colorNorm) &&
                $generoTalla === $this->normalizarTextoClave($generoNorm)
            ) {
                return [
                    'base' => $base,
                    'color' => $tallaArray['color'] ?? null,
                    'genero' => $tallaArray['genero'] ?? null,
                ];
            }
        }

        return [
            'base' => $base,
            'color' => null,
            'genero' => null,
        ];
    }

    private function normalizarTextoClave(string $texto): string
    {
        $texto = trim(mb_strtolower($texto));
        return preg_replace('/\s+/', ' ', $texto) ?: '';
    }

    private function procesarUnico(
        PedidoProduccion $pedido,
        int $prendaId,
        string $tipoReciboReal,
        int $consecutivoOriginal,
        string $encargado,
        bool $esBodega,
        ?int $prendaBodegaId
    ): array {
        $encargado = trim($encargado);

        if ($encargado === '') {
            throw new DomainException('Debe seleccionar un taller');
        }

        $taller = $this->resolverOCrearTaller($encargado);
        $prendaColumn = $esBodega ? 'prenda_bodega_id' : 'prenda_pedido_id';
        $prendaValor = $esBodega ? ($prendaBodegaId ?: $prendaId) : $prendaId;

        $procesosCostura = ProcesoPrenda::query()
            ->where('numero_pedido', $pedido->numero_pedido)
            ->where($prendaColumn, $prendaValor)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
            ->where('numero_recibo', $consecutivoOriginal)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        if ($procesosCostura->isEmpty() && $esBodega && $prendaId > 0) {
            $procesosCostura = ProcesoPrenda::query()
                ->where('numero_pedido', $pedido->numero_pedido)
                ->where('prenda_pedido_id', $prendaId)
                ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
                ->where('numero_recibo', $consecutivoOriginal)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->get();
        }

        if ($procesosCostura->isEmpty()) {
            $procesosCostura = collect([$this->crearProcesoPadre($pedido, $consecutivoOriginal, $prendaId, $prendaBodegaId, $esBodega)]);
        }

        foreach ($procesosCostura as $procesoCostura) {
            $procesoCostura->update([
                'encargado' => $taller->name,
                'usuario_id' => $taller->id,
                'fecha_de_asignacion_encargado' => now(),
                'estado_proceso' => 'En Progreso',
            ]);
        }

        return [
            'success' => true,
            'message' => 'Recibo asignado a taller correctamente',
            'data' => [
                'procesos_actualizados' => $procesosCostura->count(),
                'proceso_ids' => $procesosCostura->pluck('id')->values()->all(),
                'taller' => $taller->name,
            ],
        ];
    }

    private function procesarDistribucionMultiple(
        PedidoProduccion $pedido,
        object $recibo,
        int $pedidoId,
        int $prendaId,
        string $tipoReciboReal,
        int $consecutivoOriginal,
        array $asignaciones,
        bool $esBodega = false,
        ?int $prendaBodegaId = null
    ): array {
        $prendaColumn = $esBodega ? 'prenda_bodega_id' : 'prenda_pedido_id';

        $procesoPadre = ProcesoPrenda::query()
            ->where('numero_pedido', $pedido->numero_pedido)
            ->where($prendaColumn, $prendaId)
            ->whereRaw('LOWER(TRIM(proceso)) = ?', ['costura'])
            ->where('numero_recibo', $consecutivoOriginal)
            ->where(function ($query) {
                $query->whereNull('numero_recibo_parcial')
                    ->orWhere('numero_recibo_parcial', 0);
            })
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$procesoPadre) {
            $procesoPadre = $this->crearProcesoPadre($pedido, $consecutivoOriginal, $prendaId, $prendaBodegaId, $esBodega);
        }

        $maxParcialExistente = DB::table('recibo_por_partes')
            ->where('pedido_produccion_id', (int) $pedidoId)
            ->where('prenda_pedido_id', $prendaId)
            ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [strtoupper(trim($tipoReciboReal))])
            ->where('consecutivo_original', $consecutivoOriginal)
            ->max('consecutivo_parcial');

        $siguienteConsecutivoParcial = $this->obtenerSiguienteConsecutivoParcial($consecutivoOriginal, $maxParcialExistente);
        $creados = [];

        foreach ($asignaciones as $asig) {
            $encargado = trim((string) ($asig['encargado'] ?? ''));
            $tallas = (array) ($asig['tallas'] ?? []);

            if ($encargado === '' || empty($tallas)) {
                continue;
            }

            $taller = $this->resolverOCrearTaller($encargado);

            $consecutivoParcialDb = $this->formatearConsecutivoParcial($siguienteConsecutivoParcial);
            $siguienteConsecutivoParcial = round($siguienteConsecutivoParcial + 0.1, 1);

            $procesoHijo = ProcesoPrenda::create([
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_pedido_id' => $esBodega ? null : $prendaId,
                'prenda_bodega_id' => $esBodega ? $prendaBodegaId : null,
                'numero_recibo' => null,
                'numero_recibo_parcial' => $consecutivoParcialDb,
                'proceso' => 'Costura',
                'fecha_inicio' => now(),
                'encargado' => $encargado,
                'fecha_de_asignacion_encargado' => now(),
                'estado_proceso' => 'En Progreso',
                'codigo_referencia' => 'COS-' . $consecutivoParcialDb . '-' . date('YmdHis'),
            ]);

            $reciboParteId = DB::table('recibo_por_partes')->insertGetId([
                'pedido_produccion_id' => (int) $pedidoId,
                'prenda_pedido_id' => $prendaId,
                'tipo_recibo' => $tipoReciboReal,
                'consecutivo_original' => $consecutivoOriginal,
                'consecutivo_parcial' => $consecutivoParcialDb,
                'estado' => 'En ejecución',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($tallas as $t) {
                $talla = trim((string) ($t['talla'] ?? ''));
                $cantidad = (int) ($t['cantidad'] ?? 0);
                $colorNombre = isset($t['color_nombre']) ? (string) $t['color_nombre'] : null;
                $genero = isset($t['genero']) ? (string) $t['genero'] : null;

                if ($talla === '' || $cantidad <= 0) {
                    continue;
                }

                DB::table('recibos_por_partes_tallas')->insert([
                    'recibo_por_partes_id' => $reciboParteId,
                    'talla' => $talla,
                    'genero' => $genero ? strtoupper(trim($genero)) : null,
                    'cantidad' => $cantidad,
                    'color_nombre' => $colorNombre,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $creados[] = [
                'proceso_id' => (int) $procesoHijo->id,
                'numero_recibo' => null,
                'numero_recibo_parcial' => $consecutivoParcialDb,
                'parcial_id' => (int) $reciboParteId,
                'encargado' => $encargado,
            ];
        }

        if (empty($creados)) {
            throw new DomainException('No se pudieron crear procesos para los talleres especificados');
        }

        return [
            'proceso_padre_id' => (int) $procesoPadre->id,
            'hijos' => $creados,
            'recibo_id' => (int) $recibo->id,
        ];
    }

    private function crearProcesoPadre(
        PedidoProduccion $pedido,
        int $consecutivoOriginal,
        int $prendaId,
        ?int $prendaBodegaId,
        bool $esBodega
    ): ProcesoPrenda {
        return ProcesoPrenda::create([
            'numero_pedido' => $pedido->numero_pedido,
            'prenda_pedido_id' => $esBodega ? null : $prendaId,
            'prenda_bodega_id' => $esBodega ? $prendaBodegaId : null,
            'numero_recibo' => $consecutivoOriginal,
            'numero_recibo_parcial' => null,
            'proceso' => 'Costura',
            'fecha_inicio' => now(),
            'encargado' => null,
            'estado_proceso' => 'Pendiente',
            'codigo_referencia' => 'COS-' . $consecutivoOriginal . '-' . date('YmdHis'),
        ]);
    }

    private function resolverOCrearTaller(string $encargado): User
    {
        $taller = User::where('name', $encargado)
            ->get()
            ->first(function ($user) {
                return $user->hasRole('taller');
            });

        if ($taller) {
            return $taller;
        }

        $taller = User::create([
            'name' => $encargado,
            'email' => strtolower(str_replace(' ', '.', $encargado)) . '@taller.local',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $tallerRole = \App\Models\Role::where('name', 'taller')->first();
        if ($tallerRole) {
            $taller->addRole((int) $tallerRole->id);
        }

        return $taller;
    }

    private function obtenerSiguienteConsecutivoParcial(int $consecutivoOriginal, $maxParcialExistente): float
    {
        if ($maxParcialExistente === null) {
            return round($consecutivoOriginal + 0.1, 1);
        }

        return round(((float) $maxParcialExistente) + 0.1, 1);
    }

    private function formatearConsecutivoParcial(float $valor): string
    {
        return number_format($valor, 1, '.', '');
    }
}
