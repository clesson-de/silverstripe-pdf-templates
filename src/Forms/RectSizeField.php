<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Forms;

use Clesson\Silverstripe\PdfTemplates\ORM\DBRectSize;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\NumericField;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * A composite form field for editing a DBRectSize value.
 *
 * Renders two child NumericFields: Width and Height.
 *
 * Usage:
 * ```php
 * RectSizeField::create('PageSize', $this->fieldLabel('PageSize'))
 * ```
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Forms
 */
class RectSizeField extends FormField
{

    /**
     * Child field for the width.
     */
    protected NumericField $fieldWidth;

    /**
     * Child field for the height.
     */
    protected NumericField $fieldHeight;

    /**
     * Optional preset sizes for quick selection.
     *
     * @var array<string, array{width: float, height: float}>
     */
    protected array $presetSizes = [];

    /**
     * Builds the composite field with two NumericField children.
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
        $this->fieldWidth  = clone $this->fieldWidth;
        $this->fieldHeight = clone $this->fieldHeight;
    }

    /**
     * Initialises the two child NumericFields.
     *
     * @return void
     */
    protected function buildChildFields(): void
    {
        $name = $this->getName();

        $this->fieldWidth = NumericField::create($name . '[Width]', _t(__CLASS__ . '.LABEL_WIDTH', 'Width (mm)'))
            ->setScale(2)
            ->setAttribute('min', 0)
            ->setAttribute('step', 0.1)
            ->setAttribute('placeholder', 'e.g. 210');

        $this->fieldHeight = NumericField::create($name . '[Height]', _t(__CLASS__ . '.LABEL_HEIGHT', 'Height (mm)'))
            ->setScale(2)
            ->setAttribute('min', 0)
            ->setAttribute('step', 0.1)
            ->setAttribute('placeholder', 'e.g. 297');
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

        if (isset($this->fieldWidth)) {
            $this->fieldWidth->setName($name . '[Width]');
            $this->fieldHeight->setName($name . '[Height]');
        }

        return $this;
    }

    /**
     * Sets preset sizes for quick selection via dropdown.
     *
     * Example:
     * ```php
     * $field->setPresetSizes([
     *     'A4 Portrait'  => ['width' => 210, 'height' => 297],
     *     'A4 Landscape' => ['width' => 297, 'height' => 210],
     *     'Letter'       => ['width' => 215.9, 'height' => 279.4],
     * ]);
     * ```
     *
     * @param array<string, array{width: float, height: float}> $presets
     * @return static
     */
    public function setPresetSizes(array $presets): static
    {
        $this->presetSizes = $presets;
        return $this;
    }

    /**
     * Sets the field value from a DBRectSize instance or an associative array.
     *
     * Accepted array keys: Width, Height
     *
     * @param mixed      $value
     * @param mixed|null $data
     * @return static
     */
    public function setValue($value, $data = null): static
    {
        if ($value instanceof DBRectSize) {
            $this->value = [
                'Width'  => $value->getWidth(),
                'Height' => $value->getHeight(),
            ];
        } elseif (is_array($value)) {
            $this->value = [
                'Width'  => isset($value['Width'])  ? (float) $value['Width']  : null,
                'Height' => isset($value['Height']) ? (float) $value['Height'] : null,
            ];
        } else {
            $this->value = ['Width' => null, 'Height' => null];
        }

        $this->fieldWidth->setValue($this->value['Width']);
        $this->fieldHeight->setValue($this->value['Height']);

        return $this;
    }

    /**
     * Reads submission data and populates the child fields.
     *
     * @param mixed      $value
     * @param mixed|null $data
     * @return static
     */
    public function setSubmittedValue($value, $data = null): static
    {
        return $this->setValue($value, $data);
    }

    /**
     * Saves the composite value back into the DataObject.
     *
     * @param DataObjectInterface $record
     * @return void
     */
    public function saveInto(DataObjectInterface $record): void
    {
        $name = $this->getName();
        $record->setCastedField($name . 'Width', (float) ($this->value['Width'] ?? 0));
        $record->setCastedField($name . 'Height', (float) ($this->value['Height'] ?? 0));
    }

    /**
     * Renders the composite field as two side-by-side NumericFields with optional preset dropdown.
     *
     * @param array $properties
     * @return DBHTMLText
     */
    public function Field($properties = []): DBHTMLText
    {
        $name = $this->getName();
        $widthFieldId = $this->fieldWidth->ID();
        $heightFieldId = $this->fieldHeight->ID();

        $output = '<div class="rect-size-field">';

        // Preset dropdown if presets are defined
        if (!empty($this->presetSizes)) {
            $output .= '<div style="margin-bottom:1rem;">';
            $output .= '<label>' . _t(__CLASS__ . '.LABEL_PRESET', 'Quick select') . '</label>';
            $output .= '<select class="rect-size-preset" data-width-field="' . $widthFieldId . '" data-height-field="' . $heightFieldId . '" style="width:100%;max-width:300px;">';
            $output .= '<option value="">— ' . _t(__CLASS__ . '.CHOOSE_PRESET', 'Choose preset size') . ' —</option>';

            foreach ($this->presetSizes as $label => $size) {
                $output .= sprintf(
                    '<option value=\'%s\'>%s (%s × %s)</option>',
                    json_encode($size),
                    htmlspecialchars($label),
                    $size['width'],
                    $size['height']
                );
            }

            $output .= '</select>';
            $output .= '</div>';

            // JavaScript to handle preset selection
            $output .= '<script>
                (function() {
                    var select = document.querySelector(\'select[data-width-field="' . $widthFieldId . '"]\');
                    if (select) {
                        select.addEventListener(\'change\', function() {
                            if (this.value) {
                                var size = JSON.parse(this.value);
                                var widthField = document.getElementById(\'' . $widthFieldId . '\');
                                var heightField = document.getElementById(\'' . $heightFieldId . '\');
                                if (widthField) widthField.value = size.width;
                                if (heightField) heightField.value = size.height;
                                // Trigger change event for validation
                                if (widthField) widthField.dispatchEvent(new Event(\'change\', {bubbles: true}));
                                if (heightField) heightField.dispatchEvent(new Event(\'change\', {bubbles: true}));
                            }
                        });
                    }
                })();
            </script>';
        }

        $output .= '<div style="display:flex;gap:1rem;align-items:flex-end;">';
        $output .= '<div>' . $this->fieldWidth->FieldHolder() . '</div>';
        $output .= '<div style="padding-bottom:.5rem;color:#666;">×</div>';
        $output .= '<div>' . $this->fieldHeight->FieldHolder() . '</div>';
        $output .= '</div>';
        $output .= '</div>';

        return DBHTMLText::create()->setValue($output);
    }

    /**
     * Returns the value as an array with Width and Height keys.
     *
     * @return array{Width: float|null, Height: float|null}
     */
    public function Value(): array
    {
        return is_array($this->value) ? $this->value : ['Width' => null, 'Height' => null];
    }

    /**
     * Validates that both width and height are positive numbers.
     *
     * @return ValidationResult
     */
    public function validate(): ValidationResult
    {
        $result = ValidationResult::create();
        $width  = $this->value['Width']  ?? null;
        $height = $this->value['Height'] ?? null;

        if ($width !== null && (float) $width <= 0) {
            $result->addError(_t(__CLASS__ . '.INVALID_WIDTH', 'Width must be greater than zero.'));
        }

        if ($height !== null && (float) $height <= 0) {
            $result->addError(_t(__CLASS__ . '.INVALID_HEIGHT', 'Height must be greater than zero.'));
        }

        return $result;
    }

}





