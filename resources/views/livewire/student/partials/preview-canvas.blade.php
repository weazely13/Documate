<div>
    <img src="{{ Storage::disk('public')->url($workspace->template->currentVersion->image_path) }}"
        class="absolute inset-0 h-full w-full pointer-events-none select-none"
        draggable="false"
        alt="Document Canvas">
    @foreach($fields as $field)
        <div class="absolute" x-bind:style="fieldBoxStyle(@js($field))">
            <div class="w-full px-2 py-1 pointer-events-none" x-bind:style="fieldTextStyle(@js($field))" x-text="displayValue(@js($field))"></div>
        </div>
    @endforeach
</div>
