<!-- resources/views/components/tiptap-toolbar.blade.php -->
<div class="bg-panel-header text-h border-b-panel p-panel-header font-bold flex flex-row flex-wrap gap-sm py-1 items-center" x-show="editor" x-collapse x-cloak x-ref="tiptapToolbar">
    
    <!-- Text Formatting -->
    <button type="button" 
            class="bg-transparent border-none font-bold p-0 cursor-pointer px-2" 
            :class="checkActive('bold') ? 'font-bold text-accent' : 'text-muted hover-text-h'"
            @click="window.Alpine.raw(editor).chain().focus().toggleBold().run()" 
            @mousedown.prevent
            tabindex="-1"
            x-tooltip="'{{ addslashes(lexicon('tooltip_common_Bold', 'Bold')) }}'"
            aria-label="{{ lexicon('tooltip_common_Bold', 'Bold') }}">
        <span class="font-bold">{{ lexicon('btn_common_BoldShort', 'B') }}</span>
    </button>
            
    <button type="button" 
            class="bg-transparent border-none font-bold p-0 cursor-pointer px-2" 
            :class="checkActive('italic') ? 'font-bold italic text-accent' : 'text-muted hover-text-h'" 
            @click="window.Alpine.raw(editor).chain().focus().toggleItalic().run()" 
            @mousedown.prevent
            tabindex="-1"
            x-tooltip="'{{ addslashes(lexicon('tooltip_common_Italic', 'Italic')) }}'"
            aria-label="{{ lexicon('tooltip_common_Italic', 'Italic') }}">
        <span class="italic">{{ lexicon('btn_common_ItalicShort', 'I') }}</span>
    </button>
    
    <span class="text-muted select-none">|</span>
    
    <!-- Headings -->
    <button type="button" 
            class="bg-transparent border-none font-bold p-0 cursor-pointer px-2" 
            :class="checkActive('paragraph') ? 'font-bold text-accent' : 'text-muted hover-text-h'" 
            @click="window.Alpine.raw(editor).chain().focus().setParagraph().run()" 
            @mousedown.prevent
            tabindex="-1"
            x-tooltip="'{{ addslashes(lexicon('tooltip_common_NormalText', 'Normal Text')) }}'"
            aria-label="{{ lexicon('tooltip_common_NormalText', 'Normal Text') }}">
        <span>¶ {{ lexicon('btn_common_NormalText', 'Normal') }}</span>
    </button>
            
    <button type="button" 
            class="bg-transparent border-none font-bold p-0 cursor-pointer px-2" 
            :class="checkActive('heading', { level: 2 }) ? 'font-bold text-accent' : 'text-muted hover-text-h'" 
            @click="window.Alpine.raw(editor).chain().focus().toggleHeading({ level: 2 }).run()" 
            @mousedown.prevent
            tabindex="-1"
            x-tooltip="'{{ addslashes(lexicon('tooltip_common_Heading2', 'Heading 2')) }}'"
            aria-label="{{ lexicon('tooltip_common_Heading2', 'Heading 2') }}">
        <span class="font-bold">{{ lexicon('btn_common_Heading2Short', 'H2') }}</span>
    </button>
            
    <button type="button" 
            class="bg-transparent border-none font-bold p-0 cursor-pointer px-2" 
            :class="checkActive('heading', { level: 3 }) ? 'font-bold text-accent' : 'text-muted hover-text-h'" 
            @click="window.Alpine.raw(editor).chain().focus().toggleHeading({ level: 3 }).run()" 
            @mousedown.prevent
            tabindex="-1"
            x-tooltip="'{{ addslashes(lexicon('tooltip_common_Heading3', 'Heading 3')) }}'"
            aria-label="{{ lexicon('tooltip_common_Heading3', 'Heading 3') }}">
        <span class="font-bold">{{ lexicon('btn_common_Heading3Short', 'H3') }}</span>
    </button>
    
    <span class="text-muted select-none">|</span>
    
    <!-- Lists & Quotes -->
    <button type="button" 
            class="bg-transparent border-none font-bold p-0 cursor-pointer px-2" 
            :class="checkActive('bulletList') ? 'font-bold text-accent' : 'text-muted hover-text-h'" 
            @click="window.Alpine.raw(editor).chain().focus().toggleBulletList().run()" 
            @mousedown.prevent
            tabindex="-1"
            x-tooltip="'{{ addslashes(lexicon('tooltip_common_BulletList', 'Bullet List')) }}'"
            aria-label="{{ lexicon('tooltip_common_BulletList', 'Bullet List') }}">
        <span>• {{ lexicon('btn_common_List', 'List') }}</span>
    </button>
            
    <button type="button" 
            class="bg-transparent border-none font-bold p-0 cursor-pointer px-2" 
            :class="checkActive('orderedList') ? 'font-bold text-accent' : 'text-muted hover-text-h'" 
            @click="window.Alpine.raw(editor).chain().focus().toggleOrderedList().run()" 
            @mousedown.prevent
            tabindex="-1"
            x-tooltip="'{{ addslashes(lexicon('tooltip_common_NumberedList', 'Numbered List')) }}'"
            aria-label="{{ lexicon('tooltip_common_NumberedList', 'Numbered List') }}">
        <span>1. {{ lexicon('btn_common_List', 'List') }}</span>
    </button>
            
    <button type="button" 
            class="bg-transparent border-none font-bold p-0 cursor-pointer px-2" 
            :class="checkActive('blockquote') ? 'font-bold text-accent' : 'text-muted hover-text-h'" 
            @click="window.Alpine.raw(editor).chain().focus().toggleBlockquote().run()" 
            @mousedown.prevent
            tabindex="-1"
            x-tooltip="'{{ addslashes(lexicon('tooltip_common_Quote', 'Quote')) }}'"
            aria-label="{{ lexicon('tooltip_common_Quote', 'Quote') }}">
        <span>""</span>
    </button>
    
    <span class="text-muted select-none">|</span>
    
    <!-- Insertions (Stateless) -->
    <button type="button" 
            class="bg-transparent border-none font-bold p-0 cursor-pointer text-muted hover-text-h px-2" 
            @click="window.Alpine.raw(editor).chain().focus().setHorizontalRule().run()" 
            @mousedown.prevent
            tabindex="-1"
            x-tooltip="'{{ addslashes(lexicon('tooltip_common_Divider', 'Divider')) }}'"
            aria-label="{{ lexicon('tooltip_common_Divider', 'Divider') }}">
        <span>—</span>
    </button>
    <button type="button" 
            class="bg-transparent border-none font-bold p-0 cursor-pointer text-muted hover-text-h px-2" 
            @click="window.Alpine.raw(editor).chain().focus().insertContent('<hr class=\'scene-split-marker\'>').run()" 
            @mousedown.prevent
            tabindex="-1"
            x-tooltip="'{{ addslashes(lexicon('tooltip_common_SceneSplit', 'Scene Split')) }}'"
            aria-label="{{ lexicon('tooltip_common_SceneSplit', 'Scene Split') }}">
        <span><i class="bi bi-scissors"></i> Split</span>
    </button>
</div>