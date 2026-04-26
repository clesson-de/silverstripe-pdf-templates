<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Forms;

use Clesson\Silverstripe\PdfTemplates\ORM\DBRgba;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * A composite form field for editing a DBRgba value.
 *
 * Renders four child fields: R, G, B (integer 0–255) and A (decimal 0.0–1.0).
 * The field also renders a live CSS colour preview swatch driven by a small
 * inline script.
 *
 * Usage:
 * ```php
 * RgbaField::create('FontColor', $this->fieldLabel('FontColor'))
 * ```
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Forms
 */
class RgbaField extends FormField
{

    /**
     * Child field for red channel.
     */
    protected NumericField $fieldR;

    /**
     * Child field for green channel.
     */
    protected NumericField $fieldG;

    /**
     * Child field for blue channel.
     */
    protected NumericField $fieldB;

    /**
     * Child field for alpha channel.
     */
    protected NumericField $fieldA;

    /**
     * Builds the composite field with four NumericField children.
     *
     * @param string      $name
     * @param string|null $title
     * @param mixed       $value
     */
    public function __construct(string $name, ?string $title = null, mixed $value = null)
    {
        $this->setName($name);
        $this->buildChildFields();

        parent::__construct($name, $title, $value);
    }

    /**
     * Creates a deep clone including all child fields.
     *
     * @return void
     */
    public function __clone()
    {
        $this->fieldR = clone $this->fieldR;
        $this->fieldG = clone $this->fieldG;
        $this->fieldB = clone $this->fieldB;
        $this->fieldA = clone $this->fieldA;
    }

    /**
     * Initialises the four child NumericFields.
     *
     * @return void
     */
    protected function buildChildFields(): void
    {
        $name = $this->getName();

        $this->fieldR = NumericField::create($name . '[R]', _t(__CLASS__ . '.CHANNEL_R', 'R'))
            ->setScale(0)
            ->setAttribute('min', 0)
            ->setAttribute('max', 255)
            ->setAttribute('placeholder', '0–255');

        $this->fieldG = NumericField::create($name . '[G]', _t(__CLASS__ . '.CHANNEL_G', 'G'))
            ->setScale(0)
            ->setAttribute('min', 0)
            ->setAttribute('max', 255)
            ->setAttribute('placeholder', '0–255');

        $this->fieldB = NumericField::create($name . '[B]', _t(__CLASS__ . '.CHANNEL_B', 'B'))
            ->setScale(0)
            ->setAttribute('min', 0)
            ->setAttribute('max', 255)
            ->setAttribute('placeholder', '0–255');

        $this->fieldA = NumericField::create($name . '[A]', _t(__CLASS__ . '.CHANNEL_A', 'A'))
            ->setScale(3)
            ->setAttribute('min', 0)
            ->setAttribute('max', 1)
            ->setAttribute('step', 0.01)
            ->setAttribute('placeholder', '0–1');
    }

    /**
     * Updates child field names when the parent name changes.
     *
     * @param string $name
     * @return static
     */
    public function setName($name): static
    {
        parent::setName($name);

        if (isset($this->fieldR)) {
            $this->fieldR->setName($name . '[R]');
            $this->fieldG->setName($name . '[G]');
            $this->fieldB->setName($name . '[B]');
            $this->fieldA->setName($name . '[A]');
        }

        return $this;
    }

    /**
     * Sets the field value from a DBRgba instance or an associative array.
     *
     * Accepted array keys: R, G, B, A
     *
     * @param mixed      $value
     * @param mixed|null $data
     * @return static
     */
    public function setValue($value, $data = null): static
    {
        if ($value instanceof DBRgba) {
            $this->value = [
                'R' => $value->getR(),
                'G' => $value->getG(),
                'B' => $value->getB(),
                'A' => $value->getA(),
            ];
        } elseif (is_array($value)) {
            $this->value = [
                'R' => isset($value['R']) && $value['R'] !== '' ? (int)   $value['R'] : null,
                'G' => isset($value['G']) && $value['G'] !== '' ? (int)   $value['G'] : null,
                'B' => isset($value['B']) && $value['B'] !== '' ? (int)   $value['B'] : null,
                'A' => isset($value['A']) && $value['A'] !== '' ? (float) $value['A'] : 1.0,
            ];
        } else {
            $this->value = ['R' => null, 'G' => null, 'B' => null, 'A' => 1.0];
        }

        $this->fieldR->setValue($this->value['R']);
        $this->fieldG->setValue($this->value['G']);
        $this->fieldB->setValue($this->value['B']);
        $this->fieldA->setValue($this->value['A']);

        return $this;
    }

    /**
     * Sets the value from submitted form data.
     *
     * @param mixed      $value
     * @param mixed|null $data
     * @return static
     */
    public function setSubmittedValue($value, $data = null): static
    {
        if (is_array($value)) {
            $this->value = [
                'R' => isset($value['R']) && $value['R'] !== '' ? max(0, min(255, (int)   $value['R'])) : null,
                'G' => isset($value['G']) && $value['G'] !== '' ? max(0, min(255, (int)   $value['G'])) : null,
                'B' => isset($value['B']) && $value['B'] !== '' ? max(0, min(255, (int)   $value['B'])) : null,
                'A' => isset($value['A']) && $value['A'] !== '' ? max(0.0, min(1.0, (float) $value['A'])) : 1.0,
            ];
        } else {
            $this->value = ['R' => null, 'G' => null, 'B' => null, 'A' => 1.0];
        }

        $this->fieldR->setValue($this->value['R']);
        $this->fieldG->setValue($this->value['G']);
        $this->fieldB->setValue($this->value['B']);
        $this->fieldA->setValue($this->value['A']);

        return $this;
    }

