<?php

namespace App\Http\Controllers;

use App\Enums\ClimbStatus;
use App\Enums\ParticipantStatus;
use App\Http\Requests\ClimbRequest;
use App\Models\Climb;
use App\Models\ClimbParticipant;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClimbController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('member.climbs.index', [
            'climbs' => $user->climbs()
                ->with([
                    'photos', 'coverPhoto', 'participants.user',
                    'joinedFrom.climb.user', 'joinedFrom.climb.participants.user',
                ])
                ->latest('started_on')
                ->get(),
            'invitations' => $this->pendingInvitations($user),
            'statuses' => ClimbStatus::cases(),
            'mountains' => Climb::mountainOptions(),
            'members' => $this->memberOptions($user),
        ]);
    }

    public function store(ClimbRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $climb = $request->user()->climbs()->create($request->climbData());
            $this->storePhotos($request, $climb);
            $this->syncParticipants($climb, $request->participantIds());
        });

        return to_route('climbs.index')->with('status', $this->savedMessage('Pendakian berhasil dicatat', $request));
    }

    public function edit(Request $request, Climb $climb): View
    {
        Gate::authorize('update', $climb);

        return view('member.climbs.edit', [
            'climb' => $climb->load(['photos', 'coverPhoto', 'participants.user', 'joinedFrom.climb.user']),
            'statuses' => ClimbStatus::cases(),
            'mountains' => Climb::mountainOptions(),
            'members' => $this->memberOptions($request->user()),
        ]);
    }

    public function update(ClimbRequest $request, Climb $climb): RedirectResponse
    {
        DB::transaction(function () use ($request, $climb) {
            $climb->update($request->climbData());
            $this->storePhotos($request, $climb);

            // Salinan milik peserta tidak bisa mengajak anggota lain.
            if (! $climb->isJoined()) {
                $this->syncParticipants($climb, $request->participantIds());
                $this->syncJoinedCopies($climb);
            }
        });

        return to_route('climbs.index')->with('status', $this->savedMessage('Pendakian berhasil diperbarui', $request));
    }

    public function destroy(Climb $climb): RedirectResponse
    {
        Gate::authorize('delete', $climb);

        $climb->delete();

        return to_route('climbs.index')->with('status', 'Pendakian dihapus.');
    }

    /**
     * Foto pendakian masuk ke galeri pribadi pemilik. Foto pertama jadi foto utama
     * bila pendakian belum punya foto utama.
     */
    private function storePhotos(ClimbRequest $request, Climb $climb): void
    {
        foreach ($request->file('photos', []) as $file) {
            $photo = Photo::storeUpload($file, $request->user(), $request->user(), $climb, [
                'caption' => $climb->mountain_name.($climb->route ? " via {$climb->route}" : ''),
                'taken_on' => $climb->started_on,
            ]);

            if ($climb->cover_photo_id === null) {
                $climb->coverPhoto()->associate($photo)->save();
            }
        }
    }

    /**
     * Tambah ajakan baru dan hapus anggota yang tidak lagi dipilih.
     *
     * @param  list<int>  $userIds
     */
    private function syncParticipants(Climb $climb, array $userIds): void
    {
        $climb->participants()->whereNotIn('user_id', $userIds)->delete();

        $existing = $climb->participants()->pluck('user_id')->all();

        foreach (array_diff($userIds, $existing) as $userId) {
            $participant = new ClimbParticipant;
            $participant->user_id = $userId;
            $participant->status = ParticipantStatus::Pending;
            $climb->participants()->save($participant);
        }
    }

    /**
     * Gunung, jalur dan tanggal di salinan peserta ikut menyesuaikan perubahan pengajak.
     * Status dan catatan tetap milik masing-masing peserta.
     */
    private function syncJoinedCopies(Climb $climb): void
    {
        $copyIds = $climb->participants()->whereNotNull('joined_climb_id')->pluck('joined_climb_id');

        Climb::whereKey($copyIds)->update($climb->only(['mountain_name', 'route', 'elevation', 'started_on', 'finished_on']));
    }

    /**
     * @return EloquentCollection<int, ClimbParticipant>
     */
    private function pendingInvitations(User $user): EloquentCollection
    {
        return ClimbParticipant::where('user_id', $user->id)
            ->where('status', ParticipantStatus::Pending)
            ->with(['climb.user', 'climb.participants.user'])
            ->latest()
            ->get();
    }

    /**
     * Anggota aktif lain yang bisa diajak.
     *
     * @return EloquentCollection<int, User>
     */
    private function memberOptions(User $user): EloquentCollection
    {
        return User::where('is_active', true)
            ->whereKeyNot($user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'nickname', 'member_number', 'avatar_path']);
    }

    private function savedMessage(string $message, ClimbRequest $request): string
    {
        $parts = [];
        $photoCount = count($request->file('photos', []));
        $inviteCount = count($request->participantIds());

        if ($photoCount > 0) {
            $parts[] = "{$photoCount} foto masuk ke galeri pribadi";
        }

        if ($inviteCount > 0) {
            $parts[] = "{$inviteCount} anggota diajak";
        }

        return $parts ? $message.'; '.implode(', ', $parts).'.' : "{$message}.";
    }
}
