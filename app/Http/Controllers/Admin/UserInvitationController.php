<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Identity\InviteUserAction;
use App\Data\Identity\InviteUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserInvitationRequest;
use App\Models\User;
use App\Support\CorrelationContext;
use Illuminate\Http\RedirectResponse;

final class UserInvitationController extends Controller
{
    public function store(
        StoreUserInvitationRequest $request,
        InviteUserAction $action,
        CorrelationContext $correlation,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action->execute($actor, new InviteUserData(
            name: $request->string('name')->toString(),
            email: $request->string('email')->lower()->toString(),
            locale: $request->string('locale')->toString(),
            roleIds: $request->array('roles'),
            actorId: $actor->id,
            correlationId: $correlation->id(),
        ));

        return back()->with('status', __('Invitation queued for delivery.'));
    }
}
