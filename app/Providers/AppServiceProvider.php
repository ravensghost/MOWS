<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Mail\Transport\PhpMailTransport;
use App\Models\TableOfContent;
use App\Models\User;
use App\Models\Configuration;
use App\Models\Bulletin;
use App\Models\ContentTier;
use App\Models\Folio;
use App\Models\Persona;
use App\Models\Volume;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register native PHP mail() custom transport driver
        Mail::extend('phpmail', fn() => new PhpMailTransport());

        // Super Admin bypass for all Gates and Spatie permissions
        Gate::before(function (User $user, $ability) {
            return ($user->role === 'webmaster' || $user->hasRole('webmaster')) ? true : null;
        });

        Gate::define('access-admin', function (User $user) {
            return in_array($user->role, ['senior user', 'admin', 'webmaster'])
                || $user->hasAnyRole(['senior user', 'admin', 'webmaster']);
        });

        Gate::define('manage-users', function (User $user) {
            return $user->role === 'webmaster' || $user->hasRole('webmaster');
        });

        View::composer([
            'layouts.app',
            'layouts.partials.navigation',
            'errors.minimal',
        ], function ($view) {
            try {
                // 1. Settings & Skins (Cached primitive arrays)
                $globalSettings = Cache::rememberForever('global_settings', function () {
                    return Configuration::whereNot('configuration_token', 'like', 'theme_%')
                        ->pluck('configuration_payload', 'configuration_token')
                        ->toArray();
                });

                $skinSettings = Cache::rememberForever('skin_settings', function () {
                    return Configuration::where('configuration_token', 'like', 'theme_%')
                        ->pluck('configuration_payload', 'configuration_token')
                        ->toArray();
                });

                // 2. Active Skin (Safe Array Cache from thematic_bindings)
                $activeSkinArray = Cache::rememberForever('active_skin_default_array', function () {
                    $skin = DB::table('thematic_bindings')->where('is_global_default', 1)->first();
                    return $skin ? (array) $skin : null;
                });
                $activeSkin = $activeSkinArray ? (object) $activeSkinArray : null;

                // 3. Table of Contents Entries (Safe Array Cache from table_of_contents)
                $linksArray = Cache::rememberForever('table_of_contents_all_array', function () {
                    return TableOfContent::orderBy('group_sort_order')
                        ->orderBy('group_title')
                        ->orderBy('entry_sort_order')
                        ->where('is_visible', true)
                        ->get()
                        ->toArray();
                });
                $allRawLinks = collect(json_decode(json_encode($linksArray)));

                // 4. In-memory session-aware filtering (Spatie permission + access_clearance)
                $user         = auth()->user();
                $isAuthed     = auth()->check();
                $isAdmin      = $isAuthed && $user && $user->can('access-admin');
                $isSuperAdmin = $isAuthed && $user && ($user->role === 'webmaster' || $user->hasRole('webmaster'));

                $visibleLinks = $allRawLinks->filter(function ($link) use ($user, $isAuthed, $isAdmin, $isSuperAdmin) {
                    if (! empty($link->required_permission)) {
                        return $isAuthed && $user && $user->can($link->required_permission);
                    }

                    $clearance = strtolower(trim((string) ($link->access_clearance ?? 'public')));

                    return match ($clearance) {
                        'public'      => true,
                        'guest'       => ! $isAuthed,
                        'auth'        => $isAuthed,
                        'admin'       => $isAdmin,
                        'super_admin' => $isSuperAdmin,
                        default       => false,
                    };
                });

                $primaryNav = $visibleLinks->filter(function ($link) {
                    return strtolower(trim((string) ($link->display_location ?? ''))) === 'primary';
                });

                $drawerNav = $visibleLinks->filter(function ($link) {
                    return strtolower(trim((string) ($link->display_location ?? ''))) === 'drawer';
                });

                // 5. Messenger & Chapter Modal Globals (Isolated so secondary table changes never break Navigation)
                $myCharacters    = collect();
                $users           = collect();
                $characters      = collect();
                $threads         = collect();
                $activeBooks     = collect();
                $allCharacters   = collect();
                $languageRatings = collect();
                $sexRatings      = collect();
                $violenceRatings = collect();

                try {
                    if ($isAuthed && $user) {
                        $myCharacters = Persona::where('user_id', $user->id)->get();
                        $users        = User::all();
                        $characters   = Persona::select('id', 'persona_name')->orderBy('persona_name')->get();
                    }

                    $activeBooks = Volume::with('shelf')
                        ->whereHas('publicationState', function ($q) {
                            $q->where('book_url', 'active');
                        })
                        ->orderBy('volume_sort_order')
                        ->get();

                    $allCharacters = Persona::where('persona_status', 'active')
                        ->orderBy('persona_name')
                        ->get();

                    $languageRatings = ContentTier::where('tier_category', 'Language')->orderBy('tier_sort_order')->get();
                    $sexRatings      = ContentTier::where('tier_category', 'Sex')->orderBy('tier_sort_order')->get();
                    $violenceRatings = ContentTier::where('tier_category', 'Violence')->orderBy('tier_sort_order')->get();
                } catch (\Throwable $modalErr) {
                    logger()->warning('AppServiceProvider Modal Globals Warning: ' . $modalErr->getMessage());
                }

                $view->with([
                    'globalSettings'  => $globalSettings,
                    'skinSettings'    => $skinSettings,
                    'activeSkin'      => $activeSkin,
                    'primaryNav'      => $primaryNav,
                    'drawerNav'       => $drawerNav,
                    'myCharacters'    => $myCharacters,
                    'users'           => $users,
                    'characters'      => $characters,
                    'threads'         => $threads,
                    'activeBooks'     => $activeBooks,
                    'allCharacters'   => $allCharacters,
                    'languageRatings' => $languageRatings,
                    'sexRatings'      => $sexRatings,
                    'violenceRatings' => $violenceRatings,
                ]);
            } catch (\Throwable $e) {
                logger()->error('AppServiceProvider ViewComposer Error: ' . $e->getMessage());

                $view->with([
                    'globalSettings'  => [],
                    'skinSettings'    => [],
                    'activeSkin'      => null,
                    'primaryNav'      => collect(),
                    'drawerNav'       => collect(),
                    'myCharacters'    => collect(),
                    'users'           => collect(),
                    'characters'      => collect(),
                    'threads'         => collect(),
                    'activeBooks'     => collect(),
                    'allCharacters'   => collect(),
                    'languageRatings' => collect(),
                    'sexRatings'      => collect(),
                    'violenceRatings' => collect(),
                ]);
            }
        });

        // Self-hydrating fallback composers for News and Story Posts widgets
        View::composer('widgets.news', function ($view) {
            $data = $view->getData();
            if (empty($data['newsItems']) || ($data['newsItems'] instanceof \Illuminate\Support\Collection && $data['newsItems']->isEmpty())) {
                $view->with('newsItems', Bulletin::with('author')->orderBy('published_at', 'desc')->take(5)->get());
            }
        });

        View::composer('widgets.posts', function ($view) {
            $data = $view->getData();
            if (empty($data['storyPosts']) || ($data['storyPosts'] instanceof \Illuminate\Support\Collection && $data['storyPosts']->isEmpty())) {
                $view->with('storyPosts', Folio::with('activePersona')->where('folio_status', 'published')->orderBy('created_at', 'desc')->take(5)->get());
            }
        });

        View::composer('widgets.user_drafts', function ($view) {
            $data = $view->getData();
            if (empty($data['draftChapters']) || ($data['draftChapters'] instanceof \Illuminate\Support\Collection && $data['draftChapters']->isEmpty())) {
                $userId = auth()->id();
                $view->with('draftChapters', $userId ? Folio::where('user_id', $userId)->where('folio_status', 'draft')->orderBy('updated_at', 'desc')->get() : collect());
            }
        });
    }
}
