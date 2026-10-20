<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PublicStoryController extends Controller
{
    public function __invoke(Story $story): View
    {
        abort_unless(Gate::allows('view', $story), 404);

        $story->load('user');

        return view('stories.show', [
            'story' => $story,
            'others' => Story::featured()
                ->with('user')
                ->whereKeyNot($story->getKey())
                ->latest('featured_at')
                ->take(3)
                ->get(),
        ]);
    }
}
