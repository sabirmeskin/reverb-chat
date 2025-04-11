<?php

use Livewire\Volt\Component;

new class extends Component {
    public $media;

    public function mount($media)
    {
        $this->media = $media;
    }


}; ?>

<div>
    @if ($media->mime_type === 'image')
        <a href="{{ $media->getUrl() }}">
            <img src="{{ $media->getUrl() }}" alt="Media" class="w-32 h-32 rounded-lg">
        </a>
    @elseif ($media->mime_type === 'video')
        <video controls class="w-32 h-32 rounded-lg">
            <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
            Your browser does not support the video tag.
        </video>
    @elseif ($media->mime_type === 'audio')
        <audio controls class="w-full">
            <source src="{{ $media->getUrl() }}" type="{{ $media->mime_type }}">
            Your browser does not support the audio element.
        </audio>
    @elseif ($media->mime_type === 'application/pdf' || $media->mime_type === 'document')
        <a href="{{ $media->getUrl() }}" class="text-blue-500 underline" target="_blank">
            {{ Str::limit($media->name, 30) }}.({{ $media->extension }})
        </a>
    @elseif ($media->mime_type === 'text/plain')
        <a href="{{ $media->getUrl() }}" class="text-blue-500 underline" target="_blank">
            {{ Str::limit($media->name, 30) }}.({{ $media->extension }})
        </a>
    @elseif ($media->mime_type === 'text/html')
        <a href="{{ $media->getUrl() }}" class="text-blue-500 underline" target="_blank">
            {{ Str::limit($media->name, 30) }}.({{ $media->extension }})
        </a>
    @elseif ($media->mime_type === 'text/csv')
        <a href="{{ $media->getUrl() }}" class="text-blue-500 underline" target="_blank">
            {{ Str::limit($media->name, 30) }}.({{ $media->extension }})
        </a>

        @else
        <p>Unsupported media type</p>
    @endif
</div>
