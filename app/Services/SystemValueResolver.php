<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SystemValueResolver
{
    /** Resolve one system key for a given user. */
    public function resolve(User $user, string $key): string
    {
        $middleInitial = $user->middle_name ? Str::upper(Str::substr($user->middle_name, 0, 1)) . '.' : null;
        $fullName = trim(implode(' ', array_filter([
            $user->first_name, $middleInitial, $user->last_name, $user->suffix,
        ])));

        $programAbbr = $user->program?->abbreviation
            ?: $this->programAbbreviation((string) ($user->program?->name ?? ''));
        $ordinalYear = $this->ordinalYearLevel((string) ($user->year_level ?? ''));
        $programYearSection = trim(implode(' ', array_filter([
            $programAbbr,
            $ordinalYear ? $ordinalYear . '-' . (string) ($user->section ?? '') : (string) ($user->section ?? ''),
        ])));

        return match ($key) {
            'student_number' => (string) ($user->student_number ?? ''),
            'full_name', 'formatted_full_name' => $fullName,
            'program_year_section' => $programYearSection,
            'program_abbreviation' => $programAbbr,
            'first_name' => (string) ($user->first_name ?? ''),
            'middle_name' => (string) ($user->middle_name ?? ''),
            'last_name' => (string) ($user->last_name ?? ''),
            'suffix' => (string) ($user->suffix ?? ''),
            'sex' => (string) ($user->sex ?? ''),
            'date_of_birth' => $user->date_of_birth ? Carbon::parse($user->date_of_birth)->format('m/d/Y') : '',
            'email' => (string) ($user->email ?? ''),
            'contact_number' => (string) ($user->contact_number ?? ''),
            'college' => (string) ($user->college ?? ''),
            'program' => (string) ($user->program?->name ?? ''),
            'section' => (string) ($user->section ?? ''),
            'organization' => (string) ($user->organization?->name ?? ''),
            'year_level' => (string) ($user->year_level ?? ''),
            'academic_status' => (string) ($user->academic_status ?? ''),
            'current_semester' => function_exists('currentSemester') ? (string) currentSemester() : '',
            'current_academic_year' => function_exists('currentAcademicYear') ? (string) currentAcademicYear() : '',
            default => '',
        };
    }

    /**
     * Build the [field name => value] map the preview engine expects.
     * $fields = a collection of template fields (models) OR arrays with name/source_type/system_key.
     */
    public function mapForFields(User $user, iterable $fields): array
    {
        $map = [];

        foreach ($fields as $f) {
            $sourceType = data_get($f, 'source_type');
            if ($sourceType !== 'system') {
                continue;
            }

            $rawName = data_get($f, 'name');
            $name = $rawName ? Str::slug($rawName, '_') : ('field_' . data_get($f, 'field_id'));

            $map[$name] = $this->resolve($user, (string) data_get($f, 'system_key', ''));
        }

        return $map;
    }

    private function programAbbreviation(string $program): string
    {
        $raw  = trim($program);
        $norm = Str::lower(preg_replace('/[^a-z0-9]+/i', ' ', $raw));
        $norm = trim($norm);

        if ($norm === '') {
            return '';
        }

        // BSED with a major -> "BSED-Science"
        $isBsed = str_contains($norm, 'secondary education') || preg_match('/^bsed\b/', $norm);
        if ($isBsed) {
            $majors = [
                'social studies'   => 'Social Studies',
                'values education' => 'Values Education',
                'mathematics'      => 'Mathematics',
                'math'             => 'Mathematics',
                'filipino'         => 'Filipino',
                'english'          => 'English',
                'science'          => 'Science',
            ];
            // Strip the degree part so "Bachelor of Science..." can't be mistaken for a major
            $afterDegree = preg_replace('/^(bachelor of secondary education|bsed)\b/', '', $norm);
            foreach ($majors as $needle => $label) {
                if (str_contains($afterDegree, $needle)) {
                    return 'BSED-' . $label;
                }
            }
            return 'BSED';
        }

        // Full name (or the abbreviation itself) => abbreviation
        $map = [
            'bachelor of elementary education'               => 'BEED',
            'bachelor of early childhood education'          => 'BECED',
            'bachelor of special needs education'            => 'BSNED',
            'bachelor of technology and livelihood education'=> 'BTLED',
            'bachelor of physical education'                 => 'BPED',
            'teacher certificate program'                    => 'TCP',
            'bachelor of arts in communication'              => 'BA Comm',
            'bachelor of library and information science'    => 'BLIS',
            'bachelor of science in information technology'  => 'BSIT',
            'bachelor of arts in english language'           => 'BAEL',
            'bachelor of arts in political science'          => 'BAPoS',
            'bachelor of science in biology'                 => 'BSBio',
            'bachelor of science in social work'             => 'BSSW',
            'bachelor of science in tourism management'      => 'BSTM',
            'bachelor of science in hospitality management'  => 'BSHM',
            'bachelor of science in entrepreneurship'        => 'BSEntrep',
        ];

        foreach ($map as $full => $abbr) {
            $abbrNorm = Str::lower(preg_replace('/[^a-z0-9]+/i', ' ', $abbr));
            if ($norm === $full || str_starts_with($norm, $full . ' ') || $norm === trim($abbrNorm)) {
                return $abbr;
            }
        }

        // Unknown program: fall back to the old behaviour (the preview/PDF will shrink it to fit)
        return trim($this->programDegreePrefix($raw) . ' ' . $this->normalizedProgramName($raw));
    }
    private function programDegreePrefix(string $program): string
    {
        $normalized = Str::lower(trim($program));

        return match (true) {
            Str::startsWith($normalized, 'master of arts'),
            Str::startsWith($normalized, 'ma ') => 'MA',
            Str::startsWith($normalized, 'master of science'),
            Str::startsWith($normalized, 'ms ') => 'MS',
            Str::startsWith($normalized, 'bachelor of arts'),
            Str::startsWith($normalized, 'ba ') => 'BA',
            default => 'BS',
        };
    }
    private function normalizedProgramName(string $program): string
    {
        $cleaned = trim($program);

        $patterns = [
            '/^bachelor\s+of\s+science\s+in\s+/i',
            '/^bachelor\s+of\s+science\s+/i',
            '/^bs\s+/i',
            '/^bachelor\s+of\s+arts\s+in\s+/i',
            '/^bachelor\s+of\s+arts\s+/i',
            '/^ba\s+/i',
            '/^master\s+of\s+arts\s+in\s+/i',
            '/^master\s+of\s+arts\s+/i',
            '/^ma\s+/i',
            '/^master\s+of\s+science\s+in\s+/i',
            '/^master\s+of\s+science\s+/i',
            '/^ms\s+/i',
        ];

        foreach ($patterns as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned) ?? $cleaned;
        }

        return trim($cleaned);
    }

    private function ordinalYearLevel(string $yearLevel): string
    {
        if (preg_match('/(\d+)/', $yearLevel, $matches)) {
            $number = (int) $matches[1];
        } else {
            $number = match (Str::lower(trim($yearLevel))) {
                'first', 'first year' => 1,
                'second', 'second year' => 2,
                'third', 'third year' => 3,
                'fourth', 'fourth year' => 4,
                'fifth', 'fifth year' => 5,
                default => null,
            };
        }

        if (!$number) {
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