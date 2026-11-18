<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\Division;
use App\Models\DivisionCatalogue;
use App\Models\EncyclopediaCodex;
use App\Models\InsigniaCatalogue;
use App\Models\LexiconCodex;
use App\Models\LineageCatalogue;
use App\Models\ThematicBinding;
use Exception;
use Illuminate\Support\Facades\File;
use ZipArchive;

class CompendiumPackagingService
{
    /**
     * Directories and files excluded from starter-pack compendium builds to preserve the 30,000 inode limit.
     *
     * @var array<int, string>
     */
    protected array $excludedPaths = [
        '.git',
        '.github',
        '.vscode',
        'node_modules',
        'tests',
        'audit',
        'codebase',
        'storage/logs',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views',
        '.env',
    ];

    /**
     * Export a granular JSON manifest for a specific domain ('divisions', 'insignia', 'lineages', 'conlangs', 'codex', 'schematics', 'credits').
     */
    public function exportDomainManifest(string $domain): array
    {
        return match (strtolower(trim($domain))) {
            'divisions', 'sections' => $this->buildDivisionsManifest(),
            'insignia', 'ranks'     => $this->buildInsigniaManifest(),
            'lineages', 'species'   => $this->buildLineagesManifest(),
            'conlangs', 'dialects'  => $this->buildConlangsManifest(),
            'codex', 'wiki'         => $this->buildCodexManifest(),
            'schematics', 'msd'     => $this->buildSchematicsManifest(),
            'credits'               => $this->buildCreditsManifest(),
            default                 => throw new Exception("Unsupported export manifest domain: {$domain}"),
        };
    }

    /**
     * Compile a complete Starter-Pack Compendium .zip archive and return its absolute path on disk.
     */
    public function buildStarterPackZip(string $presetSlug = 'blank', ?string $skinDirectory = null): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new Exception('PHP ZipArchive extension is required to build starter-pack compendiums.');
        }

        $activeSkinDir = $skinDirectory ?: (
            ThematicBinding::where('is_global_default', true)->value('directory_name') ?? 'default'
        );

        $exportDir = storage_path('app/public/compendiums');
        if (! is_dir($exportDir)) {
            @mkdir($exportDir, 0755, true);
        }

        $zipFilename = sprintf('mows_compendium_%s_%s.zip', strtolower($presetSlug), now()->format('Ymd_His'));
        $zipPath = $exportDir . DIRECTORY_SEPARATOR . $zipFilename;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Unable to create compendium archive at {$zipPath}.");
        }

        // 1. Generate and embed JSON seed manifests inside storage/app/seeds/
        $seedPayloads = [
            'mows_sections_positions_export.json' => $this->buildDivisionsManifest(),
            'mows_ranks_export.json'              => $this->buildInsigniaManifest(),
            'mows_lineages_export.json'           => $this->buildLineagesManifest(),
            'mows_conlangs_export.json'           => $this->buildConlangsManifest(),
            'mows_codex_export.json'              => $this->buildCodexManifest(),
            'schematic_atlases.json'              => $this->buildSchematicsManifest(),
            'portrait_credits.json'               => $this->buildCreditsManifest(),
        ];

        foreach ($seedPayloads as $filename => $payload) {
            $zip->addFromString(
                'storage/app/seeds/' . $filename,
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }

        // 2. Bundle the selected Thematic Binding skin folder (/public/skins/{skin}/)
        $skinFullPath = public_path('skins/' . $activeSkinDir);
        if (is_dir($skinFullPath)) {
            $this->addDirectoryToZip($zip, $skinFullPath, 'public/skins/' . $activeSkinDir);
        }

        // 3. Bundle lineage overlay assets (/public/images/lineages/) if present
        $lineagesImgPath = public_path('images/lineages');
        if (is_dir($lineagesImgPath)) {
            $this->addDirectoryToZip($zip, $lineagesImgPath, 'public/images/lineages');
        }

        // 4. Write root mows_manifest.json
        $rootManifest = [
            'engine'         => 'MOWS Engine',
            'version'        => '1.0.0',
            'preset'         => $presetSlug,
            'directory_name' => $activeSkinDir,
            'built_at'       => now()->toIso8601String(),
            'seeds'          => array_keys($seedPayloads),
        ];

        $zip->addFromString(
            'mows_manifest.json',
            json_encode($rootManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $zip->close();

        return $zipPath;
    }

    protected function buildDivisionsManifest(): array
    {
        return [
            'domain'     => 'divisions_and_assignments',
            'exported_at'=> now()->toIso8601String(),
            'catalogues' => DivisionCatalogue::all()->toArray(),
            'divisions'  => Division::with('assignments')->orderBy('division_sort_order')->get()->toArray(),
        ];
    }

    protected function buildInsigniaManifest(): array
    {
        return [
            'domain'     => 'insignia',
            'exported_at'=> now()->toIso8601String(),
            'catalogues' => InsigniaCatalogue::with('insignia')->get()->toArray(),
        ];
    }

    protected function buildLineagesManifest(): array
    {
        return [
            'domain'     => 'lineage_catalogues',
            'exported_at'=> now()->toIso8601String(),
            'lineages'   => LineageCatalogue::orderBy('lineage_title')->get()->toArray(),
        ];
    }

    protected function buildConlangsManifest(): array
    {
        $profiles = Configuration::where('configuration_token', 'like', 'conlang_profile_%')
            ->pluck('configuration_payload', 'configuration_token')
            ->map(fn ($raw) => is_string($raw) ? (json_decode($raw, true) ?: $raw) : $raw)
            ->toArray();

        $vocabulary = LexiconCodex::where('prefix_group_title', 'like', 'dialect_vocab%')
            ->get()
            ->toArray();

        return [
            'domain'     => 'conlangs_and_dialect_vocab',
            'exported_at'=> now()->toIso8601String(),
            'profiles'   => $profiles,
            'vocabulary' => $vocabulary,
        ];
    }

    protected function buildCodexManifest(): array
    {
        return [
            'domain'     => 'encyclopedia_codex',
            'exported_at'=> now()->toIso8601String(),
            'entries'    => EncyclopediaCodex::orderBy('article_category')->orderBy('article_title')->get()->toArray(),
        ];
    }

    protected function buildSchematicsManifest(): array
    {
        $path = storage_path('app/public/schematic_atlases.json');
        if (! is_file($path)) {
            return [];
        }

        return json_decode((string) file_get_contents($path), true) ?: [];
    }

    protected function buildCreditsManifest(): array
    {
        $path = storage_path('app/public/portrait_credits.json');
        if (! is_file($path)) {
            return [];
        }

        return json_decode((string) file_get_contents($path), true) ?: [];
    }

    protected function addDirectoryToZip(ZipArchive $zip, string $sourceDir, string $zipPrefix): void
    {
        $files = File::allFiles($sourceDir);
        foreach ($files as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $zip->addFile($file->getRealPath(), rtrim($zipPrefix, '/') . '/' . $relativePath);
        }
    }
}