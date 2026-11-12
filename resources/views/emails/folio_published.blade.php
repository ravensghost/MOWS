@php
    $personaName =$folio->activePersona->persona_name ?? lexicon('label_common_UnknownPersona', 'Unknown Persona');
    $volumeTitle =$folio->volume->volume_title ?? lexicon('label_common_UnknownVolume', 'Unknown Volume');
    $location =$folio->scene_location ?? lexicon('label_common_UnknownLocation', 'Unknown Location');
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ lexicon('email_FolioPublished_Subject', 'New Folio Published') }}</title>
</head>
<body class="bg-main text-main p-panel font-sans">
    <div class="bg-panel border-panel rounded-panel shadow-panel p-panel max-w-2xl mx-auto flex flex-col gap-md">
        <div class="border-b-panel pb-sm">
            <h1 class="text-xl font-bold text-h m-0">
                {{ lexicon('email_FolioPublished_Heading', 'New Folio Published') }}
            </h1>
        </div>

        <div class="flex flex-col gap-sm text-sm text-p">
            <p class="m-0">
                {{ lexicon('email_FolioPublished_Intro', 'A new story folio has been published and is now live.') }}
            </p>

            <div class="bg-panel-sub border-panel rounded-panel p-panel flex flex-col gap-xs">
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
                <div class="bg-panel-sub border-panel rounded-panel p-panel text-muted italic">
                    {{ \Illuminate\Support\Str::limit(strip_tags($folio->folio_text), 350) }}
                </div>
            @endif
        </div>

        <div class="border-t-panel pt-md flex flex-row items-center justify-between">
            <a href="{{ url('/folio/' . ($folio->id ?? '')) }}" class="text-accent font-bold no-underline">
                {{ lexicon('btn_Folio_ReadFull', 'Read Full Folio') }} &rarr;
            </a>
        </div>
    </div>
</body>
</html>