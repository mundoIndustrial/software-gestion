<?php

namespace App\Providers;

use App\Helpers\EstadoHelper;
use App\Helpers\AtributosPrendaHelper;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BladeDirectivesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /**
         * Directiva @estadoLabelCotizacion
         * Retorna solo el label de un estado de cotización
         * 
         * Uso:
         * @estadoLabelCotizacion($cotizacion->estado)
         */
        Blade::directive('estadoLabelCotizacion', function (string $expression) {
            return "<?php echo \\App\\Helpers\\EstadoHelper::labelCotizacion({$expression}); ?>";
        });

        /**
         * Directiva @estadoColorCotizacion
         * Retorna solo el color de un estado de cotización
         * 
         * Uso:
         * <div style="background-color: @estadoColorCotizacion($cotizacion->estado)">
         */
        Blade::directive('estadoColorCotizacion', function (string $expression) {
            return "<?php echo \\App\\Helpers\\EstadoHelper::colorCotizacion({$expression}); ?>";
        });

        /**
         * Directiva @estadoIconoCotizacion
         * Retorna solo el icono de un estado de cotización
         * 
         * Uso:
         * <i class="@estadoIconoCotizacion($cotizacion->estado)"></i>
         */
        Blade::directive('estadoIconoCotizacion', function (string $expression) {
            return "<?php echo \\App\\Helpers\\EstadoHelper::iconoCotizacion({$expression}); ?>";
        });

        /**
         * Directiva @estadoLabelPedido
         * Retorna solo el label de un estado de pedido
         * 
         * Uso:
         * @estadoLabelPedido($pedido->estado)
         */
        Blade::directive('estadoLabelPedido', function (string $expression) {
            return "<?php echo \\App\\Helpers\\EstadoHelper::labelPedido({$expression}); ?>";
        });

        /**
         * Directiva @estadoColorPedido
         * Retorna solo el color de un estado de pedido
         * 
         * Uso:
         * style="background-color: @estadoColorPedido($pedido->estado)"
         */
        Blade::directive('estadoColorPedido', function (string $expression) {
            return "<?php echo \\App\\Helpers\\EstadoHelper::colorPedido({$expression}); ?>";
        });

        /**
         * Directiva @estadoIconoPedido
         * Retorna solo el icono de un estado de pedido
         * 
         * Uso:
         * <i class="@estadoIconoPedido($pedido->estado)"></i>
         */
        Blade::directive('estadoIconoPedido', function (string $expression) {
            return "<?php echo \\App\\Helpers\\EstadoHelper::iconoPedido({$expression}); ?>";
        });

        /**
         * Directiva @humanizar
         * Convierte cadenas con guiones bajos a formato legible
         * ENVIADA_CONTADOR → Enviada Contador
         * 
         * Uso:
         * @humanizar($cotizacion->estado)
         */
        Blade::directive('humanizar', function (string $expression) {
            return "<?php echo \\App\\Helpers\\EstadoHelper::humanizar({$expression}); ?>";
        });

        /**
         * Directiva @colorNombre
         * Retorna el nombre del color a partir del color_id
         * 
         * Uso:
         * @colorNombre($color_id)
         * @colorNombre($prenda->color_id)
         */
        Blade::directive('colorNombre', function (string $expression) {
            return "<?php echo \\App\\Helpers\\AtributosPrendaHelper::obtenerNombreColor({$expression}); ?>";
        });

        /**
         * Directiva @colorLabel
         * Alias de @colorNombre para consistencia
         * 
         * Uso:
         * @colorLabel($color_id)
         */
        Blade::directive('colorLabel', function (string $expression) {
            return "<?php echo \\App\\Helpers\\AtributosPrendaHelper::obtenerNombreColor({$expression}); ?>";
        });

        /**
         * Directiva @telaNombre
         * Retorna el nombre de la tela a partir del tela_id
         * 
         * Uso:
         * @telaNombre($tela_id)
         * @telaNombre($prenda->tela_id)
         */
        Blade::directive('telaNombre', function (string $expression) {
            return "<?php echo \\App\\Helpers\\AtributosPrendaHelper::obtenerNombreTela({$expression}); ?>";
        });

        /**
         * Directiva @telaReferencia
         * Retorna la referencia de la tela a partir del tela_id
         * 
         * Uso:
         * @telaReferencia($tela_id)
         * @telaReferencia($prenda->tela_id)
         */
        Blade::directive('telaReferencia', function (string $expression) {
            return "<?php echo \\App\\Helpers\\AtributosPrendaHelper::obtenerReferenciaTela({$expression}); ?>";
        });

        /**
         * Directiva @telaFormato
         * Retorna la tela con el formato: "Nombre Tela (Ref: XXXXX)"
         * 
         * Uso:
         * @telaFormato($tela_id)
         * @telaFormato($prenda->tela_id)
         */
        Blade::directive('telaFormato', function (string $expression) {
            return "<?php echo \\App\\Helpers\\AtributosPrendaHelper::formatearTela({$expression}); ?>";
        });

        /**
         * Directiva @telaLabel
         * Alias de @telaNombre para consistencia
         * 
         * Uso:
         * @telaLabel($tela_id)
         */
        Blade::directive('telaLabel', function (string $expression) {
            return "<?php echo \\App\\Helpers\\AtributosPrendaHelper::obtenerNombreTela({$expression}); ?>";
        });
    }
}
