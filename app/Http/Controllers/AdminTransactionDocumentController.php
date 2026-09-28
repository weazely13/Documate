<?php

namespace App\Http\Controllers;

use App\Models\StudentVerification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminTransactionDocumentController extends Controller
{
    public function downloadVerification(StudentVerification $verification)
    {
        abort_unless(auth()->user()?->role?->role_name === 'Admin', 403);
        abort_unless($verification->e_slip_path, 404);
        abort_unless(Storage::disk('public')->exists($verification->e_slip_path), 404);

        $downloadName = 'supporting-document-' . $verification->getKey() . '.' . Str::lower(pathinfo($verification->e_slip_path, PATHINFO_EXTENSION) ?: 'file');

        return Storage::disk('public')->download($verification->e_slip_path, $downloadName);
    }
}
