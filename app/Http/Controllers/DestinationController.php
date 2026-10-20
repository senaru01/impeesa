<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DestinationController extends Controller
{
    public function __invoke(string $slug): View
    {
        $partner = config("partners.{$slug}");
        $destination = collect(config('mountains.destinations'))->firstWhere('slug', $slug);

        abort_if($partner === null || $destination === null, 404);

        return view('destinasi', [
            'destination' => $destination,
            'partner' => $partner,
        ]);
    }
}
