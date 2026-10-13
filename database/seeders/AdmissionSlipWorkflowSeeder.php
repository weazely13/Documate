<?php

namespace Database\Seeders;

use App\Models\SignatoryRole;
use App\Models\Template;
use App\Models\TemplateSignatoryStep;
use Illuminate\Database\Seeder;

/**
 * Turns the Admission Slip template into a workflow template with its two-step chain:
 *   1. Recommending Approval -> Guidance Counselor
 *   2. Approved              -> Vice President for Student Development
 *
 * Finds the template by name. If yours is named differently, edit the LIKE pattern.
 */
class AdmissionSlipWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SignatoryRoleSeeder::class);

        $template = Template::where('name', 'like', '%admission%slip%')->first();

        if (! $template) {
            $this->command?->warn('No template matching "admission slip" found. Create it in the template editor first, then re-run.');

            return;
        }

        $template->forceFill(['uses_workflow' => true, 'requires_appointment' => false])->save();

        $chain = [
            1 => ['guidance_counselor', 'Recommending Approval'],
            2 => ['vpsd', 'Approved'],
        ];

        foreach ($chain as $order => [$roleKey, $label]) {
            TemplateSignatoryStep::updateOrCreate(
                ['template_id' => $template->template_id, 'step_order' => $order],
                ['signatory_role_id' => SignatoryRole::where('key', $roleKey)->value('id'), 'label' => $label],
            );
        }

        $this->command?->info("Workflow enabled for template #{$template->template_id} ({$template->name}).");
    }
}
