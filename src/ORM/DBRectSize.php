<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\ORM;

use SilverStripe\Forms\FormField;
use SilverStripe\ORM\FieldType\DBComposite;

/**
 * A composite DB field storing a rectangular size (width × height).
 *
 * Usage in $db:
 * ```php
 * 'PageSize' => 'RectSize'
 * ```
 * This creates two columns:
 * - PageSizeWidth   DECIMAL(8,2)
 * - PageSizeHeight  DECIMAL(8,2)
 *
 * Retrieve values:
 * ```php
 * $size = $record->dbObject('PageSize');
 * $size->getWidth();      // 210.0
 * $size->getHeight();     // 297.0
 * $size->Nice();          // '210 × 297'
 * $size->getFormat();     // 'portrait' | 'landscape' | 'square'
 * $size->isPortrait();    // true
 * $size->isLandscape();   // false
 * $size->isSquare();      // false
 * ```
 *
 * @property float $Width
 * @property float $Height
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage ORM
 */
class DBRectSize extends DBComposite
{

    /**
     * @inheritdoc
     */
    private static array $composite_db = [
        'Width'  => 'Decimal(8,2)',
        'Height' => 'Decimal(8,2)',
    ];

    /**
     * Returns the width, or null if not set.
     *
     * @return float|null
     */
    public function getWidth(): ?float
    {
        $v = $this->getField('Width');
        return $v !== null ? (float) $v : null;
    }

    /**
     * Returns the height, or null if not set.
     *
     * @return float|null
     */
    public function getHeight(): ?float
    {
        $v = $this->getField('Height');
        return $v !== null ? (float) $v : null;
    }

    /**
     * Sets the width.
     *
     * @param float $value
     * @return static
     */
    public function setWidth(float $value): static
    {
        $this->setField('Width', round(max(0.0, $value), 2));
        return $this;
    }

    /**
     * Sets the height.
     *
     * @param float $value
     * @return static
     */
    public function setHeight(float $value): static
    {
        $this->setField('Height', round(max(0.0, $value), 2));
        return $this;
    }

    /**
     * Returns true when both width and height are set and greater than zero.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->getField('Width') !== null
            && $this->getField('Height') !== null
            && (float) $this->getField('Width') > 0
            && (float) $this->getField('Height') > 0;
    }

    /**
     * Returns true when the dimensions form a square (width equals height).
     *
     * @return bool
     */
    public function isSquare(): bool
    {
        if (!$this->exists()) {
            return false;
        }
        return abs($this->getWidth() - $this->getHeight()) < 0.01;
    }

    /**
     * Returns true when width is greater than height (landscape orientation).
     *
     * @return bool
     */
    public function isLandscape(): bool
    {
        if (!$this->exists()) {
            return false;
        }
        return $this->getWidth() > $this->getHeight();
    }

    /**
     * Returns true when height is greater than width (portrait orientation).
     *
     * @return bool
     */
    public function isPortrait(): bool
    {
        if (!$this->exists()) {
            return false;
        }
        return $this->getHeight() > $this->getWidth();
    }

    /**
     * Returns the format as a string: 'square', 'landscape', or 'portrait'.
     *
     * @return string
     */
    public function getFormat(): string
    {
        if (!$this->exists()) {
            return '';
        }
        if ($this->isSquare()) {
            return 'square';
        }
        if ($this->isLandscape()) {
            return 'landscape';
        }
        return 'portrait';
    }

    /**
     * Returns a human-readable string, e.g. '210 × 297'.
     *
     * @return string
     */
    public function Nice(): string
    {
        if (!$this->exists()) {
            return '';
        }
        return $this->getWidth() . ' × ' . $this->getHeight();
    }

    /**
     * Returns a RectSizeField for editing this composite field in the CMS.
     *
     * @param string|null $title
     * @param array       $params
     * @return FormField
     */
    public function scaffoldFormField(?string $title = null, array $params = []): FormField
    {
        return \Clesson\Silverstripe\PdfTemplates\Forms\RectSizeField::create($this->getName(), $title)->setValue($this);
    }

}



