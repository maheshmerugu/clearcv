<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ClearCV') · {{ config('app.name', 'ClearCV') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header">
        <div class="container header-content">
            <a class="brand" href="{{ route('home') }}"><span class="brand-mark">C</span> ClearCV</a>
            <nav class="main-nav" aria-label="Main navigation">
                @auth
                    <a href="{{ route('dashboard') }}">My resumes</a>
                    <a href="{{ route('profile') }}">My profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="nav-button" type="submit">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">Sign in</a>
                    <a class="button button-small" href="{{ route('register') }}">Create account</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="container main-content">
        @if (session('status'))
            <div class="notice notice-success" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">ClearCV offers practical resume guidance. No score can guarantee how an employer's ATS will rank an application.</div>
    </footer>
</body>
</html>
