<?php

namespace App\Http\Controllers;

use App\Models\StudentDocumentWorkspace;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentDocumentWorkspaceController extends Controller
{
    public function downloadPdf(StudentDocumentWorkspace $workspace)
    {
        $role = auth()->user()?->role?->role_name;

        abort_unless(
            (int) $workspace->user_id === (int) auth()->id() || $role === 'Admin',
            403
        );
        abort_unless($workspace->generated_pdf_path, 404);
        abort_unless(Storage::disk('public')->exists($workspace->generated_pdf_path), 404);

        $downloadName = Str::slug($workspace->template?->name ?? ('document-' . $workspace->workspace_id)) . '.pdf';

        return Storage::disk('public')->download($workspace->generated_pdf_path, $downloadName);
    }
}
