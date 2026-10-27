<?php

namespace App\Http\Controllers;

use App\Http\Requests\PhotoUploadRequest;
use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Galeri Bersama: semua member bisa mengirim foto; kiriman member tampil setelah disetujui super admin.
 */
class SharedPhotoController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('member.photos.shared', [
            'photos' => Photo::shared()->approved()->with(['user', 'uploader', 'climb'])->latest('approved_at')->paginate(48),
            'explore' => $this->explore(),
            'pending' => Photo::shared()
                ->pending()
                ->with(['user', 'uploader'])
                ->unless($user->isSuperAdmin(), fn ($query) => $query->where(fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->orWhere(fn ($query) => $query->whereNull('user_id')->where('uploaded_by', $user->id))))
                ->oldest()
                ->get(),
        ]);
    }

    public function store(PhotoUploadRequest $request): RedirectResponse
    {
        $count = $request->storePhotos(null);

        return back()->with('status', $request->user()->isSuperAdmin()
            ? "{$count} foto ditambahkan ke Galeri Bersama."
            : "{$count} foto terkirim dan menunggu persetujuan super admin.");
    }

    /**
     * Data slideshow + peta jejak: semua foto yang disetujui, urut dari yang paling lama,
     * dengan lokasi dari gunung pendakian fotonya atau lokasi yang diisi manual. Urutan "places" mengikuti
     * kunjungan pertama, sehingga garis jejak di peta tersambung sesuai waktu.
     *
     * @return array{
     *     slides: list<array{url: string, caption: string, date: string, by: string, member: int|null, place: int|null}>,
     *     places: list<array{name: string, lat: float, lng: float, count: int}>,
     *     members: list<array{id: int, name: string, count: int}>,
     * }
     */
    private function explore(): array
    {
        $photos = Photo::shared()
            ->approved()
            ->with(['user', 'uploader', 'climb'])
            ->get()
            ->sortBy(fn (Photo $photo) => [
                ($photo->taken_on ?? $photo->climb?->started_on ?? $photo->approved_at)->format('Y-m-d'),
                $photo->id,
            ]);

        $slides = [];
        $places = [];
        $placeIndex = [];
        $members = [];

        foreach ($photos as $photo) {
            $location = $photo->mountainLocation();
            $place = null;

            if ($location !== null) {
                $place = $placeIndex[$location['key']] ??= count($places);
                $places[$place] ??= [
                    'name' => $photo->locationName(),
                    'lat' => $location['lat'],
                    'lng' => $location['lng'],
                    'count' => 0,
                ];
                $places[$place]['count']++;
            }

            $member = $photo->user ?? $photo->uploader;

            if ($member !== null) {
                $members[$member->id] ??= ['id' => $member->id, 'name' => $member->display_name, 'count' => 0];
                $members[$member->id]['count']++;
            }

            $slides[] = [
                'url' => route('photos.show', $photo),
                'caption' => $photo->caption ?: ($photo->locationName() ?? 'Foto bersama'),
                'date' => ($photo->taken_on ?? $photo->climb?->started_on ?? $photo->approved_at)->translatedFormat('d M Y'),
                'by' => $member?->display_name ?? 'member',
                'member' => $member?->id,
                'place' => $place,
            ];
        }

        return [
            'slides' => $slides,
            'places' => $places,
            'members' => collect($members)->sortBy('name')->values()->all(),
        ];
    }
}
