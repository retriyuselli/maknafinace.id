<?php

namespace App\Http\Controllers;

use App\Models\LogAbsensi;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AbsensiPhotoController extends Controller
{
    public function show(LogAbsensi $logAbsensi): Response
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
