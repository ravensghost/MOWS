{{-- resources/views/emails/notification.blade.php --}}
@php
    $resolvedSubject = $subject ?? ($title ?? lexicon('email_Notification_DefaultSubject', 'System Notification'));
    $resolvedLines = isset($bodyLines) && is_array($bodyLines)
        ? $bodyLines
        : (!empty($body) ? [$body] : (!empty($messageText) ? [$messageText] : []));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $resolvedSubject }}</title>
</head>
<body class="bg-body text-p font-base p-6">
    <div class="max-w-lg mx-auto bg-panel-body border-panel border-rounded shadow-panel p-panel-body flex flex-col gap-md">
        <div class="border-b-panel pb-3 flex flex-row items-center justify-between">
            <span class="text-lg font-bold text-h">{{ $resolvedSubject }}</span>
            <span class="text-xs text-muted">{{ config('app.name', 'MOWS') }}</span>
        </div>

        <div class="flex flex-col gap-sm text-sm text-p">
            @foreach($resolvedLines as $line)
                <p class="m-0">{{ $line }}</p>
            @endforeach
        </div>

        @if(!empty($actionUrl))
            <div class="border-t-actions pt-4 flex flex-row items-center justify-between">
                <a href="{{ $actionUrl }}" class="text-accent font-bold no-underline">
                    {{ $actionText ?? lexicon('btn_common_ViewDetails', 'View Details') }} &rarr;
                </a>
            </div>
        @endif
    </div>
</body>
</html>