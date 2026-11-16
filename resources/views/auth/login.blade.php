@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
    <section class="auth-wrap">
        <div class="auth-card">
            <span class="eyebrow">Welcome back</span>
            <h1>Sign in to ClearCV</h1>
            <p class="muted">Pick up where you left off and review your resumes.</p>
            <form method="POST" action="{{ route('login.store') }}" class="form-stack">
                @csrf
                <label for="email">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                @error('email')<p class="field-error">{{ $message }}</p>@enderror
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
                <label class="checkbox-label"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                <button class="button button-full" type="submit">Sign in</button>
            </form>
            <p class="auth-switch">New here? <a href="{{ route('register') }}">Create an account</a></p>
        </div>
    </section>
@endsection
