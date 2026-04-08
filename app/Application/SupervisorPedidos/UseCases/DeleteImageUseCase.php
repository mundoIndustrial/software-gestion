<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\DeleteImageRequest;
use App\Application\SupervisorPedidos\DTOs\DeleteImageResponse;
use Illuminate\Support\Facades\Storage;

class DeleteImageUseCase
{
    public function execute(DeleteImageRequest $request): DeleteImageResponse
    {
        try {
            // Mapear tipo a modelo
            $modelClass = match($request->getType()) {
                'prenda' => \App\Models\PrendaFotoPedido::class,
                'logo' => \App\Models\PrendaFotoLogoPedido::class,
                'tela' => \App\Models\PrendaFotoTelaPedido::class,
                default => null
            };

            if (!$modelClass) {
                throw new \DomainException('Tipo de imagen no válido');
            }

            // Obtener la imagen
            $foto = $modelClass::findOrFail($request->getId());

            \Log::info(" Iniciando eliminación de imagen {$request->getType()}", [
                'id' => $request->getId(),
                'ruta_original' => $foto->ruta_original ?? 'N/A',
                'ruta_webp' => $foto->ruta_webp ?? 'N/A'
            ]);

            // Eliminar archivos físicos
            $archivosEliminados = [];

            if (isset($foto->ruta_original) && Storage::disk('public')->exists($foto->ruta_original)) {
                Storage::disk('public')->delete($foto->ruta_original);
                $archivosEliminados[] = $foto->ruta_original;
            }

            if (isset($foto->ruta_webp) && $foto->ruta_webp !== $foto->ruta_original && Storage::disk('public')->exists($foto->ruta_webp)) {
                Storage::disk('public')->delete($foto->ruta_webp);
                $archivosEliminados[] = $foto->ruta_webp;
            }

            // Eliminar registro de BD
            $foto->forceDelete();

            \Log::info("Imagen eliminada correctamente para tipo {$request->getType()}", [
                'id' => $request->getId(),
                'archivos_eliminados' => $archivosEliminados
            ]);

            return new DeleteImageResponse(
                success: true,
                message: 'Imagen eliminada correctamente',
                filesDeleted: $archivosEliminados
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new \DomainException('Imagen no encontrada');
        } catch (\Throwable $e) {
            throw new \DomainException('Error al eliminar la imagen: ' . $e->getMessage());
        }
    }
}
