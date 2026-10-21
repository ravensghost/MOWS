@extends('layouts.app')

@section('title', 'Wiki Encyclopedia Manager')

@section('content')
<div class="container mx-auto p-4" x-data="{ openModal: false, editMode: false, currentId: null, title: '', category: 'General', content: '', is_visible: true }">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-h">Wiki Encyclopedia Manager</h1>
        <button @click="openModal = true; editMode = false; title = ''; category = 'General'; content = ''; is_visible = true;" 
                class="bg-accent text-white px-4 py-2 rounded font-semibold">
            Create Article
        </button>
    </div>

    <div class="bg-panel border-panel rounded-panel shadow-panel overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-panel text-muted text-xs uppercase">
                    <th class="py-3 px-4">Title</th>
                    <th class="py-3 px-4">Category</th>
                    <th class="py-3 px-4 text-center">Status</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-panel text-sm">
                @forelse($articles as$article)
                    <tr>
                        <td class="py-3 px-4 font-medium">{{ $article->title }}</td>
                        <td class="py-3 px-4 text-muted">{{ $article->category }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-1 text-xs rounded {{ $article->is_visible ? 'bg-success-muted text-success' : 'bg-muted text-muted-foreground' }}">
                                {{ $article->is_visible ? 'Published' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <button @click="openModal = true; editMode = true; currentId = {{ $article->id }}; title = '{{ addslashes($article->title) }}'; category = '{{ addslashes($article->category) }}'; content = `{!! addslashes($article->content) !!}`; is_visible = {{ $article->is_visible ? 'true' : 'false' }}"
                                    class="text-accent mr-2">Edit</button>
                            <button @click="if(confirm('Delete article?')) fetch('/admin/wiki/{{ $article->id }}', {method: 'DELETE', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}}).then(() => location.reload())" 
                                    class="text-danger">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-muted italic">No wiki articles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection