<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\View\View;

class StoryController extends Controller
{
    public function index(): View
    {
        return view('admin.stories.index', [
            'stories' => Story::with('user')
                ->orderByRaw('featured_at is null')
                ->orderByDesc('is_public')
                ->latest()
                ->paginate(20),
        ]);
    }
}
