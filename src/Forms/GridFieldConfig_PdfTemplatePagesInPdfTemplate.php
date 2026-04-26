<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Forms;

use Clesson\Silverstripe\PdfTemplates\Constants\PdfPageType;
use Clesson\Silverstripe\PdfTemplates\Models\PdfTemplatePage;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\ORM\FieldType\DBField;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * GridField configuration for displaying PdfTemplatePage records inside a PdfTemplate.
 *
 * Supports drag-and-drop reordering via GridFieldOrderableRows.
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Forms
 */
class GridFieldConfig_PdfTemplatePagesInPdfTemplate extends GridFieldConfig
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
        $this->addComponent(GridFieldOrderableRows::create('Sort'));
        $this->addComponent($dataColumns = GridFieldDataColumns::create());
        $this->addComponent(GridFieldEditButton::create());
        $this->addComponent(GridFieldDeleteAction::create());
        $this->addComponent(GridFieldDetailForm::create(null, $showPagination, $showAdd));

        $dataColumns->setDisplayFields([
            'PageType' => [
                'title'    => _t(PdfTemplatePage::class . '.PAGE_TYPE', 'Page type'),
                'callback' => function ($record, $column, $grid) {
                    return DBField::create_field('Varchar', PdfPageType::label($record->PageType));
                },
            ],
            'MarginLeft' => [
                'title'    => _t(PdfTemplatePage::class . '.MARGIN_LEFT', 'Left (mm)'),
                'callback' => function ($record, $column, $grid) {
                    return DBField::create_field('Decimal', $record->MarginLeft);
                },
            ],
            'MarginTop' => [
                'title'    => _t(PdfTemplatePage::class . '.MARGIN_TOP', 'Top (mm)'),
                'callback' => function ($record, $column, $grid) {
                    return DBField::create_field('Decimal', $record->MarginTop);
                },
            ],
            'MarginRight' => [
                'title'    => _t(PdfTemplatePage::class . '.MARGIN_RIGHT', 'Right (mm)'),
                'callback' => function ($record, $column, $grid) {
                    return DBField::create_field('Decimal', $record->MarginRight);
                },
            ],
            'MarginBottom' => [
                'title'    => _t(PdfTemplatePage::class . '.MARGIN_BOTTOM', 'Bottom (mm)'),
                'callback' => function ($record, $column, $grid) {
                    return DBField::create_field('Decimal', $record->MarginBottom);
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

