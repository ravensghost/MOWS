<div class="mt-4 border border-panel rounded-panel bg-panel-sub p-sm text-xs flex flex-col gap-xs"
     x-data="{ showDebug: true }">
    <div class="flex items-center justify-between cursor-pointer" @click="showDebug = !showDebug">
        <span class="font-bold text-muted flex items-center gap-xs">
            <i class="bi bi-bug text-accent"></i> Auto-Save Debugger Console
        </span>
        <button type="button" class="text-muted hover-text-h bg-transparent border-none cursor-pointer">
            <i class="bi" :class="showDebug ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
    </div>

    <div x-show="showDebug" class="flex flex-col gap-xs pt-2 border-t border-panel">
        <div class="flex items-center justify-between text-muted">
            <span>Status: <strong x-text="saving ? 'Saving...' : 'Idle'" :class="saving ? 'text-warning' : 'text-success'"></strong></span>
            <span>Last Saved: <strong x-text="lastSaved || 'None'"></strong></span>
            <button type="button" 
                    @click="triggerAutoSave()" 
                    class="px-2 py-0.5 bg-accent text-white rounded cursor-pointer border-none text-2xs font-bold hover-opacity-90">
                Test Now
            </button>
        </div>

        <div class="bg-input text-input p-2 rounded max-h-36 overflow-y-auto font-mono text-2xs flex flex-col gap-1">
            <template x-for="log in logs" :key="log.timestamp + log.message">
                <div class="flex items-start gap-xs border-b border-panel pb-1 last:border-b-0">
                    <span class="text-muted" x-text="log.timestamp"></span>
                    <span :class="{
                        'text-success': log.type === 'success',
                        'text-warning': log.type === 'warning',
                        'text-danger': log.type === 'error',
                        'text-input': log.type === 'info'
                    }" x-text="log.message"></span>
                </div>
            </template>
            <template x-if="logs.length === 0">
                <div class="text-muted italic">No auto-save events recorded yet. Type in the editor or click "Test Now".</div>
            </template>
        </div>
    </div>
</div>