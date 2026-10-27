<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\MemberRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();

        return view('admin.members.index', [
            'search' => $search,
            'members' => User::query()
                ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('member_number', 'like', "%{$search}%")))
                ->withCount(['climbs', 'photos', 'stories'])
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.members.form', ['member' => new User(['role' => Role::Member, 'is_active' => true]), 'roles' => Role::cases()]);
    }

    public function store(MemberRequest $request): RedirectResponse
    {
        $member = User::create($request->profileData());

        if ($request->hasFile('avatar')) {
            $member->replaceAvatar($request->file('avatar'));
        }

        return to_route('admin.members.show', $member)->with('status', "Akun {$member->name} berhasil dibuat.");
    }

    public function show(User $member): View
    {
        $member->loadCount(['climbs', 'photos', 'stories']);

        return view('admin.members.show', [
            'member' => $member,
            'climbs' => $member->climbs()->latest('started_on')->get(),
            'photos' => $member->photos()->with(['uploader', 'climb'])->latest()->get(),
            'stories' => $member->stories()->latest()->get(),
            'health' => $member->healthSummary(),
            'weights' => $member->weightLogs()->latest('recorded_on')->latest('id')->take(5)->get(),
            'runs' => $member->runs()->latest('run_on')->latest('id')->take(5)->get(),
        ]);
    }

    public function edit(User $member): View
    {
        return view('admin.members.form', ['member' => $member, 'roles' => Role::cases()]);
    }

    public function update(MemberRequest $request, User $member): RedirectResponse
    {
        $data = $request->profileData();

        // Super admin tidak boleh menurunkan atau menonaktifkan akunnya sendiri.
        if ($member->is($request->user())) {
            unset($data['role'], $data['is_active']);
        }

        $member->update($data);

        if ($request->hasFile('avatar')) {
            $member->replaceAvatar($request->file('avatar'));
        }

        return to_route('admin.members.show', $member)->with('status', 'Data member berhasil disimpan.');
    }
}
