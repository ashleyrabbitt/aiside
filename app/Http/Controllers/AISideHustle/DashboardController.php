<?php

namespace App\Http\Controllers\AISideHustle;

use App\Http\Controllers\Controller;
use App\Services\AISideHustle\AISideHustleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected AISideHustleService $aiService;

    public function __construct(AISideHustleService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $user = Auth::user();
        
        // Get daily focus and recommendations
        $dailyFocus = $this->aiService->getDailyFocus($user);
        
        // Get active contexts
        $activeContexts = $user->contexts()
            ->active()
            ->with('latestEntry')
            ->latest('updated_at')
            ->limit(5)
            ->get();
        
        // Get recent business ideas
        $recentIdeas = $user->businessIdeas()
            ->whereIn('status', ['draft', 'active'])
            ->latest()
            ->limit(3)
            ->get();
        
        // Check if user has preferences set
        $hasPreferences = $user->aiPreference()->exists();
        
        return view('panel.ai-side-hustle.dashboard', compact(
            'dailyFocus',
            'activeContexts',
            'recentIdeas',
            'hasPreferences'
        ));
    }

    public function preferences()
    {
        $preferences = Auth::user()->aiPreference;
        
        return view('panel.ai-side-hustle.preferences', compact('preferences'));
    }

    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'weekly_hours_available' => 'required|integer|min:1|max:168',
            'interests' => 'nullable|array',
            'interests.*' => 'string|max:100',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
            'income_goal' => 'nullable|string|max:255',
            'business_experience' => 'nullable|string',
            'daily_reminders' => 'boolean',
            'reminder_time' => 'required_if:daily_reminders,true|date_format:H:i',
        ]);

        Auth::user()->aiPreference()->updateOrCreate(
            ['user_id' => Auth::id()],
            $validated
        );

        return redirect()->route('dashboard.ai-side-hustle')
            ->with('success', 'Preferences updated successfully!');
    }
}