<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class AuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit 
                            {table? : The specific target or legacy table name to inspect (e.g. configurations, candidate_petitions, personae)} 
                            {--bundle : Export a compact AI refactor bundle (audit_bundle.txt) for the specified table}
                            {--all-details : Show detailed column diffs for every table in the overview}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audits live MySQL schema, Eloquent Models, Controllers, and Blade views against the MOWS Phase 7 Master Blueprint.';

    /**
     * Master Blueprint mapping derived from the MOWS Engine Architecture Specification.
     * Enforces British English (UK) conventions, Digital Library nomenclature, and _mode suffixes.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function getBlueprint(): array
    {
        return [
            'candidate_petitions' => [
                'legacy_table' => 'applications',
                'target_model' => 'App\Models\CandidatePetition',
                'legacy_model' => 'App\Models\Application',
                'columns' => [
                    'id' => 'id',
                    'user_id' => 'user_id',
                    'persona_id' => 'character_id',
                    'proposed_persona_name' => 'character_name',
                    'petition_status' => 'status',
                    'character_limit_enforced' => 'character_limit_enforced',
                    'lodged_at' => 'submitted_at',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['persona', 'user', 'answers', 'votes'],
                'controllers' => [
                    'app/Http/Controllers/CandidatePetitionSubmissionController.php',
                    'app/Http/Controllers/PublicFormController.php',
                    'app/Http/Controllers/Admin/CandidatePetitionController.php',
                    'app/Http/Controllers/Admin/ApplicationController.php',
                    'app/Http/Controllers/Admin/CommandCentreController.php',
                    'app/Http/Controllers/Admin/DashboardController.php',
                ],
                'views' => [
                    'resources/views/petition_metadata.blade.php',
                    'resources/views/form.blade.php',
                    'resources/views/admin/candidate_petitions.blade.php',
                    'resources/views/admin/applications.blade.php',
                    'resources/views/widgets/persona_metadata_summary.blade.php',
                    'resources/views/widgets/character_demographics.blade.php',
                    'resources/views/widgets/character_personality.blade.php',
                ],
                'notes' => [
                    'Ensure character_limit_enforced is present on candidate_petitions.',
                    'Ensure candidate_petitions uses approval_review_mode from configurations.',
                    'Verify DB facade import in CandidatePetitionController::reject() for hard_purge transactions.',
                ],
            ],

            'petition_entries' => [
                'legacy_table' => 'application_answers',
                'target_model' => 'App\Models\PetitionEntry',
                'legacy_model' => 'App\Models\ApplicationAnswer',
                'columns' => [
                    'id' => 'id',
                    'candidate_petition_id' => 'application_id',
                    'metadata_id' => 'form_field_id',
                    'petition_answer' => 'answer',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['petition', 'application', 'metadata', 'formField'],
                'controllers' => [
                    'app/Http/Controllers/CandidatePetitionSubmissionController.php',
                    'app/Http/Controllers/Admin/CandidatePetitionController.php',
                ],
                'views' => [
                    'resources/views/admin/candidate_petitions.blade.php',
                    'resources/views/widgets/persona_metadata_summary.blade.php',
                ],
                'notes' => [],
            ],

            'council_evaluations' => [
                'legacy_table' => 'application_votes',
                'target_model' => 'App\Models\CouncilEvaluation',
                'legacy_model' => 'App\Models\ApplicationVote',
                'columns' => [
                    'id' => 'id',
                    'candidate_petition_id' => 'application_id',
                    'subject_user_id' => 'target_user_id',
                    'evaluating_user_id' => 'council_member_id',
                    'evaluation_decision' => 'vote',
                    'evaluation_commentary' => 'comments',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['targetUser', 'councilMember', 'application'],
                'controllers' => [
                    'app/Http/Controllers/Admin/CouncilEvaluationController.php',
                    'app/Http/Controllers/Admin/ApplicationVoteController.php',
                ],
                'views' => [
                    'resources/views/admin/candidate_petitions.blade.php',
                ],
                'notes' => [],
            ],

            'distinctions' => [
                'legacy_table' => 'awards',
                'target_model' => 'App\Models\Distinction',
                'legacy_model' => 'App\Models\Award',
                'columns' => [
                    'id' => 'id',
                    'distinction_catalogue_id' => 'award_category_id',
                    'distinction_title' => 'name',
                    'scope_target' => 'master_group',
                    'distinction_description' => 'description',
                    'distinction_base_colour' => 'base_color',
                    'mid_layer_mode' => 'mid_type',
                    'mid_layer_asset' => 'mid_image',
                    'mid_layer_modifier' => 'mid_css',
                    'top_layer_mode' => 'top_type',
                    'top_layer_asset' => 'top_image',
                    'top_layer_modifier' => 'top_css',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['serviceRecords', 'category'],
                'controllers' => [
                    'app/Http/Controllers/Admin/DistinctionController.php',
                    'app/Http/Controllers/Admin/AwardController.php',
                    'app/Http/Controllers/Admin/ChronologicalRegisterController.php',
                ],
                'views' => [
                    'resources/views/admin/distinctions.blade.php',
                    'resources/views/admin/awards.blade.php',
                    'resources/views/components/distinction-badge.blade.php',
                    'resources/views/components/award-badge.blade.php',
                    'resources/views/widgets/distinctions.blade.php',
                    'resources/views/widgets/awards.blade.php',
                ],
                'notes' => [
                    'Check singular vs plural column naming in live DB (distinction_title vs distinctions_title, distinction_catalogue_id vs distinctions_catalogue_id, distinction_base_colour vs distinction_base_color).',
                ],
            ],

            'distinction_catalogues' => [
                'legacy_table' => 'award_categories',
                'target_model' => 'App\Models\DistinctionCatalogue',
                'legacy_model' => 'App\Models\AwardCategory',
                'columns' => [
                    'id' => 'id',
                    'catalogue_title' => 'name',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['awards', 'distinctions'],
                'controllers' => [
                    'app/Http/Controllers/Admin/DistinctionCatalogueController.php',
                    'app/Http/Controllers/Admin/AwardCategoryController.php',
                ],
                'views' => [
                    'resources/views/admin/distinctions.blade.php',
                ],
                'notes' => [],
            ],

            'shelves' => [
                'legacy_table' => 'bookcases',
                'target_model' => 'App\Models\Shelf',
                'legacy_model' => 'App\Models\Bookcase',
                'columns' => [
                    'id' => 'id',
                    'shelf_title' => 'name',
                    'shelf_sort_order' => 'sort_order',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['books', 'volumes'],
                'controllers' => [
                    'app/Http/Controllers/Admin/ShelfController.php',
                    'app/Http/Controllers/Admin/BookcaseController.php',
                    'app/Http/Controllers/Admin/PublicationController.php',
                ],
                'views' => [
                    'resources/views/admin/publication.blade.php',
                    'resources/views/admin/story.blade.php',
                ],
                'notes' => [],
            ],

            'volumes' => [
                'legacy_table' => 'books',
                'target_model' => 'App\Models\Volume',
                'legacy_model' => 'App\Models\Book',
                'columns' => [
                    'id' => 'id',
                    'shelf_id' => 'bookcase_id',
                    'publication_state_id' => 'status_id',
                    'volume_title' => 'title',
                    'volume_synopsis' => 'description',
                    'ic_day' => 'ic_day',
                    'ic_time' => 'ic_time',
                    'volume_sort_order' => 'sort_order',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['bookcase', 'shelf', 'chapters', 'folios', 'status'],
                'controllers' => [
                    'app/Http/Controllers/Admin/VolumeController.php',
                    'app/Http/Controllers/Admin/BookController.php',
                    'app/Http/Controllers/Admin/VolumeReviewController.php',
                    'app/Http/Controllers/Admin/PublicationController.php',
                    'app/Http/Controllers/FolioAuthoringController.php',
                ],
                'views' => [
                    'resources/views/admin/publication.blade.php',
                    'resources/views/admin/volume_reviews.blade.php',
                    'resources/views/components/volume-panel.blade.php',
                    'resources/views/components/story-book-panel.blade.php',
                ],
                'notes' => [
                    'Note table collision: legacy "books" became "volumes", while legacy "pages" became "books". Verify live DB "books" vs "volumes" columns carefully.',
                ],
            ],

            'publication_states' => [
                'legacy_table' => 'book_statuses',
                'target_model' => 'App\Models\PublicationState',
                'legacy_model' => 'App\Models\BookStatus',
                'columns' => [
                    'id' => 'id',
                    'state_title' => 'name',
                    'book_url' => 'slug',
                    'state_sort_order' => 'sort_order',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['books', 'volumes'],
                'controllers' => [
                    'app/Http/Controllers/Admin/PublicationController.php',
                    'app/Http/Controllers/Admin/ConfigurationController.php',
                ],
                'views' => [
                    'resources/views/admin/publication.blade.php',
                    'resources/views/admin/configurations.blade.php',
                ],
                'notes' => [
                    'Verify missing storeStatus() method on PublicationController (formerly StoryController).',
                ],
            ],

            'folios' => [
                'legacy_table' => 'chapters',
                'target_model' => 'App\Models\Folio',
                'legacy_model' => 'App\Models\Chapter',
                'columns' => [
                    'id' => 'id',
                    'volume_id' => 'book_id',
                    'user_id' => 'author_user_id',
                    'active_persona_id' => 'active_character_id',
                    'turn_user_id' => 'turn_user_id',
                    'folio_mode' => ['chapter_type', 'folio_type'],
                    'ic_day' => 'ic_day',
                    'ic_time' => 'ic_time',
                    'scene_location' => 'location',
                    'content_tier_l' => 'rating_l',
                    'content_tier_s' => 'rating_s',
                    'content_tier_v' => 'rating_v',
                    'folio_text' => 'content',
                    'folio_status' => 'status',
                    'folio_sort_order' => 'sort_order',
                    'locked_by_user_id' => 'locked_by_user_id',
                    'locked_at' => 'locked_at',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['book', 'volume', 'author', 'turnUser', 'lockedBy', 'activeCharacter', 'characters', 'versions', 'contributions'],
                'controllers' => [
                    'app/Http/Controllers/FolioAuthoringController.php',
                    'app/Http/Controllers/WriteController.php',
                    'app/Http/Controllers/Admin/FolioController.php',
                    'app/Http/Controllers/Admin/PublicationController.php',
                    'app/Http/Controllers/PortalController.php',
                ],
                'views' => [
                    'resources/views/components/folio-modal.blade.php',
                    'resources/views/components/chapter-modal.blade.php',
                    'resources/views/widgets/folio.blade.php',
                    'resources/views/widgets/posts.blade.php',
                    'resources/views/widgets/user_folio_drafts.blade.php',
                    'resources/views/emails/folio_published.blade.php',
                    'resources/views/emails/folio_lock_ping.blade.php',
                ],
                'notes' => [
                    'Consolidate scene-splitting logic from PublicationController::storeChapter into FolioAuthoringController.',
                    'Verify VolumeReviewController relationship names (charactersPresent vs characters, authorUser vs author).',
                ],
            ],

            'folio_personae' => [
                'legacy_table' => 'chapter_character',
                'target_model' => null,
                'legacy_model' => null,
                'columns' => [
                    'id' => 'id',
                    'folio_id' => 'chapter_id',
                    'persona_id' => 'character_id',
                ],
                'relationships' => [],
                'controllers' => [
                    'app/Http/Controllers/FolioAuthoringController.php',
                ],
                'views' => [],
                'notes' => ['Pivot table between folios and personae.'],
            ],

            'author_ledgers' => [
                'legacy_table' => 'chapter_user_contributions',
                'target_model' => 'App\Models\AuthorLedger',
                'legacy_model' => 'App\Models\ChapterUserContribution',
                'columns' => [
                    'id' => 'id',
                    'folio_id' => 'chapter_id',
                    'user_id' => 'user_id',
                    'contribution_word_count' => 'word_count',
                ],
                'relationships' => ['chapter', 'folio', 'user'],
                'controllers' => [
                    'app/Http/Controllers/FolioAuthoringController.php',
                    'app/Http/Controllers/Admin/CommandCentreController.php',
                ],
                'views' => [
                    'resources/views/admin/command_centre.blade.php',
                ],
                'notes' => [
                    'Check raw DB::raw("word_count + ...") in FolioAuthoringController and raw DB::table join in CommandCentreController.',
                ],
            ],

            'folio_revisions' => [
                'legacy_table' => 'chapter_versions',
                'target_model' => 'App\Models\FolioRevision',
                'legacy_model' => 'App\Models\ChapterVersion',
                'columns' => [
                    'id' => 'id',
                    'folio_id' => 'chapter_id',
                    'user_id' => 'user_id',
                    'revision_text' => 'content',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['chapter', 'folio', 'user'],
                'controllers' => [
                    'app/Http/Controllers/FolioAuthoringController.php',
                ],
                'views' => [],
                'notes' => [],
            ],

            'personae' => [
                'legacy_table' => 'characters',
                'target_model' => 'App\Models\Persona',
                'legacy_model' => 'App\Models\Character',
                'columns' => [
                    'id' => 'id',
                    'user_id' => 'user_id',
                    'assignment_id' => 'position_id',
                    'insignia_id' => 'rank_id',
                    'persona_mode' => ['character_type', 'persona_type'],
                    'persona_name' => 'name',
                    'persona_status' => 'status',
                    'primary_portrait_uri' => 'avatar',
                    'last_logged_location' => 'last_known_location',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['user', 'position', 'assignment', 'rank', 'insignia', 'serviceRecords', 'chapters', 'featuredChapters', 'attributes', 'application'],
                'controllers' => [
                    'app/Http/Controllers/PortalController.php',
                    'app/Http/Controllers/FolioAuthoringController.php',
                    'app/Http/Controllers/CorrespondenceController.php',
                    'app/Http/Controllers/Admin/PersonaAssignmentController.php',
                    'app/Http/Controllers/Admin/PersonaStatusController.php',
                    'app/Http/Controllers/Admin/PersonaTransferController.php',
                    'app/Http/Controllers/Admin/InsigniaPromotionController.php',
                    'app/Http/Controllers/Admin/CandidatePetitionController.php',
                ],
                'views' => [
                    'resources/views/widgets/persona_portrait.blade.php',
                    'resources/views/widgets/persona_metadata_summary.blade.php',
                    'resources/views/widgets/personae_table.blade.php',
                    'resources/views/widgets/character_sidebar_meta.blade.php',
                ],
                'notes' => [
                    'Remove legacy meta_data array cast from Persona (Character) model.',
                ],
            ],

            'persona_metadata' => [
                'legacy_table' => 'character_attributes',
                'target_model' => 'App\Models\PersonaMetadata',
                'legacy_model' => 'App\Models\CharacterAttribute',
                'columns' => [
                    'id' => 'id',
                    'persona_id' => 'character_id',
                    'attribute_key' => 'key',
                    'attribute_value' => 'value',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['character', 'persona'],
                'controllers' => [
                    'app/Http/Controllers/PortalController.php',
                ],
                'views' => [
                    'resources/views/widgets/persona_portrait.blade.php',
                ],
                'notes' => [],
            ],

            'content_tiers' => [
                'legacy_table' => 'content_ratings',
                'target_model' => 'App\Models\ContentTier',
                'legacy_model' => 'App\Models\ContentRating',
                'columns' => [
                    'id' => 'id',
                    'tier_category' => 'name',
                    'tier_level' => 'short_rating',
                    'tier_label' => 'long_rating',
                    'category_sort_order' => 'sort_rating_group',
                    'insignia_sort_order' => 'sort_rating',
                ],
                'relationships' => [],
                'controllers' => [
                    'app/Http/Controllers/FolioAuthoringController.php',
                    'app/Providers/AppServiceProvider.php',
                ],
                'views' => [
                    'resources/views/components/content-tier-badge.blade.php',
                    'resources/views/components/chapter-ratings.blade.php',
                ],
                'notes' => [],
            ],

            'metadata' => [
                'legacy_table' => 'form_fields',
                'target_model' => 'App\Models\Metadata',
                'legacy_model' => 'App\Models\FormField',
                'columns' => [
                    'id' => 'id',
                    'metadata_title' => 'name',
                    'metadata_mode' => ['type', 'metadata_type'],
                    'metadata_namespace' => 'form_type',
                    'linked_metadata' => 'form_linked',
                    'metadata_payloads' => 'options',
                    'is_required' => 'is_required',
                    'metadata_sort_order' => 'order',
                    'is_visible' => 'is_visible',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => [],
                'controllers' => [
                    'app/Http/Controllers/Admin/MetadataController.php',
                    'app/Http/Controllers/Admin/FormManagerController.php',
                    'app/Http/Controllers/CandidatePetitionSubmissionController.php',
                    'app/Http/Controllers/Admin/BookController.php',
                ],
                'views' => [
                    'resources/views/admin/metadata.blade.php',
                    'resources/views/admin/forms.blade.php',
                    'resources/views/components/dynamic-metadata.blade.php',
                    'resources/views/components/dynamic-field.blade.php',
                    'resources/views/layouts/partials/rendered_metadata.blade.php',
                    'resources/views/widgets/metadata.blade.php',
                ],
                'notes' => [
                    'Remove orphaned pages() belongsToMany relationship on FormField/Metadata model (form_field_page pivot was dropped in migration 37).',
                ],
            ],

            'books_grids' => [
                'legacy_table' => 'layouts',
                'target_model' => 'App\Models\Grid',
                'legacy_model' => 'App\Models\Layout',
                'columns' => [
                    'id' => 'id',
                    'grid_title' => 'name',
                    'grid_synopsis' => 'description',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['blocks', 'pages', 'books'],
                'controllers' => [
                    'app/Http/Controllers/Admin/GridController.php',
                    'app/Http/Controllers/Admin/LayoutController.php',
                    'app/Http/Controllers/Admin/BookController.php',
                ],
                'views' => [
                    'resources/views/admin/books.blade.php',
                    'resources/views/admin/books_grid_builder.blade.php',
                    'resources/views/components/books-grid-wrapper.blade.php',
                ],
                'notes' => [
                    'Check whether live DB table is named "grids" or "books_grids" (both appear in specification notes).',
                ],
            ],

            'book_grid_modules' => [
                'legacy_table' => 'layout_blocks',
                'target_model' => 'App\Models\GridModule',
                'legacy_model' => 'App\Models\LayoutBlock',
                'columns' => [
                    'id' => 'id',
                    'grid_id' => ['layout_id', 'grid_modules_id'],
                    'grid_module_title' => ['name', 'grid_modules_title', 'partition_title'],
                    'column_span' => 'col_span',
                    'row_span' => 'row_span',
                    'grid_module_sort_order' => ['sort_order', 'grid_modules_sort_order'],
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['layout', 'grid'],
                'controllers' => [
                    'app/Http/Controllers/Admin/GridController.php',
                ],
                'views' => [
                    'resources/views/admin/books.blade.php',
                    'resources/views/admin/books_grid_builder.blade.php',
                ],
                'notes' => [
                    'Check whether live DB table is named "grid_modules" or "book_grid_modules", and check singular/plural column prefixes.',
                ],
            ],

            'correspondence_threads' => [
                'legacy_table' => 'threads',
                'target_model' => 'App\Models\CorrespondenceThread',
                'legacy_model' => 'App\Models\Thread',
                'columns' => [
                    'id' => 'id',
                    'thread_classification' => 'type',
                    'thread_title' => 'title',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['participants', 'messages'],
                'controllers' => [
                    'app/Http/Controllers/CorrespondenceController.php',
                    'app/Http/Controllers/MessengerController.php',
                    'app/Services/MultiChannelNotificationService.php',
                ],
                'views' => [
                    'resources/views/components/correspondence-modal.blade.php',
                    'resources/views/components/correspondence-thread-item.blade.php',
                    'resources/views/widgets/unread_correspondence.blade.php',
                ],
                'notes' => [],
            ],

            'correspondence_entries' => [
                'legacy_table' => 'messages',
                'target_model' => 'App\Models\CorrespondenceEntry',
                'legacy_model' => 'App\Models\Message',
                'columns' => [
                    'id' => 'id',
                    'correspondence_thread_id' => 'thread_id',
                    'user_id' => 'user_id',
                    'persona_id' => 'character_id',
                    'correspondence_text' => 'body',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['thread', 'user', 'character', 'persona'],
                'controllers' => [
                    'app/Http/Controllers/CorrespondenceController.php',
                    'app/Http/Controllers/DashboardController.php',
                ],
                'views' => [
                    'resources/views/components/correspondence-modal.blade.php',
                    'resources/views/widgets/unread_correspondence.blade.php',
                ],
                'notes' => [],
            ],

            'correspondence_participants' => [
                'legacy_table' => 'thread_participants',
                'target_model' => 'App\Models\CorrespondenceParticipant',
                'legacy_model' => 'App\Models\ThreadParticipant',
                'columns' => [
                    'id' => 'id',
                    'correspondence_thread_id' => 'thread_id',
                    'user_id' => 'user_id',
                    'persona_id' => 'character_id',
                    'last_read_at' => 'last_read_at',
                    'deleted_at' => 'deleted_at',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['thread', 'user', 'character', 'persona'],
                'controllers' => [
                    'app/Http/Controllers/CorrespondenceController.php',
                    'app/Http/Controllers/DashboardController.php',
                ],
                'views' => [
                    'resources/views/components/correspondence-modal.blade.php',
                ],
                'notes' => [],
            ],

            'table_of_contents' => [
                'legacy_table' => 'navigation_links',
                'target_model' => 'App\Models\TableOfContent',
                'legacy_model' => 'App\Models\NavigationLink',
                'columns' => [
                    'id' => 'id',
                    'parent_entry_id' => 'parent_id',
                    'translation_code_key' => 'lexicon_key',
                    'destination_uri' => 'url',
                    'group_title' => 'group_name',
                    'group_sort_order' => 'group_order',
                    'display_location' => 'location',
                    'access_clearance' => 'visibility',
                    'required_permission' => 'required_permission',
                    'window_target' => 'target',
                    'entry_sort_order' => 'order',
                    'is_visible' => 'is_visible',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['children', 'parent'],
                'controllers' => [
                    'app/Http/Controllers/Admin/TableOfContentsController.php',
                    'app/Http/Controllers/Admin/NavigationController.php',
                    'app/Http/Controllers/Admin/AccessClearanceRoleController.php',
                    'app/Providers/AppServiceProvider.php',
                ],
                'views' => [
                    'resources/views/admin/table_of_contents.blade.php',
                    'resources/views/admin/nav.blade.php',
                    'resources/views/layouts/partials/table_of_contents.blade.php',
                    'resources/views/layouts/partials/navigation.blade.php',
                    'resources/views/book.blade.php',
                ],
                'notes' => [
                    'book.blade.php queries DB::table("navigation_links") directly; must update to table_of_contents.',
                ],
            ],

            'table_of_content_parameters' => [
                'legacy_table' => 'navigation_options',
                'target_model' => 'App\Models\TableOfContentParameter',
                'legacy_model' => 'App\Models\NavigationOption',
                'columns' => [
                    'id' => 'id',
                    'parameter_mode' => ['option_type', 'parameter_type'],
                    'parameter_value' => 'value',
                    'parameter_label' => 'label',
                    'parameter_sort_order' => 'sort_order',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => [],
                'controllers' => [
                    'app/Http/Controllers/Admin/TableOfContentsController.php',
                ],
                'views' => [
                    'resources/views/admin/table_of_content_parameters.blade.php',
                    'resources/views/admin/nav-options.blade.php',
                ],
                'notes' => [],
            ],

            'bulletins' => [
                'legacy_table' => 'news',
                'target_model' => 'App\Models\Bulletin',
                'legacy_model' => 'App\Models\News',
                'columns' => [
                    'id' => 'id',
                    'user_id' => 'author_id',
                    'bulletin_title' => ['title', 'bulletins_title'],
                    'bulletin_text' => ['content', 'bulletins_text'],
                    'published_at' => 'published_at',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['author'],
                'controllers' => [
                    'app/Http/Controllers/Admin/BulletinController.php',
                    'app/Http/Controllers/Admin/NewsController.php',
                    'app/Http/Controllers/PortalController.php',
                ],
                'views' => [
                    'resources/views/admin/bulletins.blade.php',
                    'resources/views/admin/news.blade.php',
                    'resources/views/widgets/bulletins.blade.php',
                    'resources/views/widgets/news.blade.php',
                ],
                'notes' => [],
            ],

            'books' => [
                'legacy_table' => 'pages',
                'target_model' => 'App\Models\Books',
                'legacy_model' => 'App\Models\Page',
                'columns' => [
                    'id' => 'id',
                    'grid_id' => 'layout_id',
                    'book_uri' => 'slug',
                    'book_title' => 'title',
                    'book_text' => 'content',
                    'is_published' => 'is_published',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['layout', 'grid', 'widgets', 'visibleWidgets', 'modules'],
                'controllers' => [
                    'app/Http/Controllers/Admin/BookController.php',
                    'app/Http/Controllers/Admin/PageController.php',
                    'app/Http/Controllers/PortalController.php',
                    'app/Http/Controllers/DashboardController.php',
                ],
                'views' => [
                    'resources/views/book.blade.php',
                    'resources/views/page.blade.php',
                    'resources/views/admin/books.blade.php',
                    'resources/views/admin/books_grid_builder.blade.php',
                ],
                'notes' => [
                    'Verify model class name (Book.php vs Books.php) as Volume.php replaced legacy Book.php.',
                ],
            ],

            'modules' => [
                'legacy_table' => 'widgets',
                'target_model' => 'App\Models\Modules',
                'legacy_model' => 'App\Models\Widget',
                'columns' => [
                    'id' => 'id',
                    'module_title' => 'name',
                    'module_token' => 'widget_key',
                    'module_default_settings' => 'default_options',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['pages', 'books'],
                'controllers' => [
                    'app/Http/Controllers/Admin/ModuleController.php',
                    'app/Http/Controllers/Admin/WidgetController.php',
                    'app/Http/Controllers/Admin/BookController.php',
                    'app/helpers.php',
                ],
                'views' => [
                    'resources/views/admin/books.blade.php',
                    'resources/views/admin/books_grid_builder.blade.php',
                ],
                'notes' => [
                    'Verify model class name (Module.php vs Modules.php).',
                ],
            ],

            'book_modules' => [
                'legacy_table' => 'page_widget',
                'target_model' => null,
                'legacy_model' => null,
                'columns' => [
                    'id' => 'id',
                    'book_id' => 'page_id',
                    'module_id' => 'widget_id',
                    'grid_module_id' => ['layout_block_id', 'book_grid_module_id'],
                    'module_sort_order' => ['sort_order', 'index_sort_order'],
                    'book_config' => 'instance_options',
                    'access_clearance' => 'required_permission',
                    'is_visible' => 'is_visible',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => [],
                'controllers' => [
                    'app/Http/Controllers/Admin/BookController.php',
                    'app/Http/Controllers/PortalController.php',
                ],
                'views' => [
                    'resources/views/components/books-grid-wrapper.blade.php',
                ],
                'notes' => [],
            ],

            'divisions' => [
                'legacy_table' => 'sections',
                'target_model' => 'App\Models\Division',
                'legacy_model' => 'App\Models\Section',
                'columns' => [
                    'id' => 'id',
                    'division_catalogue_id' => 'division_catalogue_id',
                    'insignia_catalogue_id' => 'rank_set_id',
                    'division_title' => 'name',
                    'division_description' => 'description',
                    'division_base_colour' => ['color', 'division_base_color'],
                    'division_sort_order' => 'order',
                    'is_visible' => 'is_visible',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['positions', 'assignments', 'rankSet', 'insigniaCatalogue', 'divisionCatalogue'],
                'controllers' => [
                    'app/Http/Controllers/Admin/DivisionController.php',
                    'app/Http/Controllers/Admin/SectionController.php',
                ],
                'views' => [
                    'resources/views/admin/divisions.blade.php',
                    'resources/views/admin/sections.blade.php',
                    'resources/views/widgets/personae_table.blade.php',
                    'resources/views/widgets/roster.blade.php',
                    'resources/views/widgets/manifest_descriptions.blade.php',
                    'resources/views/layouts/app.blade.php',
                ],
                'notes' => [
                    'Ensure division_catalogue_id is present and used by roster/personae_table and manifest_descriptions.blade.php.',
                    'Fix duplicate generateDescription() method and missing Position/Assignment import in DivisionController.',
                ],
            ],

            'division_catalogues' => [
                'legacy_table' => 'division_catalogues',
                'target_model' => 'App\Models\DivisionCatalogue',
                'legacy_model' => null,
                'columns' => [
                    'id' => 'id',
                    'catalogue_title' => 'name',
                    'catalogue_description' => 'description',
                    'is_visible' => 'is_visible',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['divisions'],
                'controllers' => [
                    'app/Http/Controllers/Admin/DivisionController.php',
                ],
                'views' => [
                    'resources/views/admin/divisions.blade.php',
                    'resources/views/widgets/personae_table.blade.php',
                    'resources/views/widgets/manifest_descriptions.blade.php',
                ],
                'notes' => [
                    'New model DivisionCatalogue.php noted in specification to build Division Catalogue names and descriptions.',
                ],
            ],

            'assignments' => [
                'legacy_table' => 'positions',
                'target_model' => 'App\Models\Assignment',
                'legacy_model' => 'App\Models\Position',
                'columns' => [
                    'id' => 'id',
                    'division_id' => 'section_id',
                    'assignment_title' => 'name',
                    'assignment_description' => 'description',
                    'maximum_occupants' => 'capacity',
                    'is_recruitment_open' => 'is_open',
                    'is_visible' => 'is_visible',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['section', 'division', 'characters', 'personae'],
                'controllers' => [
                    'app/Http/Controllers/Admin/AssignmentController.php',
                    'app/Http/Controllers/Admin/PositionController.php',
                    'app/Http/Controllers/Admin/DivisionController.php',
                ],
                'views' => [
                    'resources/views/admin/divisions.blade.php',
                    'resources/views/widgets/personae_table.blade.php',
                    'resources/views/widgets/manifest_descriptions.blade.php',
                ],
                'notes' => [],
            ],

            'insignia_catalogues' => [
                'legacy_table' => 'rank_sets',
                'target_model' => 'App\Models\InsigniaCatalogue',
                'legacy_model' => 'App\Models\RankSet',
                'columns' => [
                    'id' => 'id',
                    'catalogue_title' => 'name',
                    'is_visible' => 'is_visible',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['ranks', 'insignia', 'sections', 'divisions'],
                'controllers' => [
                    'app/Http/Controllers/Admin/InsigniaCatalogueController.php',
                    'app/Http/Controllers/Admin/RankSetController.php',
                    'app/Http/Controllers/Admin/InsigniaController.php',
                ],
                'views' => [
                    'resources/views/admin/insignia.blade.php',
                    'resources/views/admin/ranks.blade.php',
                ],
                'notes' => [],
            ],

            'insignia' => [
                'legacy_table' => 'ranks',
                'target_model' => 'App\Models\Insignia',
                'legacy_model' => 'App\Models\Ranks',
                'columns' => [
                    'id' => 'id',
                    'insignia_catalogue_id' => 'rank_set_id',
                    'insignia_title' => 'name',
                    'abbreviated_title' => 'short_name',
                    'mid_layer_mode' => 'mid_type',
                    'mid_layer_asset' => 'mid_image',
                    'mid_layer_modifier' => 'mid_css',
                    'mid_layer_hue' => 'mid_color',
                    'top_layer_mode' => 'top_type',
                    'top_layer_asset' => 'top_image',
                    'top_layer_modifier' => 'top_css',
                    'top_layer_hue' => 'top_color',
                    'insignia_description' => 'description',
                    'insignia_sort_order' => 'sort_order',
                    'is_visible' => 'is_visible',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['rankSet', 'insigniaCatalogue'],
                'controllers' => [
                    'app/Http/Controllers/Admin/InsigniaController.php',
                    'app/Http/Controllers/Admin/RankController.php',
                    'app/Http/Controllers/Admin/InsigniaPromotionController.php',
                ],
                'views' => [
                    'resources/views/admin/insignia.blade.php',
                    'resources/views/admin/ranks.blade.php',
                    'resources/views/components/insignia-badge.blade.php',
                    'resources/views/components/rank-badge.blade.php',
                    'resources/views/components/insignia-preview.blade.php',
                    'resources/views/components/insignia-layer.blade.php',
                ],
                'notes' => [
                    'Remove orphaned self-referential rank() method on Insignia (Ranks) model.',
                ],
            ],

            'chronological_registers' => [
                'legacy_table' => 'service_records',
                'target_model' => 'App\Models\ChronologicalRegister',
                'legacy_model' => 'App\Models\ServiceRecord',
                'columns' => [
                    'id' => 'id',
                    'persona_id' => 'character_id',
                    'user_id' => 'user_id',
                    'distinction_id' => ['award_id', 'distinctions_id'],
                    'authorising_user_id' => 'issued_by',
                    'register_event_mode' => ['event_type', 'register_event_type'],
                    'scope_target' => 'display_target',
                    'chronicle_text' => 'description',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['award', 'distinction', 'user', 'issuer', 'character', 'persona'],
                'controllers' => [
                    'app/Http/Controllers/Admin/ChronologicalRegisterController.php',
                    'app/Http/Controllers/Admin/ServiceRecordController.php',
                    'app/Http/Controllers/Admin/InsigniaPromotionController.php',
                    'app/Http/Controllers/Admin/PersonaTransferController.php',
                    'app/Http/Controllers/Admin/PersonaStatusController.php',
                    'app/Http/Controllers/Admin/VolumeReviewController.php',
                ],
                'views' => [
                    'resources/views/admin/chronological_registers.blade.php',
                    'resources/views/admin/service-records.blade.php',
                    'resources/views/widgets/chronological_registers.blade.php',
                    'resources/views/widgets/service_records.blade.php',
                    'resources/views/widgets/persona_biography.blade.php',
                    'resources/views/widgets/persona_chronological_register.blade.php',
                    'resources/views/widgets/persona_relationships.blade.php',
                ],
                'notes' => [],
            ],

            'configurations' => [
                'legacy_table' => 'settings',
                'target_model' => 'App\Models\Configuration',
                'legacy_model' => 'App\Models\Setting',
                'columns' => [
                    'id' => 'id',
                    'configuration_token' => 'key',
                    'configuration_payload' => 'value',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => [],
                'controllers' => [
                    'app/Http/Controllers/Admin/ConfigurationController.php',
                    'app/Http/Controllers/Admin/SettingController.php',
                    'app/Services/AiCore.php',
                    'app/Services/MultiChannelNotificationService.php',
                    'app/Providers/AppServiceProvider.php',
                    'app/helpers.php',
                ],
                'views' => [
                    'resources/views/admin/configurations.blade.php',
                    'resources/views/admin/settings.blade.php',
                    'resources/views/components/configuration-item.blade.php',
                    'resources/views/components/setting-row.blade.php',
                    'resources/views/widgets/folio.blade.php',
                    'resources/views/widgets/posts.blade.php',
                ],
                'notes' => [],
            ],

            'thematic_bindings' => [
                'legacy_table' => 'skins',
                'target_model' => 'App\Models\ThematicBinding',
                'legacy_model' => 'App\Models\Skin',
                'columns' => [
                    'id' => 'id',
                    'binding_title' => 'name',
                    'directory_name' => 'folder_name',
                    'package_version' => 'version',
                    'is_active' => 'is_active',
                    'is_global_default' => 'is_global_default',
                    'custom_head_injection' => 'custom_head',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => [],
                'controllers' => [
                    'app/Http/Controllers/Admin/ThematicBindingController.php',
                    'app/Http/Controllers/Admin/ThemeController.php',
                    'app/Providers/AppServiceProvider.php',
                ],
                'views' => [
                    'resources/views/admin/thematic_bindings.blade.php',
                    'resources/views/admin/themes.blade.php',
                    'resources/views/components/thematic-var-card.blade.php',
                    'resources/views/layouts/app.blade.php',
                    'resources/views/errors/minimal.blade.php',
                ],
                'notes' => [],
            ],

            'lexicon_codex' => [
                'legacy_table' => 'terms',
                'target_model' => 'App\Models\LexiconCodex',
                'legacy_model' => 'App\Models\Term',
                'columns' => [
                    'id' => 'id',
                    'semantic_token' => 'code_key',
                    'prefix_group_title' => 'master_group',
                    'group_title' => 'subgroup',
                    'default_lexicon_string' => 'display_value',
                    'translation_payloads' => 'translations',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => [],
                'controllers' => [
                    'app/Http/Controllers/Admin/LexiconCodexController.php',
                    'app/Http/Controllers/Admin/LexiconTermController.php',
                    'app/Http/Controllers/Admin/SemanticLanguageController.php',
                    'app/Http/Controllers/Admin/LexiconLanguageController.php',
                    'app/helpers.php',
                ],
                'views' => [
                    'resources/views/admin/lexicon_codex.blade.php',
                    'resources/views/admin/lexicon.blade.php',
                ],
                'notes' => [],
            ],

            'chronological_engines' => [
                'legacy_table' => 'time_systems',
                'target_model' => 'App\Models\ChronologicalEngine',
                'legacy_model' => 'App\Models\TimeSystem',
                'columns' => [
                    'id' => 'id',
                    'engine_title' => 'name',
                    'calculation_algorithm' => 'engine_type',
                    'date_prefix' => 'prefix',
                    'date_suffix' => 'suffix',
                    'base_earth_year' => 'base_earth_year',
                    'base_system_value' => 'base_system_value',
                    'temporal_multiplier' => 'multiplier',
                    'is_active' => 'is_active',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => [],
                'controllers' => [
                    'app/Http/Controllers/Admin/ChronologicalEngineController.php',
                    'app/Http/Controllers/Admin/TimeSystemController.php',
                    'app/Http/Controllers/Admin/ConfigurationController.php',
                    'app/helpers.php',
                ],
                'views' => [
                    'resources/views/admin/configurations.blade.php',
                ],
                'notes' => [
                    'Implement Dual-Value Hidden Input Architecture (Flatpickr standard Y-m-d H:i storage + world_date() display helper).',
                ],
            ],

            'users' => [
                'legacy_table' => 'users',
                'target_model' => 'App\Models\User',
                'legacy_model' => 'App\Models\User',
                'columns' => [
                    'id' => 'id',
                    'name' => 'name',
                    'email' => 'email',
                    'email_verified_at' => 'email_verified_at',
                    'password' => 'password',
                    'remember_token' => 'remember_token',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['characters', 'personae', 'posts', 'folios', 'oocAwards', 'applicationVotes', 'attributes', 'roles'],
                'controllers' => [
                    'app/Http/Controllers/ProfileController.php',
                    'app/Http/Controllers/Admin/UserController.php',
                    'app/Http/Controllers/Admin/CommandCentreController.php',
                ],
                'views' => [
                    'resources/views/dashboard.blade.php',
                    'resources/views/admin/users.blade.php',
                    'resources/views/admin/command_centre.blade.php',
                ],
                'notes' => [
                    'Ensure ProfileController::update writes twitter_handle and facebook_handle via setAttr() to user_attributes.',
                ],
            ],

            'user_attributes' => [
                'legacy_table' => 'user_attributes',
                'target_model' => 'App\Models\UserAttribute',
                'legacy_model' => 'App\Models\UserAttribute',
                'columns' => [
                    'id' => 'id',
                    'user_id' => 'user_id',
                    'attribute_key' => 'key',
                    'attribute_value' => 'value',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => ['user'],
                'controllers' => [
                    'app/Http/Controllers/AuthController.php',
                    'app/Http/Controllers/ProfileController.php',
                    'app/Http/Controllers/Admin/UserController.php',
                    'app/helpers.php',
                ],
                'views' => [
                    'resources/views/dashboard.blade.php',
                ],
                'notes' => [],
            ],

            'encyclopedia_codex' => [
                'legacy_table' => 'wiki_articles',
                'target_model' => 'App\Models\EncyclopediaCodex',
                'legacy_model' => 'App\Models\WikiArticle',
                'columns' => [
                    'id' => 'id',
                    'article_title' => 'title',
                    'article_uri' => 'slug',
                    'article_category' => 'category',
                    'article_text' => 'content',
                    'is_visible' => 'is_visible',
                    'created_at' => 'created_at',
                    'updated_at' => 'updated_at',
                ],
                'relationships' => [],
                'controllers' => [
                    'app/Http/Controllers/Admin/EncyclopediaCodexController.php',
                    'app/Http/Controllers/Admin/WikiController.php',
                ],
                'views' => [
                    'resources/views/admin/encyclopedia_codex.blade.php',
                    'resources/views/admin/wiki.blade.php',
                    'resources/views/widgets/encyclopedia_codex_modules.blade.php',
                    'resources/views/widgets/wiki_widget.blade.php',
                ],
                'notes' => [
                    'Standardise TipTap event name between wikiManager.js and tiptapEditor.js.',
                ],
            ],
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $blueprint = $this->getBlueprint();
        $requestedTable = $this->argument('table');

        if ($requestedTable) {
            $resolvedKey = $this->resolveTableKey($requestedTable, $blueprint);

            if (! $resolvedKey) {
                $this->error("Table '{$requestedTable}' was not found in the MOWS Blueprint.");
                $this->line('Available target tables: ' . implode(', ', array_keys($blueprint)));
                return self::FAILURE;
            }

            return $this->inspectSingleTable($resolvedKey, $blueprint[$resolvedKey]);
        }

        return $this->renderMasterOverview($blueprint);
    }

    /**
     * Resolve either a target table name or legacy table name to its blueprint key.
     */
    protected function resolveTableKey(string $input, array $blueprint): ?string
    {
        $input = strtolower(trim($input));

        if (isset($blueprint[$input])) {
            return $input;
        }

        foreach ($blueprint as $targetTable => $spec) {
            if (strtolower($spec['legacy_table'] ?? '') === $input) {
                return $targetTable;
            }
        }

        return null;
    }

    /**
     * Display the Master Overview Table across all blueprint tables + detect unknown DB tables.
     */
    protected function renderMasterOverview(array $blueprint): int
    {
        $this->info('================================================================================');
        $this->info(' MOWS ENGINE - PHASE 7 THREE-WAY DRIFT AUDITOR (DB <-> OUTLINE <-> LARAVEL)');
        $this->info('================================================================================');

        $liveTables = collect(Schema::getTables())->pluck('name')->map(fn($t) => strtolower($t))->toArray();
        $rows = [];

        foreach ($blueprint as $targetTable => $spec) {
            $audit = $this->analyseTable($targetTable, $spec, $liveTables);

            $dbStatus = match ($audit['table_state']) {
                'TARGET_EXISTS' => '<fg=green>TARGET (' . $audit['active_table'] . ')</>',
                'LEGACY_EXISTS' => '<fg=yellow>LEGACY (' . $audit['active_table'] . ')</>',
                'MISSING' => '<fg=red>MISSING IN DB</>',
            };

            $colIssues = count($audit['missing_target_cols']) + count($audit['unmigrated_legacy_cols']) + count($audit['extra_hallucinated_cols']);
            $colStatus = $colIssues === 0
                ? '<fg=green>OK (' . count($audit['live_columns']) . ' cols)</>'
                : sprintf(
                    '<fg=red>%d Miss</> / <fg=yellow>%d Old</> / <fg=magenta>%d Extra</>',
                    count($audit['missing_target_cols']),
                    count($audit['unmigrated_legacy_cols']),
                    count($audit['extra_hallucinated_cols'])
                );

            $modelStatus = match ($audit['model_state']) {
                'TARGET_FOUND' => '<fg=green>' . class_basename($spec['target_model']) . '</>',
                'LEGACY_FOUND' => '<fg=yellow>Old: ' . class_basename($spec['legacy_model']) . '</>',
                'BOTH_FOUND' => '<fg=magenta>Both Exist!</>',
                'PIVOT' => '<fg=gray>Pivot</>',
                default => '<fg=red>Missing</>',
            };

            $rows[] = [
                $targetTable,
                $dbStatus,
                $colStatus,
                $modelStatus,
                $audit['legacy_code_hits'] > 0 ? "<fg=yellow>{$audit['legacy_code_hits']} hits</>" : '<fg=green>Clean</>',
            ];
        }

        $this->table(
            ['Target Table', 'DB Table State', 'Columns (Miss / Old / Extra)', 'Model State', 'Legacy Code Hits'],
            $rows
        );

        // Check for completely unknown tables in the database (excluding standard Laravel/Spatie tables)
        $standardFrameworkTables = [
            'migrations',
            'password_reset_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'permissions',
            'roles',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'agent_prompt_cache',
            'agent_conversation_messages',
        ];

        $knownBlueprintTables = array_merge(
            array_keys($blueprint),
            array_column($blueprint, 'legacy_table'),
            ['grids', 'grid_modules'],
            $standardFrameworkTables
        );

        $unknownTables = array_diff($liveTables, array_map('strtolower', array_filter($knownBlueprintTables)));

        if (! empty($unknownTables)) {
            $this->newLine();
            $this->warn('! UNRECOGNISED / POTENTIALLY HALLUCINATED TABLES IN DATABASE:');
            foreach ($unknownTables as $uTable) {
                $cols = Schema::getColumnListing($uTable);
                $this->line("  - <fg=magenta>{$uTable}</> (Columns: " . implode(', ', $cols) . ')');
            }
        }

        if ($this->option('all-details')) {
            foreach ($blueprint as $targetTable => $spec) {
                $this->inspectSingleTable($targetTable, $spec);
            }
        } else {
            $this->newLine();
            $this->comment('Tip: Run `php artisan audit <table_name> --bundle` to inspect one table and generate a tiny AI context file.');
        }

        return self::SUCCESS;
    }

    /**
     * Analyse a single table against live MySQL, Eloquent Models, Controllers, and Views.
     */
    protected function analyseTable(string $targetTable, array $spec, ?array $liveTables = null): array
    {
        $liveTables = $liveTables ?? collect(Schema::getTables())->pluck('name')->map(fn($t) => strtolower($t))->toArray();
        $legacyTable = strtolower($spec['legacy_table'] ?? '');

        $activeTable = null;
        $tableState = 'MISSING';

        if (in_array(strtolower($targetTable), $liveTables, true)) {
            $activeTable = $targetTable;
            $tableState = 'TARGET_EXISTS';
        } elseif ($targetTable === 'books_grids' && in_array('grids', $liveTables, true)) {
            $activeTable = 'grids';
            $tableState = 'TARGET_EXISTS';
        } elseif ($targetTable === 'book_grid_modules' && in_array('grid_modules', $liveTables, true)) {
            $activeTable = 'grid_modules';
            $tableState = 'TARGET_EXISTS';
        } elseif ($legacyTable && in_array($legacyTable, $liveTables, true)) {
            $activeTable = $legacyTable;
            $tableState = 'LEGACY_EXISTS';
        }

        $liveColumns = $activeTable ? Schema::getColumnListing($activeTable) : [];
        $liveColumnsLower = array_map('strtolower', $liveColumns);

        $missingTargetCols = [];
        $unmigratedLegacyCols = [];
        $verifiedTargetCols = [];

        $expectedTargetColsLower = [];
        $expectedLegacyColsLower = [];

        foreach ($spec['columns'] as $targetCol => $legacyDef) {
            $tLower = strtolower($targetCol);
            $expectedTargetColsLower[] = $tLower;

            $legacyCandidates = is_array($legacyDef) ? $legacyDef : [$legacyDef];
            foreach ($legacyCandidates as $cand) {
                $expectedLegacyColsLower[] = strtolower($cand);
            }

            if (in_array($tLower, $liveColumnsLower, true)) {
                $verifiedTargetCols[] = $targetCol;
            } else {
                $matchedLegacy = null;
                foreach ($legacyCandidates as $cand) {
                    if (in_array(strtolower($cand), $liveColumnsLower, true)) {
                        $matchedLegacy = $cand;
                        break;
                    }
                }

                if ($matchedLegacy !== null) {
                    $unmigratedLegacyCols[$matchedLegacy] = $targetCol;
                } else {
                    $missingTargetCols[$targetCol] = implode(' / ', $legacyCandidates);
                }
            }
        }

        // Flag any column in live DB that is neither in target columns nor legacy/intermediate columns
        $extraHallucinatedCols = [];
        foreach ($liveColumns as $liveCol) {
            $lcLower = strtolower($liveCol);
            if (! in_array($lcLower, $expectedTargetColsLower, true) && ! in_array($lcLower, $expectedLegacyColsLower, true)) {
                $extraHallucinatedCols[] = $liveCol;
            }
        }

        // Check Model existence
        $modelState = 'PIVOT';
        $targetModelPath = $spec['target_model'] ? base_path(str_replace(['App\\', '\\'], ['app/', '/'], $spec['target_model']) . '.php') : null;
        $legacyModelPath = $spec['legacy_model'] ? base_path(str_replace(['App\\', '\\'], ['app/', '/'], $spec['legacy_model']) . '.php') : null;

        if ($spec['target_model']) {
            $hasTarget = $targetModelPath && File::exists($targetModelPath);
            $hasLegacy = $legacyModelPath && File::exists($legacyModelPath) && ($targetModelPath !== $legacyModelPath);

            if ($hasTarget && $hasLegacy) {
                $modelState = 'BOTH_FOUND';
            } elseif ($hasTarget) {
                $modelState = 'TARGET_FOUND';
            } elseif ($hasLegacy) {
                $modelState = 'LEGACY_FOUND';
            } else {
                $modelState = 'MISSING';
            }
        }

        // Scan linked files for legacy column/table references
        $fileFindings = [];
        $legacyCodeHits = 0;

        $filesToScan = array_unique(array_filter(array_merge(
            [$targetModelPath ? str_replace(base_path() . DIRECTORY_SEPARATOR, '', $targetModelPath) : null],
            [$legacyModelPath ? str_replace(base_path() . DIRECTORY_SEPARATOR, '', $legacyModelPath) : null],
            $spec['controllers'] ?? [],
            $spec['views'] ?? []
        )));

        foreach ($filesToScan as $relPath) {
            $fullPath = base_path($relPath);
            if (! File::exists($fullPath)) {
                continue;
            }

            $content = File::get($fullPath);
            $issuesInFile = [];

            if ($spec['legacy_table'] && $spec['legacy_table'] !== $targetTable) {
                $legTable = $spec['legacy_table'];
                if (preg_match('/(exists|unique)\s*:\s*' . preg_quote($legTable, '/') . '\b/', $content)) {
                    $issuesInFile[] = "Validation rule still references legacy table '{$legTable}'";
                }
                if (preg_match('/DB::table\(\s*[\'"]' . preg_quote($legTable, '/') . '[\'"]\s*\)/', $content)) {
                    $issuesInFile[] = "DB::table() still queries legacy table '{$legTable}'";
                }
            }

            foreach ($spec['columns'] as $targetCol => $legacyDef) {
                $legacyCandidates = is_array($legacyDef) ? $legacyDef : [$legacyDef];
                foreach ($legacyCandidates as $legacyCol) {
                    if ($targetCol === $legacyCol) {
                        continue;
                    }
                    $pattern = '/(->' . preg_quote($legacyCol, '/') . '\b|[\'"]' . preg_quote($legacyCol, '/') . '[\'"])/';
                    if (preg_match($pattern, $content)) {
                        $issuesInFile[] = "References legacy/intermediate column '{$legacyCol}' (Target: '{$targetCol}')";
                    }
                }
            }

            if (! empty($issuesInFile)) {
                $fileFindings[$relPath] = $issuesInFile;
                $legacyCodeHits += count($issuesInFile);
            }
        }

        return [
            'active_table' => $activeTable,
            'table_state' => $tableState,
            'live_columns' => $liveColumns,
            'verified_target_cols' => $verifiedTargetCols,
            'missing_target_cols' => $missingTargetCols,
            'unmigrated_legacy_cols' => $unmigratedLegacyCols,
            'extra_hallucinated_cols' => $extraHallucinatedCols,
            'model_state' => $modelState,
            'target_model_path' => $targetModelPath,
            'legacy_model_path' => $legacyModelPath,
            'file_findings' => $fileFindings,
            'legacy_code_hits' => $legacyCodeHits,
        ];
    }

    /**
     * Inspect a single table in detail and optionally export audit_bundle.txt.
     */
    protected function inspectSingleTable(string $targetTable, array $spec): int
    {
        $audit = $this->analyseTable($targetTable, $spec);

        $this->newLine();
        $this->info("================================================================================");
        $this->info(" AUDIT REPORT: [{$targetTable}] (Legacy: {$spec['legacy_table']})");
        $this->info("================================================================================");

        $this->line("<options=bold>1. LIVE MYSQL SCHEMA STATUS:</>");
        if ($audit['table_state'] === 'MISSING') {
            $this->error("   [FAIL] Neither '{$targetTable}' nor '{$spec['legacy_table']}' exists in the database!");
        } else {
            $this->line("   Active Table Found: <fg=cyan>{$audit['active_table']}</> ({$audit['table_state']})");
            $this->line("   Verified Target Columns (" . count($audit['verified_target_cols']) . "): <fg=green>" . implode(', ', $audit['verified_target_cols']) . "</>");

            if (! empty($audit['unmigrated_legacy_cols'])) {
                $this->warn("   [UNMIGRATED LEGACY / INTERMEDIATE COLUMNS IN DB]:");
                foreach ($audit['unmigrated_legacy_cols'] as $oldCol => $newCol) {
                    $this->line("     - <fg=yellow>{$oldCol}</> -> needs renaming to <fg=green>{$newCol}</>");
                }
            }

            if (! empty($audit['missing_target_cols'])) {
                $this->error("   [MISSING TARGET COLUMNS IN DB (Dropped or Not Created Yet)]:");
                foreach ($audit['missing_target_cols'] as $newCol => $oldCol) {
                    $this->line("     - <fg=red>{$newCol}</> (formerly '{$oldCol}') is completely missing from MySQL!");
                }
            }

            if (! empty($audit['extra_hallucinated_cols'])) {
                $this->warn("   [EXTRA / HALLUCINATED COLUMNS IN DB (Not in MOWS Blueprint)]:");
                foreach ($audit['extra_hallucinated_cols'] as $extraCol) {
                    $this->line("     - <fg=magenta>{$extraCol}</> (Exists in MySQL, but not in Blueprint)");
                }
            }
        }

        $this->newLine();
        $this->line("<options=bold>2. ELOQUENT MODEL STATUS:</>");
        $this->line("   Target Model: " . ($spec['target_model'] ?? 'None (Pivot)') . " -> State: <fg=cyan>{$audit['model_state']}</>");

        $this->newLine();
        $this->line("<options=bold>3. LINKED CODEBASE DRIFT (MODELS, CONTROLLERS & BLADE VIEWS):</>");
        if (empty($audit['file_findings'])) {
            $this->info("   [PASS] No legacy column or table references detected in linked files.");
        } else {
            foreach ($audit['file_findings'] as $file => $issues) {
                $this->warn("   File: {$file}");
                foreach ($issues as $issue) {
                    $this->line("     * {$issue}");
                }
            }
        }

        if (! empty($spec['notes'])) {
            $this->newLine();
            $this->line("<options=bold>4. ARCHITECTURAL NOTES FOR THIS TABLE:</>");
            foreach ($spec['notes'] as $note) {
                $this->line("   - <fg=cyan>{$note}</>");
            }
        }

        if ($this->option('bundle')) {
            $this->exportAiBundle($targetTable, $spec, $audit);
        }

        return self::SUCCESS;
    }

    /**
     * Generate a tiny, zero-waste audit_bundle.txt for the AI Chat Session.
     */
    protected function exportAiBundle(string $targetTable, array $spec, array $audit): void
    {
        $bundleLines = [];
        $bundleLines[] = "================================================================================";
        $bundleLines[] = "MOWS ENGINE - REFACTOR WORK ORDER BUNDLE FOR TABLE: {$targetTable}";
        $bundleLines[] = "Generated: " . now()->toDateTimeString();
        $bundleLines[] = "================================================================================";
        $bundleLines[] = "";
        $bundleLines[] = "### 1. SCHEMA & BLUEPRINT DELTA";
        $bundleLines[] = "Target Table: {$targetTable} (Legacy: {$spec['legacy_table']})";
        $bundleLines[] = "Active DB Table: " . ($audit['active_table'] ?? 'NONE');
        $bundleLines[] = "Live DB Columns: " . implode(', ', $audit['live_columns']);
        $bundleLines[] = "Expected Blueprint Mapping (Target => Legacy): " . json_encode($spec['columns'], JSON_PRETTY_PRINT);
        $bundleLines[] = "Verified Target Columns: " . implode(', ', $audit['verified_target_cols']);
        $bundleLines[] = "Unmigrated Legacy Columns: " . json_encode($audit['unmigrated_legacy_cols']);
        $bundleLines[] = "Missing Target Columns: " . json_encode($audit['missing_target_cols']);
        $bundleLines[] = "Extra/Hallucinated DB Columns: " . implode(', ', $audit['extra_hallucinated_cols']);
        $bundleLines[] = "";

        if ($audit['active_table']) {
            try {
                $createResult = DB::select("SHOW CREATE TABLE `{$audit['active_table']}`");
                if (! empty($createResult)) {
                    $createSql = (array) $createResult[0];
                    $sqlText = $createSql['Create Table'] ?? end($createSql);
                    $bundleLines[] = "### 2. LIVE MYSQL CREATE TABLE STATEMENT";
                    $bundleLines[] = "```sql";
                    $bundleLines[] = $sqlText . ";";
                    $bundleLines[] = "```";
                    $bundleLines[] = "";
                }
            } catch (\Throwable $e) {
                $bundleLines[] = "Could not fetch SHOW CREATE TABLE: " . $e->getMessage();
            }
        }

        $bundleLines[] = "### 3. DETECTED CODEBASE DRIFT & NOTES";
        foreach ($spec['notes'] as $note) {
            $bundleLines[] = "- NOTE: {$note}";
        }
        foreach ($audit['file_findings'] as $file => $issues) {
            $bundleLines[] = "- FILE DRIFT [{$file}]: " . implode(' | ', $issues);
        }
        $bundleLines[] = "";

        $bundleLines[] = "### 4. RELEVANT SOURCE FILES (EXISTING ON DISK)";
        $candidateFiles = array_unique(array_filter(array_merge(
            $audit['target_model_path'] ? [str_replace(base_path() . DIRECTORY_SEPARATOR, '', $audit['target_model_path'])] : [],
            $audit['legacy_model_path'] ? [str_replace(base_path() . DIRECTORY_SEPARATOR, '', $audit['legacy_model_path'])] : [],
            $spec['controllers'] ?? [],
            $spec['views'] ?? []
        )));

        foreach ($candidateFiles as $relFile) {
            $fullPath = base_path($relFile);
            if (File::exists($fullPath)) {
                $bundleLines[] = "=== FILE: {$relFile} ===";
                $bundleLines[] = File::get($fullPath);
                $bundleLines[] = "";
            }
        }

        $outputPath = base_path('audit_bundle.txt');
        File::put($outputPath, implode("\n", $bundleLines));

        $this->newLine();
        $this->info("✔ Successfully generated AI refactor bundle: {$outputPath}");
        $this->comment("  Upload or paste ONLY `audit_bundle.txt` into the chat to fix `{$targetTable}` step by step!");
    }
}
