<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\DTO\Identity\CreateOrganizationData;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SubscriptionChangeRequest;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use App\Services\Identity\OrganizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class PlatformAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.index', [
            'organizations' => Organization::query()->with(['subscription'])->withCount(['devices', 'messages'])->latest()->paginate(30),
            'pendingRequests' => SubscriptionChangeRequest::query()->with('organization')->where('status', 'pending')->oldest()->get(),
            'onboardedUsers' => User::query()->whereNotNull('onboarded_by_user_id')
                ->withCount('memberships')->latest()->limit(50)->get(),
        ]);
    }

    public function storeUser(Request $request, OrganizationService $organizations): RedirectResponse
    {
        $admin = $this->admin($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:254', 'unique:users,email'],
            'business_name' => ['required', 'string', 'min:2', 'max:120'],
            'locale' => ['required', 'in:en,fr'],
        ]);
        $user = DB::transaction(function () use ($admin, $data, $organizations): User {
            $user = new User([
                'name' => $data['name'],
                'email' => Str::lower($data['email']),
                'password' => Hash::make(Str::password(48)),
            ]);
            $user->forceFill([
                'email_verified_at' => now(),
                'onboarded_by_user_id' => $admin->getKey(),
            ])->save();
            $organizations->createForOwner(new CreateOrganizationData(
                name: $data['business_name'],
                slug: null,
                timezone: 'Africa/Douala',
                locale: $data['locale'],
            ), $user);

            return $user;
        });
        Password::sendResetLink(['email' => $user->email]);

        return back()->with('status', 'Customer onboarded. A secure password setup link was emailed to them.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        $admin = $this->admin($request);
        abort_unless($user->onboarded_by_user_id === $admin->getKey(), Response::HTTP_FORBIDDEN);
        abort_if($user->is($admin), Response::HTTP_CONFLICT);

        DB::transaction(function () use ($user, $admin): void {
            $soleOrganizations = $user->organizations()->withCount('memberships')->get()
                ->filter(fn (Organization $organization): bool => $organization->memberships_count === 1);
            Organization::query()->whereKey($soleOrganizations->modelKeys())->delete();
            DB::table('developer_api_keys')->where('created_by_user_id', $user->getKey())
                ->update(['created_by_user_id' => $admin->getKey()]);
            DB::table('subscription_change_requests')->where('requested_by_user_id', $user->getKey())
                ->update(['requested_by_user_id' => $admin->getKey()]);
            $user->tokens()->delete();
            $user->delete();
        });

        return back()->with('status', 'Onboarded customer account deleted.');
    }

    public function approve(Request $request, SubscriptionChangeRequest $changeRequest, SubscriptionService $subscriptions): RedirectResponse
    {
        abort_unless($changeRequest->status === 'pending', Response::HTTP_CONFLICT);
        $organization = $changeRequest->organization()->firstOrFail();
        $subscriptions->activate($subscriptions->current($organization), $changeRequest->requested_plan);
        $changeRequest->forceFill([
            'status' => 'approved', 'reviewed_by_user_id' => $this->admin($request)->getKey(),
            'admin_note' => $request->string('note')->limit(1000)->toString(), 'reviewed_at' => now(),
        ])->save();

        return back()->with('status', 'Subscription activated.');
    }

    public function reject(Request $request, SubscriptionChangeRequest $changeRequest): RedirectResponse
    {
        abort_unless($changeRequest->status === 'pending', Response::HTTP_CONFLICT);
        $changeRequest->forceFill([
            'status' => 'rejected', 'reviewed_by_user_id' => $this->admin($request)->getKey(),
            'admin_note' => $request->string('note')->limit(1000)->toString(), 'reviewed_at' => now(),
        ])->save();

        return back()->with('status', 'Plan request rejected.');
    }

    public function pause(Organization $organization): RedirectResponse
    {
        $organization->forceFill(['sending_paused_at' => $organization->sending_paused_at === null ? now() : null])->save();

        return back()->with('status', 'Workspace sending control updated.');
    }

    public function suspend(Organization $organization): RedirectResponse
    {
        $organization->forceFill(['suspended_at' => $organization->suspended_at === null ? now() : null])->save();

        return back()->with('status', 'Workspace suspension updated.');
    }

    public function toggleChannel(Organization $organization, string $channel): RedirectResponse
    {
        $column = $channel.'_enabled';
        $organization->forceFill([$column => ! $organization->{$column}])->save();

        return back()->with('status', ucfirst($channel).' messaging control updated.');
    }

    private function admin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->is_platform_admin, Response::HTTP_FORBIDDEN);

        return $user;
    }
}
