{{-- resources/views/admin/configurations.blade.php --}}
@extends('layouts.app')

@php
    $langOptions = ['auto' => lexicon('label_setting_OptLangAuto', 'Auto-Detect (Browser Default)')];
    foreach ($customLanguages ?? [] as $code => $displayName) {
        $langOptions[$code] = $displayName . ' (' . lexicon('label_setting_CustomLexicon', 'Custom Lexicon') . ')';
    }

    $tsOptions = ['' => lexicon('label_setting_OptRealWorldDefault', 'Real World (Default)')];
    foreach ($timeSystems ?? [] as $system) {
        $tsOptions[$system->id] = $system->engine_title;
    }

    $notConfiguredText = lexicon('msg_setting_NotConfigured', 'Not Configured');

    $settingsCatalog = [
        'general' => [
            'title' => lexicon('heading_settings_General', 'General & Lore Governance'),
            'icon'  => 'bi-Sliders',
            'items' => [
                ['token' => 'game_setting', 'label' => lexicon('label_setting_GameSetting', 'Game Setting / Franchise'), 'hint' => lexicon('hint_setting_GameSetting', 'Primary universe or franchise name used across the portal and AI prompts.')],
                ['token' => 'lore_context', 'label' => lexicon('label_setting_LoreContext', 'Lore & Era Context'), 'hint' => lexicon('hint_setting_LoreContext', 'Canonical era, year, or campaign summary injected into lore-aware tools.')],
                ['token' => 'approval_review_mode', 'label' => lexicon('label_settings_ApprovalReviewMode', 'Application Review Mode'), 'hint' => lexicon('hint_settings_ApprovalReviewMode', 'Direct admin approval or council voting evaluation mode.')],
                ['token' => 'rejection_protocol', 'label' => lexicon('label_settings_RejectionProtocol', 'Rejection Protocol'), 'hint' => lexicon('hint_settings_RejectionProtocol', 'Default handling when a candidate petition is rejected.')],
                ['token' => 'global_lexicon_setting', 'label' => lexicon('label_settings_GlobalLanguage', 'Global Site Language'), 'hint' => lexicon('hint_settings_GlobalLanguage', 'Default semantic locale applied when browser auto-detect is overridden.')],
                ['token' => 'theme_governance', 'label' => lexicon('label_setting_ThemeGovernance', 'Theme & Skin Governance'), 'hint' => lexicon('hint_setting_ThemeGovernance', 'Allow user skin selection or enforce the global default thematic binding.')],
            ],
        ],
        'mechanics' => [
            'title' => lexicon('heading_settings_Mechanics', 'RPG Mechanics & Thresholds'),
            'icon'  => 'bi-cpu',
            'items' => [
                ['token' => 'universe_time_system', 'label' => lexicon('label_setting_UniverseTimeSystem', 'Universe Time System'), 'hint' => lexicon('hint_setting_UniverseTimeSystem', 'Active chronological engine used to calculate in-universe dates.')],
                ['token' => 'character_limit_enforced', 'label' => lexicon('label_setting_CharacterLimitEnforced', 'Enforce Character Limit'), 'hint' => lexicon('hint_setting_CharacterLimitEnforced', 'Enable or disable maximum PC/SC persona limits per player.')],
                ['token' => 'pc_limit', 'label' => lexicon('label_setting_PcLimit', 'Primary Character (PC) Limit'), 'hint' => lexicon('hint_setting_PcLimit', 'Maximum active Primary Characters per player.')],
                ['token' => 'sc_limit', 'label' => lexicon('label_setting_ScLimit', 'Secondary Character (SC) Limit'), 'hint' => lexicon('hint_setting_ScLimit', 'Maximum active Supporting/Secondary Characters per player.')],
                ['token' => 'content_shield_bypass', 'label' => lexicon('label_setting_ContentShield', 'Mature Content Shield'), 'hint' => lexicon('hint_setting_ContentShield', 'Default L-S-V content shield threshold policy.')],
                ['token' => 'draft_autosave_interval', 'label' => lexicon('label_setting_DraftAutoSaveInterval', 'Draft Auto-Save Interval (ms)'), 'hint' => lexicon('hint_setting_DraftAutoSaveInterval', 'Millisecond interval between automatic folio draft saves.')],
                ['token' => 'inactivity_threshold_days', 'label' => lexicon('label_setting_InactivityThreshold', 'Player Inactivity Threshold (Days)'), 'hint' => lexicon('hint_setting_InactivityThreshold', 'Days without login before flagging a player in the Command Centre.')],
            ],
        ],
        'ai' => [
            'title' => lexicon('heading_settings_AI', 'AI Assistant & Generation Engine'),
            'icon'  => 'bi-stars',
            'items' => [
                ['token' => 'ai_mode', 'label' => lexicon('label_setting_AIHosting', 'AI Hosting Environment'), 'hint' => lexicon('hint_setting_AIHosting', 'Choose between Cloud API provider or self-hosted Local LLM endpoint.')],
                ['token' => 'ai_provider', 'label' => lexicon('label_setting_AIProvider', 'Cloud AI Provider'), 'hint' => lexicon('hint_setting_AIProvider', 'Target provider (e.g., gemini, openai, anthropic, ollama).')],
                ['token' => 'ai_model', 'label' => lexicon('label_setting_AIModel', 'Target AI Model'), 'hint' => lexicon('hint_setting_AIModel', 'Model identifier used for proofreading and lore generation.')],
                ['token' => 'ai_api_key', 'label' => lexicon('label_setting_AI_API_Key', 'API Key'), 'hint' => lexicon('hint_setting_AI_API_Key', 'Encrypted API credential for cloud AI requests.')],
            ],
        ],
        'notifications' => [
            'title' => lexicon('heading_settings_Notifications', 'Multi-Channel Notifications & Webhooks'),
            'icon'  => 'bi-bell',
            'items' => [
                ['token' => 'notifications_discord_enabled', 'label' => lexicon('label_setting_EnableDiscord', 'Enable Discord Notifications'), 'hint' => lexicon('hint_setting_EnableDiscord', 'Master toggle for outbound Discord webhook dispatches.')],
                ['token' => 'discord_webhook_chapters', 'label' => lexicon('label_setting_DiscordChapters', 'Discord Webhook: Chapters'), 'hint' => lexicon('hint_setting_DiscordChapters', 'Webhook URL for published folio/chapter announcements.')],
                ['token' => 'discord_webhook_news', 'label' => lexicon('label_setting_DiscordNews', 'Discord Webhook: News Stories'), 'hint' => lexicon('hint_setting_DiscordNews', 'Webhook URL for published bulletin announcements.')],
                ['token' => 'discord_webhook_alerts', 'label' => lexicon('label_setting_DiscordAlerts', 'Discord Webhook: Writer Alerts & Tags'), 'hint' => lexicon('hint_setting_DiscordAlerts', 'Webhook URL for turn tags and lock ping alerts.')],
                ['token' => 'notifications_email_enabled', 'label' => lexicon('label_setting_EnableEmail', 'Enable Email Notifications'), 'hint' => lexicon('hint_setting_EnableEmail', 'Master toggle for outbound email notifications.')],
                ['token' => 'mail_mailer', 'label' => lexicon('label_setting_MailMailer', 'Email Transport Driver'), 'hint' => lexicon('hint_setting_MailMailer', 'Transport driver (smtp, mail, sendmail, log).')],
                ['token' => 'mail_from_name', 'label' => lexicon('label_setting_MailFromName', 'Email Sender Name'), 'hint' => lexicon('hint_setting_MailFromName', 'Display name on outbound system emails.')],
                ['token' => 'email_subject_prefix', 'label' => lexicon('label_setting_EmailSubjectPrefix', 'Email Subject Line Prefix'), 'hint' => lexicon('hint_setting_EmailSubjectPrefix', 'Prefix prepended to outbound notification emails.')],
                ['token' => 'notifications_pm_enabled', 'label' => lexicon('label_setting_EnablePm', 'Enable Automated On-Site PM Alerts'), 'hint' => lexicon('hint_setting_EnablePm', 'Dispatch automated correspondence threads on system events.')],
            ],
        ],
    ];
