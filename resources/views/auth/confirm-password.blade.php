{{-- resources/views/auth/confirm-password.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('label_common_ConfirmPassword', 'Confirm Password'))

@section('content')
    <div class="max-w-lg mx-auto w-full">
        <x-tab type="accordion"
               :title="lexicon('label_auth_SecureArea', 'Secure Area')"
               icon="bi-shield-lock"
               :open="true">
            <x-meta type="confirm-password" />
        </x-tab>
    </div>
@endsection