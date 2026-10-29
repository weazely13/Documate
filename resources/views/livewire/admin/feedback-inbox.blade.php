@php
    use App\Models\FeedbackReport;
    use Illuminate\Support\Str;

    $field = 'rounded-lg border-slate-300 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-2 sm:text-sm';
    $name = fn ($u) => $u ? ($u->full_name ?: $u->email) : 'Deleted user';
    $severityDot = ['low' => 'bg-slate-400', 'medium' => 'bg-amber-500', 'high' => 'bg-rose-500'];
@endphp

<div class="mx-auto max-w-7xl">

    <x-admin.page-header title="Feedback & Issues"
        description="What students, officers and signatories have told the office about DocuMate. Reply, keep private notes, and resolve." />

    {{-- Summary --}}
    @php
        $tiles = [
            ['New', $stats['new'], 'bx-envelope', 'from-[#2A57B4] to-[#3b6fd8]', 'waiting for a first look'],
            ['Open issues', $stats['open_issues'], 'bx-error-circle', 'from-rose-500 to-rose-400', 'reported problems not yet resolved'],
            ['Resolved', $stats['resolved_30'], 'bx-check-double', 'from-emerald-500 to-emerald-400', 'in the last 30 days'],
        ];
    @endphp
    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ($tiles as [$label, $value, $icon, $gradient, $hint])
            <div class="relative overflow-hidden rounded-xl bg-gradient-to-br {{ $gradient }} p-4 text-white shadow-sm">
                <i class="bx {{ $icon }} pointer-events-none absolute -bottom-3 -right-2 text-6xl text-white/20" aria-hidden="true"></i>
                <p class="text-xs font-medium text-white/85">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums">{{ $value }}</p>
                <p class="mt-0.5 hidden text-[11px] text-white/75 sm:block">{{ $hint }}</p>
            </div>
        @endforeach
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-amber-400 to-orange-400 p-4 text-amber-950 shadow-sm">
            <i class="bx bxs-star pointer-events-none absolute -bottom-3 -right-2 text-6xl text-white/30" aria-hidden="true"></i>
            <p class="text-xs font-medium text-amber-950/80">Average rating</p>
            <p class="mt-1 flex items-baseline gap-1.5 text-2xl font-semibold tabular-nums">
                @if ($stats['ratings'])
                    {{ number_format($stats['avg_rating'], 1) }}<span class="text-sm font-medium">/ 5</span>
                @else
                    —
                @endif
            </p>
            <p class="mt-0.5 hidden text-[11px] text-amber-950/70 sm:block">{{ $stats['ratings'] }} {{ Str::plural('rating', $stats['ratings']) }} in the last 30 days</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-4 border-b border-slate-200">
        <nav class="-mb-px flex gap-6 overflow-x-auto whitespace-nowrap" aria-label="Report status">
            @foreach ($tabs as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')" wire:key="tab-{{ $key }}"
                        @if ($tab === $key) aria-current="page" @endif
                        class="inline-flex items-center gap-2 border-b-2 px-1 pb-3 text-sm font-medium transition
                            {{ $tab === $key ? 'border-[#2A57B4] text-[#2A57B4]' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' }}">
                    {{ $label }}
                    <span class="rounded-full px-1.5 text-[11px] font-medium tabular-nums {{ $tab === $key ? 'bg-[#2A57B4]/10 text-[#2A57B4]' : 'bg-slate-100 text-slate-500' }}">{{ $counts[$key] }}</span>
                </button>
            @endforeach
        </nav>
    </div>

    <div>

        {{-- ================= LIST ================= --}}
        <div class="min-w-0">
            <div class="mb-3 flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search subject, message, or person"
                           aria-label="Search reports" class="w-full pl-9 pr-3 {{ $field }}">
                </div>
                <div class="grid grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1 sm:w-auto" role="radiogroup" aria-label="Type">
                    @foreach (['' => 'All', 'feedback' => 'Feedback', 'issue' => 'Issues'] as $key => $text)
                        <button type="button" wire:click="$set('kind', '{{ $key }}')" role="radio" aria-checked="{{ $kind === $key ? 'true' : 'false' }}"
                                class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $kind === $key ? ['' => 'bg-slate-800 text-white', 'feedback' => 'bg-[#2A57B4] text-white', 'issue' => 'bg-rose-600 text-white'][$key] . ' shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">{{ $text }}</button>
                    @endforeach
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                {{-- Column labels (md and up) --}}
                <div class="hidden grid-cols-[minmax(0,1.3fr)_minmax(0,2fr)_7rem_6.5rem] gap-4 border-b border-blue-100 bg-blue-50/60 px-4 py-2.5 text-xs font-semibold text-[#2A57B4] md:grid">
                    <span>From</span><span>Subject</span><span>Status</span><span class="text-right">Sent</span>
                </div>

                @forelse ($reports as $r)
                    @php $active = $selected?->id === $r->id; @endphp
                    <button type="button" wire:key="report-{{ $r->id }}" wire:click="select({{ $r->id }})"
                            @if ($active) aria-current="true" @endif
                            class="relative grid w-full grid-cols-[2.25rem_minmax(0,1fr)] gap-3 border-b border-slate-100 px-4 py-3 text-left transition last:border-b-0 md:grid-cols-[minmax(0,1.3fr)_minmax(0,2fr)_7rem_6.5rem] md:items-center md:gap-4
                                {{ $active ? 'bg-blue-50/70' : 'hover:bg-slate-50' }}">
                        <span class="absolute inset-y-0 left-0 w-1 {{ $active ? 'bg-[#2A57B4]' : ($r->type === 'issue' ? ['high' => 'bg-rose-500', 'medium' => 'bg-amber-400'][$r->severity] ?? 'bg-rose-200' : 'bg-blue-200') }}" aria-hidden="true"></span>

                        {{-- From --}}
                        <span class="flex min-w-0 items-center gap-2.5 max-md:contents">
                            <x-user-avatar :src="$r->user?->avatarUrl()" :name="$name($r->user)"
                                class="h-9 w-9 text-xs ring-2 {{ $r->type === 'issue' ? 'bg-gradient-to-br from-rose-500 to-orange-400 ring-rose-100' : 'bg-gradient-to-br from-[#2A57B4] to-sky-400 ring-blue-100' }}" />
                            <span class="hidden min-w-0 md:block">
                                <span class="block truncate text-sm font-medium text-slate-900">{{ $name($r->user) }}</span>
                                <span class="block truncate text-xs text-slate-400">{{ $r->user?->role?->role_name ?? 'User' }}</span>
                            </span>
                        </span>

                        {{-- Subject --}}
                        <span class="min-w-0">
                            <span class="flex items-center gap-1.5">
                                @if ($r->status === 'new')<span class="h-2 w-2 shrink-0 rounded-full bg-[#2A57B4]" aria-label="New"></span>@endif
                                <span class="truncate text-sm {{ $r->status === 'new' ? 'font-semibold text-slate-900' : 'font-medium text-slate-700' }}">{{ $r->subject }}</span>
                            </span>
                            <span class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-slate-500">
                                <span class="md:hidden">{{ $name($r->user) }} ·</span>
                                <span class="inline-flex items-center gap-1">
                                    <i class="bx {{ $r->type === 'issue' ? 'bx-error-circle text-rose-500' : 'bx-message-square-dots text-[#2A57B4]' }}"></i>{{ $r->categoryLabel() }}
                                </span>
                                @if ($r->rating)
                                    <span class="inline-flex items-center gap-0.5 text-amber-500"><i class="bx bxs-star"></i>{{ $r->rating }}</span>
                                @endif
                                @if ($r->severity)
                                    <span class="inline-flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full {{ $severityDot[$r->severity] }}"></span>{{ $r->severityLabel() }}</span>
                                @endif
                                @if ($r->attachment_path)<i class="bx bx-paperclip" aria-label="Has attachment"></i>@endif
                                <span class="md:hidden">· {{ $r->created_at->diffForHumans(short: true) }}</span>
                            </span>
                            <span class="mt-1.5 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 md:hidden {{ FeedbackReport::statusBadge($r->status) }}">{{ $r->statusLabel() }}</span>
                        </span>

                        <span class="hidden md:block">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 {{ FeedbackReport::statusBadge($r->status) }}">{{ $r->statusLabel() }}</span>
                        </span>
                        <span class="hidden text-right text-xs text-slate-500 md:block" title="{{ $r->created_at->format('M j, Y g:i A') }}">{{ $r->created_at->format('M j, Y') }}</span>
                    </button>
                @empty
                    <div class="px-4 py-14 text-center">
                        <i class="bx bx-check-double text-4xl text-slate-300"></i>
                        <p class="mt-2 text-sm font-medium text-slate-700">{{ $search !== '' || $kind !== '' ? 'Nothing matches your filters' : ($tab === 'open' ? 'All caught up' : 'Nothing here yet') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $tab === 'open' && $search === '' && $kind === '' ? 'New feedback and issue reports will appear here.' : 'Try another tab or clear the search.' }}</p>
                    </div>
                @endforelse
            </div>

            @if ($reports->hasPages())
                <div class="mt-4 flex items-center justify-between gap-3">
                    <button type="button" wire:click="previousPage" @disabled($reports->onFirstPage())
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 sm:py-2">Previous</button>
                    <span class="text-sm text-slate-500">Page {{ $reports->currentPage() }} of {{ $reports->lastPage() }}</span>
                    <button type="button" wire:click="nextPage" @disabled(! $reports->hasMorePages())
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 sm:py-2">Next</button>
                </div>
            @endif
        </div>

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

                <div class="flex items-center justify-between gap-3 border-b px-5 py-4 {{ $selected->type === 'issue' ? 'border-rose-100 bg-gradient-to-r from-rose-50 to-amber-50' : 'border-blue-100 bg-gradient-to-r from-blue-50 to-sky-50' }}">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-white shadow-sm {{ $selected->type === 'issue' ? 'bg-rose-600' : 'bg-[#2A57B4]' }}">
                        <i class="bx {{ $selected->type === 'issue' ? 'bx-error-circle' : 'bx-message-square-dots' }} text-xl"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <h2 id="fb-drawer-title" class="text-base font-semibold text-slate-900">{{ $selected->typeLabel() }} details</h2>
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
                                class="h-10 w-10 text-xs ring-2 ring-white shadow-sm {{ $selected->type === 'issue' ? 'bg-gradient-to-br from-rose-500 to-orange-400' : 'bg-gradient-to-br from-[#2A57B4] to-sky-400' }}" />
                            <span class="min-w-0">
                                <span class="block truncate font-medium text-slate-800">{{ $name($selected->user) }}</span>
                                <span class="block truncate text-xs text-slate-400">{{ $selected->user?->role?->role_name }}{{ $selected->user?->student_number ? ' · ' . $selected->user->student_number : '' }}{{ $selected->user?->email ? ' · ' . $selected->user->email : '' }}</span>
                            </span>
                        </dd>

                        <dt class="flex items-center gap-2 text-slate-500"><i class="bx bx-loader-circle"></i>Status</dt>
                        <dd><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ FeedbackReport::statusBadge($selected->status) }}">{{ $selected->statusLabel() }}</span>
                            @if ($selected->handler)<span class="ml-1 text-xs text-slate-400">by {{ $selected->handler->first_name }}</span>@endif
                        </dd>

                        <dt class="flex items-center gap-2 text-slate-500"><i class="bx bx-purchase-tag"></i>Topic</dt>
                        <dd class="text-slate-700">{{ $selected->categoryLabel() }}</dd>

                        @if ($selected->severity)
                            <dt class="flex items-center gap-2 text-slate-500"><i class="bx bx-error"></i>Impact</dt>
                            <dd class="flex items-center gap-1.5 text-slate-700"><span class="h-2 w-2 rounded-full {{ $severityDot[$selected->severity] }}"></span>{{ FeedbackReport::SEVERITIES[$selected->severity] ?? $selected->severity }}</dd>
                        @endif

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
                            Reply to {{ $selected->user?->first_name ?? 'user' }}
                            @if ($selected->replied_at)<span class="text-xs font-normal text-slate-400">Sent {{ $selected->replied_at->diffForHumans() }}</span>@endif
                        </label>
                        <textarea id="fb-reply" rows="4" wire:model="reply" maxlength="3000" class="w-full {{ $field }}"
                                  placeholder="The user sees this on their Feedback & Issues page and gets a notification."></textarea>
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

                {{-- Actions --}}
                <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3">
                    @if (in_array($selected->status, ['new', 'in_progress'], true))
                        @if ($selected->status === 'new')
                            <button type="button" wire:click="markInProgress" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Mark in progress</button>
                        @endif
                        <button type="button" wire:click="close" wire:confirm="Close this without resolving it? The user is not notified."
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Close</button>
                        <button type="button" wire:click="resolve" wire:loading.attr="disabled" wire:target="resolve"
                                class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#24499A] disabled:opacity-60">
                            <i class="bx bx-check"></i>Resolve
                        </button>
                    @else
                        <span class="text-xs text-slate-500">
                            {{ $selected->status === 'resolved' ? 'Resolved ' . ($selected->resolved_at?->diffForHumans() ?? '') : 'Closed' }}
                        </span>
                        <button type="button" wire:click="reopen" class="ml-auto rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Reopen</button>
                    @endif
                </div>
            </aside>
        </div>
    @endif
</div>
