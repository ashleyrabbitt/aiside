@extends('panel.layout.app')
@section('title', $context->title)
@section('titlebar_subtitle', 'Project Details and Progress')

@section('content')
    <div class="lqd-dashboard-content">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Add Entry Form -->
                <div class="lqd-card mb-6">
                    <div class="lqd-card-header">
                        <h3 class="lqd-card-title">Add Progress Entry</h3>
                    </div>
                    <form action="{{ route('dashboard.contexts.add-entry', $context) }}" method="POST" id="add-entry-form">
                        @csrf
                        <div class="lqd-card-body">
                            <div class="mb-4">
                                <label for="notes" class="lqd-label required">What did you work on?</label>
                                <textarea 
                                    id="notes" 
                                    name="notes" 
                                    rows="4" 
                                    class="lqd-textarea @error('notes') is-invalid @enderror"
                                    placeholder="Describe what you accomplished, challenges faced, and next steps..."
                                    required
                                >{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="lqd-invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="lqd-card-footer">
                            <button type="submit" class="btn btn-primary" id="submit-entry">
                                <i class="fas fa-plus mr-2"></i>Add Entry & Generate Summary
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Entries List -->
                <div class="lqd-card">
                    <div class="lqd-card-header">
                        <h3 class="lqd-card-title">Progress Timeline</h3>
                    </div>
                    <div class="lqd-card-body">
                        @if($entries->isEmpty())
                            <p class="text-center text-gray-500 py-8">No entries yet. Add your first progress update above!</p>
                        @else
                            <div class="space-y-4">
                                @foreach($entries as $entry)
                                    <div class="border-l-4 border-primary pl-4 ml-2">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $entry->timestamp->format('M j, Y g:i A') }}
                                                </p>
                                                <div class="mt-2">
                                                    <h4 class="font-semibold mb-1">Your Notes:</h4>
                                                    <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $entry->notes }}</p>
                                                </div>
                                                @if($entry->ai_summary)
                                                    <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                                        <h4 class="font-semibold mb-1 flex items-center">
                                                            <i class="fas fa-robot mr-2"></i>AI Summary:
                                                            @if($entry->ai_confidence)
                                                                <span class="ml-2 badge badge-sm badge-{{ $entry->ai_confidence === 'high' ? 'success' : ($entry->ai_confidence === 'medium' ? 'warning' : 'error') }}">
                                                                    {{ ucfirst($entry->ai_confidence) }} confidence
                                                                </span>
                                                            @endif
                                                        </h4>
                                                        <p class="text-gray-700 dark:text-gray-300">{{ $entry->ai_summary }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <div class="mt-6">
                                {{ $entries->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Project Info -->
                <div class="lqd-card mb-6">
                    <div class="lqd-card-header">
                        <div class="flex items-center justify-between">
                            <h3 class="lqd-card-title">Project Info</h3>
                            <a href="{{ route('dashboard.contexts.edit', $context) }}" class="btn btn-ghost btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                    <div class="lqd-card-body">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="mt-1">
                                    <span class="badge badge-{{ $context->status === 'active' ? 'success' : ($context->status === 'completed' ? 'info' : 'warning') }}">
                                        {{ ucfirst($context->status) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="mt-1 text-sm">{{ $context->created_at->format('M j, Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Total Entries</dt>
                                <dd class="mt-1 text-sm">{{ $context->entries()->count() }}</dd>
                            </div>
                            @if($context->tags)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Tags</dt>
                                    <dd class="mt-1 flex flex-wrap gap-1">
                                        @foreach($context->tags as $tag)
                                            <span class="badge badge-sm badge-neutral">{{ $tag }}</span>
                                        @endforeach
                                    </dd>
                                </div>
                            @endif
                            @if($context->description)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                                    <dd class="mt-1 text-sm">{{ $context->description }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                <!-- Next Steps -->
                @if($nextSteps && $nextSteps['success'])
                    <div class="lqd-card">
                        <div class="lqd-card-header">
                            <h3 class="lqd-card-title">
                                <i class="fas fa-lightbulb mr-2"></i>Suggested Next Steps
                            </h3>
                        </div>
                        <div class="lqd-card-body">
                            <div class="prose prose-sm max-w-none">
                                {!! nl2br(e($nextSteps['next_steps'])) !!}
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Quick Actions -->
                <div class="lqd-card mt-6">
                    <div class="lqd-card-header">
                        <h3 class="lqd-card-title">Actions</h3>
                    </div>
                    <div class="lqd-card-body space-y-2">
                        <a href="{{ route('dashboard.contexts.edit', $context) }}" class="btn btn-outline btn-block">
                            <i class="fas fa-edit mr-2"></i>Edit Project
                        </a>
                        <form action="{{ route('dashboard.contexts.destroy', $context) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this project?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline btn-error btn-block">
                                <i class="fas fa-trash mr-2"></i>Delete Project
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('add-entry-form');
    const submitBtn = document.getElementById('submit-entry');
    
    form.addEventListener('submit', function() {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
    });
});
</script>
@endpush