<?php

namespace App\Services\AI;

use Illuminate\Support\Str;

class ResponseEnhancerService
{
    /**
     * Enhance AI response with better structure and formatting
     */
    public function enhance(string $response, array $context = []): array
    {
        // Clean and prepare the response
        $response = $this->cleanResponse($response);
        
        // Add structure if needed
        $response = $this->addStructure($response, $context);
        
        // Enhance with examples if applicable
        $response = $this->enhanceWithExamples($response, $context);
        
        // Add formatting for readability
        $response = $this->formatForReadability($response);
        
        // Generate metadata
        $metadata = $this->generateMetadata($response, $context);
        
        // Generate follow-up suggestions
        $followUps = $this->generateFollowUps($response, $context);
        
        return [
            'response' => $response,
            'metadata' => $metadata,
            'follow_ups' => $followUps,
            'quality_score' => $this->scoreQuality($response, $context),
        ];
    }

    /**
     * Clean the response
     */
    protected function cleanResponse(string $response): string
    {
        // Remove excess whitespace
        $response = preg_replace('/\s+/', ' ', $response);
        
        // Fix common formatting issues
        $response = str_replace(['  ', ' .', ' ,'], [' ', '.', ','], $response);
        
        // Ensure proper sentence spacing
        $response = preg_replace('/\.(\w)/', '. $1', $response);
        
        return trim($response);
    }

    /**
     * Add structure to responses that need it
     */
    protected function addStructure(string $response, array $context): string
    {
        // Check if response needs structure
        if (!$this->hasProperStructure($response) && strlen($response) > 500) {
            $sections = $this->identifySections($response);
            
            if (count($sections) > 1) {
                $structured = "";
                foreach ($sections as $i => $section) {
                    if ($i === 0) {
                        // First section is introduction
                        $structured .= $section . "\n\n";
                    } else {
                        // Add headers for main sections
                        $header = $this->generateSectionHeader($section, $i);
                        $structured .= "## {$header}\n\n{$section}\n\n";
                    }
                }
                return trim($structured);
            }
        }
        
        return $response;
    }

    /**
     * Check if response has proper structure
     */
    protected function hasProperStructure(string $response): bool
    {
        // Check for markdown headers
        if (preg_match('/^#{1,3}\s/m', $response)) {
            return true;
        }
        
        // Check for numbered lists
        if (preg_match('/^\d+\.\s/m', $response)) {
            return true;
        }
        
        // Check for bullet points
        if (preg_match('/^[\*\-]\s/m', $response)) {
            return true;
        }
        
        return false;
    }

    /**
     * Identify logical sections in the response
     */
    protected function identifySections(string $response): array
    {
        // Split by double newlines first
        $paragraphs = preg_split('/\n\n+/', $response);
        
        // Group related paragraphs
        $sections = [];
        $currentSection = '';
        
        foreach ($paragraphs as $paragraph) {
            // Check if this starts a new topic
            if ($this->isNewTopic($paragraph, $currentSection)) {
                if ($currentSection) {
                    $sections[] = trim($currentSection);
                }
                $currentSection = $paragraph;
            } else {
                $currentSection .= "\n\n" . $paragraph;
            }
        }
        
        if ($currentSection) {
            $sections[] = trim($currentSection);
        }
        
        return $sections;
    }

    /**
     * Check if paragraph starts a new topic
     */
    protected function isNewTopic(string $paragraph, string $currentSection): bool
    {
        // Topic transition indicators
        $transitions = [
            'however', 'additionally', 'furthermore', 'moreover',
            'on the other hand', 'in contrast', 'alternatively',
            'another', 'secondly', 'thirdly', 'finally'
        ];
        
        $paragraphStart = strtolower(substr($paragraph, 0, 50));
        foreach ($transitions as $transition) {
            if (str_starts_with($paragraphStart, $transition)) {
                return true;
            }
        }
        
        // Check if significantly different content
        if ($currentSection && $this->calculateSimilarity($paragraph, $currentSection) < 0.3) {
            return true;
        }
        
        return false;
    }

