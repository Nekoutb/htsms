<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Marketing\ConsentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCampaignRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\Marketing\CampaignService;
use App\Services\Marketing\ContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class MarketingController extends Controller
{
    public function index(Request $request, Organization $organization): View
    {
        $this->authorize('manageMarketing', $organization);

        return view('portal.marketing', [
            'organization' => $organization,
            'user' => $this->user($request),
            'contacts' => $organization->contacts()->latest()->paginate(20),
            'campaigns' => $organization->campaigns()->latest()->limit(10)->get(),
            'consentedContacts' => $organization->contacts()
                ->where('consent_status', ConsentStatus::Consented)
                ->orderBy('phone')->get(),
            'contactStats' => [
                'total' => $organization->contacts()->count(),
                'consented' => $organization->contacts()->where('consent_status', ConsentStatus::Consented)->count(),
                'opted_out' => $organization->contacts()->where('consent_status', ConsentStatus::OptedOut)->count(),
            ],
        ]);
    }

    public function storeContact(StoreContactRequest $request, Organization $organization, ContactService $contacts): RedirectResponse
    {
        $this->authorize('manageMarketing', $organization);
        $contacts->upsert($organization, $request->validated());

        return redirect()->route('portal.marketing', $organization)
            ->with('status', 'Contact saved.');
    }

    public function storeCampaign(StoreCampaignRequest $request, Organization $organization, CampaignService $campaigns): RedirectResponse
    {
        $this->authorize('manageMarketing', $organization);
        $contactIds = array_values(array_filter($request->array('contact_ids'), 'is_string'));
        $campaigns->create(
            $organization,
            $request->string('name')->toString(),
            $request->string('content')->toString(),
            $contactIds,
            $request->date('send_at'),
        );

        return redirect()->route('portal.marketing', $organization)
            ->with('status', 'Campaign launched. Consented, non-suppressed contacts are being queued.');
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);

        return $user;
    }
}
