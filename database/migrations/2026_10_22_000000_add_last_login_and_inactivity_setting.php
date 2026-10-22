<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Seed default 30-day inactivity threshold setting
        DB::table('settings')->updateOrInsert(
            ['key' => 'inactivity_threshold_days'],
            [
                'value'      => '30',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Seed Lexicon terms for Player Status & LOA Management Hub
        $now = now();
        $terms = [
            ['code_key' => 'label_setting_InactivityThreshold', 'master_group' => 'label', 'subgroup' => 'setting', 'display_value' => 'Player Inactivity Threshold (Days)'],
            ['code_key' => 'tooltip_setting_InactivityThreshold', 'master_group' => 'tooltip', 'subgroup' => 'setting', 'display_value' => 'Number of days without a login before an active player is flagged as inactive (default: 30)'],
            ['code_key' => 'msg_setting_InactivityThresholdHint', 'master_group' => 'msg', 'subgroup' => 'setting', 'display_value' => 'Number of days before flagging inactive accounts (e.g. 30)'],
            ['code_key' => 'label_AdminDashboard_LoaHubTitle', 'master_group' => 'label', 'subgroup' => 'AdminDashboard', 'display_value' => 'Active LOA & ELOA Roster'],
            ['code_key' => 'label_AdminDashboard_InactivityTitle', 'master_group' => 'label', 'subgroup' => 'AdminDashboard', 'display_value' => 'Player Activity & Inactivity Audit'],
            ['code_key' => 'label_AdminDashboard_OnLoaCount', 'master_group' => 'label', 'subgroup' => 'AdminDashboard', 'display_value' => 'Players on LOA / ELOA'],
            ['code_key' => 'tooltip_AdminDashboard_OnLoaCount', 'master_group' => 'tooltip', 'subgroup' => 'AdminDashboard', 'display_value' => 'Total players currently on Leave of Absence or Extended Leave'],
            ['code_key' => 'label_AdminDashboard_InactiveCount', 'master_group' => 'label', 'subgroup' => 'AdminDashboard', 'display_value' => 'Flagged Inactive'],
            ['code_key' => 'tooltip_AdminDashboard_InactiveCount', 'master_group' => 'tooltip', 'subgroup' => 'AdminDashboard', 'display_value' => 'Active players exceeding the login inactivity threshold'],
            ['code_key' => 'label_AdminDashboard_DaysRemaining', 'master_group' => 'label', 'subgroup' => 'AdminDashboard', 'display_value' => 'Countdown'],
            ['code_key' => 'label_AdminDashboard_LastLogin', 'master_group' => 'label', 'subgroup' => 'AdminDashboard', 'display_value' => 'Last Login'],
            ['code_key' => 'label_AdminDashboard_LastPost', 'master_group' => 'label', 'subgroup' => 'AdminDashboard', 'display_value' => 'Last Post'],
            ['code_key' => 'btn_AdminDashboard_ClearLoa', 'master_group' => 'btn', 'subgroup' => 'AdminDashboard', 'display_value' => 'Return to Duty'],
            ['code_key' => 'tooltip_AdminDashboard_ClearLoa', 'master_group' => 'tooltip', 'subgroup' => 'AdminDashboard', 'display_value' => 'Clear LOA status and return player characters to Active Duty'],
            ['code_key' => 'btn_AdminDashboard_ExtendLoa', 'master_group' => 'btn', 'subgroup' => 'AdminDashboard', 'display_value' => 'Update / Extend'],
            ['code_key' => 'tooltip_AdminDashboard_ExtendLoa', 'master_group' => 'tooltip', 'subgroup' => 'AdminDashboard', 'display_value' => 'Modify or extend expected return date'],
            ['code_key' => 'btn_AdminDashboard_PingPlayer', 'master_group' => 'btn', 'subgroup' => 'AdminDashboard', 'display_value' => 'Ping Player'],
            ['code_key' => 'tooltip_AdminDashboard_PingPlayer', 'master_group' => 'tooltip', 'subgroup' => 'AdminDashboard', 'display_value' => 'Send an automated activity or LOA check-in alert via enabled channels'],
            ['code_key' => 'msg_AdminDashboard_NoActiveLoa', 'master_group' => 'msg', 'subgroup' => 'AdminDashboard', 'display_value' => 'No players are currently on Leave of Absence.'],
            ['code_key' => 'label_AdminDashboard_ManageLoaModal', 'master_group' => 'label', 'subgroup' => 'AdminDashboard', 'display_value' => 'Manage Player Leave of Absence'],
        ];

        foreach ($terms as $term) {
            DB::table('terms')->updateOrInsert(
                ['code_key' => $term['code_key']],
                [
                    'master_group'  => $term['master_group'],
                    'subgroup'      => $term['subgroup'],
                    'display_value' => $term['display_value'],
                    'translations'  => json_encode(['gb' => $term['display_value']]),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'inactivity_threshold_days')->delete();
    }
};
