<div class="space-y-6">
    @if (session()->has('message'))
        <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
            {{ session('message') }}
        </div>
    @endif

    <div>
        <h1 class="text-4xl font-extrabold tracking-tight text-slate-900">
            {{ $pageHeading ?? 'Clearance Monitoring' }}
        </h1>
        <p class="mt-1 text-base text-slate-500">
            {{ $pageDescription ?? 'View of all the clearance status submitted per academic organization' }}
        </p>
    </div>

    <div class="rounded-[24px] border border-[#cfd7e4] bg-white p-5 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
        <div class="relative">
            <div class="mb-4 flex flex-col gap-3 lg:w-[290px]">
                <div class="relative">
                    <i class='bx bx-search absolute left-4 top-1/2 -translate-y-1/2 text-xl text-slate-400'></i>
                    <input type="text"
                           wire:model.live="search"
                           placeholder="Search"
                           class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-12 pr-4 text-sm text-slate-700 outline-none transition focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10">
                </div>

                <div class="relative">
                    <button type="button"
                            wire:click="toggleFilters"
                            class="inline-flex w-[120px] items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                        <i class='bx bx-slider-alt text-base'></i>
                        <span>Filter</span>
                    </button>

                    @if($showFilters)
                        <div class="absolute left-0 top-full z-20 mt-3 w-[320px] rounded-[20px] border border-slate-200 bg-white p-4 shadow-[0_20px_40px_rgba(15,23,42,0.18)]">
                            <div class="grid grid-cols-2 gap-3">
                                <select wire:model.live="semesterFilter"
                                        class="col-span-1 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#2A57B4]">
                                    <option value="">Semester</option>
                                    @foreach($semesters as $semester)
                                        <option value="{{ $semester }}">{{ $semester }}</option>
                                    @endforeach
                                </select>

                                <select wire:model.live="statusFilter"
                                        class="col-span-1 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#2A57B4]">
                                    <option value="">Status</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}">{{ $status }}</option>
                                    @endforeach
                                </select>

                                <select wire:model.live="academicYearFilter"
                                        class="col-span-2 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#2A57B4]">
                                    <option value="">Academic Year</option>
                                    @foreach($academicYears as $academicYear)
                                        <option value="{{ $academicYear }}">{{ $academicYear }}</option>
                                    @endforeach
                                </select>

                                <select wire:model.live="organizationFilter"
                                        class="col-span-2 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#2A57B4]">
                                    <option value="">Organization</option>
                                    @foreach($organizations as $organization)
                                        <option value="{{ $organization }}">{{ $organization }}</option>
                                    @endforeach
                                </select>

                                <select wire:model.live="yearFilter"
                                        class="col-span-2 rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#2A57B4]">
                                    <option value="">Year</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>

                                <div class="col-span-1">
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Date from</label>
                                    <input type="date"
                                           wire:model.live="dateFrom"
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#2A57B4]">
                                </div>

                                <div class="col-span-1">
                                    <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">To</label>
                                    <input type="date"
                                           wire:model.live="dateTo"
                                           class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none focus:border-[#2A57B4]">
                                </div>
                            </div>

                            <div class="mt-4 flex gap-2">
                                <button type="button"
                                        wire:click="applyFilters"
                                        class="flex-1 rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#214795]">
                                    Apply
                                </button>

                                <button type="button"
                                        wire:click="resetFilters"
                                        class="flex-1 rounded-xl bg-[#fbb02a] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#ea9b10]">
                                    Reset
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="overflow-x-auto rounded-[18px] border border-[#aeb9c8]">
                <table class="min-w-full table-auto border-collapse text-sm">
                    <thead class="bg-slate-50 text-slate-900">
                        <tr class="border-b border-[#aeb9c8]">
                            <th class="px-4 py-3 text-left text-[15px] font-medium">Student</th>
                            <th class="px-4 py-3 text-left text-[15px] font-medium">Student Number</th>
                            <th class="px-4 py-3 text-left text-[15px] font-medium">Organization</th>
                            <th class="px-4 py-3 text-left text-[15px] font-medium">Year</th>
                            <th class="px-4 py-3 text-left text-[15px] font-medium">Status</th>
                            <th class="px-4 py-3 text-left text-[15px] font-medium">Academic Year</th>
                            <th class="px-4 py-3 text-left text-[15px] font-medium">Semester</th>
                            <th class="px-4 py-3 text-center text-[15px] font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#c7d0dc] text-[15px] text-slate-800">
                        @forelse($records as $record)
                            @php
                                $tableStatusClass = match ($record['status']) {
                                    'Cleared' => 'border-green-200 bg-green-50 text-green-700',
                                    'Pending' => 'border-orange-200 bg-orange-50 text-orange-600',
                                    'Uncleared' => 'border-red-200 bg-red-50 text-red-700',
                                    default => 'border-slate-200 bg-white text-slate-600',
                                };
                                $statusChevronClass = match ($record['status']) {
                                    'Cleared' => 'text-green-700',
                                    'Pending' => 'text-orange-600',
                                    'Uncleared' => 'text-red-700',
                                    default => 'text-slate-500',
                                };
                            @endphp
                            <tr class="hover:bg-[#f8fbff]" wire:key="clearance-row-{{ $record['user_id'] }}">
                                <td class="px-4 py-3">{{ $record['student_name'] }}</td>
                                <td class="px-4 py-3">{{ $record['student_number'] }}</td>
                                <td class="px-4 py-3">{{ $record['organization'] }}</td>
                                <td class="px-4 py-3">{{ $record['year_level'] }}</td>
                                <td class="px-4 py-3">
                                    <div class="relative w-[150px]">
                                        <select wire:change="updateStatus({{ $record['user_id'] }}, $event.target.value)"
                                                class="w-full appearance-none rounded-lg border px-3 py-2 pr-9 text-sm font-extrabold outline-none transition focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10 {{ $tableStatusClass }}">
                                            @foreach($statuses as $status)
                                                @php
                                                    $optionClass = match ($status) {
                                                        'Cleared' => 'bg-green-50 text-green-700',
                                                        'Pending' => 'bg-orange-50 text-orange-600',
                                                        'Uncleared' => 'bg-red-50 text-red-700',
                                                        default => 'bg-white text-slate-600',
                                                    };
                                                @endphp
                                                <option value="{{ $status }}"
                                                        class="font-extrabold {{ $optionClass }}"
                                                        @selected($record['status'] === $status)>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                        <i class='bx bx-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lg {{ $statusChevronClass }}'></i>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ $record['academic_year'] }}</td>
                                <td class="px-4 py-3">{{ $record['semester'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button"
                                            wire:click="viewRecord({{ $record['user_id'] }})"
                                            class="inline-flex min-w-[70px] items-center justify-center rounded-md bg-[#fbb02a] px-4 py-1.5 text-sm font-semibold text-slate-900 transition hover:bg-[#ea9b10]">
                                        View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No clearance records matched your filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex items-center justify-center">
                <div class="inline-flex overflow-hidden rounded-xl border border-slate-400 text-sm">
                    <button type="button"
                            wire:click="previousPage"
                            @disabled($currentPage === 1)
                            class="px-6 py-2 transition {{ $currentPage === 1 ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white text-slate-700 hover:bg-slate-50' }}">
                        &lt;Prev
                    </button>

                    <div class="border-x border-slate-400 bg-white px-8 py-2 text-slate-700">
                        {{ $currentPage }} out of {{ $totalPages }}
                    </div>

                    <button type="button"
                            wire:click="nextPage({{ $totalPages }})"
                            @disabled($currentPage === $totalPages)
                            class="px-6 py-2 transition {{ $currentPage === $totalPages ? 'cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white text-slate-700 hover:bg-slate-50' }}">
                        Next&gt;
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($showDetailsModal && $selectedRecord)
        @php
            $modalStatusClass = match ($selectedRecord['status']) {
                'Cleared' => 'bg-green-50 text-green-600',
                'Pending' => 'bg-orange-50 text-orange-500',
                'Uncleared' => 'bg-red-50 text-red-600',
                default => 'bg-slate-100 text-slate-600',
            };
        @endphp
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/45 px-4" wire:click.self="closeDetails">
            <div class="w-full max-w-md rounded-[22px] bg-white p-6 shadow-[0_24px_60px_rgba(15,23,42,0.22)]">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-[34px] font-extrabold leading-none text-slate-900">Clearance Status</h2>
                        <p class="mt-2 text-sm text-slate-500">View the clearance status and remarks of the student</p>
                    </div>

                    <button type="button"
                            wire:click="closeDetails"
                            class="rounded-full p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <i class='bx bx-x text-2xl'></i>
                    </button>
                </div>

                <div class="mt-6 space-y-6 text-sm text-slate-700">
                    <div>
                        <p class="font-extrabold text-slate-900">Student Information</p>
                        <div class="mt-2 space-y-1">
                            <p class="text-[28px] font-semibold leading-tight text-slate-900">{{ $selectedRecord['student_name'] }}</p>
                            <p>{{ $selectedRecord['student_number'] }}</p>
                            <p>{{ $selectedRecord['program'] }}</p>
                            <p>{{ $selectedRecord['year_level'] }}</p>
                            <p>{{ $selectedRecord['organization'] }}</p>
                            <p>{{ $selectedRecord['email'] }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="font-extrabold text-slate-900">Clearance Status</p>
                        <div class="mt-2 space-y-1">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold uppercase tracking-[0.18em] {{ $modalStatusClass }}">
                                {{ $selectedRecord['status'] }}
                            </span>
                            <p>AY {{ $selectedRecord['academic_year'] }}</p>
                            <p>{{ $selectedRecord['semester'] }} Semester</p>
                            <p>Tagged by {{ $selectedRecord['tagged_by'] }}</p>
                            <p>{{ $selectedRecord['tagged_at'] }}</p>
                        </div>
                    </div>

                    <div>
                        <p class="font-extrabold text-slate-900">Remarks</p>
                        <p class="mt-2 text-slate-600">{{ $selectedRecord['remarks'] }}</p>
                    </div>
                </div>

                <button type="button"
                        wire:click="closeDetails"
                        class="mt-8 inline-flex w-full items-center justify-center rounded-xl bg-[#fbb02a] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#ea9b10]">
                    Done
                </button>
            </div>
        </div>
    @endif
</div>
