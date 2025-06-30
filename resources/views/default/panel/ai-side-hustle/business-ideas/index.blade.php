@extends('panel.layout.app')
@section('title', 'Business Ideas')
@section('titlebar_subtitle', 'Your AI-generated business opportunities')

@section('content')
    <div class="lqd-dashboard-content">
        <div class="lqd-card">
            <div class="lqd-card-header">
                <div class="flex items-center justify-between">
                    <h3 class="lqd-card-title">Business Ideas</h3>
                    <a href="{{ route('dashboard.business-ideas.generate') }}" class="btn btn-primary">
                        <i class="fas fa-lightbulb mr-2"></i>Generate New Ideas
                    </a>
                </div>
            </div>
            <div class="lqd-card-body">
                @if($ideas->isEmpty())
                    <div class="text-center py-12">
                        <i class="fas fa-lightbulb text-6xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <h3 class="text-xl font-semibold mb-2">No business ideas yet</h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">Let AI help you discover profitable business opportunities.</p>
                        <a href="{{ route('dashboard.business-ideas.generate') }}" class="btn btn-primary">
                            <i class="fas fa-magic mr-2"></i>Generate Your First Ideas
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($ideas as $idea)
                            <div class="lqd-card border">
                                <div class="lqd-card-body">
                                    <div class="mb-2 flex items-start justify-between">
                                        <h4 class="font-semibold text-lg">
                                            <a href="{{ route('dashboard.business-ideas.show', $idea) }}" class="text-primary hover:underline">
                                                {{ $idea->title }}
                                            </a>
                                        </h4>
                                        <span class="badge badge-{{ $idea->status === 'active' ? 'success' : ($idea->status === 'launched' ? 'info' : ($idea->status === 'draft' ? 'warning' : 'secondary')) }}">
                                            {{ ucfirst($idea->status) }}
                                        </span>
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        {{ Str::limit($idea->description, 150) }}
                                    </p>
                                    
                                    <div class="mb-3 space-y-2 text-sm">
                                        @if($idea->niche)
                                            <div class="flex items-center">
                                                <i class="fas fa-tag mr-2 text-gray-500"></i>
                                                <span>{{ $idea->niche }}</span>
                                            </div>
                                        @endif
                                        @if($idea->target_audience)
                                            <div class="flex items-center">
                                                <i class="fas fa-users mr-2 text-gray-500"></i>
                                                <span>{{ $idea->target_audience }}</span>
                                            </div>
                                        @endif
                                        @if($idea->revenue_potential)
                                            <div class="flex items-center">
                                                <i class="fas fa-dollar-sign mr-2 text-gray-500"></i>
                                                <span>{{ $idea->revenue_potential }}</span>
                                            </div>
                                        @endif
                                        @if($idea->weekly_hours_required)
                                            <div class="flex items-center">
                                                <i class="fas fa-clock mr-2 text-gray-500"></i>
                                                <span>{{ $idea->weekly_hours_required }}h/week</span>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="mt-4 flex gap-2">
                                        <a href="{{ route('dashboard.business-ideas.show', $idea) }}" class="btn btn-sm btn-primary flex-1">
                                            View Details
                                        </a>
                                        @if(!$idea->funnel_data)
                                            <form action="{{ route('dashboard.business-ideas.generate-funnel', $idea) }}" method="POST" class="flex-1">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline w-full">
                                                    <i class="fas fa-funnel-dollar"></i> Generate Funnel
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-6">
                        {{ $ideas->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection