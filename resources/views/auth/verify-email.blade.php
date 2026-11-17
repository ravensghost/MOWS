{{-- resources/views/auth/verify-email.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('label_auth_VerifyEmail', 'Verify Your Email'))

@section('content')
    <div class="max-w-lg mx-auto w-full">
        <x-tab type="accordion"
               :title="lexicon('label_auth_VerifyEmail', 'Verify Your Email')"
               icon="bi-envelope-check"
               :open="true">
            <x-meta type="verify-email" />
        </x-tab>
    </div>
@endsection