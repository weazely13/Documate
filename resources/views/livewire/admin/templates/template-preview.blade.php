{{-- Admin › Templates › Student preview: the student's paper form view, with sample profile details. --}}
<div>
    @include('livewire.student.partials.paper-form', [
        'template' => $template,
        'fields' => $fields,
        'formValues' => $formValues,
        'instructions' => $instructions,
        'systemValues' => $systemValues,
        'savedMessage' => null,
        'lastSavedAt' => null,
        'titleCase' => $titleCase,
        'preview' => true,
        'previewBack' => route('admin.templates.editor', $template['template_id']),
        'previewStatus' => $template['status'] === 'active' ? 'public' : 'private, students cannot see it yet',
    ])
</div>
