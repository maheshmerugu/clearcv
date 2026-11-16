@extends('layouts.app')

@section('title', 'ATS Resume Checker')

@section('content')
    <section class="hero">
        <div class="hero-copy">
            <span class="eyebrow">A clearer path to your next opportunity</span>
            <h1>Make your resume easier to read, and easier to improve.</h1>
            <p>Check how your resume reads as text, find common ATS issues, and create a clean PDF you can review before you apply.</p>
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
        <article class="feature-card"><span class="feature-number">02</span><h2>See what stands out</h2><p>Review section, contact-detail, and optional job-description checks with clear suggestions.</p></article>
        <article class="feature-card"><span class="feature-number">03</span><h2>Make it yours</h2><p>Edit a clean copy, preserve your facts, and download a simple PDF when you are ready.</p></article>
    </section>

    <section class="disclaimer">
        <strong>About the score</strong>
        <p>Scores are based on transparent checks in this app. Different employers and ATS platforms use different criteria, so a score—even 100/100—cannot guarantee a particular result.</p>
    </section>
@endsection
