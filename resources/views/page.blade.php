@extends('layouts.app')

@php
    $pageModel = $systemPage ?? $page;
    $lookupUrl = $pageModel->books_uri === '/' ? '/' : '/' . ltrim($pageModel->books_uri, '/');
    
    $navKey = \Illuminate\Support\Facades\DB::table('navigation_links')
        ->where('url', $lookupUrl)
        ->value('lexicon_key');

    $pageKey = $navKey ?: ('public_label_' . ($pageModel->books_uri === '/' ? 'Landing' : \Illuminate\Support\Str::studly($pageModel->slug)));
@endphp

@section('title', lexicon($pageKey, $pageModel->title))

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