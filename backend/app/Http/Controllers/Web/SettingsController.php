<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Identity\OrganizationRole;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class SettingsController extends Controller
{
    public function show(Request $request, Organization $organization): View
    {
        $this->authorize('view', $organization);
        $user = $this->user($request);

        return view('portal.settings', [
            'organization' => $organization,
            'user' => $user,
            'canManageWorkspace' => $user->can('update', $organization),
            'canDeleteWorkspace' => $user->can('delete', $organization),
            'role' => $organization->memberships()->where('user_id', $user->getKey())->value('role'),
        ]);
    }

    public function updateProfile(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('view', $organization);
        $user = $this->user($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
        ]);
        $user->forceFill(['name' => $data['name']])->save();

        return redirect()->route('portal.settings', $organization)->with('status', __('Profile updated.'));
    }

    public function updateWorkspace(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'locale' => ['required', Rule::in(['en', 'fr'])],
        ]);
        $organization->forceFill($data)->save();
        app()->setLocale($data['locale']);

        return redirect()->route('portal.settings', $organization)->with('status', __('Workspace settings saved.'));
    }

    public function destroyWorkspace(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);
        $request->validate(['confirm' => ['required', 'string']]);
        if ($request->string('confirm')->toString() !== $organization->slug) {
            throw ValidationException::withMessages(['confirm' => [__('Type the workspace ID exactly to confirm deletion.')]]);
        }
        $organization->delete();

        return redirect()->route('portal.home')->with('status', __('Workspace deleted.'));
    }

    public function destroyAccount(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('view', $organization);
        $user = $this->user($request);
        $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages(['password' => [__('That password is incorrect.')]]);
        }

        $blocking = $this->ownedWorkspacesWithOtherMembers($user);
        if ($blocking->isNotEmpty()) {
            throw ValidationException::withMessages(['password' => [
                __('You own workspaces with other members: :names. Transfer or delete them first.', ['names' => $blocking->implode(', ')]),
            ]]);
        }

        // Log out before deleting: logout() cycles the remember token via save(),
        // which would re-insert the user row if it ran after delete().
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        DB::transaction(function () use ($user): void {
            // Solo-owned workspaces (only this user as member) go with the account.
            foreach ($user->organizations()->wherePivot('role', OrganizationRole::Owner->value)->get() as $organization) {
                if ($organization->memberships()->count() === 1) {
                    $organization->delete();
                }
            }
            $user->tokens()->delete();
            $user->memberships()->delete();
            $user->delete();
        });

        return redirect()->route('home')->with('status', __('Your account has been deleted.'));
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    private function ownedWorkspacesWithOtherMembers(User $user)
    {
        return $user->organizations()
            ->wherePivot('role', OrganizationRole::Owner->value)
            ->get()
            ->filter(fn (Organization $organization): bool => $organization->memberships()->count() > 1)
            ->map(fn (Organization $organization): string => $organization->name)
            ->values();
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);

        return $user;
    }
}
