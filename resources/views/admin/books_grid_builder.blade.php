@extends('layouts.app')

@php
    $bookModel = $systemBook ?? $book ?? null;
@endphp

@section('title', lexicon('admin_books_grid_builder_title', $bookModel->book_title ?? 'Book Grid Builder'))

@section('content')
    <div
        x-data="bookBuilderManager(@js($grids ?? []), @js($modules ?? []), @js($availableModules ?? []))"
        @ajax-success.window="refreshDOM()"
    >
        @if($bookModel && $bookModel->book_url !== '/')
            <x-page-header :title="lexicon('admin_books_grid_builder_title', $bookModel->book_title)" />
        @endif

        <div
            id="pb-fresh-data"
            class="hidden"
            data-grids='@json($grids ?? [])'
            data-modules='@json($availableModules ?? [])'
        ></div>

        @if($bookModel)
            <x-grid-wrapper :book="$bookModel" />
        @endif
    </div>
@endsection