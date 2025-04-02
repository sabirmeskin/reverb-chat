<?php
use App\Events\MessageSendEvent;

use Livewire\Volt\Component;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use App\Models\Conversation;

new class extends Component {
    public $user;
    public $messages = [];
    public $message = '';
    public $conversation;
    public $receiver_id;
    public $sender_id;


    public function mount($conversationId)
{
    $conversation = Conversation::find($conversationId);
    // dd($conversation);
    $user = $conversation->participants()
        ->where('user_id', '!=', auth()->id())
        ->first();
    // dd($user);
    $this->sender_id = auth()->id();
    $this->receiver_id = $user->id;
    // dd(auth()->user()->conversations());
    // Find or create a conversation
    $this->conversation = $conversation;

    if ($this->conversation) {
        $this->messages = $this->conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return $this->formatMessage($message);
            })->toArray();
    }

}

public function sendMessage()
{
    if (!auth()->check()) {
        session()->flash('error', 'Vous devez être connecté pour envoyer un message.');
        return;
    }

    if (empty(trim($this->message))) {
        session()->flash('error', 'Le message ne peut pas être vide.');
        return;
    }

    try {
        $newMessage = Message::create([
            "conversation_id" => $this->conversation->id, // ✅ Link message to the conversation
            "sender_id"       => $this->sender_id ?? auth()->id(),
            "receiver_id"     => null, // No need to specify a single receiver in group chats
            "body"            => trim($this->message),
            "type"            => "text",
        ]);

        $this->message = '';

        $this->chatMessage($newMessage);

        // Diffuser l'événement en temps réel
        broadcast(new MessageSendEvent($newMessage))->toOthers();
    } catch (\Exception $e) {
        session()->flash('error', 'Une erreur est survenue lors de l\'envoi du message.');
    }
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

        $this->messages[] = [
            'id' => $message->id,
            'body' =>$message->body,
            'type' => $message->type,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'created_at' => $message->created_at,
            'updated_at' => $message->updated_at
        ];
    }

        public function getListeners()
    {
        return [
            "echo-private:chat-channel.{$this->conversation->id},MessageSendEvent" => 'listenForMessage',
        ];
    }

    public function listenForMessage($event){
        // dd($event);
        $chatMessage = Message::whereid($event['message']['id'])
        ->with('sender:id,name', 'receiver:id,name')->first();
        $this->chatMessage($chatMessage);
    }



};
?>

<div class="flex flex-col w-full">
    {{--
    <!-- Chat Header -->@dd($messages) --}}
    <div class="p-4 border-b border-border bg-card flex items-center justify-between pb-3">
        <div class="flex items-center space-x-3">
            <img src="" class="w-10 h-10 rounded-full object-cover" alt="Contact">
            <div>
                <h2 class="font-semibold text-foreground">{{ $conversation->name }}</h2>
                <p class="text-sm text-green">Online</p>
            </div>
        </div>

        <flux:dropdown>
            <flux:button icon="ellipsis-vertical"></flux:button>

            <flux:menu>
                <flux:menu.item icon="plus">New post</flux:menu.item>

                <flux:menu.separator />

                <flux:menu.submenu heading="Sort by">
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
                </flux:menu.submenu>

                <flux:menu.separator />

                <flux:menu.item variant="danger" icon="trash">Delete</flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>

    <!-- Messages Area -->
    <div class="overflow-y-scroll p-4 space-y-4 bg-background h-[calc(100vh-200px)]"
        x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"

        >
        @foreach ($messages as $msg)
        @if ($msg['sender_id'] == $sender_id)
        <!-- Sent Message -->
        <div class="flex items-start justify-end space-x-2">
            <div class="bg-blue-300 rounded-lg p-3 max-w-md">
                <p class="text-primary-foreground">{{ $msg['body'] }}</p>
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
                <span class="text-xs text-muted-foreground mt-1 block">{{
                    \Carbon\Carbon::parse($msg['created_at'])->format('h:i A') }}</span>
            </div>
        </div>
        @endif
        @endforeach

    </div>

    <!-- Message Input -->
    <div class="p-4 border-t border-border bg-card" >
        <div class="flex items-center space-x-3">

            <flux:button icon="paperclip" class="p-2"></flux:button>

            <input type="text" placeholder="Type a message..." wire:model="message" wire:keydown.enter="sendMessage"
                class="flex-1 p-2 rounded-lg bg-muted text-foreground focus:outline-none focus:ring-2 dark:border-gray-700 border-gray-200 border focus:ring-primary">
            <flux:button icon="send" class="" wire:click="sendMessage()"></flux:button>
        </div>
    </div>
</div>
