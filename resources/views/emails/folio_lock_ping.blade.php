@php
    $requesterName =$requester->name ?? lexicon('label_common_AnotherWriter', 'Another writer');
    $location =$folio->scene_location ?? lexicon('label_common_UnknownLocation', 'Unknown Location');
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ lexicon('email_FolioLockPing_Subject', 'Collaborative Folio Lock Notice') }}</title>
</head>
<body class="bg-main text-main p-panel font-sans">
    <div class="bg-panel border-panel rounded-panel shadow-panel p-panel max-w-2xl mx-auto flex flex-col gap-md">
        <div class="border-b-panel pb-sm">
            <h1 class="text-xl font-bold text-h m-0">
                {{ lexicon('email_FolioLockPing_Heading', 'Folio Edit Lock Notice') }}
            </h1>
        </div>

        <div class="flex flex-col gap-sm text-sm text-p">
            <p class="m-0">
                <strong>{{ $requesterName }}</strong> {{ lexicon('email_FolioLockPing_Body', 'is requesting access to edit a collaborative draft folio that is currently locked under your session.') }}
            </p>

            <div class="bg-panel-sub border-panel rounded-panel p-panel flex flex-col gap-xs">
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

        <div class="border-t-panel pt-md flex flex-row items-center justify-between">
            <a href="{{ url('/dashboard') }}" class="text-accent font-bold no-underline">
                {{ lexicon('btn_common_ReturnToDashboard', 'Return to Dashboard') }} &rarr;
            </a>
        </div>
    </div>
</body>
</html>