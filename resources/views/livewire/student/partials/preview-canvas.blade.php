<div>
    <img src="{{ Storage::disk('public')->url($workspace->template->currentVersion->image_path) }}"
        class="absolute inset-0 !w-full !h-full pointer-events-none select-none" alt="">

    @foreach($fields as $field)
        <div class="absolute"
            style="left: {{ $field['x'] }}px; top: {{ $field['y'] }}px; width: {{ $field['width'] }}px; height: {{ $field['height'] }}px; box-sizing: border-box;">
            <div class="w-full px-2 py-1 pointer-events-none"
                x-bind:style="fieldTextStyle(@js($field))"
                x-text="displayValue(@js($field))"></div>
        </div>
    @endforeach
</div>
