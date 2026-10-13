@extends('layouts.public')

@section('title', 'FAQs | DocuMate')

@section('content')
    @php $faqs = config('faqs'); @endphp

    <main class="pt-36 pb-24 bg-white min-h-screen">
        <div class="max-w-4xl mx-auto px-6">

            <a href="{{ url('/') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-[#2A57B4] transition mb-8 reveal">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
                Back to home
            </a>

            <h1 class="text-5xl md:text-6xl font-semibold text-[#2A57B4] mb-5 reveal">
                Frequently asked questions
            </h1>
            <p class="text-lg text-gray-600 max-w-2xl mb-10 reveal">
                Answers about DocuMate, accounts, documents, and privacy. Can't find yours? Contact us and we'll help.
            </p>

            <!-- SEARCH -->
            <div class="relative mb-12 reveal">
                <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
                <input id="faq-search" type="search" placeholder="Search questions"
                       class="w-full pl-12 pr-4 py-3 rounded-full border border-gray-200 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-[#2A57B4]/40 focus:border-[#2A57B4]">
            </div>

            <!-- CATEGORIES -->
            <div id="faq-groups" class="space-y-12">
                @foreach ($faqs as $category => $items)
                    <section data-faq-group>
                        <h2 class="text-2xl font-semibold text-[#2A57B4] mb-4 pb-2 border-b-2 border-[#FFBF00] inline-block">{{ $category }}</h2>

                        <div class="space-y-3">
                            @foreach ($items as $item)
                                <details class="faq-item bg-white rounded-xl border border-gray-200 overflow-hidden" data-faq-item>
                                    <summary class="flex items-center justify-between gap-4 px-5 py-4 font-semibold text-gray-800 hover:text-[#2A57B4] transition">
                                        <span>{{ $item['q'] }}</span>
                                        <svg class="faq-chevron w-4 h-4 shrink-0 text-gray-400 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                                    </summary>
                                    <div class="px-5 pb-5 text-gray-600 leading-relaxed">{{ $item['a'] }}</div>
                                </details>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            <!-- EMPTY STATE -->
            <p id="faq-empty" class="hidden text-center text-gray-500 py-12">
                No questions match your search. Try different words, or contact us below.
            </p>

            <!-- CONTACT -->
            <div class="mt-16 rounded-2xl bg-[#2A57B4] text-white p-8 md:p-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6 reveal">
                <div>
                    <h3 class="text-2xl font-semibold mb-1">Still have a question?</h3>
                    <p class="text-white/80">Email the university and we'll point you to the right office.</p>
                </div>
                <a href="mailto:info@lnu.edu.ph"
                   class="inline-block px-8 py-3 bg-[#FFBF00] text-[#003399] rounded-full font-bold text-center hover:opacity-90 hover:scale-[1.03] active:scale-95 transition-all">
                    info@lnu.edu.ph
                </a>
            </div>

        </div>
    </main>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var input  = document.getElementById('faq-search');
    var groups = document.querySelectorAll('[data-faq-group]');
    var empty  = document.getElementById('faq-empty');

    input.addEventListener('input', function () {
        var term = input.value.trim().toLowerCase();
        var anyVisible = false;

        groups.forEach(function (group) {
            var groupVisible = false;

            group.querySelectorAll('[data-faq-item]').forEach(function (item) {
                var match = term === '' || item.textContent.toLowerCase().indexOf(term) !== -1;
                item.classList.toggle('hidden', !match);
                if (match) groupVisible = true;
                // Open matching answers while searching, close them when cleared
                item.open = term !== '' && match;
            });

            group.classList.toggle('hidden', !groupVisible);
            if (groupVisible) anyVisible = true;
        });

        empty.classList.toggle('hidden', anyVisible);
    });
});
</script>
@endpush