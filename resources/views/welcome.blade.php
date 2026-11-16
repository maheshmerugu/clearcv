@extends('layouts.app')

@section('title', 'ATS Resume Checker')

@section('content')
    <section class="hero">
        <div class="hero-copy">
            <span class="eyebrow">A clearer path to your next opportunity</span>
            <h1>Make your resume easier to read, and easier to improve.</h1>
            <p>Tailor your resume to a job description, review relevant terms, and work toward 100/100 on this app’s checks—without inventing skills or experience.</p>
            <div class="hero-actions">
                @auth
                    <a class="button" href="{{ route('dashboard') }}">Check a resume <span aria-hidden="true">→</span></a>
                @else
                    <a class="button" href="{{ route('register') }}">Get started <span aria-hidden="true">→</span></a>
                    <a class="button button-secondary" href="{{ route('login') }}">Sign in</a>
                @endauth
            </div>
            <p class="fine-print">Your resume stays in your private account. This checker provides guidance, not an employer ATS prediction.</p>
        </div>
        <div class="hero-art" aria-hidden="true">
            <div class="preview-card">
                <div class="preview-top"><span></span><span></span><span></span><b>Resume check</b></div>
                <div class="preview-score"><strong>82</strong><span>/ 100<br>readability score</span></div>
                <div class="preview-line preview-line-long"></div>
                <div class="preview-line"></div>
                <div class="preview-line preview-line-short"></div>
                <div class="preview-result"><span class="status-dot"></span> Your experience section is easy to find</div>
                <div class="preview-result preview-result-muted"><span class="status-dot status-dot-warn"></span> Add a target role for keyword matching</div>
            </div>
            <div class="decor decor-one"></div>
            <div class="decor decor-two"></div>
        </div>
    </section>

    <section class="feature-grid" aria-label="How it works">
        <article class="feature-card"><span class="feature-number">01</span><h2>Upload a PDF</h2><p>We extract readable text from your resume and keep the original private.</p></article>
        <article class="feature-card"><span class="feature-number">02</span><h2>Tailor to a job</h2><p>Compare your resume with a job description and add only skills you confirm are accurate.</p></article>
        <article class="feature-card"><span class="feature-number">03</span><h2>Make it yours</h2><p>Edit a clean copy, preserve your facts, and download a simple PDF when you are ready.</p></article>
    </section>

    <section class="reviews-section" aria-labelledby="reviews-title">
        <div class="reviews-heading">
            <div>
                <span class="eyebrow">Member feedback</span>
                <h2 id="reviews-title">Reviews from ClearCV users</h2>
                @if ($reviewCount > 0)
                    <p class="muted">{{ number_format($averageRating, 1) }} out of 5 · {{ $reviewCount }} {{ \Illuminate\Support\Str::plural('review', $reviewCount) }}</p>
                @else
                    <p class="muted">Reviews submitted by members appear here.</p>
                @endif
            </div>
            @auth
                <a class="button button-secondary" href="{{ route('profile') }}">{{ auth()->user()->review ? 'Edit your review' : 'Write a review' }}</a>
            @else
                <a class="button button-secondary" href="{{ route('register') }}">Join to review</a>
            @endauth
        </div>
        @if ($reviews->isNotEmpty())
            <div class="review-grid">
                @foreach ($reviews as $review)
                    <article class="review-card">
                        <div class="review-stars" role="img" aria-label="{{ $review->rating }} out of 5 stars">
                            @for ($star = 1; $star <= 5; $star++)
                                <span class="{{ $star <= $review->rating ? 'star-filled' : 'star-empty' }}" aria-hidden="true">★</span>
                            @endfor
                        </div>
                        <blockquote>{{ $review->content }}</blockquote>
                        <p class="review-author">{{ $review->display_name }}</p>
                    </article>
                @endforeach
            </div>
        @else
            <div class="review-empty">No member reviews yet. Share an honest review from your profile after creating an account.</div>
        @endif
    </section>

    <section class="disclaimer">
        <strong>About the score</strong>
        <p>Scores are based on transparent checks in this app. A 100/100 means the resume passed these checks; different employers and ATS platforms use different criteria, so it cannot guarantee a particular result.</p>
    </section>
@endsection
