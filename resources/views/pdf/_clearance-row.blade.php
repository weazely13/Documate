{{-- Needs: $l, $r (each ['s' => signer, 'cap' => html] or null). $center = one centered block, uses $l. --}}
@if ($center ?? false)
    <table class="sgrid">
        <tr>
            <td style="width:24%;"></td>
            <td style="width:52%; text-align:center;">
                @include('pdf._clearance-signer', ['s' => $l['s'] ?? [], 'caption' => $l['cap'] ?? ''])
            </td>
            <td style="width:24%;"></td>
        </tr>
    </table>
@else
    <table class="sgrid">
        <tr>
            <td class="col">@if (! empty($l)) @include('pdf._clearance-signer', ['s' => $l['s'], 'caption' => $l['cap']]) @endif</td>
            <td class="gap"></td>
            <td class="col">@if (! empty($r)) @include('pdf._clearance-signer', ['s' => $r['s'], 'caption' => $r['cap']]) @endif</td>
        </tr>
    </table>
@endif