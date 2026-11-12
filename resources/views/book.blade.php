@extends('layouts.app')

@php
    $bookModel = $systemBook ?? $book;
@endphp

@section('title', lexicon($bookKey ?? 'book_title', $bookModel->book_title))

@section('content')
    <div x-data="{}" @ajax-success.window="window.refreshDOM()">
        @if($bookModel->book_url !== '/')
            <x-page-header :title="lexicon($bookKey ?? 'book_title', $bookModel->book_title)" />
        @endif

        <x-books-grid-wrapper
            :book="$bookModel"
            :newsItems="$newsItems ?? null"
            :storyPosts="$storyPosts ?? null"
        />
    </div>
@endsection