<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Division;
use App\Models\EncyclopediaCodex;
use App\Models\Folio;
use App\Models\Persona;
use App\Models\PublicationState;
use App\Models\Shelf;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NovaLegacyImportService
{
    /**
     * Migrate a structured array dump of legacy Nova 2 tables into MOWS Engine domain models.
     *
     * Supported keys in $novaPayload:
     * - nova_users
     * - nova_depts & nova_positions
     * - nova_characters
     * - nova_missions
     * - nova_posts & nova_personallogs
     * - nova_wiki
     *
     * @return array{users: int, divisions: int, assignments: int, personae: int, volumes: int, folios: int, codex: int}
     */
    public function importFromPayload(array $novaPayload): array
    {
        $counters = [
            'users'       => 0,
            'divisions'   => 0,
            'assignments' => 0,
            'personae'    => 0,
            'volumes'     => 0,
            'folios'      => 0,
            'codex'       => 0,
        ];

        return DB::transaction(function () use ($novaPayload, &$counters) {
            $userMap = [];
            $divisionMap = [];
            $assignmentMap = [];
            $personaMap = [];
            $volumeMap = [];

            // 1. Migrate nova_users -> users
            foreach ($novaPayload['nova_users'] ?? [] as $legacyUser) {
                $email = strtolower(trim((string) ($legacyUser['email'] ?? '')));
                if ($email === '') {
                    continue;
                }

                $legacyId = $legacyUser['userid'] ?? $legacyUser['id'] ?? null;
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'     => trim((string) ($legacyUser['name'] ?? strtok($email, '@'))),
                        'password' => Hash::make(Str::random(24)),
                        'role'     => ! empty($legacyUser['is_sysadmin']) ? 'webmaster' : 'user',
                    ]
                );

                if ($legacyId !== null) {
                    $userMap[$legacyId] = $user->id;
                }
                $counters['users']++;
            }

            // 2. Migrate nova_depts -> divisions & nova_positions -> assignments
            foreach ($novaPayload['nova_depts'] ?? [] as $idx => $dept) {
                $legacyDeptId = $dept['dept_id'] ?? $dept['id'] ?? null;
                $division = Division::firstOrCreate(
                    ['division_title' => trim((string) ($dept['dept_name'] ?? 'Legacy Division'))],
                    [
                        'division_synopsis'   => trim((string) ($dept['dept_desc'] ?? '')),
                        'division_base_colour'=> '#3b82f6FF',
                        'division_sort_order' => (int) ($dept['dept_order'] ?? ($idx + 1)),
                    ]
                );

                if ($legacyDeptId !== null) {
                    $divisionMap[$legacyDeptId] = $division->id;
                }
                $counters['divisions']++;
            }

            foreach ($novaPayload['nova_positions'] ?? [] as $idx => $pos) {
                $legacyPosId = $pos['pos_id'] ?? $pos['id'] ?? null;
                $legacyDeptId = $pos['pos_dept'] ?? null;
                $divisionId = $divisionMap[$legacyDeptId] ?? Division::first()?->id;

                if (! $divisionId) {
                    continue;
                }

                $assignment = Assignment::firstOrCreate(
                    [
                        'division_id'      => $divisionId,
                        'assignment_title' => trim((string) ($pos['pos_name'] ?? 'Crew Assignment')),
                    ],
                    [
                        'assignment_synopsis'   => trim((string) ($pos['pos_desc'] ?? '')),
                        'maximum_occupants'     => max(1, (int) ($pos['pos_open'] ?? 1)),
                        'assignment_sort_order' => (int) ($pos['pos_order'] ?? ($idx + 1)),
                        'is_recruitment_open'   => ((int) ($pos['pos_open'] ?? 1)) > 0,
                    ]
                );

                if ($legacyPosId !== null) {
                    $assignmentMap[$legacyPosId] = $assignment->id;
                }
                $counters['assignments']++;
            }

            // 3. Migrate nova_characters -> personae (mapping PC/SC/NPC tiers)
            foreach ($novaPayload['nova_characters'] ?? [] as $char) {
                $legacyCharId = $char['charid'] ?? $char['id'] ?? null;
                $fullName = trim(
                    ((string) ($char['first_name'] ?? '')) . ' ' . ((string) ($char['last_name'] ?? ''))
                );
                if ($fullName === '') {
                    $fullName = trim((string) ($char['name'] ?? 'Unnamed Persona'));
                }

                $legacyUserId = $char['user'] ?? $char['userid'] ?? null;
                $mappedUserId = $userMap[$legacyUserId] ?? null;
                $rawType = strtolower((string) ($char['crew_type'] ?? 'active'));

                $personaStatus = match ($rawType) {
                    'npc'      => 'npc',
                    'inactive' => 'inactive',
                    default    => $mappedUserId ? 'active' : 'npc',
                };

                $persona = Persona::create([
                    'user_id'        => $mappedUserId,
                    'persona_name'   => $fullName,
                    'assignment_id'  => $assignmentMap[$char['position_1'] ?? ''] ?? null,
                    'persona_status' => $personaStatus,
                ]);

                if ($legacyCharId !== null) {
                    $personaMap[$legacyCharId] = $persona->id;
                }
                $counters['personae']++;
            }

            // 4. Migrate nova_missions -> volumes (under a Legacy Nova Shelf)
            $legacyShelf = Shelf::firstOrCreate(
                ['shelf_title' => 'Imported Nova Archives'],
                ['shelf_synopsis' => 'Migrated mission volumes and personal logs from Nova 2.', 'shelf_sort_order' => 99]
            );
            $activeStateId = PublicationState::where('book_url', 'active')->value('id')
                ?? PublicationState::first()?->id;

            foreach ($novaPayload['nova_missions'] ?? [] as $idx => $mission) {
                $legacyMissionId = $mission['mission_id'] ?? $mission['id'] ?? null;
                $volume = Volume::create([
                    'shelf_id'             => $legacyShelf->id,
                    'publication_state_id' => $activeStateId,
                    'volume_title'         => trim((string) ($mission['mission_title'] ?? 'Imported Mission')),
                    'volume_synopsis'      => trim((string) ($mission['mission_desc'] ?? '')),
                    'volume_sort_order'    => (int) ($mission['mission_order'] ?? ($idx + 1)),
                ]);

                if ($legacyMissionId !== null) {
                    $volumeMap[$legacyMissionId] = $volume->id;
                }
                $counters['volumes']++;
            }

            $defaultVolumeId = Volume::first()?->id ?? Volume::create([
                'shelf_id'             => $legacyShelf->id,
                'publication_state_id' => $activeStateId,
                'volume_title'         => 'General Imported Logs',
                'volume_synopsis'      => 'Imported posts and personal logs.',
                'volume_sort_order'    => 1,
            ])->id;

            // 5. Migrate nova_posts & nova_personallogs -> folios
            $allLegacyPosts = array_merge(
                $novaPayload['nova_posts'] ?? [],
                array_map(function (array $log) {
                    $log['_is_personal_log'] = true;

                    return $log;
                }, $novaPayload['nova_personallogs'] ?? [])
            );

            foreach ($allLegacyPosts as $post) {
                $isPersonalLog = ! empty($post['_is_personal_log']);
                $rawTitle = trim((string) ($post['post_title'] ?? $post['log_title'] ?? 'Imported Entry'));
                $rawBody = (string) ($post['post_content'] ?? $post['log_content'] ?? '');
                $cleanBody = function_exists('purify_html') ? purify_html(nl2br($rawBody)) : nl2br($rawBody);

                $volumeId = $volumeMap[$post['post_mission'] ?? ''] ?? $defaultVolumeId;
                $firstAuthorCharId = explode(',', (string) ($post['post_authors'] ?? $post['log_author_character'] ?? ''))[0] ?? null;
                $activePersonaId = $personaMap[trim((string) $firstAuthorCharId)] ?? null;
                $userId = $userMap[$post['post_author_user'] ?? $post['log_author_user'] ?? ''] ?? auth()->id();

                $folio = Folio::create([
                    'volume_id'               => $volumeId,
                    'user_id'                 => $userId,
                    'active_persona_id'       => $activePersonaId,
                    'folio_title'             => $isPersonalLog ? "[Personal Log] {$rawTitle}" : $rawTitle,
                    'folio_text'              => $cleanBody,
                    'folio_status'            => (($post['post_status'] ?? $post['log_status'] ?? 'activated') === 'activated')
                        ? 'published'
                        : 'draft',
                    'scene_location'          => trim((string) ($post['post_location'] ?? 'Archived Sector')),
                    'in_universe_date_string' => trim((string) ($post['post_timeline'] ?? '')),
                ]);

                // Attach co-author personae to folio_personae pivot
                $authorIds = array_filter(array_map('trim', explode(',', (string) ($post['post_authors'] ?? ''))));
                $mappedPersonaIds = [];
                foreach ($authorIds as $legacyAuthorCharId) {
                    if (isset($personaMap[$legacyAuthorCharId])) {
                        $mappedPersonaIds[] = $personaMap[$legacyAuthorCharId];
                    }
                }
                if (! empty($mappedPersonaIds)) {
                    $folio->personae()->syncWithoutDetaching($mappedPersonaIds);
                }

                $counters['folios']++;
            }

            // 6. Migrate nova_wiki -> encyclopedia_codex
            foreach ($novaPayload['nova_wiki'] ?? [] as $wikiPage) {
                $title = trim((string) ($wikiPage['page_title'] ?? ''));
                if ($title === '') {
                    continue;
                }

                $rawContent = (string) ($wikiPage['page_content'] ?? '');
                EncyclopediaCodex::firstOrCreate(
                    ['article_url' => Str::slug($title)],
                    [
                        'article_title'      => $title,
                        'article_category'   => trim((string) ($wikiPage['page_category'] ?? 'Imported Lore')),
                        'article_text'       => function_exists('purify_html') ? purify_html($rawContent) : $rawContent,
                        'created_by_user_id' => auth()->id(),
                    ]
                );
                $counters['codex']++;
            }

            return $counters;
        });
    }
}