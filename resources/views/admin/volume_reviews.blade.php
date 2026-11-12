<div x-data="volumeReview"
     @open-volume-review.window="openModal('edit', $event.detail)">
    <x-modal show="reviewModalOpen" maxWidth="max-w-2xl">
        <x-slot:top>
            <div class="flex flex-row items-center gap-sm">
                <i class="bi bi-award text-accent"></i>
                <span x-text="'{{ addslashes(lexicon('label_Story_VolumeReviewTitle', 'Issue Volume Service Records')) }}: ' + volumeTitle"></span>
            </div>
        </x-slot:top>

        <div class="flex flex-col gap-md">
            <template x-if="isLoading">
                <div class="p-6 text-center text-muted italic">
                    <span>{{ lexicon('msg_common_Loading', 'Loading participating characters and co-writers...') }}</span>
                </div>
            </template>

            <template x-if="!isLoading">
                <form @submit.prevent="submitReview()" class="flex flex-col gap-md">
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-sm">
                        <div class="flex flex-col gap-xs">
                            <label for="review-event-mode" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_Story_RegisterEventMode', 'Citation Type') }}</span>
                            </label>
                            <select id="review-event-mode"
                                    name="register_event_mode"
                                    x-model="registerEventMode"
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent">
                                <option value="milestone">{{ lexicon('opt_Story_EventMilestone', 'Milestone') }}</option>
                                <option value="assignment">{{ lexicon('opt_Story_EventAssignment', 'Assignment') }}</option>
                                <option value="commendation">{{ lexicon('opt_Story_EventCommendation', 'Commendation') }}</option>
                                <option value="award">{{ lexicon('opt_Story_EventAward', 'Award') }}</option>
                            </select>
                        </div>

                        <x-form-input name="chronicle_text"
                                      id="review-chronicle-text"
                                      :label="lexicon('label_Story_ChronicleText', 'Citation / Chronicle Text')"
                                      :placeholder="lexicon('placeholder_Story_ChronicleText', 'Defaults to volume synopsis or title...')"
                                      xModel="chronicleText" />
                    </div>

                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_Story_SelectRecipients', 'Participating Personae & Co-Writers') }}</span>
                        </label>
                        <div class="bg-panel-sub border border-panel rounded-input p-3 max-h-60 overflow-y-auto flex flex-col gap-xs">
                            <template x-for="recipient in recipients" :key="recipient.type + ':' + recipient.id">
                                <label class="flex flex-row items-center gap-sm text-sm text-p cursor-pointer select-none">
                                    <input type="checkbox"
                                           :value="recipient.type + ':' + recipient.id"
                                           x-model="selectedRecipients"
                                           class="rounded border-input text-accent focus-ring-accent">
                                    <span x-text="recipient.name"></span>
                                </label>
                            </template>
                            <template x-if="recipients.length === 0">
                                <div class="text-xs text-muted italic p-2 text-center">
                                    <span>{{ lexicon('msg_Story_NoRecipientsFound', 'No participating personae or authors found in this volume.') }}</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="border-t-actions pt-6 text-right flex flex-row flex-wrap items-center justify-end gap-md">
                        <x-button type="button"
                                  variant="secondary"
                                  @click="closeModal()">
                            <span>{{ lexicon('btn_common_Cancel', 'Cancel') }}</span>
                        </x-button>
                        <x-button type="submit"
                                  variant="success">
                            <i class="bi bi-check2-circle"></i>
                            <span>{{ lexicon('btn_Story_IssueCitations', 'Issue Citations') }}</span>
                        </x-button>
                    </div>
                </form>
            </template>
        </div>
    </x-modal>
</div>