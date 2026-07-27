<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AbsensiFotoCompressor
{
    /** Lebar/tinggi maksimal (px) — cukup untuk bukti wajah, hemat storage. */
    public const MAX_SIDE = 1280;

    /** Kualitas JPEG 0–100. */
    public const JPEG_QUALITY = 75;

    /**
     * Kompres lalu simpan ke disk. Fallback ke upload asli jika GD gagal.
     */
    public function store(UploadedFile $foto, string $directory, string $disk = 'private'): string
    {
        $jpeg = $this->compressToJpeg($foto);

        if ($jpeg === null) {
            return $foto->store($directory, $disk);
        }

        $path = trim($directory, '/').'/'.Str::uuid()->toString().'.jpg';
        Storage::disk($disk)->put($path, $jpeg);

        return $path;
    }

    public function compressToJpeg(UploadedFile $foto): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }

        $binary = @file_get_contents($foto->getRealPath());
        if ($binary === false || $binary === '') {
            return null;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return null;
        }

        $source = $this->applyExifOrientation($source, $foto->getRealPath());

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($source);

            return null;
        }

        $scale = min(1, self::MAX_SIDE / max($srcW, $srcH));
        $dstW = (int) max(1, round($srcW * $scale));
        $dstH = (int) max(1, round($srcH * $scale));

        $canvas = imagecreatetruecolor($dstW, $dstH);
        if ($canvas === false) {
            imagedestroy($source);

            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $dstW, $dstH, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        ob_start();
        $ok = imagejpeg($canvas, null, self::JPEG_QUALITY);
        $jpeg = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        if (! $ok || $jpeg === false || $jpeg === '') {
            return null;
        }

        return $jpeg;
    }

    /**
     * @param  \GdImage|resource  $source
     * @return \GdImage|resource
     */
    protected function applyExifOrientation($source, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $source;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        return match ($orientation) {
            3 => $this->rotate($source, 180),
            6 => $this->rotate($source, -90),
            8 => $this->rotate($source, 90),
            default => $source,
        };
    }

    /**
     * @param  \GdImage|resource  $source
     * @return \GdImage|resource
     */
    protected function rotate($source, int $angle)
    {
        $rotated = imagerotate($source, $angle, 0);
        if ($rotated === false) {
            return $source;
        }

        imagedestroy($source);

        return $rotated;
    }
}
