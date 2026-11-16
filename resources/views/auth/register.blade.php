@extends('layouts.app')

@section('title', 'Create account')

@section('content')
    <section class="auth-wrap">
        <div class="auth-card">
            <span class="eyebrow">Start with a stronger first impression</span>
            <h1>Create your account</h1>
            <p class="muted">Your resume checks and saved copies are private to you.</p>
            <form method="POST" action="{{ route('register.store') }}" class="form-stack">
                @csrf
                <label for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus>
                @error('name')<p class="field-error">{{ $message }}</p>@enderror
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
                <span class="field-hint">Use at least 8 characters.</span>
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                <button class="button button-full" type="submit">Create account</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
        </div>
    </section>
@endsection
