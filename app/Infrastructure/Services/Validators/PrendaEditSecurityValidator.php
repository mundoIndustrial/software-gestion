<?php

namespace App\Infrastructure\Services\Validators;

use App\DTOs\Edit\EditPrendaPedidoDTO;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use Illuminate\Validation\ValidationException;

/**
 * PrendaEditSecurityValidator - Validación de restricciones de negocio para edición
 * 
 * RESPONSABILIDAD:
 * Validar que las ediciones cumplan con reglas de negocio:
 * ✓ Las tallas NO se pueden reducir por debajo de cantidades usadas en procesos
 * ✓ No se puede eliminar una talla que tenga procesos asociados
 * ✓ Las relaciones se actualizan mediante MERGE (no se borran)
 * ✓ Los procesos NO se pueden editar desde este endpoint
 * 
 * REGLAS:
 * 1. Cantidad total NO puede ser menor que la suma de procesos
 * 2. Una talla NO puede reducir cantidad por debajo de procesos
 * 3. NO se puede "borrar" una talla que tenga procesos (solo conservarla)
 */
class PrendaEditSecurityValidator
{
    /**
     * Validar edición de prenda
     * 
     * @param PrendaPedido $prendaPedido
     * @param EditPrendaPedidoDTO $dto
     * @return void
     * @throws ValidationException
     */
    public static function validateEdit(PrendaPedido $prendaPedido, EditPrendaPedidoDTO $dto): void
    {
        // Validar cambios de cantidad
        if ($dto->hasField('cantidad')) {
            self::validateCantidadChange($prendaPedido, $dto->cantidad);
        }

        // Validar cambios de tallas
        if ($dto->hasField('tallas')) {
            self::validateTallasChange($prendaPedido, $dto->tallas);
        }

        // Validar que no intenten modificar procesos
        if ($dto->hasField('procesos')) {
            throw ValidationException::withMessages([
                'procesos' => 'Los procesos no pueden editarse desde este endpoint. Use el endpoint de procesos.',
            ]);
        }
    }

    /**
     * Validar cambio de cantidad total
     * 
     * @param PrendaPedido $prenda
     * @param int $nuevaCantidad
     * @return void
     * @throws ValidationException
     */
    public static function validateCantidadChange(PrendaPedido $prenda, int $nuevaCantidad): void
    {
        // Obtener cantidad total usada en procesos
        $cantidadEnProcesos = ProcesoPrenda::where('prenda_pedido_id', $prenda->id)
            ->sum('cantidad');

        if ($nuevaCantidad < $cantidadEnProcesos) {
            throw ValidationException::withMessages([
                'cantidad' => "No se puede reducir la cantidad por debajo de {$cantidadEnProcesos} " .
                    "(cantidad ya usada en procesos). " .
                    "Cantidad actual: {$prenda->cantidad}, Nueva: {$nuevaCantidad}",
            ]);
        }
    }

    /**
     * Validar cambios en tallas mediante MERGE
     * 
     * @param PrendaPedido $prenda
     * @param array $tallasPayload
     * @return void
     * @throws ValidationException
     */
    public static function validateTallasChange(PrendaPedido $prenda, array $tallasPayload): void
    {
        $tallasActuales = $prenda->tallas()->get();
        $errores = [];

        foreach ($tallasPayload as $tallaPayload) {
            // Si viene CON id: es UPDATE
            if (isset($tallaPayload['id'])) {
                $tallaActual = $tallasActuales->find($tallaPayload['id']);

                if (!$tallaActual) {
                    $errores[] = "Talla con ID {$tallaPayload['id']} no existe en esta prenda";
                    continue;
                }

                // Validar que la cantidad nueva no es menor que cantidad en procesos
                if (isset($tallaPayload['cantidad'])) {
                    $cantidadEnProcesos = self::getCantidadTallaEnProcesos(
                        $prenda->id,
                        $tallaActual->talla,
                        $tallaActual->genero
                    );

                    if ($tallaPayload['cantidad'] < $cantidadEnProcesos) {
                        $errores[] = "Talla {$tallaActual->talla} ({$tallaActual->genero}): " .
                            "no se puede reducir cantidad por debajo de {$cantidadEnProcesos} " .
                            "(cantidad en procesos)";
                    }
                }
            } 
            // Si NO viene con id: es CREATE (se permite siempre)
            else {
                if (!isset($tallaPayload['talla'], $tallaPayload['cantidad'])) {
                    $errores[] = 'Talla nueva debe incluir "talla" y "cantidad"';
                }
            }
        }

        if (!empty($errores)) {
            throw ValidationException::withMessages([
                'tallas' => $errores,
            ]);
        }
    }

    /**
     * Obtener cantidad de talla usada en procesos
     * 
     * @param int $prendaPedidoId
     * @param string $talla
     * @param string $genero
     * @return int
     */
    private static function getCantidadTallaEnProcesos(int $prendaPedidoId, string $talla, string $genero): int
    {
        return ProcesoPrenda::where('prenda_pedido_id', $prendaPedidoId)
            ->where('talla', $talla)
            ->where('genero', $genero)
            ->sum('cantidad');
    }

    /**
     * Validar que una edición no cierra el ciclo de seguridad
     * (para uso en transacciones complejas)
     * 
     * @param PrendaPedido $prenda
     * @param array $validatedData
     * @return array - Errores encontrados (array vacío = válido)
     */
    public static function validateSecurityConstraints(PrendaPedido $prenda, array $validatedData): array
    {
        $errores = [];

        // No se puede editar cantidad si ya hay procesos asignados
        if (isset($validatedData['cantidad'])) {
            $cantidadEnProcesos = ProcesoPrenda::where('prenda_pedido_id', $prenda->id)
                ->sum('cantidad');

            if ($cantidadEnProcesos > 0 && $validatedData['cantidad'] < $cantidadEnProcesos) {
                $errores['cantidad'] = "Cantidad no puede ser menor que cantidad en procesos ({$cantidadEnProcesos})";
            }
        }

        return $errores;
    }
}
