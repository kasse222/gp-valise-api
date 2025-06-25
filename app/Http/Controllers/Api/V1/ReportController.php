<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use App\Http\Requests\Report\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
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
    public function show(Report $report)
    {
        $this->authorize('view', $report);

        return new ReportResource($report);
    }

    /**
     * 🆕 Créer un nouveau signalement
     */
    public function store(StoreReportRequest $request)
    {
        $report = $request->user()->reports()->create($request->validated());

        return response()->json(new ReportResource($report), 201);
    }
}
