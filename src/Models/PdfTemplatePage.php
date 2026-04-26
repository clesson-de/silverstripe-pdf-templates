<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Models;

use Clesson\Silverstripe\PdfTemplates\Constants\PdfPageType;
use Clesson\Silverstripe\PdfTemplates\Forms\RgbaField;
use Clesson\Silverstripe\PdfTemplates\Helpers\PdfPagePreviewHelper;
use Clesson\Silverstripe\PdfTemplates\ORM\DBRgba;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

/**
 * Represents a single page within a PDF template.
 *
 * Each page defines margins (in mm), an optional background PDF asset,
 * and an optional font colour (R, G, B).
 *
 * LEFT  pages have the hole-punch margin on the left side.
 * RIGHT pages have the hole-punch margin on the right side.
 * COVER is the optional title page placed before all inner pages.
 *
 * @property string $PageType
 * @property float  $MarginTop
 * @property float  $MarginRight
 * @property float  $MarginBottom
 * @property float  $MarginLeft
 * @property int    $FontSize
 * @property int    $FontColorR
 * @property int    $FontColorG
 * @property int    $FontColorB
 * @property float  $FontColorA
 * @property int    $Sort
 * @property int    $PdfTemplateID
 * @property int    $BackgroundPdfID
 * @property int    $PreviewFileID
 * @method PdfTemplate PdfTemplate()
 * @method File BackgroundPdf()
 * @method File PreviewFile()
 * @method DBRgba FontColor()
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Models
 */
class PdfTemplatePage extends DataObject
{

    /**
     * @inheritdoc
     */
    private static string $table_name = 'PdfTemplates_PdfTemplatePage';

    /**
     * @inheritdoc
     */
    private static string $default_sort = 'Sort ASC';

    /**
     * @inheritdoc
     */
    private static array $db = [
        'PageType'     => 'Varchar(10)',
        'MarginTop'    => 'Decimal(6,2)',
        'MarginRight'  => 'Decimal(6,2)',
        'MarginBottom' => 'Decimal(6,2)',
        'MarginLeft'   => 'Decimal(6,2)',
        'FontSize'     => 'Int',
        'FontColor'    => 'Clesson\Silverstripe\PdfTemplates\ORM\DBRgba',
        'Sort'         => 'Int',
    ];

    /**
     * @inheritdoc
     */
    private static array $has_one = [
        'PdfTemplate'   => PdfTemplate::class,
        'BackgroundPdf' => File::class,
        'PreviewFile'   => File::class,
    ];

    /**
     * @inheritdoc
     */
    private static array $owns = [
        'BackgroundPdf',
        'PreviewFile',
    ];

    /**
     * @inheritdoc
     */
    private static array $summary_fields = [
        'PageType',
        'MarginTop',
        'MarginRight',
        'MarginBottom',
        'MarginLeft',
    ];

    /**
     * @inheritdoc
     */
    public function populateDefaults(): void
    {
        parent::populateDefaults();

        $this->MarginTop    = 20.0;
        $this->MarginRight  = 15.0;
        $this->MarginBottom = 20.0;
        $this->MarginLeft   = 20.0;
        $this->FontSize     = 10;
        $this->FontColorR   = 0;
        $this->FontColorG   = 0;
        $this->FontColorB   = 0;
        $this->FontColorA   = 1.0;
    }

    /**
     * Regenerates the preview PDF after every write.
     *
     * Creates a new preview if none exists yet, or updates the existing one
     * when margins, background, or other settings have changed.
     *
     * @inheritdoc
     */
    public function onAfterWrite(): void
    {
        parent::onAfterWrite();

        try {
            $file = PdfPagePreviewHelper::generatePreview($this);
        } catch (\Throwable $e) {
            error_log('[PdfTemplatePage] Preview generation failed: ' . $e->getMessage());
            return;
        }

        if (!$file || !$file->exists()) {
            error_log('[PdfTemplatePage] Preview generation returned no file for page ID=' . $this->ID);
            return;
        }

        // Always update the relation via direct SQL to avoid infinite write loop
        $table = $this->config()->get('table_name');
        DB::prepared_query(
            'UPDATE "' . $table . '" SET "PreviewFileID" = ? WHERE "ID" = ?',
            [$file->ID, $this->ID]
        );
    }

