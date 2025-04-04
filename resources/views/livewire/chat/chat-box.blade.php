<?php
use App\Events\MessageSendEvent;
use App\Events\TypingEvent;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;
use App\Models\Message;
use App\Models\TypingIndicator;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use App\Models\Conversation;

new class extends Component {
    use WithFileUploads;

    public $user;
    public $messages = [];
    public $message = '';
    public $conversation;
    public $receiver_id;
    public $sender_id;
    public $typingIndicator = null;
    public $typingTimeout = null;
    public $istyping = false;
    public $files = [];

    public function mount($conversationId)
{
    $conversation = Conversation::find($conversationId);
    $receiver = $conversation->participants()
        ->where('user_id', '!=', auth()->id())
        ->first();
    $this->sender_id = auth()->id();
    $this->receiver_id = $receiver->id;
    $this->conversation = $conversation;

    if ($this->conversation) {
        $this->messages = $this->conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();
            // ->map(function ($message) {
            //     return $this->formatMessage($message);
            // })->toArray();
    }
    $this->checkTypingStatus();

}
public function save()
    {
        if (empty($this->files)) {
            session()->flash('error', 'Veuillez sélectionner un fichier à télécharger.');
            return;
        }
        foreach ($this->files as $file) {
            $msg = Message::create([
                'conversation_id' => $this->conversation->id,
                'sender_id' => $this->sender_id,
                'receiver_id' => $this->receiver_id,
                'body' => ' ',
                'type' => 'media',
            ]);
            $msg->addMedia($file)->toMediaCollection('chat');
            $this->dispatch('messageSent');
            $this->files = [];
            $this->message = '';
        }
    }

public function sendMessage()
{

    if (!auth()->check()) {
        session()->flash('error', 'Vous devez être connecté pour envoyer un message.');
        return;
    }

    if (empty(trim($this->message)) && empty($this->files)) {
      
        session()->flash('error', 'Le message ne peut pas être vide.');
        return;
    }

    try {
        $newMessage = Message::create([
            "conversation_id" => $this->conversation->id, 
            "sender_id"       => $this->sender_id ?? auth()->id(),
            "receiver_id"     => $this->receiver_id,
            "body"            => trim($this->message),
            "type"            => "text",
        ]);
        if (!empty($this->files)) {
            $newMessage->update([
                'type' => 'media',
            ]);
            foreach ($this->files as $file) {
            $newMessage->addMedia($file)->withResponsiveImages()->toMediaCollection('chat');
        }
        }
        
        $this->files = [];
        $this->message = '';

        $this->chatMessage($newMessage);
        broadcast(new MessageSendEvent($newMessage))->toOthers();
        $this->stopTyping();
    } catch (\Exception $e) {
        session()->flash('error', 'Une erreur est survenue lors de l\'envoi du message.');
    }
}

public function startTyping()
    {
        // Update typing_at timestamp
        TypingIndicator::updateOrCreate(
            [
                'conversation_id' => $this->conversation->id,
                'user_id' => $this->sender_id,
            ],
            [
                'typing_at' => now(),
            ]
        );

        // Broadcast typing event
        broadcast(new TypingEvent(
            $this->conversation->id,
            $this->sender_id,
            true
        ))->toOthers();

        // Set timeout to automatically stop typing after 3 seconds
        if ($this->typingTimeout) {
            $this->typingTimeout = null;
        }


    }

    public function stopTyping()
    {
        // Clear the typing_at timestamp
        TypingIndicator::where('conversation_id', $this->conversation->id)
            ->where('user_id', $this->sender_id)
            ->update(['typing_at' => null]);

        // Broadcast stop typing event
        broadcast(new TypingEvent(
            $this->conversation->id,
            $this->sender_id,
            false
        ))->toOthers();

        // Clear timeout
        if ($this->typingTimeout) {
            $this->typingTimeout = null;
        }
    }

    public function checkTypingStatus()
    {
        // Check if receiver is typing (typing_at within last 3 seconds)
        $typing = TypingIndicator::where('conversation_id', $this->conversation->id)
            ->where('user_id', $this->receiver_id)
            ->where('typing_at', '>=', now()->subSeconds(3))
            ->first();

        $this->typingIndicator = $typing
            ? User::find($this->receiver_id)->name . ' écrit...'
            : null;
    }

private function formatMessage($message)
{
    return [
        'id' => $message->id,
        'body' => $message->body,
        'type' => $message->type,
        'sender_id' => $message->sender_id,
        'receiver_id' => $message->receiver_id,
        'created_at' => $message->created_at->format('Y-m-d H:i:s'),
    ];
}

    public function chatMessage($message){

        $this->messages[] = $message;
    }

    public function getListeners()
    {
        return [
            "echo-private:conversation.{$this->conversation->id},TypingEvent" => 'listenForTyping',
            "echo-private:conversation.{$this->conversation->id},MessageSendEvent" => 'listenForMessage',
        ];
    }

    public function listenForTyping($event)
    {
        // dd($event);
        if ($event['userId'] != $this->sender_id) {
            if ($event['isTyping']) {
                // User started typing - show indicator for 3 seconds
                $this->typingIndicator = User::find($event['userId'])->name . ' écrit...';
                $this->dispatch('typingUpdated');

                $this->dispatch('startTypingTimeout');


            } else {
                // User stopped typing - hide immediately
                $this->typingIndicator = null;
                $this->dispatch('typingUpdated');
            }
        }
    }



    public function listenForMessage($event){
        // dd($event);
        $chatMessage = Message::whereid($event['id'])
        ->with('sender:id,name', 'receiver:id,name')->first();
        $this->chatMessage($chatMessage);

    }

};
?>

