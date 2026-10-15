<?php

namespace App\Http\Controllers;

use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\ItemPhotoStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/** Serves the private item photo to the student, the VPSD admin/VP, or a signatory on that document. */
class ItemPhotoController extends Controller
{
    public function show(StudentDocumentWorkspace $workspace)
    {
        $user = Auth::user();
        $path = $workspace->field_values['item_photo_path'] ?? null;

        $allowed = (int) $workspace->user_id === (int) $user->id
            || $user->isAdmin()
            || $user->isVpsd()
            || $workspace->approvals()->get()->contains(
                fn ($a) => (int) $a->acted_by === (int) $user->id || $a->isAssignedTo($user)
            );

        abort_unless($allowed, 403);
        abort_unless($path && str_starts_with($path, 'item-photos/') && Storage::disk(ItemPhotoStore::DISK)->exists($path), 404);

        return Storage::disk(ItemPhotoStore::DISK)->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}