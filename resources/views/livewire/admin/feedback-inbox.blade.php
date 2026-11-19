@php
    use App\Models\FeedbackReport;
    use Illuminate\Support\Str;

    $field = 'rounded-lg border-slate-300 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-2 sm:text-sm';
    $name = fn ($u) => $u ? ($u->full_name ?: $u->email) : 'Deleted user';
    // The Workflow review page's focus ring and search field, for the list
    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $wfField = 'rounded-lg border-slate-300 bg-white text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm';

    $loadTargets = 'tab, search, sortBy, nextPage, previousPage, gotoPage';
    $emptyText = $search !== '' ? 'No feedback matches your search.' : ($tab === 'all' ? 'No feedback yet. What users send will appear here.' : 'Nothing here.');
@endphp

<div class="mx-auto max-w-7xl">

    <h1 class="sr-only">Feedback</h1>

    {{-- Summary --}}
    @php
        $tiles = [
            ['Unread', $stats['unread'], 'bx-envelope', '', 'not opened by the office yet'],
            ['Received', $stats['received_30'], 'bx-message-square-dots', '', 'in the last 30 days'],
            ['Replied', $stats['replied_30'], 'bx-reply', '', 'in the last 30 days'],
        ];
    @endphp
    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($tiles as [$label, $value, $icon, $gradient, $hint])
            <div class="{{ \App\Support\SummaryCard::BOX }} {{ \App\Support\SummaryCard::IDLE }}">
                <i class="bx {{ $icon }} {{ \App\Support\SummaryCard::ICON }}" aria-hidden="true"></i>
                <p class="text-2xl font-semibold leading-none tabular-nums">{{ $value }}</p>
                <p class="mt-2 text-sm font-semibold leading-tight sm:text-[15px]">{{ $label }}</p>
                <p class="mt-0.5 hidden truncate text-xs text-slate-600 sm:block">{{ $hint }}</p>
            </div>
        @endforeach
        <div class="{{ \App\Support\SummaryCard::BOX }} {{ \App\Support\SummaryCard::IDLE }}">
            <i class="bx bxs-star {{ \App\Support\SummaryCard::ICON }}" aria-hidden="true"></i>
            <p class="flex items-baseline gap-1.5 text-2xl font-semibold leading-none tabular-nums">
                @if ($stats['ratings'])
                    {{ number_format($stats['avg_rating'], 1) }}<span class="text-sm font-medium">/ 5</span>
                @else
                    —
                @endif
            </p>
            <p class="mt-2 text-sm font-semibold leading-tight sm:text-[15px]">Rating</p>
            <p class="mt-0.5 hidden truncate text-xs text-slate-600 sm:block">{{ $stats['ratings'] }} {{ Str::plural('rating', $stats['ratings']) }} in the last 30 days</p>
        </div>
    </div>

    <div class="space-y-3">
        {{-- One row, as on Workflow review: which feedback to show (a dropdown), then the search --}}
        <div class="flex items-center gap-2">
            <label class="shrink-0">
                <span class="sr-only">Status</span>
                <select wire:model.live="tab" class="h-9 w-36 py-0 text-sm sm:w-44 [@media(pointer:coarse)]:h-11 {{ $wfField }}">
                    @foreach ($tabs as $key => $label)
                        <option value="{{ $key }}">{{ $label }} ({{ number_format($counts[$key]) }})</option>
                    @endforeach
                </select>
            </label>
            <div class="relative min-w-0 flex-1">
                <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400" aria-hidden="true"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search subject, message or person"
                       aria-label="Search feedback" class="h-9 w-full pl-9 pr-3 [@media(pointer:coarse)]:h-11 {{ $wfField }}">
            </div>
        </div>

        {{-- Table: the Workflow review table --}}
        <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white transition-opacity md:block"
             wire:loading.class="opacity-60" wire:loading.attr="aria-busy" wire:target="{{ $loadTargets }}">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-200 text-xs">
                    <tr>
                        <th scope="col" class="w-12 py-2.5 pl-4 pr-1 text-left font-medium text-slate-500">#</th>
                        <x-admin.sort-th key="from" label="From" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5" />
                        <x-admin.sort-th key="subject" label="Subject" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5" />
                        <x-admin.sort-th key="reply" label="Reply" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5" />
                        <x-admin.sort-th key="sent" label="Sent" :sort="$sortKey ?: null" :dir="$sortDir" class="!py-2.5" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reports as $r)
                        @php $active = $selected?->id === $r->id; @endphp
                        <tr wire:key="report-{{ $r->id }}" wire:click="select({{ $r->id }})"
                            class="cursor-pointer transition-colors {{ $active ? 'bg-[#f0f4fb]' : 'hover:bg-slate-50' }}">
                            <td class="py-3 pl-4 pr-1 text-xs tabular-nums text-slate-500">{{ ($reports->firstItem() ?? 1) + $loop->index }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @include('livewire.admin.partials.feedback-avatar', ['u' => $r->user, 'label' => $name($r->user), 'size' => 'h-8 w-8 text-xs'])
                                    <div class="min-w-0">
                                        <button type="button" wire:click.stop="select({{ $r->id }})" aria-expanded="{{ $active ? 'true' : 'false' }}"
                                                class="block max-w-[220px] truncate rounded text-left font-medium text-slate-900 hover:text-[#2A57B4] {{ $focus }}">
                                            {{ $name($r->user) }}
                                        </button>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ $r->user?->role?->role_name ?? 'User' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="flex max-w-[360px] items-center gap-1.5">
                                    @if ($r->isUnread())<span class="h-2 w-2 shrink-0 rounded-full bg-[#2A57B4]" title="Unread"></span><span class="sr-only">Unread:</span>@endif
                                    <span class="truncate {{ $r->isUnread() ? 'font-semibold text-slate-900' : 'text-slate-700' }}" title="{{ $r->subject }}">{{ $r->subject }}</span>
                                </p>
                                <p class="mt-0.5 flex items-center gap-x-2 text-xs text-slate-500">
                                    <span class="truncate">{{ $r->categoryLabel() }}</span>
                                    @if ($r->rating)<span class="inline-flex shrink-0 items-center gap-0.5 text-amber-600"><i class="bx bxs-star" aria-hidden="true"></i>{{ $r->rating }}<span class="sr-only"> out of 5</span></span>@endif
                                    @if ($r->attachment_path)<i class="bx bx-paperclip shrink-0" aria-label="Has an image"></i>@endif
                                </p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($r->isReplied())
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200"><i class="bx bx-reply" aria-hidden="true"></i>Replied</span>
                                    @if ($r->replied_at)<p class="mt-1 text-xs tabular-nums text-slate-500">{{ $r->replied_at->format('M j, Y') }}</p>@endif
                                @else
                                    <span class="text-xs text-slate-400">No reply</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3" title="{{ $r->created_at->format('M j, Y g:i A') }}">
                                <p class="tabular-nums text-slate-700">{{ $r->created_at->format('M j, Y') }}</p>
                                <p class="mt-0.5 text-xs tabular-nums text-slate-500">{{ $r->created_at->format('g:i A') }}</p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center text-sm text-slate-500">{{ $emptyText }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Cards on phones, as on Workflow review: tap one for its details --}}
        <ul class="grid gap-3 transition-opacity md:hidden" aria-label="Feedback"
            wire:loading.class="opacity-60" wire:loading.attr="aria-busy" wire:target="{{ $loadTargets }}">
            @forelse ($reports as $r)
                @php $active = $selected?->id === $r->id; @endphp
                <li wire:key="mreport-{{ $r->id }}">
                    <button type="button" wire:click="select({{ $r->id }})" aria-expanded="{{ $active ? 'true' : 'false' }}"
                            class="flex h-full w-full flex-col rounded-2xl border bg-white p-4 text-left transition-colors {{ $focus }}
                                {{ $active ? 'border-[#2A57B4] bg-[#f0f4fb]' : 'border-slate-200 active:bg-slate-50' }}">
                        <span class="flex w-full items-start gap-3">
                            @include('livewire.admin.partials.feedback-avatar', ['u' => $r->user, 'label' => $name($r->user), 'size' => 'h-10 w-10 text-[13px]'])
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[15px] font-semibold leading-snug text-slate-900">{{ $name($r->user) }}</span>
                                <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $r->user?->role?->role_name ?? 'User' }}</span>
                            </span>
                            @if ($r->isReplied())
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-200"><i class="bx bx-reply" aria-hidden="true"></i>Replied</span>
                            @endif
                        </span>

                        <span class="mt-3 flex items-center gap-1.5 text-sm leading-snug">
                            @if ($r->isUnread())<span class="h-2 w-2 shrink-0 rounded-full bg-[#2A57B4]" aria-hidden="true"></span><span class="sr-only">Unread:</span>@endif
                            <span class="{{ $r->isUnread() ? 'font-semibold text-slate-900' : 'text-slate-700' }}">{{ $r->subject }}</span>
                        </span>

                        <span class="mt-3 flex w-full items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500">
                            <span class="flex min-w-0 items-center gap-2">
                                <span class="truncate">{{ $r->categoryLabel() }}</span>
                                @if ($r->rating)<span class="inline-flex shrink-0 items-center gap-0.5 text-amber-600"><i class="bx bxs-star" aria-hidden="true"></i>{{ $r->rating }}</span>@endif
                                @if ($r->attachment_path)<i class="bx bx-paperclip shrink-0" aria-label="Has an image"></i>@endif
                            </span>
                            <span class="shrink-0 tabular-nums">{{ $r->created_at->format('M j, Y') }}</span>
                        </span>
                    </button>
                </li>
            @empty
                <li class="rounded-2xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500">{{ $emptyText }}</li>
            @endforelse
        </ul>

        @if ($reports->hasPages())
            <div>{{ $reports->links() }}</div>
        @endif
    </div>

    {{-- ================= DETAILS DRAWER ================= --}}
    {{-- Slides in from the right over the page; the backdrop, the close button and Esc slide it out --}}
    @if ($selected)
        <div wire:key="feedback-drawer"
             x-data="{ shown: false, close() { this.shown = false; setTimeout(() => $wire.closePanel(), 200) } }"
             x-init="$nextTick(() => shown = true)"
             @keydown.escape.window="close()">

            <div class="fixed inset-0 z-[60] bg-slate-900/30 transition-opacity duration-200"
                 :class="shown ? 'opacity-100' : 'opacity-0'" @click="close()" aria-hidden="true"></div>

            <aside role="dialog" aria-modal="true" aria-labelledby="fb-drawer-title"
                   class="fixed inset-y-0 right-0 z-[70] flex w-full max-w-md flex-col bg-white shadow-2xl transition-transform duration-200 ease-out sm:max-w-lg"
                   :class="shown ? 'translate-x-0' : 'translate-x-full'">

                <div class="flex items-center justify-between gap-3 border-b border-blue-100 bg-gradient-to-r from-blue-50 to-sky-50 px-5 py-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2A57B4] text-white shadow-sm">
                        <i class="bx bx-message-square-dots text-xl"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 id="fb-drawer-title" class="text-base font-semibold text-slate-900">Feedback details</h2>
                        <p class="text-xs text-slate-400">{{ $selected->reference() }}</p>
                    </div>
                    <button type="button" @click="close()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close details">
                        <i class="bx bx-x text-2xl"></i>
                    </button>
                </div>

                <div class="flex-1 space-y-5 overflow-y-auto px-5 py-4">
                    <dl class="grid grid-cols-[6.5rem_minmax(0,1fr)] items-start gap-x-3 gap-y-3 text-sm">
                        @if ($selected->rating)
                            <dt class="flex items-center gap-2 text-slate-500"><i class="bx bx-star"></i>Rating</dt>
                            <dd class="flex items-center gap-1">
                                <span class="flex text-amber-400" aria-label="{{ $selected->rating }} out of 5">
                                    @for ($i = 1; $i <= 5; $i++)<i class="bx {{ $i <= $selected->rating ? 'bxs-star' : 'bx-star text-slate-300' }}"></i>@endfor
                                </span>
                                <span class="font-semibold text-slate-800">{{ number_format($selected->rating, 1) }}</span>
                            </dd>
                        @endif

                        <dt class="flex items-center gap-2 text-slate-500"><i class="bx bx-user"></i>From</dt>
                        <dd class="flex min-w-0 items-center gap-2">
                            <x-user-avatar :src="$selected->user?->avatarUrl()" :name="$name($selected->user)"
                                class="h-10 w-10 text-xs ring-2 ring-white shadow-sm bg-gradient-to-br from-[#2A57B4] to-sky-400" />
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-slate-800">{{ $name($selected->user) }}</span>
                                <span class="block truncate text-xs text-slate-400">{{ $selected->user?->role?->role_name }}{{ $selected->user?->student_number ? ' · ' . $selected->user->student_number : '' }}{{ $selected->user?->email ? ' · ' . $selected->user->email : '' }}</span>
                            </span>
                        </dd>

                        <dt class="flex items-center gap-2 text-slate-500"><i class="bx bx-purchase-tag"></i>Topic</dt>
                        <dd class="text-slate-700">{{ $selected->categoryLabel() }}</dd>


                        <dt class="flex items-center gap-2 text-slate-500"><i class="bx bx-calendar"></i>Sent</dt>
                        <dd class="text-slate-700">{{ $selected->created_at->format('M j, Y · g:i A') }}</dd>

                        @if ($selected->page_url)
                            <dt class="flex items-center gap-2 text-slate-500"><i class="bx bx-link"></i>Page</dt>
                            <dd class="min-w-0 break-words text-slate-700">
                                @if (Str::startsWith($selected->page_url, url('/')))
                                    <a href="{{ $selected->page_url }}" target="_blank" rel="noopener" class="text-[#2A57B4] hover:underline">{{ Str::after($selected->page_url, url('/')) ?: '/' }}</a>
                                @else
                                    {{ $selected->page_url }}
                                @endif
                            </dd>
                        @endif

                        <dt class="flex items-center gap-2 text-slate-500"><i class="bx bx-detail"></i>Message</dt>
                        <dd class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $selected->subject }}</p>
                            <p class="mt-1 whitespace-pre-line break-words text-slate-700">{{ $selected->message }}</p>
                        </dd>

                        @if ($selected->attachment_path)
                            <dt class="flex items-center gap-2 text-slate-500"><i class="bx bx-image"></i>Media</dt>
                            <dd>
                                <a href="{{ route('feedback.attachment', $selected) }}" target="_blank" rel="noopener" class="group block w-fit">
                                    <img src="{{ route('feedback.attachment', $selected) }}" alt="Attached image" class="h-24 w-24 rounded-lg border border-slate-200 object-cover transition group-hover:opacity-90">
                                    <span class="mt-1 block text-xs text-[#2A57B4] group-hover:underline">Open full size</span>
                                </a>
                            </dd>
                        @endif
                    </dl>

                    {{-- Reply to the user --}}
                    <div class="border-t border-slate-100 pt-4">
                        <label for="fb-reply" class="mb-1.5 flex items-center justify-between text-sm font-medium text-slate-800">
                            <span>Reply to {{ $selected->user?->display_first_name ?? 'user' }} <span class="font-normal text-slate-400">(optional)</span></span>
                            @if ($selected->replied_at)<span class="text-xs font-normal text-slate-400">Sent {{ $selected->replied_at->diffForHumans() }}{{ $selected->handler ? ' by ' . $selected->handler->display_first_name : '' }}</span>@endif
                        </label>
                        <textarea id="fb-reply" rows="4" wire:model="reply" maxlength="3000" class="w-full {{ $field }}"
                                  placeholder="The user sees this on their Feedback page and gets a notification."></textarea>
                        @error('reply') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        <div class="mt-2 flex justify-end">
                            <button type="button" wire:click="sendReply" wire:loading.attr="disabled" wire:target="sendReply"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-[#2A57B4] px-3.5 py-2 text-sm font-semibold text-[#2A57B4] transition hover:bg-[#2A57B4] hover:text-white disabled:opacity-60">
                                <i class="bx bx-send"></i>{{ $selected->admin_reply ? 'Update reply' : 'Send reply' }}
                            </button>
                        </div>
                    </div>

                    {{-- Internal note --}}
                    <div>
                        <label for="fb-note" class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-slate-800">
                            Internal note <span class="inline-flex items-center gap-1 text-xs font-normal text-slate-400"><i class="bx bx-lock-alt"></i>admins only</span>
                        </label>
                        <textarea id="fb-note" rows="3" wire:model="note" wire:blur="saveNote" maxlength="3000"
                                  class="w-full border-dashed bg-slate-50 {{ $field }}" placeholder="Add internal note…"></textarea>
                        <p class="mt-1 text-xs text-slate-400">Saves when you click away.</p>
                    </div>
                </div>

            </aside>
        </div>
    @endif
</div>
