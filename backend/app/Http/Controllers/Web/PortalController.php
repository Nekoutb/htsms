<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Integration\WebhookEvent;
use App\Domain\Messaging\MessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDeveloperApiKeyRequest;
use App\Http\Requests\Api\V1\StoreMessageRequest;
use App\Http\Requests\Api\V1\StoreOrganizationRequest;
use App\Http\Requests\Api\V1\StoreWebhookEndpointRequest;
use App\Models\DeveloperApiKey;
use App\Models\Device;
use App\Models\Message;
use App\Models\Organization;
use App\Models\User;
use App\Models\WebhookEndpoint;
use App\Services\Gateway\DevicePairingService;
use App\Services\Identity\OrganizationService;
use App\Services\Integration\DeveloperApiKeyService;
use App\Services\Integration\WebhookEndpointService;
use App\Services\Messaging\MessageSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

final class PortalController extends Controller
{
    public function home(Request $request): RedirectResponse|View
    {
        $user = $this->user($request);
        $organization = $user->organizations()->orderBy('name')->first();

        return $organization === null
            ? view('portal.create-organization')
            : redirect()->route('portal.overview', $organization);
    }

    public function createOrganization(StoreOrganizationRequest $request, OrganizationService $organizations): RedirectResponse
    {
        $organization = $organizations->createForOwner($request->toData(), $this->user($request));

        return redirect()->route('portal.overview', $organization)->with('status', 'Workspace created. Pair your first Android phone to begin.');
    }

    public function overview(Request $request, Organization $organization): View
    {
        $this->authorize('view', $organization);
        $messageCount = Message::query()->whereBelongsTo($organization)->count();
        $deliveredCount = Message::query()->whereBelongsTo($organization)->where('status', MessageStatus::Delivered)->count();
        $queuedCount = Message::query()->whereBelongsTo($organization)
            ->whereIn('status', [MessageStatus::Queued, MessageStatus::Assigned])->count();

        return view('portal.overview', $this->context($request, $organization) + [
            'messageCount' => $messageCount,
            'deliveredCount' => $deliveredCount,
            'queuedCount' => $queuedCount,
            'onlineDevices' => $organization->devices()->whereNull('revoked_at')->where('last_seen_at', '>', now()->subMinutes(2))->count(),
            'pairedDevices' => $organization->devices()->whereNull('revoked_at')->count(),
            'activeApiKeys' => $organization->developerApiKeys()->whereNull('revoked_at')->count(),
            'recentMessages' => $organization->messages()->latest()->limit(8)->get(),
        ]);
    }

    public function messages(Request $request, Organization $organization): View
    {
        $this->authorize('view', $organization);

        return view('portal.messages', $this->context($request, $organization) + [
            'messages' => $organization->messages()->latest()->paginate(25),
            'simSlots' => $this->availableSimSlots($organization),
            'inbound' => $organization->inboundMessages()->latest('received_at')->limit(10)->get(),
        ]);
    }

    /**
     * Distinct SIM slots reported by this workspace's live devices, labelled by
     * carrier. Drives the compose SIM picker, which is hidden for single-SIM fleets.
     *
     * @return array<int, string>
     */
    private function availableSimSlots(Organization $organization): array
    {
        $slots = [];
        $devices = $organization->devices()->whereNull('revoked_at')->with('simSlots')->get();
        foreach ($devices as $device) {
            foreach ($device->simSlots as $slot) {
                if ($slot->is_active) {
                    $slots[$slot->slot_index] = $slot->carrier_name ?? ('SIM '.($slot->slot_index + 1));
                }
            }
        }
        ksort($slots);

        return $slots;
    }

    public function send(StoreMessageRequest $request, Organization $organization, MessageSubmissionService $submissions): RedirectResponse
    {
        $this->authorize('view', $organization);
        $scheduledAt = $request->date('send_at');
        $submissions->submit(
            $organization, $request->string('to')->toString(), $request->string('content')->toString(),
            $scheduledAt, $request->date('expires_at'), null, 'dashboard',
            $request->has('sim_slot') && $request->input('sim_slot') !== '' ? $request->integer('sim_slot') : null,
        );

        return redirect()->route('portal.messages', $organization)->with('status', 'Message accepted for delivery.');
    }

