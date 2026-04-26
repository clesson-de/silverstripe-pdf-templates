<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Models;

use Clesson\Silverstripe\PdfTemplates\Constants\PdfPageType;
use Clesson\Silverstripe\PdfTemplates\Forms\GridFieldConfig_PdfTemplatePagesInPdfTemplate;
use Clesson\Silverstripe\PdfTemplates\Forms\RectSizeField;
use Clesson\Silverstripe\PdfTemplates\Helpers\UkeyHelper;
use Clesson\Silverstripe\PdfTemplates\ORM\DBRectSize;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;

/**
 * Represents a PDF template with page size settings and a collection of pages.
 *
 * When a PdfTemplate is first saved, two pages are automatically created:
 * one LEFT page (hole-punch margin on the left) and one RIGHT page.
 * Optionally the user can add a COVER page which will always be first.
 *
 * @property string   $Name
 * @property string   $Ukey
 * @property string   $Description
 * @property float    $PageSizeWidth
 * @property float    $PageSizeHeight
 * @method DBRectSize PageSize()
 * @method HasManyList Pages()
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Models
 */
class PdfTemplate extends DataObject
{

    /**
     * @inheritdoc
     */
    private static string $table_name = 'PdfTemplates_PdfTemplate';

    /**
     * @inheritdoc
     */
    private static string $default_sort = 'Name ASC';

    /**
     * @inheritdoc
     */
    private static string $general_search_field = 'Name';

    /**
     * @inheritdoc
     */
    private static array $db = [
        'Name'        => 'Varchar(255)',
        'Ukey'        => 'Varchar(100)',
        'Description' => 'Text',
        'PageSize'    => 'Clesson\Silverstripe\PdfTemplates\ORM\DBRectSize',
    ];

    /**
     * @inheritdoc
     */
    private static array $indexes = [
        'Ukey' => [
            'type'    => 'unique',
            'columns' => ['Ukey'],
        ],
    ];

    /**
     * @inheritdoc
     */
    private static array $has_many = [
        'Pages' => PdfTemplatePage::class,
    ];

    /**
     * @inheritdoc
     */
    private static array $summary_fields = [
        'Name',
        'Ukey',
        'PageSize.Nice',
    ];

    /**
     * @inheritdoc
     */
    public function populateDefaults(): void
    {
        parent::populateDefaults();

        $this->PageSizeWidth  = 210.0;
        $this->PageSizeHeight = 297.0;
    }

