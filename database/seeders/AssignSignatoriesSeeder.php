<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SignatoryAssignment;
use App\Models\SignatoryRole;
use Illuminate\Database\Seeder;

class AssignSignatoriesSeeder extends Seeder
{
    public function run(): void
    {
        $counselor = User::find(23); // Rujen Andrea Ecaldre — stand-in for Guidance Counselor
        $vp = User::find(28);        // Clarisse Villa — stand-in for VP for Student Development

        if (! $counselor || ! $vp) {
            $this->command?->warn('Counselor or VP account not found — skipping signatory assignment.');
            return;
        }

        SignatoryAssignment::firstOrCreate([
            'user_id' => $counselor->id,
            'signatory_role_id' => SignatoryRole::where('key', 'guidance_counselor')->value('id'),
        ]);

        SignatoryAssignment::firstOrCreate([
            'user_id' => $vp->id,
            'signatory_role_id' => SignatoryRole::where('key', 'vpsd')->value('id'),
        ]);
    }
}