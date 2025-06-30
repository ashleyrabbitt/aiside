@extends('panel.layout.app')
@section('title', 'My Projects')
@section('titlebar_subtitle', 'Track and manage your work contexts')

@section('content')
    <div class="lqd-dashboard-content">
        <div class="lqd-card">
            <div class="lqd-card-header">
                <div class="flex items-center justify-between">
                    <h3 class="lqd-card-title">My Projects</h3>
                    <a href="{{ route('dashboard.contexts.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>New Project
                    </a>
                </div>
            </div>
            <div class="lqd-card-body">
                @if($contexts->isEmpty())
                    <div class="text-center py-12">
                        <i class="fas fa-folder-open text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">No projects yet</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Start tracking your first project to maintain momentum.</p>
                        <a href="{{ route('dashboard.contexts.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Create Your First Project
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($contexts as $context)
                            <div class="lqd-card border">
                                <div class="lqd-card-body">
                                    <div class="mb-2 flex items-start justify-between">
                                        <h4 class="font-semibold text-lg">
                                            <a href="{{ route('dashboard.contexts.show', $context) }}" class="text-primary hover:underline">
                                                {{ $context->title }}
                                            </a>
                                        </h4>
                                        <span class="badge badge-{{ $context->status === 'active' ? 'success' : ($context->status === 'completed' ? 'info' : 'warning') }}">
                                            {{ ucfirst($context->status) }}
                                        </span>
                                    </div>
                                    
                                    @if($context->description)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                            {{ Str::limit($context->description, 100) }}
                                        </p>
                                    @endif
                                    
                                    @if($context->tags)
                                        <div class="mb-3 flex flex-wrap gap-1">
                                            @foreach($context->tags as $tag)
                                                <span class="badge badge-sm badge-neutral">{{ $tag }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    
                                    @if($context->latestEntry)
                                        <div class="mb-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Latest update:</p>
                                            <p class="text-sm">
                                                {{ Str::limit($context->latestEntry->ai_summary ?? $context->latestEntry->notes, 80) }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $context->latestEntry->created_at->diffForHumans() }}
                                            </p>
                                        </div>
                                    @endif
                                    
                                    <div class="flex items-center justify-between text-sm text-gray-500">
                                        <span>{{ $context->entries()->count() }} entries</span>
                                        <span>{{ $context->updated_at->format('M j, Y') }}</span>
                                    </div>
                                    
                                    <div class="mt-4 flex gap-2">
                                        <a href="{{ route('dashboard.contexts.show', $context) }}" class="btn btn-sm btn-primary flex-1">
                                            View Details
                                        </a>
                                        <a href="{{ route('dashboard.contexts.edit', $context) }}" class="btn btn-sm btn-ghost">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-6">
                        {{ $contexts->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection