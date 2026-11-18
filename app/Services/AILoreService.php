<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiLoreService
{
    protected AiCore $aiCore;

    public function __construct(AiCore $aiCore)
    {
        $this->aiCore =$aiCore;
    }

    /**
     * Generate a concise thematic description for an organisational Division.
     */
    public function generateDivisionDescription(string $name, string$hint = ''): string
    {
        $prompt = "Generate a concise, professional, thematic division description for an organisational division named '{$name}'. "
            . (! empty($hint) ? "Context/Hint: {$hint}. " : '')
            . 'Keep it under 2 sentences. Enforce British English spelling.';

        return $this->aiCore->generate($prompt, ['temperature' => 0.7, 'max_tokens' => 256]);
    }

    /**
     * Legacy alias for generateDivisionDescription.
     */
    public function generateSectionDescription(string $name, string$hint = ''): string
    {
        return $this->generateDivisionDescription($name,$hint);
    }

    /**
     * Generate a concise duty description for an Assignment within a Division.
     */
    public function generateAssignmentDescription(string $divisionName, string $assignmentName, string$hint = ''): string
    {
        $prompt = "Generate a concise duty description for the assignment '{$assignmentName}' within the '{$divisionName}' division. "
            . (! empty($hint) ? "Context/Hint: {$hint}. " : '')
            . 'Keep it under 2 sentences. Enforce British English spelling.';

        return $this->aiCore->generate($prompt, ['temperature' => 0.7, 'max_tokens' => 256]);
    }

    /**
     * Legacy alias for generateAssignmentDescription.
     */
    public function generatePositionDescription(string $sectionName, string $positionName, string$hint = ''): string
    {
        return $this->generateAssignmentDescription($sectionName, $positionName,$hint);
    }

    /**
     * Generate a short roleplay description for an Insignia rank entry.
     */
    public function generateInsigniaDescription(string $insigniaName, string$catalogueName = ''): string
    {
        $prompt = "Generate a short roleplay description for the hierarchy insignia '{$insigniaName}'"
            . (! empty($catalogueName) ? " in insignia catalogue '{$catalogueName}'" : '')
            . '. Keep it concise. Enforce British English spelling.';

        return $this->aiCore->generate($prompt, ['temperature' => 0.7, 'max_tokens' => 256]);
    }

    /**
     * Legacy alias for generateInsigniaDescription.
     */
    public function generateRankDescription(string $rankName, string$setName = ''): string
    {
        return $this->generateInsigniaDescription($rankName,$setName);
    }

    /**
     * Step 1: Scrape and sanitise external reference URLs into clean plain-text/Markdown extracts.
     *
     * @param  array<int, string>  $urls
     * @return array<int, array{url: string, title: string, excerpt: string}>
     */
    public function scrapeExternalUrls(array $urls): array
    {
        $extracts = [];

        foreach ($urls as$url) {
            $cleanUrl = trim((string)$url);
            if (! filter_var($cleanUrl, FILTER_VALIDATE_URL)) {
                continue;
            }

            try {
                $response = Http::timeout(15) ->withUserAgent('MOWS-Lore-Ingestor/1.0') ->get($cleanUrl);

                if ($response->failed()) {
                    continue;
                }

                $html =$response->body();
                preg_match('/<title[^>]*>(.*?)<\/title>/is', $html,$titleMatch);
                $title = isset($titleMatch[1]) ? trim( html_entity_decode(strip_tags($titleMatch[1])) ) :$cleanUrl;

                // Strip scripts, styles, nav, footer, and boilerplate tags
                $sanitised = preg_replace('/<(script\vert{}style\vert{}nav\vert{}footer\vert{}header\vert{}aside\vert{}noscript)\b[^>]*>.*?<\/\1>/is', ' ',$html);
                $plainText = preg_replace('/\s+/', ' ', trim(html_entity_decode(strip_tags((string)$sanitised))));

                $extracts[] = [
                    'url'     => $cleanUrl,
                    'title'   => $title,
                    'excerpt' => Str::limit((string) $plainText, 4000, '...'),
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        return $extracts;
    }

    /**
     * Step 2: Chunk an uploaded manual or document stored in storage/app/public/manuals/.
     *
     * @return array<int, array{chunk_index: int, content: string}>
     */
    public function chunkManualFile(string $filename, int$chunkSize = 3500): array
    {
        $relativePath = 'manuals/' . ltrim($filename, '/');$disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            throw new Exception("Manual file '{$filename}' was not found in storage/app/public/manuals/.");
        }

        $rawContent = (string) $disk->get($relativePath);
        $cleanText = trim(preg_replace('/\s+/', ' ', strip_tags($rawContent)));

        if ($cleanText === '') {
            return [];
        }

        $chunks = str_split($cleanText, max(500, $chunkSize));$result = [];

        foreach ($chunks as$idx => $chunk) {$result[] = [
                'chunk_index' => $idx + 1, 'content'     =>$chunk,
            ];
        }

        return $result;
    }

    /**
     * Step 3: Generate structured Codex or Lineage Catalogue seed payloads from grounding chunks.
     * Supports 'atlas' ({Zone/Deck/Level} — {Compartment Name}), 'technology', and 'lineage'.
     */
    public function seedCodexEntries(string $category, string $groundingText, array$options = []): array
    {
        $normalisedCategory = strtolower(trim($category));

        if ($normalisedCategory === 'lineage' || $normalisedCategory === 'lineages') {$prompt = "Based on the following grounding lore:\n{$groundingText}\n\n"
                . "Return a JSON object with key 'entries' containing an array of species/lineage objects. Each object MUST include:\n"
                . "- 'lineage_title': Species or lineage name\n"
                . "- 'lineage_synopsis': Concise lore summary in British English\n"
                . "- 'visual_trait_prompt': Detailed anatomical and visual traits for portrait synthesis\n"
                . "- 'negative_visual_trait_prompt': Strict anatomical exclusions to prevent AI trope bleed\n"
                . "- 'overlay_assets_json': Array of optional prosthetic/feature reference tags.";

            return $this->aiCore->generateJson($prompt, ['temperature' => 0.4, 'max_tokens' => 2048]);
        }

        if ($normalisedCategory === 'atlas') {$prompt = "Based on the following grounding lore:\n{$groundingText}\n\n"
                . "Return a JSON object with key 'entries' containing an array of Atlas location articles. "
                . "Every 'article_title' MUST strictly follow the hierarchical format '{Zone/Deck/Level} — {Compartment Name}' (e.g., 'Deck 01 — Main Bridge' or 'Citadel Tier 2 — Great Hall'). "
                . "Each item must include 'article_title', 'article_category' ('Atlas'), and 'article_text' (rich HTML paragraph description in British English).";

            return $this->aiCore->generateJson($prompt, ['temperature' => 0.5, 'max_tokens' => 2048]);
        }

        $targetCategory = $options['article_category'] ?? 'Technology';$prompt = "Based on the following grounding lore:\n{$groundingText}\n\n"
            . "Return a JSON object with key 'entries' containing an array of Encyclopedia Codex articles for category '{$targetCategory}'. "
            . "Each item must include 'article_title', 'article_category' ('{$targetCategory}'), and 'article_text' (concise British English HTML lore entry).";

        return $this->aiCore->generateJson($prompt, ['temperature' => 0.5, 'max_tokens' => 2048]);
    }

    /**
     * Auto-plot Master Systems Display (MSD) cutaway map pin coordinates ([x_percent, y_percent]) for Atlas compartments.
     */
    public function autoPlotMsdPins(array $compartments, string$hullLayoutHint = 'horizontal starship cutaway'): array
    {
        if (empty($compartments)) {
            return [];
        }

        $list = implode("\n- ", $compartments);$prompt = "Given a {$hullLayoutHint} schematic (where x_percent 0..100 runs left-to-right and y_percent 0..100 runs top-to-bottom), assign logical pin coordinates for these compartments:\n- {$list}\n\n"
            . "Return ONLY a JSON object with key 'pins' containing an array of objects with 'label', 'x_percent' (number 5..95), and 'y_percent' (number 5..95).";

        return $this->aiCore->generateJson($prompt, ['temperature' => 0.2, 'max_tokens' => 1024]);
    }
}