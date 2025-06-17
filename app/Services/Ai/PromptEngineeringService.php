<?php

namespace App\Services\AI;

use App\Models\User;
use App\Models\OpenaiGeneratorChatCategory;
use Illuminate\Support\Facades\Auth;

class PromptEngineeringService
{
    /**
     * Dynamic system prompts based on use case
     */
    protected array $systemPrompts = [
        'expert' => "You are an expert {domain} specialist with deep knowledge. Provide comprehensive, nuanced answers that demonstrate mastery. Include relevant examples, cite best practices, and explain complex concepts clearly.",
        'analytical' => "Approach each query with critical thinking. Break down complex topics systematically, identify key patterns, and provide structured analysis. Use data and logic to support your conclusions.",
        'creative' => "Think creatively and offer unique perspectives. Provide innovative solutions, explore unconventional approaches, and make unexpected connections between ideas.",
        'conversational' => "Engage in natural, friendly conversation while being helpful and informative. Balance professionalism with approachability.",
        'educational' => "You are an expert educator. Explain concepts clearly, use analogies and examples, and adapt your explanations to the learner's level. Build understanding step by step.",
        'professional' => "Provide professional, business-focused responses. Use industry-standard terminology, focus on practical applications, and deliver actionable insights.",
    ];

    /**
     * Model parameters for different use cases
     */
    protected array $modelParameters = [
        'creative_writing' => [
            'temperature' => 0.85,
            'top_p' => 0.9,
            'frequency_penalty' => 0.6,
            'presence_penalty' => 0.4,
            'max_tokens' => 2500,
        ],
        'analytical' => [
            'temperature' => 0.3,
            'top_p' => 0.95,
            'frequency_penalty' => 0.2,
            'presence_penalty' => 0.1,
            'max_tokens' => 3000,
        ],
        'conversational' => [
            'temperature' => 0.7,
            'top_p' => 0.85,
            'frequency_penalty' => 0.4,
            'presence_penalty' => 0.4,
            'max_tokens' => 2000,
        ],
        'educational' => [
            'temperature' => 0.5,
            'top_p' => 0.9,
            'frequency_penalty' => 0.3,
            'presence_penalty' => 0.2,
            'max_tokens' => 2500,
        ],
        'professional' => [
            'temperature' => 0.4,
            'top_p' => 0.95,
            'frequency_penalty' => 0.2,
            'presence_penalty' => 0.1,
            'max_tokens' => 2000,
        ],
        'default' => [
            'temperature' => 0.7,
            'top_p' => 0.9,
            'frequency_penalty' => 0.3,
            'presence_penalty' => 0.3,
            'max_tokens' => 2000,
        ],
    ];

    /**
     * Enhancement techniques
     */
    protected array $enhancementTechniques = [
        'chain_of_thought' => "Let's approach this step-by-step:\n{prompt}\n\nPlease think through this systematically, showing your reasoning for each step.",
        'role_playing' => "As a {role}, provide insights on: {prompt}\n\nDraw from your expertise and experience in this role.",
        'pros_and_cons' => "Analyze this topic comprehensively:\n{prompt}\n\nProvide balanced perspectives including advantages, disadvantages, and nuanced considerations.",
        'examples_first' => "Provide concrete examples to illustrate your explanation of:\n{prompt}\n\nStart with specific instances before generalizing.",
        'structured_analysis' => "Provide a structured analysis of:\n{prompt}\n\nOrganize your response with clear sections: Overview, Key Points, Analysis, Implications, and Recommendations.",
    ];

    /**
     * Build an enriched prompt with context
     */
    public function buildEnrichedPrompt(string $prompt, ?User $user = null, ?OpenaiGeneratorChatCategory $category = null): array
    {
        $enrichedPrompt = $prompt;
        $systemPrompt = '';
        
        // Determine the use case
        $useCase = $this->determineUseCase($prompt, $category);
        
        // Get appropriate system prompt
        $systemPrompt = $this->getSystemPrompt($useCase, $category);
        
        // Apply enhancement techniques
        $enrichedPrompt = $this->applyEnhancementTechniques($prompt, $useCase);
        
        // Add user context if available
        if ($user) {
            $enrichedPrompt = $this->addUserContext($enrichedPrompt, $user);
        }
        
        // Add category-specific enhancements
        if ($category) {
            $enrichedPrompt = $this->addCategoryEnhancements($enrichedPrompt, $category);
        }
        
        return [
            'prompt' => $enrichedPrompt,
            'system' => $systemPrompt,
            'parameters' => $this->getModelParameters($useCase),
        ];
    }

    /**
     * Determine the use case based on prompt and category
     */
    protected function determineUseCase(string $prompt, ?OpenaiGeneratorChatCategory $category): string
    {
        $promptLower = strtolower($prompt);
        
        // Check for creative indicators
        if (preg_match('/write|create|story|poem|creative|imagine|design/i', $prompt)) {
            return 'creative_writing';
        }
        
        // Check for analytical indicators
        if (preg_match('/analyze|compare|evaluate|assess|review|examine/i', $prompt)) {
            return 'analytical';
        }
        
        // Check for educational indicators
        if (preg_match('/explain|teach|learn|understand|how does|what is/i', $prompt)) {
            return 'educational';
        }
        
        // Check for professional indicators
        if (preg_match('/business|professional|corporate|strategy|market/i', $prompt)) {
            return 'professional';
        }
        
        // Check category hints
        if ($category) {
            if (str_contains($category->slug, 'chat')) {
                return 'conversational';
            }
            if (str_contains($category->slug, 'creative')) {
                return 'creative_writing';
            }
        }
        
        return 'conversational';
    }

