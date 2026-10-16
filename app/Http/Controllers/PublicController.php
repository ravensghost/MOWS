<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Page;
use App\Models\News;
use App\Models\Chapter;
use App\Models\Character;
use App\Models\CharacterAttribute;

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
            'attributes',
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

    /**
     * Verify if the authenticated user is allowed to manage this character's portraits.
     */
    protected function authorizeAvatarAction(Character $character): void
    {
        $user = auth()->user();

        $isAuthorized = $user && (
            (int) $user->id === (int) $character->user_id ||
            $user->role === 'webmaster' ||
            $user->can('manage_roster_assignments') ||
            $user->can('manage_users')
        );

        abort_unless($isAuthorized, 403, lexicon('msg_CharacterAvatar_Unauthorised', 'Unauthorised to modify character portraits.'));
    }

    /**
     * Build the current portrait payload for reactive Alpine state updates.
     */
    protected function getPortraitPayload(Character $character): array
    {
        $character->refresh();
        $character->load('attributes');

        $primaryUrl = $character->avatar
            ? (Str::startsWith($character->avatar, ['http://', 'https://', '/']) ? $character->avatar : asset('images/characters/' . $character->avatar))
            : null;

        $gallery = $character->attributes
            ->where('key', 'gallery_image')
            ->values()
            ->map(function ($attr) {
                $val = $attr->value;
                return [
                    'id'       => $attr->id,
                    'filename' => $val,
                    'url'      => Str::startsWith($val, ['http://', 'https://', '/']) ? $val : asset('images/characters/' . $val),
                ];
            })
            ->all();

        return [
            'primary_filename' => $character->avatar,
            'primary_url'      => $primaryUrl,
            'gallery'          => $gallery,
        ];
    }

    /**
     * Upload a new character portrait via Dropzone to /public/images/characters/.
     */
    public function uploadCharacterAvatar(Request $request, Character $character)
    {
        $this->authorizeAvatarAction($character);

        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
        ]);

        $destinationPath = public_path('images/characters');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'webp');
        $filename = 'char_' . $character->id . '_' . time() . '_' . Str::lower(Str::random(5)) . '.' . $extension;

        $file->move($destinationPath, $filename);

        if (empty($character->avatar)) {
            $character->update(['avatar' => $filename]);
        } else {
            CharacterAttribute::create([
                'character_id' => $character->id,
                'key'          => 'gallery_image',
                'value'        => $filename,
            ]);
        }

        return response()->json([
            'message'   => lexicon('msg_CharacterAvatar_UploadSuccess', 'Character portrait uploaded successfully.'),
            'portraits' => $this->getPortraitPayload($character),
        ]);
    }

    /**
     * Promote a gallery image from character_attributes to be the primary characters.avatar.
     */
    public function setPrimaryCharacterAvatar(Request $request, Character $character)
    {
        $this->authorizeAvatarAction($character);

        $validated = $request->validate([
            'attribute_id' => ['required', 'integer'],
        ]);

        $attribute = CharacterAttribute::where('character_id', $character->id)
            ->where('key', 'gallery_image')
            ->where('id', $validated['attribute_id'])
            ->firstOrFail();

        $oldPrimary = $character->avatar;
        $newPrimary = $attribute->value;

        $character->update(['avatar' => $newPrimary]);

        if (!empty($oldPrimary)) {
            $attribute->update(['value' => $oldPrimary]);
        } else {
            $attribute->delete();
        }

        return response()->json([
            'message'   => lexicon('msg_CharacterAvatar_PrimaryUpdated', 'Primary character portrait updated.'),
            'portraits' => $this->getPortraitPayload($character),
        ]);
    }

    /**
     * Delete either the primary avatar or a secondary gallery image and unlink the file.
     */
    public function deleteCharacterAvatar(Request $request, Character $character)
    {
        $this->authorizeAvatarAction($character);

        $validated = $request->validate([
            'type'         => ['required', 'in:primary,gallery'],
            'attribute_id' => ['nullable', 'integer'],
        ]);

        if ($validated['type'] === 'primary') {
            $filename = $character->avatar;

            if ($filename && !Str::startsWith($filename, ['http://', 'https://'])) {
                $filePath = public_path('images/characters/' . ltrim($filename, '/'));
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }

            // Automatically promote the oldest gallery portrait if one exists
            $nextGalleryAttr = CharacterAttribute::where('character_id', $character->id)
                ->where('key', 'gallery_image')
                ->orderBy('id', 'asc')
                ->first();

            if ($nextGalleryAttr) {
                $character->update(['avatar' => $nextGalleryAttr->value]);
                $nextGalleryAttr->delete();
            } else {
                $character->update(['avatar' => null]);
            }
        } else {
            $attribute = CharacterAttribute::where('character_id', $character->id)
                ->where('key', 'gallery_image')
                ->where('id', $validated['attribute_id'])
                ->firstOrFail();

            $filename = $attribute->value;

            if ($filename && !Str::startsWith($filename, ['http://', 'https://'])) {
                $filePath = public_path('images/characters/' . ltrim($filename, '/'));
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }

            $attribute->delete();
        }

        return response()->json([
            'message'   => lexicon('msg_CharacterAvatar_Deleted', 'Character portrait removed.'),
            'portraits' => $this->getPortraitPayload($character),
        ]);
    }
}
