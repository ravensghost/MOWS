<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use Exception;

class AiTranslationService
{
    /**
     * Translates an array of base terms to the target locale using the configured AI provider.
     */
    public function translate(array $baseTerms, string$targetLocale): array
    {
        $provider = Setting::where('key', 'ai_provider')->value('value') ?? 'gemini';
        $model = Setting::where('key', 'ai_model')->value('value') ?? 'gemini-1.5-pro';$apiKey = Setting::where('key', 'ai_api_key')->value('value');

        if (empty($apiKey)) {
            throw new Exception("AI API key is missing. Please configure it in Global Settings.");
        }

        $prompt =$this->buildPrompt($baseTerms,$targetLocale);

        return match (strtolower($provider)) {
            'openai' => $this->callOpenAi($prompt, $model,$apiKey),
            'anthropic' => $this->callAnthropic($prompt, $model,$apiKey),
            default => $this->callGemini($prompt, $model,$apiKey),
        };
    }

    private function buildPrompt(array $terms, string$locale): string
    {
        $jsonTerms = json_encode($terms, JSON_UNESCAPED_UNICODE);
        return "Translate the values of the following JSON object to the language represented by the ISO code '{$locale}'. Return ONLY a valid JSON object where the keys remain exactly the same and the values are translated. Do not include markdown formatting blocks like ```json.\n\n" . $jsonTerms;
    }

    private function callGemini(string $prompt, string $model, string $apiKey): array
    {
        $response = Http::post("[https://generativelanguage.googleapis.com/v1beta/models/](https://generativelanguage.googleapis.com/v1beta/models/){$model}:generateContent?key={$apiKey}", [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json'
            ]
        ]);

        if ($response->failed()) {
            throw new Exception("Gemini API Error: " . $response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        return $this->cleanAndDecode($text);
    }

    private function callOpenAi(string $prompt, string $model, string $apiKey): array
    {
        $response = Http::withToken($apiKey)->post('[https://api.openai.com/v1/chat/completions](https://api.openai.com/v1/chat/completions)', [
            'model' => $model,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'You are a precise JSON translation API. strictly return valid JSON.'],
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if ($response->failed()) {
            throw new Exception("OpenAI API Error: " . $response->body());
        }

        $text = $response->json('choices.0.message.content');
        return $this->cleanAndDecode($text);
    }

    private function callAnthropic(string $prompt, string $model, string $apiKey): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->post('[https://api.anthropic.com/v1/messages](https://api.anthropic.com/v1/messages)', [
            'model' => $model,
            'max_tokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if ($response->failed()) {
            throw new Exception("Anthropic API Error: " . $response->body());
        }

        $text = $response->json('content.0.text');
        return $this->cleanAndDecode($text);
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