    /**
     * Generate section header based on content
     */
    protected function generateSectionHeader(string $section, int $index): string
    {
        // Extract key theme from section
        $sentences = preg_split('/[.!?]/', $section);
        $firstSentence = trim($sentences[0] ?? '');
        
        // Common section patterns
        if (preg_match('/benefits?|advantages?/i', $firstSentence)) {
            return 'Key Benefits';
        }
        if (preg_match('/challenges?|disadvantages?|cons/i', $firstSentence)) {
            return 'Challenges to Consider';
        }
        if (preg_match('/how|steps?|process/i', $firstSentence)) {
            return 'Implementation Steps';
        }
        if (preg_match('/examples?|instances?|cases?/i', $firstSentence)) {
            return 'Practical Examples';
        }
        if (preg_match('/conclusions?|summary|finally/i', $firstSentence)) {
            return 'Conclusion';
        }
        
        // Generate generic header
        return 'Key Point ' . $index;
    }

    /**
     * Enhance response with examples
     */
    protected function enhanceWithExamples(string $response, array $context): string
    {
        // Check if examples are mentioned but not provided
        if (preg_match('/for example|such as|like/i', $response) && 
            !preg_match('/for example[,:].*?[.]|such as.*?[.]|like.*?[.]/i', $response)) {
            
            // Add example placeholder
            $response .= "\n\n**Note**: Specific examples would enhance understanding here. Consider asking for concrete examples related to your use case.";
        }
        
        return $response;
    }

    /**
     * Format response for better readability
     */
    protected function formatForReadability(string $response): string
    {
        // Convert lists to proper markdown
        $response = $this->formatLists($response);
        
        // Add emphasis to key terms
        $response = $this->emphasizeKeyTerms($response);
        
        // Format code blocks if present
        $response = $this->formatCodeBlocks($response);
        
        // Ensure proper paragraph spacing
        $response = preg_replace('/([.!?])\s*\n([A-Z])/', "$1\n\n$2", $response);
        
        return $response;
    }

    /**
     * Format lists properly
     */
    protected function formatLists(string $response): string
    {
        // Convert informal lists to markdown
        $response = preg_replace('/^(\d+)\)\s/m', '$1. ', $response);
        $response = preg_replace('/^[-–]\s/m', '- ', $response);
        
        // Fix list spacing
        $lines = explode("\n", $response);
        $formatted = [];
        $inList = false;
        
        foreach ($lines as $line) {
            if (preg_match('/^(\d+\.|[-*])\s/', $line)) {
                if (!$inList && !empty($formatted)) {
                    $formatted[] = ''; // Add space before list
                }
                $inList = true;
            } else {
                if ($inList && trim($line) !== '') {
                    $formatted[] = ''; // Add space after list
                }
                $inList = false;
            }
            $formatted[] = $line;
        }
        
        return implode("\n", $formatted);
    }

    /**
     * Emphasize key terms
     */
    protected function emphasizeKeyTerms(string $response): string
    {
        // Key terms to emphasize
        $keyTerms = [
            'important' => '**Important**',
            'note:' => '**Note:**',
            'warning:' => '**Warning:**',
            'tip:' => '**Tip:**',
            'key point' => '**Key Point**',
            'remember' => '**Remember**',
        ];
        
        foreach ($keyTerms as $term => $replacement) {
            $response = preg_replace('/\b' . preg_quote($term, '/') . '\b/i', $replacement, $response);
        }
        
        return $response;
    }

    /**
     * Format code blocks
     */
    protected function formatCodeBlocks(string $response): string
    {
        // Detect and format inline code
        $response = preg_replace('/`([^`]+)`/', '`$1`', $response);
        
        // Detect and format code blocks
        if (preg_match('/```[\s\S]+?```/', $response)) {
            // Already formatted
            return $response;
        }
        
        // Look for code-like content
        $lines = explode("\n", $response);
        $inCode = false;
        $formatted = [];
        
        foreach ($lines as $line) {
            if ($this->looksLikeCode($line)) {
                if (!$inCode) {
                    $formatted[] = '```';
                    $inCode = true;
                }
                $formatted[] = $line;
            } else {
                if ($inCode && trim($line) === '') {
                    continue; // Skip empty lines in code
                }
                if ($inCode) {
                    $formatted[] = '```';
                    $inCode = false;
                }
                $formatted[] = $line;
            }
        }
        
        if ($inCode) {
            $formatted[] = '```';
        }
        
        return implode("\n", $formatted);
    }

