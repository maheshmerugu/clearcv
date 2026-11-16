@extends('layouts.app')

@section('title', 'My resumes')

@section('content')
    <section class="page-heading">
        <div>
            <span class="eyebrow">Your workspace</span>
            <h1>Good to see you, {{ auth()->user()->name }}.</h1>
            <p>Upload a text-based PDF to get a readable, actionable resume check.</p>
        </div>
    </section>

    <div class="dashboard-grid">
        <section class="panel upload-panel">
            <div class="panel-heading">
                <span class="step-badge">01</span>
                <div><h2>Check a resume</h2><p>PDF only · Up to 10 MB</p></div>
            </div>
            <form method="POST" action="{{ route('resumes.store') }}" enctype="multipart/form-data" class="form-stack">
                @csrf
                <label for="resume">Choose your resume</label>
                <input id="resume" name="resume" type="file" accept="application/pdf,.pdf" required>
                @error('resume')<p class="field-error">{{ $message }}</p>@enderror
                <label for="job_description">Target job description <span class="optional">(optional)</span></label>
                <textarea id="job_description" name="job_description" rows="6" maxlength="20000" placeholder="Paste a job description to check for relevant terms...">{{ old('job_description') }}</textarea>
                @error('job_description')<p class="field-error">{{ $message }}</p>@enderror
                <p class="field-hint">Only relevant terms are helpful. The checker will not recommend adding skills you do not have.</p>
                <button class="button button-full" type="submit">Analyze resume <span aria-hidden="true">→</span></button>
            </form>
            <div class="privacy-note"><span aria-hidden="true">🔒</span><span>Files stay private to your account until you delete them. No resume data is sent to an AI service.</span></div>
        </section>

        <section class="resume-list">
            <div class="list-heading"><div><span class="eyebrow">Your history</span><h2>Saved checks</h2></div><span class="count-pill">{{ $resumes->count() }}</span></div>
            @forelse ($resumes as $resume)
                <article class="resume-card">
                    <div class="resume-icon" aria-hidden="true">PDF</div>
                    <div class="resume-info">
                        <a class="resume-title" href="{{ route('resumes.show', $resume) }}">{{ $resume->original_name }}</a>
                        <span class="muted">{{ $resume->created_at->format('M j, Y') }} · {{ $resume->job_description ? 'Job match included' : 'General check' }}</span>
                    </div>
                    <div class="list-score"><strong>{{ $resume->ats_score }}</strong><span>/100</span></div>
                    <div class="resume-card-actions">
                        <a class="text-link" href="{{ route('resumes.show', $resume) }}">View <span aria-hidden="true">→</span></a>
                        <form method="POST" action="{{ route('resumes.destroy', $resume) }}" class="resume-delete-form" onsubmit="return confirm('Delete this resume and its report? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button class="delete-link" type="submit" aria-label="Delete {{ $resume->original_name }}">Delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="empty-state">
                    <div class="empty-icon" aria-hidden="true">✦</div>
                    <h3>Your first check starts here</h3>
                    <p>Upload a resume PDF to get a score, practical suggestions, and a clean copy you can edit.</p>
                </div>
            @endforelse
        </section>
    </div>
    <p class="score-note">This score reflects ClearCV's checks only. It cannot predict or guarantee an employer's ATS result.</p>
@endsection
