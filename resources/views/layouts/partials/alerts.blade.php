{{-- resources/views/layouts/partials/alerts.blade.php --}}
@if(session('success'))
    <div x-data
         x-init="$nextTick(() => { if (window.Swal && typeof window.Swal.toast === 'function') { window.Swal.toast('success', '{{ addslashes(e(session('success'))) }}'); } })"></div>
@elseif(session('status'))
    <div x-data
         x-init="$nextTick(() => { if (window.Swal && typeof window.Swal.toast === 'function') { window.Swal.toast('info', '{{ addslashes(e(session('status'))) }}'); } })"></div>
@elseif(session('error'))
    <div x-data
         x-init="$nextTick(() => { if (window.Swal && typeof window.Swal.error === 'function') { window.Swal.error('{{ addslashes(lexicon('label_common_Error', 'Error')) }}', '{{ addslashes(e(session('error'))) }}'); } })"></div>
@elseif($errors->any())
    <div x-data
         x-init="$nextTick(() => { if (window.Swal && typeof window.Swal.error === 'function') { window.Swal.error('{{ addslashes(lexicon('label_common_ValidationError', 'Validation Error')) }}', '{{ addslashes(implode(' ', array_map('e', $errors->all()))) }}'); } })"></div>
@endif