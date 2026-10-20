@extends('layouts.site')

@section('title', 'Profil Singkat Anggota — Impeesa X-tream Adventure Team')
@section('description', 'Kenalan dengan keluarga kecil Impeesa X-tream Adventure Team: siapa kami, dari mana kami bertemu, dan cerita di balik setiap anggota.')
@section('body-class', 'page-story')

@section('content')
    <article class="story-page">
        <div class="story-page__inner">
            <p class="story-page__meta">Keluarga Impeesa</p>
            <h1 class="story-page__title">Profil Singkat Anggota</h1>
            <p class="story-page__author">{{ $members->count() }} anggota &middot; Impeesa X-tream Adventure Team</p>

            <div class="story-page__body">
                <p>Di balik artikel yang terbit di website Impeesa X-Tream Adventure, ada kontributor yang secara sukarela menggunakan waktu luangnya untuk berbagi cerita. Apa yang mereka ceritakan murni atas apa yang mereka alami. Karena inilah Impeesa, keluarga kecil yang mempunyai cita-cita besar, dengan kekompakan anggota yang selalu punya cara untuk membuat Impeesa semakin berwarna.</p>
                <p>Pertemuan kita selalu unik: ada yang bertemu semasa sekolah, bertemu di tempat kerja, bertemu karena dia teman dari temannya teman, ada juga yang bertemu di jalur pendakian. Kami memang sederhana dan berjumlah sedikit, namun ikatan keluarga selalu menjaga kekompakan kita. Kami tak selalu berjalan bersama, tapi kami punya cerita yang selalu mencerminkan kebersamaan.</p>
                <p>Siapakah kami? Berikut profil singkat kami. Ada yang lucu, nggemesin, ngangenin, dan ada pula yang bikin gak bisa tidur.</p>
            </div>

            @forelse ($members as $i => $member)
                <section class="profile-story" id="anggota-{{ $member->id }}">
                    <figure class="profile-story__photo">
                        @if ($member->avatar_path)
                            <img src="{{ route('profiles.photo', $member) }}" alt="Foto {{ $member->name }}" loading="lazy">
                        @else
                            <span class="profile-story__initials" aria-hidden="true">{{ $member->initials }}</span>
                        @endif
                    </figure>
                    <div class="profile-story__text">
                        <p class="story-page__meta">
                            {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                            @if ($member->signature_place) &middot; @ {{ $member->signature_place }}@endif
                        </p>
                        <h2 class="profile-story__name">{{ $member->name }}</h2>
                        @if ($member->nickname || $member->position)
                            <p class="profile-story__role">{{ collect([$member->nickname ? '"'.$member->nickname.'"' : null, $member->position])->filter()->implode(' · ') }}</p>
                        @endif
                        @if ($member->bio)
                            <div class="story-page__body profile-story__bio">
                                @foreach (preg_split('/\R{2,}/', trim($member->bio)) as $paragraph)
                                    <p>{!! nl2br(e($paragraph)) !!}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @empty
                <p class="story-page__author">Profil anggota belum ditampilkan.</p>
            @endforelse

            <p class="profile-story__closing">
                Kami Keluarga, dan Itulah Keluarga Kami
                <span>Impeesa X-tream Adventure &middot; Widya Krida Jaya Ancala</span>
            </p>

            <p style="margin-top: 50px">
                <a href="{{ route('landing') }}#anggota" class="btn btn--ghost">&larr; Kembali ke beranda</a>
            </p>
        </div>
    </article>
@endsection
