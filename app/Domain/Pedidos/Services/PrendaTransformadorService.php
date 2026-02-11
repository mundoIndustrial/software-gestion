<?php

namespace App\Domain\Pedidos\Services;

use App\Domain\Pedidos\ValueObjects\OrigenPrenda;
use App\Domain\Pedidos\ValueObjects\TelaPrenda;
use App\Application\Pedidos\DTOs\TallaPrendaDTO;
use App\Application\Pedidos\DTOs\VariacionPrendaDTO;
use App\Application\Pedidos\DTOs\ProcesoPrendaDTO;
use App\Domain\Pedidos\Services\TallaProcessorService;
use App\Domain\Pedidos\Services\VariacionProcessorService;
use App\Domain\Pedidos\Services\ProcesoProcessorService;

/**
 * Domain Service: PrendaTransformadorService
 * 
 * Contiene la lógica de negocio para transformar y normalizar datos de prendas
 * Centraliza el procesamiento que antes estaba en el frontend
 */
class PrendaTransformadorService
{
    private TallaProcessorService $tallaProcessor;
    private VariacionProcessorService $variacionProcessor;
    private ProcesoProcessorService $procesoProcessor;

    public function __construct(
        TallaProcessorService $tallaProcessor, 
        VariacionProcessorService $variacionProcessor,
        ProcesoProcessorService $procesoProcessor
    ) {
        $this->tallaProcessor = $tallaProcessor;
        $this->variacionProcessor = $variacionProcessor;
        $this->procesoProcessor = $procesoProcessor;
    }
    /**
     * Transforma los datos de una prenda para el frontend
     * Aplica origen automático, procesa telas, tallas, variaciones y procesos
     */
    public function transformarParaFrontend(object $prenda, ?object $cotizacion = null): array
    {
        // 1. Determinar origen
        $origen = $this->determinarOrigen($prenda, $cotizacion);
        
        // 2. Procesar telas
        $telas = $this->procesarTelas($prenda);
        
        // 3. Enriquecer telas desde variantes si es necesario
        if ($this->hayTelasSinReferencia($telas)) {
            $telas = $this->enriquecerTelasDesdeVariantes($telas, $prenda);
        }
        
        // 4. Procesar imágenes
        $imagenes = $this->procesarImagenes($prenda);
        
        // 5. Procesar tallas
        $tallasDTO = $this->procesarTallas($prenda);
        
        // 6. Procesar variaciones
        $variacionesDTO = $this->procesarVariaciones($prenda);
        
        // 7. Procesar procesos (NUEVO)
        $procesosDTO = $this->procesarProcesos($prenda);
        
        return [
            'id' => $prenda->id ?? null,
            'nombre_prenda' => $prenda->nombre_prenda ?? $prenda->nombre ?? '',
            'descripcion' => $prenda->descripcion ?? '',
            'origen' => $origen->valor(),
            'de_bodega' => $origen->esBodega(),
            'telasAgregadas' => array_map(fn($tela) => $tela->toArray(), $telas),
            'imagenes' => $imagenes,
            'variantes' => $prenda->variantes ?? [],
            'procesos' => $prenda->procesos ?? [],
            'tallas' => $prenda->tallas ?? [],
            'tallas_procesadas' => $tallasDTO->toArray(),
            'variaciones_procesadas' => $variacionesDTO->toArray(),
            'procesos_procesados' => $procesosDTO->toArray(), // NUEVO
            'cotizacion_id' => $prenda->cotizacion_id ?? null,
            'prenda_id' => $prenda->prenda_id ?? null
        ];
    }
    
    /**
     * Determina el origen de la prenda basado en cotización y datos existentes
     */
    private function determinarOrigen(object $prenda, ?object $cotizacion = null): OrigenPrenda
    {
        // Priorizar origen desde cotización
        if ($cotizacion) {
            return OrigenPrenda::desdeCotizacion($cotizacion, $prenda->origen ?? null);
        }
        
        // Si no hay cotización, usar origen existente o de_bodega
        if (isset($prenda->origen)) {
            return new OrigenPrenda($prenda->origen);
        }
        
        if (isset($prenda->de_bodega)) {
            return OrigenPrenda::desdeDeBodega($prenda->de_bodega);
        }
        
        // Default
        return new OrigenPrenda();
    }
    
