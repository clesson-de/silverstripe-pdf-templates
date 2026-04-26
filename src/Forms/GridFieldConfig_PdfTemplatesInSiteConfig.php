<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Forms;

use Clesson\Silverstripe\PdfTemplates\Forms\GridField\GridFieldTestPdfAction;
use Clesson\Silverstripe\PdfTemplates\Models\PdfTemplate;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\ORM\FieldType\DBField;

/**
 * GridField configuration for displaying PdfTemplate records in the SiteConfig.
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Forms
 */
class GridFieldConfig_PdfTemplatesInSiteConfig extends GridFieldConfig
{

    /**
     * Initialises the GridField configuration with all required components.
     *
     * @param int|null  $itemsPerPage
     * @param bool|null $showPagination
     * @param bool|null $showAdd
     */
    public function __construct($itemsPerPage = null, $showPagination = null, $showAdd = null)
    {
        parent::__construct();

        $this->addComponent(GridFieldButtonRow::create('before'));
        $this->addComponent(GridFieldAddNewButton::create('buttons-before-right'));
        $this->addComponent(GridFieldSortableHeader::create());
        $this->addComponent(GridFieldFilterHeader::create());
        $this->addComponent($dataColumns = GridFieldDataColumns::create());
        $this->addComponent(new GridFieldTestPdfAction());
        $this->addComponent(GridFieldEditButton::create());
        $this->addComponent(GridFieldDeleteAction::create());
        $this->addComponent(GridFieldDetailForm::create(null, $showPagination, $showAdd));

        $dataColumns->setDisplayFields([
            'Name' => [
                'title'    => _t(PdfTemplate::class . '.NAME', 'Name'),
                'callback' => function ($record, $column, $grid) {
                    return DBField::create_field('Varchar', $record->Name);
                },
            ],
            'Ukey' => [
                'title'    => _t(PdfTemplate::class . '.UKEY', 'Unique key'),
                'callback' => function ($record, $column, $grid) {
                    return DBField::create_field('Varchar', $record->Ukey);
                },
            ],
            'PageSize.Nice' => [
                'title'    => _t(PdfTemplate::class . '.PAGE_SIZE', 'Page size (mm)'),
                'callback' => function ($record, $column, $grid) {
                    return DBField::create_field('Varchar', $record->dbObject('PageSize')->Nice());
                },
            ],
            'Created' => [
                'title'    => _t('Clesson\\Silverstripe\\PdfTemplates\\Common.CREATED', 'Created'),
                'callback' => function ($record, $column, $grid) {
                    return DBField::create_field('DBDatetime', $record->Created);
                },
            ],
            'LastEdited' => [
                'title'    => _t('Clesson\\Silverstripe\\PdfTemplates\\Common.LAST_EDITED', 'Last edited'),
                'callback' => function ($record, $column, $grid) {
                    return DBField::create_field('DBDatetime', $record->LastEdited);
                },
            ],
        ]);

        $this->extend('updateConfig');
    }

}

