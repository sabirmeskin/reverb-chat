<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
new class extends Component {
    public $conversation;


   #[On('conversationSelected')]
   public function conversationSelected($conversationId){
        $this->conversation = auth()->user()->conversations()->find($conversationId);
        // $this->dispatch('scroll-bottom');

    }
}; ?>

<div>
        <div class="flex flex-row gap-3">
        <livewire:chat.sidebar />
        <div class="w-full ">
            @if (isset($conversation))

            <div wire:loading.flex class="flex items-center justify-center h-full">
                <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            </div>
            <div wire:loading.remove>
                <livewire:chat.chat-box :conversation-id="$conversation->id" :key="$conversation->id" />
            </div>
            @else
            <div class="flex items-center justify-center h-full text-muted-foreground">
                Choisissez un contact pour démarrer la conversation
            </div>
            @endif
        </div>


        </div>

</div>
