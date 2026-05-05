<?php

namespace App\Http\Controllers\Api;

use App\Actions\OrganizationUpdateAction;
use App\Data\OrganizationData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrganizationResource;
use App\Http\Resources\Api\UserResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $organizations = $request->user()->organizations()
            ->latest()
            ->paginate($request->input('per_page', 15));

        return OrganizationResource::collection($organizations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $organization = Organization::create($validated);
        $organization->addMember($request->user(), 'admin');

        return OrganizationResource::make($organization)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Organization $organization): OrganizationResource
    {
        return OrganizationResource::make($organization);
    }

    public function update(Request $request, Organization $organization, OrganizationUpdateAction $action): OrganizationResource
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        // Merge with existing data for partial updates
        $data = array_merge($organization->toArray(), $validated);

        $organization = $action->execute(
            OrganizationData::from($data),
            $organization
        );

        return OrganizationResource::make($organization);
    }

    public function members(Organization $organization): AnonymousResourceCollection
    {
        $members = $organization->members()->paginate(15);

        return UserResource::collection($members);
    }
}
