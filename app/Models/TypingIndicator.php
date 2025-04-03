<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypingIndicator extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
