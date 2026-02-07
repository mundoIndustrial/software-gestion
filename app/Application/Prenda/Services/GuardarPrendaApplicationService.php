<?php

namespace App\Application\Prenda\Services;

use App\Domain\Prenda\Entities\Prenda;
use App\Domain\Prenda\Repositories\PrendaRepositoryInterface;
use App\Domain\Prenda\DomainServices\{
    AplicarOrigenAutomaticoDomainService,
    ValidarPrendaDomainService,
    NormalizarDatosPrendaDomainService
};
use App\Domain\Prenda\ValueObjects\{
    PrendaNombre,
    Descripcion,
    Genero,
    TipoCotizacion,
    Telas,
    Procesos,
    Variaciones,
    Tela,
    Proceso,
    Variacion
};
use App\Models\TipoManga;
use Exception;

/**
 * ✅ REFACTORIZADO: Maneja tipos de manga automáticamente
 * 
 * Responsabilidades:
 * - Guardar o actualizar prenda
 * - Crear tipos de manga si flag tipo_manga_crear=true
 * - Aplicar reglas de dominio
 * - Validar completamente
 */
class GuardarPrendaApplicationService
{
    public function __construct(
        private PrendaRepositoryInterface $repository,
        private AplicarOrigenAutomaticoDomainService $aplicarOrigenServicio,
        private ValidarPrendaDomainService $validarServicio,
        private NormalizarDatosPrendaDomainService $normalizarServicio
    ) {}

    /**
     * Guarda o actualiza una prenda
     * 
     * ✅ REFACTORIZADO: Ahora maneja tipos de manga automáticamente
     */
    public function ejecutar(array $datos): array
    {
        try {
            // 1. PROCESAR TIPOS DE MANGA (si viene flag tipo_manga_crear)
            // ✅ NUEVO: Backend maneja creación de tipos de manga
            if ($this->debeCrearTipoManga($datos)) {
                $datos = $this->procesarTipoManga($datos);
            }

            // 2. VALIDACIÓN BÁSICA DE DATOS
            $erroresBasicos = $this->validarDatosBasicos($datos);
            if (!empty($erroresBasicos)) {
                return $this->normalizarServicio->normalizarErrores($erroresBasicos);
            }

            // 3. CREAR O ACTUALIZAR ENTIDAD DE DOMINIO
            if (isset($datos['id']) && $datos['id']) {
                // ACTUALIZAR
                $prenda = $this->repository->porId($datos['id']);
                if ($prenda === null) {
                    return $this->normalizarServicio->normalizarErrores([
                        "Prenda con ID {$datos['id']} no encontrada"
                    ]);
                }

                // Actualizar campos permitidos
                $this->actualizarPrenda($prenda, $datos);
            } else {
                // CREAR NUEVA
                $prenda = $this->crearPrendaNueva($datos);
            }

            // 4. APLICAR ORIGEN AUTOMÁTICO basado en tipo cotización (CORE RULE)
            $this->aplicarOrigenServicio->aplicar($prenda);

            // 5. VALIDAR COMPLETAMENTE
            $erroresValidacion = $this->validarServicio->validar($prenda);
            if (!empty($erroresValidacion)) {
                return $this->normalizarServicio->normalizarErrores($erroresValidacion);
            }

            // 6. PERSISTIR
            $this->repository->guardar($prenda);

            // 7. PUBLICAR EVENTOS DE DOMINIO (en producción se enviarían a bus de eventos)
            $eventos = $prenda->obtenerEventosDominio();
            $this->publicarEventosDominio($eventos);

            // 8. RETORNAR RESPUESTA NORMALIZADA
            $prenda->limpiarEventosDominio();
            return $this->normalizarServicio->normalizarParaFrontend($prenda);

        } catch (Exception $e) {
            \Log::error('[GuardarPrendaApplicationService] Error:', ['error' => $e->getMessage()]);
            return $this->normalizarServicio->normalizarErrores([
                "Error al guardar prenda: {$e->getMessage()}"
            ]);
        }
    }

    /**
     * ✅ NUEVO: Verifica si debe crear tipo de manga
     */
    private function debeCrearTipoManga(array $datos): bool
    {
        return ($datos['variantes']['tipo_manga_crear'] ?? false) === true &&
               !empty($datos['variantes']['tipo_manga'] ?? '');
    }

