<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use App\Models\PushSubscription;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    public function notifyCarteraPedidoPendiente(PedidoProduccion $pedido): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $subscriptionQuery = $this->subscriptionsForCartera();
        if (!$subscriptionQuery->exists()) {
            return;
        }

        $payload = [
            'title' => 'Nuevo pedido pendiente de cartera',
            'body' => sprintf(
                'Pedido %s de %s pendiente por autorizar.',
                $pedido->numero_pedido ?? ('#' . $pedido->id),
                $pedido->cliente ?? 'Cliente'
            ),
            'icon' => '/mundo_icon.png',
            'badge' => '/mundo_icon2.png',
            'url' => '/cartera/pedidos',
            'tag' => 'cartera-pendiente-' . ($pedido->id ?? '0'),
            'data' => [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'estado' => $pedido->estado,
            ],
        ];

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => (string) config('push.vapid.subject'),
                'publicKey' => (string) config('push.vapid.public_key'),
                'privateKey' => (string) config('push.vapid.private_key'),
            ],
        ]);

        $subscriptions = $subscriptionQuery->get();
        foreach ($subscriptions as $subscriptionRow) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscriptionRow->endpoint,
                    'publicKey' => $subscriptionRow->public_key,
                    'authToken' => $subscriptionRow->auth_token,
                    'contentEncoding' => $subscriptionRow->content_encoding ?: 'aes128gcm',
                ]),
                json_encode($payload, JSON_UNESCAPED_UNICODE)
            );
        }

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();
            if ($report->isSuccess()) {
                continue;
            }

            PushSubscription::where('endpoint', $endpoint)->delete();
            Log::warning('[PushNotificationService] Push failed, subscription removed', [
                'endpoint' => $endpoint,
                'reason' => $report->getReason(),
            ]);
        }
    }

    private function subscriptionsForCartera()
    {
        $carteraRole = Role::query()->whereRaw('LOWER(name) = ?', ['cartera'])->first();
        if (!$carteraRole) {
            return PushSubscription::query()->whereRaw('1 = 0');
        }

        $carteraUserIds = User::query()
            ->where(function ($q) use ($carteraRole) {
                $q->where('role_id', $carteraRole->id)
                    ->orWhereJsonContains('roles_ids', $carteraRole->id);
            })
            ->pluck('id');

        return PushSubscription::query()->whereIn('user_id', $carteraUserIds);
    }

    private function isConfigured(): bool
    {
        return (bool) config('push.vapid.public_key')
            && (bool) config('push.vapid.private_key')
            && (bool) config('push.vapid.subject');
    }
}

