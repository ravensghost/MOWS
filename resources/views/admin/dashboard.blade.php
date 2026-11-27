{{-- resources/views/admin/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('admin_label_AdminDashboard', 'Command Centre'))

@section('content')
<div x-data="loaHubManager()"
     @ajax-success.window="refreshDOM()"
     x-ref="loaHubTableContent"
     class="flex flex-col gap-lg">

    <x-grid-wrapper :book="$systemBook">
        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_AdminDashboard', 'Command Centre')"
                           :subtitle="lexicon('desc_admin_AdminDashboard', 'Monitor roster readiness, leave of absence countdowns, player activity thresholds, and monthly writing metrics.')"
                           icon="bi-speedometer2">
                <x-slot:actions>
                    <x-metadata type="search"
                                name="loa_hub_search"
                                :bare="true"
                                xModel="searchQuery"
                                :placeholder="lexicon('placeholder_AdminDashboard_Search', 'Search player or character...')"
                                :tooltip="lexicon('tooltip_AdminDashboard_Search', 'Filter players across LOA and activity tables')" />

                    <x-badge type="pill"
                             variant="primary"
                             icon="bi-shield-lock"
                             :tooltip="lexicon('tooltip_AdminDashboard_RoleContext', 'Your active system role and control panel scope')">
                        {{ ucfirst(auth()->user()->role ?? lexicon('label_common_AdminRole', 'Admin')) }} &mdash; {{ lexicon('label_AdminDashboard_MasterControlPanel', 'Master Control Panel') }}
                    </x-badge>
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:middle>
            {{-- Top Summary Metric Cards --}}
            <div class="grid grid-cols-1 md-grid-cols-3 lg-grid-cols-6 gap-md w-full mb-4">
                <x-tab type="sub" :tooltip="lexicon('tooltip_AdminDashboard_PendingApps', 'Character applications awaiting review')">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold text-accent">{{ $pendingApps ?? 0 }}</span>
                        <i class="bi bi-file-earmark-person text-accent text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase text-wider">{{ lexicon('label_AdminDashboard_PendingApps', 'Pending Petitions') }}</span>
                </x-tab>

                <x-tab type="sub" :tooltip="lexicon('tooltip_AdminDashboard_ActiveUsers', 'Total registered user accounts')">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold text-success">{{ $activeUsers ?? 0 }}</span>
                        <i class="bi bi-people text-success text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase text-wider">{{ lexicon('label_AdminDashboard_ActiveUsers', 'Active Users') }}</span>
                </x-tab>

                <x-tab type="sub" :tooltip="lexicon('tooltip_AdminDashboard_Characters', 'Total active personae on the roster')">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold text-accent">{{ $totalCharacters ?? 0 }}</span>
                        <i class="bi bi-person-badge text-accent text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase text-wider">{{ lexicon('label_AdminDashboard_Characters', 'Personae') }}</span>
                </x-tab>

                <x-tab type="sub" :tooltip="lexicon('tooltip_AdminDashboard_OpenSlots', 'Available open assignment slots across all divisions')">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold text-warning">{{ $openPositions ?? 0 }}</span>
                        <i class="bi bi-person-plus text-warning text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase text-wider">{{ lexicon('label_AdminDashboard_OpenSlots', 'Open Assignments') }}</span>
                </x-tab>

                <x-tab type="sub" :tooltip="lexicon('tooltip_AdminDashboard_OnLoaCount', 'Total players currently on Leave of Absence or Extended Leave')">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold text-warning">{{ $loaCount ?? 0 }}</span>
                        <i class="bi bi-calendar-range text-warning text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase text-wider">{{ lexicon('label_AdminDashboard_OnLoaCount', 'Players on LOA / ELOA') }}</span>
                </x-tab>

                <x-tab type="sub" :tooltip="lexicon('tooltip_AdminDashboard_InactiveCount', 'Active players exceeding the login inactivity threshold')">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold {{ ($flaggedInactiveCount ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ $flaggedInactiveCount ?? 0 }}</span>
                        <i class="bi bi-exclamation-triangle {{ ($flaggedInactiveCount ?? 0) > 0 ? 'text-danger' : 'text-success' }} text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase text-wider">
                        {{ lexicon('label_AdminDashboard_InactiveCount', 'Flagged Inactive') }} (&gt;{{ $inactivityThresholdDays ?? 30 }}d)
                    </span>
                </x-tab>
            </div>
        </x-slot:middle>

        <x-slot:bottom>
            <div x-ref="table-wrapper" class="flex flex-col gap-lg">
                {{-- PANEL 1: ACTIVE LOA / ELOA ROSTER & COUNTDOWN HUB --}}
                <x-tab type="accordion"
                       :title="lexicon('label_AdminDashboard_LoaHubTitle', 'Active LOA & ELOA Roster')"
                       icon="bi-calendar2-week"
                       :open="true">
                    <x-slot:top>
                        <x-badge type="pill" variant="warning">
                            {{ $loaCount ?? 0 }} {{ lexicon('label_Dashboard_OnLeave', 'On Leave (LOA / ELOA)') }}
                        </x-badge>
                    </x-slot:top>

                    <x-table type="admin"
                             :headers="[
                                 lexicon('label_common_Name', 'Player'),
                                 lexicon('label_AdminDashboard_Characters', 'Personae'),
                                 lexicon('label_common_Status', 'Leave Type'),
                                 lexicon('label_Dashboard_ExpectedReturnDate', 'Expected Return Date'),
                                 lexicon('label_AdminDashboard_DaysRemaining', 'Countdown'),
                                 lexicon('label_common_Actions', 'Actions')
                             ]"
                             :emptyText="lexicon('msg_AdminDashboard_NoActiveLoa', 'No players are currently on Leave of Absence.')">
                        @foreach($loaPlayers ?? [] as $loaUser)
                            <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                data-search-item
                                data-loa-user-item
                                data-id="{{ $loaUser->id }}"
                                data-name="{{ $loaUser->name }}"
                                data-username="{{ $loaUser->name }}"
                                x-show="isItemVisible({{ $loaUser->id }})">
                                <td class="p-4 font-bold text-h">{{ $loaUser->name }}</td>
                                <td class="p-4 text-muted">{{ $loaUser->persona_names }}</td>
                                <td class="p-4">
                                    <x-badge type="pill" :variant="$loaUser->leave_type === 'ELOA' ? 'danger' : 'warning'">
                                        {{ $loaUser->leave_type }}
                                    </x-badge>
                                </td>
                                <td class="p-4 font-mono text-xs text-p">{{ $loaUser->return_display }}</td>
                                <td class="p-4">
                                    @if($loaUser->days_remaining === null)
                                        <x-badge type="pill" variant="muted">{{ lexicon('status_Loa_Indefinite', 'Indefinite') }}</x-badge>
                                    @elseif($loaUser->is_expired)
                                        <x-badge type="pill" variant="danger" icon="bi-exclamation-octagon">
                                            {{ lexicon('status_Loa_OverdueBy', 'Overdue by') }} {{ abs($loaUser->days_remaining) }} {{ Str::plural('day', abs($loaUser->days_remaining)) }}
                                        </x-badge>
                                    @elseif($loaUser->days_remaining === 0)
                                        <x-badge type="pill" variant="info">{{ lexicon('status_Loa_DueBackToday', 'Due Back Today') }}</x-badge>
                                    @else
                                        <x-badge type="pill" variant="success">
                                            {{ $loaUser->days_remaining }} {{ Str::plural('day',$loaUser->days_remaining) }} {{ lexicon('status_Loa_Left', 'left') }}
                                        </x-badge>
                                    @endif
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row flex-wrap items-center justify-end gap-xs">
                                        <x-button type="button"
                                                  variant="success"
                                                  size="sm"
                                                  icon="bi-check2-circle"
                                                  :tooltip="lexicon('tooltip_AdminDashboard_ClearLoa', 'Clear LOA status and return player personae to Active Duty')"
                                                  @click="quickClearLoa({{ $loaUser->id }}, '{{ addslashes($loaUser->name) }}')">
                                            {{ lexicon('btn_AdminDashboard_ClearLoa', 'Return to Duty') }}
                                        </x-button>

                                        <x-button type="button"
                                                  variant="secondary"
                                                  size="sm"
                                                  icon="bi-calendar-plus"
                                                  :tooltip="lexicon('tooltip_AdminDashboard_ExtendLoa', 'Modify or extend expected return date')"
                                                  @click="openLoaModal({{ $loaUser->id }}, '{{ addslashes($loaUser->name) }}', true, '{{$loaUser->return_date ?? '' }}')">
                                            {{ lexicon('btn_AdminDashboard_ExtendLoa', 'Update / Extend') }}
                                        </x-button>

                                        <x-button type="button"
                                                  variant="ghost"
                                                  size="sm"
                                                  icon="bi-bell"
                                                  :tooltip="lexicon('tooltip_AdminDashboard_PingPlayer', 'Send an automated activity or LOA check-in alert via enabled channels')"
                                                  @click="sendPlayerPing({{ $loaUser->id }}, '{{ addslashes($loaUser->name) }}', 'loa')">
                                            {{ lexicon('btn_AdminDashboard_PingPlayer', 'Ping Player') }}
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-tab>

                {{-- PANEL 2: PLAYER ACTIVITY & INACTIVITY AUDIT --}}
                <x-tab type="accordion"
                       :title="lexicon('label_AdminDashboard_InactivityTitle', 'Player Activity & Inactivity Audit')"
                       icon="bi-activity"
                       :open="true">
                    <x-slot:top>
                        <x-badge type="pill" variant="info">
                            {{ lexicon('label_AdminDashboard_Threshold', 'Inactivity Threshold:') }} {{ $inactivityThresholdDays ?? 30 }} {{ lexicon('label_common_Days', 'days') }}
                        </x-badge>
                    </x-slot:top>

                    <x-table type="admin"
                             :headers="[
                                 lexicon('label_common_Name', 'Player'),
                                 lexicon('label_AdminDashboard_Characters', 'Personae'),
                                 lexicon('label_common_Status', 'Duty Status'),
                                 lexicon('label_AdminDashboard_LastLogin', 'Last Login'),
                                 lexicon('label_AdminDashboard_LastPost', 'Last Post'),
                                 lexicon('label_common_Actions', 'Actions')
                             ]"
                             :emptyText="lexicon('msg_AdminDashboard_NoPlayers', 'No registered players found.')">
                        @foreach($playerActivity ?? [] as $player)
                            <tr class="border-b-table-row hover-bg-panel-hover transition-colors"
                                data-search-item
                                data-loa-user-item
                                data-id="{{ $player->id }}"
                                data-name="{{ $player->name }}"
                                data-username="{{ $player->name }}"
                                x-show="isItemVisible({{ $player->id }})">
                                <td class="p-4 font-bold text-h">
                                    <span>{{ $player->name }}</span>
                                    <span class="text-xs text-muted font-normal ml-1">({{ ucfirst($player->role) }})</span>
                                </td>
                                <td class="p-4 text-muted">{{ $player->persona_names }}</td>
                                <td class="p-4">
                                    @if($player->is_inactive)
                                        <x-badge type="pill" variant="danger" icon="bi-exclamation-triangle">
                                            {{ lexicon('status_Users_Inactive', 'Inactive') }} ({{ $player->days_since_login }}d)
                                        </x-badge>
                                    @elseif($player->is_on_loa)
                                        <x-badge type="pill" :variant="$player->leave_type === 'ELOA' ? 'danger' : 'warning'">
                                            {{ $player->leave_type }}
                                        </x-badge>
                                    @else
                                        <x-badge type="pill" variant="success">{{ lexicon('status_Users_Active', 'Active') }}</x-badge>
                                    @endif
                                </td>
                                <td class="p-4">
                                    <span class="{{ $player->is_inactive ? 'text-danger font-bold' : 'text-p' }}">
                                        {{ $player->last_login_human }}
                                    </span>
                                    <span class="block text-xs text-muted">{{ $player->last_login_date }}</span>
                                </td>
                                <td class="p-4 text-xs text-muted">{{ $player->last_post_human }}</td>
                                <td class="p-4 text-right">
                                    <div class="flex flex-row flex-wrap items-center justify-end gap-xs">
                                        <x-button type="button"
                                                  variant="secondary"
                                                  size="sm"
                                                  icon="bi-calendar-event"
                                                  :tooltip="lexicon('tooltip_Users_SelectLoaStatus', 'Set duty or leave status')"
                                                  @click="openLoaModal({{ $player->id }}, '{{ addslashes($player->name) }}', {{ $player->is_on_loa ? 'true' : 'false' }}, '{{ $player->return_date ?? '' }}')">
                                            {{ lexicon('label_Users_StatusLoa', 'LOA') }}
                                        </x-button>

                                        <x-button type="button"
                                                  variant="ghost"
                                                  size="sm"
                                                  icon="bi-bell"
                                                  :tooltip="lexicon('tooltip_AdminDashboard_PingPlayer', 'Send an automated activity or LOA check-in alert via enabled channels')"
                                                  @click="sendPlayerPing({{ $player->id }}, '{{ addslashes($player->name) }}', 'inactivity')">
                                            {{ lexicon('btn_AdminDashboard_PingPlayer', 'Ping Player') }}
                                        </x-button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-tab>

                {{-- PANEL 3: MONTHLY WRITER METRICS SCOREBOARD --}}
                <x-tab type="accordion"
                       :title="lexicon('label_AdminDashboard_MonthlyWriting', 'Monthly Writing Activity') . ' (' . now()->format('F Y') . ')'"
                       icon="bi-bar-chart-fill"
                       :open="true">
                    <x-table type="admin"
                             :headers="[
                                 lexicon('col_Metrics_UserName', 'Player Name'),
                                 lexicon('col_Metrics_PersonaName', 'Persona Name'),
                                 lexicon('col_Metrics_PostsCount', 'Folios This Month'),
                                 lexicon('col_Metrics_WordsCount', 'Words This Month')
                             ]"
                             :emptyText="lexicon('msg_Metrics_Empty', 'No writing activity recorded for this month yet.')">
                        @foreach($currentMonthMetrics ?? [] as $metric)
                            <tr class="border-b-table-row hover-bg-panel-hover transition-colors">
                                <td class="p-4 font-bold text-h">{{ $metric->user_name }}</td>
                                <td class="p-4 text-muted">{{ $metric->persona_name ?? lexicon('label_common_UnassignedOoc', 'Unassigned / OOC') }}</td>
                                <td class="p-4">
                                    <x-badge type="pill" variant="primary">{{ $metric->post_count }}</x-badge>
                                </td>
                                <td class="p-4 text-right font-bold text-success">{{ number_format($metric->total_words) }}</td>
                            </tr>
                        @endforeach
                    </x-table>
                </x-tab>
            </div>
        </x-slot:bottom>
    </x-grid-wrapper>

    {{-- MODAL: QUICK-ACTION LOA / ELOA UPDATE & EXTENSION MODAL --}}
    <x-modal show="modals.loa.open" onClose="closeLoaModal()" maxWidth="max-w-lg">
        <x-slot:top>
            <span x-text="'{{ addslashes(lexicon('label_AdminDashboard_ManageLoaModal', 'Manage Player Leave of Absence')) }}: ' + modals.loa.userName"></span>
        </x-slot:top>

        <x-meta type="modal" xAction="modals.loa.action" xMethod="modals.loa.method">
            <x-metadata type="select"
                        name="is_on_loa"
                        id="hub-loa-status-select"
                        xModel="modals.loa.is_on_loa"
                        :label="lexicon('label_Dashboard_DutyAvailability', 'Duty Availability')"
                        :tooltip="lexicon('tooltip_Users_SelectLoaStatus', 'Set player duty or leave status')">
                <option value="0">{{ lexicon('label_ServiceRecords_StatusActive', 'Active Duty') }}</option>
                <option value="1">{{ lexicon('label_Dashboard_OnLeave', 'On Leave (LOA / ELOA)') }}</option>
            </x-metadata>

            <div x-show="String(modals.loa.is_on_loa) === '1'" x-cloak class="flex flex-col gap-md">
                <x-metadata type="chronological"
                            name="loa_return_date"
                            id="hub-loa-return-date"
                            xModel="modals.loa.loa_return_date"
                            :label="lexicon('label_Dashboard_ExpectedReturnDate', 'Expected Return Date')"
                            :tooltip="lexicon('tooltip_Users_LoaReturnDate', 'Expected return date from leave (leave > 30 days automatically logs as ELOA)')" />

                <x-metadata type="text"
                            name="reason"
                            id="hub-loa-reason"
                            xModel="modals.loa.reason"
                            :label="lexicon('label_ServiceRecords_ReasonJustification', 'Reason / Note (Optional)')"
                            :placeholder="lexicon('label_Dashboard_LoaReasonPlaceholder', 'e.g. Holiday, exams...')"
                            :tooltip="lexicon('tooltip_Dashboard_LoaReason', 'Optional note logged in the chronological service register')" />
            </div>

            <x-slot:bottom>
                <x-button type="button"
                          variant="secondary"
                          :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                          @click="closeLoaModal()">
                    {{ lexicon('btn_common_Cancel', 'Cancel') }}
                </x-button>

                <x-button type="submit"
                          variant="primary"
                          icon="bi-save"
                          :tooltip="lexicon('tooltip_common_Save', 'Save LOA status changes')">
                    {{ lexicon('btn_common_Save', 'Save Changes') }}
                </x-button>
            </x-slot:footer>
        </x-meta>
    </x-modal>
</div>
@endsection