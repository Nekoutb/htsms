<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Resources\Api\V1\ContactResource;
use App\Models\Contact;
use App\Models\Organization;
use App\Services\Marketing\ContactService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ContactController extends Controller
{
    public function __construct(private readonly ContactService $contacts) {}

    public function index(Request $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        abort_unless($organization instanceof Organization, Response::HTTP_UNAUTHORIZED);

        return ContactResource::collection(Contact::query()->whereBelongsTo($organization)->latest()->paginate(50))->response();
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $organization = $request->attributes->get('organization');
        abort_unless($organization instanceof Organization, Response::HTTP_UNAUTHORIZED);
        $contact = $this->contacts->upsert($organization, $request->validated());

        return (new ContactResource($contact))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
