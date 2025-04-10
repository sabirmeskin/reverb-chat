<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Message;
use Livewire\Attributes\On;
use App\Models\Conversation;

new class extends Component {

    public $users = [];
    public $conversations = [];
    public $conversation ;
    public $presence ;



    public function loadConversations()
    {


        // Load conversations for the authenticated user
    $this->conversations = auth()->user()->conversations()
        ->with(['participants', 'lastMessage'])
        ->orderByDesc(function ($query) {
            $query->select('created_at')
                  ->from('messages')
                  ->whereColumn('conversation_id', 'conversations.id')
                  ->latest()
                  ->limit(1);
        })
        ->get();
}



    // public function getListeners()
    // {
    //     $listeners = [];
    //     foreach ($this->conversations as $conversation) {
    //         $listeners["echo-private:conversation.{$conversation->id},MessageSendEvent"] = 'refreshList';
    //     }
    //     return $listeners;
    // }

    public function mount()
    {
        $this->loadConversations();
        $this->dispatch('conversationUpdated');
    }


    public function setConversation($conversationId)
    {
        $this->conversation = auth()->user()->conversations()->find($conversationId);
        // dd($this->conversation->participants);

        $this->dispatch('conversationSelected',$conversationId);
    }

    #[On('conversationStarted')]
    public function handleConversationStarted($conversationId)
        {
            $this->setConversation($conversationId);
            $this->loadConversations();
        }


      #[On('conversationUpdated')]
      public function refreshList()
    {
        // dd('refresh');
        $this->loadConversations();

    }

    public function userLoggedIn(){
        $this->presence = 'success';
    }

    public function userLoggedOut(){
        $this->presence = 'danger';

    }

    public function getListeners()
    {
        $listeners = [];
        foreach ($this->conversations as $conversation) {
            $listeners["echo-private:conversation.{$conversation->id},MessageSendEvent"] = 'refreshList';
            $listeners["echo-presence:chat,joining"] = 'userLoggedIn';
            $listeners["echo-presence:chat,leaving"] = 'userLoggedOut';
        }
        return $listeners;

    }


};

?>

<div class="flex h-full  flex-row gap-3" >
    <div class=" border-r border-gray-300 pr-2 dark:border-gray-700">

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
                                {{ Str::limit($convo->lastMessage->body ?? 'No messages yet', 20) }}
                            </p>
                        </div>
                        <span class="text-xs text-muted-foreground">
                            {{ optional($convo->lastMessage)->created_at ?
                            $convo->lastMessage->created_at->diffForHumans() : '' }}
                        </span>
                    </div>
                </flux:navlist.item>
                @endif
                @dd($convo->lastMessage->created_at)

                @endforeach

            </flux:navlist.group>
            <flux:navlist.group heading="Contacts" expandable>
                @foreach ($conversations as $convo)
                @if (!$convo->isGroup())
                <flux:navlist.item icon="user" iconDot="{{$presence}}"  badge-color="green" >

                    <div class="flex items-center space-x-3 cursor-pointer" wire:click="setConversation({{ $convo->id }})">
                        <div class="flex-1">
                            <h3 class="font-semibold text-foreground">{{$convo->participants()->where('user_id','!=',auth()->id())->first()->name}}</h3>
                            <p class="text-sm text-muted-foreground truncate font-thin " >
                                {{ Str::limit($convo->lastMessage->body ?? 'No messages yet', 20) }}
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


</div>

{{-- <script type="module">
    Echo.join('presence.chat')
.here(users => {
    console.log('Users online:', users);
})
.joining(user => {
    console.log(user.name + ' joined');
})
.leaving(user => {
    console.log(user.name + ' left');
})
.listen('.user.logged-in', (e) => {
    console.log('Login Event:', e.user.name);
})
.listen('.user.logged-out', (e) => {
    console.log('Logout Event:', e.user.name);
});
</script> --}}
