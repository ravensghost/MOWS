@if(session('success'))
    <div x-data="sessionAlert('success', '{{ addslashes(lexicon('label_common_Success', 'Success!')) }}', '{{ addslashes(e(session('success'))) }}')"></div>
@elseif(session('status'))
    <div x-data="sessionAlert('info', '{{ addslashes(lexicon('label_common_Info', 'Info')) }}', '{{ addslashes(e(session('status'))) }}')"></div>
@elseif(session('error'))
    <div x-data="sessionAlert('error', '{{ addslashes(lexicon('label_common_Error', 'Error')) }}', '{{ addslashes(e(session('error'))) }}')"></div>
@elseif($errors->any())
    <div x-data="sessionAlert('error', '{{ addslashes(lexicon('label_common_ValidationError', 'Validation Error')) }}', '{{ addslashes(implode('<br>', array_map('e', $errors->all()))) }}')"></div>
@endif