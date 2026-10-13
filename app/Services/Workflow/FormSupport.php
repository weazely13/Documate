<?php

namespace App\Services\Workflow;

use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Pulls a student's details from their account for the form and the PDF. Takes the student
 * explicitly (never Auth::user()), so it also works when a reviewer or queue worker builds the PDF.
 */
final class FormSupport
{
    public static function systemValue(User $user, string $key, ?StudentDocumentWorkspace $workspace = null): string
    {
        $user->loadMissing('program', 'organization', 'officerRecord.position', 'officerRecord.organization');

        $middleInitial = $user->middle_name ? Str::upper(Str::substr($user->middle_name, 0, 1)) . '.' : null;
        $formattedFullName = trim(implode(' ', array_filter([
            $user->first_name, $middleInitial, $user->last_name, $user->suffix,
        ])));

        $programName = (string) ($user->program?->name ?? '');
        $ordinalYear = self::ordinalYearLevel((string) ($user->year_level ?? ''));
        $programYearSection = trim(implode(' ', array_filter([
            trim(self::programDegreePrefix($programName) . ' ' . self::normalizedProgramName($programName)),
            $ordinalYear
                ? $ordinalYear . ($user->section ? '-' . $user->section : '')
                : (string) ($user->section ?? ''),
        ])));

        return match ($key) {
            'student_number' => (string) ($user->student_number ?? ''),
            'full_name', 'formatted_full_name' => $formattedFullName,
            'program_year_section' => $programYearSection,
            'first_name' => (string) ($user->first_name ?? ''),
            'middle_name' => (string) ($user->middle_name ?? ''),
            'last_name' => (string) ($user->last_name ?? ''),
            'suffix' => (string) ($user->suffix ?? ''),
            'sex' => (string) ($user->sex ?? ''),
            'date_of_birth' => $user->date_of_birth ? Carbon::parse($user->date_of_birth)->format('m/d/Y') : '',
            'email' => (string) ($user->email ?? ''),
            'contact_number' => (string) ($user->contact_number ?? ''),
            'college' => (string) ($user->college ?? ''),
            'program' => $programName,
            'section' => (string) ($user->section ?? ''),
            'organization' => (string) ($user->organization?->name ?? ''),
            'year_level' => (string) ($user->year_level ?? ''),
            'academic_status' => (string) ($user->academic_status ?? ''),
            'current_semester' => function_exists('currentSemester') ? (string) currentSemester() : '',
            'current_academic_year' => function_exists('currentAcademicYear') ? (string) currentAcademicYear() : '',
            // Only exists once the transaction is COMPLETED (step 5 also adds 'signed_at').
            'issued_at' => $workspace?->issued_at?->format('m/d/Y') ?? '',
            'officer_position' => (string) ($user->officerRecord?->position?->title ?? ''),
            'officer_organization' => (string) ($user->officerRecord?->organization?->name ?? ''),
            default => '',
        };
    }

    private static function programDegreePrefix(string $program): string
    {
        $n = Str::lower(trim($program));

        return match (true) {
            Str::startsWith($n, 'master of arts'), Str::startsWith($n, 'ma ') => 'MA',
            Str::startsWith($n, 'master of science'), Str::startsWith($n, 'ms ') => 'MS',
            Str::startsWith($n, 'bachelor of arts'), Str::startsWith($n, 'ba ') => 'BA',
            default => 'BS',
        };
    }

    private static function normalizedProgramName(string $program): string
    {
        $cleaned = trim($program);

        foreach ([
            '/^bachelor\s+of\s+science\s+in\s+/i', '/^bachelor\s+of\s+science\s+/i', '/^bs\s+/i',
            '/^bachelor\s+of\s+arts\s+in\s+/i', '/^bachelor\s+of\s+arts\s+/i', '/^ba\s+/i',
            '/^master\s+of\s+arts\s+in\s+/i', '/^master\s+of\s+arts\s+/i', '/^ma\s+/i',
            '/^master\s+of\s+science\s+in\s+/i', '/^master\s+of\s+science\s+/i', '/^ms\s+/i',
        ] as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned) ?? $cleaned;
        }

        return trim($cleaned);
    }

    private static function ordinalYearLevel(string $yearLevel): string
    {
        if (preg_match('/(\d+)/', $yearLevel, $m)) {
            $number = (int) $m[1];
        } else {
            $number = match (Str::lower(trim($yearLevel))) {
                'first', 'first year' => 1, 'second', 'second year' => 2,
                'third', 'third year' => 3, 'fourth', 'fourth year' => 4,
                'fifth', 'fifth year' => 5, default => null,
            };
        }

        if (! $number) {
            return trim($yearLevel);
        }

        $suffix = match (true) {
            $number % 100 >= 11 && $number % 100 <= 13 => 'th',
            $number % 10 === 1 => 'st',
            $number % 10 === 2 => 'nd',
            $number % 10 === 3 => 'rd',
            default => 'th',
        };

        return $number . $suffix;
    }
}