@extends('layouts.app')

@section('title', 'My profile')

@section('content')
    <a class="back-link" href="{{ route('dashboard') }}">← Back to my resumes</a>
    <section class="panel profile-panel">
        <span class="eyebrow">Your profile</span>
        <h1>Share your ClearCV review</h1>
        <p class="muted">Your review and chosen display name appear publicly on the home page as soon as you save. Do not include private information.</p>

        <form method="POST" action="{{ route('profile.review.store') }}" class="form-stack">
            @csrf
            <label for="display_name">Display name</label>
            <input id="display_name" name="display_name" type="text" maxlength="80" value="{{ old('display_name', $review?->display_name ?? auth()->user()->name) }}" required>
            @error('display_name')<p class="field-error">{{ $message }}</p>@enderror

            <label for="rating">Your star rating</label>
            <select id="rating" name="rating" required>
                @foreach (range(5, 1) as $rating)
                    <option value="{{ $rating }}" @selected((int) old('rating', $review?->rating ?? 5) === $rating)>{{ str_repeat('★', $rating) }}{{ str_repeat('☆', 5 - $rating) }} — {{ $rating }} out of 5</option>
                @endforeach
            </select>
            @error('rating')<p class="field-error">{{ $message }}</p>@enderror

            <label for="content">Your review</label>
            <textarea id="content" name="content" rows="5" minlength="10" maxlength="1000" required>{{ old('content', $review?->content) }}</textarea>
            @error('content')<p class="field-error">{{ $message }}</p>@enderror

            <button class="button button-full" type="submit">{{ $review ? 'Update public review' : 'Publish review' }}</button>
        </form>

        @if ($review)
            <form method="POST" action="{{ route('profile.review.destroy') }}" class="review-delete-form" onsubmit="return confirm('Remove your public review?');">
                @csrf
                @method('DELETE')
                <button class="delete-button" type="submit">Remove my review</button>
            </form>
        @endif
    </section>
@endsection
