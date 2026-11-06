<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administrative Command Centre & Management Hub
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | 1. COMMAND CENTRE & SYSTEM INFRASTRUCTURE
    |--------------------------------------------------------------------------
    */
    require __DIR__ . '/admin/command_centre.php';
    require __DIR__ . '/admin/configurations.php';
    require __DIR__ . '/admin/thematic_bindings.php';
    require __DIR__ . '/admin/table_of_contents.php';
    require __DIR__ . '/admin/chronological_engines.php';
    require __DIR__ . '/admin/assets.php';
    require __DIR__ . '/admin/lexicon_codex.php';

    /*
    |--------------------------------------------------------------------------
    | 2. PUBLICATION & CONTENT MANAGEMENT
    |--------------------------------------------------------------------------
    */
    require __DIR__ . '/admin/books.php';
    require __DIR__ . '/admin/grids.php';
    require __DIR__ . '/admin/metadata.php';
    require __DIR__ . '/admin/modules.php';
    require __DIR__ . '/admin/bulletins.php';
    require __DIR__ . '/admin/encyclopedia_codex.php';

    /*
    |--------------------------------------------------------------------------
    | 3. ROSTER, DIVISIONS & CANDIDATE PETITIONS
    |--------------------------------------------------------------------------
    */
    require __DIR__ . '/admin/divisions.php';
    require __DIR__ . '/admin/assignments.php';
    require __DIR__ . '/admin/candidate_petitions.php';
    require __DIR__ . '/admin/insignia.php';

    /*
    |--------------------------------------------------------------------------
    | 4. NARRATIVE PUBLICATION ENGINE (STORY)
    |--------------------------------------------------------------------------
    */
    require __DIR__ . '/admin/shelves.php';
    require __DIR__ . '/admin/volumes.php';
    require __DIR__ . '/admin/folios.php';
    require __DIR__ . '/admin/publication_states.php';

    /*
    |--------------------------------------------------------------------------
    | 5. REGISTRY, ACCESS CLEARANCE & RECORDS
    |--------------------------------------------------------------------------
    */
    require __DIR__ . '/admin/users.php';
    require __DIR__ . '/admin/access_clearance_roles.php';
    require __DIR__ . '/admin/distinctions.php';
    require __DIR__ . '/admin/chronological_registers.php';
});
