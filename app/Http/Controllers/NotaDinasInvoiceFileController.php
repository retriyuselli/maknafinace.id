<?php

namespace App\Http\Controllers;

use App\Models\NotaDinasDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NotaDinasInvoiceFileController extends Controller
{
    public function view(NotaDinasDetail $notaDinasDetail, Request $request)
    {
        $path = $notaDinasDetail->invoice_file;
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
            Log::warning('Nota dinas invoice file served from public disk', [
                'nota_dinas_detail_id' => $notaDinasDetail->id,
                'path' => $path,
            ]);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $baseName = $notaDinasDetail->invoice_number ?: pathinfo($path, PATHINFO_FILENAME);
        $name = Str::slug((string) $baseName, '_');
        $name = $name !== '' ? $name : 'invoice';
        $fileName = $extension ? "{$name}.{$extension}" : $name;

        $mimeType = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';

        return response()->file(Storage::disk($disk)->path($path), [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }
}
