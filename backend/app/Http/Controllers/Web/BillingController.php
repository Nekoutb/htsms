<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class BillingController extends Controller
{
    public function show(Request $request, Organization $organization, SubscriptionService $subscriptions): View
    {
        $this->authorize('view', $organization);

        return view('portal.billing', [
            'organization' => $organization, 'organizations' => $this->user($request)->organizations()->orderBy('name')->get(),
            'user' => $this->user($request), 'subscription' => $subscriptions->current($organization),
            'plans' => config('htsms.plans'), 'requests' => $organization->subscriptionChangeRequests()->latest()->get(),
        ]);
    }

    public function requestChange(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('manageApiKeys', $organization);
        $data = $request->validate([
            'plan' => ['required', Rule::in(['starter', 'business'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        abort_if($organization->subscriptionChangeRequests()->where('status', 'pending')->exists(), Response::HTTP_CONFLICT, 'A plan request is already pending.');
        $organization->subscriptionChangeRequests()->create([
            'requested_by_user_id' => $this->user($request)->getKey(),
            'requested_plan' => $data['plan'], 'customer_note' => $data['note'] ?? null,
        ]);

        return back()->with('status', 'Plan request submitted. Our team will confirm payment and activation.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);

        return $user;
    }
}
