@extends('layouts.member')

@section('title', 'Kesehatan')

@php
    $kg = fn (?float $value) => $value === null ? '–' : number_format($value, 1, ',', '.');
    $km = fn (float $value) => number_format($value, $value == floor($value) ? 0 : 1, ',', '.');
    $weeklyMax = max(1, max(array_column($weeklyRuns, 'distance')));
@endphp

@section('content')
    <div class="m-pagehead">
        <div>
            <p class="m-crumb">Member &middot; Kesehatan</p>
            <h1 class="m-title">Berat badan &amp; lari</h1>
            <p class="m-subtitle">Pantau progres berat badan dan latihan lari sebagai persiapan fisik pendakian.</p>
        </div>
    </div>

    <div class="m-grid" style="margin-bottom: 20px">
        <article class="m-card">
            <p class="m-health__label">Berat terakhir</p>
            <p class="m-health__value">{{ $kg($summary['latestWeight']?->weight_kg) }}<span>kg</span></p>
            @if ($summary['weightChange'] !== null)
                <p class="m-health__meta">
                    <span class="m-chip m-chip--grey">{{ $summary['weightChange'] > 0 ? '+' : '' }}{{ $kg($summary['weightChange']) }} kg</span>
                    dari catatan sebelumnya
                </p>
            @elseif ($summary['latestWeight'])
                <p class="m-health__meta">{{ $summary['latestWeight']->recorded_on->translatedFormat('d M Y') }}</p>
            @else
                <p class="m-health__meta">Belum ada catatan.</p>
            @endif
        </article>

        <article class="m-card">
            <p class="m-health__label">Indeks massa tubuh</p>
            <p class="m-health__value">{{ $summary['bmi'] ? number_format($summary['bmi'], 1, ',', '.') : '–' }}</p>
            <p class="m-health__meta">
                @if ($summary['bmi'])
                    <span @class([
                        'm-chip',
                        'm-chip--green' => $summary['bmiCategory'] === 'Normal',
                        'm-chip--accent' => in_array($summary['bmiCategory'], ['Kurus', 'Gemuk']),
                        'm-chip--red' => $summary['bmiCategory'] === 'Obesitas',
                    ])>{{ $summary['bmiCategory'] }}</span>
                    tinggi {{ $summary['heightCm'] }} cm
                @else
                    Isi tinggi badan untuk menghitung IMT.
                @endif
            </p>
        </article>

        <article class="m-card">
            <p class="m-health__label">Lari bulan ini</p>
            <p class="m-health__value">{{ $km($summary['monthDistance']) }}<span>km</span></p>
            <p class="m-health__meta">{{ $summary['monthRuns'] }} kali lari &middot; total {{ $km($summary['totalDistance']) }} km</p>
        </article>

        <article class="m-card">
            <p class="m-health__label">Rekor</p>
            <p class="m-health__value">{{ $summary['longestRun'] ? $km($summary['longestRun']->distance_km) : '–' }}<span>km</span></p>
            <p class="m-health__meta">Lari terjauh{{ $summary['bestPace'] ? ' · pace terbaik '.$summary['bestPace'] : '' }}</p>
        </article>
    </div>

    {{-- ============ BERAT BADAN ============ --}}
    <div class="m-layout" style="margin-bottom: 20px" id="berat-badan">
        <div class="m-card">
            <div class="m-card__head">
                <h2 class="m-card__title">Progres berat badan</h2>
                @if ($summary['totalChange'] !== null)
                    <span class="m-chip">{{ $summary['totalChange'] > 0 ? '+' : '' }}{{ $kg($summary['totalChange']) }} kg sejak catatan pertama</span>
                @endif
            </div>

            @if ($weightChart)
                <figure class="m-linechart">
                    <svg viewBox="-12 -12 624 184" role="img" aria-label="Grafik berat badan {{ count($weightChart['points']) }} catatan terakhir">
                        <line x1="0" y1="0" x2="600" y2="0" class="m-linechart__grid"/>
                        <line x1="0" y1="160" x2="600" y2="160" class="m-linechart__grid"/>
                        <polyline class="m-linechart__line" points="{{ collect($weightChart['points'])->map(fn ($point) => $point['x'].','.$point['y'])->implode(' ') }}"/>
                        @foreach ($weightChart['points'] as $point)
                            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="5" class="m-linechart__dot"><title>{{ $point['label'] }}: {{ $kg($point['weight']) }} kg</title></circle>
                        @endforeach
                    </svg>
                    <figcaption class="m-linechart__axis">
                        <span>{{ $weightChart['points'][0]['label'] }}</span>
                        <span>skala {{ $kg($weightChart['min']) }}–{{ $kg($weightChart['max']) }} kg</span>
                        <span>{{ end($weightChart['points'])['label'] }}</span>
                    </figcaption>
                </figure>
            @elseif ($weights->isNotEmpty())
                <p class="m-note" style="margin-top: 0">Grafik muncul setelah ada minimal dua catatan.</p>
            @endif

            @if ($weights->isEmpty())
                <p class="m-empty">Belum ada catatan berat badan. Timbang badan lalu catat di formulir samping.</p>
            @else
                <div class="m-table-wrap" style="margin-top: 16px">
                    <table class="m-table">
                        <thead><tr><th>Tanggal</th><th>Berat</th><th>IMT</th><th>Catatan</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($weights as $log)
                                @php($bmi = $log->bmi($log->height_cm ?? $summary['heightCm']))
                                <tr>
                                    <td style="white-space: nowrap">{{ $log->recorded_on->translatedFormat('d M Y') }}</td>
                                    <td class="m-list__title" style="white-space: nowrap">{{ $kg($log->weight_kg) }} kg</td>
                                    <td>{{ $bmi ? number_format($bmi, 1, ',', '.') : '–' }}</td>
                                    <td>{{ $log->notes ?: '–' }}</td>
                                    <td>
                                        <div class="m-table__actions">
                                            <form method="POST" action="{{ route('weights.destroy', $log) }}" onsubmit="return confirm('Hapus catatan berat badan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="m-btn m-btn--danger m-btn--sm">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('weights.store') }}" class="m-card m-form">
            @csrf
            <h2 class="m-card__title">Catat berat badan</h2>
            @include('member.partials.errors', ['bag' => 'weight'])
            <div class="m-form__grid">
                <div class="m-field">
                    <label for="recorded_on">Tanggal</label>
                    <input id="recorded_on" name="recorded_on" type="date" class="m-input" max="{{ now()->format('Y-m-d') }}" value="{{ old('recorded_on', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="m-field">
                    <label for="weight_kg">Berat (kg)</label>
                    <input id="weight_kg" name="weight_kg" type="number" step="0.1" min="20" max="300" inputmode="decimal" class="m-input" value="{{ old('weight_kg') }}" placeholder="mis. 65,5" required>
                </div>
            </div>
            <div class="m-field">
                <label for="height_cm">Tinggi badan (cm)</label>
                <input id="height_cm" name="height_cm" type="number" min="100" max="250" class="m-input" value="{{ old('height_cm', $lastHeight) }}">
                <span class="m-field__hint">Opsional, untuk menghitung IMT. Terisi otomatis dari catatan terakhir.</span>
            </div>
            <div class="m-field">
                <label for="weight_notes">Catatan</label>
                <input id="weight_notes" name="notes" class="m-input" maxlength="255" value="{{ $errors->weight->any() ? old('notes') : '' }}" placeholder="mis. pagi sebelum sarapan">
            </div>
            <button type="submit" class="m-btn m-btn--primary">Simpan</button>
        </form>
    </div>

    {{-- ============ LARI ============ --}}
    <div class="m-layout" id="lari">
        <div class="m-card">
            <div class="m-card__head">
                <h2 class="m-card__title">Aktivitas lari</h2>
                <span class="m-chip">7 pekan terakhir</span>
            </div>
            <div class="m-bars">
                <span class="m-chip m-chip--solid m-bars__flag">{{ $km(end($weeklyRuns)['distance']) }} km pekan ini</span>
                @foreach ($weeklyRuns as $week)
                    <div @class(['m-bars__col', 'is-current' => $week['current']]) title="Pekan {{ $week['label'] }}: {{ $km($week['distance']) }} km">
                        <span @class(['m-bars__bar', 'has-value' => $week['distance'] > 0]) style="height: {{ $week['distance'] > 0 ? max(10, $week['distance'] / $weeklyMax * 100) : 6 }}%"></span>
                        <span class="m-bars__label">{{ $week['label'] }}</span>
                    </div>
                @endforeach
            </div>

            @if ($runs->isEmpty())
                <p class="m-empty">Belum ada catatan lari. Catat lari pertamamu di formulir samping.</p>
            @else
                <div class="m-table-wrap" style="margin-top: 20px">
                    <table class="m-table">
                        <thead><tr><th>Tanggal</th><th>Jarak</th><th>Durasi</th><th>Pace</th><th>Catatan</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($runs as $run)
                                <tr>
                                    <td style="white-space: nowrap">{{ $run->run_on->translatedFormat('d M Y') }}</td>
                                    <td class="m-list__title" style="white-space: nowrap">{{ number_format($run->distance_km, 2, ',', '.') }} km</td>
                                    <td>{{ $run->formattedDuration() }}</td>
                                    <td style="white-space: nowrap">{{ $run->formattedPace() }}</td>
                                    <td>{{ $run->notes ?: '–' }}</td>
                                    <td>
                                        <div class="m-table__actions">
                                            <form method="POST" action="{{ route('runs.destroy', $run) }}" onsubmit="return confirm('Hapus catatan lari ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="m-btn m-btn--danger m-btn--sm">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ route('runs.store') }}" class="m-card m-form">
            @csrf
            <h2 class="m-card__title">Catat lari</h2>
            @include('member.partials.errors', ['bag' => 'run'])
            <div class="m-field">
                <label for="run_on">Tanggal</label>
                <input id="run_on" name="run_on" type="date" class="m-input" max="{{ now()->format('Y-m-d') }}" value="{{ old('run_on', now()->format('Y-m-d')) }}" required>
            </div>
            <div class="m-form__grid">
                <div class="m-field">
                    <label for="distance_km">Jarak (km)</label>
                    <input id="distance_km" name="distance_km" type="number" step="0.01" min="0.1" max="300" inputmode="decimal" class="m-input" value="{{ old('distance_km') }}" placeholder="mis. 5" required>
                </div>
                <div class="m-field">
                    <label for="duration">Durasi</label>
                    <input id="duration" name="duration" class="m-input" pattern="(\d{1,2}:)?\d{1,3}:[0-5]\d" value="{{ old('duration') }}" placeholder="mm:ss" required>
                </div>
            </div>
            <span class="m-field__hint">Durasi ditulis menit:detik (35:20) atau jam:menit:detik (1:05:30). Pace dihitung otomatis.</span>
            <div class="m-field">
                <label for="run_notes">Catatan</label>
                <input id="run_notes" name="notes" class="m-input" maxlength="255" value="{{ $errors->run->any() ? old('notes') : '' }}" placeholder="mis. GBK, interval, lari santai">
            </div>
            <button type="submit" class="m-btn m-btn--primary">Simpan</button>
        </form>
    </div>
@endsection
