<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkerJobController extends Controller
{
    public function index(Request $request): View
    {
        $professional = $this->professional($request);
        $jobs = Booking::with(['company', 'unit'])->where('professional_id', $professional->id)
            ->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")
            ->orderBy('scheduled_at')->paginate(10);

        return view('worker.jobs.index', compact('professional', 'jobs'));
    }

    public function show(Request $request, Booking $booking): View
    {
        $professional = $this->professional($request);
        abort_unless($booking->professional_id === $professional->id, 403);
        $booking->load(['company', 'unit']);
        $checklist = config('workflows.'.$booking->service_type, config('workflows.maintenance'));

        return view('worker.jobs.show', compact('professional', 'booking', 'checklist'));
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $professional = $this->professional($request);
        abort_unless($booking->professional_id === $professional->id, 403);
        $checklist = config('workflows.'.$booking->service_type, config('workflows.maintenance'));
        $data = $request->validate([
            'completed_items' => ['nullable', 'array'],
            'completed_items.*' => ['string', Rule::in(array_keys($checklist))],
            'action' => ['nullable', Rule::in(['save', 'hold', 'resume', 'complete'])],
            'hold_reason' => ['nullable', Rule::in(['wet_surface', 'waiting_parts', 'no_access', 'safety_issue', 'customer_request', 'other'])],
            'hold_note' => ['nullable', 'string', 'max:500'],
        ]);
        $completed = array_values(array_unique($data['completed_items'] ?? []));
        $progress = (int) round((count($completed) / max(count($checklist), 1)) * 100);
        $updates = ['completed_items' => $completed, 'progress' => $progress];
        $action = $data['action'] ?? 'save';

        if ($action === 'hold') {
            abort_unless(filled($data['hold_reason'] ?? null), 422, 'Choose why the job is on hold.');
            $updates += ['status' => 'on_hold', 'hold_reason' => $data['hold_reason'], 'hold_note' => $data['hold_note'] ?? null];
        } elseif ($action === 'resume') {
            $updates += ['status' => 'in_progress', 'hold_reason' => null, 'hold_note' => null, 'work_started_at' => $booking->work_started_at ?? now()];
        } elseif ($action === 'complete') {
            abort_unless($progress === 100, 422, 'Complete every checklist item before closing the job.');
            $updates += ['status' => 'completed', 'work_completed_at' => now(), 'hold_reason' => null, 'hold_note' => null];
        } elseif (! in_array($booking->status, ['on_hold', 'completed'], true)) {
            $updates += ['status' => 'in_progress', 'work_started_at' => $booking->work_started_at ?? now()];
        }

        $booking->update($updates);

        return back()->with('status', $action === 'hold' ? 'Job saved on hold.' : ($action === 'complete' ? 'Job completed.' : 'Progress saved.'));
    }

    private function professional(Request $request)
    {
        abort_unless($request->user()?->role === 'professional', 403);
        $professional = $request->user()->professional;
        abort_unless($professional, 403);

        return $professional;
    }
}
