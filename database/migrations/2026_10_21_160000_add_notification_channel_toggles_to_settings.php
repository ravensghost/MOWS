<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // 1. Insert the 3 channel kill-switches defaulted to '0' (Disabled until launch)
        $settings = [
            ['key' => 'notifications_discord_enabled', 'value' => '0'],
            ['key' => 'notifications_email_enabled',   'value' => '0'],
            ['key' => 'notifications_pm_enabled',      'value' => '0'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'created_at' => $now, 'updated_at' => $now]
            );
        }

        // 2. Insert British English Lexicon terms for the toggles
        $terms = [
            ['code_key' => 'label_setting_EnableDiscord',   'master_group' => 'label',   'subgroup' => 'setting', 'display_value' => 'Enable Discord Notifications'],
            ['code_key' => 'tooltip_setting_EnableDiscord', 'master_group' => 'tooltip', 'subgroup' => 'setting', 'display_value' => 'Master switch to enable or disable outgoing Discord webhook dispatches'],
            ['code_key' => 'label_setting_EnableEmail',     'master_group' => 'label',   'subgroup' => 'setting', 'display_value' => 'Enable Email Notifications'],
            ['code_key' => 'tooltip_setting_EnableEmail',   'master_group' => 'tooltip', 'subgroup' => 'setting', 'display_value' => 'Master switch to enable or disable outgoing SMTP email alerts'],
            ['code_key' => 'label_setting_EnablePm',        'master_group' => 'label',   'subgroup' => 'setting', 'display_value' => 'Enable Automated On-Site PM Alerts'],
            ['code_key' => 'tooltip_setting_EnablePm',      'master_group' => 'tooltip', 'subgroup' => 'setting', 'display_value' => 'Master switch to enable or disable automated system Private Message alerts'],
            ['code_key' => 'label_common_Enabled',          'master_group' => 'label',   'subgroup' => 'common',  'display_value' => 'Enabled'],
            ['code_key' => 'label_common_Disabled',         'master_group' => 'label',   'subgroup' => 'common',  'display_value' => 'Disabled'],
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
        DB::table('settings')->whereIn('key', [
            'notifications_discord_enabled',
            'notifications_email_enabled',
            'notifications_pm_enabled',
        ])->delete();
    }
};
