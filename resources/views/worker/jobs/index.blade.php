@extends('layouts.worker', ['title' => 'Jobs | NextFix Work', 'screenTitle' => 'Jobs'])
@section('content')
<nav class="native-segmented" aria-label="Job list"><a class="{{ $view === 'active' ? 'active' : '' }}" href="{{ route('worker.jobs.index') }}">Active <span>{{ $activeCount }}</span></a><a class="{{ $view === 'completed' ? 'active' : '' }}" href="{{ route('worker.jobs.index', ['view' => 'completed']) }}">Completed <span>{{ $completedCount }}</span></a></nav>
<div class="native-section-title"><div><small>MY SCHEDULE</small><h1>{{ $view === 'completed' ? 'Completed jobs' : 'Active jobs' }}</h1></div><span class="worker-online"><i></i> Online</span></div>
<div class="native-job-list">@forelse($jobs as $job)@include('worker.jobs._card', ['job' => $job])@empty<section class="native-empty-state"><i class="bi {{ $view === 'completed' ? 'bi-clock-history' : 'bi-check2-circle' }}"></i><h3>{{ $view === 'completed' ? 'No completed jobs yet' : 'No active jobs' }}</h3><p>{{ $view === 'completed' ? 'Completed service records will appear here.' : 'New assignments will appear here and in Alerts.' }}</p></section>@endforelse</div>
@if($jobs->hasPages())<div class="worker-pager">{{ $jobs->links() }}</div>@endif
@endsection
