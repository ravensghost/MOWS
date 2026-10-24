<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;
use App\Models\Page;
use App\Models\TimeSystem;
use App\Models\BookStatus;

class SettingController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403, lexicon('msg_Settings_Unauthorised', 'Unauthorised access to Global Settings.'));

        $systemPage = Page::where('slug', 'admin/settings')
            ->where('is_published', true)
            ->with(['layout.blocks', 'widgets' => function ($query) {
                $query->orderBy('page_widget.sort_order');
            }])
            ->firstOrFail();

        $globalSettings = Setting::all()->pluck('value', 'key')->toArray();
        if (!isset($globalSettings['mail_mailer'])) {
            $globalSettings['mail_mailer'] = 'smtp';
        }

        $timeSystems = TimeSystem::all();
        $bookStatuses = BookStatus::orderBy('sort_order')->get();
        $customLanguages = Setting::where('key', 'like', 'lexicon_label_%')
            ->get()
            ->mapWithKeys(function ($setting) {
                $code = str_replace('lexicon_label_', '', $setting->key);
                return [$code => $setting->value];
            })
            ->toArray();

        return view('admin.settings', compact('globalSettings', 'systemPage', 'timeSystems', 'bookStatuses', 'customLanguages'));
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403, lexicon('msg_Settings_Unauthorised', 'Unauthorised access to Global Settings.'));

        $settings = $request->input('settings', []);

        if (empty($settings) && !$request->has('settings')) {
            $settings = $request->except(['_token', '_method']);
        }

        if (isset($settings['mail_mailer']) && !in_array($settings['mail_mailer'], ['smtp', 'phpmail', 'log'], true)) {
            $settings['mail_mailer'] = 'smtp';
        }

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        // Flush cached settings and lexicon so changes take effect immediately
        Cache::forget('global_lexicon_setting');
        Cache::forget('global_settings');
        Cache::forget('active_time_system');
        Cache::forget('mows_lexicon');

        return response()->json([
            'status'  => 'success',
            'success' => true,
            'message' => lexicon('msg_Settings_Updated', 'Global settings updated successfully.')
        ]);
    }
}
