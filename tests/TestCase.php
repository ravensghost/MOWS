<?php

namespace Tests;

use App\Models\FormField;
use App\Models\Layout;
use App\Models\LayoutBlock;
use App\Models\Page;
use App\Models\Position;
use App\Models\RankSet;
use App\Models\Ranks;
use App\Models\Section;
use App\Models\Skin;
use App\Models\Widget;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /**
     * This method runs automatically before every single test.
     */
    protected function setUp(): void
    {
        // Always call parent::setUp() first so Laravel boots the database and app
        parent::setUp();

        $this->seedMowsTestEnvironment();
    }

    /**
     * Ensure core MOWS database records (skins, layouts, pages, permissions) exist.
     */
    protected function seedMowsTestEnvironment(): void
    {
        // 1. Clear Spatie's cached roles and permissions so tests don't use stale data
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // 2. Ensure the default Skin exists so views/layouts don't fail
        Skin::firstOrCreate(
            ['folder_name' => 'default'],
            [
                'name' => 'Default',
                'version' => '1.0',
                'is_active' => 1,
                'is_global_default' => 1,
            ]
        );

        // 3. Ensure core Permissions exist
        $permissions = [
            'access-admin',
            'applications_view',
            'applications_vote',
            'applications_approve',
            'manage_roster_assignments',
            'issue_service_records',
            'issue_awards',
            'manage_manifest_structure',
            'publish_news',
            'manage_story_arcs',
            'edit_chapter',
            'content_shield',
            'manage_pages',
            'manage_forms',
            'manage_lexicon',
            'manage_themes',
            'manage_navigation',
            'manage_settings',
            'manage_ranks',
            'manage_sections',
            'manage_roles',
            'manage_awards',
            'manage_service_records',
            'manage_users',
            'manage_applications',
            'manage_news',
            'manage_story',
            'edit_book',
            'edit_bookcase',
            'publish_chapter',
            'publish_book',
            'publish_bookcase',
            'publish_award',
            'publish_service_records',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        // 4. Ensure core Roles exist and give webmaster/senior user full permissions
        $webmasterRole = Role::firstOrCreate(['name' => 'webmaster', 'guard_name' => 'web']);
        $seniorUserRole = Role::firstOrCreate(['name' => 'senior user', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'web']);

        $webmasterRole->syncPermissions($permissions);
        $seniorUserRole->syncPermissions($permissions);
        $userRole->syncPermissions(['edit_chapter', 'content_shield', 'publish_chapter']);

        // 5. Ensure core Grid Layouts & Blocks exist
        $layouts = [
            'Home Page' => [
                ['name' => 'Site Welcome', 'col_span' => 12, 'row_span' => 1, 'sort_order' => 0],
                ['name' => 'News Block', 'col_span' => 6, 'row_span' => 1, 'sort_order' => 1],
                ['name' => 'Post Block', 'col_span' => 6, 'row_span' => 1, 'sort_order' => 2],
            ],
            'Full Width' => [
                ['name' => 'Content', 'col_span' => 12, 'row_span' => 12, 'sort_order' => 0],
            ],
            'Side-by-Side' => [
                ['name' => 'Content', 'col_span' => 9, 'row_span' => 12, 'sort_order' => 0],
                ['name' => 'SideBar', 'col_span' => 3, 'row_span' => 12, 'sort_order' => 1],
            ],
            'Dashboard' => [
                ['name' => 'WelcomePanel', 'col_span' => 12, 'row_span' => 2, 'sort_order' => 0],
                ['name' => 'UnreadMessages', 'col_span' => 6, 'row_span' => 6, 'sort_order' => 1],
                ['name' => 'DraftChapters', 'col_span' => 6, 'row_span' => 6, 'sort_order' => 2],
                ['name' => 'ProfileInformation', 'col_span' => 5, 'row_span' => 6, 'sort_order' => 3],
                ['name' => 'Content', 'col_span' => 7, 'row_span' => 6, 'sort_order' => 4],
                ['name' => 'UpdatePassword', 'col_span' => 5, 'row_span' => 6, 'sort_order' => 5],
                ['name' => 'Bottom', 'col_span' => 7, 'row_span' => 6, 'sort_order' => 6],
                ['name' => 'DeleteAccount', 'col_span' => 5, 'row_span' => 6, 'sort_order' => 7],
            ],
            '3 Block' => [
                ['name' => 'Top', 'col_span' => 12, 'row_span' => 4, 'sort_order' => 0],
                ['name' => 'Middle', 'col_span' => 12, 'row_span' => 4, 'sort_order' => 1],
                ['name' => 'Bottom', 'col_span' => 12, 'row_span' => 4, 'sort_order' => 2],
            ],
            '2 Block' => [
                ['name' => 'Top', 'col_span' => 12, 'row_span' => 1, 'sort_order' => 0],
                ['name' => 'Bottom', 'col_span' => 12, 'row_span' => 1, 'sort_order' => 1],
            ],
            'Reverse Sidebar' => [
                ['name' => 'SideBar', 'col_span' => 4, 'row_span' => 12, 'sort_order' => 0],
                ['name' => 'Content', 'col_span' => 8, 'row_span' => 12, 'sort_order' => 1],
            ],
        ];

        $layoutModels = [];
        foreach ($layouts as $layoutName => $blocks) {
            $layout = Layout::firstOrCreate(['name' => $layoutName]);
            $layoutModels[$layoutName] = $layout;

            foreach ($blocks as $block) {
                LayoutBlock::firstOrCreate(
                    [
                        'layout_id' => $layout->id,
                        'name' => $block['name'],
                    ],
                    [
                        'col_span' => $block['col_span'],
                        'row_span' => $block['row_span'],
                        'sort_order' => $block['sort_order'],
                    ]
                );
            }
        }

        // 6. Ensure core MOWS Pages exist and link to their Layouts
        $pages = [
            ['slug' => '/', 'title' => 'Landing', 'layout' => 'Home Page'],
            ['slug' => 'apply', 'title' => 'Join the Character List', 'layout' => 'Full Width'],
            ['slug' => 'simm', 'title' => 'Database', 'layout' => 'Side-by-Side'],
            ['slug' => 'sections-positions', 'title' => 'Sections & Roles', 'layout' => 'Full Width'],
            ['slug' => 'terms', 'title' => 'Terms & Conditions', 'layout' => 'Full Width'],
            ['slug' => 'credits', 'title' => 'Site Credits', 'layout' => 'Full Width'],
            ['slug' => 'roster', 'title' => 'Character List', 'layout' => 'Full Width'],
            ['slug' => 'privacy', 'title' => 'Privacy Policy', 'layout' => 'Full Width'],
            ['slug' => 'dashboard', 'title' => 'Dashboard', 'layout' => 'Dashboard'],
            ['slug' => 'chapters/create', 'title' => 'Write Chapter', 'layout' => '3 Block'],
            ['slug' => 'messenger', 'title' => 'PM', 'layout' => 'Reverse Sidebar'],
            ['slug' => 'bio', 'title' => 'Bio', 'layout' => 'Reverse Sidebar'],
            ['slug' => 'admin', 'title' => 'Command Center', 'layout' => '2 Block'],
            ['slug' => 'admin/themes', 'title' => 'Skinning Control', 'layout' => '3 Block'],
            ['slug' => 'admin/settings', 'title' => 'Global Settings', 'layout' => '3 Block'],
            ['slug' => 'admin/lexicon', 'title' => 'Lexicon Control', 'layout' => '3 Block'],
            ['slug' => 'admin/news', 'title' => 'News', 'layout' => '2 Block'],
            ['slug' => 'admin/pages', 'title' => 'Page Builder', 'layout' => '2 Block'],
            ['slug' => 'admin/forms', 'title' => 'Form', 'layout' => '2 Block'],
            ['slug' => 'admin/navigation', 'title' => 'Navigation', 'layout' => 'Full Width'],
            ['slug' => 'admin/sections', 'title' => 'Sections', 'layout' => 'Full Width'],
            ['slug' => 'admin/applications', 'title' => 'Application Review', 'layout' => 'Full Width'],
            ['slug' => 'admin/ranks', 'title' => 'Ranks', 'layout' => 'Full Width'],
            ['slug' => 'admin/users', 'title' => 'User', 'layout' => 'Full Width'],
            ['slug' => 'admin/story', 'title' => 'Story', 'layout' => '3 Block'],
            ['slug' => 'admin/roles', 'title' => 'Roles', 'layout' => '2 Block'],
            ['slug' => 'admin/navigation/options', 'title' => 'Navigation Options', 'layout' => 'Full Width'],
            ['slug' => 'admin/awards', 'title' => 'Awards', 'layout' => 'Full Width'],
            ['slug' => 'admin/page-builder', 'title' => 'Page Builder Edit', 'layout' => '2 Block'],
            ['slug' => 'admin/service-records', 'title' => 'Service Records', 'layout' => '2 Block'],
        ];

        $pageModels = [];
        foreach ($pages as $pageData) {
            $pageModels[$pageData['slug']] = Page::firstOrCreate(
                ['slug' => $pageData['slug']],
                [
                    'title' => $pageData['title'],
                    'content' => '',
                    'is_published' => 1,
                    'layout_id' => $layoutModels[$pageData['layout']]->id ?? null,
                ]
            );
        }

        // 7. Ensure core Rank Sets & Ranks exist
        $rankSetWriters = RankSet::firstOrCreate(['name' => 'writers']);
        $rankSetRoles = RankSet::firstOrCreate(['name' => 'roles']);

        Ranks::firstOrCreate(
            ['name' => 'Head Writer', 'rank_set_id' => $rankSetWriters->id],
            [
                'short_name' => 'HW',
                'mid_type' => 'css',
                'mid_css' => 'shape-mid-original',
                'top_type' => 'image',
                'top_image' => 'sgp.png',
                'top_css' => 'shape-top-blank',
                'sort_order' => 1,
            ]
        );

        Ranks::firstOrCreate(
            ['name' => 'Protagonist', 'rank_set_id' => $rankSetRoles->id],
            [
                'short_name' => 'Pri',
                'mid_type' => 'css',
                'mid_css' => 'shape-mid-original',
                'top_type' => 'image',
                'top_image' => 'sgp.png',
                'sort_order' => 0,
            ]
        );

        // 8. Ensure core Sections & Positions exist
        $sectionWriters = Section::firstOrCreate(
            ['name' => 'Writers'],
            [
                'description' => 'Station administration and strategic operations.',
                'color' => '#dc2626',
                'order' => 1,
                'rank_set_id' => $rankSetWriters->id,
            ]
        );

        $sectionRanks = Section::firstOrCreate(
            ['name' => 'Ranks'],
            [
                'description' => 'The Medical division is responsible for maintaining operational integrity...',
                'color' => '#1068f4',
                'order' => 1,
            ]
        );

        Position::firstOrCreate(
            ['name' => 'Lead Writer', 'section_id' => $sectionWriters->id],
            ['capacity' => 1, 'is_open' => 1]
        );

        Position::firstOrCreate(
            ['name' => 'Writer', 'section_id' => $sectionWriters->id],
            ['capacity' => 10, 'is_open' => 1]
        );

        Position::firstOrCreate(
            ['name' => 'Protagonist', 'section_id' => $sectionRanks->id],
            ['capacity' => 1, 'is_open' => 1]
        );

        // 9. Ensure core Widgets exist
        $widgets = [
            ['name' => 'Station News', 'widget_key' => 'news', 'default_options' => '[]'],
            ['name' => 'Story Chapters', 'widget_key' => 'posts', 'default_options' => '[]'],
            ['name' => 'Rich Text Document', 'widget_key' => 'document', 'default_options' => '{"title": "", "content": "<p>Start typing here...</p>"}'],
            ['name' => 'Character Roster', 'widget_key' => 'roster', 'default_options' => '{"show_vacant": "yes"}'],
            ['name' => 'Unread Messages', 'widget_key' => 'unread_messages', 'default_options' => '[]'],
            ['name' => 'Draft Chapters', 'widget_key' => 'user_drafts', 'default_options' => '[]'],
            ['name' => 'Custom Form', 'widget_key' => 'form', 'default_options' => '{"form_id": ""}'],
            ['name' => 'Awards Display', 'widget_key' => 'awards', 'default_options' => '{"title": "Service Awards", "mode": "auto"}'],
            ['name' => 'Service Log & Timeline', 'widget_key' => 'service_records', 'default_options' => '{"title": "Service Log", "mode": "auto", "display_target": "", "event_types": "", "limit": "10"}'],
        ];

        $widgetModels = [];
        foreach ($widgets as $widgetData) {
            $widgetModels[$widgetData['widget_key']] = Widget::firstOrCreate(
                ['widget_key' => $widgetData['widget_key']],
                [
                    'name' => $widgetData['name'],
                    'default_options' => $widgetData['default_options'],
                ]
            );
        }

        // 10. Ensure core Form Fields exist
        FormField::firstOrCreate(
            ['id' => 25],
            ['name' => 'Application', 'type' => 'widget_form', 'form_type' => 'form', 'is_required' => 0, 'order' => 0]
        );

        FormField::firstOrCreate(
            ['id' => 1],
            ['name' => 'Character Name', 'type' => 'text', 'form_type' => 'application', 'form_linked' => '25', 'is_required' => 1, 'order' => 1]
        );

        FormField::firstOrCreate(
            ['id' => 30],
            ['name' => 'Gender', 'type' => 'dropdown', 'form_type' => 'application', 'form_linked' => '25', 'options' => '"Male, Female, Intersex, Non Binary"', 'is_required' => 1, 'order' => 4]
        );

        FormField::firstOrCreate(
            ['id' => 34],
            ['name' => 'Species / Heritage', 'type' => 'text', 'form_type' => 'application', 'form_linked' => '25', 'is_required' => 1, 'order' => 2]
        );

        FormField::firstOrCreate(
            ['id' => 35],
            ['name' => 'Age', 'type' => 'text', 'form_type' => 'application', 'form_linked' => '25', 'is_required' => 1, 'order' => 3]
        );

        // 11. Ensure essential Page-Widget connections exist
        if (isset($pageModels['/'])) {
            $homeLayout = Layout::where('name', 'Home Page')->first();
            $newsBlock = LayoutBlock::where('layout_id', $homeLayout->id)->where('name', 'News Block')->first();
            $postBlock = LayoutBlock::where('layout_id', $homeLayout->id)->where('name', 'Post Block')->first();
            $welcomeBlock = LayoutBlock::where('layout_id', $homeLayout->id)->where('name', 'Site Welcome')->first();

            if ($newsBlock && isset($widgetModels['news'])) {
                DB::table('page_widget')->updateOrInsert(
                    ['page_id' => $pageModels['/']->id, 'widget_id' => $widgetModels['news']->id],
                    ['layout_block_id' => $newsBlock->id, 'sort_order' => 0, 'instance_options' => '[]', 'created_at' => now(), 'updated_at' => now()]
                );
            }
            if ($postBlock && isset($widgetModels['posts'])) {
                DB::table('page_widget')->updateOrInsert(
                    ['page_id' => $pageModels['/']->id, 'widget_id' => $widgetModels['posts']->id],
                    ['layout_block_id' => $postBlock->id, 'sort_order' => 0, 'instance_options' => '[]', 'created_at' => now(), 'updated_at' => now()]
                );
            }
            if ($welcomeBlock && isset($widgetModels['document'])) {
                DB::table('page_widget')->updateOrInsert(
                    ['page_id' => $pageModels['/']->id, 'widget_id' => $widgetModels['document']->id],
                    ['layout_block_id' => $welcomeBlock->id, 'sort_order' => 0, 'instance_options' => json_encode(['content' => 'Welcome to the site']), 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
