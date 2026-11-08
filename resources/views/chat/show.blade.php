@php
    $isWorker = auth()->user()->role === 'professional';
    $backUrl = $isWorker ? route('worker.jobs.show', $booking) : (auth()->user()->role === 'nextfix_admin' ? route('admin.operations.index') : route('bookings.index'));
@endphp
@extends($isWorker ? 'layouts.worker' : 'layouts.app', $isWorker ? ['title' => $booking->reference.' chat | NextFix', 'screenTitle' => 'Chat'] : ['heading' => 'Job chat', 'title' => $booking->reference.' chat | NextFix'])
@section('content')
<section class="booking-chat" data-chat-feed="{{ route('chat.feed', $booking) }}" data-chat-store="{{ route('chat.store', $booking) }}">
    <header class="chat-job-head">
        <a href="{{ $backUrl }}" aria-label="Back"><i class="bi bi-chevron-left"></i></a>
        <div><small>{{ $booking->reference }}</small><strong>{{ $booking->service_name }}</strong><span>{{ $booking->company->name }} · {{ $booking->unit->building }} / {{ $booking->unit->unit_number }}</span></div>
        <span class="chat-live"><i></i><b>Job chat</b><small>Live</small></span>
    </header>
    <section class="chat-stream" data-chat-stream aria-live="polite">
        @forelse($messages as $message)
            <article class="chat-message {{ $message->user_id === auth()->id() ? 'mine' : '' }}" data-message-id="{{ $message->id }}">
                <div class="chat-sender"><strong>{{ $message->user->name }}</strong><span>{{ match($message->user->role) {'professional' => 'NextFix worker', 'nextfix_admin' => 'NextFix support', default => 'Company'} }}</span></div>
                @php($messageType = $message->displayType())
                <div class="chat-bubble">
                    @if($messageType === 'image')
                        <a class="chat-image" href="{{ route('chat.file', $message) }}" target="_blank"><img src="{{ route('chat.file', $message) }}" alt="Photo shared by {{ $message->user->name }}"><span><i class="bi bi-arrows-fullscreen"></i></span></a>
                    @elseif($messageType === 'audio')
                        <div class="chat-audio" data-audio-card><button type="button" data-audio-play aria-label="Play voice message"><i class="bi bi-play-fill"></i></button><div><div class="chat-wave" data-audio-track>@for($i=0;$i<18;$i++)<i></i>@endfor<span data-audio-progress></span></div><small><b data-audio-time>0:00</b><em>Voice message</em></small></div><audio preload="metadata" src="{{ route('chat.file', $message) }}"></audio></div>
                    @elseif($message->type === 'file')
                        <a class="chat-file" href="{{ route('chat.file', $message) }}" target="_blank"><i class="bi bi-file-earmark-text"></i><span><strong>{{ $message->original_name }}</strong><small>{{ $message->size ? number_format($message->size / 1024).' KB' : 'Open attachment' }}</small></span><b><i class="bi bi-download"></i></b></a>
                    @endif
                    @if($message->body)<p>{{ $message->body }}</p>@endif
                    <time>{{ $message->created_at->timezone('Asia/Dubai')->format('g:i A') }}</time>
                </div>
            </article>
        @empty
            <div class="chat-empty" data-chat-empty><i class="bi bi-chat-heart"></i><strong>Start the job conversation</strong><span>Photos, voice notes and files stay with this booking.</span></div>
        @endforelse
    </section>
    <div class="chat-quick">@foreach(['I have arrived','Please provide access','Please call me','Work completed'] as $reply)<button type="button" data-quick-reply="{{ $reply }}">{{ $reply }}</button>@endforeach</div>
    <form class="chat-compose" data-chat-form enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="attachment_kind" value="">
        <label class="chat-attach" title="Add photo or file"><input type="file" name="attachment" accept="image/*,audio/*,.pdf"><i class="bi bi-plus-lg"></i></label>
        <textarea name="body" rows="1" maxlength="4000" placeholder="Write a message…"></textarea>
        <button type="button" class="chat-record" data-chat-record aria-label="Record voice message"><i class="bi bi-mic"></i><span hidden>0:00</span></button>
        <button type="submit" class="chat-send" aria-label="Send"><i class="bi bi-send-fill"></i></button>
        <div class="chat-upload" data-chat-upload hidden><div><i></i></div><span>Sending attachment…</span></div>
        <div class="chat-preview" data-chat-preview hidden></div>
        <p class="chat-error" data-chat-error hidden></p>
    </form>
</section>
@endsection
