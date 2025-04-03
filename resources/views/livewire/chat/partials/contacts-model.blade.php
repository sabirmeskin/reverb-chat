<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
new class extends Component {
    public $users ;
    public $search = '';
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
            'name' => 'conv',
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

    $this->dispatch('conversationStarted', $conversation->id);

}

    public function search(){
        $this->users = User::where('name', 'like', '%'.$this->search.'%')->get()->except(auth()->id());
    }

}; ?>

<div>
    <flux:modal name="contacts" class="w-96">
        <div>
            <flux:heading size="lg">Conversation Privée</flux:heading>
            {{-- <flux:text class="mt-2">Choisissez les utilisateurs :</flux:text> --}}
        </div>
        <div>

            <flux:input
                type="text"
                placeholder="Rechercher un utilisateur"
                class="mt-2 mb-4"
                wire:model.debounce.500ms="search()" />
            <flux:separator class="mt-2 mb-4" variant="subtle" />
        </div>
    <ul>
        @foreach($users as $user)
            <li wire:click='startConversation({{ $user->id }})' x-on:click="$flux.modal('contacts').close()">{{ $user->name }}</li>
        @endforeach
    </ul>
    </flux:modal>
</div>
