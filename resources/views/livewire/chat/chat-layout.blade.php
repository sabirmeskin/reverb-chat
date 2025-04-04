<?php

use Livewire\Volt\Component;

new class extends Component {

            public function mount($conversationId)
        {

        }
}; 
?>

<div>
    <div class="flex h-full w-full  flex-row gap-3 ">
        @livewire('chat/sidebar' )
        @livewire('chat/chat-box' )
    </div>
</div>
