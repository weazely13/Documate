<div>
    @if($selectedTemplate)
        {{-- A paper form, laid out like the online forms. The admin's template preview uses the same view. --}}
        @include('livewire.student.partials.paper-form', [
            'template' => $selectedTemplate,
            'fields' => $fields,
            'formValues' => $formValues,
            'instructions' => $instructions,
            'systemValues' => $this->systemPreviewValues,
            'savedMessage' => $savedMessage,
            'lastSavedAt' => $lastSavedAt,
            'titleCase' => fn ($t) => $this->formatTitle($t),
            'preview' => false,
        ])

    @else

        {{-- ============================================================
            HOME: the sky of the time of day fills the page; the greeting is centered,
            with the form search, the office's busy times and unfinished work below.
        ============================================================= --}}
        @php
            $categories = collect($formItems)->unique('category')->map(fn ($i) => ['key' => $i['category'], 'label' => $i['categoryLabel']])->values();

        @endphp
        <div>
            @include('livewire.partials.verify-account-card')

            @include('livewire.partials.dash.header', [
                'page' => true,
                'name' => $firstName,
                'facts' => $facts,
                'slides' => $slides,
                'picker' => false,
                'inner' => ['livewire.student._home', compact('formItems', 'categories', 'resume', 'appointments', 'signing', 'signingTotal', 'signingRoles')],
            ])
        </div>
    @endif
</div>
