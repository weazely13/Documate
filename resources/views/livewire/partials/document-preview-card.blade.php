@if($workspace && $workspace->template?->currentVersion)
    @php
        $version = $workspace->template->currentVersion;
        $canvas = $version->canvasDimensions();

        $previewFields = $version->fields
            ->sortBy('x_position')
            ->sortBy('y_position')
            ->map(function ($f) {
                // Same-named fields intentionally share one value (see NewTransaction).
                $name = $f->name ? \Illuminate\Support\Str::slug($f->name, '_') : ('field_' . $f->field_id);

                return [
                    'name' => $name,
                    'type' => $f->field_type === 'paragraph' ? 'paragraph' : $f->data_type,
                    'x' => (float) $f->x_position, 'y' => (float) $f->y_position,
                    'width' => (float) $f->width, 'height' => (float) $f->height,
                    'font_family' => $f->font_family, 'font_weight' => $f->font_weight,
                    'font_size' => (float) $f->font_size, 'text_color' => $f->text_color,
                    'alignment' => $f->alignment,
                    'line_height' => (float) ($f->line_height ?? 1.3),
                    'letter_spacing' => (float) ($f->letter_spacing ?? 0),
                    'text_case' => $f->text_case ?? 'none',
                ];
            })
            ->values();

        $cropRatio = $cropRatio ?? 0.5;
    @endphp
    @include('livewire.admin.partials.preview-engine')
    <div
        x-data="{
            ...documentPreviewEngine(@js($previewFields), @js($workspace->field_values ?? [])),
            scale: 1,
            ro: null,
            fit() {
                const wrapper = this.$refs.scaleWrapper;
                if (!wrapper || !wrapper.parentElement) return;

                const available = wrapper.parentElement.clientWidth;
                if (available > 0) {
                    this.scale = available / {{ $canvas['width'] }};
                } else {
                    requestAnimationFrame(() => this.fit());
                }
            },
            initFit() {
                this.$nextTick(() => {
                    this.fit();
                    if (this.$refs.scaleWrapper?.parentElement && !this.ro) {
                        this.ro = new ResizeObserver(() => this.fit());
                        this.ro.observe(this.$refs.scaleWrapper.parentElement);
                    }
                });
            }
        }"
        x-init="initFit()"
        class="w-full"
    >
        <div
            class="w-full overflow-hidden rounded-xl border border-slate-200 bg-white"
            style="aspect-ratio: {{ $canvas['width'] }} / {{ $canvas['height'] * $cropRatio }};"
        >
            <div
                x-ref="scaleWrapper"
                class="relative bg-white origin-top-left"
                style="width: {{ $canvas['width'] }}px; height: {{ $canvas['height'] }}px;"
                x-bind:style="{ transform: 'scale(' + scale + ')' }"
            >
                <img
                    src="{{ Storage::disk('public')->url($version->image_path) }}"
                    class="absolute inset-0 w-full h-full pointer-events-none select-none"
                    @load="$data.fit()"
                >
                @foreach($previewFields as $field)
                    <div class="absolute" x-bind:style="fieldBoxStyle(@js($field))">
                        <div
                            class="w-full px-2 py-1 pointer-events-none"
                            x-bind:style="fieldTextStyle(@js($field))"
                            x-text="displayValue(@js($field))"
                        ></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
        No document attached to this appointment.
    </div>
@endif