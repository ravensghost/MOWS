<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\ContentTier;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AiCore
{
    /**
     * Return the active AI mode ('cloud', 'local', or 'off').
     */
    public function getMode(): string
    {
        $mode = Configuration::where('configuration_token', 'ai_mode')
            ->value('configuration_payload') ?? 'local';

        return strtolower(trim((string) $mode));
    }

    /**
     * Determine whether AI generation is currently enabled.
     */
    public function isEnabled(): bool
    {
        return $this->getMode() !== 'off';
    }

    /**
     * Generate text content using the configured AI provider with database prompt caching.
     */
    public function generate(string $prompt, array $options = []): string
    {
        $aiMode = $this->getMode();

        if ($aiMode === 'off') {
            if (array_key_exists('fallback', $options)) {
                return (string) $options['fallback'];
            }

            throw new Exception('AI generation is currently disabled (ai_mode is set to off).');
        }

        $gameSetting = Configuration::where('configuration_token', 'game_setting')
            ->value('configuration_payload') ?? 'Sci-Fi Frontier Station';
        $loreContext = Configuration::where('configuration_token', 'lore_context')
            ->value('configuration_payload') ?? 'Late 24th Century, Deep Space exploratory and diplomatic hub.';
        $rsvPolicy = $this->resolveRsvSafetyDirective();

        $systemContext = "Setting: {$gameSetting}\nLore Context: {$loreContext}\n{$rsvPolicy}\n\n";
        $fullPrompt = $systemContext . $prompt;

        $cacheKey = 'ai_prompt_' . md5($aiMode . '_' . $fullPrompt . (($options['response_json'] ?? false) ? '_json' : ''));

        return Cache::store('agent_prompt_cache')->remember($cacheKey, 86400, function () use ($aiMode, $fullPrompt, $options) {
            if ($aiMode === 'local') {
                return $this->callLocal($fullPrompt, $options);
            }

            $provider = Configuration::where('configuration_token', 'ai_provider')
                ->value('configuration_payload') ?? 'gemini';
            $model = Configuration::where('configuration_token', 'ai_model')
                ->value('configuration_payload') ?? 'gemini-1.5-pro';
            $apiSecret = Configuration::where('configuration_token', 'ai_api_key')
                ->value('configuration_payload');

            if (empty($apiSecret)) {
                throw new Exception('Cloud AI API token is missing. Please configure it in Global Configurations.');
            }

            return match (strtolower((string) $provider)) {
                'openai'    => $this->callOpenAi($fullPrompt, (string) $model, (string) $apiSecret, $options),
                'anthropic' => $this->callAnthropic($fullPrompt, (string) $model, (string) $apiSecret, $options),
                default     => $this->callGemini($fullPrompt, (string) $model, (string) $apiSecret, $options),
            };
        });
    }

    /**
     * Request a structured JSON response and decode it cleanly into an associative array.
     */
    public function generateJson(string $prompt, array $options = []): array
    {
        $options['response_json'] = true;
        $rawResponse = $this->generate($prompt, $options);

        return $this->cleanAndDecodeJson($rawResponse);
    }

    /**
     * Strip accidental Markdown code fences and decode JSON string into an array.
     */
    public function cleanAndDecodeJson(string $jsonString): array
    {
        $cleanJson = preg_replace('/```json\s*(.*?)\s*```/is', '$1', $jsonString);
        $cleanJson = preg_replace('/```\s*(.*?)\s*```/is', '$1', (string) $cleanJson);
        $decoded = json_decode(trim((string) $cleanJson), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw new Exception('Failed to parse AI response into JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Build the R-S-V content rating safety boundary string from content_tiers / configurations.
     */
    protected function resolveRsvSafetyDirective(): string
    {
        try {
            $rating = function_exists('ai_rating') ? ai_rating() : '2-1-2';

            return "Content Rating Policy (Language-Sex-Violence): {$rating}. Respect these narrative boundaries.";
        } catch (\Throwable) {
            return 'Content Rating Policy: Respect platform narrative boundaries.';
        }
    }

    protected function callLocal(string $prompt, array $options): string
    {
        $endpoint = Configuration::where('configuration_token', 'ai_local_endpoint')
            ->value('configuration_payload') ?? 'http://127.0.0.1:1234/v1';
        $model = Configuration::where('configuration_token', 'ai_local_model')
            ->value('configuration_payload') ?? 'llama-3-8b-instruct';
        $apiSecret = Configuration::where('configuration_token', 'ai_local_api_key')
            ->value('configuration_payload') ?? '';

        $request = Http::timeout(25);
        if (! empty($apiSecret)) {
            $request = $request->withToken((string) $apiSecret);
        }

        $payload = [
            'model'       => $model,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are an advanced creative writing and administrative co-pilot for a roleplay platform. Enforce British English (UK) spelling conventions.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens'  => $options['max_tokens'] ?? 1024,
        ];

        if (! empty($options['response_json'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = $request->post(rtrim((string) $endpoint, '/') . '/chat/completions', $payload);

        if ($response->failed()) {
            throw new Exception('Local AI request failed: ' . $response->body());
        }

        return (string) ($response->json('choices.0.message.content') ?? '');
    }

    protected function callOpenAi(string $prompt, string $model, string $apiSecret, array $options): string
    {
        $payload = [
            'model'       => $model,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are an advanced creative writing and administrative co-pilot for a roleplay platform. Enforce British English (UK) spelling conventions.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens'  => $options['max_tokens'] ?? 1024,
        ];

        if (! empty($options['response_json'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::timeout(30)
            ->withToken($apiSecret)
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if ($response->failed()) {
            throw new Exception('OpenAI API request failed: ' . $response->body());
        }

        return (string) ($response->json('choices.0.message.content') ?? '');
    }

    protected function callAnthropic(string $prompt, string $model, string $apiSecret, array $options): string
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'x-api-key'         => $apiSecret,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => $model,
                'max_tokens' => $options['max_tokens'] ?? 1024,
                'system'     => 'You are an advanced creative writing and administrative co-pilot for a roleplay platform. Enforce British English (UK) spelling conventions.',
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new Exception('Anthropic API request failed: ' . $response->body());
        }

        return (string) ($response->json('content.0.text') ?? '');
    }

    protected function callGemini(string $prompt, string $model, string $apiSecret, array $options): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiSecret}";

        $generationConfig = [
            'temperature'     => $options['temperature'] ?? 0.7,
            'maxOutputTokens' => $options['max_tokens'] ?? 1024,
        ];

        if (! empty($options['response_json'])) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $response = Http::timeout(30)->post($url, [
            'systemInstruction' => [
                'parts' => [
                    ['text' => 'You are an advanced creative writing and administrative co-pilot for a roleplay platform. Enforce British English (UK) spelling conventions.'],
                ],
            ],
            'contents'          => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig'  => $generationConfig,
        ]);

        if ($response->failed()) {
            throw new Exception('Gemini API request failed: ' . $response->body());
        }

        return (string) ($response->json('candidates.0.content.parts.0.text') ?? '');
    }
}