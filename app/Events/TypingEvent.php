<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingEvent implements ShouldBroadcastNow
{ 
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $conversationId;
    public $userId;
    public $isTyping;
    /**
     * Create a new event instance.
     */
    public function __construct($conversationId, $userId, $isTyping)
    {
        $this->conversationId = $conversationId;
        $this->userId = $userId;
        $this->isTyping = $isTyping;


    }


    public function broadcastWith()
{
    
    return [
        'conversationId' => $this->conversationId,
        'userId' => $this->userId,
        'isTyping' => $this->isTyping,
        'typing_at' => $this->isTyping ? now()->toDateTimeString() : null
    ];
}
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new PrivateChannel('conversation.' . $this->conversationId);
    }

    public function broadcastAs()
    {
        return 'TypingEvent';
    }
}
