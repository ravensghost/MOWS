@extends('layouts.app')

@section('title', lexicon('admin_label_settings', 'Global Settings'))

@section('content')
@php
    // Prepare language options for the JSON payload
    $langOptions = ['auto' => lexicon('label_setting_OptLangAuto', 'Auto-Detect (Browser Default)')];
    foreach($customLanguages ?? [] as $code => $displayName) {
        $langOptions[$code] = $displayName . ' (' . lexicon('label_setting_CustomLexicon', 'Custom Lexicon') . ')';
    }

    // Prepare Time System options for the JSON payload
    $tsOptions = ['' => lexicon('label_setting_OptRealWorldDefault', 'Real World (Default)')];
    foreach($timeSystems ?? [] as $system) {
        $tsOptions[$system->id] = $system->name;
    }

    $notConfiguredText = lexicon('msg_setting_NotConfigured', 'Not Configured');

    $settingsSearchCatalog = [
        ['key' => 'approval_mode', 'group' => 'general', 'label' => lexicon('label_settings_ApprovalReviewMode', 'Application Review Mode')],
        ['key' => 'rejection_protocol', 'group' => 'general', 'label' => lexicon('label_settings_RejectionProtocol', 'Rejection Protocol')],
        ['key' => 'global_lexicon_setting', 'group' => 'general', 'label' => lexicon('label_settings_GlobalLanguage', 'Global Site Language')],
        ['key' => 'universe_time_system', 'group' => 'mechanics', 'label' => lexicon('label_setting_UniverseTimeSystem', 'Universe Time System')],
        ['key' => 'content_shield_bypass', 'group' => 'mechanics', 'label' => lexicon('label_setting_ContentShield', 'Mature Content Shield')],
        ['key' => 'visual_mode', 'group' => 'mechanics', 'label' => lexicon('label_setting_RankAwardRendering', 'Rank & Award Rendering')],
        ['key' => 'ai_mode', 'group' => 'ai', 'label' => lexicon('label_setting_AIHosting', 'AI Hosting Environment')],
        ['key' => 'ai_provider', 'group' => 'ai', 'label' => lexicon('label_setting_AIProvider', 'Cloud AI Provider')],
        ['key' => 'ai_model', 'group' => 'ai', 'label' => lexicon('label_setting_AIModel', 'Target AI Model') . ' ' . lexicon('msg_setting_ModelHint', 'e.g., gemini-1.5-pro, gpt-4o')],
        ['key' => 'ai_api_key', 'group' => 'ai', 'label' => lexicon('label_setting_AI_API_Key', 'API Key')],
        ['key' => 'ai_local_endpoint', 'group' => 'ai', 'label' => lexicon('label_setting_AILocalEndpoint', 'Local API Endpoint URL') . ' ' . lexicon('msg_setting_EndpointHint', 'e.g., http://127.0.0.1:1234/v1 (LM Studio)')],
        ['key' => 'ai_local_model', 'group' => 'ai', 'label' => lexicon('label_setting_AILocalModel', 'Local Model Identifier') . ' ' . lexicon('msg_setting_LocalModelHint', 'e.g., llama-3-8b-instruct')],
        ['key' => 'ai_local_api_key', 'group' => 'ai', 'label' => lexicon('label_setting_AILocalApiKey', 'Local API Key (Optional)')],
        ['key' => 'game_setting', 'group' => 'general', 'label' => lexicon('label_setting_GameSetting', 'Game Setting / Franchise')],
        ['key' => 'lore_context', 'group' => 'general', 'label' => lexicon('label_setting_LoreContext', 'Lore & Era Context')],
    ];
@endphp

