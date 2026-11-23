<?php

namespace App\Services\Workflow;

use App\Models\College;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

final class SignatoryScope
{
    public const TYPES = ['organization', 'program', 'college', 'year_level'];

    /** Year levels a year-level role can be assigned to (the scope id is the year number). */
    public const YEAR_LEVELS = [1 => '1st year', 2 => '2nd year', 3 => '3rd year', 4 => '4th year', 5 => '5th year'];

    /** Readable name of a scope type: "year level", "organization", ... */
    public static function label(?string $type): string
    {
        return ['organization' => 'organization', 'program' => 'program', 'college' => 'college', 'year_level' => 'year level'][$type] ?? (string) $type;
    }

    /** The student's own organization / program / college id for a scope type. */
    public static function resolve(string $type, User $student): ?int
    {
        $student->loadMissing('program');

        return match ($type) {
            'organization' => $student->organization_id,
            'program' => $student->program_id,
            'college' => $student->program?->college_id,
            'year_level' => ((int) preg_replace('/\D/', '', (string) $student->year_level)) ?: null,
            default => null,
        };
    }

    /** id => name, for the admin dropdowns. */
    public static function options(string $type): array
    {
        return match ($type) {
            'organization' => Organization::active()->orderBy('name')->pluck('name', 'id')->all(),
            'program' => Program::active()->orderBy('name')->pluck('name', 'id')->all(),
            'college' => College::orderBy('code')->get()->mapWithKeys(fn ($c) => [$c->id => "{$c->code} - {$c->name}"])->all(),
            'year_level' => self::YEAR_LEVELS,
            default => [],
        };
    }

    public static function name(?string $type, ?int $id): ?string
    {
        if (! $type || ! $id) {
            return null;
        }

        return match ($type) {
            'organization' => Organization::find($id)?->name,
            'program' => Program::find($id)?->name,
            'college' => College::find($id)?->code,
            'year_level' => self::YEAR_LEVELS[$id] ?? "Year {$id}",
            default => null,
        };
    }
}