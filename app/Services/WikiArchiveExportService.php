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

            $icStamp = ($folio->ic_day !== null || ! empty($folio->ic_time))
                ? trim('Day ' . ($folio->ic_day ?? 1) . (! empty($folio->ic_time) ? " — {$folio->ic_time}" : ''))
                : '';

            $timeline = $icStamp !== ''
                ? "{$icStamp} ({$folio->in_universe_date})"
                : $folio->in_universe_date;

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