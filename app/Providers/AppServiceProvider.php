<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use App\Models\NavigationLink;
use App\Models\User;
use App\Models\Setting;
use App\Models\Skin;
use App\Models\News;
use App\Models\Chapter;
use App\Models\Character;
use App\Models\Thread;
use App\Models\ThreadParticipant;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
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
                    return Setting::whereNot('key', 'like', 'theme_%')->pluck('value', 'key')->toArray();
                });

                $skinSettings = Cache::rememberForever('skin_settings', function () {
                    return Setting::where('key', 'like', 'theme_%')->pluck('value', 'key')->toArray();
                });

                // 2. Active Skin (Safe Array Cache)
                $activeSkinArray = Cache::rememberForever('active_skin_default_array', function () {
                    $skin = Skin::where('is_global_default', 1)->first();
                    return $skin ? $skin->toArray() : null;
                });
                $activeSkin = $activeSkinArray ? (object) $activeSkinArray : null;

                // 3. Navigation Links (Safe Array Cache)
                $linksArray = Cache::rememberForever('navigation_links_all_array', function () {
                    return NavigationLink::orderBy('group_order')
                        ->orderBy('group_name')
                        ->orderBy('order')
                        ->where('is_visible', true)
                        ->get()
                        ->toArray();
                });
                $allRawLinks = collect(json_decode(json_encode($linksArray)));

                // 4. In-memory session-aware filtering (Spatie permission + visibility fallback)
                $user         = auth()->user();
                $isAuthed     = auth()->check();
                $isAdmin      = $isAuthed && $user && $user->can('access-admin');
                $isSuperAdmin = $isAuthed && $user && ($user->role === 'webmaster' || $user->hasRole('webmaster'));

                $visibleLinks = $allRawLinks->filter(function ($link) use ($user, $isAuthed, $isAdmin, $isSuperAdmin) {
                    if (!empty($link->required_permission)) {
                        return $isAuthed && $user && $user->can($link->required_permission);
                    }

                    $vis = strtolower(trim((string) $link->visibility));

                    return match ($vis) {
                        'public'      => true,
                        'guest'       => !$isAuthed,
                        'auth'        => $isAuthed,
                        'admin'       => $isAdmin,
                        'super_admin' => $isSuperAdmin,
                        default       => false,
                    };
                });

                $primaryNav = $visibleLinks->filter(function ($link) {
                    return strtolower(trim((string) $link->location)) === 'primary';
                });

                $drawerNav = $visibleLinks->filter(function ($link) {
                    return strtolower(trim((string) $link->location)) === 'drawer';
                });

                // 5. Messenger Globals for Authenticated Users
                $myCharacters = collect();
                $users = collect();
                $characters = collect();
                $threads = collect();

                if ($isAuthed && $user) {
                    $myCharacters = Character::where('user_id', $user->id)->get();
                    $users = User::all(['id', 'name']);
                    $characters = Character::all(['id', 'name']);

                    $threadIds = ThreadParticipant::where('user_id', $user->id)->pluck('thread_id');
                    $threads = Thread::whereIn('id', $threadIds)
                        ->with(['participants.user', 'participants.character', 'messages.user', 'messages.character'])
                        ->orderByDesc('updated_at')
                        ->get();
                }

                // 6. Chapter Modal Globals
                $activeBooks = \App\Models\Book::with('bookcase')
                    ->whereHas('status', function ($q) {
                        $q->where('slug', 'active');
                    })
                    ->orderBy('sort_order')
                    ->get();

                $allCharacters = Character::where('status', 'active')->orderBy('name')->get();

                $languageRatings = \App\Models\ContentRating::where('name', 'Language')->orderBy('sort_rating')->get();
                $sexRatings = \App\Models\ContentRating::where('name', 'Sex')->orderBy('sort_rating')->get();
                $violenceRatings = \App\Models\ContentRating::where('name', 'Violence')->orderBy('sort_rating')->get();

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
                    'globalSettings' => [],
                    'skinSettings'   => [],
                    'activeSkin'     => null,
                    'primaryNav'     => collect(),
                    'drawerNav'      => collect(),
                    'myCharacters'   => collect(),
                    'users'          => collect(),
                    'characters'     => collect(),
                    'threads'        => collect(),
                ]);
            }
        });

        // Self-hydrating fallback composers for News and Story Posts widgets
        View::composer('widgets.news', function ($view) {
            $data = $view->getData();
            if (empty($data['newsItems']) || ($data['newsItems'] instanceof \Illuminate\Support\Collection && $data['newsItems']->isEmpty())) {
                $view->with('newsItems', News::with('author')->orderBy('published_at', 'desc')->take(5)->get());
            }
        });

        View::composer('widgets.posts', function ($view) {
            $data = $view->getData();
            if (empty($data['storyPosts']) || ($data['storyPosts'] instanceof \Illuminate\Support\Collection && $data['storyPosts']->isEmpty())) {
                $view->with('storyPosts', Chapter::with('activeCharacter')->where('status', 'published')->orderBy('created_at', 'desc')->take(5)->get());
            }
        });
        View::composer('widgets.user_drafts', function ($view) {
            $data = $view->getData();
            if (empty($data['draftChapters']) || ($data['draftChapters'] instanceof \Illuminate\Support\Collection && $data['draftChapters']->isEmpty())) {
                $userId = auth()->id();
                $view->with('draftChapters', $userId ? Chapter::where('author_user_id', $userId)->where('status', 'draft')->orderBy('updated_at', 'desc')->get() : collect());
            }
        });
        View::composer('widgets.unread_messages', function ($view) {
            $data = $view->getData();
            if (empty($data['unreadMessages']) || ($data['unreadMessages'] instanceof \Illuminate\Support\Collection && $data['unreadMessages']->isEmpty())) {
                $user = auth()->user();
                if ($user) {
                    $threadIds = \App\Models\ThreadParticipant::where('user_id', $user->id)->pluck('thread_id');
                    
                    // Retrieve messages in threads where the message wasn't sent by the current user 
                    // and has not been marked read (using the thread_participants last_read_at timestamp)
                    $unreadMessages = \App\Models\Message::whereIn('thread_id', $threadIds)
                        ->where('user_id', '!=', $user->id)
                        ->where(function ($query) use ($user, $threadIds) {
                            $query->whereHas('thread.participants', function ($subq) use ($user) {
                                $subq->where('user_id', $user->id)
                                     ->where(function ($q) {
                                         $q->whereColumn('messages.created_at', '>', 'thread_participants.last_read_at')
                                           ->orWhereNull('thread_participants.last_read_at');
                                     });
                            });
                        })
                        ->orderBy('created_at', 'desc')
                        ->get();

                    $view->with('unreadMessages', $unreadMessages);
                } else {
                    $view->with('unreadMessages', collect());
                }
            }
        });
    }
}