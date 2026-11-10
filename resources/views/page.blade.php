@extends('layouts.app')

@php
    $pageModel = $systemPage ?? $page;
@endphp

@section('title', lexicon($pageKey, $pageModel->books_title))

@section('content')
    <div x-data="{}" @ajax-success.window="window.refreshDOM()">
        @if($pageModel->books_uri !== '/')
            <x-page-header :title="lexicon($pageKey, $pageModel->books_title)" />
        @endif

        <x-admin-grid-wrapper
            :page="$pageModel"
            :newsItems="$newsItems ?? null"
            :storyPosts="$storyPosts ?? null"
        />
    </div>
@endsection