@extends('panel.layout.app')
@section('title', 'Generate Business Ideas')
@section('titlebar_subtitle', 'Get AI-powered business recommendations')

@section('content')
    <div class="lqd-dashboard-content">
        <div class="mx-auto max-w-2xl">
            <form action="{{ route('dashboard.business-ideas.generate.process') }}" method="POST" id="generate-form">
                @csrf
                <div class="lqd-card">
                    <div class="lqd-card-header">
                        <h3 class="lqd-card-title">Generate Business Ideas</h3>
                    </div>
                    <div class="lqd-card-body">
                        <div class="mb-6">
                            <label for="hours" class="lqd-label">Weekly Hours Available</label>
                            <input 
                                type="number" 
                                id="hours" 
                                name="hours" 
                                class="lqd-input @error('hours') is-invalid @enderror" 
                                value="{{ old('hours', $userPreferences->weekly_hours_available ?? 5) }}"
                                min="1"
                                max="168"
                                placeholder="How many hours per week?"
                            >
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                AI will suggest businesses that fit your time commitment
                            </p>
                            @error('hours')
                                <div class="lqd-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="interests" class="lqd-label">Your Interests (Optional)</label>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Add interests to get more personalized suggestions
                            </div>
                            <input 
                                type="text" 
                                id="interests-input" 
                                class="lqd-input"
                                placeholder="Type and press Enter to add interests"
                            >
                            <div id="interests-container" class="mt-2 flex flex-wrap gap-2">
                                @if($userPreferences && $userPreferences->interests)
                                    @foreach($userPreferences->interests as $interest)
                                        <span class="badge badge-primary">
                                            {{ $interest }}
                                            <button type="button" class="ml-2" onclick="removeInterest('{{ $interest }}')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </span>
                                    @endforeach
                                @endif
                            </div>
                            <input type="hidden" name="interests" id="interests" value="{{ old('interests') ? json_encode(old('interests')) : json_encode($userPreferences->interests ?? []) }}">
                        </div>

                        <div class="lqd-card bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <h4 class="font-semibold mb-2">What You'll Get:</h4>
                            <ul class="space-y-1 text-sm">
                                <li><i class="fas fa-check text-green-500 mr-2"></i>3 personalized business ideas</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Target audience for each idea</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Revenue potential estimates</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Time to first revenue</li>
                                <li><i class="fas fa-check text-green-500 mr-2"></i>Actionable next steps</li>
                            </ul>
                        </div>
                    </div>
                    <div class="lqd-card-footer">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('dashboard.business-ideas.index') }}" class="btn btn-ghost">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="generate-btn">
                                <i class="fas fa-magic mr-2"></i>Generate Ideas
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
    const form = document.getElementById('generate-form');
    const generateBtn = document.getElementById('generate-btn');
    const interestsInput = document.getElementById('interests-input');
    const interestsContainer = document.getElementById('interests-container');
    const interestsHidden = document.getElementById('interests');
    
    let interests = [];
    try {
        interests = JSON.parse(interestsHidden.value) || [];
    } catch (e) {
        interests = [];
    }
    
    interestsInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const interest = this.value.trim();
            if (interest && !interests.includes(interest)) {
                interests.push(interest);
                addInterestToUI(interest);
                updateHiddenInput();
                this.value = '';
            }
        }
    });
    
    function addInterestToUI(interest) {
        const interestEl = document.createElement('span');
        interestEl.className = 'badge badge-primary';
        interestEl.innerHTML = `
            ${interest}
            <button type="button" class="ml-2" onclick="removeInterest('${interest}')">
                <i class="fas fa-times"></i>
            </button>
        `;
        interestsContainer.appendChild(interestEl);
    }
    
    window.removeInterest = function(interest) {
        interests = interests.filter(i => i !== interest);
        updateHiddenInput();
        renderInterests();
    }
    
    function renderInterests() {
        interestsContainer.innerHTML = '';
        interests.forEach(interest => addInterestToUI(interest));
    }
    
    function updateHiddenInput() {
        interestsHidden.value = JSON.stringify(interests);
    }
    
    form.addEventListener('submit', function() {
        generateBtn.disabled = true;
        generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating Ideas...';
    });
});
</script>
@endpush