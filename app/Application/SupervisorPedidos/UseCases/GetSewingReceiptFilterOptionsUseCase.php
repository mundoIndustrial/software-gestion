<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetSewingReceiptFilterOptionsRequest;
use App\Application\SupervisorPedidos\DTOs\GetSewingReceiptFilterOptionsResponse;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use Illuminate\Support\Facades\Log;

class GetSewingReceiptFilterOptionsUseCase
{
    public function __construct(
        private readonly ReceiptRepository $receiptRepository
    ) {}

    public function execute(GetSewingReceiptFilterOptionsRequest $request): GetSewingReceiptFilterOptionsResponse
    {
        try {
            $field = $request->getField();
            $options = $this->receiptRepository->getSewingReceiptFilterOptions($field);

            Log::info('[GetSewingReceiptFilterOptionsUseCase] Retrieved ' . count($options) . ' options for field: ' . $field);

            return new GetSewingReceiptFilterOptionsResponse($options);

        } catch (\Exception $e) {
            Log::error('[GetSewingReceiptFilterOptionsUseCase] Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
