<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\ColorPrenda;
use App\Models\PrendaPedido;
use App\Models\PrendaPedidoTalla;
use App\Models\TelaPrenda;
use Illuminate\Support\Facades\Log;

class PedidoTallaBuilder
{
    public function crear(
        PrendaPedido $prenda,
        array $cantidadTalla,
        array $asignacionesColores = [],
        string $flujo = 'simple'
    ): void {
        $esWizard = $flujo === 'wizard';

        foreach ($cantidadTalla as $generoOEspecial => $contenido) {
            if (!is_array($contenido) || empty($contenido)) {
                continue;
            }

            if (strtoupper($generoOEspecial) === 'SOBREMEDIDA') {
                $this->crearSobremedidas($prenda, $contenido);
                continue;
            }

            $this->crearTallasNormales(
                $prenda,
                (string) $generoOEspecial,
                $contenido,
                $asignacionesColores,
                $esWizard
            );
        }

        Log::info('[PedidoTallaBuilder] Tallas creadas', [
            'prenda_id' => $prenda->id,
            'cantidad_generos' => count($cantidadTalla),
            'asignaciones_procesadas' => count($asignacionesColores),
            'flujo' => $flujo,
        ]);
    }

    private function crearSobremedidas(PrendaPedido $prenda, array $contenido): void
    {
        foreach ($contenido as $generoOTalla => $cantidadOGenero) {
            $genero = strtoupper(trim((string) $generoOTalla));
            $cantidad = 0;

            if (is_array($cantidadOGenero)) {
                foreach ($cantidadOGenero as $talla => $cantidadTalla) {
                    $cantidadTalla = (int) $cantidadTalla;
                    if ($cantidadTalla <= 0) {
                        continue;
                    }

                    PrendaPedidoTalla::create([
                        'prenda_pedido_id' => $prenda->id,
                        'genero' => $this->normalizarGeneroSobremedida($genero),
                        'talla' => $this->normalizarTallaSobremedida($talla),
                        'cantidad' => $cantidadTalla,
                        'es_sobremedida' => 1,
                    ]);
                }

                continue;
            }

            $cantidad = (int) $cantidadOGenero;
            if ($cantidad <= 0) {
                continue;
            }

            PrendaPedidoTalla::create([
                'prenda_pedido_id' => $prenda->id,
                'genero' => $this->normalizarGeneroSobremedida($genero),
                'talla' => null,
                'cantidad' => $cantidad,
                'es_sobremedida' => 1,
            ]);
        }

        Log::info('[PedidoTallaBuilder] Sobremedida creada', [
            'prenda_id' => $prenda->id,
            'generos_sobremedida' => count($contenido),
        ]);
    }

    private function normalizarGeneroSobremedida(string $genero): string
    {
        $genero = strtoupper(trim($genero));

        return in_array($genero, ['DAMA', 'CABALLERO', 'UNISEX'], true)
            ? $genero
            : 'UNISEX';
    }

    private function normalizarTallaSobremedida(string $talla): ?string
    {
        $talla = strtoupper(trim($talla));

        if ($talla === '' || $talla === 'SOBREMEDIDA') {
            return null;
        }

        return $talla;
    }

    private function crearTallasNormales(
        PrendaPedido $prenda,
        string $genero,
        array $contenido,
        array $asignacionesColores,
        bool $esWizard
    ): void {
        foreach ($contenido as $talla => $cantidad) {
            if ((int) $cantidad <= 0) {
                continue;
            }

            $claveEncontrada = $this->encontrarClaveAsignacion($genero, (string) $talla, $asignacionesColores);
            $asignacion = $claveEncontrada ? ($asignacionesColores[$claveEncontrada] ?? null) : null;
            $telaGuardar = is_array($asignacion) ? ($asignacion['tela'] ?? null) : null;
            $cantidadGuardar = ($esWizard && $claveEncontrada) ? null : (int) $cantidad;

            $prendaPedidoTalla = PrendaPedidoTalla::create([
                'prenda_pedido_id' => $prenda->id,
                'genero' => strtoupper($genero),
                'talla' => (string) $talla,
                'cantidad' => $cantidadGuardar,
                'es_sobremedida' => 0,
            ]);

            if ($claveEncontrada && is_array($asignacion)) {
                $this->crearColoresAsignados($prendaPedidoTalla, $asignacion, $telaGuardar);
            }
        }
    }

    private function encontrarClaveAsignacion(string $genero, string $talla, array $asignacionesColores): ?string
    {
        $generoNormalizado = strtolower(trim($genero));
        $tallaNormalizada = trim($talla);

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
            if (!is_array($asignacion)) {
                continue;
            }

            if (
                isset($asignacion['genero'], $asignacion['talla']) &&
                strtolower(trim((string) $asignacion['genero'])) === $generoNormalizado &&
                trim((string) $asignacion['talla']) === $tallaNormalizada
            ) {
                return $clave;
            }
        }

        return null;
    }

    private function crearColoresAsignados(PrendaPedidoTalla $prendaPedidoTalla, array $asignacion, ?string $telaGuardar): void
    {
        $telaId = null;

        if ($telaGuardar) {
            $telaRecord = TelaPrenda::where('nombre', $telaGuardar)->first();
            $telaId = $telaRecord?->id;
        }

        if (!isset($asignacion['colores']) || !is_array($asignacion['colores'])) {
            return;
        }

        foreach ($asignacion['colores'] as $colorItem) {
            $colorNombre = $colorItem['nombre'] ?? null;
            if (!$colorNombre) {
                continue;
            }

            $colorRecord = ColorPrenda::where('nombre', $colorNombre)->first();
            $colorId = $colorRecord?->id;

            $prendaPedidoTalla->coloresAsignados()->create([
                'tela_id' => $telaId,
                'tela_nombre' => $telaGuardar,
                'color_id' => $colorId,
                'color_nombre' => $colorNombre,
                'cantidad' => (int) ($colorItem['cantidad'] ?? 1),
                'observaciones' => $colorItem['observaciones'] ?? null,
                'referencia' => $colorItem['referencia'] ?? null,
                'imagen_ruta' => $colorItem['imagen_ruta'] ?? null,
            ]);
        }
    }
}
