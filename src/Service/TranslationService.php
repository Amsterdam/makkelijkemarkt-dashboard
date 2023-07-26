<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Summary of TranslationService.
 */
class TranslationService
{
    /**
     * Translates a flat array with words back to a flat array of words.
     * Based on a associative $translations array.
     *
     * @param array $words        indexed array of words that you want to translate ['book', 'table']
     * @param array $translations associative array of translations in English to another language ('book' => 'boek')
     *
     * @return array indexed array with translated words ['boek', 'tafel']
     */
    public static function translateArray(array $words, array $translations, bool $toEnglish = false)
    {
        return array_map(function ($word) use ($translations, $toEnglish) {
            return self::translateWord($word, $translations, $toEnglish);
        }, $words);
    }

    public static function translateWord(string $word, array $translations, bool $toEnglish = false)
    {
        if (true === $toEnglish) {
            $translations = array_flip($translations);
        }

        return $translations[$word];
    }
}
