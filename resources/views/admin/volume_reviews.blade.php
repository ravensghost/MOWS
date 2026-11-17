{{-- resources/views/admin/volume_reviews.blade.php --}}
<div x-data="volumeReview()">
    <x-modal show="modals.review.open" onClose="closeModal()" maxWidth="max-w-lg">
        <x-slot:top>
            <div class="flex flex-col gap-xs">
                <span class="text-lg font-bold text-h">{{ lexicon('modal_Story_VolumeReviewTitle', 'End-of-Volume Service Record Citations') }}</span>
                <span class="text-xs text-accent" x-text="modals.review.volumeTitle"></span>
            </div>
        </x-slot:top>

        <div class="flex flex-col gap-md">
            <template x-if="isLoading">
                <div class="p-6 text-center text-muted font-italic">
                    <span>{{ lexicon('msg_common_Loading', 'Loading participating personae and co-writers...') }}</span>
                </div>
            </template>

            <template x-if="!isLoading">
                <form @submit.prevent="submitReview()" class="flex flex-col gap-md">
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md">
                        <x-metadata type="select"
                                    name="register_event_mode"
                                    id="review-event-mode"
                                    xModel="modals.review.registerEventMode"
                                    :label="lexicon('label_Story_RegisterEventMode', 'Citation Event Mode')"
                                    :tooltip="lexicon('tooltip_Story_RegisterEventMode', 'Select the chronological register classification')">
                            <option value="milestone">{{ lexicon('opt_Story_EventMilestone', 'Major Campaign / Career Milestone') }}</option>
                            <option value="assignment">{{ lexicon('opt_Story_EventAssignment', 'Mission Assignment') }}</option>
                            <option value="commendation">{{ lexicon('opt_Story_EventCommendation', 'Official Commendation') }}</option>
                            <option value="award">{{ lexicon('opt_Story_EventAward', 'Award / Decoration') }}</option>
                        </x-metadata>

                        <x-metadata type="text"
                                    name="chronicle_text"
                                    id="review-chronicle-text"
                                    xModel="modals.review.chronicleText"
                                    :label="lexicon('label_Story_ChronicleText', 'Citation / Chronicle Text')"
                                    :placeholder="lexicon('placeholder_Story_ChronicleText', 'Defaults to volume synopsis or title...')"
                                    :tooltip="lexicon('tooltip_Story_ChronicleText', 'Official citation recorded on each selected service record')" />
                    </div>

                    <div class="flex flex-col gap-xs">
                        <span class="text-xs font-bold text-input-muted uppercase text-wider">
                            {{ lexicon('label_Story_SelectRecipients', 'Participating Personae & Co-Writers') }}
                        </span>

                        <div class="bg-panel-sub border-panel border-rounded p-3 max-h-64 overflow-y-auto flex flex-col gap-sm">
                            <template x-for="recipient in modals.review.recipients" :key="recipient.type + ':' + recipient.id">
                                <label class="flex flex-row items-center gap-sm text-sm text-p cursor-pointer select-none">
                                    <input type="checkbox"
                                           :value="recipient.type + ':' + recipient.id"
                                           x-model="modals.review.selectedRecipients"
                                           class="w-4 h-4 m-0 bg-input border-input border-rounded-sm grid items-center justify-center checkbox-engine cursor-pointer">
                                    <span x-text="recipient.name"></span>
                                </label>
                            </template>

                            <template x-if="modals.review.recipients.length === 0">
                                <div class="text-xs text-muted font-italic p-2 text-center">
                                    <span>{{ lexicon('msg_Story_NoRecipientsFound', 'No participating personae or authors found in this volume.') }}</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="border-t-actions pt-4 flex flex-row flex-wrap items-center justify-end gap-sm">
                        <x-button type="button"
                                  variant="secondary"
                                  :tooltip="lexicon('tooltip_common_Cancel', 'Cancel and close modal')"
                                  @click="closeModal()">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>

                        <x-button type="submit"
                                  variant="success"
                                  icon="bi-check2-circle"
                                  :tooltip="lexicon('tooltip_Story_IssueCitations', 'Bulk-issue chronological register citations')">
                            {{ lexicon('btn_Story_IssueCitations', 'Issue Citations') }}
                        </x-button>
                    </div>
                </form>
            </template>
        </div>
    </x-modal>
</div>