@endphp

@section('title', lexicon('admin_label_settings', 'Global Settings'))

@section('content')
<x-grid-wrapper :book="$systemBook">
    <x-slot:content>
        <div class="flex flex-col gap-lg"
             x-data="configurationManager('{{ addslashes($settings['ai_mode'] ?? 'cloud') }}', '{{ addslashes($settings['mail_mailer'] ?? 'smtp') }}')"
             @ajax-success.window="refreshDOM()"
             x-ref="configurationsWrapper">

            {{-- Page Header --}}
            <x-page-header :title="lexicon('admin_label_settings', 'Global Settings & Chronological Engines')"
                           :subtitle="lexicon('desc_admin_settings', 'Configure system-wide governance, AI integration, multi-channel webhooks, and in-universe time engines.')"
                           icon="bi-gear-wide-connected">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="config_search"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('placeholder_settings_Search', 'Search settings by token or label...')"
                                :tooltip="lexicon('tooltip_settings_Search', 'Filter configuration tokens and labels')" />

                    @can('manage_time_systems')
                        <x-button type="button"
                                  variant="primary"
                                  size="sm"
                                  icon="bi-clock-history"
                                  :tooltip="lexicon('tooltip_ts_CreateEngine', 'Create a new in-universe chronological engine')"
                                  @click="openModal('create', {}, 'engine')">
                            + {{ lexicon('btn_ts_NewEngine', 'New Time Engine') }}
                        </x-button>
                    @endcan
                </x-slot:actions>
            </x-page-header>

            {{-- Configuration Groups --}}
            @foreach($settingsCatalog as $groupKey => $group)
                <x-tab type="accordion"
                       :title="$group['title']"
                       :icon="$group['icon']"
                       :open="true">
                    <x-table type="admin"
                             :headers="[
                                 lexicon('col_settings_Parameter', 'Configuration Parameter'),
                                 lexicon('col_settings_CurrentValue', 'Current Payload'),
                                 lexicon('col_common_Actions', 'Actions')
                             ]">
                        @foreach($group['items'] as $item)
                            @php
                                $token = $item['token'];
                                $rawVal = $settings[$token] ?? '';
                                $displayVal = $rawVal !== '' && $rawVal !== null ? $rawVal : $notConfiguredText;
                                if ($token === 'ai_api_key' && !empty($rawVal)) {
                                    $displayVal = '••••••••' . substr((string) $rawVal, -4);
                                } elseif ($token === 'universe_time_system') {
                                    $displayVal = $tsOptions[$rawVal] ?? $notConfiguredText;
                                } elseif ($token === 'global_lexicon_setting') {
                                    $displayVal = $langOptions[$rawVal] ?? $rawVal ?: $notConfiguredText;
                                }
                            @endphp
                            <x-table type="config"
                                     :label="$item['label']"
                                     :hint="$item['hint']"
                                     :tooltip="$token"
                                     data-search-item
                                     data-config-item
                                     data-id="{{ $token }}"
                                     data-configuration_token="{{ $token }}"
                                     data-label="{{ $item['label'] }}"
                                     x-show="isItemVisible('{{ $token }}')"
                                     :editClick="'openModal(\'edit\', ' . Js::from([
                                         'configuration_token' => $token,
                                         'configuration_payload' => $token === 'ai_api_key' ? '' : (string) $rawVal,
                                         'label' => $item['label'],
                                         'hint' => $item['hint'],
                                     ]) . ', \'config\')'">
                                @if($rawVal === '' || $rawVal === null)
                                    <span class="text-xs text-muted font-italic">{{ $notConfiguredText }}</span>
                                @elseif(in_array((string) $rawVal, ['1', 'true', 'enabled'], true))
                                    <x-badge type="pill" variant="success">{{ $displayVal }}</x-badge>
                                @elseif(in_array((string) $rawVal, ['0', 'false', 'disabled'], true))
                                    <x-badge type="pill" variant="muted">{{ $displayVal }}</x-badge>
                                @else
                                    <span class="font-mono text-xs text-accent">{{ $displayVal }}</span>
                                @endif
                            </x-table>
                        @endforeach
                    </x-table>
                </x-tab>
            @endforeach

            {{-- Chronological Engines Section --}}
            <x-tab type="accordion"
                   :title="lexicon('heading_ts_ChronologicalEngines', 'Chronological Time Engines')"
                   icon="bi-hourglass-split"
                   :open="true">
                <div x-ref="enginesTableContent">
                    <x-table type="admin"
                             :headers="[
                                 lexicon('col_ts_Title', 'Engine Title'),
                                 lexicon('col_ts_Algorithm', 'Algorithm'),
                                 lexicon('col_ts_Format', 'Prefix / Suffix'),
                                 lexicon('col_ts_BaseYear', 'Base Earth Year'),
                                 lexicon('col_ts_Multiplier', 'Multiplier'),
                                 lexicon('col_common_Status', 'Status'),
                                 lexicon('col_common_Actions', 'Actions')
                             ]"
                             :emptyText="lexicon('msg_ts_NoEngines', 'No custom chronological engines configured.')">
                        @foreach($timeSystems ?? [] as $engine)
                            <tr class="border-b-table-row hover-bg-panel-hover transition-colors">
                                <td class="p-4 font-bold text-h">{{ $engine->engine_title }}</td>
                                <td class="p-4 font-mono text-xs text-accent">{{ $engine->calculation_algorithm }}</td>
                                <td class="p-4 text-xs text-muted">
                                    {{ $engine->date_prefix ?: '—' }} / {{ $engine->date_suffix ?: '—' }}
                                </td>
                                <td class="p-4 text-sm text-p">{{ $engine->base_earth_year }}</td>
                                <td class="p-4 text-sm text-p">×{{ $engine->temporal_multiplier }}</td>
                                <td class="p-4">
                                    <x-badge type="pill" :variant="$engine->is_active ? 'success' : 'muted'">
                                        {{ $engine->is_active ? lexicon('status_common_Active', 'Active') : lexicon('status_common_Inactive', 'Inactive') }}
                                    </x-badge>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row flex-wrap justify-end items-center gap-xs">
                                        <x-button type="button"
                                                  variant="ghost"
                                                  size="sm"
                                                  icon="bi-pencil"
                                                  :tooltip="lexicon('tooltip_ts_EditEngine', 'Edit this chronological engine')"
                                                  @click="openModal('edit', {{ Js::from([
                                                      'id' => $engine->id,
                                                      'engine_title' => $engine->engine_title,
                                                      'calculation_algorithm' => $engine->calculation_algorithm,
                                                      'date_prefix' => $engine->date_prefix ?? '',
                                                      'date_suffix' => $engine->date_suffix ?? '',
                                                      'base_earth_year' => $engine->base_earth_year,
                                                      'base_earth_modifier' => $engine->base_earth_modifier,
                                                      'base_system_value' => $engine->base_system_value,
                                                      'temporal_multiplier' => $engine->temporal_multiplier,
                                                      'is_active' => (bool) $engine->is_active,
                                                  ]) }}, 'engine')">
                                            {{ lexicon('btn_common_Edit', 'Edit') }}
                                        </x-button>

                                        <x-button type="button"
                                                  variant="danger"
                                                  size="sm"
                                                  icon="bi-trash"
                                                  :tooltip="lexicon('tooltip_ts_DeleteEngine', 'Delete this chronological engine')"
                                                  x-data="deleteAction('{{ route('admin.chronological_engines.destroy', $engine) }}', 'DELETE', '{{ addslashes($engine->engine_title) }}')"
                                                  @click="confirmDelete()">
                                            {{ lexicon('btn_common_Delete', 'Delete') }}
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </div>
            </x-tab>

            {{-- MODAL 1: EDIT CONFIGURATION SETTING --}}
            <x-modal show="modals.config.open" onClose="closeModal('config')" maxWidth="max-w-lg">
                <x-slot:top>
                    <div class="flex flex-col gap-xs">
                        <span class="text-lg font-bold text-h" x-text="modals.config.label || '{{ addslashes(lexicon('modal_settings_EditTitle', 'Modify Configuration')) }}'"></span>
                        <span class="font-mono text-xs text-accent" x-text="modals.config.configuration_token"></span>
                    </div>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.config.action" xMethod="modals.config.method">
                    <input type="hidden" name="configuration_token" :value="modals.config.configuration_token">

                    <p class="text-xs text-muted m-0" x-show="modals.config.hint" x-text="modals.config.hint"></p>

                    <x-metadata type="text"
                                name="configuration_payload"
                                xModel="modals.config.configuration_payload"
                                :label="lexicon('label_settings_ValuePayload', 'Configuration Value')"
                                :tooltip="lexicon('tooltip_settings_ValuePayload', 'Enter the updated value for this configuration token')" />

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModal('config')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_common_Save', 'Save configuration setting')">
                            {{ lexicon('btn_common_Save', 'Save Setting') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

            {{-- MODAL 2: CREATE / EDIT CHRONOLOGICAL ENGINE --}}
            <x-modal show="modals.engine.open" onClose="closeModal('engine')" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="modals.engine.mode === 'edit'
                        ? '{{ addslashes(lexicon('modal_ts_EditEngine', 'Edit Chronological Engine')) }}'
                        : '{{ addslashes(lexicon('modal_ts_CreateEngine', 'Create Chronological Engine')) }}'"></span>
                </x-slot:top>

                <x-meta type="modal" xAction="modals.engine.action" xMethod="modals.engine.method">
                    <x-metadata type="text"
                                name="engine_title"
                                xModel="modals.engine.engine_title"
                                :label="lexicon('label_ts_EngineTitle', 'Engine Title')"
                                :placeholder="lexicon('placeholder_ts_EngineTitle', 'e.g. TNG Stardate or 3025 Inner Sphere')"
                                :tooltip="lexicon('tooltip_ts_EngineTitle', 'Display name of this time system')"
                                :required="true" />

                    <x-metadata type="select"
                                name="calculation_algorithm"
                                xModel="modals.engine.calculation_algorithm"
                                :label="lexicon('label_ts_Algorithm', 'Calculation Algorithm')"
                                :tooltip="lexicon('tooltip_ts_Algorithm', 'Mathematical formula used to convert real dates')">
                        <option value="stardate">{{ lexicon('option_ts_Stardate', 'Linear Stardate / Multiplier') }}</option>
                        <option value="year_offset">{{ lexicon('option_ts_YearOffset', 'Year Offset Calendar (e.g. +360 Years)') }}</option>
                        <option value="custom_epoch">{{ lexicon('option_ts_CustomEpoch', 'Custom Epoch Counter') }}</option>
                    </x-metadata>

                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                        <x-metadata type="text"
                                    name="date_prefix"
                                    xModel="modals.engine.date_prefix"
                                    :label="lexicon('label_ts_DatePrefix', 'Date Prefix')"
                                    :placeholder="lexicon('placeholder_ts_DatePrefix', 'e.g. SD ')"
                                    :tooltip="lexicon('tooltip_ts_DatePrefix', 'Text prepended before the calculated date')" />

                        <x-metadata type="text"
                                    name="date_suffix"
                                    xModel="modals.engine.date_suffix"
                                    :label="lexicon('label_ts_DateSuffix', 'Date Suffix')"
                                    :placeholder="lexicon('placeholder_ts_DateSuffix', 'e.g. ABY / BBY')"
                                    :tooltip="lexicon('tooltip_ts_DateSuffix', 'Text appended after the calculated date')" />
                    </div>

                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                        <x-metadata type="number"
                                    name="base_earth_year"
                                    xModel="modals.engine.base_earth_year"
                                    :label="lexicon('label_ts_BaseEarthYear', 'Base Earth Year')"
                                    :tooltip="lexicon('tooltip_ts_BaseEarthYear', 'Reference real-world year (e.g. 2026)')"
                                    :required="true" />

                        <x-metadata type="number"
                                    name="base_earth_modifier"
                                    xModel="modals.engine.base_earth_modifier"
                                    :label="lexicon('label_ts_BaseModifier', 'Base Earth Modifier')"
                                    :tooltip="lexicon('tooltip_ts_BaseModifier', 'Offset modifier applied to the base Earth year')" />
                    </div>

                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                        <x-metadata type="number"
                                    name="base_system_value"
                                    xModel="modals.engine.base_system_value"
                                    :label="lexicon('label_ts_BaseSystemValue', 'Base In-Universe Value')"
                                    :tooltip="lexicon('tooltip_ts_BaseSystemValue', 'Starting stardate or in-universe year at the base Earth year')" />

                        <x-metadata type="number"
                                    name="temporal_multiplier"
                                    xModel="modals.engine.temporal_multiplier"
                                    :label="lexicon('label_ts_TemporalMultiplier', 'Temporal Multiplier')"
                                    :tooltip="lexicon('tooltip_ts_TemporalMultiplier', 'Rate of in-universe time progression relative to real time')" />
                    </div>

                    <x-metadata type="checkbox"
                                name="is_active"
                                value="1"
                                xModel="modals.engine.is_active"
                                :label="lexicon('label_ts_IsActive', 'Set as Active Chronological Engine')"
                                :tooltip="lexicon('tooltip_ts_IsActive', 'Enable this engine for portal-wide date conversions')" />

                    <x-slot:footer>
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModal('engine')">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="primary"
                                  icon="bi-save"
                                  :tooltip="lexicon('tooltip_common_Save', 'Save chronological engine')">
                            {{ lexicon('btn_common_Save', 'Save Engine') }}
                        </x-button>
                    </x-slot:footer>
                </x-meta>
            </x-modal>

        </div>
    </x-slot:content>
</x-grid-wrapper>
@endsection