<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $resume->original_name }} - Reviewed resume</title>
    <style>
        @page { margin: 48px 56px; }
        body { color: #17233c; font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; line-height: 1.5; }
        h1 { border-bottom: 2px solid #dfe6f2; font-size: 17pt; margin: 0 0 18px; padding-bottom: 10px; }
        .content { white-space: pre-wrap; overflow-wrap: break-word; }
    </style>
</head>
<body>
    <h1>{{ pathinfo($resume->original_name, PATHINFO_FILENAME) }}</h1>
    <div class="content">{{ $resume->optimized_text }}</div>
</body>
</html>
