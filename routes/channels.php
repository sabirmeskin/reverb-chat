<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});


Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    return $user->conversations()->whereHas('participants', function ($q) use ($conversationId) {
        $q->where('conversation_id', $conversationId);
    })->exists();
});

Broadcast::channel('chat', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});

Broadcast::channel('user-presence.{conversationId}', function (User $user, $conversationId) {
    return $user;
});

