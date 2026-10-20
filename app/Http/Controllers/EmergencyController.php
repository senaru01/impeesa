<?php

namespace App\Http\Controllers;

use App\Enums\ClimbStatus;
use App\Models\Climb;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Kartu darurat ber-QR untuk tim SAR. QR memuat data penting (terbaca tanpa sinyal)
 * dan tautan ke halaman detail yang bisa dibuka tanpa login lewat token acak.
 */
class EmergencyController extends Controller
{
    /**
     * Kartu milik sendiri, atau milik member lain bila dibuka super admin lewat route admin.
     */
    public function card(Request $request, ?User $member = null): View
    {
        $person = $member ?? $request->user();

        return view('emergency.card', [
            'person' => $person,
            'qrText' => $person->emergencyQrText(),
            'missing' => $this->missingFields($person),
        ]);
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $request->user()->regenerateEmergencyToken();

        return back()->with('status', 'Kode QR baru dibuat. Cetak ulang kartumu; QR lama tidak bisa membuka halaman detail lagi.');
    }

    public function show(string $token): View
    {
        $person = $this->findByToken($token);

        return view('emergency.show', [
            'person' => $person,
            'climb' => $this->relevantClimb($person),
        ]);
    }

    public function photo(string $token): StreamedResponse
    {
        $person = $this->findByToken($token);

        abort_unless($person->avatar_path && Storage::disk('local')->exists($person->avatar_path), 404);

        return Storage::disk('local')->response($person->avatar_path, headers: ['Cache-Control' => 'private, max-age=3600']);
    }

    private function findByToken(string $token): User
    {
        return User::where('emergency_token', $token)->where('is_active', true)->firstOrFail();
    }

    /**
     * Pendakian yang sedang berlangsung, lalu rencana terdekat, lalu pendakian terakhir.
     */
    private function relevantClimb(User $person): ?Climb
    {
        $today = today();

        return $person->climbs()
            ->whereDate('started_on', '<=', $today)
            ->where(fn ($query) => $query
                ->whereDate('finished_on', '>=', $today)
                ->orWhere(fn ($query) => $query->whereNull('finished_on')->whereDate('started_on', $today)))
            ->latest('started_on')
            ->first()
            ?? $person->climbs()->where('status', ClimbStatus::Planned)->whereDate('started_on', '>=', $today)->oldest('started_on')->first()
            ?? $person->climbs()->latest('started_on')->first();
    }

    /**
     * Isian penting yang masih kosong, supaya member tahu apa yang perlu dilengkapi sebelum mencetak.
     *
     * @return list<string>
     */
    private function missingFields(User $person): array
    {
        return collect([
            'Golongan darah' => $person->blood_type,
            'Kontak darurat' => $person->emergency_contact_phone,
            'Tanggal lahir' => $person->birth_date,
            'NIK' => $person->nik,
            'Alamat KTP' => $person->id_card_address,
            'Foto profil' => $person->avatar_path,
        ])->filter(fn ($value) => blank($value))->keys()->all();
    }
}