<div x-data="settingsManager('{{ $globalSettings['ai_mode'] ?? 'cloud' }}')" @ajax-success.window="refreshDOM()">
    <x-admin-grid-wrapper :page="$systemPage">
        
        <x-slot:top>
            <script type="application/json" x-ref="settingsSearchData">
                {!! json_encode($settingsSearchCatalog) !!}
            </script>

            <x-page-header :title="lexicon('admin_label_settings', 'Global Settings')" />
        </x-slot:top>

        <x-slot:middle>
            <div class="flex flex-col gap-lg">

                {{-- Instant Fuzzy Search Bar (Fuse.js) --}}
                <div class="bg-panel border border-panel rounded-panel shadow-panel p-panel">
                    <x-form-input name="settings_search"
                                  id="settings-search-input"
                                  :label="lexicon('label_setting_FilterSettings', 'Filter Global Settings')"
                                  :placeholder="lexicon('label_setting_FilterPlaceholder', 'Search configuration settings by name or keyword...')"
                                  :tooltip="lexicon('tooltip_setting_FilterInput', 'Instantaneously filter global configuration settings using fuzzy search')"
                                  xModel="searchQuery"
                                  @input="filterSettings()" />
                </div>

                {{-- MAIN SETTINGS TABLE --}}
                <div class="bg-panel border border-panel rounded-panel shadow-panel overflow-hidden">
                    <x-table id="main-settings-table" x-ref="mainSettingsTable" :bareBody="true">
                        <x-slot:headers>
                            <th class="p-4 text-left">{{ lexicon('label_setting_name', 'Setting') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_setting_value', 'Current Value') }}</th>
                            <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                        </x-slot:headers>
                        
                        {{-- General Configuration Group --}}
                        <tbody x-data="{ expanded: true }" x-show="matchesGroup('general')">
                            <tr class="bg-panel-sub border-b-table-row hover-bg-panel-hover cursor-pointer select-none" 
                                @click="expanded = !expanded"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'"
                                aria-label="{{ lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel') }}">
                                <td colspan="3" class="px-4 py-2 font-bold text-h text-xs uppercase tracking-wider">
                                    <div class="flex flex-row items-center gap-sm">
                                        <span class="text-xs text-muted" x-text="(expanded || searchQuery.trim().length > 0) ? '▼' : '▶'"></span>
                                        <i class="bi bi-sliders text-accent"></i>
                                        <span>{{ lexicon('label_setting_GeneralConfiguration', 'General Configuration') }}</span>
                                    </div>
                                </td>
                            </tr>

                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && matchesSetting('approval_mode')"
                                           x-cloak
                                           :label="lexicon('label_settings_ApprovalReviewMode', 'Application Review Mode')"
                                           editClick="openModal('approval_mode', '{{ addslashes(lexicon('label_settings_ApprovalReviewMode', 'Application Review Mode')) }}', '{{ $globalSettings['approval_mode'] ?? 'single' }}', 'select', { single: '{{ addslashes(lexicon('label_setting_OptSingle', 'Single-Authority Approval')) }}', council: '{{ addslashes(lexicon('label_setting_OptCouncil', '14-Day Council Voting')) }}' })">
                                {{ ($globalSettings['approval_mode'] ?? '') == 'council' ? lexicon('label_setting_OptCouncil', '14-Day Council Voting') : lexicon('label_setting_OptSingle', 'Single-Authority Approval') }}
                            </x-setting-row>

                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && matchesSetting('rejection_protocol')"
                                           x-cloak
                                           :label="lexicon('label_settings_RejectionProtocol', 'Rejection Protocol')"
                                           editClick="openModal('rejection_protocol', '{{ addslashes(lexicon('label_settings_RejectionProtocol', 'Rejection Protocol')) }}', '{{ $globalSettings['rejection_protocol'] ?? 'soft_lock' }}', 'select', { soft_lock: '{{ addslashes(lexicon('label_setting_OptSoftLock', 'Soft Lock (Allow Edit & Resubmit)')) }}', hard_purge: '{{ addslashes(lexicon('label_setting_OptHardPurge', 'Hard Purge (Delete Account)')) }}' })">
                                {{ ($globalSettings['rejection_protocol'] ?? '') == 'hard_purge' ? lexicon('label_setting_OptHardPurge', 'Hard Purge (Delete Account)') : lexicon('label_setting_OptSoftLock', 'Soft Lock (Allow Edit & Resubmit)') }}
                            </x-setting-row>

                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && matchesSetting('global_lexicon_setting')"
                                           x-cloak
                                           :label="lexicon('label_settings_GlobalLanguage', 'Global Site Language')"
                                           editClick="openModal('global_lexicon_setting', '{{ addslashes(lexicon('label_settings_GlobalLanguage', 'Global Site Language')) }}', '{{ $globalSettings['global_lexicon_setting'] ?? 'auto' }}', 'select', {{ json_encode($langOptions) }})">
                                {{ ($globalSettings['global_lexicon_setting'] ?? 'auto') == 'auto' ? lexicon('label_setting_OptLangAuto', 'Auto-Detect (Browser Default)') : ($customLanguages[$globalSettings['global_lexicon_setting']] ?? $globalSettings['global_lexicon_setting']) }}
                            </x-setting-row>
                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && matchesSetting('game_setting')"
                                x-cloak
                                :label="lexicon('label_setting_GameSetting', 'Game Setting / Franchise')"
                                :tooltip="lexicon('tooltip_setting_GameSetting', 'The primary universe or franchise setting')"
                                editClick="openModal('game_setting', '{{ addslashes(lexicon('label_setting_GameSetting', 'Game Setting / Franchise')) }}', '{{ addslashes($globalSettings['game_setting'] ?? '') }}', 'text')">
                                {{ $globalSettings['game_setting'] ?? $notConfiguredText }}
                            </x-setting-row>

                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && matchesSetting('lore_context')"
                                            x-cloak
                                            :label="lexicon('label_setting_LoreContext', 'Lore & Era Context')"
                                            :tooltip="lexicon('tooltip_setting_LoreContext', 'Brief era, tone, and station premise injected into AI prompts')"
                                            editClick="openModal('lore_context', '{{ addslashes(lexicon('label_setting_LoreContext', 'Lore & Era Context')) }}', '{{ addslashes($globalSettings['lore_context'] ?? '') }}', 'text')">
                                {{ $globalSettings['lore_context'] ?? $notConfiguredText }}
                            </x-setting-row>
                        </tbody>

                        {{-- Mechanics & Display Group --}}
                        <tbody x-data="{ expanded: false }" x-show="matchesGroup('mechanics')">
                            <tr class="bg-panel-sub border-b-table-row hover-bg-panel-hover cursor-pointer border-t border-panel select-none" 
                                @click="expanded = !expanded"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'"
                                aria-label="{{ lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel') }}">
                                <td colspan="3" class="px-4 py-2 font-bold text-h text-xs uppercase tracking-wider">
                                    <div class="flex flex-row items-center gap-sm">
                                        <span class="text-xs text-muted" x-text="(expanded || searchQuery.trim().length > 0) ? '▼' : '▶'"></span>
                                        <i class="bi bi-clock-history text-accent"></i>
                                        <span>{{ lexicon('label_setting_MechanicsDisplay', 'Mechanics & Display') }}</span>
                                    </div>
                                </td>
                            </tr>
                            
                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && matchesSetting('universe_time_system')"
                                           x-cloak
                                           :label="lexicon('label_setting_UniverseTimeSystem', 'Universe Time System')"
                                           editClick="openModal('universe_time_system', '{{ addslashes(lexicon('label_setting_UniverseTimeSystem', 'Universe Time System')) }}', '{{ $globalSettings['universe_time_system'] ?? '' }}', 'select', {{ json_encode($tsOptions) }})">
                                @php
                                    $activeId = $globalSettings['universe_time_system'] ?? '';
                                    $activeTs = collect($timeSystems ?? [])->firstWhere('id', $activeId);
                                @endphp
                                {{ $activeTs ? $activeTs->name : lexicon('label_setting_OptRealWorldDefault', 'Real World (Default)') }}
                            </x-setting-row>
                            
                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && matchesSetting('content_shield_bypass')"
                                           x-cloak
                                           :label="lexicon('label_setting_ContentShield', 'Mature Content Shield')"
                                           editClick="openModal('content_shield_bypass', '{{ addslashes(lexicon('label_setting_ContentShield', 'Mature Content Shield')) }}', '{{ $globalSettings['content_shield_bypass'] ?? 'user' }}', 'select', { user: '{{ addslashes(lexicon('label_setting_OptUserBypass', 'Allow User Bypass')) }}', locked: '{{ addslashes(lexicon('label_setting_OptLocked', 'Global Lock (Require Manual Click)')) }}' })">
                                {{ ($globalSettings['content_shield_bypass'] ?? '') == 'locked' ? lexicon('label_setting_OptLocked', 'Global Lock (Require Manual Click)') : lexicon('label_setting_OptUserBypass', 'Allow User Bypass') }}
                            </x-setting-row>

                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && matchesSetting('visual_mode')"
                                           x-cloak
                                           :label="lexicon('label_setting_RankAwardRendering', 'Rank & Award Rendering')"
                                           editClick="openModal('visual_mode', '{{ addslashes(lexicon('label_setting_RankAwardRendering', 'Rank & Award Rendering')) }}', '{{ $globalSettings['visual_mode'] ?? 'dynamic' }}', 'select', { dynamic: '{{ addslashes(lexicon('label_setting_OptDynamic', 'Mode 2: Dynamic CSS Layering')) }}', static: '{{ addslashes(lexicon('label_setting_OptStatic', 'Mode 1: Static Image Only')) }}' })">
                                {{ ($globalSettings['visual_mode'] ?? '') == 'static' ? lexicon('label_setting_OptStatic', 'Mode 1: Static Image Only') : lexicon('label_setting_OptDynamic', 'Mode 2: Dynamic CSS Layering') }}
                            </x-setting-row>
                        </tbody>

                        {{-- AI Integration Group --}}
                        <tbody x-data="{ expanded: false }" x-show="matchesGroup('ai')">
                            <tr class="bg-panel-sub border-b-table-row hover-bg-panel-hover cursor-pointer border-t border-panel select-none" 
                                @click="expanded = !expanded"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'"
                                aria-label="{{ lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel') }}">
                                <td colspan="3" class="px-4 py-2 font-bold text-h text-xs uppercase tracking-wider">
                                    <div class="flex flex-row items-center gap-sm">
                                        <span class="text-xs text-muted" x-text="(expanded || searchQuery.trim().length > 0) ? '▼' : '▶'"></span>
                                        <i class="bi bi-cpu text-accent"></i>
                                        <span>{{ lexicon('label_setting_AIIntegration', 'AI Integration Configuration') }}</span>
                                    </div>
                                </td>
                            </tr>

                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && matchesSetting('ai_mode')"
                                           x-cloak
                                           :label="lexicon('label_setting_AIHosting', 'AI Hosting Environment')"
                                           editClick="openModal('ai_mode', '{{ addslashes(lexicon('label_setting_AIHosting', 'AI Hosting Environment')) }}', ai_mode, 'select', { cloud: '{{ addslashes(lexicon('label_setting_AICloud', 'Cloud-Based (API)')) }}', local: '{{ addslashes(lexicon('label_setting_AILocal', 'Self-Hosted / Local')) }}' })">
                                <span x-text="ai_mode === 'local' ? '{{ addslashes(lexicon('label_setting_AILocal', 'Self-Hosted / Local')) }}' : '{{ addslashes(lexicon('label_setting_AICloud', 'Cloud-Based (API)')) }}'">
                                    {{ ($globalSettings['ai_mode'] ?? 'cloud') == 'local' ? lexicon('label_setting_AILocal', 'Self-Hosted / Local') : lexicon('label_setting_AICloud', 'Cloud-Based (API)') }}
                                </span>
                            </x-setting-row>

                            {{-- CLOUD-SPECIFIC FIELDS --}}
                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && ai_mode === 'cloud' && matchesSetting('ai_provider')"
                                           x-cloak
                                           :label="lexicon('label_setting_AIProvider', 'Cloud AI Provider')"
                                           editClick="openModal('ai_provider', '{{ addslashes(lexicon('label_setting_AIProvider', 'Cloud AI Provider')) }}', '{{ $globalSettings['ai_provider'] ?? 'gemini' }}', 'select', { gemini: '{{ addslashes(lexicon('label_setting_OptAIGemini', 'Google Gemini')) }}', openai: '{{ addslashes(lexicon('label_setting_OptAIOpenAI', 'OpenAI')) }}', anthropic: '{{ addslashes(lexicon('label_setting_OptAIAnthropic', 'Anthropic')) }}' })">
                                {{ ucfirst($globalSettings['ai_provider'] ?? $notConfiguredText) }}
                            </x-setting-row>

                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && ai_mode === 'cloud' && matchesSetting('ai_model')"
                                           x-cloak
                                           :label="lexicon('label_setting_AIModel', 'Target AI Model')"
                                           :hint="lexicon('msg_setting_ModelHint', 'e.g., gemini-1.5-pro, gpt-4o')"
                                           editClick="openModal('ai_model', '{{ addslashes(lexicon('label_setting_AIModel', 'Target AI Model')) }}', '{{ addslashes($globalSettings['ai_model'] ?? '') }}', 'text')">
                                {{ $globalSettings['ai_model'] ?? $notConfiguredText }}
                            </x-setting-row>

                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && ai_mode === 'cloud' && matchesSetting('ai_api_key')"
                                           x-cloak
                                           :label="lexicon('label_setting_AI_API_Key', 'API Key')"
                                           editClick="openModal('ai_api_key', '{{ addslashes(lexicon('label_setting_AI_API_Key', 'API Key')) }}', '{{ addslashes($globalSettings['ai_api_key'] ?? '') }}', 'password')">
                                {{ !empty($globalSettings['ai_api_key']) ? '••••••••••••••••' : $notConfiguredText }}
                            </x-setting-row>

                            {{-- LOCAL-SPECIFIC FIELDS --}}
                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && ai_mode === 'local' && matchesSetting('ai_local_endpoint')"
                                           x-cloak
                                           :label="lexicon('label_setting_AILocalEndpoint', 'Local API Endpoint URL')"
                                           :hint="lexicon('msg_setting_EndpointHint', 'e.g., http://127.0.0.1:1234/v1 (LM Studio)')"
                                           editClick="openModal('ai_local_endpoint', '{{ addslashes(lexicon('label_setting_AILocalEndpoint', 'Local API Endpoint URL')) }}', '{{ addslashes($globalSettings['ai_local_endpoint'] ?? '') }}', 'url')">
                                {{ $globalSettings['ai_local_endpoint'] ?? $notConfiguredText }}
                            </x-setting-row>

                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && ai_mode === 'local' && matchesSetting('ai_local_model')"
                                           x-cloak
                                           :label="lexicon('label_setting_AILocalModel', 'Local Model Identifier')"
                                           :hint="lexicon('msg_setting_LocalModelHint', 'e.g., llama-3-8b-instruct')"
                                           editClick="openModal('ai_local_model', '{{ addslashes(lexicon('label_setting_AILocalModel', 'Local Model Identifier')) }}', '{{ addslashes($globalSettings['ai_local_model'] ?? '') }}', 'text')">
                                {{ $globalSettings['ai_local_model'] ?? $notConfiguredText }}
                            </x-setting-row>

                            <x-setting-row x-show="(expanded || searchQuery.trim().length > 0) && ai_mode === 'local' && matchesSetting('ai_local_api_key')"
                                           x-cloak
                                           :label="lexicon('label_setting_AILocalApiKey', 'Local API Key (Optional)')"
                                           :hint="lexicon('msg_setting_LocalApiHint', 'Only if required by proxy (e.g., LiteLLM)')"
                                           editClick="openModal('ai_local_api_key', '{{ addslashes(lexicon('label_setting_AILocalApiKey', 'Local API Key (Optional)')) }}', '{{ addslashes($globalSettings['ai_local_api_key'] ?? '') }}', 'password')">
                                {{ !empty($globalSettings['ai_local_api_key']) ? '••••••••••••••••' : $notConfiguredText }}
                            </x-setting-row>
                        </tbody>
                    </x-table>
                </div>

                {{-- BOOK STATUSES MANAGEMENT ACCORDION --}}
                <x-accordion-panel :title="lexicon('admin_label_BookStatuses', 'Book Statuses')" :expanded="false">
                    <x-slot:top>
                        <x-button type="button" 
                                  variant="primary" 
                                  class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                                  @click="openStatusModal('', '', '', 0)"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_AddStatus', 'Create a new book status')) }}'"
                                  aria-label="{{ lexicon('tooltip_Story_AddStatus', 'Create a new book status') }}">
                            <i class="bi bi-plus-lg"></i>
                            <span>{{ lexicon('btn_common_AddStatus', 'Add Status') }}</span>
                        </x-button>
                    </x-slot:top>
                    
                    <x-table id="book-statuses-table" x-ref="bookStatusesTable">
                        <x-slot:headers>
                            <th class="p-4 text-left">{{ lexicon('label_common_Name', 'Name') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_common_Slug', 'Slug') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_common_SortOrder', 'Sort Order') }}</th>
                            <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                        </x-slot:headers>
                        
                        @forelse($bookStatuses ?? [] as $status)
                            <tr class="border-b-table-row hover-bg-table-row">
                                <td class="p-4 font-bold text-p">
                                    <x-badge :variant="$status->slug ?? 'primary'"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_Story_StatusPreviewBadge', 'Status badge preview')) }}'"
                                             aria-label="{{ lexicon('tooltip_Story_StatusPreviewBadge', 'Status badge preview') }}">
                                        {{ $status->name }}
                                    </x-badge>
                                </td>
                                <td class="p-4 text-muted font-mono text-xs">{{ $status->slug }}</td>
                                <td class="p-4 text-muted">{{ $status->sort_order }}</td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row flex-wrap items-center justify-end gap-sm text-sm">
                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-warning hover-text-h flex flex-row items-center gap-xs" 
                                                  @click="openStatusModal('{{ $status->id }}', '{{ addslashes($status->name) }}', '{{ addslashes($status->slug) }}', {{$status->sort_order }})"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_EditStatus', 'Edit book status')) }}'"
                                                  aria-label="{{ lexicon('tooltip_Story_EditStatus', 'Edit book status') }}">
                                            <i class="bi bi-pencil"></i>
                                            <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                                        </x-button>
                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-danger hover-text-h flex flex-row items-center gap-xs" 
                                                  x-data="deleteAction('/admin/statuses/{{ $status->id }}', 'DELETE', '{{ addslashes($status->name) }}')" 
                                                  @click.prevent="confirmDelete()"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_DeleteStatus', 'Delete this book status')) }}'"
                                                  aria-label="{{ lexicon('tooltip_Story_DeleteStatus', 'Delete this book status') }}">
                                            <i class="bi bi-trash"></i>
                                            <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-muted italic">{{ lexicon('msg_common_NoneFound', 'No statuses found.') }}</td>
                            </tr>
                        @endforelse
                    </x-table>
                </x-accordion-panel>

                {{-- TIME SYSTEMS MANAGEMENT ACCORDION --}}
                <x-accordion-panel :title="lexicon('admin_label_TimeSystems', 'Immersive Time Systems')" :expanded="false">
                    <x-slot:top>
                        <x-button type="button" 
                                  variant="primary" 
                                  class="p-btn-sm text-sm flex flex-row items-center gap-xs" 
                                  @click="openTsModal()"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_setting_AddTimeSystem', 'Create a new custom universe time system')) }}'"
                                  aria-label="{{ lexicon('tooltip_setting_AddTimeSystem', 'Create a new custom universe time system') }}">
                            <i class="bi bi-plus-lg"></i>
                            <span>{{ lexicon('btn_setting_AddTimeSystem', 'Add Time System') }}</span>
                        </x-button>
                    </x-slot:top>
                    
                    <x-table id="time-systems-table" x-ref="timeSystemsTable">
                        <x-slot:headers>
                            <th class="p-4 text-left">{{ lexicon('label_common_Name', 'Name') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_setting_EngineType', 'Engine Type') }}</th>
                            <th class="p-4 text-left">{{ lexicon('label_setting_PrefixSuffix', 'Prefix / Suffix') }}</th>
                            <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                        </x-slot:headers>
                        
                        @forelse($timeSystems ?? [] as $ts)
                            <tr class="border-b-table-row hover-bg-table-row">
                                <td class="p-4 font-bold text-p">{{ $ts->name }}</td>
                                <td class="p-4 text-muted">
                                    <x-badge variant="info"
                                             x-tooltip="'{{ addslashes(lexicon('tooltip_setting_EngineTypeBadge', 'Active calculation engine for this time system')) }}'"
                                             aria-label="{{ lexicon('tooltip_setting_EngineTypeBadge', 'Active calculation engine for this time system') }}">
                                        {{ ucfirst(str_replace('_', ' ', $ts->engine_type)) }}
                                    </x-badge>
                                </td>
                                <td class="p-4 text-muted font-mono text-xs">"{{ $ts->prefix }}..." / "...{{ $ts->suffix }}"</td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row flex-wrap items-center justify-end gap-sm text-sm">
                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-warning hover-text-h flex flex-row items-center gap-xs" 
                                                  @click="openTsModal({{ json_encode($ts) }})"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_setting_EditTimeSystem', 'Edit this time system')) }}'"
                                                  aria-label="{{ lexicon('tooltip_setting_EditTimeSystem', 'Edit this time system') }}">
                                            <i class="bi bi-pencil"></i>
                                            <span>{{ lexicon('btn_common_Edit', 'Edit') }}</span>
                                        </x-button>
                                        <x-button type="button" 
                                                  variant="text" 
                                                  class="text-danger hover-text-h flex flex-row items-center gap-xs" 
                                                  x-data="deleteAction('/admin/time-systems/{{ $ts->id }}', 'DELETE', '{{ addslashes($ts->name) }}')" 
                                                  @click.prevent="confirmDelete()"
                                                  x-tooltip="'{{ addslashes(lexicon('tooltip_setting_DeleteTimeSystem', 'Delete this time system')) }}'"
                                                  aria-label="{{ lexicon('tooltip_setting_DeleteTimeSystem', 'Delete this time system') }}">
                                            <i class="bi bi-trash"></i>
                                            <span>{{ lexicon('btn_common_Delete', 'Delete') }}</span>
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-muted italic">{{ lexicon('msg_setting_NoTimeSystems', 'No custom time systems found.') }}</td>
                            </tr>
                        @endforelse
                    </x-table>
                </x-accordion-panel>

            </div>
        </x-slot:middle>

        <x-slot:bottom>
            {{-- MODAL 1: CENTRALISED SETTINGS MODAL --}}
            <x-modal show="modal.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="`{{ addslashes(lexicon('btn_common_Edit', 'Edit')) }} ${modal.title}`"></span>
                </x-slot:top>
                
                <form action="/admin/settings/update" method="POST" class="flex flex-col gap-md" x-data="ajaxForm" @submit.prevent="submit($event)">
                    @csrf
                    <div class="flex flex-col gap-xs">
                        <label for="central-setting-input" class="text-xs font-bold text-input-muted" x-text="modal.title"></label>
                        
                        <template x-if="modal.type === 'select'">
                            <select id="central-setting-input"
                                    :name="'settings[' + modal.key + ']'" 
                                    x-model="modal.value" 
                                    x-ref="modalSelect"
                                    :aria-label="modal.title"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                                <template x-for="(label, val) in modal.options" :key="val">
                                    <option :value="val" x-text="label" :selected="String(val) === String(modal.value)"></option>
                                </template>
                            </select>
                        </template>

                        <template x-if="['text', 'url', 'password'].includes(modal.type)">
                            <input id="central-setting-input"
                                   :type="modal.type" 
                                   :name="'settings[' + modal.key + ']'" 
                                   x-model="modal.value" 
                                   :aria-label="modal.title"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_setting_EnterSettingValue', 'Enter the new configuration value')) }}'"
                                   class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                        </template>
                    </div>

                    <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="button" 
                                  variant="secondary" 
                                  @click="modal.open = false"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'"
                                  aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                        <x-button type="submit" 
                                  variant="primary"
                                  ::disabled="submitting"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_setting_SaveSetting', 'Save configuration change')) }}'"
                                  aria-label="{{ lexicon('tooltip_setting_SaveSetting', 'Save configuration change') }}"
                                  x-text="submitting ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_common_Save', 'Save Changes')) }}'">
                            {{ lexicon('btn_common_Save', 'Save Changes') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 2: BOOK STATUS MODAL --}}
            <x-modal show="statusModal.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="statusModal.isEdit ? '{{ addslashes(lexicon('label_Story_EditStatus', 'Edit Status')) }}' : '{{ addslashes(lexicon('label_Story_CreateStatus', 'Create Status')) }}'"></span>
                </x-slot:top>

                <form :action="statusModal.isEdit ? `/admin/statuses/${statusModal.id}` : '/admin/statuses'" 
                      method="POST" 
                      class="flex flex-col gap-md" 
                      x-data="ajaxForm" 
                      @submit.prevent="submit($event)">
                    @csrf
                    <template x-if="statusModal.isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <x-form-input name="name"
                                  id="setting-status-name"
                                  :label="lexicon('label_common_Name', 'Name')"
                                  :tooltip="lexicon('tooltip_Story_StatusNameInput', 'Enter the display name for this book status')"
                                  xModel="statusModal.name"
                                  :required="true" />

                    <x-form-input name="sort_order"
                                  id="setting-status-sort-order"
                                  type="number"
                                  :label="lexicon('label_common_SortOrder', 'Sort Order')"
                                  :tooltip="lexicon('tooltip_Story_StatusSortOrderInput', 'Numeric display order for this book status')"
                                  xModel="statusModal.sort_order" />

                    <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="button" 
                                  variant="secondary" 
                                  @click="statusModal.open = false"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'"
                                  aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                        <x-button type="submit" 
                                  variant="primary"
                                  ::disabled="submitting"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_Story_SaveStatus', 'Save book status')) }}'"
                                  aria-label="{{ lexicon('tooltip_Story_SaveStatus', 'Save book status') }}"
                                  x-text="submitting ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_common_Save', 'Save Changes')) }}'">
                            {{ lexicon('btn_common_Save', 'Save Changes') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>

            {{-- MODAL 3: TIME SYSTEM EDITOR MODAL --}}
            <x-modal show="tsModal.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="tsModal.isEdit ? '{{ addslashes(lexicon('label_setting_EditTimeSystem', 'Edit Time System')) }}' : '{{ addslashes(lexicon('label_setting_NewTimeSystem', 'New Time System')) }}'"></span>
                </x-slot:top>
                
                <form :action="tsModal.isEdit ? `/admin/time-systems/${tsModal.data.id}` : '/admin/time-systems'" 
                      method="POST" 
                      class="flex flex-col gap-md" 
                      x-data="ajaxForm" 
                      @submit.prevent="submit($event)">
                    @csrf
                    <template x-if="tsModal.isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    
                    <x-form-input name="name"
                                  id="ts-name-input"
                                  :label="lexicon('label_setting_TimeSystemName', 'System Name (e.g., TNG Stardate)')"
                                  :tooltip="lexicon('tooltip_setting_TimeSystemNameInput', 'Enter the display name for this universe time system')"
                                  xModel="tsModal.data.name"
                                  :required="true" />

                    <div class="flex flex-col gap-xs">
                        <label for="ts-engine-type-select" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_setting_MathEngine', 'Math Engine') }}</span>
                        </label>
                        <select id="ts-engine-type-select"
                                name="engine_type" 
                                x-model="tsModal.data.engine_type" 
                                aria-label="{{ lexicon('label_setting_MathEngine', 'Math Engine') }}"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_setting_MathEngineSelect', 'Choose how dates are calculated and formatted')) }}'"
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                            <option value="real_world">{{ lexicon('label_setting_OptEngineRealWorld', 'Real World (No Math)') }}</option>
                            <option value="fractional">{{ lexicon('label_setting_OptEngineFractional', 'Fractional Base (Stardates)') }}</option>
                            <option value="offset">{{ lexicon('label_setting_OptEngineOffset', 'Linear Offset (Fantasy/Sci-Fi Years)') }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md" x-show="tsModal.data.engine_type !== 'real_world'" x-cloak>
                        <x-form-input name="prefix"
                                      id="ts-prefix-input"
                                      :label="lexicon('label_setting_PrefixString', 'Prefix String')"
                                      :placeholder="lexicon('label_setting_PlaceholderPrefix', 'e.g. Stardate ')"
                                      :tooltip="lexicon('tooltip_setting_PrefixInput', 'Text prepended before the calculated date value')"
                                      xModel="tsModal.data.prefix" />

                        <x-form-input name="suffix"
                                      id="ts-suffix-input"
                                      :label="lexicon('label_setting_SuffixString', 'Suffix String')"
                                      :placeholder="lexicon('label_setting_PlaceholderSuffix', 'e.g.  AD')"
                                      :tooltip="lexicon('tooltip_setting_SuffixInput', 'Text appended after the calculated date value')"
                                      xModel="tsModal.data.suffix" />

                        <x-form-input name="base_earth_year"
                                      id="ts-base-earth-year-input"
                                      type="number"
                                      :label="lexicon('label_setting_BaseEarthYear', 'Base Earth Year')"
                                      placeholder="2026"
                                      :tooltip="lexicon('tooltip_setting_BaseEarthYearInput', 'Reference Earth year used as the zero point')"
                                      xModel="tsModal.data.base_earth_year" />

                        <div x-show="tsModal.data.engine_type === 'fractional'" x-cloak>
                            <x-form-input name="base_system_value"
                                          id="ts-base-system-value-input"
                                          type="number"
                                          :label="lexicon('label_setting_BaseSystemValue', 'Base System Value')"
                                          placeholder="100000"
                                          :tooltip="lexicon('tooltip_setting_BaseSystemValueInput', 'Starting stardate or fractional base number')"
                                          xModel="tsModal.data.base_system_value" />
                        </div>

                        <div x-show="tsModal.data.engine_type === 'fractional'" x-cloak>
                            <x-form-input name="multiplier"
                                          id="ts-multiplier-input"
                                          type="number"
                                          step="0.01"
                                          :label="lexicon('label_setting_Multiplier', 'Multiplier')"
                                          placeholder="1000"
                                          :tooltip="lexicon('tooltip_setting_MultiplierInput', 'Units added per Earth year')"
                                          xModel="tsModal.data.multiplier" />
                        </div>
                    </div>

                    <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="button" 
                                  variant="secondary" 
                                  @click="tsModal.open = false"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_Cancel', 'Cancel and close modal')) }}'"
                                  aria-label="{{ lexicon('tooltip_common_Cancel', 'Cancel and close modal') }}">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                        <x-button type="submit" 
                                  variant="primary"
                                  ::disabled="submitting"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_setting_SaveTimeSystem', 'Save time system configuration')) }}'"
                                  aria-label="{{ lexicon('tooltip_setting_SaveTimeSystem', 'Save time system configuration') }}"
                                  x-text="submitting ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_setting_SaveTimeSystem', 'Save System')) }}'">
                            {{ lexicon('btn_setting_SaveTimeSystem', 'Save System') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>
        </x-slot:bottom>

    </x-admin-grid-wrapper>
</div>
@endsection