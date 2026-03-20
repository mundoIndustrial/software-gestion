<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\UpdateOrderRequest;
use App\Application\SupervisorPedidos\DTOs\UpdateOrderResponse;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class UpdateOrderUseCase
{
    private Request $request;

    public function execute(UpdateOrderRequest $dtoRequest, Request $request): UpdateOrderResponse
    {
        $this->request = $request;

        try {
            $orden = PedidoProduccion::with('prendas')->findOrFail($dtoRequest->getOrderId());

            // Validar datos de entrada
            $validated = $this->validateData($dtoRequest);

            DB::beginTransaction();

            // Actualizar datos del pedido
            $this->updateOrderData($orden, $validated);

            // Actualizar cada prenda
            $this->updatePrendas($validated['prendas']);

            DB::commit();

            // Preparar broadcast
            $this->broadcastOrderUpdate($orden);

            Log::info("Pedido #{$orden->numero_pedido} actualizado por " . auth()->user()->name);

            return new UpdateOrderResponse(true, 'Pedido actualizado correctamente', $orden->fresh('prendas'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar pedido', [
                'error' => $e->getMessage(),
                'orden_id' => $dtoRequest->getOrderId()
            ]);

            return new UpdateOrderResponse(false, 'Error al actualizar el pedido: ' . $e->getMessage());
        }
    }

    private function validateData(UpdateOrderRequest $dtoRequest): array
    {
        return [
            'cliente' => $dtoRequest->getCliente(),
            'forma_de_pago' => $dtoRequest->getFormaDePago(),
            'novedades' => $dtoRequest->getNovedades(),
            'dia_de_entrega' => $dtoRequest->getDiaDeEntrega(),
            'fecha_estimada_de_entrega' => $dtoRequest->getFechaEstimadaDeEntrega(),
            'prendas' => $dtoRequest->getPrendas(),
        ];
    }

    private function updateOrderData(PedidoProduccion $orden, array $validated): void
    {
        $datosActualizar = [
            'cliente' => $validated['cliente'],
            'forma_de_pago' => $validated['forma_de_pago'] ?? $orden->forma_de_pago,
            'novedades' => $validated['novedades'] ?? $orden->novedades,
            'dia_de_entrega' => $validated['dia_de_entrega'] ?? $orden->dia_de_entrega,
        ];

        // Si se envió fecha_estimada_de_entrega desde el frontend
        if (!empty($validated['fecha_estimada_de_entrega'])) {
            $datosActualizar['fecha_estimada_de_entrega'] = $validated['fecha_estimada_de_entrega'];
            Log::info("Fecha estimada recibida del frontend para pedido {$orden->numero_pedido}: {$validated['fecha_estimada_de_entrega']}");
        }
        // Si se está actualizando dia_de_entrega y no se envió fecha_estimada, calcularla
        elseif (isset($validated['dia_de_entrega']) && $validated['dia_de_entrega'] !== null) {
            $orden->dia_de_entrega = $validated['dia_de_entrega'];
            $fechaEstimada = $orden->calcularFechaEstimada();
            if ($fechaEstimada) {
                $datosActualizar['fecha_estimada_de_entrega'] = $fechaEstimada->format('Y-m-d H:i:s');
                Log::info("Fecha estimada calculada para pedido {$orden->numero_pedido}: {$fechaEstimada->format('Y-m-d H:i:s')}");
            }
        }

        $orden->update($datosActualizar);
        Log::info("Pedido actualizado con datos:", $datosActualizar);
    }

    private function updatePrendas(array $prendas): void
    {
        foreach ($prendas as $index => $prendaData) {
            $prenda = PrendaPedido::findOrFail($prendaData['id']);

            // Reconstruir descripcion_variaciones
            $variacionesTexto = [];
            if (!empty($prendaData['obs_manga'])) {
                $variacionesTexto[] = "Manga: " . $prendaData['obs_manga'];
            }
            if (!empty($prendaData['obs_bolsillos'])) {
                $variacionesTexto[] = "Bolsillos: " . $prendaData['obs_bolsillos'];
            }
            if (!empty($prendaData['obs_broche'])) {
                $variacionesTexto[] = "Broche: " . $prendaData['obs_broche'];
            }
            $descripcionVariaciones = implode(' | ', $variacionesTexto);

            $prenda->update([
                'nombre_prenda' => $prendaData['nombre_prenda'] ?? $prenda->nombre_prenda,
                'descripcion' => $prendaData['descripcion'] ?? $prenda->descripcion,
                'descripcion_variaciones' => $descripcionVariaciones,
                'cantidad_talla' => $prendaData['cantidad_talla'] ?? $prenda->cantidad_talla,
                'color_id' => $prendaData['color_id'] ?? $prenda->color_id,
                'tela_id' => $prendaData['tela_id'] ?? $prenda->tela_id,
                'tipo_manga_id' => $prendaData['tipo_manga_id'] ?? $prenda->tipo_manga_id,
                'tipo_broche_id' => $prendaData['tipo_broche_id'] ?? $prenda->tipo_broche_id,
                'tiene_bolsillos' => $prendaData['tiene_bolsillos'] ?? false,
                'tiene_reflectivo' => $prendaData['tiene_reflectivo'] ?? false,
            ]);

            // Procesar imágenes
            $this->handlePrendaImages($prenda, $index);
        }
    }

    private function handlePrendaImages(PrendaPedido $prenda, int $index): void
    {
        // Imágenes de prenda
        if ($this->request->hasFile("prendas.{$index}.nuevas_fotos")) {
            foreach ($this->request->file("prendas.{$index}.nuevas_fotos") as $foto) {
                $pathWebp = $this->saveImageAsWebp($foto, $prenda->pedido->numero_pedido, 'prendas');
                $prenda->fotos()->create([
                    'ruta_original' => $pathWebp,
                    'ruta_webp' => $pathWebp
                ]);
            }
        }

        // Imágenes de logo
        if ($this->request->hasFile("prendas.{$index}.nuevas_fotos_logo")) {
            foreach ($this->request->file("prendas.{$index}.nuevas_fotos_logo") as $foto) {
                $pathWebp = $this->saveImageAsWebp($foto, $prenda->pedido->numero_pedido, 'logos');
                $prenda->fotosLogo()->create([
                    'ruta_original' => $pathWebp,
                    'ruta_webp' => $pathWebp
                ]);
            }
        }

        // Imágenes de tela
        if ($this->request->hasFile("prendas.{$index}.nuevas_fotos_tela")) {
            foreach ($this->request->file("prendas.{$index}.nuevas_fotos_tela") as $foto) {
                $pathWebp = $this->saveImageAsWebp($foto, $prenda->pedido->numero_pedido, 'telas');
                $prenda->fotosTela()->create([
                    'ruta_original' => $pathWebp,
                    'ruta_webp' => $pathWebp
                ]);
            }
        }
    }

    private function saveImageAsWebp($file, string $numeroPedido, string $tipo): string
    {
        // Esta es una versión simplificada. En producción, usarías InterventionImage
        // Por ahora, guardamos la imagen con su extensión original
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = "pedidos/{$numeroPedido}/{$tipo}";
        $file->storeAs($path, $filename, 'public');
        return "{$path}/{$filename}";
    }

    private function broadcastOrderUpdate(PedidoProduccion $orden): void
    {
        $changedFields = [];
        if (!empty($this->request->input('cliente'))) $changedFields[] = 'cliente';
        if (!empty($this->request->input('forma_de_pago'))) $changedFields[] = 'forma_de_pago';
        if (!empty($this->request->input('novedades'))) $changedFields[] = 'novedades';
        if (!empty($this->request->input('dia_de_entrega'))) $changedFields[] = 'dia_de_entrega';
        if (!empty($this->request->input('fecha_estimada_de_entrega'))) $changedFields[] = 'fecha_estimada_de_entrega';

        if (!empty($changedFields)) {
            Log::info("Broadcast OMITIDO para pedido {$orden->numero_pedido} con campos:", $changedFields);
        }
    }
}
