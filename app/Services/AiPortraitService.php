<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\LineageCatalogue;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class AiPortraitService
{
    protected AiCore $aiCore;

    public function __construct(AiCore $aiCore)
    {
        $this->aiCore =$aiCore;
    }

    /**
     * Inspect active configurations to determine the highest available Visual Engine Tier (1 to 4).
     * Tier 1: Cloud Multimodal ("Direct AI" Mode)
     * Tier 2: Local Multimodal ("Direct Local AI" Mode)
     * Tier 3: Text/Vision LLM ("Smart Canvas Assist" / "AI Stage Director" Mode)
     * Tier 4: AI Disabled ("Pure Canvas Studio" / "Auto-Ensemble" Mode)
     */
    public function detectActiveTier(): int
    {
        if (! $this->aiCore->isEnabled()) {
            return 4;
        }

        $mode = $this->aiCore->getMode();$multimodalFlag = Configuration::where('configuration_token', 'ai_multimodal_enabled')
            ->value('configuration_payload');
        $isMultimodal = in_array(strtolower((string)$multimodalFlag), ['1', 'true', 'yes', 'enabled'], true);

        if ($mode === 'cloud') {$apiKey = Configuration::where('configuration_token', 'ai_api_key')->value('configuration_payload');
            if (empty($apiKey)) {
                return 4;
            }

            return $isMultimodal ? 1 : 3;
        }

        if ($mode === 'local') {
            return $isMultimodal ? 2 : 3;
        }

        return 4;
    }

    /**
     * Build the positive and negative prompt payload or canvas telemetry for the 3-Layer Portrait Forge.
     */
    public function synthesisePortrait(Persona $persona, array$layerOptions = []): array
    {
        $tier =$this->detectActiveTier();
        $lineage =$persona->lineageCatalogue ?? (
            ! empty($layerOptions['lineage_catalogue_id'])
                ? LineageCatalogue::find($layerOptions['lineage_catalogue_id'])
                : null
        );

        $artStyle =$layerOptions['art_style']
            ?? Configuration::where('configuration_token', 'portrait_default_art_style')->value('configuration_payload')
            ?? 'Cinematic Digital Illustration';

        $defaultNegative = Configuration::where('configuration_token', 'portrait_default_negative_prompt')
            ->value('configuration_payload') ?? 'low quality, blurry, deformed anatomy, watermark, text overlay';

        $speciesPositive =$lineage?->visual_trait_prompt ?? '';
        $speciesNegative =$lineage?->negative_visual_trait_prompt ?? '';
        $combinedNegative = trim($defaultNegative . ($speciesNegative !== '' ? ', ' .$speciesNegative : ''));

        $positivePrompt = trim(sprintf(
            "Character portrait of %s in '%s' medium. %s. Attire/Division colour: %s. Background context: %s. STRICT EXCLUSIONS (DO NOT INCLUDE): %s.",
            $persona->persona_name,
            $artStyle,$speciesPositive !== '' ? "Species traits: {$speciesPositive}" : 'Standard anatomical proportions',
            $layerOptions['division_colour'] ?? '#1a1a1aFF',
            $layerOptions['background_prompt'] ?? ($persona->last_logged_location ?? 'Command interior'),
            $combinedNegative
        ));

        if ($tier === 3) {
            return [
                'tier'            => 3,
                'positive_prompt' => $positivePrompt,
                'negative_prompt' => $combinedNegative,
                'telemetry'       => $this->calculateCanvasTelemetry($persona, $lineage,$layerOptions),
            ];
        }

        return [
            'tier'            => $tier,
            'positive_prompt' => $positivePrompt,
            'negative_prompt' => $combinedNegative,
            'telemetry'       => $this->defaultCanvasTelemetry($lineage,$layerOptions),
        ];
    }

    /**
     * Tier 3 Smart Canvas Assist: Request JSON head/neck anchor, lighting, and species overlay coordinates.
     */
    public function calculateCanvasTelemetry(Persona $persona, ?LineageCatalogue $lineage, array$layerOptions = []): array
    {
        $fallback =$this->defaultCanvasTelemetry($lineage,$layerOptions);

        if (! $this->aiCore->isEnabled()) {
            return $fallback;
        }

        $overlays = is_array($lineage?->overlay_assets_json) ? json_encode($lineage->overlay_assets_json) : '[]';$prompt = "Calculate HTML5 Canvas compositing telemetry for persona '{$persona->persona_name}'. "
            . "Background: '" . ($layerOptions['background_prompt'] ?? 'Standard interior') . "'. "
            . "Active Species Overlays: {$overlays}. "
            . "Return ONLY a JSON object with keys: 'head_x' (number), 'head_y' (number), 'head_scale' (0.8..1.2), "
            . "'rotation_deg' (-10..10), 'brightness' (0.8..1.2), 'contrast' (0.8..1.2), and 'warmth_hex' (8-digit hex #RRGGBBAA).";

        try {
            $result = $this->aiCore->generateJson($prompt, ['temperature' => 0.2, 'max_tokens' => 512]);

            return array_merge($fallback,$result);
        } catch (Throwable) {
            return $fallback;
        }
    }

    /**
     * Multi-Subject Folio POV Scene Visualizer: Generate stage blocking coordinates for up to 5 cast members.
     */
    public function generatePovSceneBlocking(string $compartmentTitle, array $visibleCast, string$openingProse = ''): array
    {
        $castSlice = array_slice($visibleCast, 0, 5);$defaultBlocking = [];
        $count = max(1, count($castSlice));

        foreach (array_values($castSlice) as$idx => $castMember) {$personaId = is_array($castMember) ? ($castMember['id'] ?? $idx + 1) : ($castMember->id ?? $idx + 1);$defaultBlocking[] = [
                'persona_id'   => $personaId,
                'x_percent'    => (int) round((($idx + 1) / ($count + 1)) * 100),
                'y_percent'    => 55 + (($idx \% 2) * 5), 'depth_scale'  => 1.0 - (($idx % 3) * 0.08),
                'stance_align' => $idx < ($count / 2) ? 'left' : 'right',
            ];
        }

        if (! $this->aiCore->isEnabled() \vert{}\vert{} empty($castSlice)) {
            return [
                'tier'             => 4,
                'ambient_tint_hex' => '#1e293b33',
                'blocking'         => $defaultBlocking,
            ];
        }

        $castJson = json_encode($castSlice);$excerpt = Str::limit(strip_tags($openingProse), 600, '');$prompt = "Act as an AI Stage Director for a 16:9 POV ensemble scene set in '{$compartmentTitle}'.\n"
            . "Visible Cast (max 5): {$castJson}\n"
            . "Opening Scene Excerpt: {$excerpt}\n"
            . "Return ONLY a JSON object with 'ambient_tint_hex' (8-digit hex #RRGGBBAA matching scene lighting) "
            . "and 'blocking' (array of objects with 'persona_id', 'x_percent' 10..90, 'y_percent' 35..80, 'depth_scale' 0.75..1.15, 'stance_align' left|center|right).";

        try {
            $decoded = $this->aiCore->generateJson($prompt, ['temperature' => 0.3, 'max_tokens' => 768]);

            return [
                'tier'             => $this->detectActiveTier(),
                'ambient_tint_hex' => $decoded['ambient_tint_hex'] ?? '#1e293b33',
                'blocking'         => ! empty($decoded['blocking']) && is_array($decoded['blocking'])
                    ? $decoded['blocking']
                    : $defaultBlocking,
            ];
        } catch (Throwable) {
            return [
                'tier'             => 4,
                'ambient_tint_hex' => '#1e293b33',
                'blocking'         => $defaultBlocking,
            ];
        }
    }

    /**
     * Automated Attribution Fallback & Uncredited Error Pipeline (Gold Standard Section 1.3 & 1.7).
     * Inspects EXIF/IPTC metadata and AI fallback before flagging 'uncredited_error' and alerting webmasters.
     */
    public function resolveMissingAttribution(
        string $imageUri,
        array $credits,
        ?Persona $persona = null,
        ?string $filePath = null
    ): array {
        $resolved = [
            'face'       => trim((string) ($credits['face_credit'] ?? $credits['face'] ?? '')),
            'body'       => trim((string) ($credits['body_credit'] ?? $credits['body'] ?? '')),
            'background' => trim((string) ($credits['background_credit'] ?? $credits['background'] ?? '')),
            'art_style'  => trim((string) ($credits['art_style'] ?? 'Standard Composite')),
            'status'     => $credits['status'] ?? 'user_provided',
        ];

        $missingLayers = [];
        foreach (['face', 'body', 'background'] as $layerKey) {
            if ($resolved[$layerKey] === '') {
                $missingLayers[] =$layerKey;
            }
        }

        if (empty($missingLayers)) {
            $this->syncAttributionManifest($imageUri, $resolved,$persona);

            return $resolved;
        }

        // Step 2A: Inspect local EXIF / IPTC metadata if file exists on disk
        $exifCredit = $this->extractExifOrIptcCredit($filePath);
        if ($exifCredit !== '') {
            foreach ($missingLayers as $layerKey) {$resolved[$layerKey] =$exifCredit . ' (EXIF Metadata)';
            }
            $resolved['status'] = 'ai_verified';
            $this->syncAttributionManifest($imageUri, $resolved,$persona);

            return $resolved;
        }

        // Step 2B: AI Fallback Identification when ai_mode is active
        if ($this->aiCore->isEnabled()) {
            try {
                $filenameHint = basename($filePath ?: $imageUri);
                $personaName =$persona?->persona_name ?? 'Unknown Subject';
                $prompt = "An image asset ('{$filenameHint}') was uploaded for character '{$personaName}' without attribution for layer(s): "
                    . implode(', ', $missingLayers) . ". "
                    . "If the filename or context clearly identifies a known public-domain/franchise template or artist, return a JSON object with 'identified' (true/false) and 'credit' (string). If uncertain, set 'identified' to false.";

                $aiCheck = $this->aiCore->generateJson($prompt, ['temperature' => 0.1, 'max_tokens' => 256]);
                if (! empty($aiCheck['identified']) && ! empty($aiCheck['credit'])) {
                    foreach ($missingLayers as $layerKey) {$resolved[$layerKey] = trim((string)$aiCheck['credit']) . ' (AI Auto-Detected)';
                    }
                    $resolved['status'] = 'ai_verified';
                    $this->syncAttributionManifest($imageUri, $resolved,$persona);

                    return $resolved;
                }
            } catch (Throwable) {
                // Proceed to Step 3 uncredited error flagging
            }
        }

        // Step 3: Flag as UNCREDITED_ERROR and dispatch multi-channel webmaster alert
        $uncreditedLabel = function_exists('lexicon')
            ? lexicon('error_PortraitCredits_Uncredited', 'UNCREDITED_ERROR')
            : 'UNCREDITED_ERROR';

        foreach ($missingLayers as $layerKey) {$resolved[$layerKey] =$uncreditedLabel;
        }
        $resolved['status'] = 'uncredited_error';

        $this->syncAttributionManifest($imageUri, $resolved,$persona);
        $this->dispatchWebmasterUncreditedAlert($imageUri, $missingLayers,$persona);

        return $resolved;
    }

    /**
     * Atomically update storage/app/public/portrait_credits.json with LOCK_EX and bust mows_portrait_credits cache.
     */
    public function syncAttributionManifest(string $imageUri, array $creditData, ?Persona $persona = null): void
    {
        $manifestPath = storage_path('app/public/portrait_credits.json');
        $dir = dirname($manifestPath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ledger = [];
        if (is_file($manifestPath)) {
            $ledger = json_decode((string) file_get_contents($manifestPath), true) ?: [];
        }

        $key = basename($imageUri);
        $ledger[$key] = [
            'uri'          => $imageUri,
            'persona_id'   => $persona?->id ?? ($creditData['persona_id'] ?? null),
            'persona_name' => $persona?->persona_name ?? ($creditData['persona_name'] ?? 'Site Asset'),
            'face'         => $creditData['face'] ?? 'N/A',
            'body'         => $creditData['body'] ?? 'N/A',
            'background'   => $creditData['background'] ?? 'N/A',
            'art_style'    => $creditData['art_style'] ?? 'Standard Composite',
            'status'       => $creditData['status'] ?? 'user_provided',
            'updated_at'   => now()->toIso8601String(),
        ];

        file_put_contents(
            $manifestPath,
            json_encode($ledger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );

        Cache::forget('mows_portrait_credits');
    }

    /**
     * Remove an orphaned entry from portrait_credits.json when a portrait is deleted.
     */
    public function pruneAttributionEntry(string $imageUri): void
    {
        $manifestPath = storage_path('app/public/portrait_credits.json');
        if (! is_file($manifestPath)) {
            return;
        }

        $ledger = json_decode((string) file_get_contents($manifestPath), true) ?: [];
        $key = basename($imageUri);

        if (isset($ledger[$key])) {
            unset($ledger[$key]);
            file_put_contents(
                $manifestPath,
                json_encode($ledger, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                LOCK_EX
            );
            Cache::forget('mows_portrait_credits');
        }
    }

    protected function extractExifOrIptcCredit(?string $filePath): string
    {
        if (empty($filePath) \vert{}\vert{} ! is_file($filePath)) {
            return '';
        }

        try {
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($filePath, 'IFD0');
                if (is_array($exif)) {
                    $artist = trim((string) ($exif['Artist'] ?? $exif['Copyright'] ?? $exif['COMPUTED']['Copyright'] ?? ''));
                    if ($artist !== '') {
                        return $artist;
                    }
                }
            }
        } catch (Throwable) {
            // Ignore unreadable EXIF headers
        }

        return '';
    }

    protected function dispatchWebmasterUncreditedAlert(string $imageUri, array $missingLayers, ?Persona $persona): void
    {
        try {
            $webmasters = User::role('webmaster')->get();
            if ($webmasters->isEmpty()) {
                return;
            }

            $uploaderId = auth()->id() ?? 'Guest/System';
            $personaLabel =$persona ? "{$persona->persona_name} (#{$persona->id})" : 'Standalone Asset';
            $layersList = implode(', ', $missingLayers);$subject = "Uncredited Portrait Asset Flagged: {$personaLabel}";
            $body = "An uploaded portrait asset ('{$imageUri}') for {$personaLabel} by User #{$uploaderId} is missing attribution on layer(s): {$layersList}. It has been flagged as UNCREDITED_ERROR in portrait_credits.json.";

            MultiChannelNotificationService::sendPm(auth()->user(), $webmasters, $body,$subject);

            foreach ($webmasters as$webmaster) {
                MultiChannelNotificationService::sendEmail($webmaster->email,$subject, 'emails.notification', [
                    'title'      => $subject,
                    'body'       => $body,
                    'actionUrl'  => url('/credits'),
                    'actionText' => 'Review Portrait Credits',
                ]);
            }
        } catch (Throwable) {
            // Never block portrait upload if notification dispatch fails
        }
    }

    protected function defaultCanvasTelemetry(?LineageCatalogue $lineage, array$layerOptions = []): array
    {
        return [
            'head_x'           => 0,
            'head_y'           => 0,
            'head_scale'       => 1.0,
            'rotation_deg'     => 0,
            'brightness'       => 1.0,
            'contrast'         => 1.0,
            'warmth_hex'       => $lineage?->default_skin_tint_hex ?? '#FFFFFF00',
            'division_colour'  => $layerOptions['division_colour'] ?? '#1a1a1aFF',
            'species_overlays' => is_array($lineage?->overlay_assets_json) ? $lineage->overlay_assets_json : [],
        ];
    }
}