    /**
     * Check if line looks like code
     */
    protected function looksLikeCode(string $line): bool
    {
        // Common code patterns
        $patterns = [
            '/^(function|def|class|interface|struct)\s/',
            '/^(if|else|for|while|switch)\s*\(/',
            '/^(import|require|include|use)\s/',
            '/[{};]$/',
            '/^\s{4,}[\w\$]/', // Indented content
            '/^(var|let|const)\s+\w+\s*=/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate metadata about the response
     */
    protected function generateMetadata(string $response, array $context): array
    {
        return [
            'word_count' => str_word_count($response),
            'reading_time' => ceil(str_word_count($response) / 200) . ' min',
            'complexity' => $this->assessComplexity($response),
            'topics' => $this->extractTopics($response),
            'sentiment' => $this->analyzeSentiment($response),
            'has_examples' => str_contains($response, 'example') || str_contains($response, 'for instance'),
            'has_structure' => $this->hasProperStructure($response),
            'actionable' => $this->isActionable($response),
        ];
    }

    /**
     * Assess complexity of response
     */
    protected function assessComplexity(string $response): string
    {
        $avgWordLength = $this->getAverageWordLength($response);
        $avgSentenceLength = $this->getAverageSentenceLength($response);
        
        if ($avgWordLength > 6 || $avgSentenceLength > 25) {
            return 'advanced';
        } elseif ($avgWordLength > 5 || $avgSentenceLength > 20) {
            return 'intermediate';
        } else {
            return 'beginner';
        }
    }

    /**
     * Calculate average word length
     */
    protected function getAverageWordLength(string $text): float
    {
        $words = str_word_count($text, 1);
        if (empty($words)) return 0;
        
        $totalLength = array_sum(array_map('strlen', $words));
        return $totalLength / count($words);
    }

    /**
     * Calculate average sentence length
     */
    protected function getAverageSentenceLength(string $text): float
    {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($sentences)) return 0;
        
        $totalWords = 0;
        foreach ($sentences as $sentence) {
            $totalWords += str_word_count($sentence);
        }
        
        return $totalWords / count($sentences);
    }

    /**
     * Extract main topics from response
     */
    protected function extractTopics(string $response): array
    {
        // Simple topic extraction based on frequency
        $words = str_word_count(strtolower($response), 1);
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'and', 'a', 'an', 'as', 'are', 'was', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'them', 'their', 'what', 'which', 'who', 'when', 'where', 'why', 'how', 'all', 'each', 'every', 'some', 'any', 'many', 'more', 'most', 'other', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'to', 'from', 'up', 'down', 'in', 'out', 'on', 'off', 'over', 'under', 'again', 'further', 'then', 'once'];
        
        $words = array_diff($words, $stopWords);
        $words = array_filter($words, fn($w) => strlen($w) > 3);
        
        $frequency = array_count_values($words);
        arsort($frequency);
        
        return array_slice(array_keys($frequency), 0, 5);
    }

    /**
     * Analyze sentiment
     */
    protected function analyzeSentiment(string $response): string
    {
        $positive = preg_match_all('/\b(good|great|excellent|positive|benefit|advantage|success|improve|better|best|helpful|useful)\b/i', $response);
        $negative = preg_match_all('/\b(bad|poor|negative|disadvantage|problem|issue|difficult|worse|worst|harmful|useless)\b/i', $response);
        
        if ($positive > $negative * 2) {
            return 'positive';
        } elseif ($negative > $positive * 2) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }

