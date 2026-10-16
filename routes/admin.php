<?php

use Illuminate\Support\Facades\Route;
use \Illuminate\Auth\Middleware\Authenticate;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\LayoutController;
use App\Http\Controllers\Admin\FormManagerController;
use App\Http\Controllers\Admin\WidgetController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\NavigationController;
use App\Http\Controllers\Admin\TimeSystemController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\CharacterAssignmentController;
use App\Http\Controllers\Admin\RankController;
use App\Http\Controllers\Admin\RankSetController;
use App\Http\Controllers\Admin\ServiceRecordController;
use App\Http\Controllers\Admin\PromoteCharacterController;
use App\Http\Controllers\Admin\TransferCharacterController;
use App\Http\Controllers\Admin\ChangeCharacterStatusController;
use App\Http\Controllers\Admin\StoryController;
use App\Http\Controllers\Admin\BookcaseController;
use App\Http\Controllers\Admin\BookController;
use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\AwardController;
use App\Http\Controllers\Admin\AwardCategoryController;
use App\Http\Controllers\Admin\LexiconTermController;
use App\Http\Controllers\Admin\LexiconLanguageController;


Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    
    // ==========================================
    // 1. COMMAND CENTER & TODOS
    // ==========================================
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ==========================================
    // 2. CORE ARCHITECTURE & SYSTEM
    // ==========================================
    
    // Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings/update', [SettingController::class, 'update']);
    
    // Themes & Skins
    Route::get('/themes', [ThemeController::class, 'index']);
    Route::post('/themes/upload', [ThemeController::class, 'upload']);
    Route::post('/themes/{skin}/activate', [ThemeController::class, 'activate']);
    Route::get('/themes/{skin}/export', [ThemeController::class, 'export']);
    Route::post('/themes/css', [ThemeController::class, 'updateCss']);
    
    // Navigation Links
    Route::get('/navigation', [NavigationController::class, 'index']);
    Route::post('/navigation', [NavigationController::class, 'store']);
    Route::put('/navigation/{link}', [NavigationController::class, 'update']);
    Route::delete('/navigation/{link}', [NavigationController::class, 'destroy']);
    
    // Navigation Options
    Route::get('/navigation/options', [NavigationController::class, 'optionsIndex'])->name('navigation.options');
    Route::post('/navigation/options', [NavigationController::class, 'storeOption'])->name('navigation.options.store');
    Route::put('/navigation/options/{option}', [NavigationController::class, 'updateOption'])->name('navigation.options.update');
    Route::delete('/navigation/options/{option}', [NavigationController::class, 'destroyOption'])->name('navigation.options.destroy');

    // Time Systems
    Route::post('/time-systems', [TimeSystemController::class, 'store']);
    Route::put('/time-systems/{timeSystem}', [TimeSystemController::class, 'update']);
    Route::delete('/time-systems/{timeSystem}', [TimeSystemController::class, 'destroy']);

    // ==========================================
    // 3. LEXICON & TRANSLATIONS
    // ==========================================
    // Lexicon Terms
    Route::get('/lexicon', [LexiconTermController::class, 'index']);
    Route::post('/lexicon', [LexiconTermController::class, 'update']);
    Route::delete('/lexicon/term/{id}', [LexiconTermController::class, 'destroyTerm']);
    Route::delete('/lexicon/group/{groupName}', [LexiconTermController::class, 'destroyGroup']);
    Route::delete('/lexicon/group/{master}/subgroup/{subgroup}', [LexiconTermController::class, 'destroySubgroup']);
    
    // Lexicon Languages
    Route::post('/lexicon/language', [LexiconLanguageController::class, 'store']);
    Route::put('/lexicon/language', [LexiconLanguageController::class, 'update']);
    Route::delete('/lexicon/language/{code}', [LexiconLanguageController::class, 'destroy']);
    Route::delete('/lexicon/language/{code}/clear', [LexiconLanguageController::class, 'clear']);
    Route::get('/lexicon/export/{locale?}', [LexiconLanguageController::class, 'export']);
    Route::post('/lexicon/import', [LexiconLanguageController::class, 'import']);
    Route::get('/lexicon/template/blank', [LexiconLanguageController::class, 'exportBlank']);

    // ==========================================
    // 4. CONTENT MANAGEMENT (Pages, Forms, News)
    // ==========================================
    
    // Pages
    Route::get('/pages', [PageController::class, 'index']);
    Route::post('/pages', [PageController::class, 'store']);
    Route::put('/pages/{page}', [PageController::class, 'update']);
    Route::delete('/pages/{page}', [PageController::class, 'destroy']);
    Route::get('/pages/create', [PageController::class, 'create']);
    Route::get('/pages/{page}/edit', [PageController::class, 'edit']);
    
    // Grid Layouts
    Route::post('/layouts', [LayoutController::class, 'store']);
    Route::put('/layouts/{layout}', [LayoutController::class, 'update']);
    Route::delete('/layouts/{layout}', [LayoutController::class, 'destroy']);

    // Forms & Fields Hub
    Route::get('/forms', [FormManagerController::class, 'index']);
    Route::post('/forms', [FormManagerController::class, 'storeForm']);
    Route::put('/forms/{id}', [FormManagerController::class, 'updateForm']);
    Route::delete('/forms/{id}', [FormManagerController::class, 'destroyForm']);
    Route::post('/forms/fields', [FormManagerController::class, 'storeField']);
    Route::put('/forms/fields/{id}', [FormManagerController::class, 'updateField']);
    Route::delete('/forms/fields/{id}', [FormManagerController::class, 'destroyField']);

    // Widgets Registry & Base Templates
    Route::post('/widgets/sync', [WidgetController::class, 'sync'])->name('admin.widgets.sync');
    Route::post('/widgets', [WidgetController::class, 'store']);
    Route::put('/widgets/{id}', [WidgetController::class, 'update']);
    Route::delete('/widgets/{id}', [WidgetController::class, 'destroy']);

    // News
    Route::get('/news', [NewsController::class, 'index']);
    Route::post('/news', [NewsController::class, 'store']);
    Route::put('/news/{id}', [NewsController::class, 'update']);
    Route::delete('/news/{id}', [NewsController::class, 'destroy']);

    // ==========================================
    // 5. ROSTER & OPERATIONS
    // ==========================================
    
    // Sections
    Route::get('/sections', [SectionController::class, 'index']);
    Route::post('/sections', [SectionController::class, 'store']);
    Route::put('/sections/{section}', [SectionController::class, 'update']);
    Route::delete('/sections/{section}', [SectionController::class, 'destroy']);
    Route::post('/sections/generate-description', [SectionController::class, 'generateDescription'])->name('sections.generate-description');
    
    // Positions
    Route::post('/sections/{id}/positions', [PositionController::class, 'store']);
    Route::put('/positions/{position}', [PositionController::class, 'update']);
    Route::delete('/positions/{position}', [PositionController::class, 'destroy']);

    // Applications & Intake
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/applications/{id}/approve', [ApplicationController::class, 'approve']);
    Route::post('/applications/{id}/reject', [ApplicationController::class, 'reject']);
    
    // Character Reassignment (Invokable)
    Route::post('/characters/{id}/reassign', CharacterAssignmentController::class);

    // Ranks & Graphics
    Route::get('/ranks', [RankController::class, 'index']);
    Route::get('/ranks/images', [RankController::class, 'images']);
    Route::post('/ranks/reorder', [RankController::class, 'reorder']); // Added
    Route::post('/ranks', [RankController::class, 'store']);
    Route::put('/ranks/{rank}', [RankController::class, 'update']);
    Route::delete('/ranks/{rank}', [RankController::class, 'destroy']);

    // Rank Sets & Classifications
    Route::post('/rank-sets', [RankSetController::class, 'store']);
    Route::delete('/rank-sets/{rankSet}', [RankSetController::class, 'destroy']);
    Route::post('/rank-sets/{rankSet}/assign', [RankSetController::class, 'assignSections']);
    
    // ==========================================
    // 6. STORY ENGINE
    // ==========================================
    
    // Master Story View
    Route::get('/story', [StoryController::class, 'storyManager']);
    
    // Bookcases
    Route::post('/bookcases/reorder', [BookcaseController::class, 'reorder']);
    Route::post('/bookcases', [BookcaseController::class, 'store']);
    Route::put('/bookcases/{id}', [BookcaseController::class, 'update']);
    Route::delete('/bookcases/{id}', [BookcaseController::class, 'destroy']);
    
    // Books
    Route::post('/books/reorder', [BookController::class, 'reorder']);
    Route::post('/books', [BookController::class, 'store']);
    Route::put('/books/{id}', [BookController::class, 'update']);
    Route::delete('/books/{id}', [BookController::class, 'destroy']);

    // Chapters (Admin Structural Changes)
    Route::post('/chapters/reorder', [ChapterController::class, 'reorder']);
    Route::put('/chapters/{id}/move', [ChapterController::class, 'move']);
    Route::delete('/chapters/{id}', [ChapterController::class, 'destroy']);

    // ==========================================
    // USERS, ROLES & AWARDS
    // ==========================================
    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::put('/users/{user}/role', [UserController::class, 'updateRole']);
    Route::put('/users/{user}/loa', [UserController::class, 'updateLoa']);

    // Roles & Permissions
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/roles/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::delete('/roles/permissions/{id}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    // Awards
    Route::get('awards', [AwardController::class, 'index'])->name('awards.index');
    Route::get('awards/images', [AwardController::class, 'images'])->name('awards.images');
    Route::post('awards', [AwardController::class, 'store'])->name('awards.store');
    Route::put('awards/{award}', [AwardController::class, 'update'])->name('awards.update');
    Route::delete('awards/{award}', [AwardController::class, 'destroy'])->name('awards.destroy');
    
    // Award Categories
    Route::post('awards/categories', [AwardCategoryController::class, 'store'])->name('awards.categories.store');
    Route::put('awards/categories/{id}', [AwardCategoryController::class, 'update'])->name('awards.categories.update');
    Route::delete('awards/categories/{id}', [AwardCategoryController::class, 'destroy'])->name('awards.categories.destroy');

    // ==========================================
    // 8. SERVICE RECORDS & CHARACTER ACTIONS
    // ==========================================
    
    // Core Service Record Logging
    Route::get('/service-records', [ServiceRecordController::class, 'index'])->name('service-records.index');
    Route::put('/service-records/{serviceRecord}', [ServiceRecordController::class, 'updateRecord'])->name('service-records.update');
    Route::post('/service-records/manual-entry', [ServiceRecordController::class, 'issueManualEntry'])->name('service-records.issueManualEntry');
    Route::post('/service-records/award', [ServiceRecordController::class, 'issueAward'])->name('service-records.award');
    Route::post('/service-records/{serviceRecord}/remove', [ServiceRecordController::class, 'removeAward'])->name('service-records.remove');
    
    // Structural Character Changes (Invokable Controllers)
    Route::post('/service-records/promotion', PromoteCharacterController::class)->name('service-records.promotion');
    Route::post('/service-records/transfer', TransferCharacterController::class)->name('service-records.issueTransfer');
    Route::post('/service-records/status-change', ChangeCharacterStatusController::class)->name('service-records.issueStatusChange');
});