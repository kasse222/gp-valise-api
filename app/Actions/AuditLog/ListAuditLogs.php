<?php

declare(strict_types=1);

namespace App\Actions\AuditLog;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListAuditLogs
{
    public function execute(
        ?int $actorId = null,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?string $action = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $perPage = min($perPage, 100);

        return AuditLog::query()
            ->with(['actor'])

            ->when(
                $actorId !== null,
                fn(Builder $q) =>
                $q->where('actor_id', $actorId)
            )

            ->when(
                $auditableType !== null && $auditableType !== '',
                fn(Builder $q) =>
                $q->where('auditable_type', $auditableType)
            )

            ->when(
                $auditableId !== null,
                fn(Builder $q) =>
                $q->where('auditable_id', $auditableId)
            )

            ->when(
                $action !== null && $action !== '',
                fn(Builder $q) =>
                $q->where('action', $action)
            )

            ->when(
                $dateFrom !== null && $dateFrom !== '',
                fn(Builder $q) =>
                $q->whereDate('created_at', '>=', $dateFrom)
            )

            ->when(
                $dateTo !== null && $dateTo !== '',
                fn(Builder $q) =>
                $q->whereDate('created_at', '<=', $dateTo)
            )

            ->latest('created_at')
            ->paginate($perPage);
    }
}
