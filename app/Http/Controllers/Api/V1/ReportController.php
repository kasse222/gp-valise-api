<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    use AuthorizesRequests;

    /**
     * 🗂️ Liste des signalements faits par l’utilisateur
     */
    public function index(Request $request)
    {
        $reports = $request->user()->reports()->latest()->paginate(10);
        return ReportResource::collection($reports);
    }

    /**
     * 🔍 Voir un signalement
     */
    public function show(Report $report): ReportResource
    {
        if (Auth::id() !== $report->user_id) {
            abort(403, 'Access denied to this report.');
        }

        return new ReportResource($report);
    }

    /**
     * 🆕 Créer un nouveau signalement
     */
    public function store(StoreReportRequest $request, ReportService $service)
    {
        $report = $service->create($request->user(), $request->validated());

        return (new ReportResource($report->load(['user', 'reportable'])))
            ->response()
            ->setStatusCode(201);
    }
}
