<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Plan\CreatePlan;
use App\Actions\Plan\UpdatePlan;
use Illuminate\Routing\Controller;
use App\Http\Requests\Plan\StorePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Http\Requests\Plan\UpgradePlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use App\Models\User;
use App\Services\PlanService;

class PlanController extends Controller
{
    public function index()
    {
        return PlanResource::collection(Plan::where('is_active', true)->get());
    }

    public function show(Plan $plan)
    {
        return new PlanResource($plan);
    }

    public function store(StorePlanRequest $request)
    {
        $this->authorize('create', Plan::class);
        $plan = CreatePlan::execute($request->validated());
        return new PlanResource($plan);
    }

    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $this->authorize('update', $plan);

        $plan = UpdatePlan::execute($plan, $request->validated());

        return new PlanResource($plan);
    }

    public function destroy(Plan $plan)
    {
        $this->authorize('delete', $plan);
        $plan->delete();
        return response()->json(['message' => 'Plan supprimé.']);
    }

    public function upgradePlan(UpgradePlanRequest $request, User $user, PlanService $service)
    {
        $this->authorize('update', $user);
        $service->upgrade($user, $request->validated('plan_id'));
        return response()->json(['message' => 'Abonnement mis à jour.']);
    }
}
