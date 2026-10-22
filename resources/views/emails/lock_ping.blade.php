<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>Chapter Lock Ping</title>
</head>
<body>
    <h2>Co-Writing Editor Lock Inquiry</h2>
    <p><strong>{{ $sender->name }}</strong> is asking if you have finished editing <strong>Chapter #{{ $chapter->id }}</strong> ({{ $chapter->location ?? 'Unknown Location' }}).</p>
    <p><a href="{{ url('/dashboard') }}">Return to the Chapter Editor</a></p>
</body>
</html>