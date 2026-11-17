{{-- resources/views/auth/reset-password.blade.php --}}
@extends('layouts.app')

@section('title', lexicon('label_auth_ResetPassword', 'Reset Password'))

@section('content')
    <div class="max-w-lg mx-auto w-full">
        <x-tab type="accordion"
               :title="lexicon('label_auth_ResetPassword', 'Reset Password')"
               icon="bi-key"
               :open="true">
            <x-meta type="reset-password" :request="$request" />
        </x-tab>
    </div>
@endsection