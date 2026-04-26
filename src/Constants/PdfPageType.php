<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Constants;

/**
 * Constants for PDF template page types.
 *
 * LEFT  — inner page with hole-punch margin on the left side.
 * RIGHT — inner page with hole-punch margin on the right side.
 * COVER — optional cover/title page placed as the very first page.
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Constants
 */
class PdfPageType
{

    /** Inner page, hole-punch margin on the left. */
    public const LEFT = 'LEFT';

    /** Inner page, hole-punch margin on the right. */
    public const RIGHT = 'RIGHT';

    /** Optional cover / title page (first page). */
    public const COVER = 'COVER';

    /**
     * Returns all page types as a key-value map for use in DropdownField.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::LEFT  => self::label(self::LEFT),
            self::RIGHT => self::label(self::RIGHT),
            self::COVER => self::label(self::COVER),
        ];
    }

    /**
     * Returns the translated label for a given page type constant.
     *
     * @param string $value
     * @return string
     */
    public static function label(string $value): string
    {
        return _t(
            static::class . '.' . $value,
            match ($value) {
                self::LEFT  => 'Left (hole-punch left)',
                self::RIGHT => 'Right (hole-punch right)',
                self::COVER => 'Cover page',
                default     => $value,
            }
        );
    }

}

