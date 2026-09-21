<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Identity\UpdateUserAction;
use App\Data\Identity\UpdateUserData;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\CorrelationContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles:id,name,code')
            ->when($request->filled('status'), fn ($query) => $query
                ->where('status', $request->string('status')->toString()))
            ->when($request->filled('q'), fn ($query) => $query
                ->where(function ($query) use ($request): void {
                    $query->where('name', 'like', '%'.$request->string('q')->toString().'%')
                        ->orWhere('email', 'like', '%'.$request->string('q')->toString().'%');
                }))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
            'locales' => config('impact.locales.supported', ['en', 'am']),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'managedUser' => $user->load('roles'),
            'roles' => Role::query()->orderBy('name')->get(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function update(
        UpdateUserRequest $request,
        User $user,
        UpdateUserAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action->execute($actor, new UpdateUserData(
            userId: $user->id,
            name: $request->string('name')->toString(),
            email: $request->string('email')->lower()->toString(),
            locale: $request->string('locale')->toString(),
            status: UserStatus::from($request->string('status')->toString()),
            expiresAt: $request->filled('expires_at') ? $request->string('expires_at')->toString() : null,
            roleIds: $request->array('roles'),
            actorId: $actor->id,
            correlationId: $correlation->id(),
        ));

        return redirect()->route('admin.users.edit', $user)->with('status', __('User updated.'));
    }
}
