<?php
use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ConversationParticipant;

new class extends Component {
    public $users = [];
    public $search = '';
    public $selectedUsers = [];
    public $groupName = '';

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

    public function toggleUserSelection($userId)
    {
        if (in_array($userId, $this->selectedUsers)) {
            $this->selectedUsers = array_diff($this->selectedUsers, [$userId]); // Remove user
        } else {
            $this->selectedUsers[] = $userId; // Add user
        }
    }

    public function startConversation()
    {
        $authUser = auth()->user();

        if (count($this->selectedUsers) == 1) {
            // Private conversation
            $userId = reset($this->selectedUsers);
            $conversation = $authUser->conversations()->whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->first();

            if (!$conversation) {
                $conversation = Conversation::create([
                    'name' => 'conv',
                    'type' => 'private',
                ]);

                ConversationParticipant::insert([
                    ['conversation_id' => $conversation->id, 'user_id' => $authUser->id, 'role' => 'member'],
                    ['conversation_id' => $conversation->id, 'user_id' => $userId, 'role' => 'member'],
                ]);
            }
        } else {
            // Group conversation
            if (empty($this->groupName)) {
                // Generate default group name from first 3 users
                $selectedUserNames = User::whereIn('id', $this->selectedUsers)->limit(3)->pluck('name')->toArray();
                $this->groupName = implode(', ', $selectedUserNames) . (count($this->selectedUsers) > 3 ? '...' : '');
            }

            $conversation = Conversation::create([
                'name' => $this->groupName,
                'type' => 'group',
            ]);

            $participants = array_map(fn($userId) => [
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'role' => 'member'
            ], $this->selectedUsers);

            $participants[] = [
                'conversation_id' => $conversation->id,
                'user_id' => $authUser->id,
                'role' => 'admin'
            ];

            ConversationParticipant::insert($participants);
        }

        $this->dispatch('conversationStarted', $conversation->id);
        $this->dispatch('closeModal');

        // Reset fields
        $this->selectedUsers = [];
        $this->groupName = '';
    }
};
?>

<div>
    <flux:modal name="group" class="w-96">
        <div class="p-4">
            <flux:heading size="lg">Nouvelle Conversation</flux:heading>

            <!-- Group Name Input -->
            <flux:field class="my-4">
    <flux:label>Groupe</flux:label>

    <flux:description>Nom du conversation groupe :</flux:description>

    <flux:input
                type="text"
                placeholder="Nom du groupe (facultatif)"
                class="w-full"
                wire:model.defer="groupName" autocomplete="off" />

    <flux:error name="username" />
</flux:field>


            <flux:input
                icon="search"
                type="text"
                placeholder="Rechercher un utilisateur"
                class="mt-4 w-full"
                wire:model.defer="search" autocomplete="off"
                wire:keyup="updateUsers" />

            <flux:separator class="mt-4 mb-4" variant="subtle" />

            <!-- Selected Users -->
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($selectedUsers as $userId)
                    @php
                        $user = User::find($userId);
                    @endphp
                    @if ($user)
                        <div class="bg-gray-200 px-2 py-1 rounded-full flex items-center dark:bg-gray-700">
                            <img src="{{ $user->avatar }}" class="w-6 h-6 rounded-full object-cover mr-2" alt="User">
                            {{ $user->name }}
                            <flux:button  size="xs" variant="subtle" icon="x" class="ml-2 rounded-full" wire:click="toggleUserSelection({{ $user->id }})" ></flux:button>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Users List -->
            <div class="space-y-2 overflow-y-scroll h-50">
                @foreach($users as $user)
                    <flux:button
                        wire:click="toggleUserSelection({{ $user->id }})"
                        variant="{{ in_array($user->id, $selectedUsers) ? 'filled' : 'ghost' }}"
                        class="w-full text-left cursor-pointer">
                        <div class="flex flex-row">
                            <img src="{{ $user->avatar }}" class="w-8 h-8 rounded-full object-cover mr-2" alt="Contact">
                            {{ $user->name }}
                        </div>
                    </flux:button>
                @endforeach
            </div>

            <!-- Start Conversation Button -->
            <flux:button

                class="mt-4 w-full"
                variant="primary"
                wire:click="startConversation()"
                x-on:click="$flux.modal('group').close()">
                Démarrer la conversation
            </flux:button>
        </div>
    </flux:modal>
</div>
