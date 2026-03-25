<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

/**
 * ResourceNotFoundException - Excepción cuando un recurso no existe
 * 
 * Se lanza cuando se intenta acceder a un recurso que no existe.
 */
class ResourceNotFoundException extends DomainException
{
    protected string $errorCode = 'RESOURCE_NOT_FOUND';
    protected int $httpStatusCode = Response::HTTP_NOT_FOUND;
    protected string $resourceType;

    public function __construct(
        string $resourceType = 'Recurso',
        string $identifier = '',
        ?string $message = null,
        array $context = []
    ) {
        $this->resourceType = $resourceType;

        $defaultMessage = $identifier
            ? "{$resourceType} con ID '{$identifier}' no existe"
            : "{$resourceType} no encontrado";

        parent::__construct(
            $message ?? $defaultMessage,
            'RESOURCE_NOT_FOUND',
            Response::HTTP_NOT_FOUND,
            array_merge($context, [
                'resource_type' => $resourceType,
                'identifier' => $identifier,
            ])
        );
    }

    /**
     * Obtiene el tipo de recurso
     */
    public function getResourceType(): string
    {
        return $this->resourceType;
    }
}
