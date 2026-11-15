<?php

namespace Tests;

use App\Models\Book;
use App\Models\Grid;
use App\Models\GridModule;
use App\Models\ThematicBinding;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seedMowsTestEnvironment();
    }

    protected function seedMowsTestEnvironment(): void
    {
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        ThematicBinding::firstOrCreate(
            ['directory_name' => 'default'],
            [
                'binding_title' => 'Default',
                'package_version' => '1.0',
                'is_active' => true,
                'is_global_default' => true,
            ]
        );

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

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $webmasterRole = Role::firstOrCreate([
            'name' => 'webmaster',
            'guard_name' => 'web',
        ]);
        $seniorUserRole = Role::firstOrCreate([
            'name' => 'senior user',
            'guard_name' => 'web',
        ]);
        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'web',
        ]);
        Role::firstOrCreate([
            'name' => 'guest',
            'guard_name' => 'web',
        ]);

        $webmasterRole->syncPermissions($permissions);
        $seniorUserRole->syncPermissions($permissions);
        $userRole->syncPermissions(['edit_chapter', 'content_shield', 'publish_chapter']);

        $grid = Grid::firstOrCreate(
            ['grid_title' => 'Test Grid'],
            ['grid_synopsis' => 'Shared grid fixture for feature tests.']
        );

        $gridModuleTitles = [
            'Content',
            'Top',
            'Middle',
            'Bottom',
            'SideBar',
            'WelcomePanel',
            'UnreadMessages',
            'DraftChapters',
            'ProfileInformation',
            'UpdatePassword',
            'DeleteAccount',
        ];

        foreach ($gridModuleTitles as $sortOrder => $title) {
            GridModule::firstOrCreate(
                [
                    'grid_id' => $grid->id,
                    'grid_module_title' => $title,
                ],
                [
                    'column_span' => 12,
                    'row_span' => 1,
                    'grid_module_sort_order' => $sortOrder,
                ]
            );
        }

        $books = [
            '/' => 'Landing',
            'dashboard' => 'Dashboard',
            'chapters/create' => 'Write Chapter',
            'messenger' => 'Messages',
            'admin' => 'Command Centre',
            'admin/applications' => 'Candidate Petitions',
            'admin/candidate-petitions' => 'Candidate Petitions',
            'admin/awards' => 'Awards',
            'admin/forms' => 'Forms',
            'admin/insignia' => 'Insignia',
            'admin/lexicon' => 'Lexicon',
            'admin/navigation' => 'Navigation',
            'admin/navigation/options' => 'Navigation Options',
            'admin/news' => 'News',
            'admin/ranks' => 'Ranks',
            'admin/roles' => 'Access Clearance Roles',
            'admin/sections' => 'Sections',
            'admin/service-records' => 'Service Records',
            'admin/settings' => 'Settings',
            'admin/story' => 'Story',
            'admin/themes' => 'Themes',
            'admin/users' => 'Users',
            'admin/volumes' => 'Volumes',
            'admin/wiki' => 'Wiki',
        ];

        foreach ($books as $bookUrl => $bookTitle) {
            Book::firstOrCreate(
                ['book_url' => $bookUrl],
                [
                    'book_title' => $bookTitle,
                    'is_published' => true,
                    'grid_id' => $grid->id,
                ]
            );
        }

        Cache::flush();
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
