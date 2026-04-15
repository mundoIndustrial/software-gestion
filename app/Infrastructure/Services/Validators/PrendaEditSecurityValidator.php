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
 * ✓ Si los consecutivos de COSTURA no tienen estado "DEVUELTO_ASESOR", NO se puede editar
 * 
 * LÓGICA DE BLOQUEO POR CONSECUTIVOS:
 * - Si NO hay consecutivos de COSTURA → Se permite edición
 * - Si hay consecutivos de COSTURA → AL MENOS UNO debe estar en estado "DEVUELTO_ASESOR"
 * - Si TODOS los consecutivos están en estado diferente a "DEVUELTO_ASESOR" → Bloquea edición
 * - Mensaje de bloqueo: "El pedido ya fue aprobado por ende no se puede editar. Comuníquese con el líder de producción."
 * 
 * REGLAS ADICIONALES:
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
        // 1. PRIMERA VALIDACIÓN: Verificar estado de consecutivos de COSTURA
        self::validateConsecutivosCostura($prendaPedido);

        // 2. Validar cambios de cantidad
        if ($dto->hasField('cantidad')) {
            self::validateCantidadChange($prendaPedido, $dto->cantidad);
        }

        // 3. Validar cambios de tallas
        if ($dto->hasField('tallas')) {
            self::validateTallasChange($prendaPedido, $dto->tallas);
        }

        // 4. Validar que no intenten modificar procesos
        if ($dto->hasField('procesos')) {
            throw ValidationException::withMessages([
                'procesos' => 'Los procesos no pueden editarse desde este endpoint. Use el endpoint de procesos.',
            ]);
        }
    }

    /**
     * Validar que los consecutivos de COSTURA tengan estado "DEVUELTO_ASESOR"
     * 
     * LÓGICA:
     * - Si NO hay consecutivos de COSTURA → Permitir edición
     * - Si hay consecutivos de COSTURA → AL MENOS UNO debe tener estado "DEVUELTO_ASESOR"
     * - Si NINGUNO tiene estado "DEVUELTO_ASESOR" → Bloquear edición
     * 
     * @param PrendaPedido $prendaPedido
     * @return void
     * @throws ValidationException
     */
    private static function validateConsecutivosCostura(PrendaPedido $prendaPedido): void
    {
        // Obtener todos los consecutivos COSTURA para esta prenda
        $consecutivosCostura = \DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $prendaPedido->pedido_produccion_id)
            ->where('prenda_id', $prendaPedido->id)
            ->where('tipo_recibo', 'COSTURA')
            ->get();

        // Si no hay consecutivos de costura, permitir edición
        if ($consecutivosCostura->isEmpty()) {
            return;
        }

        // Verificar si AL MENOS UNO tiene estado "DEVUELTO_ASESOR"
        $tieneDevueltoAsesor = $consecutivosCostura->contains(function ($consecutivo) {
            $estado = $consecutivo->estado ?? '';
            return strtoupper(trim($estado)) === 'DEVUELTO_ASESOR';
        });

        // Si NINGUNO tiene estado "DEVUELTO_ASESOR", bloquear
        if (!$tieneDevueltoAsesor) {
            throw ValidationException::withMessages([
                'edicion_bloqueada' => 'El pedido ya fue aprobado por ende no se puede editar. Comuníquese con el líder de producción.',
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
        // Validar estado de consecutivos de COSTURA primero
        self::validateConsecutivosCostura($prenda);

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
