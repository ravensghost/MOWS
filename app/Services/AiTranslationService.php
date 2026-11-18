<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\LexiconCodex;
use Throwable;

class AiTranslationService
{
    protected AiCore $aiCore;

    public function __construct(AiCore $aiCore)
    {
        $this->aiCore =$aiCore;
    }

    /**
     * Translate an associative array of base terms to the target locale using AiCore.
     */
    public function translate(array $baseTerms, string$targetLocale): array
    {
        $prompt =$this->buildPrompt($baseTerms,$targetLocale);

        return $this->aiCore->generateJson($prompt, [
            'temperature' => 0.3,
            'max_tokens'  => 4096,
        ]);
    }

    /**
     * Translate an inline prose snippet into an in-universe dialect using the 5-part conlang schema
     * and dialect_vocab_* dictionary, falling back to the deterministic cipher when AI is disabled.
     */
    public function translateDialectText(string $plainText, string $locale, ?string $register = null): string
    {
        $cleanText = trim(strip_tags($plainText));
        if ($cleanText === '') {
            return '';
        }

        $normalisedLocale = strtolower(trim($locale));
        $profile =$this->resolveConlangProfile($normalisedLocale);$vocab = $this->resolveDialectVocabulary($normalisedLocale);

        if (! $this->aiCore->isEnabled()) {
            return $this->applyDeterministicCipher($cleanText, $normalisedLocale,$profile, $vocab,$register);
        }

        $vocabJson = json_encode($vocab, JSON_UNESCAPED_UNICODE);
        $profileJson = json_encode($profile, JSON_UNESCAPED_UNICODE);
        $registerClause = ! empty($register) ? "Sociolinguistic Register/Tone: {$register}." : 'Standard register.';

        $prompt = "Translate the following phrase into the custom conlang dialect '{$normalisedLocale}'.\n"
            . "{$registerClause}\n"
            . "Known Vocabulary Dictionary (dialect_vocab_*): {$vocabJson}\n"
            . "5-Part Conlang Grammar & Phonotactic Profile: {$profileJson}\n"
            . "Apply the specified word order (e.g. SOV/VSO/SVO), morphological affixes, and phonotactic rules to coin any unmapped terms.\n"
            . "Return ONLY the translated in-universe string with no quotes or explanation.\n\n"
            . "Source Phrase: {$cleanText}";

        try {
            $translated = trim($this->aiCore->generate($prompt, [
                'temperature' => 0.4,
                'max_tokens'  => 512,
            ]));

            return $translated !== ''
                ? $translated
                : $this->applyDeterministicCipher($cleanText, $normalisedLocale,$profile, $vocab,$register);
        } catch (Throwable) {
            return $this->applyDeterministicCipher($cleanText, $normalisedLocale,$profile, $vocab,$register);
        }
    }

    /**
     * Bake data-lexicon-translated attributes into all <span class="mows-lexicon-tag"> blocks on Folio save.
     */
    public function bakeFolioDialectTags(string $folioHtml): string
    {
        if (! str_contains($folioHtml, 'mows-lexicon-tag')) {
            return $folioHtml;
        }

        return (string) preg_replace_callback(
            '/<span\b([^>]*class=["\'][^"\']*mows-lexicon-tag[^"\']*["\'][^>]*)>(.*?)<\/span>/is',
            function (array $matches) {
                $attrs = $matches[1];
                $innerHtml = $matches[2];

                if (! preg_match('/data-lexicon-lang=["\']([^"\']+)["\']/i', $attrs, $langMatch)) {
                    return $matches[0];
                }

                $locale = $langMatch[1];
                $register = null;
                if (preg_match('/data-lexicon-register=["\']([^"\']+)["\']/i', $attrs, $regMatch)) {
                    $register = $regMatch[1];
                }

                $translated = htmlspecialchars(
                    $this->translateDialectText($innerHtml, $locale, $register),
                    ENT_QUOTES,
                    'UTF-8'
                );

                if (preg_match('/data-lexicon-translated=["\'][^"\']*["\']/i', $attrs)) {
                    $updatedAttrs = preg_replace(
                        '/data-lexicon-translated=["\'][^"\']*["\']/i',
                        'data-lexicon-translated="' . $translated . '"',
                        $attrs
                    );
                } else {
                    $updatedAttrs = rtrim($attrs) . ' data-lexicon-translated="' . $translated . '"';
                }

                return '<span' . $updatedAttrs . '>' . $innerHtml . '</span>';
            },
            $folioHtml
        );
    }

