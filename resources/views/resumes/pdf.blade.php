<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $resume->original_name }} - Reviewed resume</title>
    <style>
        @page { margin: 42px 52px 46px; }
        body {
            color: #202b3c;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.3pt;
            line-height: 1.42;
        }
        .resume-header {
            border-bottom: 2px solid #4261a8;
            margin-bottom: 19px;
            padding: 0 0 12px;
        }
        h1 {
            color: #1c2b43;
            font-size: 23pt;
            font-weight: bold;
            line-height: 1.15;
            margin: 0;
            overflow-wrap: break-word;
        }
        .subtitle {
            color: #4261a8;
            font-size: 11pt;
            font-weight: bold;
            margin: 4px 0 0;
        }
        .contact {
            color: #55657a;
            font-size: 8.7pt;
            margin: 7px 0 0;
        }
        .resume-section {
            margin: 0 0 12px;
        }
        .resume-section h2 {
            border-bottom: 1px solid #d5deeb;
            color: #38558f;
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: .7pt;
            margin: 14px 0 6px;
            padding-bottom: 3px;
            text-transform: uppercase;
        }
        .resume-line {
            margin: 0 0 4px;
            overflow-wrap: break-word;
            white-space: pre-wrap;
        }
        .resume-entry {
            color: #263a5b;
            font-weight: bold;
            margin-top: 6px;
        }
        .resume-bullet {
            display: table;
            margin: 0 0 3px 8px;
            width: 98%;
        }
        .resume-bullet-mark, .resume-bullet-text {
            display: table-cell;
            vertical-align: top;
        }
        .resume-bullet-mark {
            color: #4261a8;
            padding-right: 7px;
            width: 12px;
        }
        .resume-bullet-text {
            overflow-wrap: break-word;
        }
    </style>
</head>
<body>
    <header class="resume-header">
        <h1>{{ $document['name'] }}</h1>
        @if ($document['subtitle'])
            <p class="subtitle">{{ $document['subtitle'] }}</p>
        @endif
        @if ($document['contact'] !== [])
            <p class="contact">{{ implode('  ·  ', $document['contact']) }}</p>
        @endif
    </header>

    @foreach ($document['sections'] as $section)
        <section class="resume-section">
            @if ($section['heading'])
                <h2>{{ $section['heading'] }}</h2>
            @endif
            @foreach ($section['items'] as $item)
                @if ($item['bullet'])
                    <div class="resume-bullet">
                        <span class="resume-bullet-mark">-</span>
                        <span class="resume-bullet-text">{{ $item['text'] }}</span>
                    </div>
                @else
                    <p class="resume-line {{ $item['entry'] ? 'resume-entry' : '' }}">{{ $item['text'] }}</p>
                @endif
            @endforeach
        </section>
    @endforeach
</body>
</html>
