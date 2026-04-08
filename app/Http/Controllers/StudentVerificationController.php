<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentVerification;
use Illuminate\Support\Facades\Storage;

class StudentVerificationController extends Controller
{
    public function uploadESlip(Request $request)
    {
        $request->validate([
            'e_slip' => 'required|file|mimes:jpg,jpeg,png,pdf'
        ]);

        $path = $request->file('e_slip')->store('e_slips');

        // 🔥 Replace with real OCR later
        $ocrData = [
            'student_number' => auth()->user()->student_number,
            'name' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
            'semester' => currentSemester(),
            'academic_year' => currentAcademicYear()
        ];

        $status = (
            $ocrData['student_number'] == auth()->user()->student_number
        ) ? 'verified' : 'rejected';

        StudentVerification::create([
            'user_id' => auth()->id(),
            'student_number' => auth()->user()->student_number,
            'e_slip_path' => $path,
            'ocr_data' => $ocrData,
            'status' => $status,
            'semester' => currentSemester(),
            'academic_year' => currentAcademicYear(),
            'verified_at' => $status === 'verified' ? now() : null,
        ]);

        if ($status === 'verified') {
            auth()->user()->update([
                'account_status' => 'active'
            ]);
        }

        return response()->json([
            'message' => 'Verification submitted',
            'status' => $status
        ]);
    }
}