    /**
     * Rule-driven deterministic phonetic and morphological cipher when ai_mode = off.
     */
    public function applyDeterministicCipher(
        string $text,
        string $locale,
        array $profile = [],
        array $vocab = [],
        ?string $register = null
    ): string {
        $words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        $consonants = $profile['phonotactics']['consonants'] ?? ['k', 'v', 'r', 'z', 't', 'h', 'l', 'n', 's', 'm'];
        $vowels = $profile['phonotactics']['vowels'] ?? ['a', 'e', 'i', 'o', 'u'];
        $honorificPrefix = ($register === 'formal' && ! empty($profile['register']['formal_prefix']))
            ? $profile['register']['formal_prefix'] . '-'
            : '';

        $translatedWords = [];

        foreach ($words as $token) {
            if (trim($token) === '') {
                $translatedWords[] = $token;
                continue;
            }

            if (! preg_match('/^(\p{P}*)([\p{L}\p{N}\'-]+)(\p{P}*)$/u', $token, $parts)) {
                $translatedWords[] = $token;
                continue;
            }

            [, $leadPunct, $coreWord, $trailPunct] = $parts;
            $lowerWord = strtolower($coreWord);

            if (isset($vocab[$lowerWord]) && $vocab[$lowerWord] !== '') {
                $mapped = (string) $vocab[$lowerWord];
            } else {
                // Deterministic hash-seeded syllable generator per word + locale
                $hash = crc32($locale . ':' . $lowerWord);
                $syllableCount = max(1, min(3, (int) ceil(strlen($lowerWord) / 3)));
                $coined = '';

                for ($i = 0; $i < $syllableCount; $i++) {
                    $c1 = $consonants[($hash + $i * 3) % count($consonants)];
                    $v1 = $vowels[($hash + $i * 5) % count($vowels)];
                    $c2 = (($i === $syllableCount - 1) && ($hash % 2 === 0))
                        ? $consonants[($hash + $i * 7) % count($consonants)]
                        : '';
                    $coined .= $c1 . $v1 . $c2;
                }

                $mapped = $coined;
            }

            if (ctype_upper(substr($coreWord, 0, 1))) {
                $mapped = ucfirst($mapped);
            }

            $translatedWords[] = $leadPunct . $mapped . $trailPunct;
        }

        // Apply SOV word-order inversion for simple clauses when configured in conlang_profile
        $syntaxOrder = strtoupper((string) ($profile['syntax']['word_order'] ?? 'SVO'));
        if ($syntaxOrder === 'SOV' && count($translatedWords) >= 5) {
            // Swap middle and trailing non-whitespace tokens
            $nonEmptyIndices = array_keys(array_filter($translatedWords, fn ($w) => trim($w) !== ''));
            if (count($nonEmptyIndices) >= 3) {
                $secondIdx = $nonEmptyIndices[1];
                $lastIdx = $nonEmptyIndices[count($nonEmptyIndices) - 1];
                [$translatedWords[$secondIdx], $translatedWords[$lastIdx]] = [$translatedWords[$lastIdx], $translatedWords[$secondIdx]];
            }
        }

        return $honorificPrefix . implode('', $translatedWords);
    }

    /**
     * Load the 5-part conlang grammar profile from configurations.
     */
    protected function resolveConlangProfile(string $locale): array
    {
        try {
            $raw = Configuration::where('configuration_token', 'conlang_profile_' . $locale)
                ->value('configuration_payload');

            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        } catch (Throwable) {
            // Fallback to default profile
        }

        return [
            'syntax'       => ['word_order' => 'SVO', 'modifier_position' => 'before'],
            'morphology'   => ['plural_suffix' => 'i', 'past_prefix' => 'ka'],
            'phonotactics' => [
                'consonants' => ['k', 'v', 'r', 'z', 't', 'h', 'l', 'n', 's', 'm'],
                'vowels'     => ['a', 'e', 'i', 'o', 'u'],
            ],
            'register'     => ['formal_prefix' => 'vel'],
        ];
    }

    /**
     * Load cached dialect_vocab_* root words for the given locale from LexiconCodex.
     */
    protected function resolveDialectVocabulary(string $locale): array
    {
        $dictionary = [];

        try {
            $entries = LexiconCodex::where('prefix_group_title', 'like', 'dialect_vocab%')
                ->get(['semantic_token', 'default_lexicon_string', 'translation_payloads']);

            foreach ($entries as $entry) {
                $payloads = is_array($entry->translation_payloads)
                    ? array_change_key_case($entry->translation_payloads, CASE_LOWER)
                    : [];

                if (! empty($payloads[$locale])) {
                    $englishKey = strtolower(trim((string) $entry->default_lexicon_string));
                    if ($englishKey !== '') {
                        $dictionary[$englishKey] = (string) $payloads[$locale];
                    }
                }
            }
        } catch (Throwable) {
            // Ignore DB errors when unmigrated
        }

        return $dictionary;
    }

    private function buildPrompt(array $terms, string $locale): string
    {
        $jsonTerms = json_encode($terms, JSON_UNESCAPED_UNICODE);

        return "Translate the values of the following JSON object to the language represented by the ISO code '{$locale}'. Return ONLY a valid JSON object where the keys remain exactly the same and the values are translated. Do not include markdown formatting blocks like ```json.\n\n" . $jsonTerms;
    }
}