<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Page;
use App\Models\News;
use App\Models\Chapter;
use App\Models\Character;

class PublicController extends Controller
{
    public function showPage($slug = '/')
    {
        $slug = $slug === '/' ? '/' : ltrim($slug, '/');

        $systemPage = Page::where('slug', $slug)
            ->where('is_published', true)
            ->with([
                'layout.blocks',
                'widgets' => function ($query) {
                    $query->orderBy('page_widget.sort_order');
                }
            ])
            ->firstOrFail();

        $user = auth()->user();
        $systemPage->setRelation('widgets', $systemPage->widgets->filter(function ($widget) use ($user) {
            $required = $widget->pivot->required_permission;
            if (empty($required)) {
                return true;
            }
            return $user ? $user->can($required) : false;
        })->values());

        $data = [
            'page'       => $systemPage,
            'systemPage' => $systemPage,
            'newsItems'  => null,
            'storyPosts' => null,
        ];

        $widgetKeys = $systemPage->widgets->pluck('widget_key')->toArray();

        if (in_array('news', $widgetKeys) || in_array('widget_news', $widgetKeys)) {
            $data['newsItems'] = News::with('author')
                ->orderBy('published_at', 'desc')
                ->take(5)
                ->get();
        }

        if (in_array('posts', $widgetKeys) || in_array('widget_posts', $widgetKeys)) {
            $data['storyPosts'] = Chapter::with('activeCharacter')
                ->where('status', 'published')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        return view('page', $data);
    }

    public function showBio(Character $character)
    {
        // Fetch the bio page blueprint for the hybrid grid system
        $systemPage = Page::where('slug', 'bio')
            ->where('is_published', true)
            ->with([
                'layout.blocks',
                'widgets' => function ($query) {
                    $query->orderBy('page_widget.sort_order');
                }
            ])
            ->firstOrFail();

        $user = auth()->user();
        $systemPage->setRelation('widgets', $systemPage->widgets->filter(function ($widget) use ($user) {
            $required = $widget->pivot->required_permission;
            if (empty($required)) {
                return true;
            }
            return $user ? $user->can($required) : false;
        })->values());

        // Eager load all nested relationships required by bio widgets to maintain Q < 10
        $character->load([
            'user',
            'rank',
            'position.section',
            'application.answers.formField',
            'serviceRecords.award',
            'serviceRecords.issuer',
        ]);

        // Share the character globally with all views/widgets for this request
        \Illuminate\Support\Facades\View::share('character', $character);

        $data = [
            'page'       => $systemPage,
            'systemPage' => $systemPage,
            'character'  => $character,
            'newsItems'  => null,
            'storyPosts' => null,
        ];

        return view('page', $data);
    }
}
