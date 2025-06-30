@extends('panel.layout.app')
@section('title', 'AI Side Hustle Preferences')
@section('titlebar_subtitle', 'Customize your AI recommendations')

@section('content')
    <div class="lqd-dashboard-content">
        <div class="mx-auto max-w-2xl">
            <form action="{{ route('dashboard.ai-side-hustle.preferences.update') }}" method="POST">
                @csrf
                <div class="lqd-card">
                    <div class="lqd-card-header">
                        <h3 class="lqd-card-title">Your Preferences</h3>
                    </div>
                    <div class="lqd-card-body">
                        <div class="mb-6">
                            <label for="weekly_hours_available" class="lqd-label required">
                                Weekly Hours Available
                            </label>
                            <input 
                                type="number" 
                                id="weekly_hours_available" 
                                name="weekly_hours_available" 
                                class="lqd-input @error('weekly_hours_available') is-invalid @enderror" 
                                value="{{ old('weekly_hours_available', $preferences->weekly_hours_available ?? 5) }}"
                                min="1"
                                max="168"
                                required
                            >
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                How many hours per week can you dedicate to your side hustle?
                            </p>
                            @error('weekly_hours_available')
                                <div class="lqd-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="income_goal" class="lqd-label">Income Goal</label>
                            <input 
                                type="text" 
                                id="income_goal" 
                                name="income_goal" 
                                class="lqd-input @error('income_goal') is-invalid @enderror" 
                                value="{{ old('income_goal', $preferences->income_goal ?? '') }}"
                                placeholder="e.g., $5,000/month, $60,000/year"
                            >
                            @error('income_goal')
                                <div class="lqd-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="interests" class="lqd-label">Your Interests</label>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Add your interests to get personalized business ideas
                            </div>
                            <input 
                                type="text" 
                                id="interests-input" 
                                class="lqd-input"
                                placeholder="Type and press Enter to add interests"
                            >
                            <div id="interests-container" class="mt-2 flex flex-wrap gap-2"></div>
                            <input type="hidden" name="interests" id="interests" value="{{ old('interests') ? json_encode(old('interests')) : json_encode($preferences->interests ?? []) }}">
                        </div>

                        <div class="mb-6">
                            <label for="skills" class="lqd-label">Your Skills</label>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                Add your skills to match with suitable business opportunities
                            </div>
                            <input 
                                type="text" 
                                id="skills-input" 
                                class="lqd-input"
                                placeholder="Type and press Enter to add skills"
                            >
                            <div id="skills-container" class="mt-2 flex flex-wrap gap-2"></div>
                            <input type="hidden" name="skills" id="skills" value="{{ old('skills') ? json_encode(old('skills')) : json_encode($preferences->skills ?? []) }}">
                        </div>

                        <div class="mb-6">
                            <label for="business_experience" class="lqd-label">Business Experience</label>
                            <textarea 
                                id="business_experience" 
                                name="business_experience" 
                                rows="3" 
                                class="lqd-textarea @error('business_experience') is-invalid @enderror"
                                placeholder="Briefly describe your business experience..."
                            >{{ old('business_experience', $preferences->business_experience ?? '') }}</textarea>
                            @error('business_experience')
                                <div class="lqd-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="lqd-label">Daily Reminders</label>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        name="daily_reminders" 
                                        value="1"
                                        class="lqd-checkbox"
                                        {{ old('daily_reminders', $preferences->daily_reminders ?? true) ? 'checked' : '' }}
                                    >
                                    <span class="ml-2">Enable daily reminders</span>
                                </label>
                                <input 
                                    type="time" 
                                    name="reminder_time" 
                                    class="lqd-input @error('reminder_time') is-invalid @enderror" 
                                    value="{{ old('reminder_time', $preferences ? $preferences->reminder_time->format('H:i') : '09:00') }}"
                                >
                            </div>
                            @error('reminder_time')
                                <div class="lqd-invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="lqd-card-footer">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('dashboard.ai-side-hustle.index') }}" class="btn btn-ghost">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>Save Preferences
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
    // Handle interests tags
    setupTags('interests');
    // Handle skills tags
    setupTags('skills');
    
    function setupTags(type) {
        const input = document.getElementById(type + '-input');
        const container = document.getElementById(type + '-container');
        const hidden = document.getElementById(type);
        
        let items = [];
        try {
            items = JSON.parse(hidden.value) || [];
        } catch (e) {
            items = [];
        }
        
        // Display existing items
        items.forEach(item => addItemToUI(item, type));
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const item = this.value.trim();
                if (item && !items.includes(item)) {
                    items.push(item);
                    addItemToUI(item, type);
                    updateHiddenInput();
                    this.value = '';
                }
            }
        });
        
        function addItemToUI(item, itemType) {
            const itemEl = document.createElement('span');
            itemEl.className = 'badge badge-primary';
            itemEl.innerHTML = `
                ${item}
                <button type="button" class="ml-2" onclick="remove${itemType}Item('${item}')">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(itemEl);
        }
        
        window['remove' + type + 'Item'] = function(item) {
            items = items.filter(i => i !== item);
            updateHiddenInput();
            renderItems();
        }
        
        function renderItems() {
            container.innerHTML = '';
            items.forEach(item => addItemToUI(item, type));
        }
        
        function updateHiddenInput() {
            hidden.value = JSON.stringify(items);
        }
    }
});
</script>
@endpush