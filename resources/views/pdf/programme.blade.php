@use('App\Enums\SessionKind')
@use('App\Models\Conference')
@use('Illuminate\Support\Carbon')
@php
    $cities = ['conference' => Conference::cityOf($conference->timezone), 'team' => Conference::cityOf($conference->team_timezone)];
    $clock = fn (string $time): string => Carbon::createFromFormat('H:i', $time)->format('h:i A');
    $offset = fn (string $offset): string => preg_replace('/^([+-])0?(\d+):00$/', 'UTC$1$2', $offset);
    $hours = fn (string $offset): float => (int) substr($offset, 0, 3) + (int) substr($offset, 4, 2) / 60;

    $days = $conference->days;
    $first = $days->first()?->date;
    $last = $days->last()?->date;
    $dateSpan = match (true) {
        $first === null => '',
        $first->isSameDay($last) => $first->format('j F Y'),
        $first->isSameMonth($last) => $first->format('j').'–'.$last->format('j F Y'),
        default => $first->format('j F').' – '.$last->format('j F Y'),
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $conference->name }} · Programme</title>
    <style>
        @font-face { font-family: 'Inter'; font-weight: normal; src: url('{{ resource_path('fonts/Inter-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: 600; src: url('{{ resource_path('fonts/Inter-SemiBold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Inter'; font-weight: bold; src: url('{{ resource_path('fonts/Inter-Bold.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Source Serif'; font-weight: normal; src: url('{{ resource_path('fonts/SourceSerif4-Regular.ttf') }}') format('truetype'); }
        @font-face { font-family: 'Source Serif'; font-weight: bold; src: url('{{ resource_path('fonts/SourceSerif4-SemiBold.ttf') }}') format('truetype'); }

        @page { margin: 40pt 42pt 56pt 42pt; }
        h1, h2, h3, p { margin: 0; padding: 0; }
        body { margin: 0; font-family: 'Inter', sans-serif; font-size: 8.5pt; line-height: 1.4; color: #13213d; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .serif { font-family: 'Source Serif', serif; }
        .caps { font-size: 7pt; font-weight: bold; letter-spacing: 0.6pt; text-transform: uppercase; }
        .muted { color: #5b6b8c; }
        .faint { color: #68748f; }

        .masthead { background: #0b1426; }
        .masthead td { padding: 14pt 18pt; vertical-align: middle; }
        .masthead img { height: 26pt; }
        .rule { height: 2pt; background: #17b8b0; }

        .cover { padding: 18pt 0 6pt; }
        .cover h1 { font-family: 'Source Serif', serif; font-weight: bold; font-size: 22pt; line-height: 1.15; color: #0b1426; }
        .cover .sub { margin-top: 4pt; font-size: 10pt; color: #3f5077; }
        .zones { margin-top: 10pt; padding: 7pt 10pt; border: 0.75pt solid #e6e3dc; background: #f9f8f5; font-size: 8pt; color: #3f5077; }

        .day { padding-top: 16pt; }
        .day-break { page-break-before: always; padding-top: 0; }
        .day-head { border-bottom: 1.5pt solid #d6d2c8; padding-bottom: 6pt; margin-bottom: 10pt; }
        .day-head h2 { font-family: 'Source Serif', serif; font-weight: bold; font-size: 16pt; color: #0b1426; margin-top: 2pt; }
        .day-span { text-align: right; vertical-align: bottom; font-size: 8pt; }

        .session { margin-top: 12pt; }
        .session-head, .item { page-break-inside: avoid; }
        .session-head { page-break-after: avoid; }
        .session-head td { padding: 8pt 10pt; }
        .session-head.is-session { background: #f4f7fb; border-top: 1.5pt solid #1f4e8c; }
        .session-head.is-plenary { background: #f5f7fa; border-top: 1.5pt solid #0b7a62; }
        .session-head h3 { font-family: 'Source Serif', serif; font-weight: bold; font-size: 12.5pt; line-height: 1.05; margin-top: 2pt; }
        .moderators { margin-top: 5pt; }
        .moderators td { padding: 0 18pt 0 0; }
        .moderators td.spp-cell { width: 34%; }

        .time { width: 112pt; font-size: 8pt; line-height: 1.35; white-space: nowrap; }
        .time b { color: #0b1426; }
        .city { font-size: 6.5pt; color: #68748f; }

        .item td { padding: 6pt 10pt; border-bottom: 0.5pt solid #e6e3dc; }
        .item.alt td { background: #f9f8f5; }
        .speaker { font-size: 10pt; font-weight: bold; color: #0b1426; margin-top: 1pt; }
        .details { margin-top: 1pt; font-size: 7.5pt; line-height: 1.3; color: #5b6b8c; }
        .topic { margin-top: 3pt; font-family: 'Source Serif', serif; font-size: 9.5pt; line-height: 1.05; color: #1d2e50; }
        .tbd { background: #fef9c3; color: #713f12; font-weight: bold; font-size: 7.5pt; padding: 1pt 4pt; }
        .email { margin-top: 3pt; font-size: 7.5pt; color: #5b6b8c; }
        .segment { margin-top: 1pt; color: #3f5077; }

        .journal { color: #1f4e8c; }
        .talk { color: #a8501f; }
        .spp { color: #b3261e; }
        .brand { color: #0b7a62; }
    </style>
</head>
<body>
    <table class="masthead">
        <tr>
            <td><img src="{{ public_path('SPP_logo.png') }}" alt="Science Park Publisher"></td>
            <td style="text-align: right; color: #6ee7c0;" class="caps">Conference programme</td>
        </tr>
    </table>
    <div class="rule"></div>

    <div class="cover">
        <h1>{{ $conference->name }}</h1>
        <p class="sub">{{ $dateSpan }} · Online via Zoom</p>
        <div class="zones">
            Every time is shown twice: <b>{{ $cities['team'] }}</b> first, then {{ $cities['conference'] }} (the time of the official programme).
            @foreach ($days as $day)
                @php
                    $offsets = $day->utcOffsets();
                    $lead = $hours($offsets['team']) - $hours($offsets['conference']);
                @endphp
                Day {{ $loop->iteration }}: {{ $cities['team'] }} is {{ rtrim(rtrim(number_format(abs($lead), 1), '0'), '.') }} {{ abs($lead) == 1 ? 'hour' : 'hours' }} {{ $lead >= 0 ? 'ahead' : 'behind' }}{{ $loop->last ? '.' : ';' }}
            @endforeach
        </div>
    </div>

    @foreach ($days as $day)
        @php
            $span = $day->timeRange($day->sessions->min('starts_at'), $day->sessions->max('ends_at'));
            $offsets = $day->utcOffsets();
        @endphp
        <div class="{{ $loop->first ? 'day' : 'day day-break' }}">
            <table class="day-head">
                <tr>
                    <td>
                        <p class="caps brand">Day {{ $loop->iteration }} · {{ $day->title }}</p>
                        <h2>{{ $day->date->format('l j F') }}</h2>
                    </td>
                    @if ($span)
                        <td class="day-span">
                            <b>{{ $clock($span['team_start']) }} – {{ $clock($span['team_end']) }}</b> <span class="city">{{ $cities['team'] }} {{ $offset($offsets['team']) }}</span><br>
                            <span class="muted">{{ $clock($span['start']) }} – {{ $clock($span['end']) }}</span> <span class="city">{{ $cities['conference'] }} {{ $offset($offsets['conference']) }}</span>
                        </td>
                    @endif
                </tr>
            </table>

            @foreach ($day->sessions as $session)
                @php
                    $isSession = $session->kind === SessionKind::Session;
                    $time = $day->timeRange($session->starts_at, $session->ends_at);
                @endphp
                <div class="session">
                        <table class="session-head {{ $isSession ? 'is-session' : 'is-plenary' }}">
                            <tr>
                                <td class="time">
                                    @include('pdf.partials.time', ['time' => $time])
                                </td>
                                <td>
                                    <p class="caps {{ $isSession ? 'journal' : 'brand' }}">{{ $isSession ? 'Session '.$session->number : 'Plenary' }}</p>
                                    @if ($session->journal)
                                        <h3 class="journal">{{ $session->journal }}</h3>
                                        @if ($session->title)
                                            <p class="segment">{{ $session->title }}</p>
                                        @endif
                                    @elseif ($session->title)
                                        <h3>{{ $session->title }}</h3>
                                    @endif
                                    @if ($isSession)
                                        <table class="moderators">
                                            <tr>
                                                <td class="spp-cell">
                                                    <p class="caps spp">SPP moderator</p>
                                                    <p>{!! filled($session->spp_moderator) ? '<b>'.e($session->spp_moderator).'</b>' : '<span class="muted">To be announced</span>' !!}</p>
                                                </td>
                                                <td>
                                                    <p class="caps journal">Journal moderator</p>
                                                    <p>{!! filled($session->journal_moderator) ? '<b>'.e($session->journal_moderator).'</b>' : '<span class="muted">To be announced</span>' !!}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    @endif
                                </td>
                            </tr>
                        </table>
                        @foreach ($session->items as $item)
                            @include('pdf.partials.item', ['item' => $item, 'alt' => $loop->even])
                        @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
