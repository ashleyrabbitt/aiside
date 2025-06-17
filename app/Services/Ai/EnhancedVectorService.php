<?php

namespace App\Services\AI;

use App\Services\VectorService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EnhancedVectorService extends VectorService
{
    /**
     * Get enriched context with metadata and related concepts
     */
    public function getEnrichedContext(string $prompt, int $chat_id, int $limit = 5): array
    {
        // Get basic similar content first
        $similar = $this->getMostSimilarText($prompt, $chat_id, $limit);
        
        if (!$similar) {
            return [];
        }
        
        // Enhance with metadata
        $enrichedContext = $this->enrichWithMetadata($similar, $prompt);
        
        // Add related concepts
        $relatedConcepts = $this->findRelatedConcepts($prompt);
        
        // Format final context
        return [
            'primary_context' => $enrichedContext,
            'related_concepts' => $relatedConcepts,
            'formatted_context' => $this->formatContext($enrichedContext, $relatedConcepts),
            'confidence_score' => $this->calculateConfidenceScore($similar, $prompt),
        ];
    }

    /**
     * Enrich content with metadata and source information
     */
    protected function enrichWithMetadata(string $content, string $query): array
    {
        $sections = $this->splitIntoSections($content);
        $enrichedSections = [];
        
        foreach ($sections as $index => $section) {
            $enrichedSections[] = [
                'content' => $section,
                'relevance_score' => $this->calculateRelevanceScore($section, $query),
                'section_type' => $this->identifySectionType($section),
                'key_concepts' => $this->extractKeyConcepts($section),
                'word_count' => str_word_count($section),
                'complexity' => $this->assessComplexity($section),
            ];
        }
        
        // Sort by relevance
        usort($enrichedSections, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        return $enrichedSections;
    }

    /**
     * Split content into logical sections
     */
    protected function splitIntoSections(string $content): array
    {
        // Split by double newlines first
        $paragraphs = preg_split('/\n\n+/', $content);
        
        // Group related paragraphs
        $sections = [];
        $currentSection = '';
        
        foreach ($paragraphs as $paragraph) {
            if ($this->isNewSection($paragraph, $currentSection)) {
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
        
        return array_filter($sections, fn($s) => strlen($s) > 50); // Filter out very short sections
    }

    /**
     * Check if paragraph starts a new section
     */
    protected function isNewSection(string $paragraph, string $currentSection): bool
    {
        // Section transition indicators
        $transitions = [
            'however', 'additionally', 'furthermore', 'moreover',
            'on the other hand', 'in contrast', 'alternatively',
            'another approach', 'secondly', 'thirdly', 'finally',
            'in conclusion', 'to summarize'
        ];
        
        $paragraphStart = strtolower(substr($paragraph, 0, 50));
        foreach ($transitions as $transition) {
            if (str_starts_with($paragraphStart, $transition)) {
                return true;
            }
        }
        
        // Check for topic headers
        if (preg_match('/^#{1,3}\s/', $paragraph)) {
            return true;
        }
        
        // Check if significantly different content
        if ($currentSection && $this->calculateSimilarity($paragraph, $currentSection) < 0.4) {
            return true;
        }
        
        return false;
    }

    /**
     * Calculate relevance score between section and query
     */
    protected function calculateRelevanceScore(string $section, string $query): float
    {
        $sectionWords = $this->extractWords($section);
        $queryWords = $this->extractWords($query);
        
        // Calculate intersection
        $intersection = count(array_intersect($sectionWords, $queryWords));
        $union = count(array_unique(array_merge($sectionWords, $queryWords)));
        
        $baseScore = $union > 0 ? $intersection / $union : 0;
        
        // Boost score for exact phrase matches
        $phraseBoost = $this->calculatePhraseMatches($section, $query) * 0.3;
        
        // Boost score for semantic similarity
        $semanticBoost = $this->calculateSemanticSimilarity($section, $query) * 0.2;
        
        return min(1.0, $baseScore + $phraseBoost + $semanticBoost);
    }

    /**
     * Calculate phrase matches between text and query
     */
    protected function calculatePhraseMatches(string $text, string $query): float
    {
        $queryPhrases = $this->extractPhrases($query, 2, 4); // 2-4 word phrases
        $matches = 0;
        
        foreach ($queryPhrases as $phrase) {
            if (stripos($text, $phrase) !== false) {
                $matches++;
            }
        }
        
        return count($queryPhrases) > 0 ? $matches / count($queryPhrases) : 0;
    }

    /**
     * Extract phrases of specified length
     */
    protected function extractPhrases(string $text, int $minLength, int $maxLength): array
    {
        $words = $this->extractWords($text);
        $phrases = [];
        
        for ($length = $minLength; $length <= $maxLength; $length++) {
            for ($i = 0; $i <= count($words) - $length; $i++) {
                $phrase = implode(' ', array_slice($words, $i, $length));
                if (strlen($phrase) > 10) { // Avoid very short phrases
                    $phrases[] = $phrase;
                }
            }
        }
        
        return array_unique($phrases);
    }

    /**
     * Calculate semantic similarity (simplified)
     */
    protected function calculateSemanticSimilarity(string $text1, string $text2): float
    {
        // This is a simplified version. In production, you might use:
        // - Word embeddings (Word2Vec, GloVe)
        // - Sentence transformers
        // - OpenAI embeddings API
        
        $concepts1 = $this->extractKeyConcepts($text1);
        $concepts2 = $this->extractKeyConcepts($text2);
        
        $intersection = count(array_intersect($concepts1, $concepts2));
        $union = count(array_unique(array_merge($concepts1, $concepts2)));
        
        return $union > 0 ? $intersection / $union : 0;
    }

    /**
     * Identify section type
     */
    protected function identifySectionType(string $section): string
    {
        $sectionLower = strtolower($section);
        
        if (preg_match('/^#{1,3}\s/', $section)) {
            return 'header';
        }
        
        if (preg_match('/^\d+\.\s|^[-*]\s/', $section)) {
            return 'list';
        }
        
        if (preg_match('/for example|such as|for instance/i', $section)) {
            return 'example';
        }
        
        if (preg_match('/step|process|procedure|how to/i', $section)) {
            return 'instruction';
        }
        
        if (preg_match('/definition|means|refers to|is defined as/i', $section)) {
            return 'definition';
        }
        
        if (preg_match('/benefit|advantage|positive|good/i', $section)) {
            return 'benefit';
        }
        
        if (preg_match('/risk|problem|issue|challenge/i', $section)) {
            return 'challenge';
        }
        
        return 'general';
    }

    /**
     * Extract key concepts from text
     */
    protected function extractKeyConcepts(string $text): array
    {
        $words = $this->extractWords($text);
        
        // Remove common stop words
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'and', 'a', 'an', 'as', 'are', 'was', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'should', 'could', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those'];
        $words = array_diff($words, $stopWords);
        
        // Filter for meaningful words (length > 3)
        $words = array_filter($words, fn($w) => strlen($w) > 3);
        
        // Count frequencies
        $frequency = array_count_values($words);
        arsort($frequency);
        
        // Return top concepts
        return array_slice(array_keys($frequency), 0, 10);
    }

    /**
     * Extract words from text
     */
    protected function extractWords(string $text): array
    {
        $text = strtolower(preg_replace('/[^\w\s]/', ' ', $text));
        return array_filter(explode(' ', $text), fn($w) => strlen($w) > 2);
    }

    /**
     * Assess complexity of text
     */
    protected function assessComplexity(string $text): string
    {
        $avgWordLength = $this->getAverageWordLength($text);
        $avgSentenceLength = $this->getAverageSentenceLength($text);
        $technicalTerms = $this->countTechnicalTerms($text);
        
        $complexityScore = 0;
        
        if ($avgWordLength > 6) $complexityScore += 2;
        elseif ($avgWordLength > 5) $complexityScore += 1;
        
        if ($avgSentenceLength > 25) $complexityScore += 2;
        elseif ($avgSentenceLength > 20) $complexityScore += 1;
        
        if ($technicalTerms > 5) $complexityScore += 2;
        elseif ($technicalTerms > 2) $complexityScore += 1;
        
        return match(true) {
            $complexityScore >= 5 => 'expert',
            $complexityScore >= 3 => 'advanced',
            $complexityScore >= 1 => 'intermediate',
            default => 'beginner'
        };
    }

    /**
     * Get average word length
     */
    protected function getAverageWordLength(string $text): float
    {
        $words = str_word_count($text, 1);
        if (empty($words)) return 0;
        
        $totalLength = array_sum(array_map('strlen', $words));
        return $totalLength / count($words);
    }

    /**
     * Get average sentence length
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
     * Count technical terms
     */
    protected function countTechnicalTerms(string $text): int
    {
        // Simple heuristic: words with specific patterns
        $patterns = [
            '/\b\w{8,}\b/', // Long words (8+ chars)
            '/\b[A-Z]{2,}\b/', // Acronyms
            '/\b\w+\-\w+\b/', // Hyphenated terms
            '/\b\w*tion\b/', // Words ending in -tion
            '/\b\w*ment\b/', // Words ending in -ment
            '/\b\w*ness\b/', // Words ending in -ness
        ];
        
        $count = 0;
        foreach ($patterns as $pattern) {
            $count += preg_match_all($pattern, $text);
        }
        
        return $count;
    }

    /**
     * Find related concepts using external knowledge
     */
    protected function findRelatedConcepts(string $query): array
    {
        $cacheKey = 'related_concepts_' . md5($query);
        
        return Cache::remember($cacheKey, 3600, function () use ($query) {
            // Extract main concepts from query
            $concepts = $this->extractKeyConcepts($query);
            $relatedConcepts = [];
            
            // For each concept, find related terms
            foreach (array_slice($concepts, 0, 3) as $concept) {
                $related = $this->getRelatedTerms($concept);
                $relatedConcepts = array_merge($relatedConcepts, $related);
            }
            
            return array_unique($relatedConcepts);
        });
    }

    /**
     * Get related terms for a concept
     */
    protected function getRelatedTerms(string $concept): array
    {
        // This is a simplified version. In production, you might use:
        // - WordNet API
        // - ConceptNet API
        // - Domain-specific ontologies
        // - Machine learning models
        
        $relatedTerms = [];
        
        // Simple word association patterns
        $associations = [
            'marketing' => ['advertising', 'branding', 'promotion', 'customer', 'sales'],
            'programming' => ['coding', 'development', 'software', 'algorithm', 'debugging'],
            'business' => ['strategy', 'management', 'planning', 'growth', 'revenue'],
            'design' => ['user experience', 'interface', 'visual', 'layout', 'aesthetics'],
            'data' => ['analytics', 'statistics', 'database', 'information', 'analysis'],
        ];
        
        foreach ($associations as $key => $terms) {
            if (stripos($concept, $key) !== false) {
                $relatedTerms = array_merge($relatedTerms, $terms);
            }
        }
        
        return $relatedTerms;
    }

    /**
     * Format context for AI consumption
     */
    protected function formatContext(array $enrichedContext, array $relatedConcepts): string
    {
        $formatted = "## Relevant Information\n\n";
        
        foreach (array_slice($enrichedContext, 0, 3) as $index => $section) {
            $formatted .= "### Section " . ($index + 1) . " (Relevance: " . round($section['relevance_score'] * 100) . "%, Type: {$section['section_type']})\n";
            $formatted .= $section['content'] . "\n\n";
            
            if (!empty($section['key_concepts'])) {
                $formatted .= "**Key concepts**: " . implode(', ', array_slice($section['key_concepts'], 0, 5)) . "\n\n";
            }
        }
        
        if (!empty($relatedConcepts)) {
            $formatted .= "## Related Concepts\n";
            $formatted .= implode(', ', array_slice($relatedConcepts, 0, 10)) . "\n\n";
        }
        
        return $formatted;
    }

    /**
     * Calculate confidence score for the context
     */
    protected function calculateConfidenceScore(string $similar, string $query): float
    {
        $similarity = $this->calculateSimilarity($similar, $query);
        $contentQuality = $this->assessContentQuality($similar);
        $completeness = $this->assessCompleteness($similar, $query);
        
        return ($similarity * 0.4 + $contentQuality * 0.3 + $completeness * 0.3);
    }

    /**
     * Calculate similarity between texts
     */
    protected function calculateSimilarity(string $text1, string $text2): float
    {
        $words1 = array_unique($this->extractWords($text1));
        $words2 = array_unique($this->extractWords($text2));
        
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));
        
        return $union > 0 ? $intersection / $union : 0;
    }

    /**
     * Assess content quality
     */
    protected function assessContentQuality(string $content): float
    {
        $score = 0.5; // Base score
        
        // Length factor
        $wordCount = str_word_count($content);
        if ($wordCount > 100) $score += 0.2;
        if ($wordCount > 300) $score += 0.1;
        
        // Structure factor
        if ($this->hasGoodStructure($content)) $score += 0.1;
        
        // Information density
        if ($this->hasHighInformationDensity($content)) $score += 0.1;
        
        return min(1.0, $score);
    }

    /**
     * Check if content has good structure
     */
    protected function hasGoodStructure(string $content): bool
    {
        // Check for lists, headers, or clear organization
        return preg_match('/^\d+\.|^[-*]|^#{1,3}\s/m', $content) > 0;
    }

    /**
     * Check information density
     */
    protected function hasHighInformationDensity(string $content): bool
    {
        $words = $this->extractWords($content);
        $uniqueWords = array_unique($words);
        
        // High ratio of unique words indicates good information density
        return count($words) > 0 && (count($uniqueWords) / count($words)) > 0.6;
    }

    /**
     * Assess completeness of answer
     */
    protected function assessCompleteness(string $content, string $query): float
    {
        $queryTerms = $this->extractKeyConcepts($query);
        $contentTerms = $this->extractKeyConcepts($content);
        
        $coverage = 0;
        foreach ($queryTerms as $term) {
            if (in_array($term, $contentTerms) || 
                $this->findTermInText($term, $content)) {
                $coverage++;
            }
        }
        
        return count($queryTerms) > 0 ? $coverage / count($queryTerms) : 0;
    }

    /**
     * Find term in text (with fuzzy matching)
     */
    protected function findTermInText(string $term, string $text): bool
    {
        // Exact match
        if (stripos($text, $term) !== false) {
            return true;
        }
        
        // Partial match (for compound terms)
        $termParts = explode(' ', $term);
        $found = 0;
        foreach ($termParts as $part) {
            if (strlen($part) > 3 && stripos($text, $part) !== false) {
                $found++;
            }
        }
        
        return $found >= ceil(count($termParts) * 0.7); // 70% of term parts found
    }
}