    /**
     * Auto-generates the Ukey from Name when empty, and creates default pages on first write.
     *
     * @return void
     */
    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        if (!$this->Ukey && $this->Name) {
            $this->Ukey = UkeyHelper::generate($this->Name, static::class);
        }
    }

    /**
     * Creates the default LEFT and RIGHT pages after the first write.
     *
     * @return void
     */
    public function onAfterWrite(): void
    {
        parent::onAfterWrite();

        if ($this->isChanged('ID') || $this->Pages()->count() === 0) {
            $this->createDefaultPages();
        }
    }

    /**
     * Creates the two default inner pages (LEFT and RIGHT).
     *
     * @return void
     */
    protected function createDefaultPages(): void
    {
        if ($this->Pages()->count() > 0) {
            return;
        }

        // LEFT page — wide left margin for hole punch
        /** @var PdfTemplatePage $left */
        $left = PdfTemplatePage::create();
        $left->PageType    = PdfPageType::LEFT;
        $left->MarginTop   = 20.0;
        $left->MarginRight = 15.0;
        $left->MarginBottom = 20.0;
        $left->MarginLeft  = 25.0;
        $left->Sort        = 1;
        $left->PdfTemplateID = $this->ID;
        $left->write();

        // RIGHT page — wide right margin for hole punch
        /** @var PdfTemplatePage $right */
        $right = PdfTemplatePage::create();
        $right->PageType    = PdfPageType::RIGHT;
        $right->MarginTop   = 20.0;
        $right->MarginRight = 25.0;
        $right->MarginBottom = 20.0;
        $right->MarginLeft  = 15.0;
        $right->Sort        = 2;
        $right->PdfTemplateID = $this->ID;
        $right->write();
    }

    /**
     * @inheritdoc
     */
    public function fieldLabels($includerelations = true): array
    {
        $labels = parent::fieldLabels($includerelations);
        $ns     = 'Clesson\\Silverstripe\\PdfTemplates\\Common';
        $labels['Name']        = _t(__CLASS__ . '.NAME', 'Name');
        $labels['Ukey']        = _t(__CLASS__ . '.UKEY', 'Unique key');
        $labels['Description'] = _t(__CLASS__ . '.DESCRIPTION', 'Description');
        $labels['PageSize']    = _t(__CLASS__ . '.PAGE_SIZE', 'Page size (mm)');
        $labels['Pages']       = _t(__CLASS__ . '.PAGES', 'Pages');
        $labels['ID']          = _t($ns . '.ID', 'ID');
        $labels['Created']     = _t($ns . '.CREATED', 'Created');
        $labels['LastEdited']  = _t($ns . '.LAST_EDITED', 'Last edited');
        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function validate(): ValidationResult
    {
        $result = parent::validate();

        if (!$this->Name) {
            $result->addError(_t(
                'SilverStripe\\Forms\\Form.FIELDISREQUIRED',
                '{name} is required',
                ['name' => $this->fieldLabel('Name')]
            ));
        }

        if (!$this->Ukey) {
            $result->addError(_t(
                'SilverStripe\\Forms\\Form.FIELDISREQUIRED',
                '{name} is required',
                ['name' => $this->fieldLabel('Ukey')]
            ));
        } elseif (!preg_match('/^[A-Z0-9_\-]+$/', $this->Ukey)) {
            $result->addError(_t(
                __CLASS__ . '.UKEY_INVALID_CHARS',
                'The unique key may only contain uppercase letters (A–Z), digits (0–9), hyphens (-) and underscores (_).'
            ));
        } elseif (
            PdfTemplate::get()
                ->filter('Ukey', $this->Ukey)
                ->exclude('ID', (int) $this->ID)
                ->count() > 0
        ) {
            $result->addError(_t(
                __CLASS__ . '.UKEY_NOT_UNIQUE',
                'The unique key "{key}" is already in use.',
                ['key' => $this->Ukey]
            ));
        }

        // At least one LEFT page is required — only checked once the record
        // is in the database, because pages are created after the first write.
        if ($this->isInDB()) {
            $hasLeftPage = $this->Pages()
                ->filter('PageType', PdfPageType::LEFT)
                ->count() > 0;

            if (!$hasLeftPage) {
                $result->addError(_t(
                    __CLASS__ . '.REQUIRES_LEFT_PAGE',
                    'The template must contain at least one page of type "{type}".',
                    ['type' => PdfPageType::label(PdfPageType::LEFT)]
                ));
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCMSFields(): FieldList
    {
        $fields = FieldList::create(TabSet::create('Root'));

        /** @var TextField $nameField */
        $nameField = TextField::create('Name', $this->fieldLabel('Name'));

        /** @var TextField $ukeyField */
        $ukeyField = TextField::create('Ukey', $this->fieldLabel('Ukey'));
        $ukeyField->setDescription(_t(__CLASS__ . '.UKEY_DESCRIPTION', 'Auto-generated from the name. Allowed characters: uppercase letters (A–Z), digits (0–9), hyphens (-) and underscores (_).'));

        /** @var TextareaField $descriptionField */
        $descriptionField = TextareaField::create('Description', $this->fieldLabel('Description'));
        $descriptionField->setRows(3);

        /** @var RectSizeField $pageSizeField */
        $pageSizeField = RectSizeField::create('PageSize', $this->fieldLabel('PageSize'));
        $pageSizeField->setValue($this->dbObject('PageSize'));
        $pageSizeField->setPresetSizes([
            'A4 Portrait'     => ['width' => 210.0, 'height' => 297.0],
            'A4 Landscape'    => ['width' => 297.0, 'height' => 210.0],
            'A3 Portrait'     => ['width' => 297.0, 'height' => 420.0],
            'A3 Landscape'    => ['width' => 420.0, 'height' => 297.0],
            'A5 Portrait'     => ['width' => 148.0, 'height' => 210.0],
            'A5 Landscape'    => ['width' => 210.0, 'height' => 148.0],
            'Letter Portrait' => ['width' => 215.9, 'height' => 279.4],
            'Letter Landscape'=> ['width' => 279.4, 'height' => 215.9],
            'Legal Portrait'  => ['width' => 215.9, 'height' => 355.6],
            'Legal Landscape' => ['width' => 355.6, 'height' => 215.9],
        ]);

        $fields->addFieldsToTab('Root.Main', [
            $nameField,
            $ukeyField,
            $descriptionField,
            $pageSizeField,
        ]);

        if ($this->isInDB()) {
            /** @var GridField $pagesField */
            $pagesField = GridField::create(
                'Pages',
                $this->fieldLabel('Pages'),
                $this->Pages()->sort('Sort ASC'),
                GridFieldConfig_PdfTemplatePagesInPdfTemplate::create()
            );
            $fields->addFieldToTab('Root.Main', $pagesField);
        }

        return $fields;
    }

}

