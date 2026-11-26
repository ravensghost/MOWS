<?php

namespace App\Http\Controllers;

use App\Models\ThematicBinding;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use PDO;
use Spatie\Permission\Models\Role;

class SetupWizardController extends Controller
{
    public function index(): View
    {
        $manifest = $this->readStarterManifest();
        $selectedTheme = (string) ($manifest['active_theme'] ?? 'default');

        $requirements = [
            'php_8_3_plus'    => version_compare(PHP_VERSION, '8.3.0', '>='),
            'pdo_mysql'       => extension_loaded('pdo_mysql'),
            'mbstring'        => extension_loaded('mbstring'),
            'openssl'         => extension_loaded('openssl'),
            'curl'            => extension_loaded('curl'),
            'fileinfo'        => extension_loaded('fileinfo'),
            'gd'              => extension_loaded('gd'),
            'zip'             => extension_loaded('zip'),
            'storage_writable' => is_writable(storage_path()),
            'cache_writable'   => is_writable(base_path('bootstrap/cache')),
        ];

        $availableSkins = collect(File::directories(public_path('skins')))
            ->map(fn (string $dir) => basename($dir))
            ->values()
            ->all();

        return view('setup.wizard', compact('manifest', 'selectedTheme', 'requirements', 'availableSkins'));
    }

    public function configureDatabase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'db_host'     => 'required|string|max:255',
            'db_port'     => 'required|string|max:10',
            'db_database' => 'required|string|max:255',
            'db_username' => 'required|string|max:255',
            'db_password' => 'nullable|string|max:255',
            'app_name'    => 'required|string|max:255',
            'app_url'     => 'required|url|max:255',
        ]);

        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $validated['db_host'],
                $validated['db_port'],
                $validated['db_database']
            );

            new PDO($dsn, $validated['db_username'], (string) ($validated['db_password'] ?? ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'   => 'error',
                'message'  => lexicon('msg_Setup_DbConnectionFailed', 'Database connection failed: ' . $e->getMessage()),
                'data'     => null,
                'redirect' => null,
            ], 422);
        }

        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (! File::exists($envPath) && File::exists($examplePath)) {
            File::copy($examplePath, $envPath);
        }

        $envContent = File::exists($envPath)
            ? (string) File::get($envPath)
            : "APP_NAME=MOWS\nAPP_ENV=production\nAPP_KEY=\nAPP_DEBUG=false\nAPP_URL=http://localhost\nDB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=mows\nDB_USERNAME=root\nDB_PASSWORD=\n";

        $replacements = [
            'APP_NAME'    => '"' . str_replace('"', '', (string) $validated['app_name']) . '"',
            'APP_URL'     => (string) $validated['app_url'],
            'DB_HOST'     => (string) $validated['db_host'],
            'DB_PORT'     => (string) $validated['db_port'],
            'DB_DATABASE' => (string) $validated['db_database'],
            'DB_USERNAME' => (string) $validated['db_username'],
            'DB_PASSWORD' => '"' . str_replace('"', '', (string) ($validated['db_password'] ?? '')) . '"',
        ];

        foreach ($replacements as $key => $val) {
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$val}", $envContent) ?? $envContent;
            } else {
                $envContent .= "\n{$key}={$val}";
            }
        }

        File::put($envPath, $envContent);

        config([
            'app.name'                    => $validated['app_name'],
            'app.url'                     => $validated['app_url'],
            'database.connections.mysql.host'     => $validated['db_host'],
            'database.connections.mysql.port'     => $validated['db_port'],
            'database.connections.mysql.database' => $validated['db_database'],
            'database.connections.mysql.username' => $validated['db_username'],
            'database.connections.mysql.password' => (string) ($validated['db_password'] ?? ''),
        ]);

        DB::purge('mysql');
        DB::reconnect('mysql');

        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Setup_DatabaseReady', 'Database configured, migrated, and seeded successfully.'),
            'data'     => ['app_name' => $validated['app_name']],
            'redirect' => null,
        ], 200);
    }

    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'skin_directory' => 'nullable|string|max:100',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|max:255',
            'password'       => 'required|string|min:8|confirmed',
        ]);

        $manifest = $this->readStarterManifest();
        $skinDir = basename((string) ($validated['skin_directory'] ?? ($manifest['active_theme'] ?? 'default')));

        DB::transaction(function () use ($validated, $skinDir) {
            ThematicBinding::query()->update(['is_global_default' => 0]);

            ThematicBinding::updateOrCreate(
                ['directory_name' => $skinDir],
                [
                    'binding_title'     => ucwords(str_replace(['_', '-'], ' ', $skinDir)),
                    'package_version'   => '1.0',
                    'is_active'         => 1,
                    'is_global_default' => 1,
                ]
            );

            $webmasterRole = Role::firstOrCreate([
                'name'       => 'webmaster',
                'guard_name' => 'web',
            ]);

            $user = User::updateOrCreate(
                ['email' => strtolower(trim((string) $validated['admin_email']))],
                [
                    'name'     => trim((string) $validated['admin_name']),
                    'password' => Hash::make((string) $validated['password']),
                    'role'     => 'webmaster',
                ]
            );

            $user->syncRoles([$webmasterRole]);
        });

        File::ensureDirectoryExists(storage_path('framework'));
        File::put(storage_path('framework/installed.lock'), json_encode([
            'installed_at' => now()->toIso8601String(),
            'skin'         => $skinDir,
        ], JSON_PRETTY_PRINT));

        Cache::flush();

        return response()->json([
            'status'   => 'success',
            'message'  => lexicon('msg_Setup_Complete', 'MOWS Engine installation complete.'),
            'data'     => ['skin' => $skinDir],
            'redirect' => url('/dashboard'),
        ], 200);
    }

    private function readStarterManifest(): array
    {
        $manifestPath = base_path('mows_manifest.json');
        if (! File::exists($manifestPath)) {
            return [];
        }

        return json_decode((string) File::get($manifestPath), true) ?: [];
    }
}