    /**
     * Returns the current value as an associative array.
     *
     * @return array{R: int|null, G: int|null, B: int|null, A: float}
     */
    public function dataValue(): array
    {
        return $this->value ?? ['R' => null, 'G' => null, 'B' => null, 'A' => 1.0];
    }

    /**
     * Saves the RGBA values into the composite DB columns.
     *
     * @param DataObjectInterface $record
     * @return void
     */
    public function saveInto(DataObjectInterface $record): void
    {
        $name = $this->getName();

        $record->{$name . 'R'} = $this->value['R'] ?? null;
        $record->{$name . 'G'} = $this->value['G'] ?? null;
        $record->{$name . 'B'} = $this->value['B'] ?? null;
        $record->{$name . 'A'} = $this->value['A'] ?? 1.0;
    }

    /**
     * Renders the field as four inline inputs with a live colour swatch.
     *
     * @param array $properties
     * @return DBHTMLText
     */
    public function Field($properties = []): DBHTMLText
    {
        $r    = (int)   ($this->value['R'] ?? 0);
        $g    = (int)   ($this->value['G'] ?? 0);
        $b    = (int)   ($this->value['B'] ?? 0);
        $a    = (float) ($this->value['A'] ?? 1.0);
        $id   = $this->ID();

        $rField = $this->fieldR->Field();
        $gField = $this->fieldG->Field();
        $bField = $this->fieldB->Field();
        $aField = $this->fieldA->Field();

        $swatchStyle = 'display:inline-block;width:32px;height:32px;border:1px solid #ccc;'
            . 'border-radius:4px;vertical-align:middle;margin-left:8px;'
            . 'background:rgba(' . $r . ',' . $g . ',' . $b . ',' . $a . ');';

        $wrapperId = htmlspecialchars($id . '_wrapper');
        $swatchId  = htmlspecialchars($id . '_swatch');

        $html = '<div class="rgba-field" id="' . $wrapperId . '" style="display:flex;align-items:center;flex-wrap:wrap;gap:8px;">'
            . '<span style="display:flex;align-items:center;gap:4px;"><label style="font-size:0.85em;">' . htmlspecialchars(_t(__CLASS__ . '.CHANNEL_R', 'R')) . '</label>' . $rField . '</span>'
            . '<span style="display:flex;align-items:center;gap:4px;"><label style="font-size:0.85em;">' . htmlspecialchars(_t(__CLASS__ . '.CHANNEL_G', 'G')) . '</label>' . $gField . '</span>'
            . '<span style="display:flex;align-items:center;gap:4px;"><label style="font-size:0.85em;">' . htmlspecialchars(_t(__CLASS__ . '.CHANNEL_B', 'B')) . '</label>' . $bField . '</span>'
            . '<span style="display:flex;align-items:center;gap:4px;"><label style="font-size:0.85em;">' . htmlspecialchars(_t(__CLASS__ . '.CHANNEL_A', 'A')) . '</label>' . $aField . '</span>'
            . '<span class="rgba-swatch" id="' . $swatchId . '" style="' . $swatchStyle . '"></span>'
            . '</div>'
            . '<script>'
            . '(function(){'
            . 'var w=document.getElementById(' . json_encode($id . '_wrapper') . ');'
            . 'var s=document.getElementById(' . json_encode($id . '_swatch') . ');'
            . 'if(!w||!s)return;'
            . 'w.addEventListener("input",function(){'
            . 'var inputs=w.querySelectorAll("input");'
            . 'var vals=Array.from(inputs).map(function(i){return i.value||0;});'
            . 's.style.background="rgba("+vals[0]+","+vals[1]+","+vals[2]+","+vals[3]+")";'
            . '});'
            . '})();'
            . '</script>';

        $field = DBHTMLText::create();
        $field->setValue($html);
        return $field;
    }

    /**
     * Disables all child fields.
     *
     * @return static
     */
    public function setDisabled($disabled): static
    {
        parent::setDisabled($disabled);
        $this->fieldR->setDisabled($disabled);
        $this->fieldG->setDisabled($disabled);
        $this->fieldB->setDisabled($disabled);
        $this->fieldA->setDisabled($disabled);
        return $this;
    }

    /**
     * Makes all child fields read-only.
     *
     * @return static
     */
    public function performReadonlyTransformation(): static
    {
        $clone = clone $this;
        $clone->fieldR = $clone->fieldR->performReadonlyTransformation();
        $clone->fieldG = $clone->fieldG->performReadonlyTransformation();
        $clone->fieldB = $clone->fieldB->performReadonlyTransformation();
        $clone->fieldA = $clone->fieldA->performReadonlyTransformation();
        return $clone;
    }

}

