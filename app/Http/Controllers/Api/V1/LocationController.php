<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Location\CreateLocation;
use Illuminate\Routing\Controller;
use App\Http\Requests\Location\StoreLocationRequest;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        return LocationResource::collection(Location::all());
    }

    public function show(Location $location)
    {
        return new LocationResource($location);
    }

    public function store(StoreLocationRequest $request)
    {
        $this->authorize('create', Location::class);

        $location = CreateLocation::execute($request->validated());

        return new LocationResource($location);
    }
}
