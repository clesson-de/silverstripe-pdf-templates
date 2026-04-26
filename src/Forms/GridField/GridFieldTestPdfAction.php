<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Forms\GridField;

use Clesson\Silverstripe\PdfTemplates\Models\PdfTemplate;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridField_ColumnProvider;

/**
 * Adds a "Test PDF" action button to each row in the GridField.
 *
 * Clicking the button generates a test PDF with lorem ipsum content
 * and downloads it directly to the browser via a separate controller.
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Forms\GridField
 */
class GridFieldTestPdfAction implements GridField_ColumnProvider
{

    /**
     * @inheritdoc
     */
    public function augmentColumns($gridField, &$columns): void
    {
        if (!in_array('Actions', $columns)) {
            $columns[] = 'Actions';
        }
    }

    /**
     * @inheritdoc
     */
    public function getColumnAttributes($gridField, $record, $columnName): array
    {
        return ['class' => 'grid-field__col-compact'];
    }

    /**
     * @inheritdoc
     */
    public function getColumnMetadata($gridField, $columnName): array
    {
        if ($columnName === 'Actions') {
            return ['title' => ''];
        }
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getColumnsHandled($gridField): array
    {
        return ['Actions'];
    }

    /**
     * @inheritdoc
     */
    public function getColumnContent($gridField, $record, $columnName): string
    {
        if (!$record instanceof PdfTemplate || !$record->canView()) {
            return '';
        }

        // Build URL to a custom controller action
        $url = '/admin/pdf-templates/test/' . $record->ID;

        return sprintf(
            '<a href="%s" target="_blank" style="display:inline-block;padding:5px 10px;background:#0074e0;color:white;text-decoration:none;border-radius:3px;font-size:12px;">Test PDF</a>',
            $url
        );
    }


}

