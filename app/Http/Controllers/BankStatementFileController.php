<?php

namespace App\Http\Controllers;

use App\Models\BankStatement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BankStatementFileController extends Controller
{
    public function download(BankStatement $bankStatement, Request $request)
    {
        Gate::authorize('view', $bankStatement);

        $path = $bankStatement->file_path;
        if (! $path) {
            abort(404);
        }

        if (str_contains($path, '..')) {
            abort(400);
        }

        $disk = Storage::disk('private')->exists($path) ? 'private' : (Storage::disk('public')->exists($path) ? 'public' : null);
        if (! $disk) {
            abort(404);
        }

        if ($disk === 'public') {
            Log::warning('Bank statement file served from public disk (pending migration)', [
                'bank_statement_id' => $bankStatement->id,
                'path' => $path,
            ]);
        }

        $name = $bankStatement->original_filename ?: basename($path);

        return response()->download(Storage::disk($disk)->path($path), $name);
    }

    public function downloadReconciliation(BankStatement $bankStatement, Request $request)
    {
        Gate::authorize('view', $bankStatement);

        $path = $bankStatement->reconciliation_file;
        if (! $path) {
            abort(404);
        }

        if (str_contains($path, '..')) {
            abort(400);
        }

        $disk = Storage::disk('private')->exists($path) ? 'private' : (Storage::disk('public')->exists($path) ? 'public' : null);
        if (! $disk) {
            abort(404);
        }

        if ($disk === 'public') {
            Log::warning('Bank reconciliation file served from public disk (pending migration)', [
                'bank_statement_id' => $bankStatement->id,
                'path' => $path,
            ]);
        }

        $name = $bankStatement->reconciliation_original_filename ?: basename($path);

        return response()->download(Storage::disk($disk)->path($path), $name);
    }
}
