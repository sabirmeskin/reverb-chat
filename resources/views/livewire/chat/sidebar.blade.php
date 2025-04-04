<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Message;
use Livewire\Attributes\On;

new class extends Component {

    public $users = [];
    public $conversations = [];
    public $conversation ;

    public function loadConversations()
    {
        $this->conversations = auth()->user()->conversations()
        ->with(['participants', 'lastMessage'])
        ->get();
    }


    public function mount()
    {
        $this->loadConversations();

    }


    public function setConversation($conversationId)
    {
        $this->conversation = auth()->user()->conversations()->find($conversationId);
        // dd($this->conversation->participants);
    }

    #[On('conversationStarted')]
    public function handleConversationStarted($conversationId)
        {
            $this->setConversation($conversationId);
            $this->loadConversations();
        }


    #[On('messageSent')]
    public function handleMessageSent()
        {
            $this->loadConversations();
          $this->dispatch('scroll-bottom');

        }


};

?>

<div class="flex h-full w-full flex-row gap-3">
    <div class="w-80 bg-card border-r border-gray-300 pr-2 dark:border-gray-700">

        <div class="p-4"
        x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
        @scroll-bottom.window="$nextTick(() => {
            $el.scrollTop = $el.scrollHeight;
        })"
        >
            <div class="flex flex-row w-full items-center justify-center space-x-5 ">
                <flux:modal.trigger name="contacts">
                    <flux:button icon="message-square">Contacts</flux:button>
                </flux:modal.trigger>

                @livewire('chat.partials.group-modal')
                @livewire('chat.partials.contacts-model')
                <flux:modal.trigger name="group">
                    <flux:button icon="user">Groupe</flux:button>
                </flux:modal.trigger>

            </div>
        </div>
        <flux:separator />
        <flux:navlist class="w-full" class="overflow-y-auto h-[calc(100vh-200px)]">
            <flux:navlist.group heading="Groupes" expandable :expanded="false">
                @foreach ($conversations as $convo)
                @if ($convo->isGroup())
                <flux:navlist.item icon="users"   badge-color="green" >
                    <div class="flex items-center space-x-3 cursor-pointer" wire:click="setConversation({{ $convo->id }})">
                        <div class="flex-1">
                            <h3 class="font-semibold text-foreground">{{$convo->name}}</h3>
                            <p class="text-xs text-muted-foreground truncate font-thin">
                                {{ $convo->lastMessage->body ?? 'No messages yet' }}
                            </p>
                        </div>
                        <span class="text-xs text-muted-foreground">
                            {{ optional($convo->lastMessage)->created_at ?
                            $convo->lastMessage->created_at->diffForHumans() : '' }}
                        </span>

                    </div>
                </flux:navlist.item>
                @endif

                @endforeach

            </flux:navlist.group>
            <flux:navlist.group heading="Contacts" expandable>
                @foreach ($conversations as $convo)
                @if (!$convo->isGroup())
                <flux:navlist.item icon="user" iconDot="success"  badge-color="green" >
                    <div class="flex items-center space-x-3 cursor-pointer" wire:click="setConversation({{ $convo->id }})">
                        <div class="flex-1">
                            <h3 class="font-semibold text-foreground">{{$convo->participants()->where('user_id','!=',auth()->id())->first()->name}}</h3>
                            <p class="text-sm text-muted-foreground truncate font-thin">
                                {{ $convo->lastMessage->body ?? 'No messages yet' }}
                            </p>
                        </div>
                        <span class="text-xs text-muted-foreground font-thin">
                            {{ optional($convo->lastMessage)->created_at ?
                            $convo->lastMessage->created_at->diffForHumans() : '' }}
                        </span>

                    </div>
                </flux:navlist.item>
                @endif
                @endforeach
            </flux:navlist.group>

        </flux:navlist>
        <flux:separator />

        {{-- <div class="overflow-y-auto h-[calc(100vh-200px)]">
            <!-- Contact List -->
            @foreach ($users as $contact)
            <div class="cursor-pointer hover:bg-gray-100 p-3 dark:hover:bg-gray-700"
                wire:click="setUser({{ $contact->id }})">
                <div class="flex items-center space-x-3">
                    <img src="" alt="{{ $contact->name }}" class="w-10 h-10 rounded-full object-cover">

                    <div class="flex-1">
                        <h3 class="font-semibold text-foreground">{{ $contact->name }}</h3>
                        <p class="text-sm text-muted-foreground truncate">
                            {{ $contact->latestMessage->body ?? 'No messages yet' }}
                        </p>
                    </div>
                    <span class="text-xs text-muted-foreground">
                        {{ optional($contact->latestMessage)->created_at ?
                        $contact->latestMessage->created_at->diffForHumans() : '' }}
                    </span>
                </div>
            </div>
            <flux:separator />

            @endforeach

        </div> --}}

        <div class="p-4 ">
            <div class="flex space-x-2.5 flex-wrap space-y-2">



                <flux:button icon="settings" href="{{ route('settings.appearance') }}">

                </flux:button>


                <flux:button variant="danger" icon="log-out" href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">

                </flux:button>

                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </div>
        </div>
    </div>

    <!-- Dynamically render chat-box only if a user is selected -->
    <div class="flex-1">
        @if ($conversation)
        <div wire:loading.flex class="flex items-center justify-center h-full">
            <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
        </div>
        <div wire:loading.remove>
            @livewire('chat.chat-box', ['conversationId' => $conversation->id], key($conversation->id))
        </div>
        @else
        <div class="flex items-center justify-center h-full text-muted-foreground">
            Choisissez un contact pour démarrer la conversation
        </div>
        @endif
    </div>
</div>
