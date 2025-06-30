@extends('panel.layout.app')
@section('title', 'AI Side Hustle Dashboard')
@section('titlebar_subtitle', 'Manage your projects and business ideas')

@section('content')
    <div class="lqd-dashboard-content">
        @if(!$hasPreferences)
            <div class="col-span-full mb-6">
                <div class="lqd-notice lqd-notice-info">
                    <h3 class="lqd-notice-title">Welcome to AI Side Hustle!</h3>
                    <p>Set up your preferences to get personalized recommendations.</p>
                    <a href="{{ route('dashboard.ai-side-hustle.preferences') }}" class="btn btn-primary btn-sm mt-2">
                        Set Preferences
                    </a>
                </div>
            </div>
        @endif

        <!-- Daily Focus Section -->
        @if($dailyFocus['success'] ?? false)
            <div class="col-span-full mb-6">
                <div class="lqd-card">
                    <div class="lqd-card-header">
                        <h3 class="lqd-card-title">Today's Focus</h3>
                    </div>
                    <div class="lqd-card-body">
                        <div class="prose max-w-none">
                            {!! nl2br(e($dailyFocus['daily_focus']['top_goal'] ?? '')) !!}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Active Contexts -->
            <div class="lqd-card">
                <div class="lqd-card-header">
                    <h3 class="lqd-card-title">Active Projects</h3>
                    <a href="{{ route('dashboard.contexts.create') }}" class="btn btn-outline btn-sm">
                        <i class="fas fa-plus"></i> New Project
                    </a>
                </div>
                <div class="lqd-card-body">
                    @if($activeContexts->isEmpty())
                        <p class="text-muted text-center py-4">No active projects yet. Start your first one!</p>
                    @else
                        <div class="space-y-4">
                            @foreach($activeContexts as $context)
                                <div class="border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="font-semibold">
                                                <a href="{{ route('dashboard.contexts.show', $context) }}" class="text-primary hover:underline">
                                                    {{ $context->title }}
                                                </a>
                                            </h4>
                                            @if($context->latestEntry && $context->latestEntry->ai_summary)
                                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ Str::limit($context->latestEntry->ai_summary, 100) }}
                                                </p>
                                            @endif
                                            <p class="mt-2 text-xs text-gray-500">
                                                Updated {{ $context->updated_at->diffForHumans() }}
                                            </p>
                                        </div>
                                        <div class="ml-4">
                                            <a href="{{ route('dashboard.contexts.show', $context) }}" class="btn btn-ghost btn-sm">
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 text-center">
                            <a href="{{ route('dashboard.contexts.index') }}" class="text-primary hover:underline">
                                View all projects →
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Business Ideas -->
            <div class="lqd-card">
                <div class="lqd-card-header">
                    <h3 class="lqd-card-title">Business Ideas</h3>
                    <a href="{{ route('dashboard.business-ideas.generate') }}" class="btn btn-outline btn-sm">
                        <i class="fas fa-lightbulb"></i> Generate Ideas
                    </a>
                </div>
                <div class="lqd-card-body">
                    @if($recentIdeas->isEmpty())
                        <p class="text-muted text-center py-4">No business ideas yet. Let AI help you generate some!</p>
                    @else
                        <div class="space-y-4">
                            @foreach($recentIdeas as $idea)
                                <div class="border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="font-semibold">
                                                <a href="{{ route('dashboard.business-ideas.show', $idea) }}" class="text-primary hover:underline">
                                                    {{ $idea->title }}
                                                </a>
                                            </h4>
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                {{ Str::limit($idea->description, 100) }}
                                            </p>
                                            <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                                                @if($idea->revenue_potential)
                                                    <span><i class="fas fa-dollar-sign mr-1"></i>{{ $idea->revenue_potential }}</span>
                                                @endif
                                                @if($idea->weekly_hours_required)
                                                    <span><i class="fas fa-clock mr-1"></i>{{ $idea->weekly_hours_required }}h/week</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <span class="badge badge-{{ $idea->status === 'active' ? 'success' : ($idea->status === 'draft' ? 'warning' : 'info') }}">
                                                {{ ucfirst($idea->status) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 text-center">
                            <a href="{{ route('dashboard.business-ideas.index') }}" class="text-primary hover:underline">
                                View all ideas →
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-6">
            <div class="lqd-card">
                <div class="lqd-card-header">
                    <h3 class="lqd-card-title">Quick Actions</h3>
                </div>
                <div class="lqd-card-body">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <a href="{{ route('dashboard.contexts.create') }}" class="block rounded-lg border border-gray-200 p-4 text-center transition hover:border-primary hover:shadow-md dark:border-gray-700">
                            <i class="fas fa-folder-plus mb-2 text-2xl text-primary"></i>
                            <p class="font-semibold">New Project</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Start tracking a new project</p>
                        </a>
                        <a href="{{ route('dashboard.business-ideas.generate') }}" class="block rounded-lg border border-gray-200 p-4 text-center transition hover:border-primary hover:shadow-md dark:border-gray-700">
                            <i class="fas fa-lightbulb mb-2 text-2xl text-primary"></i>
                            <p class="font-semibold">Generate Ideas</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Get AI-powered business ideas</p>
                        </a>
                        <a href="{{ route('dashboard.contexts.index') }}" class="block rounded-lg border border-gray-200 p-4 text-center transition hover:border-primary hover:shadow-md dark:border-gray-700">
                            <i class="fas fa-list mb-2 text-2xl text-primary"></i>
                            <p class="font-semibold">View Projects</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">See all your projects</p>
                        </a>
                        <a href="{{ route('dashboard.ai-side-hustle.preferences') }}" class="block rounded-lg border border-gray-200 p-4 text-center transition hover:border-primary hover:shadow-md dark:border-gray-700">
                            <i class="fas fa-cog mb-2 text-2xl text-primary"></i>
                            <p class="font-semibold">Preferences</p>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Customize your experience</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection