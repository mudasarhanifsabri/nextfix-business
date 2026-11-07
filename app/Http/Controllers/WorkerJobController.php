<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\JobPhoto;
use App\Models\User;
use App\Notifications\AdminAlert;
use App\Support\WorkChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        $booking->load(['company', 'unit', 'issuePhotos', 'inspectionPhotos', 'workPhotos']);
        $checklist = WorkChecklist::for($booking);

        return view('worker.jobs.show', compact('professional', 'booking', 'checklist'));
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        $professional = $this->professional($request);
        abort_unless($booking->professional_id === $professional->id, 403);
        abort_if($booking->status === 'completed', 422, 'Completed jobs are locked and cannot be changed.');
        $booking->loadMissing('unit');
        $checklist = WorkChecklist::for($booking);
        $data = $request->validate([
            'completed_items' => ['nullable', 'array'],
            'completed_items.*' => ['string', Rule::in(array_keys($checklist))],
            'action' => ['nullable', Rule::in(['inspection', 'estimate', 'save', 'photo', 'hold', 'resume', 'complete'])],
            'inspection_condition' => ['nullable', Rule::in(['good', 'attention', 'urgent'])],
            'inspection_issues' => ['nullable', 'array'],
            'inspection_issues.*' => ['string', Rule::in(['water_leak', 'electrical', 'ac', 'damage', 'hygiene', 'access', 'safety', 'other'])],
            'inspection_report' => ['nullable', 'string', 'max:2000'],
            'labour_hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'labour_cost' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'parts_cost' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'parts_required' => ['nullable', 'string', 'max:2000'],
            'estimate_note' => ['nullable', 'string', 'max:2000'],
            'inspection_photos' => ['nullable', 'array', 'max:8'],
            'inspection_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'work_photo_category' => ['nullable', Rule::in(['before', 'progress', 'after'])],
            'work_photos' => ['nullable', 'array', 'max:8'],
            'work_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'hold_reason' => ['nullable', Rule::in(['wet_surface', 'waiting_parts', 'no_access', 'safety_issue', 'customer_request', 'other'])],
            'hold_note' => ['nullable', 'string', 'max:500'],
        ]);
        $action = $data['action'] ?? 'save';
        $completed = $action === 'photo' ? ($booking->completed_items ?? []) : array_values(array_unique($data['completed_items'] ?? []));
        $progress = (int) round((count($completed) / max(count($checklist), 1)) * 100);
        $updates = $action === 'photo' ? [] : ['completed_items' => $completed, 'progress' => $progress];

        if (in_array($action, ['save', 'photo', 'hold', 'resume', 'complete'], true)) {
            abort_unless(in_array($booking->workflow_stage, ['work', 'complete'], true), 422, 'Work is locked until the estimate is approved.');
        }

        if ($action === 'inspection') {
            abort_unless(in_array($booking->workflow_stage ?: 'inspection', ['inspection', 'inspect'], true), 422, 'The inspection is already submitted and locked.');
            $request->validate(['inspection_condition' => ['required'], 'inspection_report' => ['required', 'string', 'max:2000']]);
            $updates += ['workflow_stage' => 'estimate', 'inspection_condition' => $data['inspection_condition'], 'inspection_issues' => $data['inspection_issues'] ?? [], 'inspection_report' => $data['inspection_report']];
            foreach ($request->file('inspection_photos', []) as $photo) {
                $path = $photo->store('job-evidence/'.$booking->id, 'public');
                JobPhoto::create(['booking_id' => $booking->id, 'professional_id' => $professional->id, 'path' => $path, 'original_name' => $photo->getClientOriginalName(), 'mime_type' => $photo->getMimeType(), 'size' => $photo->getSize(), 'category' => 'inspection', 'captured_at' => now()]);
            }
        } elseif ($action === 'estimate') {
            abort_unless($booking->workflow_stage === 'estimate', 422, 'Complete the inspection before submitting an estimate.');
            $request->validate(['labour_hours' => ['required', 'numeric', 'min:0'], 'labour_cost' => ['required', 'numeric', 'min:0'], 'parts_cost' => ['required', 'numeric', 'min:0']]);
            $updates += ['workflow_stage' => 'awaiting_customer_approval', 'status' => 'awaiting_customer_approval', 'labour_hours' => $data['labour_hours'], 'labour_amount_minor' => (int) round($data['labour_cost'] * 100), 'parts_amount_minor' => (int) round($data['parts_cost'] * 100), 'parts_required' => $data['parts_required'] ?? null, 'estimate_note' => $data['estimate_note'] ?? null, 'estimate_submitted_at' => now(), 'quotation_sent_at' => now()];
        } elseif ($action === 'photo') {
            $request->validate(['work_photo_category' => ['required'], 'work_photos' => ['required', 'array', 'min:1', 'max:8']]);
            foreach ($request->file('work_photos', []) as $photo) {
                $path = $photo->store('job-evidence/'.$booking->id, 'public');
                JobPhoto::create(['booking_id' => $booking->id, 'professional_id' => $professional->id, 'path' => $path, 'original_name' => $photo->getClientOriginalName(), 'mime_type' => $photo->getMimeType(), 'size' => $photo->getSize(), 'category' => $data['work_photo_category'], 'captured_at' => now()]);
            }
        } elseif ($action === 'hold') {
            abort_unless(filled($data['hold_reason'] ?? null), 422, 'Choose why the job is on hold.');
            $updates += ['status' => 'on_hold', 'hold_reason' => $data['hold_reason'], 'hold_note' => $data['hold_note'] ?? null];
        } elseif ($action === 'resume') {
            $updates += ['status' => 'in_progress', 'hold_reason' => null, 'hold_note' => null, 'work_started_at' => $booking->work_started_at ?? now()];
        } elseif ($action === 'complete') {
            abort_unless($progress === 100, 422, 'Complete every checklist item before closing the job.');
            if ($booking->service_type === 'cleaning') {
                abort_unless($booking->photos()->where('category', 'before')->exists(), 422, 'Add a before-cleaning photo before closing the job.');
            }
            abort_unless(
                $booking->photos()->where('category', 'after')->exists(),
                422,
                'Add at least one after-work photo before closing the job.'
            );
            $updates += ['status' => 'completed', 'work_completed_at' => now(), 'hold_reason' => null, 'hold_note' => null];
        } elseif (! in_array($booking->status, ['on_hold', 'completed'], true)) {
            $updates += ['status' => 'in_progress', 'work_started_at' => $booking->work_started_at ?? now()];
        }

        $booking->update($updates);

        if ($action === 'estimate') {
            foreach ($booking->company->users as $manager) {
                $manager->notifyNow(new AdminAlert(
                    'Quotation ready for approval',
                    $booking->reference.' is ready. Review the inspection, fee credit and remaining amount.',
                    route('bookings.index').'#quotation-'.$booking->id,
                    'warning'
                ), ['database']);
            }
            User::where('role', 'nextfix_admin')->get()->each->notifyNow(new AdminAlert(
                'Quotation sent to company',
                $booking->reference.' quotation was submitted by '.$professional->name.' and sent to '.$booking->company->name.'.',
                route('admin.operations.index'),
                'info'
            ), ['database']);
        }

        if (in_array($action, ['hold', 'complete'], true)) {
            $title = $action === 'hold' ? 'Job placed on hold' : 'Job completed';
            $detail = $action === 'hold'
                ? $booking->reference.' paused: '.str_replace('_', ' ', (string) $booking->hold_reason).'.'
                : $booking->reference.' was completed by '.$professional->name.'.';
            foreach ($booking->company->users as $manager) $manager->notifyNow(new AdminAlert($title, $detail, route('bookings.index'), $action === 'hold' ? 'warning' : 'success'), ['database']);
            User::where('role', 'nextfix_admin')->get()->each->notifyNow(new AdminAlert($title, $detail, route('admin.operations.index'), $action === 'hold' ? 'warning' : 'success'), ['database']);
        }

        $message = match ($action) {
            'inspection' => 'Inspection saved. Add the estimate next.',
            'estimate' => 'Quotation sent to the company for approval.',
            'photo' => 'After-work proof saved.',
            'hold' => 'Job saved on hold.',
            'complete' => 'Job completed.',
            default => 'Progress saved.',
        };
        return back()->with('status', $message);
    }

    public function evidence(Request $request, JobPhoto $jobPhoto): BinaryFileResponse
    {
        $jobPhoto->load('booking');
        $user = $request->user();
        $allowed = $user?->role === 'nextfix_admin'
            || ($user?->role === 'company_manager' && $user->company_id === $jobPhoto->booking->company_id)
            || ($user?->role === 'professional' && $user->professional?->id === $jobPhoto->booking->professional_id);
        abort_unless($allowed, 403);
        abort_unless(Storage::disk('public')->exists($jobPhoto->path), 404);
        return response()->file(Storage::disk('public')->path($jobPhoto->path), ['Cache-Control' => 'private, max-age=3600']);
    }

    private function professional(Request $request)
    {
        abort_unless($request->user()?->role === 'professional', 403);
        $professional = $request->user()->professional;
        abort_unless($professional, 403);

        return $professional;
    }
}
