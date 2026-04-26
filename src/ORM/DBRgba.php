<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\ORM;

use Clesson\Silverstripe\PdfTemplates\Forms\RgbaField;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\FieldType\DBComposite;

/**
 * A composite DB field storing an RGBA colour value.
 *
 * Usage in $db:
 * ```php
 * 'FontColor' => 'Rgba'
 * ```
 * This creates four columns:
 * - FontColorR   TINYINT (0–255)
 * - FontColorG   TINYINT (0–255)
 * - FontColorB   TINYINT (0–255)
 * - FontColorA   DECIMAL(4,3) (0.000–1.000, default 1.000 = fully opaque)
 *
 * Retrieve values:
 * ```php
 * $color = $record->dbObject('FontColor');
 * $color->getCssRgba();  // 'rgba(0,0,0,1)'
 * $color->getCssHex();   // '#000000'
 * ```
 *
 * @property int   $R
 * @property int   $G
 * @property int   $B
 * @property float $A
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage ORM
 */
class DBRgba extends DBComposite
{

    /**
     * @inheritdoc
     */
    private static array $composite_db = [
        'R' => 'Int',
        'G' => 'Int',
        'B' => 'Int',
        'A' => 'Decimal(4,3)',
    ];

    /**
     * Returns the red channel value (0–255), or null if not set.
     *
     * @return int|null
     */
    public function getR(): ?int
    {
        $v = $this->getField('R');
        return $v !== null ? (int) $v : null;
    }

    /**
     * Returns the green channel value (0–255), or null if not set.
     *
     * @return int|null
     */
    public function getG(): ?int
    {
        $v = $this->getField('G');
        return $v !== null ? (int) $v : null;
    }

    /**
     * Returns the blue channel value (0–255), or null if not set.
     *
     * @return int|null
     */
    public function getB(): ?int
    {
        $v = $this->getField('B');
        return $v !== null ? (int) $v : null;
    }

    /**
     * Returns the alpha value (0.0–1.0), or 1.0 when not set.
     *
     * @return float
     */
    public function getA(): float
    {
        $v = $this->getField('A');
        return $v !== null ? (float) $v : 1.0;
    }

    /**
     * Sets the red channel (0–255).
     *
     * @param int $value
     * @return static
     */
    public function setR(int $value): static
    {
        $this->setField('R', max(0, min(255, $value)));
        return $this;
    }

    /**
     * Sets the green channel (0–255).
     *
     * @param int $value
     * @return static
     */
    public function setG(int $value): static
    {
        $this->setField('G', max(0, min(255, $value)));
        return $this;
    }

    /**
     * Sets the blue channel (0–255).
     *
     * @param int $value
     * @return static
     */
    public function setB(int $value): static
    {
        $this->setField('B', max(0, min(255, $value)));
        return $this;
    }

    /**
     * Sets the alpha channel (0.0–1.0).
     *
     * @param float $value
     * @return static
     */
    public function setA(float $value): static
    {
        $this->setField('A', round(max(0.0, min(1.0, $value)), 3));
        return $this;
    }

    /**
     * Returns true when all channels are set.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->getField('R') !== null
            && $this->getField('G') !== null
            && $this->getField('B') !== null;
    }

    /**
     * Returns the colour as a CSS rgba() string, e.g. 'rgba(255,128,0,0.5)'.
     * Returns an empty string when the colour is not set.
     *
     * @return string
     */
    public function getCssRgba(): string
    {
        if (!$this->exists()) {
            return '';
        }

        return 'rgba(' . $this->getR() . ',' . $this->getG() . ',' . $this->getB() . ',' . $this->getA() . ')';
    }

    /**
     * Returns the colour as a CSS hex string, e.g. '#ff8000'.
     * The alpha channel is ignored. Returns an empty string when not set.
     *
     * @return string
     */
    public function getCssHex(): string
    {
        if (!$this->exists()) {
            return '';
        }

        return sprintf('#%02x%02x%02x', $this->getR(), $this->getG(), $this->getB());
    }

    /**
     * Returns a human-readable representation, e.g. 'rgba(0,0,0,1)'.
     *
     * @return string
     */
    public function Nice(): string
    {
        return $this->getCssRgba();
    }

    /**
     * Returns an RgbaField for editing this composite field in the CMS.
     *
     * @param string|null $title
     * @param array       $params
     * @return FormField
     */
    public function scaffoldFormField(?string $title = null, array $params = []): FormField
    {
        return RgbaField::create($this->getName(), $title)->setValue($this);
    }

}

