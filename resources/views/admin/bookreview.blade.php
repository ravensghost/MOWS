<div x-data="bookreview" @open-book-review.window="openReviewModal($event.detail.bookId, $event.detail.bookTitle)" x-cloak>
    <x-modal show="reviewModalOpen" maxWidth="max-w-2xl" onClose="closeModal()">
        <x-slot:top>
            <div class="flex flex-row items-center gap-xs">
                <i class="bi bi-award text-accent"></i>
                <span>{{ lexicon('label_BookReview_Title', 'End-of-Book Service Record Review') }}</span>
                <span class="text-accent" x-text="bookTitle ? ' - ' + bookTitle : ''"></span>
            </div>
        </x-slot:top>

        <div class="flex flex-col gap-md">
            <template x-if="isLoading">
                <div class="p-6 text-center text-muted italic">
                    <span>{{ lexicon('msg_common_LoadingParticipants', 'Scanning chapters and aggregating participants...') }}</span>
                </div>
            </template>

            <template x-if="!isLoading">
                <div class="flex flex-col gap-md">
                    <!-- Event Type Selection -->
                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_ServiceRecords_EventType', 'Event Type') }}</span>
                            <span class="text-required">*</span>
                        </label>
                        <select x-model="eventType" class="lumi-input">
                            <option value="milestone">{{ lexicon('label_ServiceRecords_OptMilestone', 'Milestone') }}</option>
                            <option value="assignment">{{ lexicon('label_ServiceRecords_OptAssignment', 'Assignment') }}</option>
                            <option value="commendation">{{ lexicon('label_ServiceRecords_OptCommendation', 'Commendation') }}</option>
                            <option value="award">{{ lexicon('label_ServiceRecords_TypeAward', 'Award / Decoration') }}</option>
                        </select>
                    </div>

                    <!-- Citation / Description -->
                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_ServiceRecords_ReasonCitation', 'Reason / Citation') }}</span>
                            <span class="text-required">*</span>
                        </label>
                        <input type="text" 
                               x-model="description" 
                               class="lumi-input" 
                               placeholder="{{ lexicon('label_BookReview_CitationPlaceholder', 'e.g. Successful completion of story arc...') }}">
                    </div>

                    <!-- Participating Characters & Co-Writers Checklist -->
                    <div class="flex flex-col gap-xs">
                        <label class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_BookReview_Participants', 'Participating Cast & Co-Writers') }}</span>
                        </label>
                        
                        <div class="bg-panel-sub border border-panel rounded-panel p-4 flex flex-col gap-sm max-h-60 overflow-y-auto">
                            <template x-for="recipient in recipients" :key="recipient.type + ':' + recipient.id">
                                <div class="flex flex-row items-center gap-sm">
                                    <input type="checkbox" 
                                           :id="'rec_' + recipient.type + '_' + recipient.id"
                                           :value="recipient.type + ':' + recipient.id"
                                           x-model="selectedRecipients"
                                           class="appearance-none bg-input m-0 w-4 h-4 border-input rounded-input-sm grid place-content-center checkbox-engine">
                                    <label :for="'rec_' + recipient.type + '_' + recipient.id" class="text-sm font-bold text-p cursor-pointer">
                                        <span x-text="recipient.name"></span>
                                    </label>
                                </div>
                            </template>

                            <template x-if="recipients.length === 0">
                                <span class="text-xs text-muted italic">{{ lexicon('msg_BookReview_NoParticipants', 'No participating characters or co-writers found in this book chapters.') }}</span>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <x-slot:bottom>
            <x-button type="button" variant="secondary" @click="closeModal()">
                {{ lexicon('btn_common_Cancel', 'Cancel') }}
            </x-button>
            <x-button type="button" variant="primary" @click="submitReview()" x-bind:disabled="isLoading">
                {{ lexicon('btn_BookReview_IssueRecords', 'Issue Records to Selected') }}
            </x-button>
        </x-slot:bottom>
    </x-modal>
</div>