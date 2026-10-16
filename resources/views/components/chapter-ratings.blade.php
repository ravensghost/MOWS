@props(['languageRatings' => [], 'sexRatings' => [], 'violenceRatings' => []])

<x-accordion-panel :title="lexicon('label_WriteChapter_ToggleRatings', 'Toggle Content Ratings (L-S-V)')" :expanded="false">
    <div class="grid grid-cols-1 lg-grid-cols-3 gap-lg">
        <div class="flex flex-col gap-xs">
            <label for="rating_l" class="text-xs font-bold text-warning">{{ lexicon('label_WriteChapter_LanguageRating', 'Language (L) Rating') }}</label>
            <select id="rating_l" name="rating_l"
                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                    x-tooltip="'{{ addslashes(lexicon('tooltip_WriteChapter_LanguageRating', 'Select the language maturity rating for this chapter')) }}'"
                    required>
                <option value="">{{ lexicon('label_WriteChapter_OptSelectRating', '-- Select Rating --') }}</option>
                @foreach($languageRatings as $rating)
                    <option value="{{ $rating->short_rating }}">{{ $rating->long_rating }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-xs">
            <label for="rating_s" class="text-xs font-bold text-warning">{{ lexicon('label_WriteChapter_SexRating', 'Sex (S) Rating') }}</label>
            <select id="rating_s" name="rating_s"
                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                    x-tooltip="'{{ addslashes(lexicon('tooltip_WriteChapter_SexRating', 'Select the sexual content maturity rating for this chapter')) }}'"
                    required>
                <option value="">{{ lexicon('label_WriteChapter_OptSelectRating', '-- Select Rating --') }}</option>
                @foreach($sexRatings as $rating)
                    <option value="{{ $rating->short_rating }}">{{ $rating->long_rating }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-xs">
            <label for="rating_v" class="text-xs font-bold text-warning">{{ lexicon('label_WriteChapter_ViolenceRating', 'Violence (V) Rating') }}</label>
            <select id="rating_v" name="rating_v"
                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent"
                    x-tooltip="'{{ addslashes(lexicon('tooltip_WriteChapter_ViolenceRating', 'Select the violence maturity rating for this chapter')) }}'"
                    required>
                <option value="">{{ lexicon('label_WriteChapter_OptSelectRating', '-- Select Rating --') }}</option>
                @foreach($violenceRatings as $rating)
                    <option value="{{ $rating->short_rating }}">{{ $rating->long_rating }}</option>
                @endforeach
            </select>
        </div>
    </div>
</x-accordion-panel>