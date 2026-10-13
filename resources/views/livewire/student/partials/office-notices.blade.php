@if ($notices->isNotEmpty())
    <section class="space-y-2.5">
        @foreach ($notices as $n)
            @php
                $v = $n->field_values ?? [];
                $signed = $n->workflow_status === \App\Enums\TransactionStatus::Completed;
                $day = ! empty($v['appointment_date']) ? \Carbon\Carbon::parse($v['appointment_date'])->format('l, F j, Y') : '';
                $time = ! empty($v['appointment_time'])
                    ? \Carbon\Carbon::createFromFormat('H:i', substr($v['appointment_time'], 0, 5))->format('g:i A') : '';
            @endphp
            <a href="{{ route('student.transactions.show', $n->workspace_id) }}" wire:navigate wire:key="notice-{{ $n->workspace_id }}"
               class="flex flex-wrap items-center gap-4 rounded-2xl border-2 border-[#2A57B4] bg-blue-50 p-4 shadow-md transition hover:shadow-lg">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#2A57B4] text-white">
                    <i class='bx bx-envelope text-2xl'></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-[#2A57B4]">You have been called by the VPSD office</p>
                    <p class="mt-0.5 truncate text-sm font-semibold text-slate-900">{{ $v['subject'] ?? '' }}</p>
                    <p class="text-sm text-slate-700">See the Vice President for Student Development at <strong>{{ $time }}</strong> on <strong>{{ $day }}</strong>.</p>
                </div>
                <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $signed ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $signed ? 'Signed. Download your slip' : 'Waiting for the VP signature' }}
                </span>
            </a>
        @endforeach
    </section>
@endif