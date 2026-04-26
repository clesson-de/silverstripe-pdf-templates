<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Extensions;

use Clesson\Silverstripe\PdfTemplates\Forms\GridFieldConfig_PdfTemplatesInSiteConfig;
use Clesson\Silverstripe\PdfTemplates\Models\PdfTemplate;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;

/**
 * Extends SiteConfig with a "PDF Templates" tab containing a GridField
 * for managing PdfTemplate records.
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Extensions
 */
class SiteConfigExtension extends Extension
{

    /**
     * Adds the PDF Templates tab to the SiteConfig CMS form.
     *
     * @param FieldList $fields
     * @return void
     */
    public function updateCMSFields(FieldList $fields): void
    {
        /** @var GridField $pdfTemplatesField */
        $pdfTemplatesField = GridField::create(
            'PdfTemplates',
            _t(__CLASS__ . '.PDF_TEMPLATES', 'PDF templates'),
            PdfTemplate::get()->sort('Name ASC'),
            GridFieldConfig_PdfTemplatesInSiteConfig::create()
        );

        $tabName = 'PdfTemplates';
        $tabTitle = _t(__CLASS__ . '.TAB_PDF_TEMPLATES', 'PDF Templates');

        $fields->findOrMakeTab('Root.' . $tabName)->setTitle($tabTitle);
        $fields->addFieldToTab('Root.' . $tabName, $pdfTemplatesField);
    }

}

