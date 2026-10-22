<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? 'MOWS Engine Notification' }}</title>
</head>
<body>
    <h2>{{ $heading ?? 'Station Transmission' }}</h2>
    <p>{{ $messageText ?? 'You have received a new notification on the platform.' }}</p>
    @if(!empty($actionUrl))
        <p><a href="{{ $actionUrl }}">{{ $actionLabel ?? 'View on Platform' }}</a></p>
    @endif
</body>
</html>