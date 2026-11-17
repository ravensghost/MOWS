{{-- resources/views/emails/folio_published.blade.php --}}
@php
    $personaName = $folio->activePersona->persona_name ?? lexicon('label_common_UnknownPersona', 'Unknown Persona');
    $volumeTitle = $folio->volume->volume_title ?? lexicon('label_common_UnknownVolume', 'Unknown Volume');
    $location = $folio->scene_location ?? lexicon('label_common_UnknownLocation', 'Unknown Location');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ lexicon('email_FolioPublished_Subject', 'New Story Folio Published') }}</title>
</head>
<body class="bg-body text-p font-base p-6">
    <div class="max-w-lg mx-auto bg-panel-body border-panel border-rounded shadow-panel p-panel-body flex flex-col gap-md">
        <div class="border-b-panel pb-3 flex flex-row items-center justify-between">
            <span class="text-lg font-bold text-h">{{ lexicon('email_FolioPublished_Heading', 'New Story Folio Published') }}</span>
            <span class="text-xs text-muted">{{ config('app.name', 'MOWS') }}</span>
        </div>

        <div class="flex flex-col gap-sm text-sm text-p">
            <p class="m-0">
                {{ lexicon('email_FolioPublished_Intro', 'A new story folio has been published and is now live.') }}
            </p>

            <div class="bg-panel-sub border-panel border-rounded p-3 flex flex-col gap-xs">
                <div>
                    <strong class="text-h">{{ lexicon('label_Folio_Volume', 'Volume') }}:</strong>
                    <span class="text-muted">{{ $volumeTitle }}</span>
                </div>
                <div>
                    <strong class="text-h">{{ lexicon('label_Folio_AuthorPersona', 'Posting As') }}:</strong>
                    <span class="text-muted">{{ $personaName }}</span>
                </div>
                <div>
                    <strong class="text-h">{{ lexicon('label_Folio_Timeline', 'In-Universe Timeline') }}:</strong>
                    <span class="text-muted">Day {{ $folio->ic_day ?? 1 }}, {{ $folio->ic_time ?? '0800' }}</span>
                </div>
                <div>
                    <strong class="text-h">{{ lexicon('label_Folio_Location', 'Scene Location') }}:</strong>
                    <span class="text-muted">{{ $location }}</span>
                </div>
                <div>
                    <strong class="text-h">{{ lexicon('label_Folio_ContentTiers', 'Content Tiers (L-S-V)') }}:</strong>
                    <span class="text-muted">{{ $folio->content_tier_l ?? 0 }}-{{ $folio->content_tier_s ?? 0 }}-{{ $folio->content_tier_v ?? 0 }}</span>
                </div>
            </div>

            @if(!empty($folio->folio_text))
                <div class="bg-panel-sub border-panel border-rounded p-3 text-muted font-italic">
                    {{ \Illuminate\Support\Str::limit(strip_tags($folio->folio_text), 350) }}
                </div>
            @endif
        </div>

        <div class="border-t-actions pt-4 flex flex-row items-center justify-between">
            <a href="{{ url('/folio/' . ($folio->id ?? '')) }}" class="text-accent font-bold no-underline">
                {{ lexicon('btn_Folio_ReadFull', 'Read Full Folio') }} &rarr;
            </a>
        </div>
    </div>
</body>
</html>