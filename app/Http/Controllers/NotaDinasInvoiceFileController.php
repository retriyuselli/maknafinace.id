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
            return response('Invoice tidak ditemukan.', 404);
        }

        $path = ltrim((string) $path, '/');
        if (str_contains($path, '..')) {
            return response('Path invoice tidak valid.', 400);
        }

        if (str_starts_with($path, 'storage/')) {
            $path = (string) Str::of($path)->after('storage/');
        }

        $candidatePaths = array_values(array_unique([
            $path,
            (string) Str::of($path)->after('private/'),
            (string) Str::of($path)->after('public/'),
        ]));

        $candidateDisks = array_values(array_unique(array_filter([
            config('filament.default_filesystem_disk') ?: null,
            config('filesystems.default') ?: null,
            config('filesystems.disks.private') ? 'private' : null,
            config('filesystems.disks.local') ? 'local' : null,
            config('filesystems.disks.public') ? 'public' : null,
            config('filesystems.disks.s3') ? 's3' : null,
        ])));

        $disk = null;
        foreach ($candidateDisks as $tryDisk) {
            foreach ($candidatePaths as $tryPath) {
                if ($tryPath === '') {
                    continue;
                }

                if (Storage::disk($tryDisk)->exists($tryPath)) {
                    $disk = $tryDisk;
                    $path = $tryPath;
                    break 2;
                }
            }
        }

        if (! $disk) {
            return response('Invoice tidak ditemukan.', 404);
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

        $stream = Storage::disk($disk)->readStream($path);
        if (! is_resource($stream)) {
            return response('Invoice tidak ditemukan.', 404);
        }

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }
}
