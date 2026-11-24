<?php

namespace App\Providers;

use App\Mail\Transport\PhpMailTransport;
use App\Models\Bulletin;
use App\Models\Configuration;
use App\Models\ContentTier;
use App\Models\CorrespondenceThread;
use App\Models\Folio;
use App\Models\Persona;
use App\Models\TableOfContent;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // 1. Register native PHP mail() custom transport driver
        Mail::extend('phpmail', fn () => new PhpMailTransport);

        // 2. Register Global Chronological Eloquent Macros (Gold Standard Section 6.3)
        Builder::macro('worldDateFor', function (string $column = 'created_at', string $format = 'M d, Y H:i'): string {
            /** @var Builder $this */
            $model = $this->getModel();
            $value = $model->getAttribute($column);

            return function_exists('world_date') ? world_date($value, $format) : (string) $value;
        });

        Builder::macro('realDateFor', function (string $column = 'in_universe_date_string', string $format = 'M d, Y'): string {
            /** @var Builder $this */
            $model = $this->getModel();
            $value = $model->getAttribute($column);

            return function_exists('real_date') ? real_date($value, $format) : (string) $value;
        });

        // 3. Super Admin bypass for all Gates and Spatie permissions
        Gate::before(function (User $user, string $ability) {
            return ($user->role === 'webmaster' || $user->hasRole('webmaster')) ? true : null;
        });

        Gate::define('access-admin', function (User $user) {
            return in_array($user->role, ['senior user', 'admin', 'webmaster'], true)
                || $user->hasAnyRole(['senior user', 'admin', 'webmaster']);
        });

        Gate::define('manage-users', function (User $user) {
            return $user->role === 'webmaster' || $user->hasRole('webmaster');
        });

        // 4. Master Layout, Table of Contents & Modal View Composer
        View::composer([
            'layouts.app',
            'layouts.partials.table_of_contents',
            'layouts.partials.navigation',
            'errors.minimal',
        ], function ($view) {
            try {
                // Settings & Skins (Cached primitive arrays)
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

                // Active Skin (Safe Array Cache from thematic_bindings)
                $activeSkinArray = Cache::rememberForever('active_skin_default_array', function () {
                    $skin = DB::table('thematic_bindings')->where('is_global_default', 1)->first();

                    return $skin ? (array) $skin : null;
                });
                $activeSkin = $activeSkinArray ? (object) $activeSkinArray : null;

                // Table of Contents Entries (Safe Array Cache from table_of_contents)
                $linksArray = Cache::rememberForever('table_of_contents_all_array', function () {
                    return TableOfContent::orderBy('group_sort_order')
                        ->orderBy('group_title')
                        ->orderBy('entry_sort_order')
                        ->where('is_visible', true)
                        ->get()
                        ->toArray();
                });

                $allLinks = collect(array_map(fn ($item) => (object) $item, $linksArray));

                // In-memory session-aware filtering (Spatie permission + access_clearance)
                $user = auth()->user();
                $isAuthed = auth()->check();
                $isAdmin = $isAuthed && $user && $user->can('access-admin');
                $isSuperAdmin = $isAuthed && $user && ($user->role === 'webmaster' || $user->hasRole('webmaster'));

                $visibleLinks = $allLinks->filter(function ($link) use ($user, $isAuthed, $isAdmin, $isSuperAdmin) {
                    if (! empty($link->required_permission)) {
                        return $isAuthed && $user && $user->can($link->required_permission);
                    }

                    $clearance = strtolower(trim((string) ($link->access_clearance ?? 'public')));

                    return match ($clearance) {
                        'public' => true,
                        'guest' => ! $isAuthed,
                        'auth' => $isAuthed,
                        'admin' => $isAdmin,
                        'super_admin' => $isSuperAdmin,
                        default => false,
                    };
                });

                $primaryNav = $visibleLinks->filter(function ($link) {
                    return strtolower(trim((string) ($link->display_location ?? ''))) === 'primary';
                });

                $drawerNav = $visibleLinks->filter(function ($link) {
                    return strtolower(trim((string) ($link->display_location ?? ''))) === 'drawer';
                });

                // Messenger & Folio Modal Globals (Isolated so secondary table changes never break Navigation)
                $myCharacters = collect();
                $users = collect();
                $characters = collect();
                $threads = collect();
                $activeBooks = collect();
                $allCharacters = collect();
                $languageRatings = collect();
                $sexRatings = collect();
                $violenceRatings = collect();

                try {
                    if ($isAuthed && $user) {
                        $myCharacters = Persona::where('user_id', $user->id)->get();
                        $users = User::all();
                        $characters = Persona::select('id', 'persona_name')->orderBy('persona_name')->get();
                        $threads = CorrespondenceThread::with(['participants.user', 'participants.persona', 'entries.user', 'entries.persona'])
                            ->whereHas('participants', fn ($q) => $q->where('user_id', $user->id))
                            ->orderByDesc('updated_at')
                            ->limit(25)
                            ->get();
                    }

                    $activeBooks = Volume::with('shelf')
                        ->whereHas('publicationState', function ($q) {
                            $q->where('book_url', 'active');
                        })
                        ->orderBy('volume_sort_order')
                        ->get();

                    $allCharacters = Persona::whereIn('persona_status', ['active', 'npc', 'secondary'])
                        ->orderBy('persona_name')
                        ->get();

                    $languageRatings = ContentTier::where('tier_category', 'Language')->orderBy('tier_sort_order')->get();
                    $sexRatings = ContentTier::where('tier_category', 'Sex')->orderBy('tier_sort_order')->get();
                    $violenceRatings = ContentTier::where('tier_category', 'Violence')->orderBy('tier_sort_order')->get();
                } catch (\Throwable $modalErr) {
                    logger()->warning('AppServiceProvider Modal Globals Warning: '.$modalErr->getMessage());
                }

                $view->with([
                    'globalSettings' => $globalSettings,
                    'skinSettings' => $skinSettings,
                    'activeSkin' => $activeSkin,
                    'primaryNav' => $primaryNav,
                    'drawerNav' => $drawerNav,
                    'myCharacters' => $myCharacters,
                    'users' => $users,
                    'characters' => $characters,
                    'threads' => $threads,
                    'activeBooks' => $activeBooks,
                    'allCharacters' => $allCharacters,
                    'languageRatings' => $languageRatings,
                    'sexRatings' => $sexRatings,
                    'violenceRatings' => $violenceRatings,
                ]);
            } catch (\Throwable $e) {
                logger()->error('AppServiceProvider ViewComposer Error: '.$e->getMessage());
                $view->with([
                    'globalSettings' => [],
                    'skinSettings' => [],
                    'activeSkin' => null,
                    'primaryNav' => collect(),
                    'drawerNav' => collect(),
                    'myCharacters' => collect(),
                    'users' => collect(),
                    'characters' => collect(),
                    'threads' => collect(),
                    'activeBooks' => collect(),
                    'allCharacters' => collect(),
                    'languageRatings' => collect(),
                    'sexRatings' => collect(),
                    'violenceRatings' => collect(),
                ]);
            }
        });

        // 5. Self-hydrating fallback composers for Bulletins, Story Folios, and User Drafts modules
        View::composer(['modules.bulletins', 'modules.news', 'widgets.bulletins', 'widgets.news'], function ($view) {
            $data = $view->getData();
            if (empty($data['newsItems']) || ($data['newsItems'] instanceof Collection && $data['newsItems']->isEmpty())) {
                $bulletins = Bulletin::with('author')->orderBy('published_at', 'desc')->take(5)->get();
                $view->with([
                    'newsItems' => $bulletins,
                    'bulletins' => $bulletins,
                ]);
            }
        });

        View::composer(['modules.folio', 'modules.posts', 'widgets.folio', 'widgets.posts'], function ($view) {
            $data = $view->getData();
            if (empty($data['storyPosts']) || ($data['storyPosts'] instanceof Collection && $data['storyPosts']->isEmpty())) {
                $folios = Folio::with('activePersona')
                    ->where('folio_status', 'published')
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();
                $view->with([
                    'storyPosts' => $folios,
                    'folios' => $folios,
                ]);
            }
        });

        View::composer(['modules.user_folio_drafts', 'widgets.user_drafts'], function ($view) {
            $data = $view->getData();
            if (empty($data['draftChapters']) || ($data['draftChapters'] instanceof Collection && $data['draftChapters']->isEmpty())) {
                $userId = auth()->id();
                $drafts = $userId
                    ? Folio::where('user_id', $userId)->where('folio_status', 'draft')->orderBy('updated_at', 'desc')->get()
                    : collect();
                $view->with([
                    'draftChapters' => $drafts,
                    'draftFolios' => $drafts,
                ]);
            }
        });
    }
}
