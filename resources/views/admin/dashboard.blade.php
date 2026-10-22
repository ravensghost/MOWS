@extends('layouts.app')

@section('title', lexicon('admin_label_AdminDashboard', 'Command Centre'))

@section('content')
<div x-data="loaHubManager()" @ajax-success.window="loaModal.open = false; window.refreshDOM_US()">
    <x-admin-grid-wrapper :page="$systemPage">

        <x-slot:top>
            <x-page-header :title="lexicon('admin_label_AdminDashboard', 'Command Centre')">
                <x-slot:actions>
                    <x-badge type="primary"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_AdminDashboard_RoleContext', 'Your active system role and control panel scope')) }}'"
                        aria-label="{{ lexicon('tooltip_AdminDashboard_RoleContext', 'Your active system role and control panel scope') }}">
                        <i class="bi bi-shield-lock mr-1" aria-hidden="true"></i>
                        <span>{{ ucfirst(auth()->user()->role ?? lexicon('label_common_AdminRole', 'Admin')) }} &mdash; {{ lexicon('label_AdminDashboard_MasterControlPanel', 'Master Control Panel') }}</span>
                    </x-badge>
                </x-slot:actions>
            </x-page-header>
        </x-slot:top>

        <x-slot:middle>
            {{-- Top Summary Metric Cards --}}
            <div class="grid grid-cols-1 md-grid-cols-3 lg-grid-cols-6 gap-md w-full mb-4">
                <div class="bg-panel border-panel rounded-panel shadow-panel p-4 flex flex-col gap-sm"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_AdminDashboard_PendingApps', 'Character applications awaiting review')) }}'"
                     aria-label="{{ lexicon('tooltip_AdminDashboard_PendingApps', 'Character applications awaiting review') }}">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold text-accent">{{ $pendingApps ?? 0 }}</span>
                        <i class="bi bi-file-earmark-person text-accent text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase tracking-wider">{{ lexicon('label_AdminDashboard_PendingApps', 'Pending Apps') }}</span>
                </div>

                <div class="bg-panel border-panel rounded-panel shadow-panel p-4 flex flex-col gap-sm"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_AdminDashboard_ActiveUsers', 'Total registered user accounts')) }}'"
                     aria-label="{{ lexicon('tooltip_AdminDashboard_ActiveUsers', 'Total registered user accounts') }}">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold text-success">{{ $activeUsers ?? 0 }}</span>
                        <i class="bi bi-people text-success text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase tracking-wider">{{ lexicon('label_AdminDashboard_ActiveUsers', 'Active Users') }}</span>
                </div>

                <div class="bg-panel border-panel rounded-panel shadow-panel p-4 flex flex-col gap-sm"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_AdminDashboard_Characters', 'Total active characters on the roster')) }}'"
                     aria-label="{{ lexicon('tooltip_AdminDashboard_Characters', 'Total active characters on the roster') }}">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold text-info">{{ $totalCharacters ?? 0 }}</span>
                        <i class="bi bi-person-badge text-info text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase tracking-wider">{{ lexicon('label_AdminDashboard_Characters', 'Characters') }}</span>
                </div>

                <div class="bg-panel border-panel rounded-panel shadow-panel p-4 flex flex-col gap-sm"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_AdminDashboard_OpenSlots', 'Available open position slots across all sections')) }}'"
                     aria-label="{{ lexicon('tooltip_AdminDashboard_OpenSlots', 'Available open position slots across all sections') }}">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold text-warning">{{ $openPositions ?? 0 }}</span>
                        <i class="bi bi-person-plus text-warning text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase tracking-wider">{{ lexicon('label_AdminDashboard_OpenSlots', 'Open Slots') }}</span>
                </div>

                <div class="bg-panel border-panel rounded-panel shadow-panel p-4 flex flex-col gap-sm"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_AdminDashboard_OnLoaCount', 'Total players currently on Leave of Absence or Extended Leave')) }}'"
                     aria-label="{{ lexicon('tooltip_AdminDashboard_OnLoaCount', 'Total players currently on Leave of Absence or Extended Leave') }}">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold text-warning">{{ $loaCount ?? 0 }}</span>
                        <i class="bi bi-calendar-range text-warning text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase tracking-wider">{{ lexicon('label_AdminDashboard_OnLoaCount', 'Players on LOA / ELOA') }}</span>
                </div>

                <div class="bg-panel border-panel rounded-panel shadow-panel p-4 flex flex-col gap-sm"
                     x-tooltip="'{{ addslashes(lexicon('tooltip_AdminDashboard_InactiveCount', 'Active players exceeding the login inactivity threshold')) }}'"
                     aria-label="{{ lexicon('tooltip_AdminDashboard_InactiveCount', 'Active players exceeding the login inactivity threshold') }}">
                    <div class="flex flex-row items-center justify-between">
                        <span class="text-2xl font-bold {{ ($flaggedInactiveCount ?? 0) > 0 ? 'text-danger' : 'text-success' }}">{{ $flaggedInactiveCount ?? 0 }}</span>
                        <i class="bi bi-exclamation-triangle {{ ($flaggedInactiveCount ?? 0) > 0 ? 'text-danger' : 'text-success' }} text-xl" aria-hidden="true"></i>
                    </div>
                    <span class="text-xs text-muted uppercase tracking-wider">{{ lexicon('label_AdminDashboard_InactiveCount', 'Flagged Inactive') }} (&gt;{{ $inactivityThresholdDays ?? 30 }}d)</span>
                </div>
            </div>
        </x-slot:middle>
        <x-slot:bottom>
            {{-- PANEL 1: ACTIVE LOA / ELOA ROSTER & COUNTDOWN HUB --}}
            <div class="bg-panel border-panel rounded-panel shadow-panel p-4 w-full mt-6">
                <div class="flex flex-row flex-wrap items-center justify-between gap-sm mb-4">
                    <h3 class="text-lg font-semibold text-accent flex items-center">
                        <i class="bi bi-calendar2-week mr-2" aria-hidden="true"></i>
                        <span>{{ lexicon('label_AdminDashboard_LoaHubTitle', 'Active LOA & ELOA Roster') }}</span>
                    </h3>
                    <x-badge variant="warning">
                        {{ $loaCount ?? 0 }} {{ lexicon('label_Dashboard_OnLeave', 'On Leave (LOA / ELOA)') }}
                    </x-badge>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-panel text-muted text-xs uppercase tracking-wider">
                                <th class="py-3 px-4">{{ lexicon('label_common_Name', 'Player') }}</th>
                                <th class="py-3 px-4">{{ lexicon('label_AdminDashboard_Characters', 'Characters') }}</th>
                                <th class="py-3 px-4 text-center">{{ lexicon('label_common_Status', 'Leave Type') }}</th>
                                <th class="py-3 px-4 text-center">{{ lexicon('label_Dashboard_ExpectedReturnDate', 'Expected Return Date') }}</th>
                                <th class="py-3 px-4 text-center">{{ lexicon('label_AdminDashboard_DaysRemaining', 'Countdown') }}</th>
                                <th class="py-3 px-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-panel text-sm">
                            @forelse($loaPlayers ?? [] as $loaUser)
                                <tr class="hover:bg-panel-hover transition-colors">
                                    <td class="py-3 px-4 font-bold text-p">{{ $loaUser->name }}</td>
                                    <td class="py-3 px-4 text-muted">{{ $loaUser->character_names }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <x-badge :variant="$loaUser->leave_type === 'ELOA' ? 'danger' : 'warning'">
                                            {{ $loaUser->leave_type }}
                                        </x-badge>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs">
                                        {{ $loaUser->return_display }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($loaUser->days_remaining === null)
                                            <x-badge variant="secondary">Indefinite</x-badge>
                                        @elseif($loaUser->is_expired)
                                            <x-badge variant="danger">
                                                <i class="bi bi-exclamation-octagon mr-1" aria-hidden="true"></i>
                                                Overdue by {{ abs($loaUser->days_remaining) }} {{ Str::plural('day', abs($loaUser->days_remaining)) }}
                                            </x-badge>
                                        @elseif($loaUser->days_remaining === 0)
                                            <x-badge variant="info">Due Back Today</x-badge>
                                        @else
                                            <x-badge variant="success">
                                                {{ $loaUser->days_remaining }} {{ Str::plural('day',$loaUser->days_remaining) }} left
                                            </x-badge>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex flex-row flex-wrap items-center justify-end gap-sm">
                                            <x-button type="button"
                                                      variant="text"
                                                      class="text-success hover-text-h text-xs flex flex-row items-center gap-xs"
                                                      @click="quickClearLoa({{ $loaUser->id }}, '{{ addslashes($loaUser->name) }}')"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_AdminDashboard_ClearLoa', 'Clear LOA status and return player characters to Active Duty')) }}'"
                                                      aria-label="{{ lexicon('tooltip_AdminDashboard_ClearLoa', 'Clear LOA status and return player characters to Active Duty') }}">
                                                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                                <span>{{ lexicon('btn_AdminDashboard_ClearLoa', 'Return to Duty') }}</span>
                                            </x-button>

                                            <x-button type="button"
                                                      variant="text"
                                                      class="text-warning hover-text-h text-xs flex flex-row items-center gap-xs"
                                                      @click="openLoaModal({{ $loaUser->id }}, '{{ addslashes($loaUser->name) }}', true, '{{$loaUser->return_date ?? '' }}')"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_AdminDashboard_ExtendLoa', 'Modify or extend expected return date')) }}'"
                                                      aria-label="{{ lexicon('tooltip_AdminDashboard_ExtendLoa', 'Modify or extend expected return date') }}">
                                                <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                                                <span>{{ lexicon('btn_AdminDashboard_ExtendLoa', 'Update / Extend') }}</span>
                                            </x-button>

                                            <x-button type="button"
                                                      variant="text"
                                                      class="text-accent hover-text-h text-xs flex flex-row items-center gap-xs"
                                                      @click="sendPlayerPing({{ $loaUser->id }}, '{{ addslashes($loaUser->name) }}', 'loa')"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_AdminDashboard_PingPlayer', 'Send an automated activity or LOA check-in alert via enabled channels')) }}'"
                                                      aria-label="{{ lexicon('tooltip_AdminDashboard_PingPlayer', 'Send an automated activity or LOA check-in alert via enabled channels') }}">
                                                <i class="bi bi-bell" aria-hidden="true"></i>
                                                <span>{{ lexicon('btn_AdminDashboard_PingPlayer', 'Ping Player') }}</span>
                                            </x-button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-muted italic">
                                        {{ lexicon('msg_AdminDashboard_NoActiveLoa', 'No players are currently on Leave of Absence.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PANEL 2: PLAYER ACTIVITY & INACTIVITY AUDIT --}}
            <div class="bg-panel border-panel rounded-panel shadow-panel p-4 w-full mt-6">
                <div class="flex flex-row flex-wrap items-center justify-between gap-sm mb-4">
                    <h3 class="text-lg font-semibold text-accent flex items-center">
                        <i class="bi bi-activity mr-2" aria-hidden="true"></i>
                        <span>{{ lexicon('label_AdminDashboard_InactivityTitle', 'Player Activity & Inactivity Audit') }}</span>
                    </h3>
                    <span class="text-xs text-muted">
                        Inactivity Threshold: <strong>{{ $inactivityThresholdDays ?? 30 }} days</strong>
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-panel text-muted text-xs uppercase tracking-wider">
                                <th class="py-3 px-4">{{ lexicon('label_common_Name', 'Player') }}</th>
                                <th class="py-3 px-4">{{ lexicon('label_AdminDashboard_Characters', 'Characters') }}</th>
                                <th class="py-3 px-4 text-center">{{ lexicon('label_common_Status', 'Duty Status') }}</th>
                                <th class="py-3 px-4 text-center">{{ lexicon('label_AdminDashboard_LastLogin', 'Last Login') }}</th>
                                <th class="py-3 px-4 text-center">{{ lexicon('label_AdminDashboard_LastPost', 'Last Post') }}</th>
                                <th class="py-3 px-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-panel text-sm">
                            @forelse($playerActivity ?? [] as $player)
                                <tr class="hover:bg-panel-hover transition-colors">
                                    <td class="py-3 px-4 font-bold text-p">
                                        {{ $player->name }}
                                        <span class="text-xs text-muted font-normal ml-1">({{ ucfirst($player->role) }})</span>
                                    </td>
                                    <td class="py-3 px-4 text-muted">{{ $player->character_names }}</td>
                                    <td class="py-3 px-4 text-center">
                                        @if($player->is_inactive)
                                            <x-badge variant="danger">
                                                <i class="bi bi-exclamation-triangle mr-1" aria-hidden="true"></i>
                                                Inactive ({{ $player->days_since_login }}d)
                                            </x-badge>
                                        @elseif($player->is_on_loa)
                                            <x-badge :variant="$player->leave_type === 'ELOA' ? 'danger' : 'warning'">
                                                {{ $player->leave_type }}
                                            </x-badge>
                                        @else
                                            <x-badge variant="success">Active</x-badge>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="{{ $player->is_inactive ? 'text-danger font-bold' : 'text-p' }}">
                                            {{ $player->last_login_human }}
                                        </span>
                                        <span class="block text-xs text-muted">{{ $player->last_login_date }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center text-muted text-xs">
                                        {{ $player->last_post_human }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex flex-row flex-wrap items-center justify-end gap-sm">
                                            <x-button type="button"
                                                      variant="text"
                                                      class="text-warning hover-text-h text-xs flex flex-row items-center gap-xs"
                                                      @click="openLoaModal({{ $player->id }}, '{{ addslashes($player->name) }}', {{ $player->is_on_loa ? 'true' : 'false' }}, '{{ $player->return_date }}')"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_Users_SelectLoaStatus', 'Set duty or leave status')) }}'"
                                                      aria-label="{{ lexicon('tooltip_Users_SelectLoaStatus', 'Set duty or leave status') }}">
                                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                                <span>{{ lexicon('label_Users_StatusLoa', 'LOA') }}</span>
                                            </x-button>

                                            <x-button type="button"
                                                      variant="text"
                                                      class="text-accent hover-text-h text-xs flex flex-row items-center gap-xs"
                                                      @click="sendPlayerPing({{ $player->id }}, '{{ addslashes($player->name) }}', 'inactivity')"
                                                      x-tooltip="'{{ addslashes(lexicon('tooltip_AdminDashboard_PingPlayer', 'Send an automated activity or LOA check-in alert via enabled channels')) }}'"
                                                      aria-label="{{ lexicon('tooltip_AdminDashboard_PingPlayer', 'Send an automated activity or LOA check-in alert via enabled channels') }}">
                                                <i class="bi bi-bell" aria-hidden="true"></i>
                                                <span>{{ lexicon('btn_AdminDashboard_PingPlayer', 'Ping Player') }}</span>
                                            </x-button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-muted italic">
                                        No registered players found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- PANEL 3: MONTHLY WRITER METRICS SCOREBOARD --}}
            <div class="bg-panel border-panel rounded-panel shadow-panel p-4 w-full mt-6">
                <div class="flex flex-row items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-accent flex items-center">
                        <i class="bi bi-bar-chart-fill mr-2" aria-hidden="true"></i>
                        <span>Monthly Writing Activity ({{ now()->format('F Y') }})</span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-panel text-muted text-xs uppercase tracking-wider">
                                <th class="py-3 px-4">User Name</th>
                                <th class="py-3 px-4">Character Name</th>
                                <th class="py-3 px-4 text-center">Posts This Month</th>
                                <th class="py-3 px-4 text-right">Words This Month</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-panel text-sm">
                            @forelse($currentMonthMetrics ?? [] as $metric)
                                <tr class="hover:bg-panel-hover transition-colors">
                                    <td class="py-3 px-4 font-medium">{{ $metric->user_name }}</td>
                                    <td class="py-3 px-4 text-muted">{{ $metric->character_name ?? 'Unassigned / OOC' }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-1 bg-accent-muted text-accent rounded text-xs font-bold">
                                            {{ $metric->post_count }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-semibold text-success">
                                        {{ number_format($metric->total_words) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-6 text-center text-muted italic">
                                        No writing activity recorded for this month yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- MODAL: QUICK-ACTION LOA / ELOA UPDATE & EXTENSION MODAL --}}
            <x-modal show="loaModal.open" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="`{{ addslashes(lexicon('label_AdminDashboard_ManageLoaModal', 'Manage Player Leave of Absence')) }}: ${loaModal.userName}`"></span>
                </x-slot:top>

                <form :action="`/admin/users/${loaModal.userId}/loa`"
                      method="POST"
                      class="flex flex-col gap-md"
                      x-data="ajaxForm"
                      @submit.prevent="submit($event)">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">

                    <div class="flex flex-col gap-xs">
                        <label for="hub-loa-status-select" class="text-xs font-bold text-input-muted">
                            {{ lexicon('label_Dashboard_DutyAvailability', 'Duty Availability') }}
                        </label>
                        <select id="hub-loa-status-select"
                                name="is_on_loa"
                                x-model="loaModal.is_on_loa"
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                            <option value="0">{{ lexicon('label_ServiceRecords_StatusActive', 'Active') }}</option>
                            <option value="1">{{ lexicon('label_Dashboard_OnLeave', 'On Leave (LOA / ELOA)') }}</option>
                        </select>
                    </div>

                    <div x-show="String(loaModal.is_on_loa) === '1'" x-cloak class="flex flex-col gap-md">
                        <x-form-input name="loa_return_date"
                                      id="hub-loa-return-date"
                                      type="date"
                                      :label="lexicon('label_Dashboard_ExpectedReturnDate', 'Expected Return Date')"
                                      :tooltip="lexicon('tooltip_Users_LoaReturnDate', 'Expected return date from leave')"
                                      xModel="loaModal.loa_return_date" />

                        <x-form-input name="reason"
                                      id="hub-loa-reason"
                                      :label="lexicon('label_ServiceRecords_ReasonJustification', 'Reason / Note (Optional)')"
                                      :placeholder="lexicon('label_Dashboard_LoaReasonPlaceholder', 'e.g. Holiday, exams...')"
                                      :tooltip="lexicon('tooltip_Dashboard_LoaReason', 'Optional note logged in your service record')"
                                      xModel="loaModal.reason" />
                    </div>

                    <div class="border-t-actions pt-6 mt-4 flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="button"
                                  variant="secondary"
                                  @click="loaModal.open = false">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                        <x-button type="submit"
                                  variant="primary"
                                  ::disabled="submitting"
                                  x-text="submitting ? '{{ addslashes(lexicon('msg_common_Saving', 'Saving...')) }}' : '{{ addslashes(lexicon('btn_common_Save', 'Save Changes')) }}'">
                            {{ lexicon('btn_common_Save', 'Save Changes') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>

        </x-slot:bottom>

    </x-admin-grid-wrapper>
</div>
@endsection