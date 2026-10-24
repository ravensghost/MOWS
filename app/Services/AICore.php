<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Exception;

class AICore
{
    /**
     * Generate text content using the configured AI provider with database prompt caching.
     */
    public function generate(string $prompt, array $options = []): string
    {
        $aiMode = Setting::where('key', 'ai_mode')->value('value') ?? 'local';
        $gameSetting = Setting::where('key', 'game_setting')->value('value') ?? 'Sci-Fi Frontier Station';
        $loreContext = Setting::where('key', 'lore_context')->value('value') ?? 'Late 24th Century, Deep Space exploratory and diplomatic hub.';

        $systemContext = "Setting: {$gameSetting}\nLore Context: {$loreContext}\n\n";
        $fullPrompt = $systemContext . $prompt;

        // Check database prompt cache (agent_prompt_cache table via custom store or cache driver)
        $cacheKey = 'ai_prompt_' . md5($fullPrompt . ($options['response_json'] ?? false ? '_json' : ''));

        return Cache::store('agent_prompt_cache')->remember($cacheKey, now()->hours(24), function () use ($aiMode, $fullPrompt, $options) {
            if ($aiMode === 'local') {
                return $this->callLocal($fullPrompt, $options);
            }

            $provider = Setting::where('key', 'ai_provider')->value('value') ?? 'gemini';
            $model = Setting::where('key', 'ai_model')->value('value') ?? 'gemini-1.5-pro';
            $apiKey = Setting::where('key', 'ai_api_key')->value('value');

            if (empty($apiKey)) {
                throw new Exception("Cloud AI API key is missing. Please configure it in Global Settings.");
            }

            return match (strtolower($provider)) {
                'openai'    => $this->callOpenAi($fullPrompt, $model, $apiKey, $options),
                'anthropic' => $this->callAnthropic($fullPrompt, $model, $apiKey, $options),
                default     => $this->callGemini($fullPrompt, $model, $apiKey, $options),
            };
        });
    }

    protected function callLocal(string $prompt, array $options): string
    {
        $endpoint = Setting::where('key', 'ai_local_endpoint')->value('value') ?? '[http://127.0.0.1:1234/v1](http://127.0.0.1:1234/v1)';
        $model = Setting::where('key', 'ai_local_model')->value('value') ?? 'llama-3-8b-instruct';
        $apiKey = Setting::where('key', 'ai_local_api_key')->value('value') ?? '';

        $request = Http::timeout(25);
        if (!empty($apiKey)) {
            $request->withToken($apiKey);
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an advanced creative writing and administrative co-pilot for a sci-fi roleplay platform. Enforce British English (UK) spelling conventions.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 1024,
        ];

        if (!empty($options['response_json'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = $request->post(rtrim($endpoint, '/') . '/chat/completions', $payload);

        if ($response->failed()) {
            throw new Exception("Local AI Endpoint Error: " . $response->body());
        }

        return trim($response->json('choices.0.message.content') ?? '');
    }

    protected function callGemini(string $prompt, string $model, string $apiKey, array $options): string
    {
        $generationConfig = [
            'temperature' => $options['temperature'] ?? 0.7,
            'maxOutputTokens' => $options['max_tokens'] ?? 1024,
        ];

        if (!empty($options['response_json'])) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $response = Http::timeout(25)->post("[https://generativelanguage.googleapis.com/v1beta/models/](https://generativelanguage.googleapis.com/v1beta/models/){$model}:generateContent?key={$apiKey}", [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => $generationConfig
        ]);

        if ($response->failed()) {
            throw new Exception("Gemini API Error: " . $response->body());
        }

        return trim($response->json('candidates.0.content.parts.0.text') ?? '');
    }

    protected function callOpenAi(string $prompt, string $model, string $apiKey, array $options): string
    {
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an advanced creative writing and administrative co-pilot. Enforce British English (UK) spelling.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 1024,
        ];

        if (!empty($options['response_json'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::timeout(25)->withToken($apiKey)->post('[https://api.openai.com/v1/chat/completions](https://api.openai.com/v1/chat/completions)', $payload);

        if ($response->failed()) {
            throw new Exception("OpenAI API Error: " . $response->body());
        }

        return trim($response->json('choices.0.message.content') ?? '');
    }

    protected function callAnthropic(string $prompt, string $model, string $apiKey, array $options): string
    {
        $response = Http::timeout(25)->withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->post('[https://api.anthropic.com/v1/messages](https://api.anthropic.com/v1/messages)', [
            'model' => $model,
            'max_tokens' => $options['max_tokens'] ?? 1024,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        if ($response->failed()) {
            throw new Exception("Anthropic API Error: " . $response->body());
        }

        return trim($response->json('content.0.text') ?? '');
    }
}
