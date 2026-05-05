<?php

namespace App\Http\Controllers\Api;

use App\Actions\OrganizationUpdateAction;
use App\Data\OrganizationData;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\OrganizationResource;
use App\Http\Resources\Api\UserResource;
use App\Models\Organization;
use App\QueryBuilders\OrganizationIndexQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    public function index(OrganizationIndexQuery $query): AnonymousResourceCollection
    {
        return OrganizationResource::collection($query->jsonPaginate());
    }

    public function store(OrganizationData $data, Request $request): JsonResponse
    {
        $organization = Organization::create($data->toModelData());
        $organization->addMember($request->user(), 'admin');

        return OrganizationResource::make($organization)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Organization $organization): OrganizationResource
    {
        return OrganizationResource::make($organization);
    }

    public function update(OrganizationData $data, Organization $organization, OrganizationUpdateAction $action): OrganizationResource
    {
        $organization = $action->execute($data, $organization);

        return OrganizationResource::make($organization);
    }

    public function members(Organization $organization): AnonymousResourceCollection
    {
        $members = $organization->members()->paginate(15);

        return UserResource::collection($members);
    }
}
