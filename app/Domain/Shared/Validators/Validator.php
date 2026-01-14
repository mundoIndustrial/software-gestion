<?php

namespace App\Domain\Shared\Validators;

use InvalidArgumentException;

/**
 * Base Validator - Interface para validadores de dominio
 * 
 * Responsabilidad:
 * - Definir contrato para validadores
 * - Validar datos según reglas de negocio
 * - Lanzar excepciones si hay errores
 */
interface Validator
{
    /**
     * Validar los datos
     * 
     * @param array $data Datos a validar
     * @return void
     * @throws InvalidArgumentException Si hay error de validación
     */
    public function validate(array $data): void;

    /**
     * Validar un campo específico
     * 
     * @param string $field Nombre del campo
     * @param mixed $value Valor a validar
     * @return void
     * @throws InvalidArgumentException Si hay error de validación
     */
    public function validateField(string $field, mixed $value): void;
}
