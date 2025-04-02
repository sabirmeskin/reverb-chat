<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Message extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();
    }
    protected $guarded = [];

    public function sender(){
        return $this->belongsTo(User::class);
    }

    public function receiver(){
        return $this->belongsTo(User::class);
    }
    public function conversation(){
        return $this->belongsTo(Conversation::class);
    }

    public function parent(){
        return $this->belongsTo(Message::class, 'parent_id');
    }

    public function replies(){
        return $this->hasMany(Message::class, 'parent_id');
    }

    public function markAsRead(User $user){
        $this->update('status', 'read');
    }

    public function markAsUnread(User $user){
        $this->update('status', 'unread');
    }

    public function markAsDelivered(User $user){
        $this->update(['status' => 'delivered' , 'delivered_at' => now()]);
    }



}
