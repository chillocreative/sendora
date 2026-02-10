<?php

namespace App\Services;

class PlaybookSanitizer
{
    /**
     * Sanitize playbook content for safe storage and prompt injection.
     */
    public static function sanitize(string $content): string
    {
        // 1. Strip delimiter markers that could break the prompt structure
        $content = str_ireplace('=== PLAYBOOK START ===', '', $content);
        $content = str_ireplace('=== PLAYBOOK END ===', '', $content);
        $content = str_ireplace('=== RESPONSE FORMAT ===', '', $content);
        $content = str_ireplace('=== RULES ===', '', $content);

        // 2. Strip common prompt injection patterns
        $injectionPatterns = [
            '/ignore\s+(all\s+)?previous\s+instructions/i',
            '/ignore\s+(all\s+)?above\s+instructions/i',
            '/disregard\s+(all\s+)?previous/i',
            '/you\s+are\s+now\s+(a|an)\s+/i',
            '/new\s+system\s+prompt/i',
            '/override\s+system\s+prompt/i',
            '/\bsystem\s*:\s*/i',
            '/\bassistant\s*:\s*/i',
            '/\buser\s*:\s*/i',
        ];

        foreach ($injectionPatterns as $pattern) {
            $content = preg_replace($pattern, '[REMOVED]', $content);
        }

        // 3. Strip HTML script tags and event handlers
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
        $content = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);

        // 4. Normalize excessive whitespace (preserve markdown structure)
        $content = preg_replace('/\n{4,}/', "\n\n\n", $content);

        return trim($content);
    }

    /**
     * Validate markdown structure. Returns warnings (not blockers).
     */
    public static function validate(string $content): array
    {
        $warnings = [];

        if (!preg_match('/^#{1,6}\s+/m', $content)) {
            $warnings[] = 'No markdown headings found. Consider using # sections for better organization.';
        }

        $backtickCount = substr_count($content, '```');
        if ($backtickCount % 2 !== 0) {
            $warnings[] = 'Unclosed code block detected (unmatched ``` markers).';
        }

        return $warnings;
    }
}
