<?php

namespace App\Http\Controllers;

use App\Exceptions\RegistroOrdenException;
use App\Exceptions\RegistroOrdenValidationException;
use App\Exceptions\RegistroOrdenNotFoundException;
use App\Exceptions\RegistroOrdenPedidoNumberException;
use App\Exceptions\RegistroOrdenCreationException;
use App\Exceptions\RegistroOrdenUpdateException;
use App\Exceptions\RegistroOrdenDeletionException;
use App\Exceptions\RegistroOrdenPrendaException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

/**
 * RegistroOrdenExceptionHandler
 * 
 * Trait para centralizar manejo de excepciones en el controlador
 * Implementa el patrón Advice para respuestas consistentes
 * 
 * Uso: use RegistroOrdenExceptionHandler en el controlador
 */
trait RegistroOrdenExceptionHandler
{
    /**
     * Maneja RegistroOrdenException y sus subclases
     * Todas las excepciones personalizadas son centralizadas aquí
     */
    protected function handleRegistroOrdenException(RegistroOrdenException $e): JsonResponse
    {
        // Log según el nivel de error
        if ($e->getStatusCode() >= 500) {
            \Log::error('RegistroOrdenException - Server Error', [
                'error_code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
                'context' => $e->getContext(),
                'trace' => $e->getTraceAsString()
            ]);
        } else {
            \Log::warning('RegistroOrdenException - Client Error', [
                'error_code' => $e->getErrorCode(),
                'message' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
        }

        return response()->json(
            $e->getJsonResponse(),
            $e->getStatusCode()
        );
    }

    /**
     * Maneja RegistroOrdenValidationException
     */
    protected function handleValidationException(RegistroOrdenValidationException $e): JsonResponse
    {
        \Log::info('Validation Error', [
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'errors' => $e->getContext()['validation_errors'] ?? []
        ]);

        return response()->json([
            'success' => false,
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'errors' => $e->getContext()['validation_errors'] ?? [],
            'timestamp' => now()->toIso8601String()
        ], 422);
    }

    /**
     * Maneja RegistroOrdenNotFoundException
     */
    protected function handleNotFoundException(RegistroOrdenNotFoundException $e): JsonResponse
    {
        \Log::warning('Order Not Found', [
            'error_code' => $e->getErrorCode(),
            'pedido' => $e->getContext()['pedido'] ?? 'N/A'
        ]);

        return response()->json([
            'success' => false,
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'timestamp' => now()->toIso8601String()
        ], 404);
    }

    /**
     * Maneja ModelNotFoundException de Eloquent
     */
    protected function handleModelNotFoundException(ModelNotFoundException $e, string $pedido = ''): JsonResponse
    {
        $exception = RegistroOrdenNotFoundException::fromModelNotFound($pedido, $e);
        return $this->handleNotFoundException($exception);
    }

    /**
     * Maneja ValidationException de Laravel
     */
    protected function handleLaravelValidationException(ValidationException $e): JsonResponse
    {
        $exception = new RegistroOrdenValidationException(
            'Error de validación',
            $e->errors()
        );
        return $this->handleValidationException($exception);
    }

    /**
     * Maneja RegistroOrdenPedidoNumberException
     */
    protected function handlePedidoNumberException(RegistroOrdenPedidoNumberException $e): JsonResponse
    {
        \Log::warning('Pedido Number Error', [
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'context' => $e->getContext()
        ]);

        return response()->json([
            'success' => false,
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'context' => $e->getContext(),
            'timestamp' => now()->toIso8601String()
        ], 422);
    }

    /**
     * Maneja RegistroOrdenCreationException
     */
    protected function handleCreationException(RegistroOrdenCreationException $e): JsonResponse
    {
        \Log::error('Order Creation Error', [
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'context' => $e->getContext(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'context' => $e->getContext(),
            'timestamp' => now()->toIso8601String()
        ], $e->getStatusCode());
    }

    /**
     * Maneja RegistroOrdenUpdateException
     */
    protected function handleUpdateException(RegistroOrdenUpdateException $e): JsonResponse
    {
        \Log::error('Order Update Error', [
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'context' => $e->getContext(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'context' => $e->getContext(),
            'timestamp' => now()->toIso8601String()
        ], $e->getStatusCode());
    }

    /**
     * Maneja RegistroOrdenDeletionException
     */
    protected function handleDeletionException(RegistroOrdenDeletionException $e): JsonResponse
    {
        \Log::error('Order Deletion Error', [
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'context' => $e->getContext(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'context' => $e->getContext(),
            'timestamp' => now()->toIso8601String()
        ], $e->getStatusCode());
    }

    /**
     * Maneja RegistroOrdenPrendaException
     */
    protected function handlePrendaException(RegistroOrdenPrendaException $e): JsonResponse
    {
        \Log::warning('Prenda Error', [
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'context' => $e->getContext()
        ]);

        return response()->json([
            'success' => false,
            'error_code' => $e->getErrorCode(),
            'message' => $e->getMessage(),
            'context' => $e->getContext(),
            'timestamp' => now()->toIso8601String()
        ], 422);
    }

    /**
     * Maneja excepciones genéricas (fallback)
     */
    protected function handleGenericException(\Exception $e): JsonResponse
    {
        \Log::error('Unhandled Exception in RegistroOrdenController', [
            'exception_class' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'error_code' => 'INTERNAL_SERVER_ERROR',
            'message' => config('app.debug') 
                ? $e->getMessage()
                : 'Error interno del servidor. Por favor intente nuevamente.',
            'timestamp' => now()->toIso8601String()
        ], 500);
    }

    /**
     * Método helper para tryExec (ejecutar con manejo de excepciones)
     * Uso: return $this->tryExec(fn() => $this->someMethod());
     */
    protected function tryExec(callable $callback, bool $returnJson = true): mixed
    {
        try {
            return $callback();
        } catch (RegistroOrdenValidationException $e) {
            return $returnJson ? $this->handleValidationException($e) : throw $e;
        } catch (RegistroOrdenNotFoundException $e) {
            return $returnJson ? $this->handleNotFoundException($e) : throw $e;
        } catch (RegistroOrdenPedidoNumberException $e) {
            return $returnJson ? $this->handlePedidoNumberException($e) : throw $e;
        } catch (RegistroOrdenCreationException $e) {
            return $returnJson ? $this->handleCreationException($e) : throw $e;
        } catch (RegistroOrdenUpdateException $e) {
            return $returnJson ? $this->handleUpdateException($e) : throw $e;
        } catch (RegistroOrdenDeletionException $e) {
            return $returnJson ? $this->handleDeletionException($e) : throw $e;
        } catch (RegistroOrdenPrendaException $e) {
            return $returnJson ? $this->handlePrendaException($e) : throw $e;
        } catch (RegistroOrdenException $e) {
            return $returnJson ? $this->handleRegistroOrdenException($e) : throw $e;
        } catch (ValidationException $e) {
            return $returnJson ? $this->handleLaravelValidationException($e) : throw $e;
        } catch (ModelNotFoundException $e) {
            return $returnJson ? $this->handleModelNotFoundException($e) : throw $e;
        } catch (\Exception $e) {
            return $returnJson ? $this->handleGenericException($e) : throw $e;
        }
    }
}
