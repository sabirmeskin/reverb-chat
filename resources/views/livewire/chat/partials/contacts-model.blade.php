<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
new class extends Component {
    public $users ;

    public function mount(){
        $this->users = User::all()->except(auth()->id());
    }

    public function startConversation($userId)
{
    $authUser = auth()->user();

    // Check if a conversation already exists
    $conversation = $authUser->conversations()->whereHas('participants', function ($query) use ($userId) {
        $query->where('user_id', $userId);
    })->first();

    if (!$conversation) {
        // Create a new conversation
        $conversation = Conversation::create([
            'name' => User::find($userId)->name,
            'type' => 'private',
        ]);

        // Add both users as participants
        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $authUser->id,
            'role' => 'member'
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'role' => 'member'
        ]);
    }

    $this->dispatch('conversationStarted', $userId);

}


}; ?>

<div>
    <flux:modal name="contacts" class="w-96">
        <div>
            <flux:heading size="lg">Conversation</flux:heading>
            <flux:text class="mt-2">Choisissez les utilisateurs .</flux:text>
        </div>
        <div>

            <div class="flex justify-between items-center">
                <flux:heading>Contacts</flux:heading>
            </div>
            <flux:separator class="mt-2 mb-4" variant="subtle" />
        </div>
    <ul>
        @foreach($users as $user)
            <li wire:click='startConversation({{ $user->id }})'>{{ $user->name }}</li>
        @endforeach
    </ul>
    </flux:modal>
</div>
