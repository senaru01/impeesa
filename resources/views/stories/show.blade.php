@extends('layouts.site')

@section('title', $story->title.' — Cerita Pendakian Impeesa')
@section('description', \Illuminate\Support\Str::limit($story->body, 155))
@section('body-class', 'page-story')

@section('content')
    <article class="story-page">
        <div class="story-page__inner">
            <p class="story-page__meta">{{ $story->mountain_name ?: 'Cerita pendakian' }}@if ($story->climbed_on) &middot; {{ $story->climbed_on->translatedFormat('F Y') }}@endif</p>
            <h1 class="story-page__title">{{ $story->title }}</h1>
            <p class="story-page__author">Ditulis oleh {{ $story->user->display_name }}, anggota Impeesa X-tream Adventure Team</p>

            @if ($story->cover_path)
                <figure class="story-page__cover">
                    <img src="{{ route('stories.cover', $story) }}" alt="">
                </figure>
            @endif

            <div class="story-page__body">
                @foreach (preg_split('/\R{2,}/', trim($story->body)) as $paragraph)
                    <p>{!! nl2br(e($paragraph)) !!}</p>
                @endforeach
            </div>

            @if ($others->isNotEmpty())
                <div class="story-page__more">
                    <p class="story-page__meta">Cerita lainnya</p>
                    <div class="tales__grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))">
                        @foreach ($others as $other)
                            <a href="{{ route('stories.public', $other) }}" class="tale">
                                <span class="tale__body">
                                    <span class="tale__meta">{{ $other->mountain_name ?: 'Cerita' }}</span>
                                    <span class="tale__title">{{ $other->title }}</span>
                                    <span class="tale__author">&mdash; {{ $other->user->display_name }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <p style="margin-top: 50px">
                @if ($story->isFeatured())
                    <a href="{{ route('landing') }}#cerita" class="btn btn--ghost">&larr; Kembali ke beranda</a>
                @else
                    <a href="{{ $story->is_public ? route('stories.shared') : route('stories.index') }}" class="btn btn--ghost">&larr; Kembali ke {{ $story->is_public ? 'Cerita Bersama' : 'Cerita Saya' }}</a>
                @endif
            </p>
        </div>
    </article>
@endsection