    /**
     * Procesa las telas de la prenda desde diferentes fuentes
     */
    private function procesarTelas(object $prenda): array
    {
        $telas = [];
        
        // 1. Intentar desde telasAgregadas (ya procesadas)
        if (isset($prenda->telasAgregadas) && is_array($prenda->telasAgregadas)) {
            foreach ($prenda->telasAgregadas as $telaData) {
                $telas[] = new TelaPrenda(
                    $telaData->id ?? null,
                    $telaData->nombre_tela ?? $telaData->nombre ?? '',
                    $telaData->color ?? '',
                    $telaData->referencia ?? '',
                    $telaData->descripcion ?? null,
                    $telaData->grosor ?? null,
                    $telaData->composicion ?? null,
                    $telaData->imagenes ?? [],
                    $telaData->origen ?? 'frontend'
                );
            }
            return $telas;
        }
        
        // 2. Transformar desde colores_telas (BD)
        if (isset($prenda->colores_telas) && is_array($prenda->colores_telas)) {
            foreach ($prenda->colores_telas as $colorTela) {
                $telas[] = TelaPrenda::desdeColorTela($colorTela);
            }
            return $telas;
        }
        
        // 3. Extraer desde variantes.telais_multiples
        if (isset($prenda->variantes) && is_array($prenda->variantes)) {
            foreach ($prenda->variantes as $varianteIndex => $variante) {
                if (isset($variante->telas_multiples) && is_array($variante->telas_multiples)) {
                    foreach ($variante->telas_multiples as $telaIndex => $telaVariante) {
                        $telas[] = TelaPrenda::desdeVariante($telaVariante, $varianteIndex, $telaIndex);
                    }
                }
            }
            return $telas;
        }
        
        // 4. Buscar telas_multiples directamente en prenda
        if (isset($prenda->telas_multiples) && is_array($prenda->telas_multiples)) {
            foreach ($prenda->telas_multiples as $telaIndex => $telaVariante) {
                $telas[] = TelaPrenda::desdeVariante($telaVariante, 0, $telaIndex);
            }
        }
        
        return $telas;
    }
    
