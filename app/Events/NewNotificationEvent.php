<?php

namespace App\Events;

use App\Models\News;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $excludeUserId;

    /**
     * Create a new event instance.
     */
    public function __construct(News $notification, ?int $excludeUserId = null)
    {
        $this->notification = $notification;
        $this->excludeUserId = $excludeUserId;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('notifications'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'new-notification';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'event_type' => $this->notification->event_type,
            'table_name' => $this->notification->table_name,
            'record_id' => $this->notification->record_id,
            'description' => $this->notification->description,
            'created_at' => $this->notification->created_at->format('Y-m-d H:i:s'),
            'user' => $this->notification->user ? $this->notification->user->name : 'Sistema',
            'user_id' => $this->notification->user_id,
            'pedido' => $this->notification->pedido,
            'metadata' => $this->notification->metadata,
            'status' => $this->notification->status ?? 'unread',
            'exclude_user_id' => $this->excludeUserId,
        ];
    }
}
