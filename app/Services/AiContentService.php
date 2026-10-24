<?php

namespace App\Services;

class AiContentService
{
    protected AICore $aiCore;

    public function __construct(AICore $aiCore)
    {
        $this->aiCore = $aiCore;
    }

    public function generateNpcDialogue(string $sceneContext, string $npcName, array $charactersPresent = []): string
    {
        $charsStr = !empty($charactersPresent) ? implode(', ', $charactersPresent) : 'General crew';
        $prompt = "Write a short dialogue snippet or action for NPC '{$npcName}' in the following scene context: '{$sceneContext}'. Characters present: {$charsStr}. Keep the response focused strictly on the NPC's actions and dialogue. Enforce British English spelling.";
        return $this->aiCore->generate($prompt, ['temperature' => 0.8, 'max_tokens' => 512]);
    }

    public function summarizeBookReview(string $bookTitle, string $synopsis, array $chapterSummaries): string
    {
        $summariesText = implode("\n- ", $chapterSummaries);
        $prompt = "Provide a brief service record citation summary (under 255 characters) for the successful completion of the book titled '{$bookTitle}' based on these chapters:\n- {$summariesText}\nEnforce British English spelling.";
        return $this->aiCore->generate($prompt, ['temperature' => 0.5, 'max_tokens' => 150]);
    }
}
