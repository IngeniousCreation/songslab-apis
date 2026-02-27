<?php

namespace App\Utils;

class ContentFilter
{
    /**
     * Check if content contains spam, URLs, or unwanted characters
     * 
     * @param string $content
     * @return array ['is_valid' => bool, 'reason' => string|null]
     */
    public static function validate(string $content): array
    {
        // Check for URLs
        if (self::containsUrl($content)) {
            return ['is_valid' => false, 'reason' => 'URLs are not allowed in comments'];
        }

        // Check for Russian characters (Cyrillic)
        if (self::containsRussian($content)) {
            return ['is_valid' => false, 'reason' => 'Russian characters are not allowed'];
        }

        // Check for Chinese characters
        if (self::containsChinese($content)) {
            return ['is_valid' => false, 'reason' => 'Chinese characters are not allowed'];
        }

        // Check for common spam words
        if (self::containsSpamWords($content)) {
            return ['is_valid' => false, 'reason' => 'Content contains prohibited words'];
        }

        return ['is_valid' => true, 'reason' => null];
    }

    /**
     * Check if content contains URLs
     */
    private static function containsUrl(string $content): bool
    {
        // Match http://, https://, www., and common TLDs
        $urlPattern = '/(https?:\/\/|www\.|[a-zA-Z0-9-]+\.(com|net|org|io|co|ru|cn|info|biz|xyz|top|online|site|club|shop|store|link))/i';
        return preg_match($urlPattern, $content) === 1;
    }

    /**
     * Check if content contains Russian (Cyrillic) characters
     */
    private static function containsRussian(string $content): bool
    {
        // Cyrillic Unicode range: \x{0400}-\x{04FF}
        return preg_match('/[\x{0400}-\x{04FF}]/u', $content) === 1;
    }

    /**
     * Check if content contains Chinese characters
     */
    private static function containsChinese(string $content): bool
    {
        // Chinese Unicode ranges: \x{4E00}-\x{9FFF} (CJK Unified Ideographs)
        return preg_match('/[\x{4E00}-\x{9FFF}]/u', $content) === 1;
    }

    /**
     * Check if content contains common spam words
     */
    private static function containsSpamWords(string $content): bool
    {
        $spamWords = [
            'viagra', 'cialis', 'casino', 'poker', 'lottery', 'winner',
            'click here', 'buy now', 'limited time', 'act now',
            'free money', 'make money', 'work from home', 'earn cash',
            'weight loss', 'lose weight', 'diet pills',
            'crypto', 'bitcoin', 'investment opportunity',
            'congratulations', 'you won', 'claim prize',
            'nigerian prince', 'inheritance', 'million dollars',
            'enlargement', 'pharmacy', 'prescription',
            'dating', 'singles', 'meet women', 'meet men',
            'replica', 'rolex', 'luxury watches',
            'seo services', 'increase traffic', 'backlinks',
        ];

        $contentLower = strtolower($content);
        
        foreach ($spamWords as $spamWord) {
            if (stripos($contentLower, $spamWord) !== false) {
                return true;
            }
        }

        return false;
    }
}

