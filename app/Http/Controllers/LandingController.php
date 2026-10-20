<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\User;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        $destinations = collect(config('mountains.destinations'))
            ->map(fn (array $destination) => [
                ...$destination,
                'detail_url' => config()->has("partners.{$destination['slug']}") ? route('destinasi', $destination['slug']) : null,
            ]);
        $bySlug = $destinations->keyBy('slug');

        $recommendations = collect(config('mountains.recommended'))
            ->map(fn (string $slug) => $bySlug[$slug])
            ->all();

        $credits = $destinations
            ->map(fn (array $destination) => ['title' => $destination['name'], ...$destination['credit']])
            ->concat(config('mountains.extra_credits'))
            ->map(fn (array $credit) => [
                ...$credit,
                'url' => 'https://commons.wikimedia.org/wiki/File:'.rawurlencode($credit['source']),
            ])
            ->all();

        return view('landing', [
            'destinations' => $destinations->all(),
            'recommendations' => $recommendations,
            'credits' => $credits,
            'gallery' => config('gallery.items'),
            'stories' => Story::featured()->with('user')->latest('featured_at')->take(3)->get(),
            'profiles' => User::featuredProfile()->whereNotNull('avatar_path')->inRandomOrder()->take(8)->get(),
            'profileCount' => User::featuredProfile()->count(),
            'trailCount' => count(config('trails.trails')),
            'regionCount' => count(config('trails.regions')),
        ]);
    }
}