<div class="flex flex-col w-full">

    <div class="p-4  bg-card flex items-center justify-between pb-3">

        <div class="flex items-center space-x-3">
            <img src="" class="w-10 h-10 rounded-full object-cover" alt="Contact">
            @if ($conversation->isGroup())
            <div>
                <h2 class="font-semibold text-foreground">{{ $conversation->name }}</h2>

            </div>
            @endif
            @if (!$conversation->isGroup())
            <div>
                <h2 class="font-semibold text-foreground">{{ $conversation->participants()->where('user_id','!=',
                    auth()->id())->first()->name }}</h2>
                <p class="text-sm text-green">Online</p>
            </div>
            @endif
        </div>

        <flux:dropdown>
            <flux:button icon="ellipsis-vertical"></flux:button>

            <flux:menu>
                @if ($conversation->isGroup())
                {{-- <flux:menu.item icon="pencil" x-on:click="$flux.modal('editGroup').show()">Edit Group
                </flux:menu.item> --}}
                <flux:menu.item>
                    <flux:modal.trigger name="editGroup">
                        <flux:button icon="user">Groupe</flux:button>
                    </flux:modal.trigger>
                </flux:menu.item>

                <flux:menu.item icon="user">View Group Members</flux:menu.item>
                @endif

                <flux:menu.item icon="plus">Ajouter Membre</flux:menu.item>

                <flux:menu.separator />

                {{-- <flux:menu.submenu heading="Sort by">
                    <flux:menu.radio.group>
                        <flux:menu.radio checked>Name</flux:menu.radio>
                        <flux:menu.radio>Date</flux:menu.radio>
                        <flux:menu.radio>Popularity</flux:menu.radio>
                    </flux:menu.radio.group>
                </flux:menu.submenu>

                <flux:menu.submenu heading="Filter">
                    <flux:menu.checkbox checked>Draft</flux:menu.checkbox>
                    <flux:menu.checkbox checked>Published</flux:menu.checkbox>
                    <flux:menu.checkbox>Archived</flux:menu.checkbox>
                </flux:menu.submenu> --}}

                {{--
                <flux:menu.separator /> --}}

                <flux:menu.item variant="danger" icon="trash">Supprimer</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>
    <flux:separator />
    <!-- Messages Area -->
    <div class="overflow-y-scroll p-4 space-y-4 bg-background h-[calc(100vh-200px)]"
        x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
        @foreach ($messages as $msg)
        @if ($msg['sender_id'] == $sender_id)
        <!-- Sent Message -->
        <div class="flex items-start justify-end space-x-2">
            <div class="bg-blue-300 rounded-lg p-3 max-w-md">
                <p class="text-primary-foreground">{{ $msg['body'] }}</p>
                @if ($msg['type'] == 'media')
                <a href="{{$msg->getFirstMediaUrl('chat') }}">
                    <img src="{{$msg->getFirstMediaUrl('chat') }}" alt="Image" class="w-32 h-32 rounded-lg">
                </a>
                @endif

                {{-- @if($media = $msg->getFirstMedia('chat'))
                <img src="{{ $media->getUrl() }}" 
                    alt="{{ $media->name }}"
                    class="w-32 h-32 rounded-lg">
                <div class="text-xs text-gray-500">
                    Actual path: {{ $media->getPath() }}
                </div>
                @endif --}}
                <span class="text-xs text-primary-foreground/80 mt-1 block">{{
                    \Carbon\Carbon::parse($msg['created_at'])->format('h:i A') }}
                </span>
            </div>
        </div>
        @else
        <!-- Received Message -->
        <div class="flex items-start space-x-2">
            <img src="" class="w-8 h-8 rounded-full object-cover" alt="Contact">
            <div class="bg-gray-200 dark:bg-gray-500 rounded-lg p-3 max-w-md">
                <p class="text-foreground">{{ $msg['body'] }}</p>
                @if ($msg['type'] == 'media')
                <a href="{{$msg->getFirstMediaUrl('chat') }}">
                    <img src="{{$msg->getFirstMediaUrl('chat') }}" alt="Image" class="w-32 h-32 rounded-lg">
                </a>
                @endif
                <span class="text-xs text-muted-foreground mt-1 block">{{
                    \Carbon\Carbon::parse($msg['created_at'])->format('h:i A') }}</span>
            </div>
        </div>
        @endif
        @endforeach
        <!-- Typing Indicator -->
        @if ($typingIndicator)
<div class="flex items-start space-x-2">
    <img src="" class="w-8 h-8 rounded-full object-cover" alt="Contact">
    <div class="bg-gray-200 dark:bg-gray-500 rounded-lg p-3 max-w-md">
        <p class="text-foreground italic">{{ $typingIndicator }}</p>
    </div>
</div>
@endif

    </div>

    <!-- Message Input -->
    <flux:separator />
    <div class="p-4  bg-card">
        <div class="flex items-center space-x-3">

            <flux:button icon="paperclip" class="p-2">
                <input wire:model="files" multiple type="file" class=""/>
            </flux:button>
            
            <input type="text" placeholder="Typer un message..." wire:model="message" wire:keydown.enter="sendMessage()" wire:keydown="startTyping()"  wire:keydown.debounce.2000ms="stopTyping()"
                class="flex-1 p-2 rounded-lg bg-muted text-foreground focus:outline-none focus:ring-2 dark:border-gray-700 border-gray-200 border focus:ring-primary">
            <flux:button icon="send" class="" wire:click="sendMessage()"></flux:button>
        </div>
    </div>
    @livewire('chat.partials.edit-group-modal')
</div>
