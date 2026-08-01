<?php

namespace App\Http\Controllers;

use App\Models\LogAbsensi;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbsensiPhotoController extends Controller
{
    public function show(LogAbsensi $logAbsensi): StreamedResponse
    {
        abort_unless($logAbsensi->path_foto, 404);

        $disk = $logAbsensi->fotoDisk();
        abort_unless($disk !== null, 404);

        return Storage::disk($disk)->response(
            $logAbsensi->path_foto,
            basename($logAbsensi->path_foto),
            [
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Disposition' => 'inline; filename="'.basename($logAbsensi->path_foto).'"',
            ]
        );
    }
}
