<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsRequest;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsResponse;
use App\Application\SupervisorPedidos\Services\GetOrderDetailsReadService;
use Illuminate\Support\Facades\Log;

class GetOrderDetailsUseCase
{
    public function __construct(
        private readonly GetOrderDetailsReadService $readService
    ) {}

    public function execute(GetOrderDetailsRequest $request): GetOrderDetailsResponse
    {
        try {
            $data = $this->readService->getDetails($request);
            return new GetOrderDetailsResponse($data);
        } catch (\Exception $e) {
            Log::error('Error en GetOrderDetails: ' . $e->getMessage());
            throw $e;
        }
    }
}
