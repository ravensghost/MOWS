<?php

namespace App\Services;

use App\Models\Bulletin;
use App\Models\Folio;
use App\Models\MissionCalendar;
use Illuminate\Support\Facades\DB;

class AiTaskingService
{
    protected AiCore $aiCore;

    public function __construct(AiCore $aiCore)
    {
        $this->aiCore =$aiCore;
    }

    /**
     * Step 1: Construct an in-universe transmission header using bespoke communication templates and world_date().
     */
    public function generateCommsHeader(array $params = []): string
    {
        $templateStyle = strtolower((string) ($params['template_style'] ?? 'sci-fi'));$sender = $params['sender'] ?? 'Sector Command';$recipient = $params['recipient'] ?? 'All Assigned Personnel';$clearance = $params['clearance'] ?? 'LEVEL-3 RESTRICTED';$protocol = $params['protocol'] ?? 'SUBSPACE-ALPHA';$worldDate = function_exists('world_date') ? world_date(now()) : now()->format('M d, Y H:i');

        [$wrapperTag,$wrapperClass, $syntaxMarker] = match ($templateStyle) {
            'fantasy', 'parchment' => ['div', 'folio-parchment', '~*~'],
            'cyberpunk', 'encrypted' => ['blockquote', 'folio-transmission-sci-fi', '>>> [ENCRYPTED] <<<'],
            default => ['blockquote', 'folio-transmission-sci-fi', '=/\='],
        };

        $skeleton = "<{$wrapperTag} class=\"{$wrapperClass}\">"
            . "<p><strong>{$syntaxMarker} OFFICIAL DISPATCH {$syntaxMarker}</strong><br>"
            . "<strong>SENDER:</strong> {$sender}<br>"
            . "<strong>RECIPIENT:</strong> {$recipient}<br>"
            . "<strong>SECURITY CLEARANCE:</strong> {$clearance}<br>"
            . "<strong>ENCRYPTION PROTOCOL:</strong> {$protocol}<br>"
            . "<strong>DATE:</strong> {$worldDate}</p>"
            . "</{$wrapperTag}>";

        if (! $this->aiCore->isEnabled() \vert{}\vert{} empty($params['enhance_with_ai'])) {
            return $skeleton;
        }

        $prompt = "Using this exact HTML communication header block:\n{$skeleton}\n\n"
            . "Add a single 1-sentence atmospheric routing or authentication status line inside the block right below DATE. "
            . "Preserve the wrapper tag, class ('{$wrapperClass}'), and syntax marker ('{$syntaxMarker}') with zero markdown fences. Enforce British English spelling.";

        return $this->aiCore->generate($prompt, [
            'temperature' => 0.4,
            'max_tokens'  => 256,
            'fallback'    => $skeleton,
        ]);
    }

    /**
     * Step 2: Generate ONLY the Operational Brief & Intelligence Report for a target location or faction.
     */
    public function generateOperationalBrief(string $title, string $targetLocation, string$hookHint = ''): string
    {
        $formattedLocation = $this->normaliseCompartmentFormat($targetLocation);

        $prompt = "Generate ONLY an in-universe 'Operational Brief & Intelligence Report' (2 concise HTML paragraphs) for tasking order '{$title}' at target location '{$formattedLocation}'. "
            . (! empty($hookHint) ? "Tactical Context: {$hookHint}. " : '')
            . "Do NOT include rewards or act milestones. Enforce British English spelling.";

        return $this->aiCore->generate($prompt, [
            'temperature' => 0.7,
            'max_tokens'  => 512,
            'fallback'    => "<p><strong>Operational Brief ({$formattedLocation}):</strong> {$hookHint}</p>",
        ]);
    }

    /**
     * Step 3: Generate ONLY the Terms, Compensation & Logistics clauses.
     */
    public function generateTermsAndLogistics(string $title, string $patron = 'Command Authority', string$hint = ''): string
    {
        $prompt = "Generate ONLY the 'Terms, Compensation & Logistics' section (as a concise HTML bulleted list <ul><li>...</li></ul>) for tasking commission '{$title}' issued by '{$patron}'. "
            . (! empty($hint) ? "Parameters: {$hint}. " : '')
            . "Cover logistical support, commendation/resource allocation, and operational contingencies. Enforce British English spelling.";

        return $this->aiCore->generate($prompt, [
            'temperature' => 0.6,
            'max_tokens'  => 384,
            'fallback'    => "<ul><li><strong>Issuing Authority:</strong> {$patron}</li><li><strong>Logistics:</strong> Standard operational allocation.</li></ul>",
        ]);
    }

