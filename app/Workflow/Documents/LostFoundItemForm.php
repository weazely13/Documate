<?php

namespace App\Workflow\Documents;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** F-SDM-012 Rev. 1 (03-31-23): Lost/Found Item Form. */
final class LostFoundItemForm extends WorkflowDocument
{
    public function category(): \App\Enums\DocumentCategory { return \App\Enums\DocumentCategory::Reports; }

    private const KINDS = ['Lost', 'Found'];

    public function key(): string
    {
        return 'lost_found_item';
    }

    public function name(): string
    {
        return 'Lost/Found Item Form';
    }

    public function description(): string
    {
        return 'Report an item you lost, or turn over an item you found, to the VPSD office.';
    }

    public function formCode(): string
    {
        return 'F-SDM-012 Rev. 1 (03-31-23)';
    }

    public function fields(): array
    {
        return [
            'item_kind' => [
                'label' => 'I am reporting an item that I', 'type' => 'select', 'required' => true,
                'options' => self::KINDS,
                'rules' => [Rule::in(self::KINDS)],
            ],
            'item_description' => [
                'label' => 'Name and description of the item', 'type' => 'textarea', 'required' => true, 'max' => 400,
                'help' => 'Include color, brand, markings and where it was lost or found.',
            ],
        ];
    }

    public function requiresStudentSignature(): bool
    {
        return true;
    }

    public function requiresItemPhoto(): bool
    {
        return true;
    }

    public function chain(array $values = []): array
    {
        return [];
    }

    public function studentNote(): ?string
    {
        return 'Take a clear photo of the item. The photo is kept by the VPSD office to identify the item. It is not printed on the form.';
    }

    public function pdfView(): string
    {
        return 'pdf.lost-found-item';
    }
    public function afterReviewNote(array $values): ?string
    {
        return ($values['item_kind'] ?? '') === 'Found'
            ? 'The VPSD office has reviewed your report. Please bring the item to the VPSD office to turn it over, and bring your school ID.'
            : 'The VPSD office has reviewed your report. If your item is turned in, you will be able to claim it at the VPSD office. Bring your school ID.';
    }

    public function summary(array $values): string
    {
        return collect([$values['item_kind'] ?? null, Str::limit((string) ($values['item_description'] ?? ''), 50)])
            ->filter()->implode(': ');
    }
}