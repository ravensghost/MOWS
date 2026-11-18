<?php

namespace App\Services;

use App\Models\EncyclopediaCodex;
use App\Models\LineageCatalogue;
use App\Models\Persona;
use Throwable;

class AiContentService
{
    protected AiCore $aiCore;

    public function __construct(AiCore $aiCore)
    {
        $this->aiCore =$aiCore;
    }

    /**
     * Generate an in-character NPC action or dialogue snippet for a Folio scene.
     */
    public function generateNpcDialogue(string $sceneContext, string $npcName, array$charactersPresent = []): string
    {
        $charsStr = ! empty($charactersPresent) ? implode(', ', $charactersPresent) : 'General crew';$prompt = "Write a short dialogue snippet or action for NPC '{$npcName}' in the following scene context: '{$sceneContext}'. Characters present: {$charsStr}. Keep the response focused strictly on the NPC's actions and dialogue. Enforce British English spelling.";

        return $this->aiCore->generate($prompt, ['temperature' => 0.8, 'max_tokens' => 512]);
    }

    /**
     * Generate a concise Chronological Register citation summary for a completed Volume.
     */
    public function summariseVolumeReview(string $volumeTitle, string $synopsis, array$folioSummaries): string
    {
        $summariesText = implode("\n- ", $folioSummaries);$prompt = "Provide a brief service record citation summary (under 255 characters) for the successful completion of the volume titled '{$volumeTitle}' ({$synopsis}) based on these folios:\n- {$summariesText}\nEnforce British English spelling.";

        return $this->aiCore->generate($prompt, ['temperature' => 0.5, 'max_tokens' => 150]);
    }

    /**
     * Legacy method name retained for controller compatibility prior to Stage 5 refactor.
     */
    public function summarizeBookReview(string $bookTitle, string $synopsis, array$chapterSummaries): string
    {
        return $this->summariseVolumeReview($bookTitle, $synopsis,$chapterSummaries);
    }

    /**
     * On-demand lore-aware HTML proofreader for the TipTap authoring suite (<x-tt-tb>).
     * Preserves HTML structure, .woff2 font spans, server-signed dice badges, and .mows-lexicon-tag attributes.
     */
    public function proofreadContent(string $htmlContent, array$context = []): string
    {
        if (trim($htmlContent) === '' \vert{}\vert{} !$this->aiCore->isEnabled()) {
            return $htmlContent;
        }

        $loreGlossary = $this->compileLoreGlossary($context);

        $prompt = "Proofread the following roleplay prose for grammar, punctuation, and flow while preserving the author's regional spelling preference and voice.\n"
            . "CRITICAL HTML & LORE PRESERVATION RULES:\n"
            . "1. Do NOT flag or alter canonical proper nouns from this Lore Glossary: {$loreGlossary}\n"
            . "2. Preserve all HTML tags, classes, and attributes EXACTLY as written, including custom font spans (style=\"font-family: ...\"), communication blocks (.folio-transmission-sci-fi, .folio-parchment), and server-signed dice/tarot roll badges.\n"
            . "3. For inline dialect tags (<span class=\"mows-lexicon-tag\" ...>), proofread ONLY the plain inner text without removing or altering data-lexicon-lang, data-lexicon-register, data-lexicon-translated, or data-lexicon-font attributes.\n"
            . "4. Return ONLY the corrected HTML with zero markdown code fences or commentary.\n\n"
            . $htmlContent;

        try {
            return $this->aiCore->generate($prompt, [
                'temperature' => 0.2,
                'max_tokens'  => 2048,
                'fallback'    => $htmlContent,
            ]);
        } catch (Throwable) {
            return $htmlContent;
        }
    }

    /**
     * Gather active Persona names, Lineage titles, Codex titles, and chronological terms for proofreading context.
     */
    protected function compileLoreGlossary(array $context = []): string
    {
        $terms = [];

        try {
            $personaNames = Persona::whereIn('persona_status', ['active', 'npc', 'secondary'])
                ->limit(60)
                ->pluck('persona_name')
                ->toArray();
            $lineageNames = LineageCatalogue::limit(30)->pluck('lineage_title')->toArray();$codexTitles = EncyclopediaCodex::limit(50)->pluck('article_title')->toArray();

            $terms = array_merge($personaNames,$lineageNames, $codexTitles,$context['extra_terms'] ?? []);

            if (function_exists('active_time_system')) {
                $timeSystem = active_time_system();
                if (! empty($timeSystem['engine_title'])) {
                    $terms[] =$timeSystem['engine_title'];
                }
                if (! empty($timeSystem['date_prefix'])) {
                    $terms[] = trim((string)$timeSystem['date_prefix']);
                }
            }
        } catch (Throwable) {
            // Gracefully ignore missing tables during testing or initial setup
        }

        $uniqueTerms = array_values(array_unique(array_filter($terms)));

        return ! empty($uniqueTerms) ? implode(', ', $uniqueTerms) : 'Standard campaign nomenclature';
    }
}