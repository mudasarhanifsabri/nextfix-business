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
            'action' => ['nullable', Rule::in(['inspection', 'estimate', 'save', 'hold', 'resume', 'complete'])],
            'inspection_condition' => ['nullable', Rule::in(['good', 'attention', 'urgent'])],
            'inspection_issues' => ['nullable', 'array'],
            'inspection_issues.*' => ['string', Rule::in(['water_leak', 'electrical', 'ac', 'damage', 'hygiene', 'access', 'safety', 'other'])],
            'inspection_report' => ['nullable', 'string', 'max:2000'],
            'labour_hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'labour_cost' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'parts_cost' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'parts_required' => ['nullable', 'string', 'max:2000'],
            'estimate_note' => ['nullable', 'string', 'max:2000'],
            'hold_reason' => ['nullable', Rule::in(['wet_surface', 'waiting_parts', 'no_access', 'safety_issue', 'customer_request', 'other'])],
            'hold_note' => ['nullable', 'string', 'max:500'],
        ]);
        $completed = array_values(array_unique($data['completed_items'] ?? []));
        $progress = (int) round((count($completed) / max(count($checklist), 1)) * 100);
        $updates = ['completed_items' => $completed, 'progress' => $progress];
        $action = $data['action'] ?? 'save';

        if (in_array($action, ['save', 'hold', 'resume', 'complete'], true)) {
            abort_unless(in_array($booking->workflow_stage, ['work', 'complete'], true), 422, 'Work is locked until the estimate is approved.');
        }

        if ($action === 'inspection') {
            $request->validate(['inspection_condition' => ['required'], 'inspection_report' => ['required', 'string', 'max:2000']]);
            $updates += ['workflow_stage' => 'estimate', 'inspection_condition' => $data['inspection_condition'], 'inspection_issues' => $data['inspection_issues'] ?? [], 'inspection_report' => $data['inspection_report']];
        } elseif ($action === 'estimate') {
            abort_unless($booking->workflow_stage === 'estimate', 422, 'Complete the inspection before submitting an estimate.');
            $request->validate(['labour_hours' => ['required', 'numeric', 'min:0'], 'labour_cost' => ['required', 'numeric', 'min:0'], 'parts_cost' => ['required', 'numeric', 'min:0']]);
            $updates += ['workflow_stage' => 'admin_review', 'status' => 'estimate_review', 'labour_hours' => $data['labour_hours'], 'labour_amount_minor' => (int) round($data['labour_cost'] * 100), 'parts_amount_minor' => (int) round($data['parts_cost'] * 100), 'parts_required' => $data['parts_required'] ?? null, 'estimate_note' => $data['estimate_note'] ?? null, 'estimate_submitted_at' => now()];
        } elseif ($action === 'hold') {
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

        $message = match ($action) {
            'inspection' => 'Inspection saved. Add the estimate next.',
            'estimate' => 'Estimate sent to NextFix for review.',
            'hold' => 'Job saved on hold.',
            'complete' => 'Job completed.',
            default => 'Progress saved.',
        };
        return back()->with('status', $message);
    }

    private function professional(Request $request)
    {
        abort_unless($request->user()?->role === 'professional', 403);
        $professional = $request->user()->professional;
        abort_unless($professional, 403);

        return $professional;
    }
}
