@extends('layouts.app')

@section('title', 'Resume report')

@section('content')
    <a class="back-link" href="{{ route('dashboard') }}">← Back to my resumes</a>
    <section class="report-header">
        <div>
            <span class="eyebrow">Resume check</span>
            <h1>{{ $resume->original_name }}</h1>
            <p class="muted">Current score for your saved, edited copy · Updated {{ $resume->updated_at->format('F j, Y \a\t g:i A') }}</p>
        </div>
        <div class="score-ring" style="--score: {{ $resume->ats_score }}%;" role="img" aria-label="ClearCV score: {{ $resume->ats_score }} out of 100">
            <div><strong>{{ $resume->ats_score }}</strong><span>out of 100</span></div>
        </div>
    </section>

    <div class="report-grid">
        <section class="panel findings-panel">
            <div class="list-heading"><div><span class="eyebrow">Your results</span><h2>What we found</h2></div><span class="count-pill">{{ count($resume->findings) }} checks</span></div>
            <p class="muted report-intro">The “Matched X of Y” line is the optional job-keyword check, not your overall score; the overall score is shown above. Saving edits reruns the checks. A 100/100 score is possible only when the checks pass and the resume accurately reflects your experience, and it cannot guarantee an employer's ATS result.</p>
            <div class="finding-list">
                @foreach ($resume->findings as $finding)
                    <article class="finding finding-{{ $finding['severity'] }}">
                        <div class="finding-icon" aria-hidden="true">
                            @if ($finding['severity'] === 'success')
                                ✓
                            @elseif ($finding['severity'] === 'warning')
                                !
                            @else
                                i
                            @endif
                        </div>
                        <div>
                            <div class="finding-meta">
                                <span class="finding-category">{{ $finding['category'] }}</span>
                                <span class="finding-impact {{ $finding['impact'] < 0 ? 'impact-negative' : '' }}">{{ $finding['impact'] < 0 ? $finding['impact'].' points' : 'No deduction' }}</span>
                            </div>
                            <h3>{{ $finding['title'] }}</h3>
                            <p>{{ $finding['message'] }}</p>
                            @if ($finding['recommendation'])
                                <p class="recommendation"><strong>Suggestion:</strong> {{ $finding['recommendation'] }}</p>
                            @endif
                            @if ($finding['category'] === 'Job match' && !empty($finding['missing_terms']))
                                <form method="POST" action="{{ route('resumes.tailor', $resume) }}" class="form-stack">
                                    @csrf
                                    <fieldset>
                                        <legend>Select only terms that accurately describe skills or experience you genuinely have:</legend>
                                        @foreach ($finding['missing_terms'] as $term)
                                            <label>
                                                <input type="checkbox" name="confirmed_terms[]" value="{{ $term }}">
                                                {{ $term }}
                                            </label>
                                        @endforeach
                                    </fieldset>
                                    <label>
                                        <input type="checkbox" name="confirm_skills" value="1" required>
                                        I confirm the selected terms are accurate and appropriate for my Skills section.
                                    </label>
                                    @foreach ($errors->getMessages() as $key => $messages)
                                        @if (str_starts_with($key, 'confirmed_terms'))
                                            <p class="field-error">{{ $messages[0] }}</p>
                                        @endif
                                    @endforeach
                                    @error('confirm_skills')<p class="field-error">{{ $message }}</p>@enderror
                                    <button class="button button-secondary button-full" type="submit">Add confirmed terms to Skills &amp; recalculate</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <aside class="panel edit-panel">
            <span class="eyebrow">Your reviewed copy</span>
            <h2>Make it yours</h2>
            <p class="muted">We prepared a clean text copy. Edit it below, keep every detail truthful, then save and download your PDF.</p>
            <form method="POST" action="{{ route('resumes.update', $resume) }}" class="form-stack">
                @csrf
                @method('PUT')
                <label for="optimized_text">Resume content</label>
                <textarea id="optimized_text" name="optimized_text" rows="25" maxlength="100000" required>{{ old('optimized_text', $resume->optimized_text) }}</textarea>
                @error('optimized_text')<p class="field-error">{{ $message }}</p>@enderror
                <button class="button button-full" type="submit">Save edits &amp; recalculate score</button>
            </form>
            <a class="button button-secondary button-full download-button" href="{{ route('resumes.download', $resume) }}">Download optimized PDF</a>
            <p class="field-hint">The original PDF is kept separately. Saving this copy does not change it.</p>
            <form method="POST" action="{{ route('resumes.destroy', $resume) }}" onsubmit="return confirm('Delete this resume and its report? This cannot be undone.');" class="delete-form">
                @csrf
                @method('DELETE')
                <button class="delete-button" type="submit">Delete this resume and report</button>
            </form>
        </aside>
    </div>

    <details class="panel extracted-panel">
        <summary>View text extracted from the original PDF</summary>
        <pre>{{ $resume->extracted_text }}</pre>
    </details>
@endsection
