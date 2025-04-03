<?php
use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationParticipant;

new class extends Component {
    public $users = [];
    public $search = '';

    public function mount()
    {
        $this->updateUsers();
    }

    public function updateUsers()
    {
        $this->users = User::where('name', 'like', '%' . $this->search . '%')
            ->where('id', '!=', auth()->id())
            ->limit(10)
            ->get();
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

        // Emit event and close modal
        $this->dispatch('conversationStarted', $conversation->id);

    }
};?>

<div>
    <flux:modal name="contacts" class="w-96">
        <div class="p-4">
            <flux:heading size="lg">Conversation Privée</flux:heading>

            <flux:input
                type="text"
                placeholder="Rechercher un utilisateur"
                class="mt-4 w-full"
                wire:model.defer="search" autocomplete="off"
                x-on:keydown.enter="updateUsers"
                wire:keyup="updateUsers" />

            <flux:separator class="mt-4 mb-4" variant="subtle" />

            <div class="space-y-2 overflow-y-scroll">
                @foreach($users as $user)
                    <flux:button
                        wire:click='startConversation({{ $user->id }})'
                        x-on:click="$flux.modal('contacts').close()"
                        variant="ghost"
                        class="w-full text-left cursor-pointer">
                        <div class="flex flex-row"><img src="{{ $user->avatar }}" class="w-8 h-8 rounded-full object-cover mr-2" alt="Contact">
                            {{ $user->name }}</div>
                    </flux:button>
                @endforeach
            </div>
        </div>
    </flux:modal>
</div>




