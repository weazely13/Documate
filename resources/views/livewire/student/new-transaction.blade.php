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
                'plainOnPhone' => true,
                'tour' => ['key' => 'documate.tour.student-home.v3', 'steps' => [
                    ['target' => null, 'tab' => 'overview', 'preview' => null, 'title' => 'Welcome to DocuMate', 'text' => 'Request your VPSD documents online, follow them as they are signed, and download the finished copy. Let us walk through it, then make a sample request together.'],
                    ['target' => 'search', 'tab' => 'overview', 'preview' => null, 'type' => 'admission', 'title' => 'Find a form', 'text' => 'Type what you need, like "admission slip" or "clearance", and pick it from the list. Online forms are signed here; Print forms are filled in here and brought to the VPSD office.'],
                    ['target' => 'resume', 'tab' => 'overview', 'preview' => null, 'title' => 'Pick up where you left off', 'text' => 'Drafts and requests that need your changes wait here. Open one to continue.'],
                    ['target' => 'appointments', 'tab' => 'overview', 'preview' => null, 'title' => 'Your appointments', 'text' => 'Visits to the VPSD office, with your queue number once they are approved.'],
                    ['target' => 'nav-student.home', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Home', 'text' => 'This page: start a new request, and see what you have going on.'],
                    ['target' => 'nav-student.documents.index', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'My Documents', 'text' => 'Every document you have requested: drafts, ones being checked or signed, and finished copies to download.'],
                    ['target' => 'nav-student.appointments.index', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Appointments', 'text' => 'Book a visit to the VPSD office, see your queue number on the day, or reschedule.'],
                    ['target' => 'nav-student.clearance-status', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Clearance Status', 'text' => 'Whether you are cleared each semester, as tagged by your organization officers and the VPSD office.'],
                    ['target' => 'nav-officer.clearance', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Clearance Tagging', 'text' => 'For officers: tag the members of your organization as cleared or not for the semester.'],
                    ['target' => 'nav-signatory.inbox', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Signing Inbox', 'text' => 'Documents waiting for your signature in a role you hold, like an organization adviser.'],
                    ['target' => 'nav-signatory.issue', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Issue a Report', 'text' => 'Fill in a report for a student, then send it for signing.'],
                    ['target' => 'nav-signatory.issued', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Issued Documents', 'text' => 'Reports you have issued, and where each one stands.'],
                    ['target' => 'nav-support.feedback', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Feedback & Issues', 'text' => 'Found a problem or have a suggestion? Send it here and the VPSD office will reply.'],
                    ['target' => 'nav-handbook', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Handbook', 'text' => 'The LNU Student Handbook, to read or search anytime.'],
                    ['target' => 'nav-profile', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Your profile', 'text' => 'Your name, program, photo and e-signature. What is here is what gets printed on your forms, so keep it up to date.'],
                    ['target' => '[aria-label="Notifications"]', 'tab' => 'overview', 'preview' => null, 'title' => 'Notifications', 'text' => 'Updates on your documents and appointments arrive here, so you know when to act.'],
                    ['target' => '#chatbotBtn', 'title' => 'The DocuMate Assistant', 'text' => 'Not sure what to do? Ask the assistant (in English, Tagalog, Bisaya or Waray; it answers in English). It can explain the page you are on, find an office or a person, or check how your requests are going.'],
                    ['target' => 'tour-btn', 'title' => 'See this again anytime', 'text' => 'Tap the question mark to replay this tour.'],
                    ['target' => 'tab-explore', 'tab' => 'overview', 'preview' => null, 'click' => true, 'clickLabel' => 'Open Explore', 'title' => 'Now let\'s make a request', 'text' => 'Explore shows every document you can request. We will open it for you.'],
                    ['target' => 'explore', 'tab' => 'explore', 'preview' => null, 'title' => 'Explore documents', 'text' => 'Every form you can request, grouped by kind. The search box above filters these cards as you type.'],
                    ['target' => 'explore-card', 'tab' => 'explore', 'preview' => null, 'title' => 'One card per form', 'text' => 'Each card shows the whole form. The badge tells you if it is Online (signed here) or Print (brought to the office). Tap a card to preview it or start it.'],
                    ['target' => 'sample-preview', 'tab' => 'explore', 'preview' => 'first', 'title' => 'A sample form', 'text' => 'This is a blank copy of the form with your details, exactly as it prints. Nothing is saved by looking.'],
                    ['target' => 'sample-start', 'tab' => 'explore', 'preview' => 'first', 'click' => true, 'follow' => true, 'clickLabel' => 'Start it', 'title' => 'Start the request', 'text' => 'Tap Start to open the form. We will show you how to fill it in. Nothing is sent until you submit it yourself.'],
                ]],
                'picker' => false,
                'inner' => ['livewire.student._home', compact('formItems', 'categories', 'resume', 'appointments', 'signing', 'signingTotal', 'signingRoles')],
            ])
        </div>
    @endif
</div>
