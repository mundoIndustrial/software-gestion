<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\TokenMismatchException;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): Response
    {
        // Manejar excepciones personalizadas del dominio
        if ($this->isDomainException($e)) {
            return $this->renderDomainException($request, $e);
        }

        // Si la sesión expiró, token CSRF inválido o no está autenticado, redirigir a login
        if ($e instanceof AuthenticationException || $e instanceof TokenMismatchException) {
            // Si es logout y hay error de token, permitir que continúe
            if ($request->path() === 'logout' || $request->routeIs('logout')) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                return redirect('/')->with('error', 'Tu sesión expiró. Por favor, inicia sesión nuevamente.');
            }
            
            return redirect()->route('login')->with('error', 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
        }

        // Si usuario no está autenticado y accede a ruta protegida, redirigir a login
        if (!auth()->check() && ($e instanceof NotFoundHttpException || $e instanceof AccessDeniedHttpException)) {
            return redirect()->route('login')->with('error', 'No tienes acceso a esta página. Debes estar autenticado.');
        }

        // Si es una petición AJAX o API, devolver JSON
        if ($request->expectsJson()) {
            return $this->renderJsonResponse($e);
        }

        // Para errores de validación, usar el comportamiento por defecto
        if ($e instanceof ValidationException) {
            return parent::render($request, $e);
        }

        // Renderizar nuestra vista personalizada de error
        return $this->renderCustomErrorPage($request, $e);
    }

    /**
     * Verifica si es una excepción personalizada del dominio
     */
    protected function isDomainException(Throwable $e): bool
    {
        return $e instanceof CotizacionException ||
               $e instanceof PrendaException ||
               $e instanceof ImagenException ||
               $e instanceof PedidoException;
    }

    /**
     * Renderiza excepción del dominio
     */
    protected function renderDomainException(Request $request, Throwable $e): Response
    {
        // Log con contexto
        \Log::warning('Excepción de dominio', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'context' => method_exists($e, 'getContext') ? $e->getContext() : []
        ]);

        // Si espera JSON, responder con el array del error
        if ($request->expectsJson()) {
            if (method_exists($e, 'toArray')) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 400;
                return response()->json($e->toArray(), $statusCode);
            }
        }

        // Para HTML, mostrar error amigable
        return $this->renderCustomErrorPage($request, $e);
    }

    /**
     * Renderiza la página de error personalizada
     */
    protected function renderCustomErrorPage(Request $request, Throwable $e): Response
    {
        $errorData = $this->prepareErrorData($e);
        
        return response()->view('error', $errorData, $this->getStatusCode($e));
    }

    /**
     * Prepara los datos del error para la vista
     */
    protected function prepareErrorData(Throwable $e): array
    {
        return [
            'friendlyMessage' => $this->getFriendlyMessage($e),
            'errorCode' => $this->getErrorCode($e),
            'technicalDetails' => $this->getTechnicalDetails($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $this->getFormattedTrace($e),
        ];
    }

    /**
     * Convierte errores técnicos a mensajes amigables
     */
    protected function getFriendlyMessage(Throwable $e): string
    {
        switch (true) {
            case $e instanceof CotizacionException:
            case $e instanceof PrendaException:
            case $e instanceof ImagenException:
            case $e instanceof PedidoException:
                return $e->getMessage();

            case $e instanceof NotFoundHttpException:
                return 'La página que buscas no existe o ha sido movida. Verifica que la dirección esté correcta.';
                
            case $e instanceof MethodNotAllowedHttpException:
                return 'La acción que intentas realizar no está permitida en esta página.';
                
            case $e instanceof AccessDeniedHttpException:
                return 'No tienes permisos para acceder a esta sección. Contacta al administrador si crees que es un error.';
                
            case $e instanceof QueryException:
                if (str_contains($e->getMessage(), 'Duplicate entry')) {
                    return 'Ya existe un registro con esa información. Por favor, verifica los datos e intenta nuevamente.';
                }
                if (str_contains($e->getMessage(), 'foreign key constraint')) {
                    return 'No se puede completar la operación porque hay datos relacionados. Elimina primero las dependencias.';
                }
                if (str_contains($e->getMessage(), 'Connection refused')) {
                    return 'No se puede conectar con la base de datos. El servicio podría estar temporalmente no disponible.';
                }
                return 'Ocurrió un problema al procesar la información en la base de datos. Intenta nuevamente.';
                
            case str_contains($e->getMessage(), 'file_get_contents'):
            case str_contains($e->getMessage(), 'fopen'):
                return 'No se pudo acceder a un archivo necesario. Verifica que el archivo existe y tienes permisos.';
                
            case str_contains($e->getMessage(), 'Class') && str_contains($e->getMessage(), 'not found'):
                return 'Falta un componente del sistema. Contacta al administrador para resolver este problema.';
                
            case str_contains($e->getMessage(), 'Call to undefined method'):
                return 'Se intentó usar una función que no existe. Este es un error de programación que debe ser corregido.';
                
            case str_contains($e->getMessage(), 'memory limit'):
                return 'El sistema se quedó sin memoria para procesar la solicitud. Intenta con menos datos o contacta al administrador.';
                
            case str_contains($e->getMessage(), 'timeout'):
                return 'La operación tardó demasiado tiempo en completarse. Intenta nuevamente o contacta al administrador.';
                
            case str_contains($e->getMessage(), 'CSRF'):
                return 'Tu sesión ha expirado por seguridad. Por favor, inicia sesión nuevamente.';
                
            case str_contains($e->getMessage(), 'permission denied'):
                return 'El sistema no tiene permisos para realizar esta operación. Contacta al administrador.';
                
            default:
                return 'Ocurrió un problema inesperado en el sistema. Nuestro equipo ha sido notificado y está trabajando para solucionarlo.';
        }
    }

    /**
     * Genera un código de error único
     */
    protected function getErrorCode(Throwable $e): string
    {
        return 'ERR-' . strtoupper(substr(md5($e->getFile() . $e->getLine() . $e->getMessage()), 0, 8));
    }

    /**
     * Obtiene los detalles técnicos del error
     */
    protected function getTechnicalDetails(Throwable $e): string
    {
        $details = [];
        $details[] = 'Tipo: ' . get_class($e);
        $details[] = 'Mensaje: ' . $e->getMessage();
        
        if ($e instanceof QueryException && $e->getPrevious()) {
            $details[] = 'Error SQL: ' . $e->getPrevious()->getMessage();
        }
        
        return implode("\n", $details);
    }

    /**
     * Formatea el stack trace para mostrar
     */
    protected function getFormattedTrace(Throwable $e): string
    {
        $trace = $e->getTraceAsString();
        
        // Limitar el trace a las primeras 10 líneas para evitar información excesiva
        $lines = explode("\n", $trace);
        $limitedLines = array_slice($lines, 0, 10);
        
        if (count($lines) > 10) {
            $limitedLines[] = '... (' . (count($lines) - 10) . ' líneas más)';
        }
        
        return implode("\n", $limitedLines);
    }

    /**
     * Obtiene el código de estado HTTP apropiado
     */
    protected function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }
        
        switch (true) {
            case $e instanceof NotFoundHttpException:
                return 404;
            case $e instanceof MethodNotAllowedHttpException:
                return 405;
            case $e instanceof AccessDeniedHttpException:
                return 403;
            case $e instanceof ValidationException:
                return 422;
            default:
                return 500;
        }
    }

    /**
     * Renderiza respuesta JSON para peticiones AJAX/API
     */
    protected function renderJsonResponse(Throwable $e): Response
    {
        return response()->json([
            'error' => true,
            'message' => $this->getFriendlyMessage($e),
            'code' => $this->getErrorCode($e),
            'details' => config('app.debug') ? [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ] : null
        ], $this->getStatusCode($e));
    }
}

