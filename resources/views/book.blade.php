@extends('layouts.app')

@section('title', lexicon($bookKey ?? 'book_title', $book->book_title))

@section('content')
    <div x-data="{}" @ajax-success.window="window.refreshDOM()">
        @if($book->book_url !== '/')
            <x-page-header :title="lexicon($bookKey ?? 'book_title', $book->book_title)" />
        @endif

        <x-grid-wrapper
            :book="$book"
            :newsItems="$bulletins ?? null"
            :storyPosts="$folios ?? null"
        />
    </div>
@endsection