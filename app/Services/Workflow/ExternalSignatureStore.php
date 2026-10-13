<?php

namespace App\Services\Workflow;

use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Turns the canvas drawing from the signing page into an immutable, content-addressed PNG on
 * the private disk (same archive folder SignatureStore uses). The upload is never trusted:
 * it is decoded, size-checked, checked for actual ink, and re-encoded with GD.
 */
class ExternalSignatureStore
{
    private const MAX_BYTES = 300_000;
    private const MAX_PIXELS = 2000;

    /** @return array{0: string, 1: string} [path, sha256] */
    public function fromDataUrl(string $dataUrl): array
    {
        if (! preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $m)) {
            throw new InvalidArgumentException('Please draw your signature in the box.');
        }

        $raw = base64_decode($m[1], true);
        if ($raw === false || $raw === '' || strlen($raw) > self::MAX_BYTES) {
            throw new InvalidArgumentException('That signature could not be read. Clear it and draw again.');
        }

        $info = @getimagesizefromstring($raw);
        if (! $info || $info[2] !== IMAGETYPE_PNG || $info[0] > self::MAX_PIXELS || $info[1] > self::MAX_PIXELS) {
            throw new InvalidArgumentException('That signature could not be read. Clear it and draw again.');
        }

        $img = @imagecreatefromstring($raw);
        if (! $img) {
            throw new InvalidArgumentException('That signature could not be read. Clear it and draw again.');
        }

        [$w, $h] = [imagesx($img), imagesy($img)];

        // Must contain real strokes, not a blank canvas or a single dot.
        $step = max(1, (int) floor(min($w, $h) / 100));
        $ink = 0;
        for ($y = 0; $y < $h; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                if ((((imagecolorat($img, $x, $y)) >> 24) & 0x7F) < 60) {
                    $ink++;
                }
            }
        }
        if ($ink < 25) {
            throw new InvalidArgumentException('Please draw your signature in the box.');
        }

        $scale = min(1, 600 / $w, 200 / $h);
        [$nw, $nh] = [max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))];

        $out = imagecreatetruecolor($nw, $nh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagepng($out);
        $png = (string) ob_get_clean();

        $hash = hash('sha256', $png);
        $path = "signatures/archive/{$hash}.png";
        $disk = Storage::disk(SignatureStore::DISK);

        if (! $disk->exists($path)) {
            $disk->put($path, $png);
        }

        return [$path, $hash];
    }
}