@if ($time)
    <b>{{ $clock($time['team_start']) }} – {{ $clock($time['team_end']) }}</b> <span class="city">{{ $cities['team'] }}</span><br>
    <span class="muted">{{ $clock($time['start']) }} – {{ $clock($time['end']) }}</span> <span class="city">{{ $cities['conference'] }}</span>
@else
    <span class="faint">Within session</span>
@endif
