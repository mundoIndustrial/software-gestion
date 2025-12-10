<?php

namespace App\Application\Cotizacion\Handlers\Commands;

use App\Application\Cotizacion\Commands\SubirImagenCotizacionCommand;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Domain\Cotizacion\ValueObjects\CotizacionId;
use App\Domain\Cotizacion\ValueObjects\RutaImagen;
use App\Domain\Cotizacion\Specifications\EsPropietarioSpecification;
use App\Infrastructure\Storage\ImagenAlmacenador;
use App\Domain\Shared\ValueObjects\UserId;
use App\Models\PrendaCot;
use App\Models\LogoCotizacion;
use App\Models\PrendaTelaFoto;
use App\Models\LogoFoto;
use Illuminate\Support\Facades\Log;

/**
 * SubirImagenCotizacionHandler - Handler para subir imagen
 *
 * Orquesta:
 * 1. Validación de propiedad
 * 2. Guardado de imagen en storage
 * 3. Persistencia en BD (modelos)
 * 4. Validaciones (máximo 5 logos)
 */
final class SubirImagenCotizacionHandler
{
    public function __construct(
        private readonly CotizacionRepositoryInterface $repository,
        private readonly ImagenAlmacenador $almacenador
    ) {
    }

    /**
     * Ejecutar comando
     *
     * @return RutaImagen La ruta de la imagen guardada
     * @throws \DomainException Si hay error de validación
     */
    public function handle(SubirImagenCotizacionCommand $comando): RutaImagen
    {
        Log::info('SubirImagenCotizacionHandler: Iniciando subida', [
            'cotizacion_id' => $comando->cotizacionId,
            'prenda_id' => $comando->prendaId,
            'tipo' => $comando->tipo,
            'archivo' => $comando->archivo->getClientOriginalName(),
            'usuario_id' => $comando->usuarioId,
        ]);

        try {
            // Obtener cotización
            $cotizacionId = CotizacionId::crear($comando->cotizacionId);
            $cotizacion = $this->repository->findById($cotizacionId);

            if (!$cotizacion) {
                throw new \DomainException('Cotización no encontrada');
            }

            // Verificar propiedad
            $usuarioId = UserId::crear($comando->usuarioId);
            $esPropietario = new EsPropietarioSpecification($usuarioId);
            $esPropietario->throwIfNotSatisfied($cotizacion);

            // Guardar imagen en storage
            $ruta = $this->almacenador->guardar(
                $comando->archivo,
                $comando->cotizacionId,
                $comando->prendaId,
                $comando->tipo
            );

            $rutaImagen = RutaImagen::crear($ruta);

            Log::info('SubirImagenCotizacionHandler: Imagen guardada en storage', [
                'cotizacion_id' => $comando->cotizacionId,
                'ruta' => $ruta,
            ]);

            // Guardar en BD según tipo
            $this->guardarEnBD(
                $comando->cotizacionId,
                $comando->prendaId,
                $comando->tipo,
                $ruta
            );

            Log::info('SubirImagenCotizacionHandler: Imagen subida exitosamente', [
                'cotizacion_id' => $comando->cotizacionId,
                'prenda_id' => $comando->prendaId,
                'tipo' => $comando->tipo,
                'ruta' => $ruta,
            ]);

            return $rutaImagen;
        } catch (\DomainException $e) {
            Log::warning('SubirImagenCotizacionHandler: Error de dominio', [
                'error' => $e->getMessage(),
                'cotizacion_id' => $comando->cotizacionId,
            ]);

            throw $e;
        } catch (\Exception $e) {
            Log::error('SubirImagenCotizacionHandler: Error al subir imagen', [
                'error' => $e->getMessage(),
                'cotizacion_id' => $comando->cotizacionId,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Guardar imagen en BD según tipo
     *
     * @throws \DomainException Si hay validación fallida
     */
    private function guardarEnBD(
        int $cotizacionId,
        int $prendaId,
        string $tipo,
        string $ruta
    ): void {
        switch ($tipo) {
            case 'prenda':
                $this->guardarFotoPrend($prendaId, $ruta);
                break;

            case 'tela':
                $this->guardarFotoTela($prendaId, $ruta);
                break;

            case 'logo':
                $this->guardarFotoLogo($cotizacionId, $ruta);
                break;

            default:
                throw new \DomainException("Tipo de imagen no soportado: {$tipo}");
        }
    }

    /**
     * Guardar foto de prenda
     */
    private function guardarFotoPrend(int $prendaId, string $ruta): void
    {
        $prenda = PrendaCot::findOrFail($prendaId);

        // Obtener orden (siguiente)
        $orden = $prenda->fotos()->max('orden') ?? -1;
        $orden++;

        $prenda->fotos()->create([
            'ruta_original' => $ruta,
            'ruta_webp' => $ruta,
            'ruta_miniatura' => null,
            'tipo' => 'prenda',
            'orden' => $orden,
        ]);

        Log::info('SubirImagenCotizacionHandler: Foto de prenda guardada', [
            'prenda_id' => $prendaId,
            'orden' => $orden,
        ]);
    }

    /**
     * Guardar foto de tela
     */
    private function guardarFotoTela(int $prendaId, string $ruta): void
    {
        $prenda = PrendaCot::findOrFail($prendaId);

        // Obtener orden (siguiente)
        $orden = $prenda->telaFotos()->max('orden') ?? -1;
        $orden++;

        PrendaTelaFoto::create([
            'prenda_cot_id' => $prendaId,
            'ruta_original' => $ruta,
            'ruta_webp' => $ruta,
            'ruta_miniatura' => null,
            'orden' => $orden,
        ]);

        Log::info('SubirImagenCotizacionHandler: Foto de tela guardada', [
            'prenda_id' => $prendaId,
            'orden' => $orden,
        ]);
    }

    /**
     * Guardar foto de logo
     *
     * @throws \DomainException Si ya hay 5 fotos
     */
    private function guardarFotoLogo(int $cotizacionId, string $ruta): void
    {
        $logo = LogoCotizacion::where('cotizacion_id', $cotizacionId)->firstOrFail();

        // Validar máximo 5 fotos
        $cantidad = $logo->fotos()->count();
        if ($cantidad >= 5) {
            throw new \DomainException('El logo no puede tener más de 5 fotos');
        }

        // Obtener orden (siguiente)
        $orden = $logo->fotos()->max('orden') ?? -1;
        $orden++;

        LogoFoto::create([
            'logo_cotizacion_id' => $logo->id,
            'ruta_original' => $ruta,
            'ruta_webp' => $ruta,
            'ruta_miniatura' => null,
            'orden' => $orden,
        ]);

        Log::info('SubirImagenCotizacionHandler: Foto de logo guardada', [
            'logo_id' => $logo->id,
            'orden' => $orden,
        ]);
    }
}
