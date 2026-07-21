<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SubscriptionChangeRequest;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class PlatformAdminController extends Controller
{
    public function index(): View
    {
        return view('admin.index', [
            'organizations' => Organization::query()->with(['subscription'])->withCount(['devices', 'messages'])->latest()->paginate(30),
            'pendingRequests' => SubscriptionChangeRequest::query()->with('organization')->where('status', 'pending')->oldest()->get(),
        ]);
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

    private function admin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->is_platform_admin, Response::HTTP_FORBIDDEN);

        return $user;
    }
}
