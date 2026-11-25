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
                    ['target' => null, 'tab' => 'overview', 'preview' => null, 'title' => 'Welcome to DocuMate', 'text' => 'Request your VPSD documents online, track their progress as they go through the signing process, and download the completed copy. Let us walk you through the process and create a request together.'],
                    ['target' => 'search', 'tab' => 'overview', 'preview' => null, 'type' => 'admission', 'title' => 'Find a form', 'text' => 'Type what you need, e.g. "admission slip", and select it from the list. Online forms are signed here. While Print forms are filled out here and brought to the VPSD office.'],
                    ['target' => 'resume', 'tab' => 'overview', 'preview' => null, 'title' => 'Pick up where you left off', 'text' => 'Drafts and requests that needs your changes show up here. Open one to continue editing.'],
                    ['target' => 'appointments', 'tab' => 'overview', 'preview' => null, 'title' => 'Your appointments', 'text' => 'Visit the VPSD office with your queue number once they have been approved.'],
                    ['target' => 'nav-student.home', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Home', 'text' => 'Start a new request and keep track of your ongoing documents, all in one place.'],
                    ['target' => 'nav-student.documents.index', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'My Documents', 'text' => 'Shows all of your requested documents in one place, from drafts and documents that are under review and signing to completed copies ready for download.'],
                    ['target' => 'nav-student.appointments.index', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Appointments', 'text' => 'Book a visit to the VPSD office, see your queue number on the day, or reschedule.'],
                    ['target' => 'nav-student.clearance-status', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Clearance Status', 'text' => 'Shows your clearance status each semester.'],
                    ['target' => 'nav-officer.clearance', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Clearance Tagging', 'text' => 'Your respective organization officers tags your clearance status each semester.'],
                    ['target' => 'nav-signatory.inbox', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Signing Inbox', 'text' => 'Documents waiting for your signature in a role you hold, like an organization adviser.'],
                    ['target' => 'nav-signatory.issue', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Issue a Report', 'text' => 'Fill out a report for a student, then send it for signing.'],
                    ['target' => 'nav-signatory.issued', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Issued Documents', 'text' => 'Reports you have issued, and where each one stands.'],
                    ['target' => 'nav-support.feedback', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Feedback', 'text' => 'Have a suggestion or a compliment? Send it here and the VPSD office will reply.'],
                    ['target' => 'nav-handbook', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Handbook', 'text' => 'Access the LNU Student Handbook anytime to read or search for information.'],
                    ['target' => 'nav-profile', 'sidebar' => true, 'tab' => 'overview', 'preview' => null, 'title' => 'Your profile', 'text' => 'Your name, program, photo and e-signature. What is here is what gets printed on your forms, so keep it up to date.'],
                    ['target' => '[aria-label="Notifications"]', 'tab' => 'overview', 'preview' => null, 'title' => 'Notifications', 'text' => 'Get updates on your documents and appointments so you know when action is needed.'],
                    ['target' => '#chatbotBtn', 'title' => 'The DocuMate Assistant', 'text' => 'Not sure what to do? Ask the assistant (in English, Tagalog, Bisaya or Waray; it answers in English). It can explain the page you are on, find an office or a person, or check how your requests are going.'],
                    ['target' => 'tour-btn', 'title' => 'See this again anytime', 'text' => 'Tap the question mark to replay this tour.'],
                    ['target' => 'tab-explore', 'tab' => 'overview', 'preview' => null, 'click' => true, 'clickLabel' => 'Open Explore', 'title' => 'Now let\'s make a request', 'text' => 'Explore shows every document you can request. We will open it for you.'],
                    ['target' => 'explore', 'tab' => 'explore', 'preview' => null, 'title' => 'Explore documents', 'text' => 'Browse all available forms, organized by category. Use the search box above to quickly find the form you need.'],
                    ['target' => 'explore-card', 'tab' => 'explore', 'preview' => null, 'title' => 'One card per form', 'text' => 'Each card displays the full form. The badge shows whether it is available online (complete and sign through DocuMate) or requires printing and submission to the office. Select a card to preview the form or start your request.'],
                    ['target' => 'sample-preview', 'tab' => 'explore', 'preview' => 'first', 'title' => 'A sample form', 'text' => 'Here is how your form will look when printed, with your details filled in. Viewing it will not save or submit your request.'],
                    ['target' => 'sample-start', 'tab' => 'explore', 'preview' => 'first', 'click' => true, 'follow' => true, 'clickLabel' => 'Start it', 'title' => 'Start the request', 'text' => 'Tap Start to open the form. We will show you how to fill it out. Nothing is sent until you submit it yourself.'],
                ]],
                'picker' => false,
                'inner' => ['livewire.student._home', compact('formItems', 'categories', 'resume', 'appointments', 'signing', 'signingTotal')],
            ])
        </div>
    @endif
</div>
