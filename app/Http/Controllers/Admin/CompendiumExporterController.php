<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CompendiumPackagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompendiumExporterController extends Controller
{
    private function authoriseExporterAccess(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (
                $user->role === 'webmaster'
                || (method_exists($user, 'hasRole') && $user->hasRole('webmaster'))
                || $user->can('manage_settings')
            ),
            403,
            lexicon('msg_Compendium_Unauthorised', 'Unauthorised access to Compendium Exporter.')
        );
    }

    public function export(Request $request, CompendiumPackagingService $packager): BinaryFileResponse|JsonResponse|RedirectResponse
    {
        $this->authoriseExporterAccess();

        $validated = $request->validate([
            'preset'         => 'nullable|string|max:100',
            'skin_directory' => 'nullable|string|max:100',
        ]);

        $presetSlug = (string) ($validated['preset'] ?? 'blank');
        $skinDirectory = isset($validated['skin_directory']) && $validated['skin_directory'] !== ''
            ? basename((string) $validated['skin_directory'])
            : null;

        try {
            $zipPath = $packager->buildStarterPackZip($presetSlug, $skinDirectory);

            return response()->download($zipPath)->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status'   => 'error',
                    'message'  => lexicon('msg_Compendium_BuildFailed', 'Failed to compile starter-pack compendium: ' . $e->getMessage()),
                    'data'     => null,
                    'redirect' => null,
                ], 500);
            }

            return back()->with('error', lexicon('msg_Compendium_BuildFailed', 'Failed to compile starter-pack compendium: ' . $e->getMessage()));
        }
    }

    public function manifest(string $domain, CompendiumPackagingService $packager): StreamedResponse|JsonResponse
    {
        $this->authoriseExporterAccess();

        try {
            $payload = $packager->exportDomainManifest($domain);
            $filename = 'mows_' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $domain) . '_manifest_' . now()->format('Y-m-d') . '.json';

            return response()->streamDownload(function () use ($payload) {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }, $filename, [
                'Content-Type' => 'application/json',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'   => 'error',
                'message'  => lexicon('msg_Compendium_ManifestFailed', $e->getMessage()),
                'data'     => null,
                'redirect' => null,
            ], 422);
        }
    }
}