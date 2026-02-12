<?php

namespace App\Application\Services\Cotizacion;

use App\Models\PrendaFotoCot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class BorrarImagenPrendaService
{
    public function ejecutar(int $fotoId): void
    {
        $foto = PrendaFotoCot::find($fotoId);

        if (!$foto) {
            throw new \DomainException('Imagen no encontrada');
        }

        if ($foto->ruta_original && Storage::disk('public')->exists($foto->ruta_original)) {
            Storage::disk('public')->delete($foto->ruta_original);
        }

        $foto->forceDelete();

        Log::info('Imagen de prenda borrada exitosamente', ['foto_id' => $fotoId]);
    }
}
