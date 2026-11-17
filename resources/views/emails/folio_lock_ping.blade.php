{{-- resources/views/emails/folio_lock_ping.blade.php --}}
@php
    $requesterName = $requester->name ?? lexicon('label_common_AnotherWriter', 'Another writer');
    $location = $folio->scene_location ?? lexicon('label_common_UnknownLocation', 'Unknown Location');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ lexicon('email_FolioLockPing_Subject', 'Collaborative Folio Lock Request') }}</title>
</head>
<body class="bg-body text-p font-base p-6">
    <div class="max-w-lg mx-auto bg-panel-body border-panel border-rounded shadow-panel p-panel-body flex flex-col gap-md">
        <div class="border-b-panel pb-3 flex flex-row items-center justify-between">
            <span class="text-lg font-bold text-h">{{ lexicon('email_FolioLockPing_Heading', 'Collaborative Folio Lock Ping') }}</span>
            <span class="text-xs text-muted">{{ config('app.name', 'MOWS') }}</span>
        </div>

        <div class="flex flex-col gap-sm text-sm text-p">
            <p class="m-0">
                <strong>{{ $requesterName }}</strong> {{ lexicon('email_FolioLockPing_Body', 'is requesting access to edit a collaborative draft folio that is currently locked under your session.') }}
            </p>

            <div class="bg-panel-sub border-panel border-rounded p-3 flex flex-col gap-xs">
                <div>
                    <strong class="text-h">{{ lexicon('label_Folio_Identifier', 'Folio ID') }}:</strong>
                    <span class="text-muted">#{{ $folio->id ?? '' }}</span>
                </div>
                <div>
                    <strong class="text-h">{{ lexicon('label_Folio_Location', 'Scene Location') }}:</strong>
                    <span class="text-muted">{{ $location }}</span>
                </div>
                @if(!empty($folio->locked_at))
                    <div>
                        <strong class="text-h">{{ lexicon('label_Folio_LockedSince', 'Locked Since') }}:</strong>
                        <span class="text-muted">{{ $folio->locked_at->diffForHumans() }}</span>
                    </div>
                @endif
            </div>

            <p class="m-0 text-xs text-muted">
                {{ lexicon('email_FolioLockPing_FooterNote', 'If you have finished your current edits, please save your draft so the editing lock is released for your co-authors.') }}
            </p>
        </div>

        <div class="border-t-actions pt-4 flex flex-row items-center justify-between">
            <a href="{{ url('/dashboard') }}" class="text-accent font-bold no-underline">
                {{ lexicon('btn_common_ReturnToDashboard', 'Return to Dashboard') }} &rarr;
            </a>
        </div>
    </div>
</body>
</html>