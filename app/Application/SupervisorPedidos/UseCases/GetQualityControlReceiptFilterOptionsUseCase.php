<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetSewingReceiptFilterOptionsRequest;
use App\Application\SupervisorPedidos\DTOs\GetSewingReceiptFilterOptionsResponse;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use Illuminate\Support\Facades\Log;

class GetQualityControlReceiptFilterOptionsUseCase
{
    public function __construct(
        private readonly ReceiptRepository $receiptRepository
    ) {}

    public function execute(GetSewingReceiptFilterOptionsRequest $request): GetSewingReceiptFilterOptionsResponse
    {
        try {
            $field = $request->getField();
            $options = $this->receiptRepository->getQualityControlReceiptFilterOptions($field);

            Log::info('[GetQualityControlReceiptFilterOptionsUseCase] Retrieved ' . count($options) . ' options for field: ' . $field);

            return new GetSewingReceiptFilterOptionsResponse($options);

        } catch (\Exception $e) {
            Log::error('[GetQualityControlReceiptFilterOptionsUseCase] Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
