@extends('layouts.app')

@php
    $pageModel = $systemPage ?? $page;
    $lookupUrl = $pageModel->slug === '/' ? '/' : '/' . ltrim($pageModel->slug, '/');
    
    $navKey = \Illuminate\Support\Facades\DB::table('navigation_links')
        ->where('url', $lookupUrl)
        ->value('lexicon_key');

    $pageKey = $navKey ?: ('public_label_' . ($pageModel->slug === '/' ? 'Landing' : \Illuminate\Support\Str::studly($pageModel->slug)));
@endphp

@section('title', lexicon($pageKey, $pageModel->title))

@section('content')
    <div x-data="{}" @ajax-success.window="window.refreshDOM()">
        @if($pageModel->slug !== '/')
            <x-page-header :title="lexicon($pageKey, $pageModel->title)" />
        @endif

        <x-admin-grid-wrapper
            :page="$pageModel"
            :newsItems="$newsItems ?? null"
            :storyPosts="$storyPosts ?? null"
        />
    </div>
@endsection