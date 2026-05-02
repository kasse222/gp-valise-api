<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Arr;

class AuditLogIntegrityService
{
    public function seal(AuditLog $log): void
    {
        $previousHash = AuditLog::query()
            ->where('id', '<', $log->id)
            ->latest('id')
            ->value('integrity_hash');

        $integrityHash = $this->computeHashWithPrevious($log, $previousHash);

        AuditLog::query()
            ->whereKey($log->id)
            ->update([
                'previous_hash'  => $previousHash,
                'integrity_hash' => $integrityHash,
            ]);
    }

    public function verifyLog(AuditLog $log): bool
    {
        if ($log->integrity_hash === null) {
            return false;
        }

        return hash_equals(
            $log->integrity_hash,
            $this->computeHashWithPrevious($log, $log->previous_hash)
        );
    }

    public function verifyChainFrom(int $startId = 0): bool
    {
        $previousHash = null;

        $logs = AuditLog::query()
            ->where('id', '>', $startId)
            ->orderBy('id')
            ->cursor();

        foreach ($logs as $log) {
            if ($log->previous_hash !== $previousHash) {
                return false;
            }

            if (! $this->verifyLog($log)) {
                return false;
            }

            $previousHash = $log->integrity_hash;
        }

        return true;
    }

    private function computeHashWithPrevious(AuditLog $log, ?string $previousHash): string
    {
        $payload = [
            'actor_id'       => $log->actor_id,
            'action'         => $log->action,
            'auditable_type' => $log->auditable_type,
            'auditable_id'   => $log->auditable_id,
            'metadata'       => $this->normalizeMetadata($log->metadata),
            'reason'         => $log->reason,
            'created_at'     => $log->created_at?->toIso8601String(),
            'previous_hash'  => $previousHash,
        ];

        return hash(
            'sha256',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function normalizeMetadata(?array $metadata): ?array
    {
        if ($metadata === null) {
            return null;
        }

        return Arr::sortRecursive($metadata);
    }
}
