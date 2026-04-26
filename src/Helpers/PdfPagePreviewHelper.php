<?php

declare(strict_types=1);

namespace Clesson\Silverstripe\PdfTemplates\Helpers;

use Clesson\Silverstripe\PdfTemplates\Models\PdfTemplatePage;
use Clesson\Silverstripe\PdfTemplates\Models\PdfTemplate;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\Versioned;
use TCPDF;

/**
 * Generates a single-page preview PDF for a PdfTemplatePage.
 *
 * The preview contains the background PDF (if set) and dashed lines
 * that visualise the margin boundaries.
 *
 * @package Clesson\Silverstripe\PdfTemplates
 * @subpackage Helpers
 */
class PdfPagePreviewHelper
{
    /**
     * Generates a preview PDF for the given page and stores it as a
     * Silverstripe File asset.
     *
     * Returns the File record (already written) or null on failure.
     *
     * @param PdfTemplatePage $page
     * @return File|null
     */
    public static function generatePreview(PdfTemplatePage $page): ?File
    {
        $template = $page->PdfTemplate();
        $dimensions = self::resolvePageDimensions($template);
        $pageW = $dimensions['width'];
        $pageH = $dimensions['height'];

        $pdf = self::initPdf($pageW, $pageH);
        $pdf->AddPage('P', [$pageW, $pageH]);

        self::applyBackground($pdf, $page, $pageW, $pageH);
        self::drawDashedMargins($pdf, $page, $pageW, $pageH);

        return self::saveToAsset($pdf, $page);
    }

    /**
     * Resolves the page dimensions from the parent PdfTemplate.
     *
     * Falls back to A4 (210×297 mm) when the template has no size set.
     *
     * @param PdfTemplate|null $template
     * @return array{width: float, height: float}
     */
    private static function resolvePageDimensions(?PdfTemplate $template): array
    {
        $pageW = 210.0;
        $pageH = 297.0;

        if ($template && $template->exists()) {
            $pageSize = $template->dbObject('PageSize');
            if ($pageSize && $pageSize->exists()) {
                $pageW = $pageSize->getWidth();
                $pageH = $pageSize->getHeight();
            }
        }

        return ['width' => $pageW, 'height' => $pageH];
    }

    /**
     * Initialises and configures a TCPDF instance for preview generation.
     *
     * @param float $pageW
     * @param float $pageH
     * @return TCPDF
     */
    private static function initPdf(float $pageW, float $pageH): TCPDF
    {
        $pdf = new TCPDF('P', 'mm', [$pageW, $pageH], true, 'UTF-8', false);
        $pdf->SetCreator('PdfTemplatePage Preview');
        $pdf->SetAutoPageBreak(false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        return $pdf;
    }

    /**
     * Applies the background PDF layer to the current page.
     *
     * Reads the background PDF from the Silverstripe Asset Store via
     * getString(), writes it to a temp file and imports it via TCPDF.
     *
     * @param TCPDF           $pdf
     * @param PdfTemplatePage $page
     * @param float           $pageW
     * @param float           $pageH
     * @return void
     */
    private static function applyBackground(TCPDF $pdf, PdfTemplatePage $page, float $pageW, float $pageH): void
    {
        $bg = $page->BackgroundPdf();

        if (!$bg || !$bg->exists()) {
            return;
        }

        $content = $bg->getString();

        if (!$content) {
            return;
        }

        $tmpBg = tempnam(sys_get_temp_dir(), 'bg_pdf_');

        if ($tmpBg === false) {
            return;
        }

        file_put_contents($tmpBg, $content);

        try {
            $pdf->Image($tmpBg, 0, 0, $pageW, $pageH, 'PDF', '', 'N', false, 300, '', false, false, 0);
        } catch (\Exception) {
            // Silently ignore background PDF errors
        } finally {
            @unlink($tmpBg);
        }
    }

    /**
     * Draws dashed lines at the margin boundaries.
     *
     * @param TCPDF           $pdf
     * @param PdfTemplatePage $page
     * @param float           $pageW
     * @param float           $pageH
     * @return void
     */
    private static function drawDashedMargins(TCPDF $pdf, PdfTemplatePage $page, float $pageW, float $pageH): void
    {
        $mt = (float) $page->MarginTop;
        $mr = (float) $page->MarginRight;
        $mb = (float) $page->MarginBottom;
        $ml = (float) $page->MarginLeft;

        // Dashed line style: grey colour, dash pattern
        $dashStyle = [
            'width' => 0.3,
            'cap'   => 'butt',
            'join'  => 'miter',
            'dash'  => '3,2',
            'color' => [150, 150, 150],
        ];

        // Top margin line (horizontal)
        $pdf->Line(0, $mt, $pageW, $mt, $dashStyle);

        // Bottom margin line (horizontal)
        $pdf->Line(0, $pageH - $mb, $pageW, $pageH - $mb, $dashStyle);

        // Left margin line (vertical)
        $pdf->Line($ml, 0, $ml, $pageH, $dashStyle);

        // Right margin line (vertical)
        $pdf->Line($pageW - $mr, 0, $pageW - $mr, $pageH, $dashStyle);
    }

    /**
     * Writes the generated PDF to the assets folder and returns the File record.
     *
     * @param TCPDF           $pdf
     * @param PdfTemplatePage $page
     * @return File|null
     */
    private static function saveToAsset(TCPDF $pdf, PdfTemplatePage $page): ?File
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'page_preview_');

        if ($tmpFile === false) {
            return null;
        }

        try {
            $pdf->Output($tmpFile, 'F');
        } catch (\Exception) {
            @unlink($tmpFile);
            return null;
        }

        $filename = 'pdf-previews/page-preview-' . $page->ID . '.pdf';

        // Perform all File/Folder operations in LIVE reading mode
        // to avoid issues when called from within a CMS write context
        $file = Versioned::withVersionedMode(static function () use ($tmpFile, $filename, $page): ?File {
            Versioned::set_stage(Versioned::LIVE);

            $folder = Folder::find_or_make('pdf-previews');
            $file = PdfPagePreviewHelper::findExistingPreviewFile($page, $filename);

            if (!$file) {
                $file = File::create();
            }

            $file->setFromLocalFile($tmpFile, $filename);
            $file->ParentID = $folder->ID;
            $file->write();
            $file->publishSingle();

            return $file;
        });

        @unlink($tmpFile);

        return $file;
    }

    /**
     * Finds an existing preview File record via DB query or filename lookup.
     *
     * @param PdfTemplatePage $page
     * @param string          $filename
     * @return File|null
     */
    public static function findExistingPreviewFile(PdfTemplatePage $page, string $filename): ?File
    {
        // Try direct DB lookup for PreviewFileID (bypasses ORM cache)
        $tableName = $page->config()->get('table_name');
        $previewFileID = (int) DB::query(
            'SELECT "PreviewFileID" FROM "' . $tableName . '" WHERE "ID" = ' . (int) $page->ID
        )->value();

        if ($previewFileID) {
            $file = File::get()->byID($previewFileID);
            if ($file && $file->exists()) {
                return $file;
            }
        }

        // Fallback: find by filename
        $file = File::get()->filter('FileFilename', $filename)->first();
        if ($file && $file->exists()) {
            return $file;
        }

        return null;
    }
}










