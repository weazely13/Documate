{{-- Documents by type as a table: in progress, completed, total.
     @include('livewire.partials.insights.type-table', ['title' => 'Documents by type', 'note' => '...', 'rows' => DashboardInsights::byType(...), 'empty' => '...']) --}}
@php $titleId = 'types-' . \Illuminate\Support\Str::slug($title); @endphp
<section class="rounded-2xl border border-gray-200/70 bg-white p-5" aria-labelledby="{{ $titleId }}">
    <h2 id="{{ $titleId }}" class="text-base font-semibold text-gray-900">{{ $title }}</h2>
    @isset($note)
        <p class="mt-0.5 text-sm text-gray-500">{{ $note }}</p>
    @endisset

    @if ($rows)
        <ul class="mt-2 divide-y divide-gray-100 cq-sm:hidden">
            @foreach ($rows as $row)
                <li class="flex items-start gap-3 py-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-500"><i class='bx bx-file text-base' aria-hidden="true"></i></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-medium leading-snug text-gray-900">{{ $row['label'] }}</span>
                        <span class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-xs tabular-nums text-gray-600">
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#eb6834]" aria-hidden="true"></span>{{ $row['open'] }} in progress</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#2A57B4]" aria-hidden="true"></span>{{ $row['completed'] }} completed</span>
                        </span>
                    </span>
                    <span class="shrink-0 text-right">
                        <span class="block text-base font-semibold leading-tight tabular-nums text-gray-900">{{ $row['total'] }}</span>
                        <span class="block text-[11px] text-gray-500">total</span>
                    </span>
                </li>
            @endforeach
        </ul>
        <div class="-mx-5 mt-3 hidden overflow-x-auto px-5 cq-sm:block">
            <table class="w-full min-w-[28rem] table-fixed text-sm">
                <thead>
                    <tr class="whitespace-nowrap border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-500">
                        <th scope="col" class="py-2.5 pr-4 font-medium">Document</th>
                        <th scope="col" class="w-28 px-4 py-2.5 text-right font-medium">In progress</th>
                        <th scope="col" class="w-28 px-4 py-2.5 text-right font-medium">Completed</th>
                        <th scope="col" class="w-16 py-2.5 pl-4 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="py-3 pr-4">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-50 text-gray-500"><i class='bx bx-file text-base' aria-hidden="true"></i></span>
                                    <span class="truncate font-medium text-gray-900" title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-700">
                                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#eb6834]" aria-hidden="true"></span>{{ $row['open'] }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-gray-700">
                                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#2A57B4]" aria-hidden="true"></span>{{ $row['completed'] }}</span>
                            </td>
                            <td class="py-3 pl-4 text-right font-semibold tabular-nums text-gray-900">{{ $row['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="mt-4 rounded-xl bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">{{ $empty ?? 'Nothing yet.' }}</p>
    @endif
</section>
