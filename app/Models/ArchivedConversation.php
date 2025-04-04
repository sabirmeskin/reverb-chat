<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivedConversation extends Model
{
    protected $fillable = [
        'user_id',
        'conversation_id',
        'archived_at',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
    public function scopeUnarchived($query)
    {
        return $query->whereNull('archived_at');
    }
    public function scopeArchivedByUser($query, $userId)
    {
        return $query->where('user_id', $userId)->archived();
    }
    public function scopeUnarchivedByUser($query, $userId)
    {
        return $query->where('user_id', $userId)->unarchived();
    }
    public function scopeArchivedConversations($query, $userId)
    {
        return $query->where('user_id', $userId)->archived();
    }
    public function scopeUnarchivedConversations($query, $userId)
    {
        return $query->where('user_id', $userId)->unarchived();
    }
    public function scopeArchivedConversationsCount($query, $userId)
    {
        return $query->where('user_id', $userId)->archived()->count();
    }
    public function scopeUnarchivedConversationsCount($query, $userId)
    {
        return $query->where('user_id', $userId)->unarchived()->count();
    }
    public function scopeArchivedConversationsWithCount($query, $userId)
    {
        return $query->where('user_id', $userId)->archived()->withCount('conversation');
    }
    public function scopeUnarchivedConversationsWithCount($query, $userId)
    {
        return $query->where('user_id', $userId)->unarchived()->withCount('conversation');
    }
}
