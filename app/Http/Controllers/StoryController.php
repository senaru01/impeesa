<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoryRequest;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoryController extends Controller
{
    public function index(Request $request): View
    {
        return view('member.stories.index', [
            'stories' => $request->user()->stories()->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('member.stories.form', ['story' => new Story]);
    }

    public function store(StoryRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->stories()->create([
            ...$request->safe()->except('cover'),
            'cover_path' => $request->file('cover')?->store("stories/{$user->id}", 'local'),
        ]);

        return to_route('stories.index')->with('status', $request->boolean('is_public')
            ? 'Cerita publik tersimpan. Semua member bisa membacanya, dan super admin bisa memilihnya untuk halaman utama.'
            : 'Cerita privat tersimpan. Hanya kamu dan super admin yang bisa membacanya.');
    }

    public function edit(Story $story): View
    {
        Gate::authorize('update', $story);

        return view('member.stories.form', ['story' => $story]);
    }

    public function update(StoryRequest $request, Story $story): RedirectResponse
    {
        $story->fill($request->safe()->except('cover'));

        if ($request->hasFile('cover')) {
            $previous = $story->cover_path;
            $story->cover_path = $request->file('cover')->store("stories/{$story->user_id}", 'local');

            if ($previous) {
                Storage::disk('local')->delete($previous);
            }
        }

        // Cerita yang dijadikan privat otomatis turun dari halaman utama.
        if (! $story->is_public) {
            $story->featured_at = null;
        }

        $story->save();

        return to_route('stories.index')->with('status', 'Cerita berhasil diperbarui.');
    }

    public function destroy(Story $story): RedirectResponse
    {
        Gate::authorize('delete', $story);

        if ($story->cover_path) {
            Storage::disk('local')->delete($story->cover_path);
        }

        $story->delete();

        return back()->with('status', 'Cerita dihapus.');
    }

    /**
     * Sampul cerita ikut publik setelah cerita ditampilkan di beranda.
     */
    public function cover(Story $story): StreamedResponse
    {
        abort_unless($story->cover_path && Gate::allows('view', $story), 404);

        return Storage::disk('local')->response($story->cover_path, headers: [
            'Cache-Control' => ($story->isFeatured() ? 'public' : 'private').', max-age=86400',
        ]);
    }
}
