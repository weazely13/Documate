{{-- "Something to check": a small pop-up on a page whose sidebar tab has a number, saying what that number
     counts on this page (e.g. on Workflow Review: "52 transactions need your review"). Other pages' numbers stay
     in the sidebar. Closing it keeps it closed until the number changes (per browser tab); otherwise it fades.
     Needs: $navSections (from layouts.app). --}}
@php
    $plural = fn (int $n, string $one, string $many) => $n . ' ' . ($n === 1 ? $one : $many);
    $sayFor = [
        'admin.workflow.index' => fn ($n) => $plural($n, 'transaction needs', 'transactions need') . ' your review',
        'admin.appointments.index' => fn ($n) => $plural($n, 'appointment request is', 'appointment requests are') . ' waiting for approval',
        'admin.templates' => fn ($n) => $plural($n, 'signed paper form needs', 'signed paper forms need') . ' a photo upload',
        'admin.users' => fn ($n) => $plural($n, 'account verification is', 'account verifications are') . ' waiting to be checked',
        'admin.feedback' => fn ($n) => $plural($n, 'new feedback report', 'new feedback reports') . ' to read',
        'student.documents.index' => fn ($n) => $plural($n, 'update', 'updates') . ' on your documents from the office',
        'student.appointments.index' => fn ($n) => $plural($n, 'upcoming visit', 'upcoming visits') . ' to the VPSD office',
        'student.clearance-status' => fn ($n) => 'You are tagged Uncleared this semester',
        'officer.clearance' => fn ($n) => $plural($n, 'member of your organization is', 'members of your organization are') . ' not tagged yet this semester',
        'signatory.inbox' => fn ($n) => $plural($n, 'document is', 'documents are') . ' waiting for your signature',
        'signatory.issued' => fn ($n) => $plural($n, 'report you issued was', 'reports you issued were') . ' sent back for changes',
    ];
    $alerts = [];
    foreach ($navSections as $section) {
        foreach ($section['items'] as [$label, $icon, $routeName, $patterns, $badge]) {
            // Only this page's own tab
            if ($badge > 0 && request()->routeIs(...$patterns)) {
                $alerts[] = [
                    'key' => $routeName . ':' . $badge,
                    'text' => isset($sayFor[$routeName]) ? $sayFor[$routeName]($badge) : $label . ': ' . $badge,
                    'page' => $label, 'icon' => $icon, 'url' => route($routeName),
                ];
            }
        }
    }
    $signature = md5(collect($alerts)->pluck('key')->implode('|'));
@endphp

@if ($alerts)
    <div class="attention-pop" role="status" aria-live="polite"
         x-data="{
            show: false,
            key: 'documate.attention.' + @js($signature),
            close() { this.show = false; try { sessionStorage.setItem(this.key, '1'); } catch (e) {} },
         }"
         x-init="let seen = null; try { seen = sessionStorage.getItem(key); } catch (e) {}
                 if (! seen) {
                     setTimeout(() => { if (! document.getElementById('dm-tour-title')) show = true; }, 700);
                     setTimeout(() => show = false, 14000);
                 }"
         x-on:tour-started.window="show = false"
         x-show="show" x-cloak
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="attention-pop-head">
            <span class="attention-pop-bell" aria-hidden="true"><i class='bx bxs-bell-ring'></i></span>
            <p class="attention-pop-title">Something to check here</p>
            <button type="button" @click="close()" class="attention-pop-close" aria-label="Close"><i class='bx bx-x'></i></button>
        </div>
        <ul class="attention-pop-list">
            @foreach ($alerts as $a)
                <li>
                    <div class="attention-pop-item">
                        <i class='bx {{ $a['icon'] }}' aria-hidden="true"></i>
                        <span class="attention-pop-text">{{ $a['text'] }}.</span>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
