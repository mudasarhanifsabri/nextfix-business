<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingMessage;
use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BookingChatController extends Controller
{
    public function show(Request $request, Booking $booking): View
    {
        $this->authorizeBooking($request, $booking);
        $booking->load(['company', 'unit', 'professional.user']);

        return view('chat.show', [
            'booking' => $booking,
            'messages' => $this->messageQuery($booking)->latest('id')->limit(60)->get()->reverse()->values(),
        ]);
    }

    public function feed(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($request, $booking);
        $after = max(0, $request->integer('after'));
        $messages = $this->messageQuery($booking)->where('id', '>', $after)->limit(100)->get();
        $this->markRead($request, $messages);

        return response()->json(['messages' => $messages->map(fn (BookingMessage $message) => $this->messageData($message, $request->user()->id))]);
    }

    public function store(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeBooking($request, $booking);
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,mp3,m4a,mp4,webm,ogg,wav', 'max:15360'],
        ]);
        abort_unless(filled($data['body'] ?? null) || $request->hasFile('attachment'), 422, 'Write a message or add an attachment.');

        $file = $request->file('attachment');
        $type = 'text';
        $path = null;
        if ($file) {
            $type = str_starts_with((string) $file->getMimeType(), 'image/') ? 'image'
                : (str_starts_with((string) $file->getMimeType(), 'audio/') ? 'audio' : 'file');
            $path = $file->store('booking-chat/'.$booking->id, 'local');
        }
        $message = BookingMessage::create([
            'booking_id' => $booking->id,
            'user_id' => $request->user()->id,
            'type' => $type,
            'body' => trim((string) ($data['body'] ?? '')) ?: null,
            'path' => $path,
            'original_name' => $file?->getClientOriginalName(),
            'mime_type' => $file?->getMimeType(),
            'size' => $file?->getSize(),
        ]);
        $message->load('user');
        $message->readers()->syncWithoutDetaching([$request->user()->id => ['read_at' => now()]]);
        $this->notifyParticipants($booking, $request->user(), $message);

        return response()->json(['message' => $this->messageData($message, $request->user()->id)], 201);
    }

    public function file(Request $request, BookingMessage $message): BinaryFileResponse
    {
        $message->load('booking');
        $this->authorizeBooking($request, $message->booking);
        abort_unless($message->path && Storage::disk('local')->exists($message->path), 404);

        $safeName = str_replace(['"', "\r", "\n"], '', basename($message->original_name ?: 'attachment'));
        return response()->file(Storage::disk('local')->path($message->path), [
            'Content-Type' => $message->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$safeName.'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    private function messageQuery(Booking $booking)
    {
        return $booking->messages()->with('user:id,name,role')->orderBy('id');
    }

    private function markRead(Request $request, $messages): void
    {
        $rows = $messages->where('user_id', '!=', $request->user()->id)->map(fn ($message) => [
            'booking_message_id' => $message->id, 'user_id' => $request->user()->id, 'read_at' => now(),
        ])->all();
        if ($rows) DB::table('booking_message_reads')->upsert($rows, ['booking_message_id', 'user_id'], ['read_at']);
    }

    private function messageData(BookingMessage $message, int $viewerId): array
    {
        return [
            'id' => $message->id,
            'mine' => $message->user_id === $viewerId,
            'sender' => $message->user->name,
            'sender_role' => match ($message->user->role) {
                'professional' => 'NextFix worker', 'nextfix_admin' => 'NextFix support', default => 'Company',
            },
            'type' => $message->type,
            'body' => $message->body,
            'file_url' => $message->path ? route('chat.file', $message) : null,
            'file_name' => $message->original_name,
            'file_size' => $message->size,
            'time' => $message->created_at->timezone('Asia/Dubai')->format('g:i A'),
            'date' => $message->created_at->timezone('Asia/Dubai')->format('d M Y'),
        ];
    }

    private function notifyParticipants(Booking $booking, User $sender, BookingMessage $message): void
    {
        $participants = User::query()
            ->where('id', '!=', $sender->id)
            ->where(function ($query) use ($booking): void {
                $query->where('role', 'nextfix_admin')
                    ->orWhere(fn ($company) => $company->where('role', 'company_manager')->where('company_id', $booking->company_id));
                if ($booking->professional?->user_id) $query->orWhere('id', $booking->professional->user_id);
            })->get();
        $preview = $message->body ?: match ($message->type) {'image' => 'Sent a photo', 'audio' => 'Sent a voice message', default => 'Sent an attachment'};
        foreach ($participants as $participant) {
            $participant->notifyNow(new AdminAlert(
                $sender->name.' · '.$booking->reference,
                (string) str($preview)->limit(100),
                route('chat.show', $booking),
                'info'
            ), ['database']);
        }
    }

    private function authorizeBooking(Request $request, Booking $booking): void
    {
        $user = $request->user();
        $allowed = $user?->role === 'nextfix_admin'
            || ($user?->role === 'company_manager' && $user->company_id === $booking->company_id)
            || ($user?->role === 'professional' && $user->professional?->id === $booking->professional_id);
        abort_unless($allowed, 403);
    }
}
