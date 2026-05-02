<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\AuditLog\ListAuditLogs;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;


class AuditLogController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ListAuditLogs $listAuditLogs,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = $this->listAuditLogs->execute(
            actorId: $request->integer('actor_id') ?: null,
            auditableType: $request->string('auditable_type')->toString() ?: null,
            auditableId: $request->integer('auditable_id') ?: null,
            action: $request->string('action')->toString() ?: null,
            dateFrom: $request->string('date_from')->toString() ?: null,
            dateTo: $request->string('date_to')->toString() ?: null,
            perPage: min($request->integer('per_page', 20), 100),
        );

        return AuditLogResource::collection($logs);
    }

    public function show(AuditLog $auditLog): AuditLogResource
    {
        $this->authorize('view', $auditLog);

        return new AuditLogResource(
            $auditLog->loadMissing('actor')
        );
    }
}
