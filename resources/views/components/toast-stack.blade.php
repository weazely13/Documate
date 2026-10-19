<div x-data="{ items: [], add(d){ const id = Date.now() + Math.random(); this.items.push({ id, type: d.type ?? 'success', title: d.title ?? '', message: d.message ?? '' }); setTimeout(() => this.items = this.items.filter(i => i.id !== id), 7000); } }"
     x-on:toast.window="add($event.detail)"
     class="pointer-events-none fixed right-4 top-4 z-[200] flex w-full max-w-sm flex-col gap-2">
    <template x-for="t in items" :key="t.id">
        <div class="pointer-events-auto rounded-xl border bg-white p-4 shadow-lg"
             :class="t.type === 'success' ? 'border-emerald-200' : 'border-amber-200'">
            <p class="text-sm font-semibold text-slate-900" x-text="t.title"></p>
            <p class="mt-0.5 text-sm text-slate-600" x-text="t.message"></p>
        </div>
    </template>
</div>