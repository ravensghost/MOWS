@extends('layouts.app')

@section('title', lexicon('admin_label_Awards', 'Award Management'))

@section('content')
<div x-data="distinctionManager()" 
     @ajax-success.window="closeModal('distinction'); closeModal('catalogue'); refreshDOM_US()"
     @sync-color-up.window="updateColourFromPicker($event.detail.model, $event.detail.value)">
    <x-grid-wrapper :book="$systemBook">
        <x-slot:content>
            <x-page-header :title="lexicon('admin_label_Awards', 'Award Management')">
                <x-slot:actions>
                    <div class="flex flex-row gap-md items-center flex-wrap">
                        <x-button variant="secondary" 
                                  @click="openModal('create', {}, 'catalogue')"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_awards_ManageSubgroups', 'Configure award subgroup categories')) }}'"
                                  aria-label="{{ lexicon('tooltip_awards_ManageSubgroups', 'Configure award subgroup categories') }}">
                            ⚙️ {{ lexicon('btn_awards_ManageSubgroups', 'Manage Subgroups') }}
                        </x-button>
                        <x-button variant="success" 
                                  @click="openModal('create', {}, 'distinction')"
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

                @forelse($distinctions as $distinction)
                    <tr class="table-row">
                        <td class="p-4 text-sm font-bold text-main">{{ $distinction->distinction_title }}</td>
                        <td class="p-4 text-sm text-muted">
                            <x-badge :variant="$distinction->scope_target === 'IC' ? 'primary' : ($distinction->scope_target === 'OOC' ? 'warning' : 'success')">
                                {{ $distinction->scope_target }}
                            </x-badge>
                            @if($distinction->catalogue)
                                <span class="text-xs ml-2 opacity-75">/ {{ $distinction->catalogue->catalogue_title }}</span>
                            @endif
                        </td>
                        <td class="p-4 text-sm text-muted">
                            <x-badge :variant="!empty($distinction->top_layer_mode) ? 'info' : 'secondary'">
                                {{ !empty($distinction->top_layer_mode) ? lexicon('label_awards_3Layered', '3-Layered') : lexicon('label_awards_StaticImage', 'Static Image') }}
                            </x-badge>
                        </td>
                        <td class="p-4">
                            <x-distinction-badge 
                                :distinction-title="$distinction->distinction_title"
                                :distinction-description="$distinction->distinction_description"
                                :distinction-base-colour="$distinction->distinction_base_colour"
                                :mid-layer-mode="$distinction->mid_layer_mode"
                                :mid-layer-asset="$distinction->mid_layer_asset"
                                :mid-layer-modifier="$distinction->mid_layer_modifier"
                                :top-layer-mode="$distinction->top_layer_mode"
                                :top-layer-asset="$distinction->top_layer_asset"
                                :top-layer-modifier="$distinction->top_layer_modifier"
                                :skin="$activeSkin->folder_name ?? 'default'"
                            />
                        </td>
                        <td class="p-4 text-right flex flex-row justify-end gap-sm items-center text-sm">
                            <x-button variant="text" class="text-warning hover-warning" 
                                @click="openModal('edit', {{ json_encode($distinction) }}, 'distinction')"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_common_EditAward', 'Edit this award configuration')) }}'"
                                aria-label="{{ lexicon('tooltip_common_EditAward', 'Edit this award configuration') }}">
                                {{ lexicon('btn_common_Edit', 'Edit') }}
                            </x-button>
                            
                            <x-button variant="text" class="text-danger hover-danger" 
                                x-data="deleteAction('{{ route('admin.distinctions.destroy', $distinction) }}', 'DELETE', '{{ addslashes($distinction->distinction_title) }}')" 
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

            <!-- MODAL: DISTINCTION CREATION/EDITING -->
            <x-modal show="modalOpen" onClose="closeModal('distinction')" maxWidth="max-w-lg">
                <x-slot:top>
                    <span x-text="isEdit ? '{{ addslashes(lexicon('label_edit_award', 'Edit Award')) }}' : '{{ addslashes(lexicon('label_create_award', 'Create Award')) }}'"></span>
                </x-slot:top>
                
                <form :action="isEdit ? '/admin/distinctions/' + id : '{{ route('admin.distinctions.store') }}'" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-col gap-md">
                    @csrf
                    
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <x-form-input 
                        name="distinction_title" 
                        :label="lexicon('label_awards_AwardName', 'Award Name')" 
                        xModel="distinction_title" 
                        :required="true"
                        :tooltip="lexicon('tooltip_awards_NameInput', 'Enter the full title of the award')" 
                    />

                    <div class="flex flex-col gap-xs">
                        <label for="distinction_description" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_common_Description', 'Description') }}</span>
                        </label>
                        <textarea id="distinction_description" 
                                  name="distinction_description" 
                                  x-model="distinction_description" 
                                  class="bg-input text-input border-input rounded-input p-input outline-none w-full box-border focus-border-accent focus-shadow-accent p-2" 
                                  rows="3"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_awards_DescriptionInput', 'Provide a brief explanation or citation for this award')) }}'"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md-grid-cols-2 gap-md w-full">
                        <div class="flex flex-col gap-xs">
                            <label for="distinction_scope_target" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_MasterGroup', 'Master Group') }}</span>
                            </label>
                            <select id="distinction_scope_target" 
                                    name="scope_target" 
                                    x-model="scope_target" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2" 
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_MasterGroupSelect', 'Select whether this award targets users, characters, or both')) }}'"
                                    required>
                                <option value="BOTH">{{ lexicon('label_awards_OptBoth', 'BOTH (User & Character)') }}</option>
                                <option value="IC">{{ lexicon('label_awards_OptIC', 'IC (Character Only)') }}</option>
                                <option value="OOC">{{ lexicon('label_awards_OptOOC', 'OOC (User Only)') }}</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-xs">
                            <label for="distinction_catalogue_id" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_SubgroupCategory', 'Subgroup Category') }}</span>
                            </label>
                            <select id="distinction_catalogue_id" 
                                    name="distinction_catalogue_id" 
                                    x-model="distinction_catalogue_id" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_SubgroupSelect', 'Assign this award to a custom subgroup category')) }}'">
                                <option value="">{{ lexicon('label_awards_NoSubgroup', '-- No Subgroup --') }}</option>
                                @foreach($catalogues as $catalogue)
                                    <option value="{{ $catalogue->id }}">{{ $catalogue->catalogue_title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-xs border-t border-panel pt-3">
                        <x-colour-picker name="distinction_base_colour" xModel="distinction_base_colour" :label="lexicon('label_awards_BaseColor', 'Base Colour / Background')" />
                    </div>

                    <!-- Middle Layer Configuration -->
                    <div class="border-t border-panel pt-3 flex flex-col gap-xs">
                        <label for="distinction_mid_type" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_awards_MidLayerType', 'Middle Layer Type') }}</span>
                        </label>
                        <select id="distinction_mid_type" 
                                name="mid_layer_mode" 
                                x-model="mid_layer_mode" 
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_awards_MidTypeSelect', 'Choose between a CSS shape or an image asset for the middle layer')) }}'">
                            <option value="css">{{ lexicon('label_awards_CssShape', 'CSS Shape Class') }}</option>
                            <option value="image">{{ lexicon('label_awards_ImageAsset', 'Image Asset') }}</option>
                        </select>

                        <div class="mt-2" x-show="mid_layer_mode === 'image'">
                            <label for="distinction_mid_image" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_MidImage', 'Middle Image Asset (ml-*)') }}</span>
                            </label>
                            <select id="distinction_mid_image" 
                                    name="mid_layer_asset" 
                                    x-model="mid_layer_asset" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_MidImageSelect', 'Select an image file from the skin award folder')) }}'">
                                <option value="">{{ lexicon('msg_awards_SelectMidImage', 'Select Middle Image...') }}</option>
                                <template x-for="img in available_images.filter(i => i.startsWith('ml-') || i.startsWith('ml_'))" :key="img">
                                    <option :value="img" x-text="img"></option>
                                </template>
                            </select>
                        </div>
                        
                        <div class="mt-2" x-show="mid_layer_mode === 'css'">
                            <label for="distinction_mid_css" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_MidCss', 'Middle CSS Shape') }}</span>
                            </label>
                            <select id="distinction_mid_css" 
                                    name="mid_layer_modifier" 
                                    x-model="mid_layer_modifier" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_MidCssSelect', 'Select a predefined middle CSS shape')) }}'">
                                <option value="">{{ lexicon('msg_awards_SelectShape', '-- Select Shape Modifier --') }}</option>
                                @foreach($midShapes ?? [] as $class => $label)
                                    <option value="{{ $class }}">{{ $label }} (distinction-ml-{{ $class }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Top Layer Configuration -->
                    <div class="border-t border-panel pt-3 flex flex-col gap-xs">
                        <label for="distinction_top_type" class="text-xs font-bold text-input-muted">
                            <span>{{ lexicon('label_awards_TopLayerType', 'Top Layer Type') }}</span>
                        </label>
                        <select id="distinction_top_type" 
                                name="top_layer_mode" 
                                x-model="top_layer_mode" 
                                class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                x-tooltip="'{{ addslashes(lexicon('tooltip_awards_TopTypeSelect', 'Choose between an image asset or a CSS shape for the top layer')) }}'">
                            <option value="">{{ lexicon('label_awards_NoneDisabled', '-- None (Disabled) --') }}</option>
                            <option value="css">{{ lexicon('label_awards_CssShape', 'CSS Shape Class') }}</option>
                            <option value="image">{{ lexicon('label_awards_ImageAsset', 'Image Asset') }}</option>
                        </select>

                        <div class="mt-2" x-show="top_layer_mode === 'image'">
                            <label for="distinction_top_image" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_TopImage', 'Top Image Asset (tl-*)') }}</span>
                            </label>
                            <select id="distinction_top_image" 
                                    name="top_layer_asset" 
                                    x-model="top_layer_asset" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_TopImageSelect', 'Select a top layer image asset file')) }}'">
                                <option value="">{{ lexicon('msg_awards_SelectTopImage', 'Select Top Image...') }}</option>
                                <template x-for="img in available_images.filter(i => i.startsWith('tl-') || i.startsWith('tl_'))" :key="img">
                                    <option :value="img" x-text="img"></option>
                                </template>
                            </select>
                        </div>

                        <div class="mt-2" x-show="top_layer_mode === 'css'">
                            <label for="distinction_top_css" class="text-xs font-bold text-input-muted">
                                <span>{{ lexicon('label_awards_TopCss', 'Top CSS Shape') }}</span>
                            </label>
                            <select id="distinction_top_css" 
                                    name="top_layer_modifier" 
                                    x-model="top_layer_modifier" 
                                    class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_awards_TopCssSelect', 'Select a predefined top CSS shape')) }}'">
                                <option value="">{{ lexicon('msg_awards_SelectShape', '-- Select Shape Modifier --') }}</option>
                                @foreach($topShapes ?? [] as $class => $label)
                                    <option value="{{ $class }}">{{ $label }} (distinction-tl-{{ $class }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="border-t-actions pt-6 mt-4 text-right flex flex-row flex-wrap justify-end gap-sm items-center">
                        <x-button variant="secondary" 
                                  @click="closeModal('distinction')"
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
            
            <!-- SUBGROUP CATALOGUE MANAGER MODAL -->
            <x-modal show="catalogueModalOpen" onClose="closeModal('catalogue')" maxWidth="max-w-lg">
                <x-slot:top>
                    {{ lexicon('label_awards_ManageSubgroups', 'Manage Award Subgroups') }}
                </x-slot:top>
                
                <form action="{{ route('admin.distinctions.catalogues.store') }}" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-row gap-sm mb-4">
                    @csrf
                    <input type="text" 
                           name="catalogue_title" 
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
                    @forelse($catalogues as $catalogue)
                        <tr class="table-row">
                            <td class="p-3">
                                <form action="{{ route('admin.distinctions.catalogues.update', $catalogue) }}" method="POST" x-data="ajaxForm" @submit.prevent="submit" class="flex flex-row gap-sm items-center">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" 
                                           name="catalogue_title" 
                                           class="bg-input text-input border-input rounded-input p-input outline-none h-input w-full box-border focus-border-accent focus-shadow-accent p-2 text-sm" 
                                           value="{{ $catalogue->catalogue_title }}" 
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
                                    x-data="deleteAction('{{ route('admin.distinctions.catalogues.destroy', $catalogue) }}', 'DELETE', '{{ addslashes($catalogue->catalogue_title) }}')" 
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
    </x-grid-wrapper>
</div>
@endsection