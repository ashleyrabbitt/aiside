@extends('panel.layout.app')
@section('title', 'Create New Project')
@section('titlebar_subtitle', 'Start tracking a new work context')

@section('content')
    <div class="lqd-dashboard-content">
        <div class="mx-auto max-w-2xl">
            <form action="{{ route('dashboard.contexts.store') }}" method="POST">
                @csrf
                <div class="lqd-card">
                    <div class="lqd-card-header">
                        <h3 class="lqd-card-title">New Project Details</h3>
                    </div>
                    <div class="lqd-card-body">
                        <div class="mb-6">
                            <label for="title" class="lqd-label required">Project Title</label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                class="lqd-input @error('title') is-invalid @enderror" 
                                value="{{ old('title') }}"
                                placeholder="e.g., Launch Online Course, Build SaaS MVP"
                                required
                            >
                            @error('title')
                                <div class="lqd-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="description" class="lqd-label">Project Description</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                rows="4" 
                                class="lqd-textarea @error('description') is-invalid @enderror"
                                placeholder="Describe what you're working on and your goals..."
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="lqd-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="tags" class="lqd-label">Tags</label>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Add tags to help categorize your project (press Enter after each tag)
                            </div>
                            <input 
                                type="text" 
                                id="tags-input" 
                                class="lqd-input"
                                placeholder="Type and press Enter to add tags"
                            >
                            <div id="tags-container" class="mt-2 flex flex-wrap gap-2"></div>
                            <input type="hidden" name="tags" id="tags" value="{{ old('tags') ? json_encode(old('tags')) : '[]' }}">
                            @error('tags')
                                <div class="lqd-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="lqd-card-footer">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('dashboard.contexts.index') }}" class="btn btn-ghost">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Create Project
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tagsInput = document.getElementById('tags-input');
    const tagsContainer = document.getElementById('tags-container');
    const tagsHidden = document.getElementById('tags');
    
    let tags = [];
    try {
        tags = JSON.parse(tagsHidden.value) || [];
    } catch (e) {
        tags = [];
    }
    
    // Display existing tags
    tags.forEach(tag => addTagToUI(tag));
    
    tagsInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const tag = this.value.trim();
            if (tag && !tags.includes(tag)) {
                tags.push(tag);
                addTagToUI(tag);
                updateHiddenInput();
                this.value = '';
            }
        }
    });
    
    function addTagToUI(tag) {
        const tagEl = document.createElement('span');
        tagEl.className = 'badge badge-primary';
        tagEl.innerHTML = `
            ${tag}
            <button type="button" class="ml-2" onclick="removeTag('${tag}')">
                <i class="fas fa-times"></i>
            </button>
        `;
        tagsContainer.appendChild(tagEl);
    }
    
    window.removeTag = function(tag) {
        tags = tags.filter(t => t !== tag);
        updateHiddenInput();
        renderTags();
    }
    
    function renderTags() {
        tagsContainer.innerHTML = '';
        tags.forEach(tag => addTagToUI(tag));
    }
    
    function updateHiddenInput() {
        tagsHidden.value = JSON.stringify(tags);
    }
});
</script>
@endpush