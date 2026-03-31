<?php

namespace App\Actions\Common;

use App\Models\Branch;

class SwitchBranch
{
    public function execute(int $branchId): void
    {
        // Ensure the branch actually exists
        $branch = Branch::findOrFail($branchId);

        auth()->user()->update([
            'branch_id' => $branch->id,
        ]);
    }

    public function clear(): void
    {
        // Call this when the admin wants to "Exit" the branch and return to Admin Hub
        auth()->user()->update([
            'branch_id' => null,
        ]);
    }
}
