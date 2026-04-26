<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Http\Resources\LocationResource;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Actions\Location\CreateLocation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LocationController extends Controller
{
    use AuthorizesRequests;


    public function index()
    {
        $locations = Location::with('trip')->ordered()->get();

        return LocationResource::collection($locations);
    }


    public function show(Location $location)
    {
        $this->authorize('view', $location);

        return new LocationResource($location);
    }


    public function store(StoreLocationRequest $request)
    {
        $this->authorize('create', Location::class);

        $location = CreateLocation::execute($request->validated());

        return new LocationResource($location);
    }
}
