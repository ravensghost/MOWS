<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>New Chapter Published</title>
</head>
<body>
    <h2>New Chapter Published</h2>
    <p>Chapter #{{ $chapter->id ?? '' }} ({{ $chapter->chapter_type ?? 'mission' }}) at <strong>{{ $chapter->location ?? 'Unknown Location' }}</strong> has been published.</p>
    <p><a href="{{ url('/dashboard') }}">Read on the Platform</a></p>
</body>
</html>