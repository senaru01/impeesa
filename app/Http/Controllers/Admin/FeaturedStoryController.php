<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Menampilkan atau menurunkan cerita member dari halaman utama.
 */
class FeaturedStoryController extends Controller
{
    public function store(Story $story): RedirectResponse
    {
        Gate::authorize('feature', $story);

        $story->forceFill(['featured_at' => now()])->save();

        return back()->with('status', "\"{$story->title}\" sekarang tampil di halaman utama.");
    }

    public function destroy(Story $story): RedirectResponse
    {
        $story->forceFill(['featured_at' => null])->save();

        return back()->with('status', "\"{$story->title}\" tidak lagi tampil di halaman utama.");
    }
}
