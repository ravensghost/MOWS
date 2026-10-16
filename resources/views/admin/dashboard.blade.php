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
        </x-slot:bottom>

    </x-admin-grid-wrapper>
</div>
@endsection