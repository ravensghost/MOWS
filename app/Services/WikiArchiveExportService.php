<?php

namespace App\Services;

use App\Models\EncyclopediaCodex;
use App\Models\LineageCatalogue;
use App\Models\Volume;
use Illuminate\Support\Collection;

class WikiArchiveExportService
{
    /**
     * Convert basic HTML formatting tags into standard MediaWiki Wikitext markup.
     */
    public function htmlToWikitext(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        $text = $html;

        // Headings
        $text = preg_replace('/<h1[^>]*>(.*?)<\/h1>/is', "\n== $1 ==\n", (string) $text);
        $text = preg_replace('/<h2[^>]*>(.*?)<\/h2>/is', "\n=== $1 ===\n", (string) $text);
        $text = preg_replace('/<h3[^>]*>(.*?)<\/h3>/is', "\n==== $1 ====\n", (string) $text);

        // Bold & Italic
        $text = preg_replace('/<(strong|b)\b[^>]*>(.*?)<\/\1>/is', "'''$2'''", (string) $text);
        $text = preg_replace('/<(em|i)\b[^>]*>(.*?)<\/\1>/is', "''$2''", (string) $text);

        // Lists
        $text = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "* $1\n", (string) $text);
        $text = preg_replace('/<\/?(ul|ol)\b[^>]*>/is', "\n", (string) $text);

        // Paragraphs and breaks
        $text = preg_replace('/<br\s*\/?>/i', "\n", (string) $text);
        $text = preg_replace('/<\/p>/i', "\n\n", (string) $text);

        $clean = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $clean));
    }

    /**
     * Export all EncyclopediaCodex and LineageCatalogue entries as a MediaWiki Wikitext bundle.
     */
    public function exportCodexToWikitext(?string $categoryFilter = null): string
    {
        $query = EncyclopediaCodex::orderBy('article_category')->orderBy('article_title');
        if (! empty($categoryFilter)) {
            $query->where('article_category', $categoryFilter);
        }

        $articles = $query->get();
        $blocks = [];

        foreach ($articles as $article) {
            $body = $this->htmlToWikitext($article->article_text);
            $category = $article->article_category ?: 'General';
            $blocks[] = "= {$article->article_title} =\n"
                . "'''Category:''' {$category}\n\n"
                . "{$body}\n\n"
                . "[[Category:{$category}]]\n"
                . "----";
        }

        if (empty($categoryFilter) || strtolower($categoryFilter) === 'lineages') {
            $lineages = LineageCatalogue::where('is_visible', true)->orderBy('lineage_title')->get();
            foreach ($lineages as $lineage) {
                $synopsis = $this->htmlToWikitext($lineage->lineage_synopsis);
                $blocks[] = "= Species: {$lineage->lineage_title} =\n"
                    . "{$synopsis}\n\n"
                    . "[[Category:Species and Lineages]]\n"
                    . "----";
            }
        }

        return implode("\n\n", $blocks);
    }

    /**
     * Export a Volume and its published Folios into a single MediaWiki story archive page.
     */
    public function exportVolumeToWikitext(Volume $volume): string
    {
        $volume->loadMissing(['shelf', 'folios' => function ($q) {
            $q->where('folio_status', 'published')->orderBy('created_at', 'asc');
        }, 'folios.activePersona', 'folios.personae']);

        $shelfTitle = $volume->shelf?->shelf_title ?? 'Campaign Archive';
        $synopsis = $this->htmlToWikitext($volume->volume_synopsis);

        $output = "= {$volume->volume_title} =\n"
            . "'''Shelf:''' {$shelfTitle}\n\n"
            . ($synopsis !== '' ? "''{$synopsis}''\n\n" : '');

        /** @var Collection<int, \App\Models\Folio> $folios */
        $folios = $volume->folios;

        foreach ($folios as $idx => $folio) {
            $chapterNum = $idx + 1;
            $title = $folio->folio_title ?: "Folio #{$folio->id}";
            $location = $folio->scene_location ?: 'Unlisted Location';
            $timeline = $folio->in_universe_date_string ?: $folio->in_universe_date;
            $authorPersona = $folio->activePersona?->persona_name ?? 'Narrator';

            $output .= "== {$chapterNum}. {$title} ==\n"
                . "* '''Location:''' {$location}\n"
                . "* '''Timeline:''' {$timeline}\n"
                . "* '''Lead Persona:''' {$authorPersona}\n\n"
                . $this->htmlToWikitext($folio->folio_text) . "\n\n";
        }

        $output .= "[[Category:Volumes]]\n[[Category:{$shelfTitle}]]";

        return $output;
    }
}