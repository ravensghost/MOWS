{{-- resources/views/layouts/partials/table_of_contents.blade.php --}}
@php
    $topLevelNav = isset($primaryNav)
        ? $primaryNav->filter(fn($entry) => empty($entry->parent_entry_id))
        : collect();
    $childNav = isset($primaryNav)
        ? $primaryNav->filter(fn($entry) => !empty($entry->parent_entry_id))->groupBy('parent_entry_id')
        : collect();
    $groupedDrawerNav = isset($drawerNav)
        ? $drawerNav->groupBy('group_title')
        : collect();
@endphp

<div x-data="{ drawerOpen: false, activeDropdown: null }"
     @keydown.escape.window="drawerOpen = false; activeDropdown = null"
     data-global-sync="navigation">

    <nav class="bg-panel-header border-b-panel shadow-panel w-full relative z-20">
        <div class="w-full max-w-container mx-auto px-container p-2 flex flex-row flex-wrap items-center justify-between gap-md">

            <!-- Primary Navigation Links -->
            <div data-global-nav="primary" class="flex flex-row flex-wrap items-center gap-md">
                @foreach($topLevelNav as $entry)
                    @php
                        $children =$childNav->get($entry->id, collect());$entryLabel = lexicon(
                            $entry->translation_code_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '',$entry->translation_code_key))
                        );
                    @endphp

                    @if($children->isNotEmpty())
                        <div class="relative flex flex-row items-center gap-xs" @click.outside="if (activeDropdown === {{ $entry->id }}) activeDropdown = null">
                            <a href="{{ $entry->destination_url }}"
                               target="{{ $entry->window_target }}"
                               class="font-bold text-sm text-input-muted hover-text-accent no-underline transition-colors">
                                {{ $entryLabel }}
                            </a>

                            <button type="button"
                                    @click.stop="activeDropdown = activeDropdown === {{ $entry->id }} ? null : {{ $entry->id }}"
                                    class="bg-transparent text-muted border-none cursor-pointer flex flex-row items-center justify-center p-1"
                                    :aria-expanded="activeDropdown === {{ $entry->id }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_nav_ToggleSubmenu', 'Toggle Submenu')) }}'"
                                    title="{{ lexicon('tooltip_nav_ToggleSubmenu', 'Toggle Submenu') }}"
                                    aria-label="{{ lexicon('tooltip_nav_ToggleSubmenu', 'Toggle Submenu') }}">
                                <i class="bi bi-chevron-down text-xs"></i>
                            </button>

                            <div x-show="activeDropdown === {{ $entry->id }}"
                                 x-transition.opacity.duration.200ms
                                 x-cloak
                                 class="bg-panel-body border-panel border-rounded shadow-panel flex flex-col absolute top-4 z-100 mt-4 w-48 overflow-hidden">
                                @foreach($children as $child)
                                    <a href="{{ $child->destination_url }}"
                                       target="{{ $child->window_target }}"
                                       class="inline-block p-2 text-sm text-p border-b-table-row hover-bg-panel-hover hover-text-h transition-colors no-underline">
                                        {{ lexicon($child->translation_code_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '',$child->translation_code_key))) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $entry->destination_url }}"
                           target="{{ $entry->window_target }}"
                           class="font-bold text-sm text-input-muted hover-text-accent no-underline transition-colors">
                            {{ $entryLabel }}
                        </a>
                    @endif
                @endforeach
            </div>

            <!-- Right-Side Action Triggers & Drawer Toggle -->
            <div class="flex flex-row flex-wrap gap-sm items-center justify-center">
                @auth
                    @php
                        $user = auth()->user();$hasTwitter = !empty($user->twitter_handle);$hasFacebook = !empty($user->facebook_handle);$currentBookAddress = urlencode(request()->fullUrl());
                    @endphp

                    <x-button type="button"
                              variant="ghost"
                              size="sm"
                              icon="bi-envelope"
                              :tooltip="lexicon('tooltip_nav_OpenCorrespondence', 'Open Correspondence')"
                              @click="$dispatch('open-messenger')" />

                    <x-button type="button"
                              variant="ghost"
                              size="sm"
                              icon="bi-journal-richtext"
                              :tooltip="lexicon('tooltip_nav_WriteFolio', 'Write a new story folio')"
                              @click="$dispatch('open-folio-modal')" />

                    @if($hasTwitter ||$hasFacebook)
                        <div class="hidden-mobile flex flex-row items-center gap-xs">
                            @if($hasTwitter)
                                @php $cleanTwitter = ltrim($user->twitter_handle, '@'); @endphp
                                <a href="https://twitter.com/intent/tweet?text={{ $currentBookAddress }}&via={{$cleanTwitter }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-muted hover-text-accent cursor-pointer flex items-center justify-center no-underline p-1"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_nav_ShareTwitter', 'Share on X (Twitter)')) }}'"
                                   title="{{ lexicon('tooltip_nav_ShareTwitter', 'Share on X (Twitter)') }}"
                                   aria-label="{{ lexicon('tooltip_nav_ShareTwitter', 'Share on X (Twitter)') }}">
                                    <i class="bi bi-twitter-x"></i>
                                </a>
                            @endif

                            @if($hasFacebook)
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $currentBookAddress }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-muted hover-text-accent cursor-pointer flex items-center justify-center no-underline p-1"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_nav_ShareFacebook', 'Share on Facebook')) }}'"
                                   title="{{ lexicon('tooltip_nav_ShareFacebook', 'Share on Facebook') }}"
                                   aria-label="{{ lexicon('tooltip_nav_ShareFacebook', 'Share on Facebook') }}">
                                    <i class="bi bi-facebook"></i>
                                </a>
                            @endif
                        </div>
                    @endif
                @endauth

                <!-- Drawer Menu Toggle -->
                <x-button type="button"
                          variant="ghost"
                          size="sm"
                          icon="bi-list"
                          :tooltip="lexicon('tooltip_nav_OpenMenu', 'Open Navigation Menu')"
                          @click="drawerOpen = true" />
            </div>
        </div>
    </nav>

    <!-- Context Drawer Modal Backdrop & Panel Container -->
    <div class="fixed inset-0 z-100 flex flex-row justify-end"
         x-show="drawerOpen"
         x-transition.opacity.duration.200ms
         x-cloak>

        <button type="button"
                class="backdrop-bg backdrop-blur fixed inset-0 cursor-pointer border-none"
                @click="drawerOpen = false"
                aria-label="{{ lexicon('tooltip_nav_CloseMenu', 'Close Navigation Menu') }}"
                tabindex="-1">
        </button>

        <div class="bg-panel-body border-panel shadow-lg relative z-10 w-64 max-w-75 h-full flex flex-col p-panel-body overflow-y-auto">
            <button type="button"
                    @click="drawerOpen = false"
                    class="text-muted bg-transparent hover-text-h absolute top-4 right-4 border-none cursor-pointer p-1 border-rounded-sm"
                    x-tooltip="'{{ addslashes(lexicon('tooltip_nav_CloseMenu', 'Close Navigation Menu')) }}'"
                    title="{{ lexicon('tooltip_nav_CloseMenu', 'Close Navigation Menu') }}"
                    aria-label="{{ lexicon('tooltip_nav_CloseMenu', 'Close Navigation Menu') }}">
                <i class="bi bi-x-lg"></i>
            </button>

            <div data-global-nav="drawer" class="flex flex-col gap-md mt-lg">
                @guest
                    <x-button variant="success"
                              :tooltip="lexicon('tooltip_auth_OpenLoginModal', 'Log in or register an account')"
                              @click="drawerOpen = false; $dispatch('open-auth')">
                        {{ lexicon('btn_common_LoginRegister', 'Login / Register') }}
                    </x-button>
                @endguest

                @auth
                    <x-meta action="/logout" method="POST" :ajax="false">
                        <x-button type="submit"
                                  variant="danger"
                                  class="w-full"
                                  :tooltip="lexicon('tooltip_auth_Logout', 'Sign out of your account')">
                            {{ lexicon('btn_common_Logout', 'Logout') }}
                        </x-button>
                    </x-meta>
                @endauth

                @foreach($groupedDrawerNav as $group =>$entries)
                    @if($group)
                        <div class="flex flex-col gap-sm mb-4" x-data="{ expanded: false }">
                            <button type="button"
                                    @click="expanded = !expanded"
                                    class="text-xs font-bold text-muted uppercase text-wider border-b-panel bg-transparent hover-text-h flex flex-row flex-wrap items-center justify-between w-full p-2 cursor-pointer border-rounded-sm"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'"
                                    title="{{ lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel') }}">
                                <span>{{ lexicon('label_nav_group_' . \Illuminate\Support\Str::slug($group, '_'),$group) }}</span>
                                <span class="text-xs text-muted" x-text="expanded ? '▼' : '▶'"></span>
                            </button>

                            <div class="flex flex-col gap-xs" x-show="expanded" x-collapse x-cloak>
                                @foreach($entries as $entry)
                                    @php
                                        $drawerEntryLabel = lexicon(
                                            $entry->translation_code_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '',$entry->translation_code_key))
                                        );
                                    @endphp
                                    @if($entry->destination_url === '/folios/create')
                                        <button type="button"
                                                @click="drawerOpen = false; $dispatch('open-folio-modal')"
                                                class="font-bold text-sm text-input-muted border-b-table-row hover-text-accent p-2 block w-full text-left bg-transparent cursor-pointer">
                                            {{ $drawerEntryLabel }}
                                        </button>
                                    @elseif($entry->destination_url === '/correspondence')
                                        <button type="button"
                                                @click="drawerOpen = false; $dispatch('open-messenger')"
                                                class="font-bold text-sm text-input-muted border-b-table-row hover-text-accent p-2 block w-full text-left bg-transparent cursor-pointer">
                                            {{ $drawerEntryLabel }}
                                        </button>
                                    @else
                                        <a href="{{ $entry->destination_url }}"
                                           target="{{ $entry->window_target }}"
                                           class="font-bold text-sm text-input-muted border-b-table-row hover-text-accent p-2 block no-underline">
                                            {{ $drawerEntryLabel }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col gap-xs mb-4">
                            @foreach($entries as $entry)
                                @php
                                    $drawerEntryLabel = lexicon(
                                        $entry->translation_code_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '',$entry->translation_code_key))
                                    );
                                @endphp
                                @if($entry->destination_url === '/folios/create')
                                    <button type="button"
                                            @click="drawerOpen = false; $dispatch('open-folio-modal')"
                                            class="font-bold text-sm text-input-muted border-b-table-row hover-text-accent p-2 block w-full text-left bg-transparent cursor-pointer">
                                        {{ $drawerEntryLabel }}
                                    </button>
                                @elseif($entry->destination_url === '/correspondence')
                                    <button type="button"
                                            @click="drawerOpen = false; $dispatch('open-messenger')"
                                            class="font-bold text-sm text-input-muted border-b-table-row hover-text-accent p-2 block w-full text-left bg-transparent cursor-pointer">
                                        {{ $drawerEntryLabel }}
                                    </button>
                                @else
                                    <a href="{{ $entry->destination_url }}"
                                       target="{{ $entry->window_target }}"
                                       class="font-bold text-sm text-input-muted border-b-table-row hover-text-accent p-2 block no-underline">
                                        {{ $drawerEntryLabel }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>