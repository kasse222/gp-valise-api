<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLuggageRequest;
use App\Http\Requests\UpdateLuggageRequest;
use App\Models\Luggage;

class LuggageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLuggageRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Luggage $luggage)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLuggageRequest $request, Luggage $luggage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Luggage $luggage)
    {
        //
    }
}