    /**
     * ✅ NUEVO: Crea tipo de manga y retorna datos actualizados con el ID
     */
    private function procesarTipoManga(array $datos): array
    {
        $nombreManga = trim($datos['variantes']['tipo_manga']);

        // Buscar o crear tipo de manga (case-insensitive)
        $tipoManga = TipoManga::whereRaw('LOWER(nombre) = ?', [strtolower($nombreManga)])
            ->first();

        if (!$tipoManga) {
            $tipoManga = TipoManga::create([
                'nombre' => ucfirst(strtolower($nombreManga)),
                'activo' => true
            ]);

            \Log::info('[GuardarPrendaApplicationService] Tipo de manga creado', [
                'id' => $tipoManga->id,
                'nombre' => $tipoManga->nombre
            ]);
        }

        // Asignar ID al tipo de manga
        $datos['variantes']['tipo_manga_id'] = $tipoManga->id;
        
        // Limpiar flag
        unset($datos['variantes']['tipo_manga_crear']);

        return $datos;
    }

    private function validarDatosBasicos(array $datos): array
    {
        $errores = [];

        if (empty($datos['nombre_prenda'] ?? '')) {
            $errores[] = "El nombre de la prenda es requerido";
        }

        if (empty($datos['tipo_cotizacion'] ?? '')) {
            $errores[] = "El tipo de cotización es requerido";
        }

        if (empty($datos['telas'] ?? [])) {
            $errores[] = "Debe seleccionar al menos una tela";
        }

        if (empty($datos['genero'] ?? '')) {
            $errores[] = "El género es requerido";
        }

        return $errores;
    }

    private function crearPrendaNueva(array $datos): Prenda
    {
        $nombre = PrendaNombre::desde($datos['nombre_prenda']);
        $genero = Genero::desde((int)$datos['genero']);
        $tipoCotizacion = TipoCotizacion::desde($datos['tipo_cotizacion']);
        $telas = $this->construirTelas($datos['telas']);
        $descripcion = Descripcion::desde($datos['descripcion'] ?? null);

        return Prenda::crearParaCotizacion(
            $nombre,
            $genero,
            $tipoCotizacion,
            $telas,
            $descripcion
        );
    }

    private function actualizarPrenda(Prenda $prenda, array $datos): void
    {
        // Actualizar procesos si vienen
        if (isset($datos['procesos']) && is_array($datos['procesos'])) {
            $procesos = $this->construirProcesos($datos['procesos']);
            $prenda->establecerProcesos($procesos);
        }

        // Actualizar variaciones si vienen
        if (isset($datos['variaciones']) && is_array($datos['variaciones'])) {
            $variaciones = $this->construirVariaciones($datos['variaciones']);
            $prenda->establecerVariaciones($variaciones);
        }
    }

    private function construirTelas(array $telasData): Telas
    {
        $telas = array_map(function (array $data) {
            return Tela::desde(
                (int)$data['id'],
                $data['nombre'],
                $data['codigo']
            );
        }, $telasData);

        return Telas::desde(...$telas);
    }

    private function construirProcesos(array $procesosData): Procesos
    {
        if (empty($procesosData)) {
            return Procesos::vacia();
        }

        $procesos = array_map(function (array $data) {
            return Proceso::desde(
                (int)$data['id'],
                $data['nombre']
            );
        }, $procesosData);

        return Procesos::desde(...$procesos);
    }

    private function construirVariaciones(array $variacionesData): Variaciones
    {
        if (empty($variacionesData)) {
            return Variaciones::vacia();
        }

        $variaciones = array_map(function (array $data) {
            return Variacion::desde(
                (int)$data['id'],
                $data['talla'],
                $data['color']
            );
        }, $variacionesData);

        return Variaciones::desde(...$variaciones);
    }

    /**
     * Publica eventos de dominio a bus de eventos (si está implementado)
     */
    private function publicarEventosDominio(array $eventos): void
    {
        foreach ($eventos as $evento) {
            // En producción: $this->eventBus->publicar($evento);
            // Por ahora solo logs
            \Log::info("Evento de dominio", $evento);
        }
    }
}