    /**
     * Get the appropriate system prompt
     */
    protected function getSystemPrompt(string $useCase, ?OpenaiGeneratorChatCategory $category): string
    {
        $basePrompt = match($useCase) {
            'creative_writing' => $this->systemPrompts['creative'],
            'analytical' => $this->systemPrompts['analytical'],
            'educational' => $this->systemPrompts['educational'],
            'professional' => $this->systemPrompts['professional'],
            default => $this->systemPrompts['conversational'],
        };
        
        // Add category-specific instructions
        if ($category && $category->instructions) {
            $basePrompt .= "\n\nAdditional context: " . $category->instructions;
        }
        
        // Replace placeholders
        $domain = $category ? $category->name : 'general knowledge';
        $basePrompt = str_replace('{domain}', $domain, $basePrompt);
        
        return $basePrompt;
    }

    /**
     * Apply enhancement techniques based on use case
     */
    protected function applyEnhancementTechniques(string $prompt, string $useCase): string
    {
        $enhancedPrompt = $prompt;
        
        // Apply chain of thought for complex queries
        if ($this->isComplexQuery($prompt)) {
            $template = $this->enhancementTechniques['chain_of_thought'];
            $enhancedPrompt = str_replace('{prompt}', $prompt, $template);
        }
        
        // Apply structured analysis for analytical queries
        elseif ($useCase === 'analytical') {
            $template = $this->enhancementTechniques['structured_analysis'];
            $enhancedPrompt = str_replace('{prompt}', $prompt, $template);
        }
        
        // Apply examples-first for educational queries
        elseif ($useCase === 'educational') {
            $template = $this->enhancementTechniques['examples_first'];
            $enhancedPrompt = str_replace('{prompt}', $prompt, $template);
        }
        
        return $enhancedPrompt;
    }

    /**
     * Add user-specific context
     */
    protected function addUserContext(string $prompt, User $user): string
    {
        $contextAdditions = [];
        
        // Add expertise level context
        if ($user->expertise_level ?? null) {
            $contextAdditions[] = "Please adjust your explanation for someone with {$user->expertise_level} level expertise.";
        }
        
        // Add industry context
        if ($user->industry ?? null) {
            $contextAdditions[] = "Consider applications and examples relevant to the {$user->industry} industry.";
        }
        
        // Add preference context
        if ($user->communication_style ?? null) {
            $contextAdditions[] = "Use a {$user->communication_style} communication style.";
        }
        
        if (!empty($contextAdditions)) {
            $prompt .= "\n\nContext: " . implode(' ', $contextAdditions);
        }
        
        return $prompt;
    }

    /**
     * Add category-specific enhancements
     */
    protected function addCategoryEnhancements(string $prompt, OpenaiGeneratorChatCategory $category): string
    {
        // Add specific enhancements based on category
        switch ($category->slug) {
            case 'ai_code':
                $prompt .= "\n\nProvide well-commented, production-ready code with error handling and best practices.";
                break;
            case 'ai_article_wizard':
                $prompt .= "\n\nStructure your response with engaging introduction, clear sections, and compelling conclusion. Include relevant statistics and examples.";
                break;
            case 'ai_product_description':
                $prompt .= "\n\nFocus on benefits over features, use persuasive language, and include sensory details that help readers visualize the product.";
                break;
        }
        
        return $prompt;
    }

    /**
     * Get model parameters for the use case
     */
    public function getModelParameters(string $useCase): array
    {
        return $this->modelParameters[$useCase] ?? $this->modelParameters['default'];
    }

    /**
     * Check if query is complex enough for chain of thought
     */
    protected function isComplexQuery(string $prompt): bool
    {
        // Check for multi-step indicators
        if (preg_match('/step by step|how to|explain the process|walk me through/i', $prompt)) {
            return true;
        }
        
        // Check for comparison or analysis
        if (preg_match('/compare|contrast|analyze|evaluate/i', $prompt)) {
            return true;
        }
        
        // Check prompt length (complex queries tend to be longer)
        if (str_word_count($prompt) > 20) {
            return true;
        }
        
        return false;
    }

    /**
     * Generate follow-up suggestions based on the response
     */
    public function generateFollowUpSuggestions(string $prompt, string $response): array
    {
        $suggestions = [];
        
        // Analyze the response content
        if (str_contains($response, 'example')) {
            $suggestions[] = "Would you like more specific examples?";
        }
        
        if (preg_match('/\d+\.|\d+\)/', $response)) {
            $suggestions[] = "Should I elaborate on any of these points?";
        }
        
        if (strlen($response) > 1000) {
            $suggestions[] = "Would you like a summary of the key points?";
        }
        
        // Add general suggestions
        $suggestions[] = "Would you like me to explain this differently?";
        $suggestions[] = "Do you have any specific questions about this topic?";
        
        return array_slice($suggestions, 0, 3);
    }
}