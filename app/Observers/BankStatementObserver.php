<?php

namespace App\Observers;

use App\Models\BankStatement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class BankStatementObserver
{
    /**
     * Handle the BankStatement "creating" event.
     */
    public function creating(BankStatement $bankStatement): void
    {
        if (Auth::check()) {
            $bankStatement->uploaded_by    = Auth::id();
            $bankStatement->last_edited_by = Auth::id();
        }

        // Catat waktu upload jika belum diset
        if (empty($bankStatement->uploaded_at)) {
            $bankStatement->uploaded_at = Carbon::now();
        }
    }

    /**
     * Handle the BankStatement "updating" event.
     */
    public function updating(BankStatement $bankStatement): void
    {
        if (Auth::check()) {
            $bankStatement->last_edited_by = Auth::id();
        }
    }
}
