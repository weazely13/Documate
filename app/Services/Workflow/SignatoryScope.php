<?php

namespace App\Services\Workflow;

use App\Models\College;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

final class SignatoryScope
{
    public const TYPES = ['organization', 'program', 'college'];

    /** The student's own organization / program / college id for a scope type. */
    public static function resolve(string $type, User $student): ?int
    {
        $student->loadMissing('program');

        return match ($type) {
            'organization' => $student->organization_id,
            'program' => $student->program_id,
            'college' => $student->program?->college_id,
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
            default => null,
        };
    }
}