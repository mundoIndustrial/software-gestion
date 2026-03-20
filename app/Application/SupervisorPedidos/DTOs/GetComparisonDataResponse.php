<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetComparisonDataResponse
{
    private array $comparisonData;

    public function __construct(array $comparisonData)
    {
        $this->comparisonData = $comparisonData;
    }

    public function getData(): array
    {
        return $this->comparisonData;
    }

    public function toArray(): array
    {
        return $this->comparisonData;
    }
}
