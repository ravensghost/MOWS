<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\ChronologicalEngine;
use App\Models\Configuration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ConfigurationController extends Controller
{
    public function index(): View
    {
        abort_unless(
            auth()->user()?->can('manage_settings'),
            403,
            lexicon('msg_Settings_Unauthorised', 'Unauthorised access to Global Settings.')
        );

        $systemBook = Book::forUrl('/admin/configurations')->publishedWithLayout()->firstOrFail()

        $globalSettings = Configuration::all()->pluck('configuration_payload', 'configuration_token')->toArray();
        if (! isset($globalSettings['mail_mailer'])) {
            $globalSettings['mail_mailer'] = 'smtp';
        }

        $timeSystems = ChronologicalEngine::all();
        $customLanguages = Configuration::where('configuration_token', 'like', 'lexicon_label_%')
            ->get()
            ->mapWithKeys(function (Configuration $setting) {
                $code = str_replace('lexicon_label_', '', $setting->configuration_token);

                return [$code => $setting->configuration_payload];
            })
            ->toArray();

        return view('admin.configurations', compact('globalSettings', 'systemBook', 'timeSystems', 'customLanguages'));
    }

    public function update(Request $request): JsonResponse
    {
        abort_unless(
            auth()->user()?->can('manage_settings'),
            403,
            lexicon('msg_Settings_Unauthorised', 'Unauthorised access to Global Settings.')
        );

        $settings = $request->input('settings', []);

        if (empty($settings) && ! $request->has('settings')) {
            $settings = $request->except(['_token', '_method']);
        }

        if (isset($settings['mail_mailer']) && ! in_array($settings['mail_mailer'], ['smtp', 'phpmail', 'log'], true)) {
            $settings['mail_mailer'] = 'smtp';
        }

        $updatedConfigurations = [];

        foreach ($settings as $token => $value) {
            $updatedConfigurations[$token] = Configuration::updateOrCreate(
                ['configuration_token' => $token],
                ['configuration_payload' => is_array($value) ? json_encode($value) : $value]
            );
        }

        Cache::forget('global_lexicon_setting');
        Cache::forget('global_settings');
        Cache::forget('active_time_system');
        Cache::forget('mows_lexicon');

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Settings_Updated', 'Global settings updated successfully.'),
            'data'     => $updatedConfigurations,
            'redirect' => null,
        ], 200);
    }
}