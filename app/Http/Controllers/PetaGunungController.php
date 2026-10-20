<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PetaGunungController extends Controller
{
    public function __invoke(): View
    {
        $source = config('trails.source');
        $photos = collect(config('mountains.destinations'))->pluck('image', 'slug');

        $trails = collect(config('trails.trails'))
            ->map(function (array $trail, int $index) use ($source, $photos) {
                $base = "{$source['files_base_url']}/{$trail['file']}";

                return [
                    'id' => $index,
                    'name' => $trail['name'],
                    'area' => $trail['area'],
                    'region' => $trail['region'],
                    'lat' => $trail['lat'],
                    'lng' => $trail['lng'],
                    'photo' => $trail['photo'] ? asset($photos[$trail['photo']]) : null,
                    'files' => array_filter([
                        'gpx' => "{$base}-gpx.gpx",
                        'kmz' => $trail['kmz'] ? "{$base}-kmz.kmz" : null,
                        'pdf' => $trail['pdf'] ? "{$base}-pdf.pdf" : null,
                    ]),
                ];
            });

        $regions = collect(config('trails.regions'))
            ->mapWithKeys(fn (string $region) => [$region => $trails->where('region', $region)->values()])
            ->filter(fn ($items) => $items->isNotEmpty());

        return view('peta', [
            'source' => $source,
            'trails' => $trails,
            'regions' => $regions,
            'pdfCount' => $trails->filter(fn (array $trail) => isset($trail['files']['pdf']))->count(),
        ]);
    }
}
