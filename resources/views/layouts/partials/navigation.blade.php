@php
    $topLevelNav = isset($primaryNav) ? $primaryNav->filter(fn($l) => !isset($l->parent_id) || empty($l->parent_id)) : collect();
    $childNav = isset($primaryNav) ? $primaryNav->filter(fn($l) => isset($l->parent_id) && !empty($l->parent_id))->groupBy('parent_id') : collect();
@endphp

<div x-data="{ drawerOpen: false, activeDropdown: null }">
    <nav class="bg-panel-header border-b-panel w-full relative">
        <div class="w-full max-w-container mx-auto px-container flex flex-row flex-wrap gap-md items-center justify-between h-16">
            <div data-global-nav="primary" class="flex flex-row flex-wrap gap-md items-center justify-center">
                @foreach($topLevelNav as $link)
                    @if($childNav->has($link->id))
                        <div class="relative flex flex-row items-center gap-xs"
                             @mouseenter="activeDropdown = {{ $link->id }}"
                             @mouseleave="activeDropdown = null"
                             @click.outside="if (activeDropdown === {{ $link->id }}) activeDropdown = null">
                            
                            <a href="{{ $link->url }}" target="{{ $link->target }}" class="font-bold text-sm text-input-muted hover-text-accent no-underline">
                                {{ lexicon($link->lexicon_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '', $link->lexicon_key))) }}
                            </a>

                            <button type="button"
                                    @click.stop="activeDropdown = activeDropdown === {{ $link->id }} ? null : {{ $link->id }}"
                                    class="bg-transparent text-muted border-none cursor-pointer flex flex-row items-center justify-center p-0"
                                    :aria-expanded="activeDropdown === {{ $link->id }}"
                                    x-tooltip="'{{ addslashes(lexicon('tooltip_nav_ToggleSubmenu', 'Toggle Submenu')) }}'"
                                    aria-label="{{ lexicon('tooltip_nav_ToggleSubmenu', 'Toggle Submenu') }}">
                                <svg class="w-icon h-icon transition-transform" :class="activeDropdown === {{ $link->id }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <div x-show="activeDropdown === {{ $link->id }}" 
                                 x-transition.opacity.duration.200ms
                                 x-cloak
                                 class="bg-panel border-panel rounded-panel shadow-panel flex flex-col absolute top-4 z-100 mt-4 w-48 overflow-hidden">
                                @foreach($childNav->get($link->id) as $child)
                                    <a href="{{ $child->url }}" target="{{ $child->target }}" class="inline-block p-nav-link text-sm text-p border-b border-panel hover-text-h transition-colors no-underline">
                                        {{ lexicon($child->lexicon_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '', $child->lexicon_key))) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $link->url }}" target="{{ $link->target }}" class="font-bold text-sm text-input-muted hover-text-accent no-underline">
                            {{ lexicon($link->lexicon_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '', $link->lexicon_key))) }}
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="flex flex-row flex-wrap gap-md items-center justify-center">
                <!-- Personalised Social Share & Modal Triggers -->
                @auth
                    @php
                        $user = auth()->user();
                        $hasTwitter = !empty($user->twitter_handle);
                        $hasFacebook = !empty($user->facebook_handle);
                        $currentUrl = urlencode(request()->url());
                    @endphp

                    <x-button type="button"
                              variant="text"
                              @click="$dispatch('open-messenger')"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_nav_OpenMessenger', 'Open Private Messages')) }}'"
                              aria-label="{{ lexicon('tooltip_nav_OpenMessenger', 'Open Private Messages') }}">
                        <i class="bi bi-envelope"></i>
                    </x-button>

                    <x-button type="button"
                              variant="text"
                              @click="$dispatch('open-chapter-modal')"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_nav_WriteChapter', 'Write a new story chapter')) }}'"
                              aria-label="{{ lexicon('tooltip_nav_WriteChapter', 'Write a new story chapter') }}">
                        <i class="bi bi-journal-richtext"></i>
                    </x-button>

                    @if($hasTwitter || $hasFacebook)
                        <div class="hidden-mobile flex flex-row items-center gap-sm">
                            @if($hasTwitter)
                                @php $cleanTwitter = ltrim($user->twitter_handle, '@'); @endphp
                                <a href="https://twitter.com/intent/tweet?url={{ $currentUrl }}&via={{ $cleanTwitter }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="bg-transparent border-none text-muted hover-text-accent cursor-pointer flex items-center justify-center no-underline"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_nav_ShareTwitter', 'Share on X (Twitter)')) }}'"
                                   aria-label="{{ lexicon('tooltip_nav_ShareTwitter', 'Share on X (Twitter)') }}">
                                    <svg class="w-icon h-icon" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 24.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                </a>
                            @endif

                            @if($hasFacebook)
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $currentUrl }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="bg-transparent border-none text-muted hover-text-accent cursor-pointer flex items-center justify-center no-underline"
                                   x-tooltip="'{{ addslashes(lexicon('tooltip_nav_ShareFacebook', 'Share on Facebook')) }}'"
                                   aria-label="{{ lexicon('tooltip_nav_ShareFacebook', 'Share on Facebook') }}">
                                    <svg class="w-icon h-icon" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    @endif
                @endauth

                <!-- Mobile Drawer Toggle -->
                <button type="button"
                        @click="drawerOpen = true"
                        class="bg-transparent border-none text-muted hover-text-h cursor-pointer p-0 flex items-center justify-center"
                        x-tooltip="'{{ addslashes(lexicon('tooltip_nav_OpenMenu', 'Open Navigation Menu')) }}'"
                        aria-label="{{ lexicon('tooltip_nav_OpenMenu', 'Open Navigation Menu') }}">
                    <svg class="w-icon h-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Context Drawer -->
    <div class="fixed inset-0 z-100 flex flex-row justify-end"
         x-show="drawerOpen"
         x-transition.opacity.duration.200ms
         x-cloak>
         
        <button type="button" 
                class="bg-backdrop backdrop-blur fixed inset-0 cursor-pointer border-none" 
                @click="drawerOpen = false"
                aria-label="{{ lexicon('tooltip_nav_CloseMenu', 'Close Navigation Menu') }}"
                tabindex="-1">
        </button>

        <div class="bg-body border-l-panel shadow-drawer relative z-10 w-64 max-w-75 h-full flex flex-col p-panel overflow-y-auto">
            
            <button type="button"
                    @click="drawerOpen = false"
                    class="text-muted bg-transparent hover-text-h hover-bg-close absolute top-4 right-4 border-none cursor-pointer p-1 rounded"
                    x-tooltip="'{{ addslashes(lexicon('tooltip_nav_CloseMenu', 'Close Navigation Menu')) }}'"
                    aria-label="{{ lexicon('tooltip_nav_CloseMenu', 'Close Navigation Menu') }}">
                <svg class="w-icon h-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div data-global-nav="drawer" class="flex flex-col gap-md mt-lg">
                @guest
                    <x-button variant="success"
                              @click="drawerOpen = false; $dispatch('open-auth')"
                              x-tooltip="'{{ addslashes(lexicon('tooltip_auth_OpenLoginModal', 'Log in or register an account')) }}'">
                        {{ lexicon('btn_common_LoginRegister', 'Login / Register') }}
                    </x-button>
                @endguest
                @auth
                    <form method="POST" action="/logout">
                        @csrf
                        <x-button type="submit"
                                  variant="danger"
                                  x-tooltip="'{{ addslashes(lexicon('tooltip_auth_Logout', 'Sign out of your account')) }}'">
                            {{ lexicon('btn_common_Logout', 'Logout') }}
                        </x-button>
                    </form>
                @endauth
                
                @if(isset($drawerNav))
                    @foreach($drawerNav->groupBy('group_name') as $group => $links)
                        @if($group)
                            <div class="flex flex-col gap-md mb-6" x-data="{ expanded: false }">
                                <button type="button"
                                        @click="expanded = !expanded"
                                        class="text-xs font-bold text-muted uppercase tracking-wider border-b border-panel bg-transparent border-none hover-text-h flex flex-row flex-wrap items-center justify-between w-full p-2 cursor-pointer rounded"
                                        x-tooltip="'{{ addslashes(lexicon('tooltip_common_TogglePanel', 'Click to expand or collapse panel')) }}'">
                                    <span>{{ lexicon('label_nav_group_' . \Illuminate\Support\Str::slug($group, '_'), $group) }}</span>
                                    <svg class="w-icon h-icon transition-transform flex-shrink-0" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <div class="flex flex-col gap-sm" x-show="expanded" x-collapse x-cloak>
                                    @foreach($links as $link)
                                        @if($link->url === '/chapters/create')
                                            <button type="button"
                                                    @click="drawerOpen = false; $dispatch('open-chapter-modal')"
                                                    class="font-bold text-lg text-input-muted border-none border-b border-b-table-row hover-text-accent p-2 block w-full text-left bg-transparent cursor-pointer">
                                                {{ lexicon($link->lexicon_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '', $link->lexicon_key))) }}
                                            </button>
                                        @elseif($link->url === '/messenger')
                                            <button type="button"
                                                    @click="drawerOpen = false; $dispatch('open-messenger')"
                                                    class="font-bold text-lg text-input-muted border-none border-b border-b-table-row hover-text-accent p-2 block w-full text-left bg-transparent cursor-pointer">
                                                {{ lexicon($link->lexicon_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '', $link->lexicon_key))) }}
                                            </button>
                                        @else
                                            <a href="{{ $link->url }}" target="{{ $link->target }}" class="font-bold text-lg text-input-muted border-b border-b-table-row hover-text-accent p-2 block no-underline">
                                                {{ lexicon($link->lexicon_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '', $link->lexicon_key))) }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="flex flex-col gap-sm mb-6">
                                @foreach($links as $link)
                                    @if($link->url === '/chapters/create')
                                        <button type="button"
                                                @click="drawerOpen = false; $dispatch('open-chapter-modal')"
                                                class="font-bold text-lg text-input-muted border-none border-b border-b-table-row hover-text-accent p-2 block w-full text-left bg-transparent cursor-pointer">
                                            {{ lexicon($link->lexicon_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '', $link->lexicon_key))) }}
                                        </button>
                                    @elseif($link->url === '/messenger')
                                        <button type="button"
                                                @click="drawerOpen = false; $dispatch('open-messenger')"
                                                class="font-bold text-lg text-input-muted border-none border-b border-b-table-row hover-text-accent p-2 block w-full text-left bg-transparent cursor-pointer">
                                            {{ lexicon($link->lexicon_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '', $link->lexicon_key))) }}
                                        </button>
                                    @else
                                        <a href="{{ $link->url }}" target="{{ $link->target }}" class="font-bold text-lg text-input-muted border-b border-b-table-row hover-text-accent p-2 block no-underline">
                                            {{ lexicon($link->lexicon_key, \Illuminate\Support\Str::headline(str_replace('nav_link_', '', $link->lexicon_key))) }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>