<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use App\Models\Term;
use App\Models\FormField;
use App\Models\Section;
use App\Models\NavigationLink;
use App\Models\Skin;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 0. Master Admin Account
        \App\Models\User::updateOrCreate(
            ['email' => 'admin@mows.engine'],
            [
                'name' => 'System Administrator',
                'password' => \Illuminate\Support\Facades\Hash::make('admin1234'),
                'role' => 'webmaster',
            ]
        );

        // 1. Core Architecture Settings
        $settings = [
            ['key' => 'site_title', 'value' => 'MOWS Engine Alpha'],
            ['key' => 'welcome_title', 'value' => 'Welcome to the Station'],
            ['key' => 'welcome_message', 'value' => 'Join our interactive writing community.'],
            ['key' => 'character_limit_enforced', 'value' => 'true'],
            ['key' => 'rejection_protocol', 'value' => 'soft_lock'],
            ['key' => 'content_shield_bypass', 'value' => 'locked'],
            ['key' => 'theme_governance', 'value' => 'user_choice'],
            ['key' => 'theme_base_color', 'value' => '#111827'],
            ['key' => 'theme_accent_color', 'value' => '#3b82f6'],
            ['key' => 'theme_badge_pc', 'value' => 'none'],
            ['key' => 'theme_badge_sc', 'value' => '2px solid white'],
            ['key' => 'theme_badge_npc', 'value' => '2px solid red'],
            ['key' => 'theme_font_family', 'value' => 'sans-serif'],
            ['key' => 'theme_h_color', 'value' => '#ffffff'],
            ['key' => 'theme_p_color', 'value' => '#9ca3af'],
            ['key' => 'theme_logo_path', 'value' => ''],
            ['key' => 'theme_banner_path', 'value' => ''],
            ['key' => 'theme_border_radius', 'value' => '0.5rem'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], ['value' => $setting['value']]);
        }

        // 2. Lexicon Defaults
        $terms = [
            ['code_key' => 'index', 'translations' => 'Landing'],
            ['code_key' => 'site_name', 'translations' => 'MOWS Engine'],
            ['code_key' => 'recent_posts', 'translations' => 'Recent Transmissions'],
            ['code_key' => 'news', 'translations' => 'System News'],
            ['code_key' => 'page', 'translations' => 'Post'],
            ['code_key' => 'character_list', 'translations' => 'Manifest'],
        ];

        foreach ($terms as $term) {
            Term::updateOrCreate(
                ['code_key' => $term['code_key']],
                [
                    'display_value' => $term['display_value'] ?? $term['translations'],
                    'translations' => $term['translations']
                ]
            );
        }

        // 3. Application Form Defaults
        $fields = [
            ['name' => 'Character Name', 'type' => 'text', 'options' => null, 'is_required' => true, 'order' => 1],
            ['name' => 'Species', 'type' => 'dropdown', 'options' => json_encode(['Human', 'Vulcan', 'Romulan', 'Klingon']), 'is_required' => true, 'order' => 2],
            ['name' => 'Physical Description', 'type' => 'textarea', 'options' => null, 'is_required' => false, 'order' => 3],
        ];

        foreach ($fields as $field) {
            FormField::updateOrCreate(['name' => $field['name']], $field);
        }

        // 4. Default Department Sections
        Section::updateOrCreate(
            ['name' => 'Command'],
            ['description' => 'Station administration and strategic operations.', 'color' => '#dc2626', 'order' => 1]
        );
        
        // 5. Navigation Defaults
        NavigationLink::insert([
            // Primary Navigation (Publicly Visible)
            [
                'lexicon_key' => 'page_landing',
                'url'         => '/',
                'location'    => 'primary',
                'visibility'  => 'public',
                'target'      => '_self',
                'order'       => 1,
            ],
            [
                'lexicon_key' => 'page_roster',
                'url'         => '/roster',
                'location'    => 'primary',
                'visibility'  => 'public',
                'target'      => '_self',
                'order'       => 2,
            ],
            [
                'lexicon_key' => 'page_simm',
                'url'         => '/simm',
                'location'    => 'primary',
                'visibility'  => 'public',
                'target'      => '_self',
                'order'       => 3,
            ],

            // Context Drawer (State-Dependent Visibility)
            [
                'lexicon_key' => 'btn_login',
                'url'         => '/login',
                'location'    => 'drawer',
                'visibility'  => 'guest', // Only shows when logged out
                'target'      => '_self',
                'order'       => 1,
            ],
            [
                'lexicon_key' => 'page_dashboard',
                'url'         => '/dashboard',
                'location'    => 'drawer',
                'visibility'  => 'auth', // Only shows when logged in
                'target'      => '_self',
                'order'       => 2,
            ],
            [
                'lexicon_key' => 'admin_dashboard',
                'url'         => '/admin',
                'location'    => 'drawer',
                'visibility'  => 'admin', // Only shows to administrators
                'target'      => '_self',
                'order'       => 3,
            ],
        ]);

        // 6. Default Skin Package
        Skin::updateOrCreate(
            ['folder_name' => 'default'],
            [
                'name' => 'Default',
                'version' => '1.0',
                'is_active' => 1,
                'is_global_default' => 1,
            ]
        );
   }
}