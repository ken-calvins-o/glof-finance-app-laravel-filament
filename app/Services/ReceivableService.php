<?php

namespace App\Services;

use App\Models\Receivable;
use Illuminate\Support\Facades\DB;

class ReceivableService
{
    protected ReceivableEffectService $effectService;

    public function __construct()
    {
        $this->effectService = new ReceivableEffectService();
    }

    /**
     * Safely revert all effects caused by a receivable and soft-delete it atomically.
     *
     * @param Receivable $receivable
     * @param int|null $actorId
     * @return void
     * @throws \Throwable
     */
    public function safeDelete(Receivable $receivable, ?int $actorId = null): void
    {
        DB::transaction(function () use ($receivable, $actorId) {
            // Revert effects (the effect service is idempotent and will set reverted flag)
            $this->effectService->revertEffectsForReceivable($receivable);

            // Soft-delete the receivable in the same transaction for atomicity
            $receivable->delete();

            // Optionally you can update the latest effect to include actor info (already set in effectService when it runs)
        });
    }

    /**
     * Safe restore (optional): restore receivable and re-apply effects where possible
     */
    public function safeRestore(Receivable $receivable, ?int $actorId = null): void
    {
        DB::transaction(function () use ($receivable, $actorId) {
            $receivable->restore();
            $this->effectService->restoreEffectsForReceivable($receivable);
        });
    }
}