    /**
     * Check if response is actionable
     */
    protected function isActionable(string $response): bool
    {
        $actionIndicators = [
            'you can', 'you should', 'try to', 'make sure',
            'follow these steps', 'here\'s how', 'to do this',
            'implement', 'create', 'build', 'develop'
        ];
        
        foreach ($actionIndicators as $indicator) {
            if (stripos($response, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate follow-up suggestions
     */
    protected function generateFollowUps(string $response, array $context): array
    {
        $suggestions = [];
        
        // Based on response content
        if (str_contains($response, 'example')) {
            $suggestions[] = "Can you provide more specific examples?";
        }
        
        if ($this->hasProperStructure($response)) {
            $suggestions[] = "Would you like me to elaborate on any particular section?";
        }
        
        if ($this->isActionable($response)) {
            $suggestions[] = "Do you need help implementing any of these steps?";
        }
        
        // Based on topics
        $topics = $this->extractTopics($response);
        if (!empty($topics)) {
            $suggestions[] = "Would you like to explore more about " . $topics[0] . "?";
        }
        
        // Always include a general option
        $suggestions[] = "Is there a specific aspect you'd like me to clarify?";
        
        return array_slice(array_unique($suggestions), 0, 3);
    }

    /**
     * Score response quality
     */
    protected function scoreQuality(string $response, array $context): array
    {
        $scores = [
            'clarity' => $this->scoreClar,
            'completeness' => $this->scoreCompleteness($response, $context),
            'structure' => $this->scoreStructure($response),
            'engagement' => $this->scoreEngagement($response),
            'usefulness' => $this->scoreUsefulness($response),
        ];
        
        $scores['overall'] = round(array_sum($scores) / count($scores), 1);
        
        return $scores;
    }

    /**
     * Score clarity
     */
    protected function scoreClarity(string $response): float
    {
        $score = 10.0;
        
        // Deduct for very long sentences
        if ($this->getAverageSentenceLength($response) > 30) {
            $score -= 2;
        }
        
        // Deduct for very complex words
        if ($this->getAverageWordLength($response) > 7) {
            $score -= 1;
        }
        
        // Bonus for structure
        if ($this->hasProperStructure($response)) {
            $score += 1;
        }
        
        return max(0, min(10, $score));
    }

    /**
     * Score completeness
     */
    protected function scoreCompleteness(string $response, array $context): float
    {
        $score = 8.0;
        
        // Check if response addresses the question
        if (strlen($response) < 100) {
            $score -= 3;
        }
        
        // Bonus for examples
        if (str_contains($response, 'example') || str_contains($response, 'for instance')) {
            $score += 1;
        }
        
        // Bonus for comprehensive coverage
        if (str_word_count($response) > 300) {
            $score += 1;
        }
        
        return max(0, min(10, $score));
    }

    /**
     * Score structure
     */
    protected function scoreStructure(string $response): float
    {
        $score = 7.0;
        
        if ($this->hasProperStructure($response)) {
            $score += 2;
        }
        
        // Check for logical flow
        if (preg_match('/first|second|finally|in conclusion/i', $response)) {
            $score += 1;
        }
        
        return max(0, min(10, $score));
    }

    /**
     * Score engagement
     */
    protected function scoreEngagement(string $response): float
    {
        $score = 7.0;
        
        // Check for varied sentence structure
        $sentences = preg_split('/[.!?]/', $response);
        $lengths = array_map('strlen', $sentences);
        $variance = $this->calculateVariance($lengths);
        
        if ($variance > 50) {
            $score += 1;
        }
        
        // Check for questions or interactive elements
        if (substr_count($response, '?') > 0) {
            $score += 1;
        }
        
        // Check for personal pronouns (more engaging)
        if (preg_match('/\b(you|your)\b/i', $response)) {
            $score += 1;
        }
        
        return max(0, min(10, $score));
    }

    /**
     * Score usefulness
     */
    protected function scoreUsefulness(string $response): float
    {
        $score = 7.0;
        
        if ($this->isActionable($response)) {
            $score += 2;
        }
        
        if (preg_match('/\d+/', $response)) { // Contains numbers/data
            $score += 1;
        }
        
        return max(0, min(10, $score));
    }

    /**
     * Calculate variance
     */
    protected function calculateVariance(array $numbers): float
    {
        if (empty($numbers)) return 0;
        
        $mean = array_sum($numbers) / count($numbers);
        $variance = 0;
        
        foreach ($numbers as $num) {
            $variance += pow($num - $mean, 2);
        }
        
        return $variance / count($numbers);
    }

    /**
     * Calculate similarity between texts
     */
    protected function calculateSimilarity(string $text1, string $text2): float
    {
        $words1 = array_unique(str_word_count(strtolower($text1), 1));
        $words2 = array_unique(str_word_count(strtolower($text2), 1));
        
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));
        
        return $union > 0 ? $intersection / $union : 0;
    }
}