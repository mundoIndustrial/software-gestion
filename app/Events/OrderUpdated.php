<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderId;
    public $field;
    public $newValue;
    public $oldValue;
    public $updatedFields;
    public $order;
    public $totalDiasCalculados;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct($orderId, $field, $newValue, $oldValue, $updatedFields = [], $order = null, $totalDiasCalculados = [], $userId = null)
    {
        $this->orderId = $orderId;
        $this->field = $field;
        $this->newValue = $newValue;
        $this->oldValue = $oldValue;
        $this->updatedFields = $updatedFields;
        $this->order = $order;
        $this->totalDiasCalculados = $totalDiasCalculados;
        $this->userId = $userId ?: auth()->id();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders-updates'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'order_update',
            'orderId' => $this->orderId,
            'field' => $this->field,
            'newValue' => $this->newValue,
            'oldValue' => $this->oldValue,
            'updatedFields' => $this->updatedFields,
            'order' => $this->order,
            'totalDiasCalculados' => $this->totalDiasCalculados,
            'userId' => $this->userId,
            'timestamp' => now()->timestamp * 1000, // milliseconds
        ];
    }
}
