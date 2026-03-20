<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetNotificationsResponse
{
    public function __construct(
        private bool $success,
        private \Illuminate\Support\Collection $notifications,
        private \Illuminate\Support\Collection $news,
        private int $totalPending,
        private int $totalOrdersNotViewed,
        private int $totalNews,
        private int $totalNewsNotViewed,
        private int $totalGeneral
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'notificaciones' => $this->notifications->values(),
            'novedades' => $this->news,
            'totalPendientes' => $this->totalPending,
            'totalOrdenesNoVistas' => $this->totalOrdersNotViewed,
            'totalNovedades' => $this->totalNews,
            'totalNovedadesNoVistas' => $this->totalNewsNotViewed,
            'totalGeneral' => $this->totalGeneral,
        ];
    }

    public function getNotifications(): \Illuminate\Support\Collection
    {
        return $this->notifications;
    }

    public function getNews(): \Illuminate\Support\Collection
    {
        return $this->news;
    }

    public function getTotalGeneral(): int
    {
        return $this->totalGeneral;
    }
}
