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
    public $conversationId;
    public $error = "";

    public function mount($conversationId)
    {
        $this->loadConversation($conversationId);
    }

    public function updateUsers()
    {
        $this->users = User::where(function ($query) {
    $query->where('name', 'like', '%' . $this->search . '%')
          ->orWhereIn('id', $this->selectedUsers);
})
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

    public function updateConversation()
    {
        $authUser = auth()->user();

        if (count($this->selectedUsers) == 0) {
            $this->addError('noUsers', 'Veuillez sélectionner au moins un utilisateur.');
            return;
        }

        $this->error = "";

        $conversation = Conversation::findOrFail($this->conversationId);

        // Update group name
        $conversation->update([
            'name' => $this->groupName,
        ]);

        // Sync participants
        $participants = array_merge(
            $this->selectedUsers,
            [$authUser->id] // Ensure the current user is always a participant
        );

        $conversation->participants()->sync($participants);

        $this->dispatch('conversationUpdated');

        $this->dispatch('closeModal', 'group');
        // Reset fields
        // $this->selectedUsers = [];
        // $this->groupName = '';

    }

    public function loadConversation($conversationId)
{
    $this->conversationId = $conversationId;
    $conversation = Conversation::findOrFail($conversationId);

    $this->groupName = $conversation->name;
    $this->selectedUsers = $conversation->participants()->pluck('user_id')->toArray();

    $this->updateUsers();
}



};

?>

<div>
    <flux:modal name="editGroup" class="w-96">
        <div class="p-4">
            <flux:heading size="lg">Modifier la Conversation</flux:heading>

            <!-- Group Name Input -->
            <flux:field class="my-4">
                <flux:label>Groupe</flux:label>
                <flux:description>Nom du conversation groupe :</flux:description>
                <flux:input
                    type="text"
                    placeholder="Nom du groupe"
                    class="w-full"
                    wire:model.defer="groupName"
                    autocomplete="off" />
                <flux:error name="username" />
            </flux:field>

            <flux:input
                icon="search"
                type="text"
                placeholder="Rechercher un utilisateur"
                class="mt-4 w-full"
                wire:model.defer="search"
                autocomplete="off"
                wire:keyup="updateUsers" />

            <flux:separator class="mt-4 mb-4" variant="subtle" />
            <flux:error name="noUsers" />

            <!-- Selected Users -->
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($selectedUsers as $userId)
                    @php
                        $user = User::find($userId);
                    @endphp
                    @if ($user)
                        <div class="bg-gray-200 px-2 py-1 rounded-full flex items-center dark:bg-gray-700">
                            <img src="" class="w-6 h-6 rounded-full object-cover mr-2" alt="User">
                            {{ $user->name }}
                            <flux:button size="xs" variant="subtle" icon="x" class="ml-2 rounded-full" wire:click="toggleUserSelection({{ $user->id }})"></flux:button>
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

            <!-- Update Conversation Button -->
            <flux:button
                class="mt-4 w-full"
                variant="primary"
                wire:click="updateConversation()">
                Mettre à jour la conversation
            </flux:button>
        </div>
    </flux:modal>

    @script
    <script>
        $wire.on('closeModal', () => {
            $flux.modal('editGroup').close();
        });
    </script>
    @endscript
</div>
