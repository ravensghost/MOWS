<?php

namespace App\Services;

use Exception;

class AiTranslationService
{
    protected AICore $aiCore;

    public function __construct(AICore $aiCore)
    {
        $this->aiCore = $aiCore;
    }

    /**
     * Translates an array of base terms to the target locale using AICore.
     */
    public function translate(array $baseTerms, string $targetLocale): array
    {
        $prompt = $this->buildPrompt($baseTerms, $targetLocale);

        // Request JSON structured response mode via AICore
        $responseJson = $this->aiCore->generate($prompt, [
            'temperature'   => 0.3,
            'response_json' => true,
            'max_tokens'    => 4096,
        ]);

        return $this->cleanAndDecode($responseJson);
    }

    private function buildPrompt(array $terms, string $locale): string
    {
        $jsonTerms = json_encode($terms, JSON_UNESCAPED_UNICODE);
        return "Translate the values of the following JSON object to the language represented by the ISO code '{$locale}'. Return ONLY a valid JSON object where the keys remain exactly the same and the values are translated. Do not include markdown formatting blocks like ```json.\n\n" . $jsonTerms;
    }

    private function cleanAndDecode(string $jsonString): array
    {
        // Strip out markdown code blocks if the AI includes them despite instructions
        $cleanJson = preg_replace('/```json\s*(.*?)\s*```/s', '$1', $jsonString);
        $cleanJson = preg_replace('/```\s*(.*?)\s*```/s', '$1', $cleanJson);

        $decoded = json_decode(trim($cleanJson), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Failed to parse AI response into JSON. " . json_last_error_msg());
        }

        return $decoded;
    }
}