    public function devices(Request $request, Organization $organization): View
    {
        $this->authorize('view', $organization);

        return view('portal.devices', $this->context($request, $organization) + [
            'devices' => $organization->devices()->whereNull('revoked_at')->with('simSlots')->latest()->get(),
        ]);
    }

    public function pairingChallenge(Request $request, Organization $organization, DevicePairingService $pairing): RedirectResponse
    {
        $this->authorize('manageDevices', $organization);
        $issued = $pairing->createChallenge($organization, $this->user($request));

        return redirect()->route('portal.devices', $organization)
            ->with('pairing_token', $issued->plainTextToken)
            ->with('pairing_code', $issued->shortCode)
            ->with('pairing_uri', $issued->pairingUri())
            ->with('status', 'Secure pairing QR created. Scan it within 10 minutes; it can be used once.');
    }

    public function revokeDevice(Request $request, Organization $organization, Device $device): RedirectResponse
    {
        $this->authorize('manageDevices', $organization);
        abort_unless($device->organization_id === $organization->getKey(), Response::HTTP_NOT_FOUND);
        DB::transaction(function () use ($device): void {
            $device->credentials()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $device->forceFill(['revoked_at' => now()])->save();
        });

        return back()->with('status', 'Device removed from your account. Message history has been preserved.');
    }

    public function developer(Request $request, Organization $organization): View
    {
        $this->authorize('manageApiKeys', $organization);

        return view('portal.developer', $this->context($request, $organization) + [
            'apiKeys' => $organization->developerApiKeys()->latest()->get(),
            'webhooks' => $organization->webhookEndpoints()->latest()->get(),
            'webhookEvents' => WebhookEvent::cases(),
        ]);
    }

    public function createWebhook(StoreWebhookEndpointRequest $request, Organization $organization, WebhookEndpointService $webhooks): RedirectResponse
    {
        $this->authorize('manageApiKeys', $organization);
        $issued = $webhooks->create(
            $organization, $request->string('name')->toString(),
            $request->string('url')->toString(), $request->eventValues(),
        );

        return redirect()->route('portal.developer', $organization)
            ->with('plain_text_webhook_secret', $issued->plainTextSecret)
            ->with('status', 'Webhook created. Copy the signing secret now; it will not be shown again.');
    }

    public function revokeWebhook(Request $request, Organization $organization, WebhookEndpoint $webhookEndpoint, WebhookEndpointService $webhooks): RedirectResponse
    {
        $this->authorize('manageApiKeys', $organization);
        abort_unless($webhookEndpoint->organization_id === $organization->getKey(), Response::HTTP_NOT_FOUND);
        $webhooks->disable($webhookEndpoint);

        return back()->with('status', 'Webhook endpoint disabled.');
    }

    public function createApiKey(StoreDeveloperApiKeyRequest $request, Organization $organization, DeveloperApiKeyService $apiKeys): RedirectResponse
    {
        $this->authorize('manageApiKeys', $organization);
        $issued = $apiKeys->issue($organization, $this->user($request), $request->toData());

        return redirect()->route('portal.developer', $organization)
            ->with('plain_text_key', $issued->plainTextKey)
            ->with('status', 'API key created. Copy it now; it will not be shown again.');
    }

    public function revokeApiKey(Request $request, Organization $organization, DeveloperApiKey $apiKey, DeveloperApiKeyService $apiKeys): RedirectResponse
    {
        $this->authorize('manageApiKeys', $organization);
        abort_unless($apiKey->organization_id === $organization->getKey(), Response::HTTP_NOT_FOUND);
        $apiKeys->revoke($apiKey);

        return back()->with('status', 'API key revoked.');
    }

    /** @return array{organization: Organization, organizations: Collection<int, Organization>, user: User} */
    private function context(Request $request, Organization $organization): array
    {
        $user = $this->user($request);

        return ['organization' => $organization, 'organizations' => $user->organizations()->orderBy('name')->get(), 'user' => $user];
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);

        return $user;
    }
}
