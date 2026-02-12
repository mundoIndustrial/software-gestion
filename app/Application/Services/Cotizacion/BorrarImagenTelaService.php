<?php

namespace App\Application\Services\Cotizacion;

use App\Models\PrendaTelaFotoCot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class BorrarImagenTelaService
{
    public function ejecutar(int $fotoId): void
    {
        $foto = PrendaTelaFotoCot::find($fotoId);

        if (!$foto) {
            throw new \DomainException('Imagen no encontrada');
        }

        if ($foto->ruta_original && Storage::disk('public')->exists($foto->ruta_original)) {
            Storage::disk('public')->delete($foto->ruta_original);
        }

        $foto->forceDelete();

        Log::info('Imagen de tela borrada exitosamente', ['foto_id' => $fotoId]);
    }
}
