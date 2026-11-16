<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('welcome', [
            'reviews' => Review::query()->latest()->limit(6)->get(),
            'reviewCount' => Review::count(),
            'averageRating' => Review::avg('rating') ?? 0,
        ]);
    }
}
