<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Exception;

class AiCore
{
    /**
     * Generate text content using the configured AI provider with database prompt caching.
     */
    public function generate(string $prompt, array $options = []): string
    {
        $aiMode = Configuration::where('configuration_token', 'ai_mode')->pluck('configuration_payload')->first() ?? 'local';
        $gameSetting = Configuration::where('configuration_token', 'game_setting')->pluck('configuration_payload')->first() ?? 'Sci-Fi Frontier Station';
        $loreContext = Configuration::where('configuration_token', 'lore_context')->pluck('configuration_payload')->first() ?? 'Late 24th Century, Deep Space exploratory and diplomatic hub.';

        $systemContext = "Setting: {$gameSetting}\nLore Context: {$loreContext}\n\n";
        $fullPrompt = $systemContext . $prompt;

        $cacheToken = 'ai_prompt_' . md5($fullPrompt . (($options['response_json'] ?? false) ? '_json' : ''));

        return Cache::store('agent_prompt_cache')->remember($cacheToken, now()->addHours(24), function () use ($aiMode, $fullPrompt, $options) {
            if ($aiMode === 'local') {
                return $this->callLocal($fullPrompt, $options);
            }

            $provider = Configuration::where('configuration_token', 'ai_provider')->pluck('configuration_payload')->first() ?? 'gemini';
            $model = Configuration::where('configuration_token', 'ai_model')->pluck('configuration_payload')->first() ?? 'gemini-1.5-pro';
            $apiSecret = Configuration::where('configuration_token', 'ai_api_key')->pluck('configuration_payload')->first();

            if (empty($apiSecret)) {
                throw new Exception('Cloud AI API token is missing. Please configure it in Global Configurations.');
            }

            return match (strtolower($provider)) {
                'openai'    => $this->callOpenAi($fullPrompt, $model, $apiSecret, $options),
                'anthropic' => $this->callAnthropic($fullPrompt, $model, $apiSecret, $options),
                default     => $this->callGemini($fullPrompt, $model, $apiSecret, $options),
            };
        });
    }

    protected function callLocal(string $prompt, array $options): string
    {
        $endpoint = Configuration::where('configuration_token', 'ai_local_endpoint')->pluck('configuration_payload')->first() ?? 'http://127.0.0.1:1234/v1';
        $model = Configuration::where('configuration_token', 'ai_local_model')->pluck('configuration_payload')->first() ?? 'llama-3-8b-instruct';
        $apiSecret = Configuration::where('configuration_token', 'ai_local_api_key')->pluck('configuration_payload')->first() ?? '';

        $request = Http::timeout(25);
        if (!empty($apiSecret)) {
            $request->withToken($apiSecret);
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an advanced creative writing and administrative co-pilot for a sci-fi roleplay platform. Enforce British English (UK) spelling conventions.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens'  => $options['max_tokens'] ?? 1024,
        ];

        if (!empty($options['response_json'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = $request->post(rtrim($endpoint, '/') . '/chat/completions', $payload);

        if ($response->failed()) {
            throw new Exception('Local AI request failed: ' . $response->body());
        }

        return (string) ($response->json('choices.0.message.content') ?? '');
    }

    protected function callOpenAi(string $prompt, string $model, string $apiSecret, array $options): string
    {
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an advanced creative writing and administrative co-pilot for a sci-fi roleplay platform. Enforce British English (UK) spelling conventions.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens'  => $options['max_tokens'] ?? 1024,
        ];

        if (!empty($options['response_json'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = Http::timeout(30)->withToken($apiSecret)
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
                'system'     => 'You are an advanced creative writing and administrative co-pilot for a sci-fi roleplay platform. Enforce British English (UK) spelling conventions.',
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

        if (!empty($options['response_json'])) {
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $response = Http::timeout(30)->post($url, [
            'systemInstruction' => [
                'parts' => [
                    ['text' => 'You are an advanced creative writing and administrative co-pilot for a sci-fi roleplay platform. Enforce British English (UK) spelling conventions.'],
                ],
            ],
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => $generationConfig,
        ]);

        if ($response->failed()) {
            throw new Exception('Gemini API request failed: ' . $response->body());
        }

        return (string) ($response->json('candidates.0.content.parts.0.text') ?? '');
    }
}
