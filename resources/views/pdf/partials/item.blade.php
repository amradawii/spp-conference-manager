@use('App\Enums\AgendaItemType')
@php
    $hasSpeaker = $item->type !== AgendaItemType::Segment;
    $details = collect([$item->speaker_role, $item->affiliation])->filter()->implode(' · ');
@endphp
<table class="item {{ $alt ? 'alt' : '' }}">
    <tr>
        <td class="time">
            @unless ($repeatsTime)
                @include('pdf.partials.time', ['time' => $day->timeRange($item->starts_at, $item->ends_at)])
            @endunless
        </td>
        <td>
            @if ($item->label)
                <p class="caps {{ $hasSpeaker ? 'talk' : 'muted' }}">{{ $item->label }}</p>
            @endif
            @if ($hasSpeaker)
                <p class="speaker">{{ $item->speaker_name }}</p>
                @if ($details)
                    <p class="details">{{ $details }}</p>
                @endif
                @if (filled($item->title))
                    <p class="topic">{!! nl2br(e($item->title)) !!}</p>
                @else
                    <p style="margin-top: 4pt;"><span class="tbd">Topic TBD</span></p>
                @endif
                @if (! empty($item->emails))
                    <p class="email">{{ implode('   ·   ', $item->emails) }}</p>
                @endif
            @else
                <p class="segment">{!! nl2br(e($item->title)) !!}</p>
            @endif
        </td>
    </tr>
</table>
