{{-- resources/views/components/tt-tb.blade.php --}}
@props([
    'editor' => 'editor',
    'name' => null,
    'label' => null,
])

<div class="editor-container overflow-hidden flex flex-col w-full border-panel border-rounded bg-panel-body">
    @if($label)
        <label class="text-xs font-bold text-input-muted uppercase text-wider p-2 border-b-panel">{{ $label }}</label>
    @endif

    <div class="bg-panel-header border-b-panel p-2 flex flex-row flex-wrap items-center gap-xs"
         x-show="{{ $editor }}"
         x-collapse
         x-cloak
         x-ref="tiptapToolbar">

        <!-- Text Formatting -->
        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('bold') ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleBold().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Bold', 'Bold')) }}'"
                title="{{ lexicon('tooltip_common_Bold', 'Bold') }}"
                aria-label="{{ lexicon('tooltip_common_Bold', 'Bold') }}">
            <span class="font-bold">{{ lexicon('btn_common_BoldShort', 'B') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('italic') ? 'font-bold font-italic text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleItalic().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Italic', 'Italic')) }}'"
                title="{{ lexicon('tooltip_common_Italic', 'Italic') }}"
                aria-label="{{ lexicon('tooltip_common_Italic', 'Italic') }}">
            <span class="font-italic">{{ lexicon('btn_common_ItalicShort', 'I') }}</span>
        </button>

        <span class="text-muted select-none">|</span>

        <!-- Headings -->
        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('paragraph') ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().setParagraph().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_NormalText', 'Normal Text')) }}'"
                title="{{ lexicon('tooltip_common_NormalText', 'Normal Text') }}"
                aria-label="{{ lexicon('tooltip_common_NormalText', 'Normal Text') }}">
            <span>¶ {{ lexicon('btn_common_NormalText', 'Normal') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('heading', { level: 2 }) ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleHeading({ level: 2 }).run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Heading2', 'Heading 2')) }}'"
                title="{{ lexicon('tooltip_common_Heading2', 'Heading 2') }}"
                aria-label="{{ lexicon('tooltip_common_Heading2', 'Heading 2') }}">
            <span class="font-bold">{{ lexicon('btn_common_Heading2Short', 'H2') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('heading', { level: 3 }) ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleHeading({ level: 3 }).run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Heading3', 'Heading 3')) }}'"
                title="{{ lexicon('tooltip_common_Heading3', 'Heading 3') }}"
                aria-label="{{ lexicon('tooltip_common_Heading3', 'Heading 3') }}">
            <span class="font-bold">{{ lexicon('btn_common_Heading3Short', 'H3') }}</span>
        </button>

        <span class="text-muted select-none">|</span>

        <!-- Lists & Quotes -->
        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('bulletList') ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleBulletList().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_BulletList', 'Bullet List')) }}'"
                title="{{ lexicon('tooltip_common_BulletList', 'Bullet List') }}"
                aria-label="{{ lexicon('tooltip_common_BulletList', 'Bullet List') }}">
            <span>• {{ lexicon('btn_common_List', 'List') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('orderedList') ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleOrderedList().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_NumberedList', 'Numbered List')) }}'"
                title="{{ lexicon('tooltip_common_NumberedList', 'Numbered List') }}"
                aria-label="{{ lexicon('tooltip_common_NumberedList', 'Numbered List') }}">
            <span>1. {{ lexicon('btn_common_List', 'List') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('blockquote') ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleBlockquote().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Quote', 'Quote')) }}'"
                title="{{ lexicon('tooltip_common_Quote', 'Quote') }}"
                aria-label="{{ lexicon('tooltip_common_Quote', 'Quote') }}">
            <span>""</span>
        </button>

        <span class="text-muted select-none">|</span>

        <!-- Insertions & Authoring Suite Tools -->
        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-muted hover-text-h px-2"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().setHorizontalRule().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Divider', 'Divider')) }}'"
                title="{{ lexicon('tooltip_common_Divider', 'Divider') }}"
                aria-label="{{ lexicon('tooltip_common_Divider', 'Divider') }}">
            <span>—</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-muted hover-text-h px-2 flex flex-row items-center gap-xs"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().insertContent('<hr class=\'scene-split-marker\'>').run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_SceneSplit', 'Scene Split')) }}'"
                title="{{ lexicon('tooltip_common_SceneSplit', 'Scene Split') }}"
                aria-label="{{ lexicon('tooltip_common_SceneSplit', 'Scene Split') }}">
            <i class="bi bi-scissors"></i>
            <span>{{ lexicon('btn_common_Split', 'Split') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-muted hover-text-h px-2 flex flex-row items-center gap-xs"
                @click="typeof openCommsTemplate === 'function' && openCommsTemplate()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_Editor_CommsTemplate', 'Insert In-Universe Communication Header')) }}'"
                title="{{ lexicon('tooltip_Editor_CommsTemplate', 'Insert In-Universe Communication Header') }}"
                aria-label="{{ lexicon('tooltip_Editor_CommsTemplate', 'Insert In-Universe Communication Header') }}">
            <i class="bi bi-broadcast"></i>
            <span>{{ lexicon('btn_Editor_Comms', 'Comms') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-muted hover-text-h px-2 flex flex-row items-center gap-xs"
                @click="typeof tagDialectSpan === 'function' && tagDialectSpan()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_Editor_DialectTag', 'Tag selected text with In-Universe Conlang Dialect')) }}'"
                title="{{ lexicon('tooltip_Editor_DialectTag', 'Tag selected text with In-Universe Conlang Dialect') }}"
                aria-label="{{ lexicon('tooltip_Editor_DialectTag', 'Tag selected text with In-Universe Conlang Dialect') }}">
            <i class="bi bi-translate"></i>
            <span>{{ lexicon('btn_Editor_Dialect', 'Dialect') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-muted hover-text-h px-2 flex flex-row items-center gap-xs"
                @click="typeof rollDiceOrTarot === 'function' && rollDiceOrTarot()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_Editor_DiceRoller', 'Insert Server-Signed Dice or Tarot Roll')) }}'"
                title="{{ lexicon('tooltip_Editor_DiceRoller', 'Insert Server-Signed Dice or Tarot Roll') }}"
                aria-label="{{ lexicon('tooltip_Editor_DiceRoller', 'Insert Server-Signed Dice or Tarot Roll') }}">
            <i class="bi bi-dice-5"></i>
            <span>{{ lexicon('btn_Editor_Roll', 'Roll') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-accent hover-text-h px-2 flex flex-row items-center gap-xs"
                @click="typeof proofreadContent === 'function' && proofreadContent()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_Editor_AiProofread', 'Run Lore-Aware AI Proofreader')) }}'"
                title="{{ lexicon('tooltip_Editor_AiProofread', 'Run Lore-Aware AI Proofreader') }}"
                aria-label="{{ lexicon('tooltip_Editor_AiProofread', 'Run Lore-Aware AI Proofreader') }}">
            <i class="bi bi-stars"></i>
            <span>{{ lexicon('btn_Editor_Proofread', 'Proofread') }}</span>
        </button>
    </div>

    <div x-ref="editorContainer" class="editor-content p-3 w-full overflow-y-auto flex-grow"></div>

    @if($name)
        <input type="hidden" name="{{ $name }}" x-model="content">
    @endif
</div>{{-- resources/views/components/tt-tb.blade.php --}}
@props([
    'editor' => 'editor',
    'name' => null,
    'label' => null,
])

<div class="editor-container overflow-hidden flex flex-col w-full border-panel border-rounded bg-panel-body">
    @if($label)
        <label class="text-xs font-bold text-input-muted uppercase text-wider p-2 border-b-panel">{{ $label }}</label>
    @endif

    <div class="bg-panel-header border-b-panel p-2 flex flex-row flex-wrap items-center gap-xs"
         x-show="{{ $editor }}"
         x-collapse
         x-cloak
         x-ref="tiptapToolbar">

        <!-- Custom Web Font (.woff2) Selector -->
        <select x-model="selectedFont"
                @change="typeof setFontFamily === 'function' && setFontFamily($event.target.value)"
                class="h-input p-1 bg-input text-input border-input border-rounded-sm text-xs cursor-pointer transition-colors hover-border-accent"
                x-tooltip="'{{ addslashes(lexicon('tooltip_Editor_SelectFont', 'Select Typography / In-Universe Font')) }}'"
                title="{{ lexicon('tooltip_Editor_SelectFont', 'Select Typography / In-Universe Font') }}"
                aria-label="{{ lexicon('tooltip_Editor_SelectFont', 'Select Typography / In-Universe Font') }}">
            <option value="">{{ lexicon('label_Editor_DefaultFont', 'Default Font') }}</option>
            <option value="OpenDyslexic">OpenDyslexic</option>
            <option value="StarJedi">Star Jedi</option>
            <option value="Dovahkiin">Dovahkiin</option>
            <option value="Daedra">Daedra</option>
            <option value="ElderFuthark">Elder Futhark Runes</option>
            <option value="VikingMiddleRunes">Viking Middle Runes</option>
            <option value="ElvishRing">Elvish Ring</option>
            <option value="ComicRunes">Comic Runes</option>
            <option value="XCrypt">XCrypt Cipher</option>
        </select>

        <span class="text-muted select-none">|</span>

        <!-- Text Formatting -->
        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('bold') ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleBold().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Bold', 'Bold')) }}'"
                title="{{ lexicon('tooltip_common_Bold', 'Bold') }}"
                aria-label="{{ lexicon('tooltip_common_Bold', 'Bold') }}">
            <span class="font-bold">{{ lexicon('btn_common_BoldShort', 'B') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('italic') ? 'font-bold font-italic text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleItalic().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Italic', 'Italic')) }}'"
                title="{{ lexicon('tooltip_common_Italic', 'Italic') }}"
                aria-label="{{ lexicon('tooltip_common_Italic', 'Italic') }}">
            <span class="font-italic">{{ lexicon('btn_common_ItalicShort', 'I') }}</span>
        </button>

        <span class="text-muted select-none">|</span>

        <!-- Headings -->
        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('paragraph') ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().setParagraph().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_NormalText', 'Normal Text')) }}'"
                title="{{ lexicon('tooltip_common_NormalText', 'Normal Text') }}"
                aria-label="{{ lexicon('tooltip_common_NormalText', 'Normal Text') }}">
            <span>¶ {{ lexicon('btn_common_NormalText', 'Normal') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('heading', { level: 2 }) ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleHeading({ level: 2 }).run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Heading2', 'Heading 2')) }}'"
                title="{{ lexicon('tooltip_common_Heading2', 'Heading 2') }}"
                aria-label="{{ lexicon('tooltip_common_Heading2', 'Heading 2') }}">
            <span class="font-bold">{{ lexicon('btn_common_Heading2Short', 'H2') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('heading', { level: 3 }) ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleHeading({ level: 3 }).run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Heading3', 'Heading 3')) }}'"
                title="{{ lexicon('tooltip_common_Heading3', 'Heading 3') }}"
                aria-label="{{ lexicon('tooltip_common_Heading3', 'Heading 3') }}">
            <span class="font-bold">{{ lexicon('btn_common_Heading3Short', 'H3') }}</span>
        </button>

        <span class="text-muted select-none">|</span>

        <!-- Lists & Quotes -->
        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('bulletList') ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleBulletList().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_BulletList', 'Bullet List')) }}'"
                title="{{ lexicon('tooltip_common_BulletList', 'Bullet List') }}"
                aria-label="{{ lexicon('tooltip_common_BulletList', 'Bullet List') }}">
            <span>• {{ lexicon('btn_common_List', 'List') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('orderedList') ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleOrderedList().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_NumberedList', 'Numbered List')) }}'"
                title="{{ lexicon('tooltip_common_NumberedList', 'Numbered List') }}"
                aria-label="{{ lexicon('tooltip_common_NumberedList', 'Numbered List') }}">
            <span>1. {{ lexicon('btn_common_List', 'List') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer px-2 border-rounded-sm"
                :class="checkActive('blockquote') ? 'font-bold text-accent bg-panel-sub' : 'text-muted hover-text-h'"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().toggleBlockquote().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Quote', 'Quote')) }}'"
                title="{{ lexicon('tooltip_common_Quote', 'Quote') }}"
                aria-label="{{ lexicon('tooltip_common_Quote', 'Quote') }}">
            <span>""</span>
        </button>

        <span class="text-muted select-none">|</span>

        <!-- Insertions & Authoring Suite Tools -->
        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-muted hover-text-h px-2"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().setHorizontalRule().run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_Divider', 'Divider')) }}'"
                title="{{ lexicon('tooltip_common_Divider', 'Divider') }}"
                aria-label="{{ lexicon('tooltip_common_Divider', 'Divider') }}">
            <span>—</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-muted hover-text-h px-2 flex flex-row items-center gap-xs"
                @click="window.Alpine.raw({{ $editor }}).chain().focus().insertContent('<hr class=\'scene-split-marker\'>').run()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_common_SceneSplit', 'Scene Split')) }}'"
                title="{{ lexicon('tooltip_common_SceneSplit', 'Scene Split') }}"
                aria-label="{{ lexicon('tooltip_common_SceneSplit', 'Scene Split') }}">
            <i class="bi bi-scissors"></i>
            <span>{{ lexicon('btn_common_Split', 'Split') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-muted hover-text-h px-2 flex flex-row items-center gap-xs"
                @click="typeof openCommsTemplate === 'function' && openCommsTemplate()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_Editor_CommsTemplate', 'Insert In-Universe Communication Header')) }}'"
                title="{{ lexicon('tooltip_Editor_CommsTemplate', 'Insert In-Universe Communication Header') }}"
                aria-label="{{ lexicon('tooltip_Editor_CommsTemplate', 'Insert In-Universe Communication Header') }}">
            <i class="bi bi-broadcast"></i>
            <span>{{ lexicon('btn_Editor_Comms', 'Comms') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-muted hover-text-h px-2 flex flex-row items-center gap-xs"
                @click="typeof tagDialectSpan === 'function' && tagDialectSpan()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_Editor_DialectTag', 'Tag selected text with In-Universe Conlang Dialect')) }}'"
                title="{{ lexicon('tooltip_Editor_DialectTag', 'Tag selected text with In-Universe Conlang Dialect') }}"
                aria-label="{{ lexicon('tooltip_Editor_DialectTag', 'Tag selected text with In-Universe Conlang Dialect') }}">
            <i class="bi bi-translate"></i>
            <span>{{ lexicon('btn_Editor_Dialect', 'Dialect') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-muted hover-text-h px-2 flex flex-row items-center gap-xs"
                @click="typeof rollDiceOrTarot === 'function' && rollDiceOrTarot()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_Editor_DiceRoller', 'Insert Server-Signed Dice or Tarot Roll')) }}'"
                title="{{ lexicon('tooltip_Editor_DiceRoller', 'Insert Server-Signed Dice or Tarot Roll') }}"
                aria-label="{{ lexicon('tooltip_Editor_DiceRoller', 'Insert Server-Signed Dice or Tarot Roll') }}">
            <i class="bi bi-dice-5"></i>
            <span>{{ lexicon('btn_Editor_Roll', 'Roll') }}</span>
        </button>

        <button type="button"
                class="bg-transparent border-none font-bold p-1 cursor-pointer text-accent hover-text-h px-2 flex flex-row items-center gap-xs"
                @click="typeof proofreadContent === 'function' && proofreadContent()"
                @mousedown.prevent
                tabindex="-1"
                x-tooltip="'{{ addslashes(lexicon('tooltip_Editor_AiProofread', 'Run Lore-Aware AI Proofreader')) }}'"
                title="{{ lexicon('tooltip_Editor_AiProofread', 'Run Lore-Aware AI Proofreader') }}"
                aria-label="{{ lexicon('tooltip_Editor_AiProofread', 'Run Lore-Aware AI Proofreader') }}">
            <i class="bi bi-stars"></i>
            <span>{{ lexicon('btn_Editor_Proofread', 'Proofread') }}</span>
        </button>
    </div>

    <div x-ref="editorContainer" class="editor-content p-3 w-full overflow-y-auto flex-grow"></div>

    @if($name)
        <input type="hidden" name="{{ $name }}" x-model="content">
    @endif
</div>