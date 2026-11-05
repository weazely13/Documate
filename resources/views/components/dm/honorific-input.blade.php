{{-- An "Honorifics" (or post-nominals) box: pick a suggestion or type your own when it is not in the list.
     <x-dm.honorific-input id="f-adviser_title" model="values.adviser_title" class="..." />
     'kind' => 'post' offers post-nominals (PhD, MA, LPT ...) instead of honorifics.
     Any other attributes (aria-*, required, wire:model.live ...) go on the input. --}}
@props(['id', 'model' => null, 'kind' => 'pre', 'placeholder' => null])
@php
    $suggestions = $kind === 'post'
        ? \App\Support\Honorifics::POST_NOMINALS
        : \App\Support\Honorifics::HONORIFICS;
    $listId = 'dl-' . $kind . '-' . preg_replace('/[^a-z0-9_-]/i', '-', $id);
@endphp
<input id="{{ $id }}" type="text" list="{{ $listId }}" autocomplete="off" maxlength="40"
       @if ($model) wire:model="{{ $model }}" @endif
       placeholder="{{ $placeholder ?? ($kind === 'post' ? 'e.g. PhD' : 'Honorifics') }}"
       {{ $attributes }}>
<datalist id="{{ $listId }}">
    @foreach ($suggestions as $opt)
        <option value="{{ $opt }}"></option>
    @endforeach
</datalist>