    /**
     * @inheritdoc
     */
    public function fieldLabels($includerelations = true): array
    {
        $labels = parent::fieldLabels($includerelations);
        $ns     = 'Clesson\\Silverstripe\\PdfTemplates\\Common';
        $labels['PageType']      = _t(__CLASS__ . '.PAGE_TYPE', 'Page type');
        $labels['MarginTop']     = _t(__CLASS__ . '.MARGIN_TOP', 'Margin top (mm)');
        $labels['MarginRight']   = _t(__CLASS__ . '.MARGIN_RIGHT', 'Margin right (mm)');
        $labels['MarginBottom']  = _t(__CLASS__ . '.MARGIN_BOTTOM', 'Margin bottom (mm)');
        $labels['MarginLeft']    = _t(__CLASS__ . '.MARGIN_LEFT', 'Margin left (mm)');
        $labels['FontSize']      = _t(__CLASS__ . '.FONT_SIZE', 'Font size (pt)');
        $labels['FontColor']     = _t(__CLASS__ . '.FONT_COLOR', 'Font colour (R, G, B, A)');
        $labels['BackgroundPdf'] = _t(__CLASS__ . '.BACKGROUND_PDF', 'Background PDF');
        $labels['PreviewFile']   = _t(__CLASS__ . '.PREVIEW_FILE', 'Preview PDF');
        $labels['Sort']          = _t(__CLASS__ . '.SORT', 'Sort order');
        $labels['ID']            = _t($ns . '.ID', 'ID');
        $labels['Created']       = _t($ns . '.CREATED', 'Created');
        $labels['LastEdited']    = _t($ns . '.LAST_EDITED', 'Last edited');
        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function validate(): ValidationResult
    {
        $result = parent::validate();

        if (!$this->PageType) {
            $result->addError(_t(
                'SilverStripe\\Forms\\Form.FIELDISREQUIRED',
                '{name} is required',
                ['name' => $this->fieldLabel('PageType')]
            ));
        }


        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCMSFields(): FieldList
    {
        $fields = FieldList::create(TabSet::create('Root'));

        /** @var DropdownField $pageTypeField */
        $pageTypeField = DropdownField::create(
            'PageType',
            $this->fieldLabel('PageType'),
            PdfPageType::options()
        );

        /** @var NumericField $marginTopField */
        $marginTopField = NumericField::create('MarginTop', $this->fieldLabel('MarginTop'));
        $marginTopField->setScale(2);

        /** @var NumericField $marginRightField */
        $marginRightField = NumericField::create('MarginRight', $this->fieldLabel('MarginRight'));
        $marginRightField->setScale(2);

        /** @var NumericField $marginBottomField */
        $marginBottomField = NumericField::create('MarginBottom', $this->fieldLabel('MarginBottom'));
        $marginBottomField->setScale(2);

        /** @var NumericField $marginLeftField */
        $marginLeftField = NumericField::create('MarginLeft', $this->fieldLabel('MarginLeft'));
        $marginLeftField->setScale(2);

        /** @var RgbaField $fontColorField */
        $fontColorField = RgbaField::create('FontColor', $this->fieldLabel('FontColor'));
        $fontColorField->setValue($this->dbObject('FontColor'));

        /** @var NumericField $fontSizeField */
        $fontSizeField = NumericField::create('FontSize', $this->fieldLabel('FontSize'));
        $fontSizeField->setScale(0);
        $fontSizeField->setAttribute('min', 1);
        $fontSizeField->setAttribute('max', 144);
        $fontSizeField->setAttribute('placeholder', '10');

        /** @var UploadField $backgroundPdfField */
        $backgroundPdfField = UploadField::create('BackgroundPdf', $this->fieldLabel('BackgroundPdf'));
        $backgroundPdfField->setAllowedExtensions(['pdf']);
        $backgroundPdfField->setFolderName('pdf-backgrounds');
        $backgroundPdfField->setIsMultiUpload(false);
        $backgroundPdfField->setDescription(_t(
            __CLASS__ . '.BACKGROUND_PDF_DESCRIPTION',
            'Optional PDF file used as a background layer for this page.'
        ));

        // Build the right-hand preview panel (floated sidebar)
        $previewHtml = '<div class="pdfpage-preview-sidebar" style="'
            . 'float:right; width:260px; margin:0 0 1rem 1.5rem; '
            . 'padding:0.75rem; background:#f7f7f7; border:1px solid #ddd; border-radius:4px;'
            . '">';

        // Resolve PreviewFile directly from DB (the ID may have been set via
        // direct SQL in onAfterWrite, so the ORM cache could be stale)
        $previewFileID = (int) DB::query(
            'SELECT "PreviewFileID" FROM "' . $this->config()->get('table_name') . '" WHERE "ID" = ' . (int) $this->ID
        )->value();

        $previewFile = $previewFileID ? File::get()->byID($previewFileID) : null;
        if ($this->exists() && $previewFile && $previewFile->exists()) {
            $previewUrl = $previewFile->getURL() . '?t=' . strtotime($previewFile->LastEdited);
            $previewHtml .= '<iframe src="' . $previewUrl . '" '
                . 'style="width:100%;height:380px;border:1px solid #ccc;background:#fff;" '
                . 'title="' . _t(__CLASS__ . '.PDF_PREVIEW', 'PDF preview') . '">'
                . '</iframe>';
        } else {
            $previewHtml .= '<p style="color:#888;font-size:0.85em;text-align:center;">'
                . _t(__CLASS__ . '.PREVIEW_AFTER_SAVE', 'PDF preview available after first save.')
                . '</p>';
        }

        $previewHtml .= '</div>';

        /** @var LiteralField $previewSidebarField */
        $previewSidebarField = LiteralField::create('PreviewSidebar', $previewHtml);

        $fields->addFieldsToTab('Root.Main', [
            $previewSidebarField,
            $pageTypeField,
            $marginTopField,
            $marginRightField,
            $marginBottomField,
            $marginLeftField,
            $fontSizeField,
            $fontColorField,
            $backgroundPdfField,
        ]);

        return $fields;
    }

    /**
     * Returns the title of this page for display in GridField.
     *
     * @return string
     */
    public function getTitle(): string
    {
        $type = $this->PageType ? PdfPageType::label($this->PageType) : $this->PageType;
        return $type . ' — ' . $this->MarginLeft . '/' . $this->MarginTop . '/' . $this->MarginRight . '/' . $this->MarginBottom . ' mm';
    }

    /**
     * Deletes the associated preview file before this page is removed.
     *
     * @inheritdoc
     */
    public function onBeforeDelete(): void
    {
        parent::onBeforeDelete();

        $preview = $this->PreviewFile();
        if ($preview && $preview->exists()) {
            $preview->delete();
        }
    }

}

