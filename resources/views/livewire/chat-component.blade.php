<?php

use App\Events\MessageSendEvent;
use App\Models\User;
use App\Models\Message;
use Livewire\Volt\Component;

new class extends Component {

    public $user;
    public $messages = [];
    public $message = '';
    public $showDropdown = false;

    public $receiver_id;
    public $sender_id;
    public function mount($user){

        $this->sender_id = Auth::user()->id;
        $this->receiver_id = $user;
        $this->user = User::find($user);

        $messages = Message::where(function($query){
            $query->where('sender_id', $this->sender_id)
            ->where('receiver_id',$this->receiver_id);
        })->orWhere(function($query){
            $query->where('sender_id', $this->receiver_id)
            ->where('receiver_id',$this->sender_id);
        })->with('sender:id,name', 'receiver:id,name')->get();

        foreach ($messages as $message) {

            $this->chatMessage($message);
        }
    }

    public function chatMessage($message){
        $this->messages[] = [
            'id' => $message->id,
            'message' =>$message->message,
            'sender' => $message->sender->name,
            'receiver' => $message->receiver->name
        ];
    }
    public function toggleDropdown() {
        $this->showDropdown = !$this->showDropdown;
    }

    public function sendMessage() {
        // dd($this->message);
        if (!empty($this->message)) {
            $message = Message::create(
                [
                    "sender_id" => $this->sender_id,
                    "receiver_id" => $this->receiver_id,
                    "message" =>$this->message
                ]
        );
        $this->message = '';
        $this->chatMessage($message);

        broadcast(new MessageSendEvent($message))->toOthers();
        }
    }
    public function getListeners()
    {
        return [
            "echo-private:chat-channel.{$this->sender_id},MessageSendEvent" => 'listenForMessage',
        ];
    }
    public function listenForMessage($event){
        // dd($event);
        $chatMessage = Message::whereid($event['message']['id'])
        ->with('sender:id,name', 'receiver:id,name')->first();
        $this->chatMessage($chatMessage);
    }

}; ?>

<div>
    <div class="w-full h-full flex flex-col bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded overflow-hidden">
        <!-- Chat Header -->
        <div class="border-b border-gray-300 flex flex-row justify-between items-center px-3 py-2 bg-gray-100 dark:bg-gray-700">
            <span class="font-semibold text-gray-800 dark:text-gray-200">{{$user->name}}</span>

<div class="">
    <div
        x-data="{
            open: false,
            toggle() {
                if (this.open) {
                    return this.close()
                }

                this.$refs.button.focus()

                this.open = true
            },
            close(focusAfter) {
                if (! this.open) return

                this.open = false

                focusAfter && focusAfter.focus()
            }
        }"
        x-on:keydown.escape.prevent.stop="close($refs.button)"
        x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
        x-id="['dropdown-button']"
        class="relative"
    >
        <!-- Button -->
        <button
            x-ref="button"
            x-on:click="toggle()"
            :aria-expanded="open"
            :aria-controls="$id('dropdown-button')"
            type="button"
            class="relative flex items-center whitespace-nowrap justify-center gap-2 py-2 rounded-lg shadow-sm bg-white hover:bg-gray-50 text-gray-800 border border-gray-200 hover:border-gray-200 px-4"
        >
            <span></span>

            <!-- Heroicon: micro chevron-down -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                <path fill-rule="evenodd" d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
            </svg>
        </button>

        <!-- Panel -->
        <div
            x-ref="panel"
            x-show="open"
            x-transition.origin.top.right
            x-on:click.outside="close($refs.button)"
            :id="$id('dropdown-button')"
            x-cloak
            class=" min-w-48 rounded-lg shadow-sm mt-2 z-10 origin-bottom-right bg-white p-1.5 outline-none border border-gray-200 mr-2"
        >
            <a href="{{ route('dashboard') }}" class="px-2 lg:py-1.5 py-2  flex items-center rounded-md transition-colors text-left text-gray-800 hover:bg-gray-50 focus-visible:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                retour
            </a>

        </div>
    </div>
</div>
        </div>

        <!-- Chat Messages -->
        <div id="chatBox" class="flex-1 p-4 text-sm flex flex-col gap-y-1 ">
            @foreach($messages as $message)
                @if ($message['sender'] != auth()->user()->name)
                    <div class="flex justify-start">
                        <div class="p-2 rounded-md bg-gray-200 dark:bg-gray-700 w-1/2">
                            <span class="dark:text-white">{{ $message['message'] }}</span>
                        </div>
                    </div>
                @else
                    <div class="flex justify-end">
                        <div class="p-2 rounded-md bg-blue-400 dark:bg-gray-700 w-1/2">
                            <span class="dark:text-white">{{ $message['message'] }}</span>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Chat Input -->
        <div class="p-3 border-t border-gray-300 dark:border-gray-700 bg-gray-100 dark:bg-gray-700">
            <form wire:submit.prevent="sendMessage()" class="flex gap-2">
                <flux:input wire:model="message" name="message" id="message" class="block w-full" placeholder="Type a message..." />
                <flux:button type="submit">Send</flux:button>
            </form>
        </div>
    </div>
</div>
