<?php

namespace App\Services;

class AILoreService
{
    protected AICore $aiCore;

    public function __construct(AICore $aiCore)
    {
        $this->aiCore = $aiCore;
    }

    public function generateSectionDescription(string $name, string $hint = ''): string
    {
        $prompt = "Generate a concise, professional, thematic section description for an organisational division named '{$name}'. " . (!empty($hint) ? "Context/Hint: {$hint}." : "") . " Keep it under 2 sentences. Enforce British English spelling.";
        return $this->aiCore->generate($prompt, ['temperature' => 0.7, 'max_tokens' => 256]);
    }

    public function generatePositionDescription(string $sectionName, string $positionName, string $hint = ''): string
    {
        $prompt = "Generate a concise duty description for the position '{$positionName}' within the '{$sectionName}' division. " . (!empty($hint) ? "Context/Hint: {$hint}." : "") . " Keep it under 2 sentences. Enforce British English spelling.";
        return $this->aiCore->generate($prompt, ['temperature' => 0.7, 'max_tokens' => 256]);
    }

    public function generateRankDescription(string $rankName, string $setName = ''): string
    {
        $prompt = "Generate a short roleplay description for the hierarchy rank '{$rankName}'" . (!empty($setName) ? " in rank set '{$setName}'" : "") . ". Keep it concise. Enforce British English spelling.";
        return $this->aiCore->generate($prompt, ['temperature' => 0.7, 'max_tokens' => 256]);
    }
}
