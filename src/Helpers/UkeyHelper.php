<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Helpers;

use SilverStripe\ORM\DataObject;

/**
 * Helper for generating unique keys (Ukey) from a human-readable name.
 *
 * The generated key uses only uppercase ASCII letters, digits and underscores.
 * If the candidate key already exists in the given DataObject class, a numeric
 * suffix is appended until a unique value is found.
 *
 * Example: 'My Invoice Template' → 'MY_INVOICE_TEMPLATE'
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Helpers
 */
class UkeyHelper
{

    /**
     * Generates a unique Ukey for the given class from a name string.
     *
     * @param string $name       Source string (e.g. template name)
     * @param string $className  Fully-qualified DataObject class name to check for uniqueness
     * @param int    $excludeId  ID to exclude from the uniqueness check (for edits)
     * @return string
     */
    public static function generate(string $name, string $className, int $excludeId = 0): string
    {
        $base      = self::sanitise($name);
        $candidate = $base;
        $suffix    = 2;

        while (!self::isUnique($candidate, $className, $excludeId)) {
            $candidate = $base . '_' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Converts a string to a valid Ukey.
     *
     * Rules:
     * - Transliterated to ASCII
     * - Uppercased
     * - Only A–Z, 0–9, hyphens (-) and underscores (_) are kept
     * - All other characters are replaced with underscores
     * - Leading/trailing underscores and hyphens are trimmed
     * - Consecutive underscores/hyphens are collapsed to a single underscore
     *
     * Examples:
     *   'My Invoice Template' → 'MY_INVOICE_TEMPLATE'
     *   'Rechnungs-Vorlage #1' → 'RECHNUNGS-VORLAGE_1'
     *
     * @param string $name
     * @return string
     */
    public static function sanitise(string $name): string
    {
        // Transliterate to ASCII (ä→a, ü→u, etc.)
        $result = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        // Uppercase
        $result = strtoupper($result);
        // Replace anything that is not A-Z, 0-9, hyphen or underscore with underscore
        $result = preg_replace('/[^A-Z0-9\-_]+/', '_', $result) ?? '';
        // Collapse multiple consecutive separators into a single underscore
        $result = preg_replace('/[_\-]{2,}/', '_', $result) ?? '';
        // Trim leading/trailing separators
        return trim($result, '_-');
    }

    /**
     * Checks whether the given Ukey value is unique for the given class.
     *
     * @param string $ukey
     * @param string $className
     * @param int    $excludeId
     * @return bool
     */
    public static function isUnique(string $ukey, string $className, int $excludeId = 0): bool
    {
        $query = DataObject::get($className)->filter('Ukey', $ukey);

        if ($excludeId > 0) {
            $query = $query->exclude('ID', $excludeId);
        }

        return $query->count() === 0;
    }

}

