<?php

namespace App\Http\Controllers;

use App\Enums\ClimbStatus;
use App\Enums\ParticipantStatus;
use App\Models\ClimbParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Anggota yang diajak menyetujui atau menolak ajakan pendakian. Setuju berarti pendakian
 * ikut tercatat di catatan pendakiannya sendiri (statistik, kalender, peta, kartu darurat).
 */
class ClimbInvitationController extends Controller
{
    public function accept(Request $request, ClimbParticipant $participant): RedirectResponse
    {
        abort_unless($participant->user_id === $request->user()->id, 403);

        DB::transaction(function () use ($participant, $request) {
            $original = $participant->climb;

            if ($participant->joined_climb_id === null) {
                $copy = $request->user()->climbs()->create([
                    ...$original->only(['mountain_name', 'route', 'elevation', 'started_on', 'finished_on']),
                    'status' => $original->started_on->isFuture() || $original->started_on->isToday()
                        ? ClimbStatus::Planned
                        : $original->status,
                ]);
                $participant->joinedClimb()->associate($copy);
            }

            $participant->status = ParticipantStatus::Accepted;
            $participant->responded_at = now();
            $participant->save();
        });

        return back()->with('status', "Kamu ikut pendakian {$participant->climb->mountain_name} bersama {$participant->climb->user->display_name}. Pendakian ini sudah masuk ke catatanmu.");
    }

    /**
     * Menolak ajakan; bila sebelumnya sudah setuju, salinan pendakian di catatan peserta dihapus.
     * Foto yang sudah diunggah ke salinan itu tetap ada di galeri pribadi.
     */
    public function decline(Request $request, ClimbParticipant $participant): RedirectResponse
    {
        abort_unless($participant->user_id === $request->user()->id, 403);

        DB::transaction(function () use ($participant) {
            $copy = $participant->joinedClimb;

            $participant->status = ParticipantStatus::Declined;
            $participant->responded_at = now();
            $participant->joined_climb_id = null;
            $participant->save();

            $copy?->delete();
        });

        return back()->with('status', "Ajakan pendakian {$participant->climb->mountain_name} ditolak.");
    }
}
