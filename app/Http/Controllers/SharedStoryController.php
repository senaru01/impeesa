<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\View\View;

/**
 * Cerita Bersama: semua cerita publik dari member, bisa dibaca semua member yang login.
 */
class SharedStoryController extends Controller
{
    public function index(): View
    {
        return view('member.stories.shared', [
            'stories' => Story::public()->with('user')->latest()->paginate(18),
        ]);
    }
}
