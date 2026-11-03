<div>
    @include('livewire.partials.dash.header', [
        'page' => true,
        'name' => $firstName,
        'facts' => $facts,
        'picker' => false,
        'inner' => ['livewire.signatory._home', compact('reports', 'categories', 'docsByRole', 'assignments', 'waitingByRole', 'waiting', 'oldest', 'hasSignature', 'signing', 'drafts')],
    ])
</div>
