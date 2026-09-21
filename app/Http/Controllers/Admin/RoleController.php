<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Identity\UpdateRoleAction;
use App\Data\Identity\UpdateRoleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\CorrelationContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::query()->withCount('users')->with('permissions:id,code')->orderBy('name')->get(),
        ]);
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'managedRole' => $role->load('permissions'),
            'permissions' => Permission::query()->orderBy('code')->get()->groupBy(
                fn (Permission $permission): string => str($permission->code)->before('.')->toString(),
            ),
        ]);
    }

    public function update(
        UpdateRoleRequest $request,
        Role $role,
        UpdateRoleAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action->execute($actor, new UpdateRoleData(
            roleId: $role->id,
            name: $request->string('name')->toString(),
            permissionIds: $request->collect('permissions')
                ->map(static fn (mixed $id): string => (string) $id)
                ->values()
                ->all(),
            actorId: $actor->id,
            correlationId: $correlation->id(),
        ));

        return redirect()->route('admin.roles.edit', $role)->with('status', __('Role updated.'));
    }
}
