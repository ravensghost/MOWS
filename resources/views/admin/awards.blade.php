@extends('layouts.app')

@section('title', lexicon('admin_label_Awards', 'Award Management'))

@section('content')
<div x-data="awardManager()" 
     @ajax-success.window="modalOpen = false; categoryModalOpen = false; refreshDOM_US()"
     @sync-color-up.window="updateColorFromPicker($event.detail.model, $event.detail.value)">
    <x-admin-grid-wrapper :page="$systemPage">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_label_Awards', 'Award Management')">
                <x-slot:actions>
                    <div class="flex flex-row gap-md items-center flex-wrap">
                        <x-button variant="secondary" 
                                  @click="categoryModalOpen = true"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_awards_ManageSubgroups', 'Configure award subgroup categories')) }}'"
                                  aria-label="{{ lexicon('tooltip_awards_ManageSubgroups', 'Configure award subgroup categories') }}">
                            ⚙️ {{ lexicon('btn_awards_ManageSubgroups', 'Manage Subgroups') }}
                        </x-button>
                        <x-button variant="success" 
                                  @click="openCreateModal()"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_awards_CreateAward', 'Create a new station award badge')) }}'"
                                  aria-label="{{ lexicon('tooltip_awards_CreateAward', 'Create a new station award badge') }}">
                            + {{ lexicon('btn_create_award', 'Create New Award') }}
                        </x-button>
                    </div>
                </x-slot:actions>
            </x-page-header>

            <x-table class="mt-4">
                <x-slot:headers>
                    <th class="p-4 text-left">{{ lexicon('label_common_Name', 'Name') }}</th>
                    <th class="p-4 text-left">{{ lexicon('label_awards_Grouping', 'Grouping') }}</th>
                    <th class="p-4 text-left">{{ lexicon('label_common_Type', 'Type') }}</th>
                    <th class="p-4 text-left">{{ lexicon('label_common_Preview', 'Preview') }}</th>
                    <th class="p-4 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                </x-slot:headers>

                @forelse($awards as $award)
                    <tr class="table-row">
                        <td class="p-4 text-sm font-bold text-main">{{ $award->name }}</td>
                        <td class="p-4 text-sm text-muted">
                            <x-badge :variant="$award->master_group === 'IC' ? 'primary' : ($award->master_group === 'OOC' ? 'warning' : 'success')">
                                {{ $award->master_group }}
                            </x-badge>
                            @if($award->category)
                                <span class="text-xs ml-2 opacity-75">/ {{ $award->category->name }}</span>
                            @endif
                        </td>
                        <td class="p-4 text-sm text-muted">
                            <x-badge :variant="$award->is_layered ? 'info' : 'secondary'">
                                {{ $award->is_layered ? lexicon('label_awards_3Layered', '3-Layered') : lexicon('label_awards_StaticImage', 'Static Image') }}
                            </x-badge>
                        </td>
                        <td class="p-4">
                            <x-award-badge 
                                :award-name="$award->name"
                                :description="$award->description"
                                :base-layer="$award->base_color"
                                :mid-type="$award->mid_type"
                                :mid-image="$award->mid_image"
                                :mid-css="$award->mid_css"
                                :top-type="$award->top_type"
                                :top-image="$award->top_image"
                                :top-css="$award->top_css"
                                :skin="$activeSkin->folder_name ?? 'default'"
                            />
                        </td>
                        <td class="p-4 text-right flex flex-row justify-end gap-sm items-center text-sm">
                            <x-button variant="text" class="text-warning hover-warning" 
                                @click="openEditModal({{ json_encode($award) }})"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_common_EditAward', 'Edit this award configuration')) }}'"
                                aria-label="{{ lexicon('tooltip_common_EditAward', 'Edit this award configuration') }}">
                                {{ lexicon('btn_common_Edit', 'Edit') }}
                            </x-button>
                            
                            <x-button variant="text" class="text-danger hover-danger" 
                                x-data="deleteAction('{{ route('admin.awards.destroy', $award) }}', 'DELETE', '{{ addslashes($award->name) }}')" 
                                @click="confirmDelete()"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_common_DeleteAward', 'Delete this award permanently')) }}'"
                                aria-label="{{ lexicon('tooltip_common_DeleteAward', 'Delete this award permanently') }}">
                                {{ lexicon('btn_common_Delete', 'Delete') }}
                            </x-button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-muted italic">
                            <span class="block">{{ lexicon('msg_no_awards', 'No awards configured yet.') }}</span>
                        </td>
                    </tr>
                @endforelse
            </x-table>

            <!-- MODAL: AWARD CREATION/EDITING -->
            <x-modal show="modalOpen" onClose="modalOpen = false" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="isEdit ? '{{ addslashes(lexicon('label_edit_award', 'Edit Award')) }}' : '{{ addslashes(lexicon('label_create_award', 'Create Award')) }}'"></span>
                </x-slot:top>
                
                <form :action="isEdit ? '/admin/awards/' + awardId : '/admin/awards'" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                    @csrf
                    
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <x-form-input 
                        name="name" 
                        :label="lexicon('label_awards_AwardName', 'Award Name')" 
                        xModel="name" 
                        :required="true"
                        :tooltip="lexicon('tooltip_awards_NameInput', 'Enter the full title of the award')" 
                    />

                    <div class="flex flex-col gap-xs">
                        <label for="award_description" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_common_Description', 'Description') }}</span>
                        </label>
                        <textarea id="award_description" 
                                  name="description" 
                                  x-model="description" 
                                  class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent p-2" 
                                  rows="3"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_awards_DescriptionInput', 'Provide a brief explanation or citation for this award')) }}'"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                        <div class="flex flex-col gap-xs">
                            <label for="award_master_group" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_MasterGroup', 'Master Group') }}</span>
                            </label>
                            <select id="award_master_group" 
                                    name="master_group" 
                                    x-model="master_group" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2" 
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_MasterGroupSelect', 'Select whether this award targets users, characters, or both')) }}'"
                                    required>
                                <option value="BOTH">{{ lexicon('label_awards_OptBoth', 'BOTH (User & Character)') }}</option>
                                <option value="IC">{{ lexicon('label_awards_OptIC', 'IC (Character Only)') }}</option>
                                <option value="OOC">{{ lexicon('label_awards_OptOOC', 'OOC (User Only)') }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label for="award_category_id" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_SubgroupCategory', 'Subgroup Category') }}</span>
                            </label>
                            <select id="award_category_id" 
                                    name="award_category_id" 
                                    x-model="award_category_id" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_SubgroupSelect', 'Assign this award to a custom subgroup category')) }}'">
                                <option value="">{{ lexicon('label_awards_NoSubgroup', '-- No Subgroup --') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-xs border-t border-panel pt-3">
                        <x-colour-picker name="base_color" xModel="base_color" :label="lexicon('label_awards_BaseColor', 'Base Color / Background')" />
                    </div>

                    <!-- Middle Layer Configuration -->
                    <div class="border-t border-panel pt-3 flex flex-col gap-xs">
                        <label for="award_mid_type" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_awards_MidLayerType', 'Middle Layer Type') }}</span>
                        </label>
                        <select id="award_mid_type" 
                                name="mid_type" 
                                x-model="mid_type" 
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_awards_MidTypeSelect', 'Choose between a CSS shape or an image asset for the middle layer')) }}'">
                            <option value="css">{{ lexicon('label_awards_CssShape', 'CSS Shape Class') }}</option>
                            <option value="image">{{ lexicon('label_awards_ImageAsset', 'Image Asset') }}</option>
                        </select>

                        <div class="mt-2" x-show="mid_type === 'image'">
                            <label for="award_mid_image" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_MidImage', 'Middle Image Asset (ml-*)') }}</span>
                            </label>
                            <select id="award_mid_image" 
                                    name="mid_image" 
                                    x-model="mid_image" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_MidImageSelect', 'Select an image file from the skin award folder')) }}'">
                                <option value="">{{ lexicon('msg_awards_SelectMidImage', 'Select Middle Image...') }}</option>
                                <template x-for="img in available_images.filter(i => i.startsWith('ml-') || i.startsWith('ml_'))" :key="img">
                                    <option :value="img" x-text="img"></option>
                                </template>
                            </select>
                        </div>
                        
                        <div class="mt-2" x-show="mid_type === 'css'">
                            <label for="award_mid_css" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_MidCss', 'Middle CSS Shape') }}</span>
                            </label>
                            <select id="award_mid_css" 
                                    name="mid_css" 
                                    x-model="mid_css" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_MidCssSelect', 'Select a predefined middle CSS shape')) }}'">
                                <option value="">{{ lexicon('msg_awards_SelectShape', '-- Select Shape Modifier --') }}</option>
                                @foreach($midShapes ?? [] as $class => $label)
                                    <option value="{{ $class }}">{{ $label }} (award-ml-{{ $class }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Top Layer Configuration -->
                    <div class="border-t border-panel pt-3 flex flex-col gap-xs">
                        <label for="award_top_type" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_awards_TopLayerType', 'Top Layer Type') }}</span>
                        </label>
                        <select id="award_top_type" 
                                name="top_type" 
                                x-model="top_type" 
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_awards_TopTypeSelect', 'Choose between an image asset or a CSS shape for the top layer')) }}'">
                            <option value="">{{ lexicon('label_awards_NoneDisabled', '-- None (Disabled) --') }}</option>
                            <option value="css">{{ lexicon('label_awards_CssShape', 'CSS Shape Class') }}</option>
                            <option value="image">{{ lexicon('label_awards_ImageAsset', 'Image Asset') }}</option>
                        </select>

                        <div class="mt-2" x-show="top_type === 'image'">
                            <label for="award_top_image" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_TopImage', 'Top Image Asset (tl-*)') }}</span>
                            </label>
                            <select id="award_top_image" 
                                    name="top_image" 
                                    x-model="top_image" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_TopImageSelect', 'Select a top layer image asset file')) }}'">
                                <option value="">{{ lexicon('msg_awards_SelectTopImage', 'Select Top Image...') }}</option>
                                <template x-for="img in available_images.filter(i => i.startsWith('tl-') || i.startsWith('tl_'))" :key="img">
                                    <option :value="img" x-text="img"></option>
                                </template>
                            </select>
                        </div>

                        <div class="mt-2" x-show="top_type === 'css'">
                            <label for="award_top_css" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_TopCss', 'Top CSS Shape') }}</span>
                            </label>
                            <select id="award_top_css" 
                                    name="top_css" 
                                    x-model="top_css" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_TopCssSelect', 'Select a predefined top CSS shape')) }}'">
                                <option value="">{{ lexicon('msg_awards_SelectShape', '-- Select Shape Modifier --') }}</option>
                                @foreach($topShapes ?? [] as $class => $label)
                                    <option value="{{ $class }}">{{ $label }} (award-tl-{{ $class }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="border-t-actions pt-6 mt-4 text-right flex flex-row flex-wrap justify-end gap-sm items-center">
                        <x-button variant="secondary" 
                                  @click="modalOpen = false"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_CancelModal', 'Cancel and close modal')) }}'"
                                  aria-label="{{ lexicon('tooltip_common_CancelModal', 'Cancel and close modal') }}">
                            {{ lexicon('btn_common_Cancel', 'Cancel') }}
                        </x-button>
                        <x-button type="submit" 
                                  variant="primary"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_common_SaveAward', 'Save award configuration')) }}'"
                                  aria-label="{{ lexicon('tooltip_common_SaveAward', 'Save award configuration') }}">
                            {{ lexicon('btn_common_Save', 'Save Changes') }}
                        </x-button>
                    </div>
                </form>
            </x-modal>
            
            <!-- SUBGROUP CATEGORY MANAGER MODAL -->
            <x-modal show="categoryModalOpen" onClose="categoryModalOpen = false" maxWidth="max-w-lg">
                <x-slot:top>
                    {{ lexicon('label_awards_ManageSubgroups', 'Manage Award Subgroups') }}
                </x-slot:top>
                
                <form action="/admin/awards/categories" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-row gap-sm mb-4">
                    @csrf
                    <input type="text" 
                           name="name" 
                           class="bg-input text-input border-input rounded-input p-input outline-none h-input flex-grow box-border focus-border-accent focus-shadow-accent p-2" 
                           placeholder="{{ lexicon('msg_awards_NewSubgroupName', 'New Subgroup Name (e.g., Mission Medals)') }}" 
                           x-tooltip="'{{ addslashes(lexicon('tooltip_awards_NewSubgroupInput', 'Enter new award category title')) }}'"
                           aria-label="{{ lexicon('tooltip_awards_NewSubgroupInput', 'Enter new award category title') }}"
                           required>
                    <x-button type="submit" 
                              variant="success"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_common_AddCategory', 'Add new category subgroup')) }}'"
                              aria-label="{{ lexicon('tooltip_common_AddCategory', 'Add new category subgroup') }}">
                        + {{ lexicon('btn_common_Add', 'Add') }}
                    </x-button>
                </form>

                <x-table>
                    <x-slot:headers>
                        <th class="p-3 text-left">{{ lexicon('label_awards_SubgroupName', 'Subgroup Name') }}</th>
                        <th class="p-3 text-right">{{ lexicon('label_common_Actions', 'Actions') }}</th>
                    </x-slot:headers>
                    @forelse($categories as $category)
                        <tr class="table-row">
                            <td class="p-3">
                                <form action="/admin/awards/categories/{{ $category->id }}" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-row gap-sm items-center">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" 
                                           name="name" 
                                           class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2 text-sm" 
                                           value="{{ $category->name }}" 
                                           x-tooltip="'{{ addslashes(lexicon('tooltip_awards_EditSubgroupInput', 'Edit category title')) }}'"
                                           aria-label="{{ lexicon('tooltip_awards_EditSubgroupInput', 'Edit category title') }}"
                                           required>
                                    <x-button type="submit" variant="text" class="text-primary hover-main text-xs" 
                                        x-tooltip="'{{ addslashes(lexicon('tooltip_common_SaveEdit', 'Save Edit')) }}'" 
                                        aria-label="{{ lexicon('tooltip_common_SaveEdit', 'Save Edit') }}">💾</x-button>
                                </form>
                            </td>
                            <td class="p-3 text-right">
                                <x-button variant="text" class="text-danger hover-danger text-sm" 
                                    x-data="deleteAction('/admin/awards/categories/{{ $category->id }}', 'DELETE', '{{ addslashes($category->name) }}')" 
                                    @click="confirmDelete()"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_common_DeleteSubgroup', 'Delete this subgroup')) }}'"
                                    aria-label="{{ lexicon('tooltip_common_DeleteSubgroup', 'Delete this subgroup') }}">
                                    {{ lexicon('btn_common_Delete', 'Delete') }}
                                </x-button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="p-4 text-center text-muted italic">
                                <span class="block">{{ lexicon('msg_awards_NoSubgroups', 'No subgroups defined.') }}</span>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </x-modal>

        </x-slot:content>
    </x-admin-grid-wrapper>
</div>
@endsection