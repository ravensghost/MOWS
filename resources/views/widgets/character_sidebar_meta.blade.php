{{-- 
@WidgetManifest({
    "name": "Sidebar User & Status Vitals",
    "description": "Displays character duty status, LOA indicator, owning user details, and social handles.",
    "icon": "bi-shield-shaded",
    "fields": {
        "title": { "type": "text", "label": "Widget Title", "default": "Account & Status" }
    }
})
--}}
@php
    $options = is_string($widget->pivot->instance_options) ? json_decode($widget->pivot->instance_options, true) : ($widget->pivot->instance_options ?? []);
    $rawTitle = trim($options['title'] ?? '');
    $title = $rawTitle !== '' ? $rawTitle : lexicon('label_Sidebar_DefaultTitle', 'Account & Status');

    $user = $character?->user;
    $status = strtolower($character?->status ?? 'active');
    
    $statusBadge = match($status) {
        'active'                    => 'success',
        'loa'                       => 'warning',
        'eloa'                      => 'danger',
        'honoured_dead', 'memorial' => 'primary',
        default                     => 'primary'
    };

    $statusLabel = match($status) {
        'active'        => lexicon('label_ServiceRecords_StatusActive', 'Active'),
        'loa'           => lexicon('label_ServiceRecords_StatusLoa', 'Leave of Absence (LOA)'),
        'eloa'          => lexicon('label_ServiceRecords_StatusEloa', 'Extended Leave of Absence (ELOA)'),
        'honoured_dead' => lexicon('label_ServiceRecords_StatusHonouredDead', 'Honoured Dead'),
        'memorial'      => lexicon('label_ServiceRecords_StatusMemorial', 'Memorial'),
        default         => strtoupper(str_replace('_', ' ', $status))
    };

    // Pull social handles from the users table schema (twitter_handle / facebook_handle)
    $twitter = $user?->twitter_handle ?? null;
    $facebook = $user?->facebook_handle ?? null;
    $facebookUrl = $facebook && \Illuminate\Support\Str::startsWith($facebook, ['http://', 'https://'])
        ? $facebook
        : ($facebook ? 'https://facebook.com/' . ltrim($facebook, '/') : null);
@endphp

<div class="bg-panel border-panel rounded-panel shadow-panel flex flex-col overflow-hidden mb-6 widget-character-sidebar-meta" x-data>
    @if($title)
        <div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap justify-between items-center gap-md">
            <span class="text-base font-bold text-accent">{{ $title }}</span>
            <i class="bi bi-shield-shaded text-accent"
               x-tooltip="'{{ addslashes(lexicon('tooltip_Sidebar_AccountStatusIcon', 'Account and duty status')) }}'"
               aria-label="{{ lexicon('tooltip_Sidebar_AccountStatusIcon', 'Account and duty status') }}"></i>
        </div>
    @endif

    <div class="p-panel flex flex-col gap-md">
        
        {{-- Duty Status / LOA Indicator --}}
        <div class="flex flex-row flex-wrap justify-between items-center gap-xs bg-panel-sub border-panel rounded-panel p-3">
            <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Sidebar_DutyStatus', 'Duty Status') }}</span>
            <x-badge :variant="$statusBadge"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_Sidebar_CurrentDutyStatus', 'Current character duty status')) }}'"
                     aria-label="{{ lexicon('tooltip_Sidebar_CurrentDutyStatus', 'Current character duty status') }}">
                {{ $statusLabel }}
            </x-badge>
        </div>

        {{-- Owning User / Writer Info --}}
        @if($user)
            <div class="flex flex-col gap-xs bg-panel-sub border-panel rounded-panel p-3">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Sidebar_Writer', 'Account Writer') }}</span>
                <span class="text-sm font-bold text-p">{{ $user->name }}</span>
            </div>
        @endif

        {{-- Social Handles (X/Twitter & Facebook) --}}
        @if($twitter || $facebookUrl)
            <div class="flex flex-col gap-xs bg-panel-sub border-panel rounded-panel p-3">
                <span class="text-xs font-bold text-input-muted uppercase tracking-wider">{{ lexicon('label_Sidebar_Socials', 'Social Channels') }}</span>
                <div class="flex flex-row flex-wrap gap-sm items-center mt-1">
                    @if($twitter)
                        <a href="https://twitter.com/{{ ltrim($twitter, '@') }}"
                           target="_blank"
                           rel="noopener"
                           class="text-xs text-accent hover-underline flex items-center gap-1"
                           x-tooltip="'{{ addslashes(lexicon('tooltip_Sidebar_TwitterProfile', 'View writer on X (Twitter)')) }}'"
                           aria-label="{{ lexicon('tooltip_Sidebar_TwitterProfile', 'View writer on X (Twitter)') }}">
                            <i class="bi bi-twitter-x" aria-hidden="true"></i>
                            <span>{{ '@' . ltrim($twitter, '@') }}</span>
                        </a>
                    @endif
                    @if($facebookUrl)
                        <a href="{{ $facebookUrl }}"
                           target="_blank"
                           rel="noopener"
                           class="text-xs text-accent hover-underline flex items-center gap-1"
                           x-tooltip="'{{ addslashes(lexicon('tooltip_Sidebar_FacebookProfile', 'View writer on Facebook')) }}'"
                           aria-label="{{ lexicon('tooltip_Sidebar_FacebookProfile', 'View writer on Facebook') }}">
                            <i class="bi bi-facebook" aria-hidden="true"></i>
                            <span>{{ lexicon('label_Sidebar_FacebookLink', 'Facebook') }}</span>
                        </a>
                    @endif
                </div>
            </div>
        @endif

    </div>
</div>