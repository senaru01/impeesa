<?php

namespace App\Http\Controllers;

use App\Enums\ClimbStatus;
use App\Enums\ParticipantStatus;
use App\Models\Climb;
use App\Models\ClimbParticipant;
use App\Models\Photo;
use App\Models\Story;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $today = CarbonImmutable::today();
        $climbs = $user->climbs()
            ->with([
                'photos' => fn ($query) => $query->latest(), 'coverPhoto', 'participants.user',
                'joinedFrom.climb.user', 'joinedFrom.climb.participants.user',
            ])
            ->orderBy('started_on')
            ->get();
        $done = $climbs->where('status', '!=', ClimbStatus::Planned);
        $summits = $climbs->where('status', ClimbStatus::Summit);

        return view('member.dashboard', [
            'user' => $user,
            'today' => $today,
            'counts' => [
                'summit' => $summits->count(),
                'not_summit' => $climbs->where('status', ClimbStatus::NotSummit)->count(),
                'planned' => $climbs->where('status', ClimbStatus::Planned)->count(),
                'photos' => $user->photos()->count(),
                'stories' => $user->stories()->count(),
            ],
            'totals' => [
                'climbs' => $done->count(),
                'highest' => (int) $summits->max('elevation'),
                'this_year' => $done->filter(fn (Climb $climb) => $climb->started_on->year === $today->year)->count(),
            ],
            'monthly' => $this->monthlyClimbs($done, $today),
            ...$this->climbMap($done),
            'nextClimb' => $climbs
                ->where('status', ClimbStatus::Planned)
                ->first(fn (Climb $climb) => $climb->started_on->gte($today)),
            'week' => $this->week($climbs, $today),
            'agenda' => $climbs->filter(fn (Climb $climb) => $climb->started_on->isSameMonth($today))->values(),
            'recentClimbs' => $done->sortByDesc('started_on')->take(5)->values(),
            'recentPhotos' => $user->photos()->latest()->take(6)->get(),
            'sharedPhotos' => Photo::shared()->approved()->with(['user', 'uploader'])->latest('approved_at')->take(8)->get(),
            'recentMembers' => $user->isSuperAdmin() ? User::latest()->take(5)->get() : collect(),
            'memberCount' => $user->isSuperAdmin() ? User::count() : null,
            'featuredCount' => $user->isSuperAdmin() ? Story::featured()->count() : null,
            'pendingPhotoCount' => $user->isSuperAdmin() ? Photo::shared()->pending()->count() : null,
            'invitations' => ClimbParticipant::where('user_id', $user->id)
                ->where('status', ParticipantStatus::Pending)
                ->with(['climb.user', 'climb.participants.user'])
                ->latest()
                ->get(),
        ]);
    }

    /**
     * Titik peta untuk gunung yang sudah didaki, dikelompokkan per gunung.
     * Pendakian yang nama gunungnya tidak dikenali dikembalikan terpisah.
     *
     * @param  Collection<int, Climb>  $done
     * @return array{mapPoints: list<array<string, mixed>>, unmappedClimbs: list<string>}
     */
    private function climbMap(Collection $done): array
    {
        $points = [];
        $unmapped = [];

        foreach ($done->sortByDesc('started_on') as $climb) {
            $location = $climb->mountainLocation();

            if ($location === null) {
                $unmapped[] = $climb->mountain_name;

                continue;
            }

            $key = $location['key'];
            $points[$key] ??= [
                'name' => $climb->mountain_name,
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'summit' => false,
                'climbs' => [],
                'photos' => [],
            ];
            $points[$key]['summit'] = $points[$key]['summit'] || $climb->status === ClimbStatus::Summit;
            $points[$key]['climbs'][] = [
                'date' => $climb->started_on->translatedFormat('d M Y'),
                'route' => $climb->route,
                'elevation' => $climb->elevation,
                'status' => $climb->status->label(),
            ];

            // Foto utama pendakian tampil paling depan di popup peta.
            $mainPhotoId = $climb->mainPhoto()?->id;
            $photos = $climb->photos->sortByDesc(fn (Photo $photo) => $photo->id === $mainPhotoId);

            foreach ($photos as $photo) {
                $points[$key]['photos'][] = [
                    'url' => route('photos.show', $photo),
                    'caption' => $photo->caption ?: $climb->mountain_name,
                    'date' => $climb->started_on->translatedFormat('d M Y'),
                ];
            }
        }

        return [
            'mapPoints' => array_values($points),
            'unmappedClimbs' => array_values(array_unique($unmapped)),
        ];
    }

    /**
     * Jumlah pendakian per bulan selama tujuh bulan terakhir.
     *
     * @param  Collection<int, Climb>  $done
     * @return list<array{label: string, count: int, current: bool}>
     */
    private function monthlyClimbs(Collection $done, CarbonImmutable $today): array
    {
        return collect(range(6, 0))
            ->map(function (int $monthsAgo) use ($done, $today) {
                $month = $today->startOfMonth()->subMonths($monthsAgo);

                return [
                    'label' => $month->translatedFormat('M'),
                    'count' => $done->filter(fn (Climb $climb) => $climb->started_on->isSameMonth($month))->count(),
                    'current' => $monthsAgo === 0,
                ];
            })
            ->all();
    }

    /**
     * Hari Senin sampai Minggu pekan ini beserta pendakian yang berlangsung di hari itu.
     *
     * @param  Collection<int, Climb>  $climbs
     * @return list<array{date: CarbonImmutable, climbs: Collection<int, Climb>}>
     */
    private function week(Collection $climbs, CarbonImmutable $today): array
    {
        $monday = $today->startOfWeek();

        return collect(range(0, 6))
            ->map(function (int $offset) use ($climbs, $monday) {
                $date = $monday->addDays($offset);

                return [
                    'date' => $date,
                    'climbs' => $climbs->filter(fn (Climb $climb) => $date->betweenIncluded(
                        $climb->started_on,
                        $climb->finished_on ?? $climb->started_on,
                    ))->values(),
                ];
            })
            ->all();
    }
}
