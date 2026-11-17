{{-- resources/views/book.blade.php --}}
@extends('layouts.app')

@section('title', lexicon($bookKey ?? 'book_title', $book->book_title))

@section('content')
    <div x-data="{}"
         x-ref="bookContentWrapper"
         @ajax-success.window="if (window.updateDOM) { window.updateDOM($refs, ['bookContentWrapper'], null, false); }">
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