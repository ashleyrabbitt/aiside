<?php

namespace App\Http\Controllers\AISideHustle;

use App\Http\Controllers\Controller;
use App\Models\Context;
use App\Models\ContextEntry;
use App\Services\AISideHustle\AISideHustleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContextController extends Controller
{
    protected AISideHustleService $aiService;

    public function __construct(AISideHustleService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $contexts = Auth::user()->contexts()
            ->with('latestEntry')
            ->latest()
            ->paginate(12);

        return view('panel.ai-side-hustle.contexts.index', compact('contexts'));
    }

    public function create()
    {
        return view('panel.ai-side-hustle.contexts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);

        $context = Auth::user()->contexts()->create($validated);

        return redirect()->route('dashboard.contexts.show', $context)
            ->with('success', 'Context created successfully!');
    }

    public function show(Context $context)
    {
        $this->authorize('view', $context);

        $entries = $context->entries()
            ->latest('timestamp')
            ->paginate(10);

        $nextSteps = null;
        if ($context->latestEntry && $context->latestEntry->ai_summary) {
            $nextSteps = $this->aiService->suggestNextSteps($context);
        }

        return view('panel.ai-side-hustle.contexts.show', compact('context', 'entries', 'nextSteps'));
    }

    public function edit(Context $context)
    {
        $this->authorize('update', $context);

        return view('panel.ai-side-hustle.contexts.edit', compact('context'));
    }

    public function update(Request $request, Context $context)
    {
        $this->authorize('update', $context);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'required|in:active,archived,completed',
        ]);

        $context->update($validated);

        return redirect()->route('dashboard.contexts.show', $context)
            ->with('success', 'Context updated successfully!');
    }

    public function destroy(Context $context)
    {
        $this->authorize('delete', $context);

        $context->delete();

        return redirect()->route('dashboard.contexts.index')
            ->with('success', 'Context deleted successfully!');
    }

    public function addEntry(Request $request, Context $context)
    {
        $this->authorize('update', $context);

        $validated = $request->validate([
            'notes' => 'required|string|min:10',
        ]);

        $entry = $context->entries()->create($validated);

        // Generate AI summary asynchronously
        $summary = $this->aiService->summarizeContextEntry($entry);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'entry' => $entry->fresh(),
                'summary' => $summary,
            ]);
        }

        return redirect()->route('dashboard.contexts.show', $context)
            ->with('success', 'Entry added and summary generated!');
    }
}