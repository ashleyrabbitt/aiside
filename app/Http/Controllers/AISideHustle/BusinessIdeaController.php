<?php

namespace App\Http\Controllers\AISideHustle;

use App\Http\Controllers\Controller;
use App\Models\BusinessIdea;
use App\Services\AISideHustle\AISideHustleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessIdeaController extends Controller
{
    protected AISideHustleService $aiService;

    public function __construct(AISideHustleService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $ideas = Auth::user()->businessIdeas()
            ->latest()
            ->paginate(12);

        return view('panel.ai-side-hustle.business-ideas.index', compact('ideas'));
    }

    public function generate()
    {
        $userPreferences = Auth::user()->aiPreference;
        
        return view('panel.ai-side-hustle.business-ideas.generate', compact('userPreferences'));
    }

    public function generateIdeas(Request $request)
    {
        $validated = $request->validate([
            'hours' => 'nullable|integer|min:1|max:168',
            'interests' => 'nullable|array',
            'interests.*' => 'string|max:100',
        ]);

        $result = $this->aiService->generateBusinessIdeas(Auth::user(), $validated);

        if (!$result['success']) {
            return back()->with('error', 'Failed to generate ideas: ' . $result['error']);
        }

        // Store generated ideas in session for review
        session(['generated_ideas' => $result['ideas']]);

        return redirect()->route('dashboard.business-ideas.review');
    }

    public function review()
    {
        $generatedIdeas = session('generated_ideas', []);
        
        if (empty($generatedIdeas)) {
            return redirect()->route('dashboard.business-ideas.generate')
                ->with('error', 'No generated ideas found. Please generate new ideas.');
        }

        return view('panel.ai-side-hustle.business-ideas.review', compact('generatedIdeas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'niche' => 'nullable|string|max:255',
            'target_audience' => 'nullable|string|max:255',
            'offer_details' => 'nullable|string',
            'weekly_hours_required' => 'nullable|integer|min:1|max:168',
            'revenue_potential' => 'nullable|string|max:255',
        ]);

        $idea = Auth::user()->businessIdeas()->create($validated);

        // Clear generated ideas from session
        session()->forget('generated_ideas');

        return redirect()->route('dashboard.business-ideas.show', $idea)
            ->with('success', 'Business idea saved successfully!');
    }

    public function show(BusinessIdea $businessIdea)
    {
        $this->authorize('view', $businessIdea);

        return view('panel.ai-side-hustle.business-ideas.show', compact('businessIdea'));
    }

    public function edit(BusinessIdea $businessIdea)
    {
        $this->authorize('update', $businessIdea);

        return view('panel.ai-side-hustle.business-ideas.edit', compact('businessIdea'));
    }

    public function update(Request $request, BusinessIdea $businessIdea)
    {
        $this->authorize('update', $businessIdea);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'niche' => 'nullable|string|max:255',
            'target_audience' => 'nullable|string|max:255',
            'offer_details' => 'nullable|string',
            'status' => 'required|in:draft,active,launched,archived',
            'weekly_hours_required' => 'nullable|integer|min:1|max:168',
            'revenue_potential' => 'nullable|string|max:255',
        ]);

        $businessIdea->update($validated);

        return redirect()->route('dashboard.business-ideas.show', $businessIdea)
            ->with('success', 'Business idea updated successfully!');
    }

    public function destroy(BusinessIdea $businessIdea)
    {
        $this->authorize('delete', $businessIdea);

        $businessIdea->delete();

        return redirect()->route('dashboard.business-ideas.index')
            ->with('success', 'Business idea deleted successfully!');
    }

    public function generateFunnel(BusinessIdea $businessIdea)
    {
        $this->authorize('update', $businessIdea);

        $result = $this->aiService->generateFunnel($businessIdea);

        if (!$result['success']) {
            return back()->with('error', 'Failed to generate funnel: ' . $result['error']);
        }

        return redirect()->route('dashboard.business-ideas.show', $businessIdea)
            ->with('success', 'Sales funnel generated successfully!');
    }
}