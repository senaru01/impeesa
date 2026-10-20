<?php

use App\Http\Controllers\Admin\FeaturedProfileController;
use App\Http\Controllers\Admin\FeaturedStoryController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MemberPhotoController;
use App\Http\Controllers\Admin\SharedPhotoApprovalController;
use App\Http\Controllers\Admin\StoryController as AdminStoryController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\ClimbController;
use App\Http\Controllers\ClimbCoverController;
use App\Http\Controllers\ClimbInvitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\EmergencyController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MemberProfileController;
use App\Http\Controllers\MemberProfilePhotoController;
use App\Http\Controllers\PetaGunungController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicStoryController;
use App\Http\Controllers\SharedPhotoController;
use App\Http\Controllers\SharedPhotoRequestController;
use App\Http\Controllers\SharedStoryController;
use App\Http\Controllers\StoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('landing');
Route::get('/peta-gunung', PetaGunungController::class)->name('peta');
Route::get('/destinasi/{slug}', DestinationController::class)->name('destinasi');
Route::get('/cerita-pendakian/{story}', PublicStoryController::class)->name('stories.public');
Route::get('/profil-anggota', MemberProfileController::class)->name('profiles.index');
Route::get('/profil-anggota/{user}/foto', MemberProfilePhotoController::class)->name('profiles.photo');
Route::get('/cerita/{story}/sampul', [StoryController::class, 'cover'])->name('stories.cover');

Route::middleware('throttle:30,1')->group(function () {
    Route::get('/darurat/{token}', [EmergencyController::class, 'show'])->name('emergency.show');
    Route::get('/darurat/{token}/foto', [EmergencyController::class, 'photo'])->name('emergency.photo');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/anggota/{user}/foto-profil', AvatarController::class)->name('avatar');

    Route::get('/kartu-darurat', [EmergencyController::class, 'card'])->name('emergency.card');
    Route::post('/kartu-darurat/qr-baru', [EmergencyController::class, 'regenerate'])->name('emergency.regenerate');

    Route::resource('pendakian', ClimbController::class)
        ->except(['create', 'show'])
        ->parameters(['pendakian' => 'climb'])
        ->names('climbs');

    Route::put('/pendakian/{climb}/foto-utama/{photo}', [ClimbCoverController::class, 'update'])->name('climbs.cover');
    Route::post('/ajakan-pendakian/{participant}/setuju', [ClimbInvitationController::class, 'accept'])->name('invitations.accept');
    Route::post('/ajakan-pendakian/{participant}/tolak', [ClimbInvitationController::class, 'decline'])->name('invitations.decline');

    Route::get('/galeri-bersama', [SharedPhotoController::class, 'index'])->name('photos.shared');
    Route::post('/galeri-bersama', [SharedPhotoController::class, 'store'])->name('photos.shared.store');

    Route::get('/galeri', [PhotoController::class, 'index'])->name('photos.index');
    Route::post('/galeri', [PhotoController::class, 'store'])->name('photos.store');
    Route::get('/galeri/{photo}', [PhotoController::class, 'show'])->name('photos.show');
    Route::delete('/galeri/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
    Route::post('/galeri/{photo}/bagikan', [SharedPhotoRequestController::class, 'store'])->name('photos.share');
    Route::delete('/galeri/{photo}/bagikan', [SharedPhotoRequestController::class, 'destroy'])->name('photos.unshare');

    Route::get('/cerita-bersama', [SharedStoryController::class, 'index'])->name('stories.shared');

    Route::resource('cerita', StoryController::class)
        ->except(['show'])
        ->parameters(['cerita' => 'story'])
        ->names('stories');

    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('anggota', MemberController::class)
            ->except(['destroy'])
            ->parameters(['anggota' => 'member'])
            ->names('members');
        Route::post('/anggota/{member}/foto', [MemberPhotoController::class, 'store'])->name('members.photos.store');
        Route::post('/anggota/{member}/profil-publik', [FeaturedProfileController::class, 'store'])->name('members.profile.feature');
        Route::delete('/anggota/{member}/profil-publik', [FeaturedProfileController::class, 'destroy'])->name('members.profile.unfeature');
        Route::get('/anggota/{member}/kartu-darurat', [EmergencyController::class, 'card'])->name('members.emergency-card');

        Route::post('/galeri-bersama/{photo}/setujui', [SharedPhotoApprovalController::class, 'store'])->name('shared-photos.approve');
        Route::delete('/galeri-bersama/{photo}/tolak', [SharedPhotoApprovalController::class, 'destroy'])->name('shared-photos.reject');

        Route::get('/cerita', [AdminStoryController::class, 'index'])->name('stories.index');
        Route::post('/cerita/{story}/tampilkan', [FeaturedStoryController::class, 'store'])->name('stories.feature');
        Route::delete('/cerita/{story}/tampilkan', [FeaturedStoryController::class, 'destroy'])->name('stories.unfeature');
    });
});
