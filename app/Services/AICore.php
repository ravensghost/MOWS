<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Exception;

class AICore
{
    /**
     * Generate text content using the configured AI provider with database prompt caching.
     */
    public function generate(string $prompt, array$options = []): string
    {
        $aiMode = Configuration::where('configuration_token', 'ai_mode')->value('configuration_payload') ?? 'local';
        $gameSetting = Configuration::where('configuration_token', 'game_setting')->value('configuration_payload') ?? 'Sci-Fi Frontier Station';$loreContext = Configuration::where('configuration_token', 'lore_context')->value('configuration_payload') ?? 'Late 24th Century, Deep Space exploratory and diplomatic hub.';

        $systemContext = "Setting: {$gameSetting}\nLore Context: {$loreContext}\n\n";
        $fullPrompt = $systemContext .$prompt;

        // Check database prompt cache (agent_prompt_cache table via custom store or cache driver)
        $cacheKey = 'ai_prompt_' . md5($fullPrompt . ($options['response_json'] ?? false ? '_json' : ''));

        return Cache::store('agent_prompt_cache')->remember($cacheKey, now()->hours(24), function () use ($aiMode, $fullPrompt,$options) {
            if ($aiMode === 'local') {
                return $this->callLocal($fullPrompt,$options);
            }

            $provider = Configuration::where('configuration_token', 'ai_provider')->value('configuration_payload') ?? 'gemini';
            $model = Configuration::where('configuration_token', 'ai_model')->value('configuration_payload') ?? 'gemini-1.5-pro';$apiKey = Configuration::where('configuration_token', 'ai_api_key')->value('configuration_payload');

            if (empty($apiKey)) {
                throw new Exception("Cloud AI API key is missing. Please configure it in Global Settings.");
            }

            return match (strtolower($provider)) {
                'openai'    => $this->callOpenAi($fullPrompt,$model, $apiKey,$options),
                'anthropic' => $this->callAnthropic($fullPrompt,$model, $apiKey,$options),
                default     => $this->callGemini($fullPrompt,$model, $apiKey,$options),
            };
        });
    }

    protected function callLocal(string $prompt, array$options): string
    {
        $endpoint = Configuration::where('configuration_token', 'ai_local_endpoint')->value('configuration_payload') ?? 'http://127.0.0.1:1234/v1';
        $model = Configuration::where('configuration_token', 'ai_local_model')->value('configuration_payload') ?? 'llama-3-8b-instruct';$apiKey = Configuration::where('configuration_token', 'ai_local_api_key')->value('configuration_payload') ?? '';

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
            'temperature' => $options['temperature'] ?? 0.7, 'max_tokens' =>$options['max_tokens'] ?? 1024,
        ];

        if (!