    /**
     * Step 4: Generate ONLY the Multi-Stage Story Arc Milestones (Acts I through IV).
     */
    public function generateStoryArcMilestones(string $title, string $targetLocation, string$briefSummary = ''): array
    {
        $formattedLocation =$this->normaliseCompartmentFormat($targetLocation);$fallback = [
            'milestones' => [
                ['act' => 'Act I', 'title' => 'Deployment & Briefing', 'objective' => "Assemble at {$formattedLocation} and review initial telemetry."],
                ['act' => 'Act II', 'title' => 'Primary Investigation', 'objective' => 'Engage with the core anomaly or operational contact.'],
                ['act' => 'Act III', 'title' => 'Escalation & Complication', 'objective' => 'Resolve unexpected tactical or diplomatic resistance.'],
                ['act' => 'Act IV', 'title' => 'Resolution & Debrief', 'objective' => 'Secure the objective and log final chronological registers.'],
            ],
        ];

        if (! $this->aiCore->isEnabled()) {
            return $fallback;
        }

        $prompt = "Generate a 4-stage sequential narrative roadmap (Acts I through IV) for the tasking order '{$title}' set at '{$formattedLocation}'. "
            . (! empty($briefSummary) ? "Context: {$briefSummary}. " : '')
            . "Return ONLY a JSON object with key 'milestones' containing 4 objects, each with 'act' ('Act I'..'Act IV'), 'title', and 'objective' (1 sentence in British English).";

        try {
            return $this->aiCore->generateJson($prompt, ['temperature' => 0.6, 'max_tokens' => 768]);
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * One-Click Export: Publish a compiled Tasking Order directly to the Bulletins table.
     */
    public function publishToBulletin(string $title, string $compiledHtml, ?int $authorUserId = null): Bulletin
    {
        $cleanHtml = function_exists('purify_html') ? purify_html($compiledHtml) :$compiledHtml;

        return Bulletin::create([
            'bulletin_title' => $title,
            'bulletin_text'  => $cleanHtml,
            'user_id'        => $authorUserId ?? auth()->id(),
            'published_at'   => now(),
        ]);
    }

    /**
     * One-Click Transition: Seed a new collaborative Folio draft pre-configured with scene_location
     * and optionally log a scheduled entry in mission_calendar.
     */
    public function seedCollaborativeFolio(array $payload): Folio
    {
        return DB::transaction(function () use ($payload) {
            $location = $this->normaliseCompartmentFormat((string) ($payload['scene_location'] ?? 'Deck 01 — Main Bridge'));
            $worldDate = function_exists('world_date') ? world_date(now()) : now()->format('M d, Y');

            $folio = Folio::create([
                'volume_id'               => $payload['volume_id'],
                'user_id'                 => $payload['user_id'] ?? auth()->id(),
                'active_persona_id'       => $payload['active_persona_id'] ?? null,
                'folio_title'             => $payload['folio_title'] ?? 'Tasking Order: Operational Deployment',
                'folio_text'              => function_exists('purify_html')
                    ? purify_html((string) ($payload['folio_text'] ?? ''))
                    : ($payload['folio_text'] ?? ''),
                'folio_status'            => $payload['folio_status'] ?? 'draft',
                'scene_location'          => $location,
                'in_universe_date_string' => $payload['in_universe_date_string'] ?? $worldDate,
                'content_tier_l'          => (int) ($payload['content_tier_l'] ?? 1),
                'content_tier_s'          => (int) ($payload['content_tier_s'] ?? 0),
                'content_tier_v'          => (int) ($payload['content_tier_v'] ?? 1),
            ]);

            if (! empty($payload['log_mission_calendar'])) {
                MissionCalendar::create([
                    'event_title'             => $folio->folio_title,
                    'event_synopsis'          => $payload['event_synopsis'] ?? "Active tasking deployment at {$location}.",
                    'event_category'          => $payload['event_category'] ?? 'mission',
                    'scene_location'          => $location,
                    'in_universe_date_string' => $folio->in_universe_date_string,
                    'starts_at'               => $payload['starts_at'] ?? now(),
                    'ends_at'                 => $payload['ends_at'] ?? now()->addDays(7),
                    'volume_id'               => $folio->volume_id,
                    'folio_id'                => $folio->id,
                    'created_by_user_id'      => $folio->user_id,
                ]);
            }

            return $folio;
        });
    }

    /**
     * Enforce the {Zone/Deck/Level} — {Compartment Name} convention for MSD schematic pin linking.
     */
    public function normaliseCompartmentFormat(string $location): string
    {
        $trimmed = trim($location);
        if ($trimmed === '') {
            return 'Zone 01 — Primary Operations';
        }

        if (str_contains($trimmed, '—')) {
            return $trimmed;
        }

        if (str_contains($trimmed, ' - ')) {
            return str_replace(' - ', ' — ', $trimmed);
        }

        return 'Sector 01 — ' . $trimmed;
    }
}