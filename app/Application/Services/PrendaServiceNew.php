<?php

namespace App\Application\Services;

use App\Application\DTOs\CrearPrendaDTO;
use App\Models\Prenda;
use Illuminate\Pagination\LengthAwarePaginator;

class PrendaServiceNew
{
    public function __construct(
        private TipoPrendaDetectorService $tipoPrendaDetector,
        private ColorGeneroMangaBrocheService $colorGeneroService,
        private PrendaVariantesService $variantesService,
        private PrendaTelasService $telasService,
        private ImagenProcesadorService $imagenService,
    ) {}

    /**
     * Crear una nueva prenda
     */
    public function crear(CrearPrendaDTO $dto): Prenda
    {
        \Log::info(' Iniciando creación de prenda', [
            'nombre' => $dto->nombre_producto,
            'tipo' => $dto->tipo_prenda,
        ]);

        try {
            // Detectar tipo de prenda
            $tipoPrenda = $this->tipoPrendaDetector->detectar($dto->tipo_prenda);
            $tipoPrendaModel = $this->tipoPrendaDetector->obtenerOCrear($tipoPrenda);

            // Obtener o crear género
            $genero = $this->colorGeneroService->obtenerOCrearGenero($dto->genero);

            // Crear prenda
            $prenda = Prenda::create([
                'nombre_producto' => $dto->nombre_producto,
                'descripcion' => $dto->descripcion,
                'tipo_prenda_id' => $tipoPrendaModel->id,
                'genero_id' => $genero?->id,
                'estado' => 'activo',
            ]);

            \Log::info(' Prenda creada', [
                'prenda_id' => $prenda->id,
                'nombre' => $prenda->nombre_producto,
            ]);

            // Registrar tallas
            if (!empty($dto->tallas)) {
                $this->variantesService->registrarTallas($prenda->id, $dto->tallas);
            }

            // Crear variantes
            if (!empty($dto->variantes)) {
                foreach ($dto->variantes as $varianteDTO) {
                    $variante = $this->variantesService->crear($prenda->id, $varianteDTO);

                    // Registrar telas para la variante
                    if (!empty($dto->telas)) {
                        $this->telasService->registrarTelas($variante->id, $dto->telas);
                    }
                }
            }

            return $prenda->load([
                'tipoPrenda',
                'genero',
                'variantes.tipoManga',
                'variantes.tipoBroche',
                'variantes.telas.color',
                'variantes.telas.tela',
                'tallas',
            ]);

        } catch (\Exception $e) {
            \Log::error(' Error creando prenda', [
                'error' => $e->getMessage(),
                'nombre' => $dto->nombre_producto,
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar prenda
     */
    public function actualizar(int $id, CrearPrendaDTO $dto): Prenda
    {
        \Log::info(' Actualizando prenda', ['prenda_id' => $id]);

        try {
            $prenda = Prenda::findOrFail($id);

            // Detectar tipo de prenda
            $tipoPrenda = $this->tipoPrendaDetector->detectar($dto->tipo_prenda);
            $tipoPrendaModel = $this->tipoPrendaDetector->obtenerOCrear($tipoPrenda);

            // Obtener o crear género
            $genero = $this->colorGeneroService->obtenerOCrearGenero($dto->genero);

            // Actualizar prenda
            $prenda->update([
                'nombre_producto' => $dto->nombre_producto,
                'descripcion' => $dto->descripcion,
                'tipo_prenda_id' => $tipoPrendaModel->id,
                'genero_id' => $genero?->id,
            ]);

            // Actualizar tallas
            if (!empty($dto->tallas)) {
                $this->variantesService->registrarTallas($prenda->id, $dto->tallas);
            }

            \Log::info(' Prenda actualizada', ['prenda_id' => $id]);

            return $prenda->load([
                'tipoPrenda',
                'genero',
                'variantes.tipoManga',
                'variantes.tipoBroche',
                'variantes.telas.color',
                'variantes.telas.tela',
                'tallas',
            ]);

        } catch (\Exception $e) {
            \Log::error(' Error actualizando prenda', [
                'prenda_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Obtener prenda por ID
     */
    public function obtenerPorId(int $id): Prenda
    {
        return Prenda::with([
            'tipoPrenda',
            'genero',
            'variantes.tipoManga',
            'variantes.tipoBroche',
            'variantes.telas.color',
            'variantes.telas.tela',
            'tallas',
            'fotos',
        ])->findOrFail($id);
    }

    /**
     * Listar prendas
     */
    public function listar(int $pagina = 1, int $porPagina = 15): LengthAwarePaginator
    {
        return Prenda::with([
            'tipoPrenda',
            'genero',
            'variantes',
        ])
            ->where('estado', 'activo')
            ->orderBy('created_at', 'desc')
            ->paginate($porPagina, ['*'], 'page', $pagina);
    }

    /**
     * Buscar prendas
     */
    public function buscar(string $termino, int $pagina = 1, int $porPagina = 15): LengthAwarePaginator
    {
        return Prenda::with([
            'tipoPrenda',
            'genero',
        ])
            ->where('estado', 'activo')
            ->where(function ($query) use ($termino) {
                $query->where('nombre_producto', 'like', "%{$termino}%")
                    ->orWhere('descripcion', 'like', "%{$termino}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($porPagina, ['*'], 'page', $pagina);
    }

    /**
     * Eliminar prenda
     */
    public function eliminar(int $id): bool
    {
        try {
            $prenda = Prenda::findOrFail($id);

            // Eliminar imágenes
            $this->imagenService->eliminarImagenesPrenda($id);

            // Eliminar prenda
            $prenda->delete();

            \Log::info(' Prenda eliminada', ['prenda_id' => $id]);

            return true;
        } catch (\Exception $e) {
            \Log::error(' Error eliminando prenda', [
                'prenda_id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cambiar estado de prenda
     */
    public function cambiarEstado(int $id, string $estado): Prenda
    {
        $prenda = Prenda::findOrFail($id);
        $prenda->update(['estado' => $estado]);

        \Log::info(' Estado de prenda actualizado', [
            'prenda_id' => $id,
            'estado' => $estado,
        ]);

        return $prenda;
    }

    /**
     * Obtener estadísticas de prendas
     */
    public function obtenerEstadisticas(): array
    {
        return [
            'total' => Prenda::count(),
            'activas' => Prenda::where('estado', 'activo')->count(),
            'inactivas' => Prenda::where('estado', 'inactivo')->count(),
            'por_tipo' => Prenda::with('tipoPrenda')
                ->groupBy('tipo_prenda_id')
                ->selectRaw('tipo_prenda_id, count(*) as cantidad')
                ->get()
                ->toArray(),
        ];
    }
}
