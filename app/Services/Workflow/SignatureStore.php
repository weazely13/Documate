<?php

namespace App\Services\Workflow;

use App\Exceptions\Workflow\WorkflowException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use App\Services\Workflow\ExternalSignatureStore;

/**
 * Signature images live on the PRIVATE disk only, never under /storage or /public.
 *  - storeFor():  validates the upload, re-encodes it with GD (strips metadata and any
 *                 embedded payload), names the file by its SHA-256, never overwrites.
 *  - archive():   called at signing time. Copies the image to an immutable, content-addressed
 *                 location so the approval keeps the exact image that was used.
 */
class SignatureStore
{
    public const DISK = 'local';
    private const MAX_PIXELS = 4000;

    public function storeFor(User $user, UploadedFile $file): string
    {
        $raw = (string) file_get_contents($file->getRealPath());
        $info = @getimagesizefromstring($raw);

        if (! $info || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            throw new InvalidArgumentException('Upload a PNG or JPG image of your signature.');
        }

        if ($info[0] > self::MAX_PIXELS || $info[1] > self::MAX_PIXELS) {
            throw new InvalidArgumentException('That image is too large. Use one under 4000 px wide.');
        }

        $img = @imagecreatefromstring($raw);
        if (! $img) {
            throw new InvalidArgumentException('That file could not be read as an image.');
        }

        [$w, $h] = [imagesx($img), imagesy($img)];
        $scale = min(1, 800 / $w, 300 / $h);
        [$nw, $nh] = [max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))];

        $out = imagecreatetruecolor($nw, $nh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 255, 255, 255, 127));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagepng($out);
        $png = (string) ob_get_clean();

        $path = "signatures/{$user->id}/" . hash('sha256', $png) . '.png';
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($path)) {
            $disk->put($path, $png);
        }

        // Old files are kept on purpose: past approvals reference them.
        $user->forceFill(['signature_path' => $path])->save();

        return $path;
    }
    /** Saves a signature drawn on the pad (PNG data URL). Validated and re-encoded by ExternalSignatureStore. */
    public function storeFromDataUrl(User $user, string $dataUrl): string
    {
        [$archivePath, ] = app(ExternalSignatureStore::class)->fromDataUrl($dataUrl);

        $disk = Storage::disk(self::DISK);
        $bytes = $disk->get($archivePath);
        $path = "signatures/{$user->id}/" . hash('sha256', $bytes) . '.png';

        if (! $disk->exists($path)) {
            $disk->put($path, $bytes);
        }

        // Old files are kept on purpose: past approvals reference them.
        $user->forceFill(['signature_path' => $path])->save();

        return $path;
    }

    /** @return array{0: string, 1: string} [archived path, sha256] */
    public function archive(string $path): array
    {
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($path)) {
            throw WorkflowException::signatureMissing();
        }

        $bytes = $disk->get($path);
        $hash = hash('sha256', $bytes);
        $target = "signatures/archive/{$hash}.png";

        if (! $disk->exists($target)) {
            $disk->put($target, $bytes);
        }

        return [$target, $hash];
    }
}