    /**
     * Verifica si hay telas sin referencia
     */
    private function hayTelasSinReferencia(array $telas): bool
    {
        foreach ($telas as $tela) {
            if (!$tela->tieneReferencia()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Enriquece las telas existentes con referencias desde variantes
     */
    private function enriquecerTelasDesdeVariantes(array $telas, object $prenda): array
    {
        // Crear mapa de telas existentes por clave
        $mapaTelas = [];
        foreach ($telas as $index => $tela) {
            $mapaTelas[$tela->clave()] = ['index' => $index, 'tela' => $tela];
        }
        
        // Buscar referencias en variantes
        $variantesParaProcesar = $this->obtenerVariantesParaProcesar($prenda);
        
        foreach ($variantesParaProcesar as $variante) {
            if (isset($variante->telas_multiples) && is_array($variante->telas_multiples)) {
                foreach ($variante->telas_multiples as $telaVariante) {
                    $telaCandidata = TelaPrenda::desdeVariante($telaVariante);
                    $clave = $telaCandidata->clave();
                    
                    if (isset($mapaTelas[$clave])) {
                        $telaExistente = $mapaTelas[$clave]['tela'];
                        if (!$telaExistente->tieneReferencia() && $telaCandidata->tieneReferencia()) {
                            // Enriquecer la tela existente
                            $telaExistente->enriquecerDesde($telaCandidata);
                        }
                    }
                }
            }
        }
        
        return $telas;
    }
    
    /**
     * Obtiene las variantes para procesar referencias
     */
    private function obtenerVariantesParaProcesar(object $prenda): array
    {
        $variantes = [];
        
        // Caso 1: variantes es array
        if (isset($prenda->variantes) && is_array($prenda->variantes)) {
            $variantes = $prenda->variantes;
        }
        // Caso 2: variantes es objeto con telas_multiples
        elseif (isset($prenda->variantes) && is_object($prenda->variantes) && isset($prenda->variantes->telas_multiples)) {
            $variantes = [$prenda->variantes];
        }
        // Caso 3: telas_multiples directamente en prenda
        elseif (isset($prenda->telas_multiples) && is_array($prenda->telas_multiples)) {
            $variantes = [(object) ['telas_multiples' => $prenda->telas_multiples]];
        }
        
        return $variantes;
    }
    
    /**
     * Procesa y estandariza las imágenes de la prenda
     */
    private function procesarImagenes(object $prenda): array
    {
        $imagenes = [];
        
        // Prioridad 1: imágenes (formulario)
        if (isset($prenda->imagenes) && is_array($prenda->imagenes)) {
            foreach ($prenda->imagenes as $img) {
                $imagenes[] = $this->estandarizarImagen($img);
            }
        }
        
        // Prioridad 2: fotos (BD alternativo)
        if (empty($imagenes) && isset($prenda->fotos) && is_array($prenda->fotos)) {
            foreach ($prenda->fotos as $foto) {
                $imagenes[] = $this->estandarizarImagen($foto);
            }
        }
        
        // Prioridad 3: imágenes de procesos
        if (empty($imagenes) && isset($prenda->procesos) && is_object($prenda->procesos)) {
            foreach ($prenda->procesos as $tipoProceso => $dataProceso) {
                if (isset($dataProceso->imagenes) && is_array($dataProceso->imagenes)) {
                    foreach ($dataProceso->imagenes as $img) {
                        $imagenes[] = $this->estandarizarImagen($img);
                    }
                    break; // Solo primer proceso con imágenes
                }
            }
        }
        
        return $imagenes;
    }
    
    /**
     * Estandariza una imagen al formato esperado por el frontend
     */
    private function estandarizarImagen($imagen): array
    {
        if (is_string($imagen)) {
            return [
                'previewUrl' => $imagen,
                'url' => $imagen,
                'nombre' => basename($imagen),
                'tamaño' => 0,
                'file' => null,
                'urlDesdeDB' => true
            ];
        }
        
        if (is_object($imagen)) {
            return [
                'id' => $imagen->id ?? null,
                'prenda_foto_id' => $imagen->prenda_foto_id ?? null,
                'previewUrl' => $imagen->previewUrl ?? $imagen->url ?? $imagen->ruta ?? '',
                'url' => $imagen->url ?? $imagen->ruta ?? '',
                'ruta_original' => $imagen->ruta_original ?? '',
                'ruta_webp' => $imagen->ruta_webp ?? '',
                'nombre' => $imagen->nombre ?? basename($imagen->url ?? ''),
                'tamaño' => $imagen->tamaño ?? 0,
                'file' => null,
                'urlDesdeDB' => true
            ];
        }
        
        return [];
    }
    
    /**
     * Carga datos específicos de cotización para prendas Reflectivo/Logo
     */
    public function cargarDatosCotizacion(int $cotizacionId, int $prendaId): array
    {
        // Esta lógica debería implementarse con los repositorios apropiados
        // Por ahora retornamos estructura esperada
        return [
            'telas' => [],
            'variaciones' => [],
            'ubicaciones' => [],
            'descripcion' => ''
        ];
    }
    
    /**
     * Procesar tallas usando el TallaProcessorService
     */
    private function procesarTallas(object $prenda): TallaPrendaDTO
    {
        $prendaData = [
            'cantidad_talla' => $prenda->cantidad_talla ?? null,
            'tallas' => $prenda->tallas ?? [],
            'variantes' => $prenda->variantes ?? [],
            'procesos' => $prenda->procesos ?? [],
            'cantidad' => $prenda->cantidad ?? 0,
            'genero' => $prenda->genero ?? null
        ];
        
        return $this->tallaProcessor->procesarTallasPrenda($prendaData);
    }
    
    /**
     * Procesar variaciones usando el VariacionProcessorService
     */
    private function procesarVariaciones(object $prenda): VariacionPrendaDTO
    {
        $prendaData = [
            'variantes' => $prenda->variantes ?? [],
            'procesos' => $prenda->procesos ?? [],
            'genero' => $prenda->genero ?? null
        ];
        
        return $this->variacionProcessor->procesarVariacionesPrenda($prendaData);
    }
    
    /**
     * Procesar procesos usando el ProcesoProcessorService
     */
    private function procesarProcesos(object $prenda): ProcesoPrendaDTO
    {
        $prendaData = [
            'procesos' => $prenda->procesos ?? [],
            'genero' => $prenda->genero ?? null
        ];
        
        return $this->procesoProcessor->procesarProcesosPrenda($prendaData);
    }
}
