<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationParticipant extends Model
{
    protected $guarded = [

    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function markAsRead($messageId)
    {
        $this->update(['last_read_message_id' => $messageId]);
    }
    public function isOnline()
    {
        return $this->is_online;
    }
    public function setOnlineStatus($status)
    {
        $this->update(['is_online' => $status]);
    }
}
