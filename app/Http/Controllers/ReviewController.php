<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile', [
            'review' => $request->user()->review,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:80'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'content' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $request->user()->review()->updateOrCreate([], $validated);

        return redirect()->route('profile')->with('status', 'Your review is now visible on the home page.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->review()->delete();

        return redirect()->route('profile')->with('status', 'Your review was removed from the home page.');
    }
}
