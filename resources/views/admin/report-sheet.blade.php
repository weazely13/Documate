{{-- The Reports table as a spreadsheet in its own tab: column letters, row numbers, a frozen
     header, a cell bar that shows the picked cell, and .xlsx / .csv downloads. --}}
@php
    $letter = function (int $i) {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    };
    $download = fn ($format) => route('admin.reports.sheet', array_merge($query, ['format' => $format]));
    $isNumber = fn ($v) => is_int($v) || is_float($v);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $table['title'] }} · DocuMate</title>
    @vite(['resources/css/app.css'])
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .sheet td.cell, .sheet th.cell { min-width: 7.5rem; max-width: 22rem; }
        .sheet td.cell.picked { outline: 2px solid #1a73e8; outline-offset: -2px; background: #fff; }
        .sheet .col-picked { background: #d3e3fd !important; color: #0b57d0; }
    </style>
</head>
<body class="flex h-dvh flex-col overflow-hidden bg-white text-slate-800 antialiased">
    <header class="flex flex-wrap items-center gap-3 border-b border-slate-200 px-4 py-2.5">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-emerald-600 text-white" aria-hidden="true"><i class="bx bx-spreadsheet text-xl"></i></span>
        <div class="min-w-0 flex-1">
            <h1 class="truncate text-base font-medium text-slate-900">{{ $table['title'] }}</h1>
            <p class="truncate text-xs text-slate-500">DocuMate Reports · {{ $period }} · {{ number_format($table['count']) }} {{ Str::plural('row', $table['count']) }} · {{ now()->format('M j, Y g:i A') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ $download('csv') }}" class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 px-3.5 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"><i class="bx bx-download" aria-hidden="true"></i>.csv</a>
            <a href="{{ $download('xlsx') }}" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-600 px-3.5 py-1.5 text-sm font-medium text-white hover:bg-emerald-700"><i class="bx bx-download" aria-hidden="true"></i>Excel (.xlsx)</a>
        </div>
    </header>

    {{-- Cell bar: the picked cell's address and value --}}
    <div class="flex items-center border-b border-slate-200 text-sm">
        <span id="ref" class="w-20 shrink-0 border-r border-slate-200 px-3 py-1.5 font-medium text-slate-600">A1</span>
        <span class="border-r border-slate-200 px-2.5 py-1.5 italic text-slate-400" aria-hidden="true">fx</span>
        <output id="val" class="min-w-0 flex-1 truncate px-3 py-1.5">{{ $table['head'][0] ?? '' }}</output>
    </div>

    <div class="sheet min-h-0 flex-1 overflow-auto bg-white">
        <table class="border-separate border-spacing-0 text-[13px]">
            <thead>
                <tr>
                    <th class="sticky left-0 top-0 z-30 h-6 w-12 min-w-[3rem] border-b border-r border-slate-300 bg-slate-100"></th>
                    @foreach ($table['head'] as $i => $h)
                        <th data-col="{{ $i }}" class="sticky top-0 z-20 h-6 border-b border-r border-slate-300 bg-slate-100 px-2 text-center text-xs font-normal text-slate-600">{{ $letter($i) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ([$table['head'], $table['totals'], ...$table['rows']] as $r => $row)
                    <tr>
                        <th class="sticky left-0 z-10 border-b border-r border-slate-300 bg-slate-100 px-2 text-center text-xs font-normal text-slate-600 {{ $r === 0 ? 'top-6 !z-20' : '' }}">{{ $r + 1 }}</th>
                        @foreach ($row as $c => $v)
                            <td tabindex="-1" data-ref="{{ $letter($c) }}{{ $r + 1 }}" data-col="{{ $c }}"
                                class="cell cursor-cell truncate whitespace-nowrap border-b border-r border-slate-200 px-2 py-1 {{ $isNumber($v) ? 'text-right tabular-nums' : '' }} {{ $r === 0 ? 'sticky top-6 z-10 bg-slate-50 font-semibold' : '' }} {{ $r === 1 ? 'font-semibold' : '' }}"
                                title="{{ $v }}">{{ $v ?? '' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if (! $table['count'])
            <p class="p-6 text-sm text-slate-500">This report has no rows for the chosen period and filters.</p>
        @endif
    </div>

    <footer class="flex items-center gap-1 border-t border-slate-200 bg-slate-50 px-2 py-1 text-sm">
        <span class="rounded-t-md border-x border-b-2 border-b-emerald-600 bg-white px-4 py-1 font-medium text-emerald-700">Table data</span>
        <span class="ml-auto pr-2 text-xs text-slate-500">Row 1 holds the column names, row 2 the totals.</span>
    </footer>

    <script>
        // Click a cell to see its address and full value in the cell bar.
        (() => {
            const ref = document.getElementById('ref'), val = document.getElementById('val');
            let picked = null;
            document.querySelector('.sheet').addEventListener('click', (e) => {
                const td = e.target.closest('td.cell');
                if (!td) return;
                picked?.classList.remove('picked');
                document.querySelectorAll('.col-picked').forEach(el => el.classList.remove('col-picked'));
                picked = td;
                td.classList.add('picked');
                document.querySelector(`thead th[data-col="${td.dataset.col}"]`)?.classList.add('col-picked');
                td.closest('tr').firstElementChild.classList.add('col-picked');
                ref.textContent = td.dataset.ref;
                val.textContent = td.textContent;
            });
        })();
    </script>
</body>
</html>
