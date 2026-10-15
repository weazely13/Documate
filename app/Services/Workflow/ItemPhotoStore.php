<?php

namespace App\Services\Workflow;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ItemPhotoStore
{
    public const DISK = 'local';
    private const MAX_SIDE = 1280;

    /** @return array{0: string, 1: string} [path, sha256] */
    public function store(UploadedFile $file): array
    {
        $raw = (string) file_get_contents($file->getRealPath());
        $info = @getimagesizefromstring($raw);

        if (! $info || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            throw new InvalidArgumentException('Upload a JPG or PNG photo of the item.');
        }
        if ($info[0] > 8000 || $info[1] > 8000) {
            throw new InvalidArgumentException('That photo is too large.');
        }

        $img = @imagecreatefromstring($raw);
        if (! $img) {
            throw new InvalidArgumentException('That file could not be read as an image.');
        }

        [$w, $h] = [imagesx($img), imagesy($img)];
        $scale = min(1, self::MAX_SIDE / max($w, $h));
        [$nw, $nh] = [max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))];

        $out = imagecreatetruecolor($nw, $nh);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        // Prefer JPEG (smaller). Fall back to PNG if this PHP's GD has no JPEG encoder.
        ob_start();
        if (function_exists('imagejpeg')) {
            imagejpeg($out, null, 82);
            $ext = 'jpg';
        } else {
            imagepng($out, null, 6);
            $ext = 'png';
        }
        $bytes = (string) ob_get_clean();

        if ($bytes === '') {
            throw new InvalidArgumentException('That photo could not be processed.');
        }

        $hash = hash('sha256', $bytes);
        $path = "item-photos/{$hash}.{$ext}";
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($path)) {
            $disk->put($path, $bytes);
        }

        return [$path, $hash];
    }
}