<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvatarController extends Controller
{
    public function __invoke(Request $request, User $user): StreamedResponse
    {
        abort_unless($request->user()->is($user) || $request->user()->isSuperAdmin(), 403);
        abort_unless($user->avatar_path && Storage::disk('local')->exists($user->avatar_path), 404);

        return Storage::disk('local')->response($user->avatar_path, headers: ['Cache-Control' => 'private, max-age=86400']);
    }
}
