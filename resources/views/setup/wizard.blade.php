@extends('setup.layout', ['selectedTheme' => $selectedTheme ?? 'default'])

@section('title', lexicon('title_Setup_Wizard', 'MOWS Engine — First-Run Setup Wizard'))

@section('content')
<div x-data="setupWizardManager(@js($availableSkins ?? []), @js($selectedTheme ?? 'default'))"
     x-init="initData()"
     class="flex flex-col gap-lg">

    <x-page-header :title="lexicon('title_Setup_Wizard', 'MOWS Engine Setup Wizard')"
                   :subtitle="lexicon('desc_Setup_Wizard', 'Configure environment requirements, database connection, franchise binding, and your webmaster account.')"
                   icon="bi-sliders" />

    <x-tab type="panel"
           :title="lexicon('label_Setup_Progress', 'Installation Progress')"
           icon="bi-check2-circle">
        <div class="flex flex-row flex-wrap items-center gap-sm">
            <x-badge type="pill" :variant="step === 1 ? 'primary' : (step > 1 ? 'success' : 'muted')">
                1. {{ lexicon('label_Setup_StepEnv', 'Environment') }}
            </x-badge>
            <x-badge type="pill" :variant="step === 2 ? 'primary' : (step > 2 ? 'success' : 'muted')">
                2. {{ lexicon('label_Setup_StepDb', 'Database & .env') }}
            </x-badge>
            <x-badge type="pill" :variant="step === 3 ? 'primary' : 'muted'">
                3. {{ lexicon('label_Setup_StepAdmin', 'Skin & Webmaster') }}
            </x-badge>
        </div>
    </x-tab>

    {{-- Step 1: Environment Readiness --}}
    <div x-show="step === 1" class="flex flex-col gap-md">
        <x-tab type="panel"
               :title="lexicon('label_Setup_Step1Title', 'Step 1: Server & PHP Readiness')"
               icon="bi-cpu">
            <x-table type="admin"
                     :headers="[
                         lexicon('col_Setup_Check', 'Requirement'),
                         lexicon('col_common_Status', 'Status')
                     ]"
                     :emptyText="lexicon('msg_Setup_NoChecks', 'No checks available.')">
                @foreach(($requirements ?? []) as $checkKey => $passed)
                    <tr class="border-b-table-row">
                        <td class="p-3 font-bold text-h">{{ str_replace('_', ' ', ucfirst($checkKey)) }}</td>
                        <td class="p-3">
                            <x-badge type="pill" :variant="$passed ? 'success' : 'danger'">
                                {{ $passed ? lexicon('label_common_Ready', 'Ready') : lexicon('label_common_Missing', 'Missing / Unwritable') }}
                            </x-badge>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="flex flex-row justify-end gap-sm pt-4">
                <x-button type="button"
                          variant="primary"
                          icon="bi-arrow-right"
                          :disabled="!allRequirementsPassed"
                          @click="nextStep(2)">
                    {{ lexicon('btn_Setup_Continue', 'Continue to Database Setup') }}
                </x-button>
            </div>
        </x-tab>
    </div>

    {{-- Step 2: Database & .env Configuration --}}
    <div x-show="step === 2" x-cloak class="flex flex-col gap-md">
        <x-tab type="panel"
               :title="lexicon('label_Setup_Step2Title', 'Step 2: Database & Application Configuration')"
               icon="bi-database">
            <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                <x-metadata type="text"
                            name="app_name"
                            xModel="form.app_name"
                            :label="lexicon('label_Setup_AppName', 'Site / Unit Name')"
                            :required="true" />

                <x-metadata type="text"
                            name="app_url"
                            xModel="form.app_url"
                            :label="lexicon('label_Setup_AppUrl', 'Application URL')"
                            :required="true" />

                <x-metadata type="text"
                            name="db_host"
                            xModel="form.db_host"
                            :label="lexicon('label_Setup_DbHost', 'Database Host')"
                            :required="true" />

                <x-metadata type="text"
                            name="db_port"
                            xModel="form.db_port"
                            :label="lexicon('label_Setup_DbPort', 'Database Port')"
                            :required="true" />

                <x-metadata type="text"
                            name="db_database"
                            xModel="form.db_database"
                            :label="lexicon('label_Setup_DbName', 'Database Name')"
                            :required="true" />

                <x-metadata type="text"
                            name="db_username"
                            xModel="form.db_username"
                            :label="lexicon('label_Setup_DbUser', 'Database Username')"
                            :required="true" />

                <x-metadata type="text"
                            name="db_password"
                            xModel="form.db_password"
                            :label="lexicon('label_Setup_DbPassword', 'Database Password')" />
            </div>

            <div class="flex flex-row justify-between gap-sm pt-4">
                <x-button type="button"
                          variant="secondary"
                          icon="bi-arrow-left"
                          @click="nextStep(1)">
                    {{ lexicon('btn_common_Back', 'Back') }}
                </x-button>

                <x-button type="button"
                          variant="success"
                          icon="bi-database-check"
                          :disabled="isSubmitting"
                          @click="submitDatabase()">
                    <span x-show="!isSubmitting">{{ lexicon('btn_Setup_RunMigrations', 'Test Connection & Migrate') }}</span>
                    <span x-show="isSubmitting">{{ lexicon('msg_Setup_Migrating', 'Migrating & Seeding...') }}</span>
                </x-button>
            </div>
        </x-tab>
    </div>

    {{-- Step 3: Skin Binding & Webmaster Account --}}
    <div x-show="step === 3" x-cloak class="flex flex-col gap-md">
        <x-tab type="panel"
               :title="lexicon('label_Setup_Step3Title', 'Step 3: Default Skin & Webmaster Account')"
               icon="bi-shield-lock">
            <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                <x-metadata type="select"
                            name="skin_directory"
                            xModel="form.skin_directory"
                            :label="lexicon('label_Setup_Skin', 'Default Skin / Theme')">
                    @foreach(($availableSkins ?? ['default']) as $skinFolder)
                        <option value="{{ $skinFolder }}">{{ $skinFolder }}</option>
                    @endforeach
                </x-metadata>

                <x-metadata type="text"
                            name="admin_name"
                            xModel="form.admin_name"
                            :label="lexicon('label_Setup_AdminName', 'Webmaster Display Name')"
                            :required="true" />

                <x-metadata type="text"
                            name="admin_email"
                            xModel="form.admin_email"
                            :label="lexicon('label_Setup_AdminEmail', 'Webmaster Email Address')"
                            :required="true" />

                <x-metadata type="text"
                            name="password"
                            xModel="form.password"
                            :label="lexicon('label_Setup_Password', 'Webmaster Password')"
                            :required="true" />

                <x-metadata type="text"
                            name="password_confirmation"
                            xModel="form.password_confirmation"
                            :label="lexicon('label_Setup_PasswordConfirm', 'Confirm Password')"
                            :required="true" />
            </div>

            <div class="flex flex-row justify-between gap-sm pt-4">
                <x-button type="button"
                          variant="secondary"
                          icon="bi-arrow-left"
                          @click="nextStep(2)">
                    {{ lexicon('btn_common_Back', 'Back') }}
                </x-button>

                <x-button type="button"
                          variant="primary"
                          icon="bi-check-lg"
                          :disabled="isSubmitting"
                          @click="completeSetup()">
                    <span x-show="!isSubmitting">{{ lexicon('btn_Setup_Complete', 'Complete Installation') }}</span>
                    <span x-show="isSubmitting">{{ lexicon('msg_Setup_Finalising', 'Finalising Installation...') }}</span>
                </x-button>
            </div>
        </x-tab>
    </div>
</div>
@endsection