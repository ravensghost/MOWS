@extends('layouts.app')

@section('title', lexicon('admin_label_AdminDashboard', 'Command Centre'))

@section('content')
<div x-data @ajax-success.window="window.refreshDOM_US()">
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

        <x-slot:bottom>
            <div class="grid grid-cols-1 md-grid-cols-2 lg-grid-cols-4 gap-md w-full mb-4">
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
            </div>

            <!-- Monthly Writer Metrics Scoreboard -->
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
        </x-slot:bottom>

    </x-admin-grid-wrapper>
</div>
@endsection