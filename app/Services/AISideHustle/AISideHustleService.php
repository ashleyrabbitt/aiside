<?php

namespace App\Services\AISideHustle;

use App\Domains\Engine\Services\AnthropicService;
use App\Models\Context;
use App\Models\ContextEntry;
use App\Models\BusinessIdea;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AISideHustleService
{
    protected AnthropicService $anthropic;
    
    public function __construct(AnthropicService $anthropic)
    {
        $this->anthropic = $anthropic;
    }

    public function summarizeContextEntry(ContextEntry $entry): array
    {
        $cacheKey = 'context_summary_' . md5($entry->notes);
        
        return Cache::remember($cacheKey, 3600, function () use ($entry) {
            try {
                $response = $this->anthropic
                    ->setMessages([
                        [
                            'role' => 'user',
                            'content' => "Summarize this work log concisely: {$entry->notes}"
                        ]
                    ])
                    ->setSystem('You are a helpful assistant that creates concise summaries of work logs. Focus on key accomplishments and next steps.')
                    ->stream();

                $data = $response->json();
                
                if (isset($data['content'][0]['text'])) {
                    $summary = $data['content'][0]['text'];
                    
                    $entry->update([
                        'ai_summary' => $summary,
                        'ai_confidence' => $this->determineConfidence($summary, $entry->notes)
                    ]);
                    
                    return [
                        'success' => true,
                        'summary' => $summary,
                        'confidence' => $entry->ai_confidence
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => 'Invalid response format'
                ];
                
            } catch (\Exception $e) {
                Log::error('Context summarization failed: ' . $e->getMessage());
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    public function suggestNextSteps(Context $context): array
    {
        $latestEntry = $context->latestEntry;
        if (!$latestEntry || !$latestEntry->ai_summary) {
            return [
                'success' => false,
                'error' => 'No summary available for context'
            ];
        }

        $cacheKey = 'next_steps_' . $context->id . '_' . $latestEntry->id;
        
        return Cache::remember($cacheKey, 1800, function () use ($latestEntry) {
            try {
                $response = $this->anthropic
                    ->setMessages([
                        [
                            'role' => 'user',
                            'content' => "Based on this summary: '{$latestEntry->ai_summary}', what should I do next to keep momentum? Provide 3 specific, actionable next steps."
                        ]
                    ])
                    ->setSystem('You are a productivity coach helping users maintain momentum on their projects. Provide specific, actionable next steps.')
                    ->stream();

                $data = $response->json();
                
                if (isset($data['content'][0]['text'])) {
                    return [
                        'success' => true,
                        'next_steps' => $data['content'][0]['text'],
                        'confidence' => 'high'
                    ];
                }
                
                return [
                    'success' => false,
                    'error' => 'Invalid response format'
                ];
                
            } catch (\Exception $e) {
                Log::error('Next steps generation failed: ' . $e->getMessage());
                return [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        });
    }

    public function generateBusinessIdeas(User $user, array $preferences = []): array
    {
        $userPreferences = $user->aiPreference;
        $hours = $preferences['hours'] ?? $userPreferences->weekly_hours_available ?? 5;
        $interests = $preferences['interests'] ?? $userPreferences->interests ?? [];
        
        $prompt = "Suggest 3 online business ideas for a solopreneur who wants passive income and has {$hours} hours per week.";
        
        if (!empty($interests)) {
            $prompt .= " Their interests include: " . implode(', ', $interests) . ".";
        }
        
        $prompt .= " For each idea, provide: 1) Business name, 2) Description, 3) Target audience, 4) Revenue potential, 5) Time to first revenue.";

        try {
            $response = $this->anthropic
                ->setMessages([
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ])
                ->setSystem('You are a business strategist specializing in online businesses and passive income. Provide realistic, actionable business ideas.')
                ->stream();

            $data = $response->json();
            
            if (isset($data['content'][0]['text'])) {
                return [
                    'success' => true,
                    'ideas' => $this->parseBusinessIdeas($data['content'][0]['text']),
                    'raw_response' => $data['content'][0]['text']
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Invalid response format'
            ];
            
        } catch (\Exception $e) {
            Log::error('Business idea generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function generateFunnel(BusinessIdea $idea): array
    {
        $prompt = "Create a sales funnel for: {$idea->title}. ";
        $prompt .= "Product description: {$idea->description}. ";
        $prompt .= "Target audience: {$idea->target_audience}. ";
        $prompt .= "Include: 1) Landing page (headline, subheadline, 3 key benefits, CTA), ";
        $prompt .= "2) Email sequence (5 emails with subject lines and key points), ";
        $prompt .= "3) Offer structure (pricing tiers, bonuses).";

        try {
            $response = $this->anthropic
                ->setMessages([
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ])
                ->setSystem('You are a digital marketing expert specializing in sales funnels and conversion optimization. Create compelling, high-converting funnel content.')
                ->stream();

            $data = $response->json();
            
            if (isset($data['content'][0]['text'])) {
                $funnelData = $this->parseFunnelData($data['content'][0]['text']);
                
                $idea->update([
                    'funnel_data' => $funnelData
                ]);
                
                return [
                    'success' => true,
                    'funnel' => $funnelData,
                    'raw_response' => $data['content'][0]['text']
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Invalid response format'
            ];
            
        } catch (\Exception $e) {
            Log::error('Funnel generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getDailyFocus(User $user): array
    {
        $activeContexts = $user->contexts()->active()->with('latestEntry')->get();
        $recentIdeas = $user->businessIdeas()->where('status', 'active')->latest()->limit(3)->get();
        
        $context = "User has " . $activeContexts->count() . " active projects. ";
        
        foreach ($activeContexts as $activeContext) {
            if ($activeContext->latestEntry) {
                $context .= "Project '{$activeContext->title}': {$activeContext->latestEntry->ai_summary}. ";
            }
        }
        
        $prompt = "Based on this context: {$context} What should be today's top goal to maximize progress? Also suggest 3 quick wins for today.";

        try {
            $response = $this->anthropic
                ->setMessages([
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ])
                ->setSystem('You are a productivity coach helping users focus on high-impact activities. Provide specific, achievable daily goals.')
                ->stream();

            $data = $response->json();
            
            if (isset($data['content'][0]['text'])) {
                return [
                    'success' => true,
                    'daily_focus' => $this->parseDailyFocus($data['content'][0]['text']),
                    'contexts' => $activeContexts,
                    'recent_ideas' => $recentIdeas
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Invalid response format'
            ];
            
        } catch (\Exception $e) {
            Log::error('Daily focus generation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function determineConfidence(string $summary, string $original): string
    {
        $summaryLength = strlen($summary);
        $originalLength = strlen($original);
        
        if ($summaryLength < 50 || $summaryLength > $originalLength * 0.5) {
            return 'low';
        } elseif ($summaryLength > $originalLength * 0.2) {
            return 'medium';
        }
        
        return 'high';
    }

    protected function parseBusinessIdeas(string $response): array
    {
        // Simple parsing - in production, you'd want more sophisticated parsing
        $ideas = [];
        $sections = preg_split('/\d+\.\s+/', $response, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($sections as $section) {
            if (trim($section)) {
                $ideas[] = [
                    'raw_text' => trim($section),
                    // Additional parsing logic here
                ];
            }
        }
        
        return $ideas;
    }

    protected function parseFunnelData(string $response): array
    {
        // Simple structure - enhance based on actual response format
        return [
            'landing_page' => [
                'raw_content' => $response,
                // Parse specific sections
            ],
            'email_sequence' => [],
            'offer_structure' => []
        ];
    }

    protected function parseDailyFocus(string $response): array
    {
        return [
            'top_goal' => $response,
            'quick_wins' => [],
            // Parse response to extract structured data
        ];
    }
}